# PRD — Admin Dashboard SaaS POS (Frontend Only)

**Proyek:** `saas-app` · `D:\Development\saas-app\website\saas-app`
**Versi:** 1.0 · **Tanggal:** 25 Juli 2026 · **Status:** Selesai — F0–F10 terpasang, `npm run build` & `npm run lint` bersih (26 Juli 2026)

---

## 1. Context — Kenapa ini dibuat

Anda sedang membangun **platform SaaS Point of Sale (POS)**. Pemilik usaha berlangganan, lalu memakai aplikasi POS di **mobile** untuk transaksi, master data kategori/barang, riwayat, dan laporan. Websitenya **bukan** untuk mereka — website ini khusus untuk **Anda sebagai pemilik platform**, untuk memantau siapa yang sudah berlangganan, siapa yang belum, dan kapan langganan mereka habis.

Saat ini belum ada apa-apa: folder proyek masih template `npm create vite` kosong (React 19.2.8 + Vite 8 + TypeScript 6 + Oxlint). Tidak ada Tailwind, shadcn, Zustand, router — semuanya nol.

**Yang dikerjakan sekarang:** admin dashboard-nya saja, **frontend only** — seluruh data dimock di memori, belum ada backend. Tujuannya supaya tampilan, alur, dan struktur data sudah matang dan bisa Anda lihat berjalan, sebelum backend dibangun.

**Hasil akhir yang diharapkan:** aplikasi admin yang bisa dijalankan (`npm run dev`), lengkap dengan 7 modul, data contoh yang realistis, dan lapisan data yang sudah dirancang agar **penggantian ke API asli nanti hanya menyentuh satu folder**.

> ⚠️ **Catatan koreksi.** Di permintaan awal tertulis "master data **reset**". Setelah dikonfirmasi, yang dimaksud adalah **master data RESEP** — dan bentuknya adalah **katalog ebook siap unduh**, bukan input bahan & takaran. Tidak ada fitur hapus/reset data user di dokumen ini.

---

## 2. Tujuan & Batasan

### Tujuan
| # | Tujuan | Ukuran keberhasilan |
|---|---|---|
| G1 | Tahu kondisi langganan seluruh user dalam sekali lihat | Dashboard menampilkan jumlah aktif / akan berakhir / kedaluwarsa tanpa perlu klik |
| G2 | Tidak ada langganan habis tanpa disadari | Daftar "akan berakhir ≤ 7 hari" tampil di halaman depan |
| G3 | Perpanjang langganan user secara manual | ≤ 3 klik dari tabel user |
| G4 | Kelola katalog ebook resep untuk pelanggan | Tambah/ubah/terbitkan ebook + lihat statistik unduhan |
| G5 | Struktur siap disambung backend | Ganti ke API asli hanya mengubah isi `src/lib/api/` |

### Non-Goals (tegas TIDAK dikerjakan sekarang)
- Backend, database, autentikasi asli, API
- Halaman publik: landing page, pricing, form pendaftaran user
- Payment gateway / pembayaran otomatis
- Aplikasi POS mobile-nya sendiri
- Multi-admin, role & permission
- Notifikasi email / WhatsApp otomatis
- Export laporan (CSV/PDF/Excel)
- Multi-bahasa (i18n) — **UI 100% Bahasa Indonesia**

---

## 3. Persona

**Admin Platform (satu orang — Anda).**
Pemilik platform. Membuka dashboard beberapa kali seminggu dari laptop. Yang dicari: *"siapa yang mau habis?"*, *"bulan ini masuk berapa?"*, *"user ini sudah bayar belum?"*. Bukan pengguna teknis yang butuh query — butuh angka besar, tabel yang bisa dicari, dan tombol aksi yang jelas.

**POS User / Pemilik Toko (di luar sistem ini).**
Muncul di dashboard hanya sebagai *data*. Tidak pernah membuka website ini. Nanti hanya memakai aplikasi mobile.

---

## 4. Aturan Domain

### 4.1 Paket Langganan
**Satu paket, beda durasi.** Fitur identik di semua durasi — yang membedakan hanya lama berlangganan dan harga.

| Durasi | Kode | Harga (contoh — bisa diubah di Pengaturan) |
|---|---|---|
| 1 bulan | `BULANAN` | Rp 99.000 |
| 6 bulan | `SEMESTERAN` | Rp 499.000 |
| 12 bulan | `TAHUNAN` | Rp 899.000 |
| Uji coba 14 hari | `TRIAL` | Gratis, otomatis saat daftar |

> Angka harga di atas adalah **placeholder** untuk seed data, bukan harga final. Ditaruh di satu konstanta agar mudah diganti.

### 4.2 Status Langganan (diturunkan dari tanggal, bukan disimpan)

Status **dihitung** dari `tanggalBerakhir` + flag `ditangguhkan`, sehingga tidak pernah basi:

```
ditangguhkan = true                       → NONAKTIF     (abu-abu)  ← override, menang atas semua
tanggalBerakhir < hari ini                → KEDALUWARSA  (merah)
sisaHari ≤ 7                              → AKAN BERAKHIR(oranye)
sumber = TRIAL & tanggalBerakhir ≥ hari ini → TRIAL      (biru)
selain itu                                → AKTIF        (hijau)
```

Satu fungsi murni `hitungStatusLangganan(langganan, sekarang)` dipakai di **semua** tempat (tabel, badge, dashboard, filter) — supaya tidak ada dua tempat yang menghitung beda.

### 4.3 Akses Ebook
**User dengan langganan aktif boleh mengunduh SELURUH ebook yang berstatus Terbit.** Tidak ada ebook gratis, tidak ada pemberian akses per-user. Sederhana — dan justru jadi nilai jual langganan.

---

## 5. Modul & Kebutuhan Fungsional

### M1 · Autentikasi & Pengaturan

| ID | Kebutuhan |
|---|---|
| F1.1 | Halaman `/login`: form email + password, validasi, pesan error jelas, tombol lihat/sembunyikan password |
| F1.2 | Mock auth — kredensial di konstanta (`admin@poskita.id` / `admin123`), ditampilkan sebagai hint di halaman login karena ini demo |
| F1.3 | Sesi disimpan di `localStorage`, tetap login setelah refresh |
| F1.4 | Semua route selain `/login` terproteksi → redirect ke login bila belum masuk |
| F1.5 | Halaman `/pengaturan`: profil admin (nama, email, avatar), ganti tema Terang/Gelap/Ikut Sistem, dan **harga paket** (1/6/12 bulan) yang dipakai seed data |
| F1.6 | Tombol keluar di menu profil sidebar, dengan konfirmasi |

### M2 · Dashboard (`/dasbor`)

| ID | Kebutuhan |
|---|---|
| F2.1 | 4 kartu statistik: **Total User**, **Langganan Aktif**, **Akan Berakhir ≤7 hari**, **Pendapatan Bulan Ini** — masing-masing dengan delta % vs bulan lalu (naik/turun) |
| F2.2 | Grafik garis/area: tren pendaftaran user 12 bulan terakhir |
| F2.3 | Grafik area: tren pendapatan 12 bulan terakhir (IDR) |
| F2.4 | Grafik donat: komposisi durasi paket (1 / 6 / 12 bulan / trial) |
| F2.5 | Tabel ringkas: 10 user dengan langganan terdekat berakhir, ada tombol **Perpanjang** langsung di baris |
| F2.6 | Feed: 8 aktivitas terbaru dari log |
| F2.7 | Semua kartu & grafik punya skeleton saat memuat, dan empty state bila data kosong |

### M3 · Manajemen User (`/pengguna`)

| ID | Kebutuhan |
|---|---|
| F3.1 | Tabel user: Avatar+Nama, Nama Toko, Kota, Tanggal Daftar, Paket, Status (badge berwarna), Sisa Hari, Aksi |
| F3.2 | Pencarian teks (nama, email, nama toko, telepon) dengan debounce |
| F3.3 | Filter: Status langganan, Durasi paket, Jenis usaha |
| F3.4 | Sortir kolom (tanggal daftar, sisa hari, nama) + pagination (10/25/50 per halaman) |
| F3.5 | Aksi baris: Lihat Detail · Perpanjang Langganan · Tangguhkan/Pulihkan |
| F3.6 | Halaman detail `/pengguna/:id` dengan 4 tab: **Profil** (data toko), **Langganan** (riwayat + tombol perpanjang), **Pembayaran** (invoice user ini), **Unduhan** (ebook yang pernah diunduh) |
| F3.7 | Menangguhkan user → dialog konfirmasi, wajib isi alasan, tercatat di log aktivitas |

### M4 · Manajemen Langganan (`/langganan`)

| ID | Kebutuhan |
|---|---|
| F4.1 | Tabel seluruh langganan: User, Durasi, Mulai, Berakhir, Sisa Hari, Sumber (Trial/Beli/Perpanjangan Manual), Status |
| F4.2 | Filter cepat berupa tab: Semua · Aktif · Akan Berakhir · Kedaluwarsa · Trial · Nonaktif — dengan jumlah di tiap tab |
| F4.3 | Dialog **Perpanjang**: pilih durasi (+1 / +6 / +12 bulan), preview tanggal berakhir baru, catatan opsional, konfirmasi |
| F4.4 | Perpanjangan menambah dari `tanggalBerakhir` bila masih aktif, atau dari **hari ini** bila sudah kedaluwarsa — aturan ini ditulis eksplisit di UI |
| F4.5 | Setiap perpanjangan membuat record langganan baru + entri log, riwayat lama tidak hilang |
| F4.6 | Sisa hari tampil sebagai badge: `23 hari lagi`, `Berakhir besok`, `Lewat 5 hari` |

### M5 · Master Data Resep — Katalog Ebook (`/resep`)

> Modul konten, **bukan** input bahan/takaran. Resep sudah jadi dalam bentuk file ebook.

| ID | Kebutuhan |
|---|---|
| F5.1 | Tampilan katalog: toggle **Grid** (kartu bercover) ↔ **Tabel** |
| F5.2 | Data per ebook: Judul, Kategori, Deskripsi, Cover, File PDF, Jumlah Halaman, Ukuran File, Status, Jumlah Unduhan, Tanggal Terbit |
| F5.3 | Kategori (enum tetap, tidak perlu CRUD): Minuman · Makanan Berat · Snack · Dessert · Bakery · Bumbu & Saus |
| F5.4 | Form tambah/ubah `/resep/baru`, `/resep/:id/ubah` — upload cover (preview gambar) & file PDF via `URL.createObjectURL` (mock, tidak persist setelah refresh — **ditandai jelas di UI**) |
| F5.5 | Status **Draf ↔ Terbit**. Hanya yang Terbit terlihat oleh user. Toggle cepat dari tabel |
| F5.6 | Halaman detail `/resep/:id`: metadata + **statistik unduhan** — total, grafik unduhan 30 hari terakhir, dan tabel siapa saja yang mengunduh (nama user + tanggal) |
| F5.7 | Hapus ebook → dialog konfirmasi destruktif (ketik ulang judul), tercatat di log |
| F5.8 | Pencarian judul + filter Kategori & Status |

### M6 · Riwayat Pembayaran (`/pembayaran`)

| ID | Kebutuhan |
|---|---|
| F6.1 | Tabel: No. Invoice, User, Tanggal, Jenis, Paket/Konten, Metode, Status, Nominal |
| F6.2 | Status: **Lunas** (hijau) · **Menunggu** (oranye) · **Gagal** (merah) · **Refund** (abu) |
| F6.3 | Metode: Transfer Bank · QRIS · Virtual Account · Manual/Tunai |
| F6.4 | Filter: jenis, status, metode, rentang tanggal; pencarian no. invoice / nama user |
| F6.5 | Ringkasan di atas tabel: total lunas bulan ini, jumlah menunggu, jumlah gagal |
| F6.6 | Detail `/pembayaran/:id`: tampilan invoice (data user, rincian paket, total, status), tombol **Tandai Lunas** untuk yang menunggu |
| F6.7 | Menandai lunas → **Langganan**: masa aktif user terkait diperpanjang. **Pustaka satuan**: akses konten itu dibuka, masa aktif tidak disentuh. Keduanya menulis entri log |

> Satu tabel `pembayaran` menampung dua jenis tagihan, dibedakan kolom `tipe`:
> **Langganan** (memperpanjang masa aktif, `durasi` terisi) dan **Pustaka satuan**
> (membeli satu konten, `durasi` null, terikat `ebook_id`). Kolom "Paket/Konten"
> menampilkan durasi untuk yang pertama dan judul konten untuk yang kedua — jadi
> `durasi` tidak boleh dibaca tanpa memeriksa `tipe` lebih dulu.

### M7 · Log Aktivitas (`/aktivitas`)

| ID | Kebutuhan |
|---|---|
| F7.1 | Timeline/tabel kronologis: waktu, aktor, aksi (ikon+warna), target, deskripsi |
| F7.2 | Aksi tercatat: masuk/keluar · perpanjang langganan · tangguhkan/pulihkan user · tambah/ubah/terbitkan/hapus ebook · tandai pembayaran lunas/gagal · ubah pengaturan |
| F7.3 | Filter jenis aksi + rentang tanggal; pencarian bebas |
| F7.4 | Klik entri → langsung ke halaman objek terkait (user/ebook/pembayaran) |
| F7.5 | Semua aksi tulis di seluruh aplikasi **wajib** menulis log — satu helper terpusat, bukan dipanggil manual di tiap tempat |

---

## 6. Model Data (TypeScript)

Semua tipe di `src/types/`. Nama field Bahasa Indonesia agar konsisten dengan UI dan mudah dipetakan ke backend nanti.

```ts
// ---- Enum & union ----
type DurasiPaket   = 'BULANAN' | 'SEMESTERAN' | 'TAHUNAN' | 'TRIAL';
type StatusLangganan = 'AKTIF' | 'AKAN_BERAKHIR' | 'KEDALUWARSA' | 'TRIAL' | 'NONAKTIF';
type SumberLangganan = 'TRIAL' | 'PEMBELIAN' | 'PERPANJANGAN_MANUAL' | 'HADIAH';
type StatusPembayaran = 'LUNAS' | 'MENUNGGU' | 'GAGAL' | 'REFUND';
type MetodePembayaran = 'TRANSFER_BANK' | 'QRIS' | 'VIRTUAL_ACCOUNT' | 'MANUAL';
type TipePembayaran = 'LANGGANAN' | 'PUSTAKA_SATUAN';
type KategoriEbook = 'MINUMAN' | 'MAKANAN_BERAT' | 'SNACK' | 'DESSERT' | 'BAKERY' | 'BUMBU_SAUS';
type StatusEbook   = 'DRAF' | 'TERBIT';
type JenisUsaha    = 'KAFE' | 'RESTORAN' | 'WARUNG_MAKAN' | 'BAKERY' | 'TOKO_KELONTONG' | 'LAINNYA';

// ---- Entitas ----
interface AdminUser   { id; nama; email; avatarUrl?; terakhirMasuk: string }

interface PosUser {                       // pemilik toko = tenant
  id; nama; email; telepon; avatarUrl?;
  namaToko; jenisUsaha: JenisUsaha; kota;
  tanggalDaftar: string;                  // ISO
  ditangguhkan: boolean; alasanPenangguhan?: string;
}

interface Langganan {
  id; userId;
  durasi: DurasiPaket; sumber: SumberLangganan;
  tanggalMulai: string; tanggalBerakhir: string;   // ISO
  dibuatOleh?: string; catatan?: string;
}

interface Pembayaran {
  id; nomorInvoice; userId; langgananId?;
  tipe: TipePembayaran;
  nominal: number;
  durasi: DurasiPaket | null;             // null untuk PUSTAKA_SATUAN
  ebookJudul?: string | null;             // diisi hanya untuk PUSTAKA_SATUAN
  metode: MetodePembayaran; status: StatusPemba yaran;
  tanggal: string; catatan?: string;
}

interface Ebook {
  id; judul; slug; kategori: KategoriEbook; deskripsi;
  coverUrl?; fileUrl?; namaFile?; ukuranFileBytes?; jumlahHalaman?;
  status: StatusEbook;
  tanggalDibuat: string; tanggalTerbit?: string;
  jumlahUnduhan: number;                  // denormalisasi untuk tabel
}

interface UnduhanEbook { id; ebookId; userId; tanggal: string }

interface LogAktivitas {
  id; waktu: string; aktorId; aktorNama;
  aksi: JenisAksi;                        // enum di §M7 F7.2
  targetTipe: 'USER'|'EBOOK'|'LANGGANAN'|'PEMBAYARAN'|'SISTEM';
  targetId?; targetLabel?; deskripsi: string;
}
```

**Turunan (dihitung, tidak disimpan):** `StatusLangganan`, `sisaHari`, total pendapatan, jumlah per status.

---

## 7. Peta Halaman

| Route | Halaman | Akses |
|---|---|---|
| `/login` | Login admin | Publik |
| `/` | → redirect ke `/dasbor` | Terproteksi |
| `/dasbor` | Dashboard | Terproteksi |
| `/pengguna` | Tabel user | Terproteksi |
| `/pengguna/:id` | Detail user (4 tab) | Terproteksi |
| `/langganan` | Tabel langganan + tab status | Terproteksi |
| `/resep` | Katalog ebook (grid/tabel) | Terproteksi |
| `/resep/baru` · `/resep/:id/ubah` | Form ebook | Terproteksi |
| `/resep/:id` | Detail + statistik unduhan | Terproteksi |
| `/pembayaran` | Tabel pembayaran | Terproteksi |
| `/pembayaran/:id` | Detail invoice | Terproteksi |
| `/aktivitas` | Log aktivitas | Terproteksi |
| `/pengaturan` | Pengaturan | Terproteksi |
| `*` | 404 | — |

**Shell:** sidebar kiri (Dasbor · Pengguna · Langganan · Resep · Pembayaran · Aktivitas · Pengaturan) + topbar (breadcrumb, pencarian global, toggle tema, menu profil). Di layar < 1024px sidebar jadi drawer.

---

## 8. Kebutuhan Non-Fungsional

| Aspek | Ketentuan |
|---|---|
| Bahasa | 100% Bahasa Indonesia. Tanggal `25 Jul 2026`, relatif `3 hari lagi`, uang `Rp 1.250.000` |
| Tema | Terang & Gelap, bisa ikut sistem, pilihan tersimpan |
| Responsif | Diuji di 375 / 768 / 1024 / 1440 px. Tabel jadi kartu di mobile. Tidak boleh ada scroll horizontal di `body` |
| State UI | Setiap tabel & kartu punya 4 kondisi: **memuat (skeleton)** · **kosong** · **error** · **normal** |
| Umpan balik | Toast untuk setiap aksi berhasil/gagal |
| Aksi destruktif | Selalu dialog konfirmasi; yang berat (hapus ebook) minta ketik ulang nama |
| Aksesibilitas | Kontras AA, fokus terlihat, navigasi keyboard penuh (dibawa Radix), label form terhubung |
| Performa | Tabel 500 baris tetap responsif; pagination di sisi klien untuk sekarang |
| Data | Seluruhnya mock in-memory + seed deterministik (seed tetap → data sama tiap refresh) |

---

## 9. Arsitektur Frontend

### 9.1 Dependensi (versi sudah diverifikasi dari npm hari ini)

```bash
npm i react-router@8 zustand @tanstack/react-table react-hook-form zod \
      @hookform/resolvers recharts date-fns lucide-react \
      clsx tailwind-merge class-variance-authority sonner
npm i -D tailwindcss @tailwindcss/vite tw-animate-css
```

| Paket | Versi | Catatan kompatibilitas |
|---|---|---|
| `@tailwindcss/vite` | 4.3.3 | `peerDependencies.vite` = `^5.2 \|\| ^6 \|\| ^7 \|\| ^8` → **Vite 8 didukung resmi**. Pakai plugin Vite, **bukan** jalur PostCSS |
| `react-router` | 8.3.0 | GA 17 Jun 2026, peer `react >=19.2.7` → cocok persis dengan React 19.2.8 di sini. Paket `react-router-dom` **sudah tidak dipakai** sejak v7 |
| `recharts` | 3.10.0 | peer sudah mencakup `^19.0.0` — friksi React 19 di recharts 2.x tidak berlaku lagi |
| `zod` + `@hookform/resolvers` | 4.4.3 + 5.4.2 | resolvers 5 menerima `zod: ^3.25 \|\| ^4` → **zod 4 aman** |
| `tw-animate-css` | 1.4.0 | Pengganti `tailwindcss-animate` untuk Tailwind **v4**. Jangan pakai yang lama |
| `zustand` | 5.0.14 | peer `react >=18` ✓ |
| `sonner` | 2.0.7 | toast; peer `^19` ✓ |

**Risiko yang dipantau:** react-router 8 baru berumur ~5 minggu sebagai mayor. Untuk SPA deklaratif sederhana seperti ini permukaan API-nya kecil, tapi kalau ada masalah, **jalur mundurnya adalah `react-router@7.18.1`** (matang, API rute yang kita pakai identik).

> #### ⚙️ Catatan implementasi F0 — dua penyimpangan dari rencana awal
>
> **1. Konflik dependensi `@hookform/resolvers@5.4.2` (nyata, memblokir instalasi).**
> Paket ini punya bug pemaketan: graf *optional peer*-nya bertentangan dengan dirinya sendiri —
> `@hookform/resolvers` → `@typeschema/main` → `@typeschema/valibot` → minta `valibot@^0.39`,
> padahal `@hookform/resolvers` sendiri minta `valibot@^1.0`. npm menolak memasang apa pun.
> **Perbaikan (bedah, bukan `--legacy-peer-deps`):** buang tiga optional-peer tak terpakai itu
> lewat `overrides` di `package.json` dengan nilai `"-"`:
> ```json
> "overrides": {
>   "@hookform/resolvers": { "@typeschema/main": "-", "@typeschema/valibot": "-", "valibot": "-" }
> }
> ```
> Kita memakai zod, jadi seluruh cabang `@typeschema`/`valibot` memang tidak pernah dipakai.
> Pengecekan peer di seluruh dependensi lain **tetap aktif** — ini yang penting di tumpukan sebaru ini.
>
> **2. `baseUrl` sudah usang di TypeScript 6.**
> `tsc` menolak dengan `TS5101: Option 'baseUrl' is deprecated`. Di TS 6 `paths` sudah relatif ke
> lokasi file tsconfig, jadi `baseUrl` **dihapus** dari `tsconfig.json` dan `tsconfig.app.json`.
> Alias `@/*` tetap berfungsi, dan CLI shadcn tetap bisa mendeteksinya.
>
> **3. `recharts` dipatok ke 3.8.0** oleh CLI shadcn (bukan 3.10.0). Tetap mendukung React 19 —
> dibiarkan karena komponen `chart` shadcn diuji terhadap versi itu.
>
> **Hasil verifikasi F0:** 30 komponen shadcn terpasang; `npm run build` lolos — artinya seluruh
> komponen bawaan shadcn **sudah lolos `strict` + `verbatimModuleSyntax` + `noUnusedLocals`**
> (jebakan yang dikhawatirkan di §11 ternyata bersih); `npm run lint` bersih; dev server HTTP 200.

### 9.2 Perubahan Konfigurasi

**`vite.config.ts`** — tambah plugin Tailwind + alias `@`:
```ts
import path from 'node:path'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: { alias: { '@': path.resolve(import.meta.dirname, './src') } },
})
```

**⚠️ Jebakan `tsconfig.json`.** CLI shadcn membaca `tsconfig.json` untuk menemukan alias `@/*`. Di proyek ini `tsconfig.json` hanyalah *solution file* (`files: []` + `references`) — **tidak punya `compilerOptions` sama sekali**, jadi CLI akan gagal mendeteksi alias dan menolak init. Perbaikannya: tulis `baseUrl` + `paths` di **dua** tempat.

```jsonc
// tsconfig.json  — supaya CLI shadcn bisa membacanya
{
  "files": [],
  "references": [{ "path": "./tsconfig.app.json" }, { "path": "./tsconfig.node.json" }],
  "compilerOptions": { "baseUrl": ".", "paths": { "@/*": ["./src/*"] } }
}
```
```jsonc
// tsconfig.app.json — supaya typecheck sungguhan jalan
{
  "compilerOptions": {
    /* ...yang sudah ada... */
    "strict": true,                       // ← template TS 6 tidak menyetelnya; nyalakan eksplisit
    "baseUrl": ".",
    "paths": { "@/*": ["./src/*"] }
  }
}
```

**`components.json`** (dibuat oleh `npx shadcn@latest init`, pastikan isinya):
```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "new-york", "rsc": false, "tsx": true,
  "tailwind": { "config": "", "css": "src/index.css", "baseColor": "slate", "cssVariables": true },
  "iconLibrary": "lucide",
  "aliases": { "components": "@/components", "utils": "@/lib/utils", "ui": "@/components/ui", "lib": "@/lib", "hooks": "@/hooks" }
}
```
`tailwind.config` sengaja **string kosong** — Tailwind v4 tidak memakai file config JS.

**`src/index.css`** — **ditulis ulang total.** Isi lama (`#root { width:1126px; text-align:center; border-inline:... }`) akan merusak layout sidebar+konten dan wajib dibuang. Yang baru:
```css
@import "tailwindcss";
@import "tw-animate-css";
@custom-variant dark (&:is(.dark *));

:root       { --background: …; --foreground: …; /* token shadcn */ }
.dark       { --background: …; --foreground: …; }
@theme inline { --color-background: var(--background); /* … */ }
@layer base { * { @apply border-border; } body { @apply bg-background text-foreground; } }
```

**`index.html`** — ganti `<title>` jadi nama produk, `lang="id"`, tambah `class="dark"` awal jika perlu (dikelola store tema).

**Dihapus:** `src/App.css`, `src/assets/{hero.png,react.svg,vite.svg}`, isi lama `src/App.tsx`, `public/icons.svg`.

### 9.3 Struktur Folder

Dipilih **feature-folder**, bukan type-folder. Alasannya: ada 7 modul yang tiap modulnya punya halaman + kolom tabel + dialog sendiri. Menaruhnya berdekatan membuat satu modul bisa dikerjakan/diubah tanpa menyentuh yang lain.

```
src/
├─ main.tsx
├─ App.tsx                      # RouterProvider + penyedia tema + <Toaster/>
├─ index.css                    # Tailwind v4 + token tema
├─ routes.tsx                   # definisi rute terpusat
├─ types/index.ts               # seluruh tipe domain (§6)
│
├─ components/
│  ├─ ui/                       # hasil shadcn — jangan diedit manual
│  ├─ layout/                   # AppShell · Sidebar · Topbar · Breadcrumb · MenuProfil
│  └─ shared/                   # DataTable · StatCard · BadgeStatus · BadgeSisaHari
│                               # EmptyState · ErrorState · PageHeader · DialogKonfirmasi
├─ features/
│  ├─ auth/        (HalamanLogin, RuteTerproteksi)
│  ├─ dasbor/      (HalamanDasbor, KartuStatistik, GrafikPendaftaran, GrafikPendapatan,
│  │                GrafikKomposisiPaket, TabelAkanBerakhir, FeedAktivitas)
│  ├─ pengguna/    (HalamanPengguna, kolomPengguna, HalamanDetailPengguna, TabProfil/Langganan/…)
│  ├─ langganan/   (HalamanLangganan, kolomLangganan, DialogPerpanjang)
│  ├─ resep/       (HalamanKatalog, KartuEbook, kolomEbook, FormEbook, HalamanDetailEbook)
│  ├─ pembayaran/  (HalamanPembayaran, kolomPembayaran, HalamanDetailInvoice)
│  ├─ aktivitas/   (HalamanAktivitas, ItemAktivitas)
│  └─ pengaturan/  (HalamanPengaturan, FormProfil, PilihanTema, FormHargaPaket)
│
├─ lib/
│  ├─ api/                      # ⭐ SATU-SATUNYA folder yang diganti saat backend siap
│  │  ├─ client.ts              #   simulasi latensi + error acak (bisa dimatikan)
│  │  ├─ auth.ts  pengguna.ts  langganan.ts  ebook.ts  pembayaran.ts  aktivitas.ts  statistik.ts
│  ├─ mock/
│  │  ├─ prng.ts                #   mulberry32 — seed tetap ⇒ data sama tiap refresh
│  │  ├─ seed.ts                #   pembangkit data contoh
│  │  └─ db.ts                  #   penyimpanan in-memory + operasi CRUD
│  ├─ langganan.ts              # hitungStatusLangganan() · hitungSisaHari() · tanggalBerakhirBaru()
│  ├─ format.ts                 # formatRupiah · formatTanggal · formatRelatif · formatUkuranFile
│  ├─ log.ts                    # catatAktivitas() — helper terpusat (§F7.5)
│  └─ utils.ts                  # cn()
│
├─ stores/                      # Zustand
│  ├─ authStore.ts  temaStore.ts
│  └─ penggunaStore.ts  langgananStore.ts  ebookStore.ts
│     pembayaranStore.ts  aktivitasStore.ts  dasborStore.ts
└─ hooks/                       # useDebounce, useMediaQuery
```

### 9.4 Routing

**`react-router` 8, `createBrowserRouter` deklaratif — TANPA loader/action.** Alasan: data sudah diurus Zustand; memakai loader berarti dua sumber kebenaran dan membuat penggantian ke backend nanti menyentuh dua tempat, bukan satu.

```
<RuteTerproteksi>          → cek authStore, kalau belum masuk redirect /login
  └─ <AppShell>            → sidebar + topbar + <Outlet/>
       ├─ /dasbor  /pengguna  /pengguna/:id  /langganan
       ├─ /resep  /resep/baru  /resep/:id  /resep/:id/ubah
       └─ /pembayaran  /pembayaran/:id  /aktivitas  /pengaturan
/login (di luar shell)  ·  * → 404
```

### 9.5 Desain Store Zustand

**8 store**, dipisah per domain agar perubahan di satu modul tidak me-render ulang modul lain.

| Store | Isi | `persist`? |
|---|---|---|
| `authStore` | `admin`, `sudahMasuk`, `masuk()`, `keluar()` | ✅ localStorage |
| `temaStore` | `tema: 'terang'\|'gelap'\|'sistem'`, `setTema()` | ✅ localStorage |
| `penggunaStore` | `daftar`, `memuat`, `error`, `filter`, `ambilDaftar()`, `tangguhkan()`, `pulihkan()` | ❌ |
| `langgananStore` | `daftar`, `filter`, `ambilDaftar()`, `perpanjang()` | ❌ |
| `ebookStore` | `daftar`, `unduhan`, `tambah()`, `ubah()`, `ubahStatus()`, `hapus()` | ❌ |
| `pembayaranStore` | `daftar`, `filter`, `tandaiLunas()`, `tandaiGagal()` | ❌ |
| `aktivitasStore` | `daftar`, `filter`, `ambilDaftar()` | ❌ |
| `dasborStore` | `statistik`, `deretPendaftaran`, `deretPendapatan`, `komposisiPaket` | ❌ |

Pola baku tiap store domain:
```ts
interface PenggunaState {
  daftar: PosUser[]
  memuat: boolean
  error: string | null
  filter: { cari: string; status: StatusLangganan | 'SEMUA'; durasi: DurasiPaket | 'SEMUA' }
  ambilDaftar: () => Promise<void>
  setFilter: (patch: Partial<PenggunaState['filter']>) => void
  tangguhkan: (id: string, alasan: string) => Promise<void>
  pulihkan: (id: string) => Promise<void>
}
```

**Aturan wajib — pemilihan selektif.** Selalu `const daftar = usePenggunaStore(s => s.daftar)`, **jangan pernah** `const { daftar, filter } = usePenggunaStore()` — bentuk kedua membuat komponen render ulang pada setiap perubahan apa pun di store. Untuk mengambil beberapa nilai sekaligus gunakan `useShallow` dari `zustand/react/shallow`.

`devtools` dipasang hanya saat `import.meta.env.DEV`.

### 9.6 Lapisan Data Mock — bagian terpenting

Ini yang menentukan apakah penyambungan backend nanti gampang atau menyakitkan.

**Kontraknya: store TIDAK PERNAH menyentuh data mock langsung.** Store hanya memanggil fungsi async di `src/lib/api/`. Semua fungsi mengembalikan `Promise` dan punya jeda buatan, sehingga skeleton & error state benar-benar teruji sejak sekarang.

```ts
// src/lib/api/pengguna.ts  — nanti isinya diganti fetch(), tanda tangannya tetap
export async function ambilDaftarPengguna(params?: ParamsPengguna): Promise<Halaman<PosUser>> {
  await tunda()                       // 300–800 ms
  return db.pengguna.cari(params)
}
export async function tangguhkanPengguna(id: string, alasan: string): Promise<PosUser> { … }
```

Saat backend siap, `ambilDaftarPengguna` cukup berubah jadi `return http.get('/pengguna', { params })`. **Store, komponen, dan tipe tidak berubah sama sekali.** Karena itu bentuk kembaliannya sudah dibuat berhalaman (`Halaman<T>` = `{ data, total, halaman, perHalaman }`) sejak awal — supaya pindah ke pagination sisi server tidak merombak tabel.

**Seed deterministik** (`mulberry32`, seed tetap) — refresh menghasilkan data yang sama, jadi tampilan bisa dibandingkan antar sesi:

| Data | Jumlah | Sebaran |
|---|---|---|
| PosUser | 48 | 18 aktif · 7 akan berakhir · 9 kedaluwarsa · 8 trial · 6 nonaktif |
| Langganan | ~90 | termasuk riwayat perpanjangan |
| Pembayaran | ~120 | 85% lunas · 8% menunggu · 5% gagal · 2% refund, tersebar 12 bulan |
| Ebook | 12 | 9 terbit · 3 draf, lintas 6 kategori |
| UnduhanEbook | ~300 | menumpuk di ebook populer |
| LogAktivitas | ~80 | 90 hari terakhir |

Nama toko, kota, dan judul ebook memakai data Indonesia yang wajar (Bandung, Surabaya, "Kopi Senja", "50 Resep Minuman Kekinian") supaya tampilannya terasa nyata saat ditinjau.

### 9.7 Tabel

**`@tanstack/react-table` v8 + satu komponen generik `<DataTable<T>>`** di `components/shared/`. Ada 5 tabel dengan kebutuhan sama (cari/filter/sortir/paginasi) — menulis manual lima kali adalah pemborosan.

```ts
interface DataTableProps<T> {
  kolom: ColumnDef<T>[]
  data: T[]
  memuat?: boolean
  error?: string | null
  paginasiManual?: boolean      // ← saklar untuk pindah ke paginasi sisi server nanti
  totalBaris?: number
  onStateChange?: (s: TableState) => void
  toolbar?: ReactNode           // slot untuk kotak cari + filter tiap modul
}
```
Sekarang `paginasiManual = false` (semua di klien). Nanti tinggal `true` + teruskan state ke `lib/api` — komponen tabelnya tidak diubah.

### 9.8 Form & Validasi

`react-hook-form` + `zod` + komponen `Form` shadcn. Skema zod ditaruh sebaris dengan form-nya, dengan **pesan error Bahasa Indonesia**.

Form yang ada: **Login** · **Ebook baru/ubah** (paling kompleks — ada unggah berkas) · **Dialog perpanjang langganan** · **Pengaturan profil** · **Pengaturan harga paket**.

### 9.9 Grafik

`recharts` 3.10 lewat komponen `chart` shadcn (yang memang membungkus recharts dan sudah menyediakan token warna + tooltip yang rapi). Tiga grafik: garis (pendaftaran), area (pendapatan), donat (komposisi paket), plus grafik batang kecil di detail ebook.

### 9.10 Komponen shadcn yang dipasang

```bash
npx shadcn@latest add button card input label textarea select checkbox switch \
  radio-group form table badge avatar separator skeleton progress \
  dialog alert-dialog sheet dropdown-menu popover tooltip command \
  tabs breadcrumb sidebar pagination scroll-area calendar chart sonner
```
`sidebar` dipilih sengaja: sudah membawa perilaku ciut/lebar, drawer mobile, dan penyimpanan preferensi — menghemat pekerjaan shell yang lumayan besar.

---

## 10. Urutan Pengerjaan

Tiap fase harus bisa dijalankan (`npm run dev`) dan lolos `npm run build` sebelum lanjut.

| Fase | Isi | Hasil yang terlihat |
|---|---|---|
| **F0** | Bersihkan template · pasang dependensi · Tailwind v4 · alias `@` · `strict` · init shadcn · tulis ulang `index.css` | Halaman kosong bertema, Tailwind jalan |
| **F1** | Tipe domain · `lib/format` · `lib/langganan` · PRNG · seed · `lib/mock/db` · `lib/api/*` | Belum ada UI; diperiksa lewat console |
| **F2** | AppShell (sidebar+topbar) · tema terang/gelap · routing · halaman login · rute terproteksi | Bisa masuk, sidebar berpindah halaman |
| **F3** | `<DataTable>` generik · BadgeStatus · BadgeSisaHari · EmptyState · ErrorState · PageHeader · DialogKonfirmasi | Komponen bersama siap pakai |
| **F4** | Manajemen User: tabel, cari, filter, paginasi, detail 4 tab, tangguhkan | Modul user utuh |
| **F5** | Manajemen Langganan: tabel + tab status + dialog perpanjang | Perpanjangan berfungsi & tercatat |
| **F6** | Katalog Ebook: grid/tabel, form + unggah, draf/terbit, detail + statistik unduhan | Modul resep utuh |
| **F7** | Pembayaran: tabel, filter, detail invoice, tandai lunas | Modul pembayaran utuh |
| **F8** | Log Aktivitas: timeline, filter, tautan ke objek | Semua aksi sebelumnya muncul di sini |
| **F9** | Dashboard: 4 kartu statistik, 3 grafik, tabel akan berakhir, feed aktivitas | Halaman depan hidup (**dikerjakan terakhir karena butuh semua data**) |
| **F10** | Pengaturan · rapikan responsif 375/768/1024/1440 · lengkapi skeleton & empty state · aksesibilitas | Siap ditinjau |

Selain kode, **fase F0 juga menyimpan PRD ini ke `docs/PRD.md`** di dalam proyek, supaya dokumennya ikut hidup bersama kodenya.

> #### ⚙️ Catatan implementasi F4–F10 — temuan yang mengubah kode
>
> **1. Sebaran data seed salah karena sisa hari tak dibatasi panjang paket (`lib/mock/seed.ts`).**
> Terlihat langsung begitu tabel pengguna hidup: ada baris "paket 1 Bulan, sisa 378 hari" dan
> **tanggal daftar di masa depan** (24 Jun 2027). Penyebabnya, tanggal berakhir diundi bebas
> (`8..400` hari) lalu seluruh riwayat dihitung MUNDUR dari titik itu — kalau siklus terakhir
> cuma 1 bulan, awal siklusnya jatuh di masa depan, dan tanggal daftar ikut terlempar.
> **Perbaikan:** durasi siklus diundi lebih dulu, lalu sisa hari dibatasi
> `BULAN_PER_DURASI[siklus terakhir] × 30 − 2`. Diverifikasi ulang: sebaran tetap persis
> 18/7/9/8/6, nol tanggal daftar di masa depan, nol sisa hari melebihi panjang paketnya.
>
> **2. `<body>` bisa di-scroll ke samping karena flex item tidak boleh menyusut (`AppShell.tsx`).**
> Melanggar PRD §8. `SidebarInset` adalah flex item dengan `min-width: auto` bawaan, jadi tabel
> lebar melebarkan seluruh halaman alih-alih di-scroll di dalam wadahnya sendiri.
> **Perbaikan:** `min-w-0` pada `SidebarInset` — satu kelas, dan `scrollWidth` kembali sama
> dengan lebar viewport di 375px maupun 1440px.
>
> **3. Grafik recharts bisa berhenti selamanya pada tinggi nol.**
> Batang dianimasikan dari 0 lewat `requestAnimationFrame`, dan rAF di-throttle saat tab tidak
> aktif — grafik tampak "rata nol" padahal datanya ada. **Perbaikan:** `isAnimationActive={false}`
> di semua seri, plus `domain` sumbu Y yang dihitung sendiri agar skala sumbu dan skala batang
> tidak pernah berbeda.
>
> **4. Angka di tab status /langganan awalnya tidak mungkin cocok dengan isi tabelnya.**
> Jumlah dihitung per-toko, sementara tabel menampilkan per-baris-langganan (termasuk riwayat).
> **Perbaikan:** keduanya dihitung dari kumpulan baris yang sama, dan ditambahkan sakelar
> **"Tampilkan siklus lama"** — mati (48 toko) atau hidup (160 baris riwayat), angka tab selalu
> menjumlah tepat sebesar isi tabel. Baris riwayat tidak punya tombol Perpanjang, karena
> perpanjangan selalu menempel pada siklus yang berlaku.
>
> **5. Tabel jadi kartu di mobile (PRD §8) dikerjakan lewat satu prop.**
> `<DataTable>` menerima `kartu?: (baris) => ReactNode`; di bawah 768px tabel diganti daftar
> kartu, sementara toolbar, filter, dan paginasi tetap komponen yang sama. Menu aksi tiap modul
> dipisah ke berkasnya sendiri (`AksiPengguna`, `AksiPembayaran`, `AksiEbookMenu`) supaya dipakai
> bersama tabel dan kartu tanpa digandakan.
>
> **6. Pemuatan malas per rute + pemisahan vendor.**
> Sebelum dipisah, satu bundel 1,14 MB (berisi recharts DAN semua tabel) harus selesai diunduh
> sebelum halaman login muncul. Sekarang tiap halaman punya potongan sendiri, `react` dan
> `react-router` dipisah lewat `advancedChunks` di `vite.config.ts`, dan **tidak ada lagi
> potongan di atas 500 kB**.
>
> **7. Komponen `calendar` shadcn ternyata tidak ikut terpasang di F0.**
> Filter rentang tanggal (§F6.4, §F7.3) memakai `<input type="date">` bawaan peramban —
> memenuhi kebutuhan tanpa menambah dependensi `react-day-picker`.
>
> **8. Harga paket dipindah dari konstanta ke store (§F1.5).**
> `usePengaturanStore` menyimpannya di `localStorage` dan `DialogPerpanjang` membacanya dari sana.
> Nominal invoice lama tidak ikut berubah karena tersimpan per transaksi — dinyatakan eksplisit
> di UI Pengaturan.

---

## 11. Verifikasi

**Per fase:**
```bash
npm run dev      # http://localhost:5173
npm run build    # tsc -b && vite build  ← gerbang sesungguhnya
npm run lint     # oxlint
```

**Tiga jebakan build yang pasti muncul** (konfigurasi TS di proyek ini ketat):
1. `verbatimModuleSyntax: true` → semua impor tipe **wajib** `import type { … }`. Kode hasil generate shadcn kadang tidak begitu.
2. `noUnusedLocals` + `noUnusedParameters` → satu impor menganggur = `npm run build` gagal (padahal `npm run dev` tetap jalan, karena Vite tidak melakukan typecheck). **Jalankan `npm run build` di akhir setiap fase, jangan menumpuk.**
3. `#root` di `index.css` lama membatasi lebar 1126px + `text-align:center` → wajib dihapus di F0, kalau tidak layout dashboard akan aneh dan sulit dilacak.

**Pemeriksaan manual di browser tiap fase:**
- Masuk dengan kredensial demo → refresh halaman → harus tetap masuk
- Ganti tema gelap → refresh → harus tetap gelap
- Tabel: ketik di kotak cari, ganti filter, urutkan kolom, pindah halaman
- Perpanjang satu langganan → cek badge sisa hari berubah → cek entri baru muncul di `/aktivitas`
- Ebook: buat draf → terbitkan → cek statusnya berubah di tabel
- Kecilkan jendela ke 375px → sidebar jadi drawer, **tidak boleh ada scroll horizontal**
- Muat ulang beberapa kali → skeleton harus terlihat (karena jeda buatan 300–800 ms)

---

## 12. Risiko & Asumsi

| Hal | Penanganan |
|---|---|
| Tumpukan sangat baru (Vite 8 / TS 6 / React 19.2 / react-router 8) | Semua peer dep sudah dicek cocok. Kalau CLI shadcn bermasalah, komponennya bisa disalin manual dari registri — bukan penghalang |
| Unggah berkas mock tidak bertahan setelah refresh (`URL.createObjectURL`) | Diterima untuk tahap frontend-only; **UI menampilkan catatan jelas** agar tidak dikira bug |
| Harga paket di §4.1 adalah contoh | Disimpan di satu konstanta + bisa diubah dari halaman Pengaturan |
| Nama field memakai Bahasa Indonesia | Disengaja agar konsisten dengan UI; pemetaan ke backend nanti dilakukan **hanya di `lib/api/`** |
| Symlink rusak `.claude/skills/hallmark` → `website\.agents\...` (folder itu tidak ada; yang benar `website\saas-app\.agents\...`) | Bukan penghalang. Sebutkan saja — perbaiki kalau skill desain `hallmark` mau dipakai |

---

## 13. Di Luar Cakupan — Fase Berikutnya

1. **Fase 2 — Backend & API.** Ganti isi `src/lib/api/` ke HTTP asli, JWT, database.
2. **Fase 3 — Portal user & pendaftaran.** Landing page, pricing, registrasi, pembayaran otomatis (Midtrans/Xendit).
3. **Fase 4 — Aplikasi POS mobile.** Transaksi, master barang & kategori, riwayat, laporan.
4. **Fase 5 — Operasional.** Notifikasi WA/email menjelang habis, export laporan, multi-admin + role.