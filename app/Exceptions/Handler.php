<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    public function render($request, Throwable $e)
    {
        if (! $request->expectsJson()) {
            return parent::render($request, $e);
        }

        if ($e instanceof ValidationException) {
            return $this->errorResponse('Validation error', $e->errors(), 422);
        }

        if ($e instanceof AuthenticationException) {
            return $this->errorResponse('Unauthorized', null, 401);
        }

        if ($e instanceof AuthorizationException) {
            return $this->errorResponse('Forbidden', null, 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->errorResponse('Resource not found', null, 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse('Endpoint not found', null, 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('Method not allowed', null, 405);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return $this->errorResponse('Too many requests', null, 429);
        }

        if ($e instanceof QueryException) {
            $errors = config('app.debug') ? ['exception' => $e->getMessage()] : null;

            return $this->errorResponse('Database error', $errors, 500);
        }

        if ($e instanceof TokenMismatchException) {
            return $this->errorResponse('CSRF token mismatch', null, 419);
        }

        if ($e instanceof RequestException) {
            $errors = config('app.debug') ? ['exception' => $e->getMessage()] : null;

            return $this->errorResponse('Request timeout or external API error', $errors, 408);
        }

        if ($e instanceof HttpException) {
            return $this->errorResponse(
                $e->getMessage() ?: 'HTTP Error',
                null,
                $e->getStatusCode()
            );
        }

        $errors = config('app.debug') ? ['exception' => $e->getMessage()] : null;

        return $this->errorResponse('Internal server error', $errors, 500);
    }
}
