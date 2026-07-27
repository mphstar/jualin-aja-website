<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        /**
         * Panel admin dan API-nya berbagi satu sesi: halaman dirender Inertia,
         * datanya diambil lewat `/api/v1/*`. Tanpa ini, cookie sesi tidak
         * dianggap oleh guard `sanctum` dan setiap permintaan data akan 401.
         * Token pribadi tetap bisa dipakai — jalur untuk aplikasi POS mobile.
         */
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Galat halaman dirender Inertia, bukan Blade — salah ketik alamat
         * tidak melempar pengguna keluar dari panel.
         *
         * 500 sengaja dikecualikan di lokal: halaman debug Laravel jauh lebih
         * berguna saat mengembangkan daripada layar "terjadi kesalahan".
         */
        $exceptions->respond(function (Response $respons, \Throwable $e, Request $request) {
            $status = $respons->getStatusCode();

            $render = in_array($status, [403, 404, 419, 503], true)
                || ($status === 500 && ! app()->hasDebugModeEnabled());

            if (! $render || $request->is('api/*') || $request->expectsJson()) {
                return $respons;
            }

            return Inertia::render('kesalahan', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
