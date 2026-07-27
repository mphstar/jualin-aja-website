<?php

namespace App\Http\Middleware;

use App\Http\Resources\AdminResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $admin = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            /*
             * Satu-satunya data yang dititipkan lewat props Inertia. Sisanya
             * lewat /api/v1 — tapi identitas admin harus sudah ada sejak render
             * pertama, kalau tidak sidebar berkedip kosong menunggu /auth/saya
             * pada setiap muat halaman.
             */
            'auth' => [
                'admin' => $admin !== null ? new AdminResource($admin) : null,
            ],
        ];
    }
}
