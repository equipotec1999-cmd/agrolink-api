<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function mine(Request $request)
    {
        return UserNotification::query()
            ->where('notificable_tipo', User::class)
            ->where('notificable_id', $request->user()->id);
    }

    /** Las 50 más recientes + cuántas hay sin leer (en total, no solo en esta página). */
    public function index(Request $request)
    {
        $items = $this->mine($request)->orderByDesc('creado_en')->limit(50)->get();

        return NotificationResource::collection($items)->additional([
            'unread_count' => $this->mine($request)->whereNull('leido_en')->count(),
        ]);
    }

    /** Idempotente; una notificación ajena (o inexistente) da 404. */
    public function read(Request $request, string $id)
    {
        $notification = $this->mine($request)->whereKey($id)->firstOrFail();
        if ($notification->leido_en === null) {
            $notification->update(['leido_en' => now()]);
        }

        return response()->noContent();
    }

    public function readAll(Request $request)
    {
        $this->mine($request)->whereNull('leido_en')->update(['leido_en' => now()]);

        return response()->noContent();
    }
}
