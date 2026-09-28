<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductTypeResource;
use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    /**
     * Árbol completo de categorías → tipos de producto → atributos dinámicos (con opciones
     * y reglas del pivot). Se manda TODO de una vez —Flutter lo necesita completo para el
     * wizard de publicar y los filtros de búsqueda, y son ~13 tipos con ~60 atributos en
     * total, un payload chico— en vez de que la app tenga que pedir los atributos de cada
     * tipo por separado. Se cachea porque el catálogo cambia poco (Fase 1 §2/§3) y esta
     * ruta es pública y muy visitada.
     */
    public function categories()
    {
        $categories = Cache::remember('catalog.categories', now()->addHour(), function () {
            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with([
                    'productTypes' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                    'productTypes.attributes.options',
                ])
                ->get();
        });

        return CategoryResource::collection($categories);
    }

    /**
     * Atributos dinámicos de un tipo de producto puntual, con opciones y reglas del pivot.
     * Esta es la ruta que arma el formulario de características al publicar/filtrar.
     */
    public function productTypeAttributes(ProductType $productType)
    {
        $productType->load(['attributes.options']);

        return new ProductTypeResource($productType);
    }
}
