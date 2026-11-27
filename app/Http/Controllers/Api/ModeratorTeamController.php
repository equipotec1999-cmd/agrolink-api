<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Equipo de moderación. Ver, agregar y quitar moderadores es exclusivo de la cuenta dueña
 * (config agrolink.owner_email). Para agregar a alguien hace falta su correo y el código de
 * 6 dígitos que esa persona genera en su propia pantalla.
 */
class ModeratorTeamController extends Controller
{
    private const CODE_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    private function owner(Request $request): User
    {
        $user = $request->user();
        abort_unless($user->isOwner(), 403, 'Solo el dueño de AgroLink administra moderadores.');

        return $user;
    }

    private function audit(Request $request, string $action, User $target): void
    {
        AuditLog::create([
            'usuario_id' => $request->user()->id,
            'accion' => $action,
            'auditable_tipo' => 'user',
            'auditable_id' => $target->id,
            'cambios' => ['correo' => $target->correo],
            'direccion_ip' => $request->ip(),
            'agente_usuario' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }

    /** Lista de moderadores y administradores. */
    public function index(Request $request)
    {
        $owner = $this->owner($request);
        $team = User::role(['moderador', 'administrador'])
            ->whereNull('eliminado_en')
            ->orderBy('nombre')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->nombre.' '.($u->apellidos ?? '')),
                'email' => $u->correo,
                'roles' => $u->getRoleNames()->values(),
                'two_factor_enabled' => $u->hasTwoFactorEnabled(),
                'is_owner' => $u->id === $owner->id,
            ])
            ->values();

        return response()->json(['data' => $team]);
    }

    /** Quien quiere ser moderador genera un código de 6 dígitos y se lo dicta a la dueña. */
    public function inviteCode(Request $request)
    {
        $user = $request->user();
        abort_if($user->hasRole('moderador'), 422, 'Ya eres parte del equipo de moderación.');

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        DB::table('invitaciones_moderador')->updateOrInsert(
            ['usuario_id' => $user->id],
            [
                'codigo_hash' => Hash::make($code),
                'intentos' => 0,
                'expira_en' => Carbon::now()->addMinutes(self::CODE_MINUTES),
                'creado_en' => Carbon::now(),
            ],
        );

        return response()->json([
            'code' => $code,
            'email' => $user->correo,
            'expires_in_minutes' => self::CODE_MINUTES,
        ]);
    }

    /** La dueña agrega a alguien con su correo y el código de su pantalla. */
    public function store(Request $request)
    {
        $this->owner($request);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $target = User::whereRaw('lower(correo) = ?', [strtolower(trim($data['email']))])
            ->whereNull('eliminado_en')
            ->first();
        $row = $target ? DB::table('invitaciones_moderador')->where('usuario_id', $target->id)->first() : null;

        // Mismo mensaje si no existe la cuenta o no hay código, para no revelar correos.
        if (! $row) {
            throw ValidationException::withMessages(['code' => ['No hay un código activo para ese correo. Pídele que genere uno en su app.']]);
        }
        if (Carbon::parse($row->expira_en)->isPast() || $row->intentos >= self::MAX_ATTEMPTS) {
            DB::table('invitaciones_moderador')->where('id', $row->id)->delete();
            throw ValidationException::withMessages(['code' => ['El código venció. Pídele que genere uno nuevo.']]);
        }
        if (! Hash::check($data['code'], $row->codigo_hash)) {
            DB::table('invitaciones_moderador')->where('id', $row->id)->increment('intentos');
            throw ValidationException::withMessages(['code' => ['Código incorrecto.']]);
        }

        DB::table('invitaciones_moderador')->where('id', $row->id)->delete();
        $target->assignRole('moderador');
        $this->audit($request, 'moderator.added', $target);

        return response()->json(['message' => "{$target->nombre} ya es moderador."], 201);
    }

    /** Quita el rol de moderación (la dueña no puede quitarse a sí misma). */
    public function destroy(Request $request, User $user)
    {
        $owner = $this->owner($request);
        abort_if($user->id === $owner->id, 422, 'No puedes quitarte a ti misma del equipo.');
        abort_unless($user->hasAnyRole(['moderador', 'administrador']), 404);

        $user->removeRole('moderador');
        if ($user->hasRole('administrador')) {
            $user->removeRole('administrador');
        }
        // Cierra sus sesiones: al volver a entrar ya no ve moderación.
        $user->tokens()->delete();
        $this->audit($request, 'moderator.removed', $user);

        return response()->json(['message' => 'Se quitó del equipo de moderación.']);
    }
}
