<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\StoreListingMediaRequest;
use App\Http\Requests\Listing\StoreListingRequest;
use App\Http\Requests\Listing\UpdateListingRequest;
use App\Http\Resources\ListingMediaResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\ListingMedia;
use App\Services\ListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $query = Listing::query()
            ->with([
                'productType', 'user.sellerProfile', 'media',
                'location' => fn ($q) => $q->select(['publicacion_id', 'estado', 'municipio', 'codigo_postal'])->addSelect([
                    DB::raw('ST_Y(ubicacion_aproximada::geometry) as approx_lat'),
                    DB::raw('ST_X(ubicacion_aproximada::geometry) as approx_lng'),
                ]),
            ])
            ->where('estatus', 'published');

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

        $listings = $query->orderByDesc('publicado_en')->paginate(20);

        return ListingResource::collection($listings);
    }

    /** Mis publicaciones en cualquier estado (borrador, publicada, rechazada...), las más nuevas primero. */
    public function mine(Request $request)
    {
        $listings = Listing::query()
            ->with([
                'productType', 'user.sellerProfile', 'media',
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
     * Reenviar a revisión una publicación rechazada o suspendida (ya corregida). Queda oculta
     * (`pending_review`) hasta que un moderador la apruebe.
     */
    public function resubmit(Request $request, Listing $listing)
    {
        $this->authorize('publish', $listing);
        abort_unless(in_array($listing->estatus, ['rejected', 'suspended'], true), 422, 'Solo se pueden reenviar publicaciones rechazadas o suspendidas.');

        $listing->update(['estatus' => 'pending_review', 'estatus_moderacion' => 'pending']);

        return new ListingResource($listing->load(['productType', 'user.sellerProfile', 'media']));
    }

    public function store(StoreListingRequest $request)
    {
        $listing = $this->listings->create($request->user(), $request->validated());

        return (new ListingResource($listing))->response()->setStatusCode(201);
    }

    public function show(Listing $listing)
    {
        abort_unless($listing->estatus === 'published' || $listing->usuario_id === request()->user()?->id, 404);

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

        $listing = $this->listings->update($listing, $request->validated());

        return new ListingResource($listing);
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

        $disk = config('filesystems.default');
        $path = $request->file('photo')->store("listings/{$listing->id}", $disk);
        abort_if($path === false, 502, 'No se pudo guardar la foto en el almacenamiento.');

        // getimagesize no siempre puede leer el archivo ya movido según el driver;
        // si falla, width/height quedan null en vez de inventar un valor.
        $dimensions = @getimagesize($request->file('photo')->getRealPath());

        $media = $listing->media()->create([
            'tipo' => 'photo',
            'ruta_almacenamiento' => $path,
            'posicion' => $listing->media()->count(),
            'ancho' => $dimensions[0] ?? null,
            'alto' => $dimensions[1] ?? null,
        ]);

        return new ListingMediaResource($media);
    }

    public function destroyMedia(Listing $listing, ListingMedia $media)
    {
        $this->authorize('update', $listing);
        abort_unless($media->publicacion_id === $listing->id, 404);

        Storage::disk(config('filesystems.default'))->delete($media->ruta_almacenamiento);
        $media->delete();

        return response()->json(['message' => 'Foto eliminada.']);
    }
}
