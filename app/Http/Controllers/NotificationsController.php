<?php

namespace App\Http\Controllers;

use App\Notifications\DemoNotification;
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
        return response()->json([
            'unread' => $user->unreadNotifications()->limit(20)->get(),
            'all' => $user->notifications()->limit(50)->get(),
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
}
