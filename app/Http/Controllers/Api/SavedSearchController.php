<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\Request;

/** Búsquedas guardadas del usuario. `consulta` guarda los filtros tal como los arma la app. */
class SavedSearchController extends Controller
{
    private const MAX_PER_USER = 20;

    private function present(SavedSearch $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->nombre,
            'filters' => $s->consulta ?? [],
            'created_at' => $s->creado_en,
        ];
    }

    public function index(Request $request)
    {
        $items = SavedSearch::query()
            ->where('usuario_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($s) => $this->present($s));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'filters' => ['required', 'array'],
            'filters.query' => ['nullable', 'string', 'max:300'],
            'filters.category_id' => ['nullable', 'string', 'max:60'],
            'filters.product_type_id' => ['nullable', 'string', 'max:60'],
            'filters.max_distance_km' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'filters.verified_only' => ['nullable', 'boolean'],
            'filters.negotiable_only' => ['nullable', 'boolean'],
            'filters.lots_only' => ['nullable', 'boolean'],
            'filters.sort' => ['nullable', 'string', 'max:20'],
            'filters.attributes' => ['nullable', 'array', 'max:30'],
        ]);

        abort_if(
            SavedSearch::where('usuario_id', $request->user()->id)->count() >= self::MAX_PER_USER,
            422,
            'Llegaste al límite de '.self::MAX_PER_USER.' búsquedas guardadas. Elimina alguna para guardar otra.',
        );

        $search = SavedSearch::create([
            'usuario_id' => $request->user()->id,
            'nombre' => $data['name'],
            'consulta' => $data['filters'],
        ]);

        return response()->json(['data' => $this->present($search)], 201);
    }

    public function destroy(Request $request, SavedSearch $search)
    {
        abort_unless($search->usuario_id === $request->user()->id, 404);
        $search->delete();

        return response()->noContent();
    }
}
