<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\MasukPosRequest;
use App\Http\Requests\Pos\UbahProfilPosRequest;
use App\Http\Resources\Pos\LanggananTokoResource;
use App\Http\Resources\Pos\ProfilResource;
use App\Http\Resources\Pos\TokoResource;
use App\Models\PosUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Autentikasi aplikasi POS mobile.
 *
 * Token bearer, bukan sesi: aplikasi berjalan di perangkat lain, sering di
 * jaringan lain, dan harus tetap masuk berminggu-minggu tanpa membuka peramban.
 * Guard-nya `pos` dengan provider terpisah, jadi token ini tidak bisa dipakai
 * di `/api/v1/*` milik panel admin.
 */
class AuthController extends Controller
{
    public function masuk(MasukPosRequest $request): JsonResponse
    {
        $email = $request->string('email')->lower()->trim()->value();

        $toko = PosUser::query()->where('email', $email)->first();

        /*
         * Satu pesan untuk email yang tidak ada MAUPUN kata sandi yang salah.
         * Membedakannya memberi tahu penyerang email mana yang terdaftar, dan
         * tidak menolong pengguna sah sedikit pun.
         *
         * `Hash::check` tetap dijalankan atas hash palsu saat akunnya tidak
         * ada, supaya waktu jawabannya tidak membocorkan hal yang sama.
         */
        $sah = $toko !== null
            && $toko->password !== null
            && Hash::check((string) $request->string('kataSandi'), $toko->password);

        if (! $sah) {
            if ($toko === null) {
                Hash::check((string) $request->string('kataSandi'), Hash::make('tidak-ada'));
            }

            throw ValidationException::withMessages([
                'kataSandi' => 'Email atau kata sandi salah.',
            ]);
        }

        if ($toko->ditangguhkan) {
            throw ValidationException::withMessages([
                'email' => 'Akun toko ditangguhkan. Hubungi dukungan Jualin Aja.',
            ]);
        }

        /*
         * Token lama perangkat yang sama dicabut lebih dulu. Tanpa ini, tiap
         * pemasangan ulang aplikasi meninggalkan token hidup yang tidak pernah
         * bisa dicabut pemiliknya dari mana pun.
         */
        $perangkat = (string) ($request->string('perangkat')->trim()->value() ?: 'Aplikasi POS');
        $toko->tokens()->where('name', $perangkat)->delete();

        $token = $toko->createToken($perangkat)->plainTextToken;

        $toko->forceFill(['terakhir_masuk' => now()])->saveQuietly();
        $toko->load('langgananBerlaku');

        return response()->json([
            'token' => $token,
            'profil' => new ProfilResource($toko),
            'toko' => new TokoResource($toko),
            'langganan' => new LanggananTokoResource($toko),
        ]);
    }

    public function keluar(Request $request): JsonResponse
    {
        /*
         * Dicabut lewat bearer token permintaan INI, bukan lewat
         * `currentAccessToken()`. Dua alasan:
         *
         * 1. Hanya token yang sedang dipakai. Mencabut seluruhnya akan
         *    mengeluarkan perangkat lain milik pemilik toko yang sama tanpa ia
         *    pernah memintanya.
         * 2. `currentAccessToken()` bisa berisi TransientToken (permintaan
         *    bersesi) atau null — keduanya tidak punya baris untuk dihapus,
         *    dan keduanya tidak terlihat dari tipe yang dijanjikan Sanctum.
         */
        PersonalAccessToken::findToken((string) $request->bearerToken())?->delete();

        return response()->json(['status' => 'keluar']);
    }

    /** @return array<string, mixed> */
    public function saya(Request $request): array
    {
        /** @var PosUser $toko */
        $toko = $request->user();
        $toko->load('langgananBerlaku');

        return [
            'profil' => new ProfilResource($toko),
            'toko' => new TokoResource($toko),
            'langganan' => new LanggananTokoResource($toko),
        ];
    }

    public function ubahProfil(UbahProfilPosRequest $request): ProfilResource
    {
        /** @var PosUser $toko */
        $toko = $request->user();

        $toko->fill($request->nilai())->save();

        return new ProfilResource($toko);
    }
}
