<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Str;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'current_password', 'code', 'recovery_code',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof \RuntimeException && Str::startsWith($exception->getMessage(), 'Falta el estado:')) {
            $mensaje = $exception->getMessage();
            $estado = null;
            if (preg_match('/Falta el estado:\s*([^\.]+)/', $mensaje, $matches)) {
                $estado = trim($matches[1]);
            }
            return response()->view('errors.missing-estado', [
                'estado' => $estado,
                'mensaje' => $mensaje,
            ], 500);
        }
        return parent::render($request, $exception);
    }
}
