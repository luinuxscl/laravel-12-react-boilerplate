<?php

namespace App\Http\Controllers;

use App\Notifications\DemoNotification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class NotificationsController extends Controller
{
    /**
     * Listar notificaciones del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $perPage = (int) $request->integer('perPage', 10);
        $unreadPage = (int) $request->integer('unreadPage', 1);
        $allPage = (int) $request->integer('allPage', 1);

        $unread = $user->unreadNotifications()
            ->orderByDesc('created_at')
            ->paginate(perPage: $perPage, pageName: 'unreadPage', page: $unreadPage);

        $allQuery = $user->notifications()->orderByDesc('created_at');

        // Filters for "all" list
        $q = trim((string) $request->query('q', ''));
        $allOnlyUnread = filter_var($request->query('allOnlyUnread', false), FILTER_VALIDATE_BOOL);

        if ($allOnlyUnread) {
            $allQuery->whereNull('read_at');
        }

        if ($q !== '') {
            $allQuery->where(function ($sub) use ($q) {
                $sub->where('type', 'like', "%{$q}%")
                    ->orWhere('data->title', 'like', "%{$q}%")
                    ->orWhere('data->body', 'like', "%{$q}%");
            });
        }

        $all = $allQuery->paginate(perPage: $perPage, pageName: 'allPage', page: $allPage);

        // Use API Resources while preserving paginator structure
        $unread->setCollection(NotificationResource::collection($unread->getCollection())->collection);
        $all->setCollection(NotificationResource::collection($all->getCollection())->collection);

        return response()->json([
            'unread' => $unread,
            'all' => $all,
        ]);
    }

    /**
     * Crear una notificación de demostración para el usuario autenticado.
     */
    public function demo(Request $request)
    {
        $user = Auth::user();
        $user->notify(new DemoNotification(
            title: 'Welcome',
            body: 'This is a demo notification.'
        ));

        return Response::json(['status' => 'ok']);
    }

    /**
     * Marcar una notificación como leída.
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        return Response::json(['status' => 'ok']);
    }

    /**
     * Marcar todas las notificaciones como leídas.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        return Response::json(['status' => 'ok']);
    }
}
