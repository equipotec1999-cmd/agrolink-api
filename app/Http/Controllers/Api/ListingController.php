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
                'location' => fn ($q) => $q->addSelect([
                    DB::raw('ST_Y(approx_location::geometry) as approx_lat'),
                    DB::raw('ST_X(approx_location::geometry) as approx_lng'),
                ]),
            ])
            ->where('status', 'published');

        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->integer('product_type_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('productType', fn ($q) => $q->where('category_id', $request->integer('category_id')));
        }

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->whereRaw(
                "to_tsvector('spanish', immutable_unaccent(title) || ' ' || immutable_unaccent(coalesce(description, ''))) @@ plainto_tsquery('spanish', immutable_unaccent(?))",
                [$term]
            );
        }

        $listings = $query->orderByDesc('published_at')->paginate(20);

        return ListingResource::collection($listings);
    }

    public function store(StoreListingRequest $request)
    {
        $listing = $this->listings->create($request->user(), $request->validated());

        return new ListingResource($listing);
    }

    public function show(Listing $listing)
    {
        abort_unless($listing->status === 'published' || $listing->user_id === request()->user()?->id, 404);

        $listing->load([
            'productType',
            'user.sellerProfile',
            'media',
            'documents',
            'location' => fn ($q) => $q->addSelect([
                DB::raw('ST_Y(approx_location::geometry) as approx_lat'),
                DB::raw('ST_X(approx_location::geometry) as approx_lng'),
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

        // getimagesize no siempre puede leer el archivo ya movido según el driver;
        // si falla, width/height quedan null en vez de inventar un valor.
        $dimensions = @getimagesize($request->file('photo')->getRealPath());

        $media = $listing->media()->create([
            'type' => 'photo',
            'storage_path' => $path,
            'position' => $listing->media()->count(),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ]);

        return new ListingMediaResource($media);
    }

    public function destroyMedia(Listing $listing, ListingMedia $media)
    {
        $this->authorize('update', $listing);
        abort_unless($media->listing_id === $listing->id, 404);

        Storage::disk(config('filesystems.default'))->delete($media->storage_path);
        $media->delete();

        return response()->json(['message' => 'Foto eliminada.']);
    }
}
