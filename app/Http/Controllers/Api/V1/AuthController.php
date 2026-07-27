<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\JenisAksi;
use App\Enums\TargetAksi;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UbahProfilRequest;
use App\Http\Resources\AdminResource;
use App\Models\User;
use App\Services\PencatatAktivitas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Autentikasi admin panel.
 *
 * Memakai sesi, bukan token bearer: panel ini disajikan Laravel sendiri, jadi
 * cookie httpOnly lebih aman daripada token di localStorage — dan Sanctum
 * stateful membuat guard `sanctum` menerimanya. Token pribadi tetap tersedia
 * untuk aplikasi POS mobile nanti tanpa mengubah apa pun di sini.
 */
class AuthController extends Controller
{
    public function __construct(private readonly PencatatAktivitas $pencatat) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $kredensial = [
            'email' => $request->string('email')->lower()->trim()->value(),
            'password' => (string) $request->string('kataSandi'),
        ];

        if (! Auth::guard('web')->attempt($kredensial, remember: true)) {
            // Dilekatkan ke field `kataSandi` supaya pesannya muncul di bawah
            // input yang benar, bukan sebagai galat lepas di atas form.
            throw ValidationException::withMessages([
                'kataSandi' => 'Email atau kata sandi salah.',
            ]);
        }

        $request->session()->regenerate();

        /*
         * Middleware `auth` menyimpan halaman yang tadi hendak dibuka sebelum
         * tamu dilempar ke /login. Alamatnya dikembalikan ke klien karena login
         * ini permintaan XHR — tidak ada redirect server yang bisa membawanya.
         */
        $tujuan = $request->session()->pull('url.intended', '/dasbor');

        /** @var User $admin */
        $admin = Auth::user();
        $admin->forceFill(['terakhir_masuk' => now()])->save();

        $this->pencatat->catat(
            aksi: JenisAksi::Masuk,
            targetTipe: TargetAksi::Sistem,
            deskripsi: 'Masuk ke panel admin.',
            aktor: $admin,
        );

        return response()->json([
            'admin' => new AdminResource($admin),
            'tujuan' => $tujuan,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin !== null) {
            $this->pencatat->catat(
                aksi: JenisAksi::Keluar,
                targetTipe: TargetAksi::Sistem,
                deskripsi: 'Keluar dari panel admin.',
                aktor: $admin,
            );
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'keluar']);
    }

    public function saya(Request $request): AdminResource
    {
        return new AdminResource($request->user());
    }

    public function ubahProfil(UbahProfilRequest $request): AdminResource
    {
        /** @var User $admin */
        $admin = $request->user();

        $admin->fill(array_filter([
            'name' => $request->has('nama') ? (string) $request->string('nama') : null,
            'email' => $request->has('email') ? (string) $request->string('email') : null,
        ], static fn (?string $nilai): bool => $nilai !== null));

        if ($request->has('avatarUrl')) {
            $admin->avatar_url = $request->input('avatarUrl');
        }

        $admin->save();

        $this->pencatat->catat(
            aksi: JenisAksi::PengaturanUbah,
            targetTipe: TargetAksi::Sistem,
            deskripsi: 'Memperbarui profil admin.',
            aktor: $admin,
        );

        return new AdminResource($admin);
    }
}
