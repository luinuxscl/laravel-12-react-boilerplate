<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Puedes registrar reportables/rendereables específicos si hace falta.
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // Si es una petición Inertia (frontend React), devolvemos páginas de error SPA.
        if ($this->isInertiaRequest($request)) {
            $status = $this->resolveStatusCode($e);

            if (in_array($status, [403, 404, 500], true)) {
                return Inertia::render("errors/{$status}", [
                    'status' => $status,
                    'message' => $this->exceptionMessage($e, $status),
                ])->toResponse($request)->setStatusCode($status);
            }
        }

        return parent::render($request, $e);
    }

    protected function isInertiaRequest(Request $request): bool
    {
        return $request->headers->get('X-Inertia') === 'true';
    }

    protected function resolveStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return 500;
    }

    protected function exceptionMessage(Throwable $e, int $status): string
    {
        // En producción es común no exponer mensajes de 500.
        if ($status === 500) {
            return __('Server error');
        }

        return $e->getMessage() ?: match ($status) {
            403 => __('Forbidden'),
            404 => __('Not Found'),
            default => __('Error'),
        };
    }
}
