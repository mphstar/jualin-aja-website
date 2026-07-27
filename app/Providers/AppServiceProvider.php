<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        /*
         * Frontend menerima bentuk yang persis sama dengan tipe TypeScript-nya
         * (lihat resources/js/types/index.ts) — tanpa pembungkus `data` bawaan
         * Eloquent Resource. Amplop untuk daftar dibentuk sendiri oleh
         * App\Http\Concerns\MengirimHalaman.
         */
        JsonResource::withoutWrapping();

        // Relasi yang lupa di-eager-load akan gagal keras di lokal, bukan diam-diam
        // menembak N+1 kueri sampai ketahuan di produksi.
        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
