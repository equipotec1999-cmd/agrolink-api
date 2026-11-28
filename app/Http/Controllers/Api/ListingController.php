<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\StoreListingMediaRequest;
use App\Http\Requests\Listing\StoreListingRequest;
use App\Http\Requests\Listing\UpdateListingRequest;
use App\Http\Resources\ListingMediaResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\ListingDocument;
use App\Models\ListingMedia;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listings)
    {
    }

    /**
     * Listado público: solo publicaciones visibles (status=published), sin importar
     * moderation_status (Fase 1 §5: se publica primero, se modera después). El buscador
     * de texto/atributos en forma completa (parser en lenguaje natural) llega en Fase 4
     * junto con el resto de la pantalla de búsqueda; aquí van los filtros básicos.
     */
    public function index(Request $request)
    {
        $this->listings->purgeExpiredDrafts();

        $query = $this->publicQuery();

        // Perfil público de un usuario: solo sus publicaciones.
        if ($request->filled('seller_id')) {
            $query->where('usuario_id', $request->integer('seller_id'));
        }

        if ($request->filled('product_type_id')) {
            $query->where('tipo_producto_id', $request->integer('product_type_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('productType', fn ($q) => $q->where('categoria_id', $request->integer('category_id')));
        }

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->whereRaw(
                "to_tsvector('spanish', immutable_unaccent(titulo) || ' ' || immutable_unaccent(coalesce(descripcion, ''))) @@ plainto_tsquery('spanish', immutable_unaccent(?))",
                [$term]
            );
        }

        // `per_page` lo pide la app (hasta 50); sin él, 20 como siempre. Tope para que nadie pida todo de golpe.
        $perPage = min(max($request->integer('per_page', 20), 1), 50);
        $listings = $query->orderByDesc('publicado_en')->paginate($perPage);

        return ListingResource::collection($listings);
    }

    /** Publicaciones visibles para todos (publicadas y de vendedores sin sanción), con lo que pinta una tarjeta. */
    private function publicQuery()
    {
        return Listing::query()
            ->with([
                'productType', 'user.sellerProfile', 'media',
                'location' => fn ($q) => $q->select(['publicacion_id', 'estado', 'municipio', 'codigo_postal'])->addSelect([
                    DB::raw('ST_Y(ubicacion_aproximada::geometry) as approx_lat'),
                    DB::raw('ST_X(ubicacion_aproximada::geometry) as approx_lng'),
                ]),
            ])
            ->where('estatus', 'publicada')
            // Vendedores sancionados o eliminados no aparecen en el feed.
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('usuarios as u')
                ->whereColumn('u.id', 'publicaciones.usuario_id')
                ->where(fn ($w) => $w->whereNotNull('u.eliminado_en')->orWhere('u.suspendido_hasta', '>', now())));
    }

    /**
     * Recomendados al final de una publicación: primero el mismo tipo de producto, luego la
     * misma categoría y al final lo más nuevo; nunca la misma publicación.
     */
    public function recommended(Request $request, Listing $listing)
    {
        $categoryId = $listing->productType?->categoria_id;
        $limit = min(max($request->integer('limit', 10), 1), 20);

        $items = $this->publicQuery()
            ->where('publicaciones.id', '!=', $listing->id)
            ->orderByRaw('CASE WHEN tipo_producto_id = ? THEN 0 ELSE 1 END', [$listing->tipo_producto_id])
            ->orderByRaw(
                'CASE WHEN tipo_producto_id IN (SELECT id FROM tipos_producto WHERE categoria_id = ?) THEN 0 ELSE 1 END',
                [$categoryId ?? 0]
            )
            ->orderByDesc('publicado_en')
            ->limit($limit)
            ->get();

        return ListingResource::collection($items);
    }

    /** Mis publicaciones en cualquier estado (borrador, publicada, rechazada...), las más nuevas primero. */
    public function mine(Request $request)
    {
        $this->listings->purgeExpiredDrafts($request->user()->id);

        $listings = Listing::query()
            ->with([
                'productType', 'user.sellerProfile', 'media', 'documents',
                'location' => fn ($q) => $q->select(['publicacion_id', 'estado', 'municipio', 'codigo_postal'])->addSelect([
                    DB::raw('ST_Y(ubicacion_aproximada::geometry) as approx_lat'),
                    DB::raw('ST_X(ubicacion_aproximada::geometry) as approx_lng'),
                ]),
            ])
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return ListingResource::collection($listings);
    }

    /**
     * Reenviar a revisión una publicación rechazada o suspendida (ya corregida), o reactivar una
     * archivada o vencida. Queda oculta (`en_revision`) hasta que un moderador la apruebe.
     */
    public function resubmit(Request $request, Listing $listing)
    {
        $this->authorize('publish', $listing);
        abort_unless(
            in_array($listing->estatus, ['rechazada', 'suspended', 'archivada', 'vencida'], true),
            422,
            'Solo se pueden reenviar publicaciones rechazadas, suspendidas, archivadas o vencidas.',
        );

        $listing->update(['estatus' => 'en_revision', 'estatus_moderacion' => 'pendiente']);

        return new ListingResource($listing->load(['productType', 'user.sellerProfile', 'media']));
    }

    public function store(StoreListingRequest $request)
    {
        $listing = $this->listings->create($request->user(), $request->validated());

        return (new ListingResource($listing))->response()->setStatusCode(201);
    }

    /**
     * Quién hace la petición en una ruta PÚBLICA. Estas rutas no pasan por `auth:sanctum`, y el guard por
     * defecto es `web` (sesión), así que `$request->user()` llegaba siempre vacío aunque la app mandara el
     * token: el dueño y los moderadores recibían 404 al abrir una publicación que aún no es pública.
     * Un token a medias (verificación en dos pasos pendiente) cuenta como visitante.
     */
    private function sessionUser(Request $request): ?User
    {
        $user = $request->user('sanctum');
        $token = $user?->currentAccessToken();
        if ($token && method_exists($token, 'can') && ! $token->can('*')) {
            return null;
        }

        return $user;
    }

    public function show(Request $request, Listing $listing)
    {
        $u = $this->sessionUser($request);
        $puedeVerOcultas = $u && ($u->can('moderate listings') || $u->can('moderate documents'));
        abort_unless($listing->estatus === 'publicada' || $u?->id === $listing->usuario_id || $puedeVerOcultas, 404);

        $listing->load([
            'productType',
            'user.sellerProfile',
            'media',
            'documents',
            'location' => fn ($q) => $q->select(['publicacion_id', 'estado', 'municipio', 'codigo_postal'])->addSelect([
                DB::raw('ST_Y(ubicacion_aproximada::geometry) as approx_lat'),
                DB::raw('ST_X(ubicacion_aproximada::geometry) as approx_lng'),
            ]),
        ]);

        return new ListingResource($listing);
    }

    public function update(UpdateListingRequest $request, Listing $listing)
    {
        $this->authorize('update', $listing);

        $wasPublic = $listing->estatus === 'publicada';
        $listing = $this->listings->update($listing, $request->validated());

        return (new ListingResource($listing->load(['media', 'documents'])))->additional([
            // La app avisa «tus cambios pasan a revisión» cuando esto es true.
            'sent_to_review' => $wasPublic && $listing->estatus === 'en_revision',
        ]);
    }

    public function destroy(Listing $listing)
    {
        $this->authorize('delete', $listing);

        $listing->delete(); // soft delete; no borra medios/documentos, solo oculta el listing.

        return response()->json(['message' => 'Publicación eliminada.']);
    }

    public function publish(Listing $listing)
    {
        $this->authorize('publish', $listing);

        $listing = $this->listings->publish($listing);

        return new ListingResource($listing);
    }

    public function archive(Listing $listing)
    {
        $this->authorize('update', $listing);

        $listing = $this->listings->archive($listing);

        return new ListingResource($listing);
    }

    /**
     * Sube UNA foto (multipart) y la agrega al final del carrusel del listing.
     * Solo el dueño puede subir (misma regla que editar). La BD nunca guarda el
     * binario (Fase 1 §16), solo la ruta del disco configurado (local/public en
     * desarrollo, S3/R2 en producción — ver config/filesystems.php).
     */
    public function uploadMedia(StoreListingMediaRequest $request, Listing $listing)
    {
        $this->authorize('update', $listing);
        abort_if($listing->media()->count() >= 10, 422, 'Puedes subir hasta 10 fotos por publicación.');

        $disk = config('filesystems.default');
        $path = $request->file('foto')->store("listings/{$listing->id}", $disk);
        abort_if($path === false, 502, 'No se pudo guardar la foto en el almacenamiento.');

        // getimagesize no siempre puede leer el archivo ya movido según el driver;
        // si falla, width/height quedan null en vez de inventar un valor.
        $dimensions = @getimagesize($request->file('foto')->getRealPath());

        $media = $listing->media()->create([
            'tipo' => 'foto',
            'ruta_almacenamiento' => $path,
            'posicion' => $listing->media()->count(),
            'ancho' => $dimensions[0] ?? null,
            'alto' => $dimensions[1] ?? null,
        ]);

        // Una foto nueva en una publicación visible se revisa antes de mostrarse.
        $sentToReview = false;
        if ($listing->estatus === 'publicada') {
            $listing->update(['estatus' => 'en_revision', 'estatus_moderacion' => 'pendiente', 'motivo_moderacion' => null]);
            $sentToReview = true;
        }

        return (new ListingMediaResource($media))->additional(['sent_to_review' => $sentToReview]);
    }

    public function destroyMedia(Listing $listing, ListingMedia $media)
    {
        $this->authorize('update', $listing);
        abort_unless($media->publicacion_id === $listing->id, 404);

        Storage::disk(config('filesystems.default'))->delete($media->ruta_almacenamiento);
        $media->delete();
        $listing->media()->orderBy('posicion')->get()->values()
            ->each(fn ($m, $i) => $m->posicion === $i ? null : $m->update(['posicion' => $i]));

        return response()->json(['message' => 'Foto eliminada.']);
    }

    /** Nuevo orden de las fotos (la primera es la portada). Recibe todos los ids de la publicación. */
    public function reorderMedia(Request $request, Listing $listing)
    {
        $this->authorize('update', $listing);
        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']]);

        $current = $listing->media()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $wanted = collect($data['ids'])->map(fn ($id) => (int) $id);
        abort_unless($wanted->sort()->values()->all() === $current, 422, 'La lista de fotos no coincide con la publicación.');

        DB::transaction(function () use ($listing, $wanted) {
            foreach ($wanted->values() as $i => $id) {
                $listing->media()->whereKey($id)->update(['posicion' => $i]);
            }
        });

        return ListingMediaResource::collection($listing->media()->orderBy('posicion')->get());
    }

    /** Máximo de documentos por publicación. */
    private const MAX_DOCUMENTS = 5;

    /**
     * Sube un documento (PDF o imagen) a la publicación. Queda «pendiente» hasta que un
     * moderador lo revise; nunca se da por válido solo por subirlo.
     */
    public function storeDocument(Request $request, Listing $listing)
    {
        $this->authorize('update', $listing);
        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'name' => ['nullable', 'string', 'max:120'],
        ], [
            'document.mimes' => 'El documento debe ser PDF o imagen (JPG, PNG, WEBP).',
            'document.max' => 'El documento pesa más de 10 MB.',
        ]);
        abort_if($listing->documents()->count() >= self::MAX_DOCUMENTS, 422, 'Puedes subir hasta '.self::MAX_DOCUMENTS.' documentos por publicación.');

        $file = $request->file('document');
        $path = $file->store("listings/{$listing->id}/docs", config('filesystems.default'));
        abort_if($path === false, 502, 'No se pudo guardar el documento.');

        $name = trim((string) ($data['name'] ?? '')) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = Str::limit(trim(preg_replace('/[\\x00-\\x1F]+/', ' ', $name)) ?: 'Documento', 120, '');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        $doc = $listing->documents()->create([
            'nombre' => $ext ? "{$name}.{$ext}" : $name,
            'ruta_almacenamiento' => $path,
            'estatus' => 'pendiente',
        ]);

        return response()->json(['data' => ListingResource::documentArray($doc, true)], 201);
    }

    public function destroyDocument(Listing $listing, ListingDocument $document)
    {
        $this->authorize('update', $listing);
        abort_unless($document->publicacion_id === $listing->id, 404);

        Storage::disk(config('filesystems.default'))->delete($document->ruta_almacenamiento);
        $document->delete();

        return response()->json(['message' => 'Documento eliminado.']);
    }

    /**
     * Archivo del documento por enlace firmado y temporal (lo genera ListingResource solo para el
     * dueño y moderación). Así la app lo abre en el navegador o visor de PDF del celular.
     */
    public function documentFile(ListingDocument $document)
    {
        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($disk->exists($document->ruta_almacenamiento), 404);

        return $disk->response($document->ruta_almacenamiento, $document->nombre, [
            'Cache-Control' => 'private, max-age=600',
        ], 'inline');
    }
}
