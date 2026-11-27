<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Aviso de privacidad y Términos de uso. Un solo texto (resources/legal/*.md) para la
 * app (JSON) y para la web (HTML, la liga pública que pide Google Play).
 */
class LegalController extends Controller
{
    public const DOCS = [
        'privacidad' => 'Aviso de privacidad',
        'terminos' => 'Términos y condiciones',
    ];

    public static function markdown(string $doc): string
    {
        $raw = file_get_contents(resource_path("legal/{$doc}.md"));
        $contacto = config('legal.contacto') ?: 'el correo de contacto publicado en la ficha de la app';

        return strtr($raw, [
            '{{version}}' => config('legal.version'),
            '{{responsable}}' => config('legal.responsable'),
            '{{domicilio}}' => config('legal.domicilio'),
            '{{contacto}}' => $contacto,
        ]);
    }

    /** GET /api/legal/{doc} — para mostrarlo dentro de la app. */
    public function show(string $doc)
    {
        abort_unless(isset(self::DOCS[$doc]), 404);

        return response()->json(['data' => [
            'doc' => $doc,
            'title' => self::DOCS[$doc],
            'version' => config('legal.version'),
            'markdown' => self::markdown($doc),
        ]])->header('Cache-Control', 'public, max-age=3600');
    }

    /** GET /privacidad y /terminos — página web pública. */
    public function page(string $doc)
    {
        abort_unless(isset(self::DOCS[$doc]), 404);
        $html = Str::markdown(self::markdown($doc), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $title = e(self::DOCS[$doc]).' — AgroLink';

        return response(<<<HTML
<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<style>
body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#121714;color:#ECEAE4;margin:0;line-height:1.6}
main{max-width:760px;margin:0 auto;padding:32px 20px 64px}
h1{font-size:1.7rem;color:#C9D4B8}h2{font-size:1.15rem;color:#C9D4B8;margin-top:2rem}
a{color:#86A98F}li{margin:.25rem 0}strong{color:#fff}
</style></head><body><main>{$html}</main></body></html>
HTML)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /** POST /api/me/accept-terms — acepta la versión vigente (cuentas creadas antes o tras un cambio). */
    public function accept(Request $request)
    {
        $data = $request->validate(['version' => ['required', 'string', 'max:20']]);
        abort_unless($data['version'] === config('legal.version'), 409,
            'Los textos cambiaron mientras los leías. Vuelve a abrirlos.');

        $request->user()->forceFill([
            'terminos_version' => $data['version'],
            'terminos_aceptados_en' => now(),
        ])->save();

        return response()->json(['message' => 'Gracias. Aceptaste el aviso de privacidad y los términos.']);
    }
}
