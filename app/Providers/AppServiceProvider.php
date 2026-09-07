<?php

namespace App\Providers;

use App\Contracts\GerbangPembayaran;
use App\Services\MayarGerbang;
use App\Support\KonfigurasiMayar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * Gerbang pembayaran diikat sebagai singleton lewat antarmukanya, jadi
         * uji fitur bisa menukarnya dengan tiruan tanpa satu pun Action tahu —
         * dan tidak ada satu pun tes yang menembak jaringan Mayar.
         */
        $this->app->singleton(GerbangPembayaran::class, fn (): MayarGerbang => new MayarGerbang(
            apiKey: KonfigurasiMayar::apiKey(),
            produksi: KonfigurasiMayar::produksi(),
            timeout: KonfigurasiMayar::timeout(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://') || request()->server->get('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }
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
