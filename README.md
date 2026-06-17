# Kaminoa Uploader

Layanan unggah file sederhana. Pilih file, unggah, lalu dapatkan tautan untuk dibagikan.
Semua file bersifat sementara dan dihapus otomatis dalam 1 jam. Versi Node.js (Fastify + EJS).

## Menjalankan

```bash
npm install
npm start            # http://localhost:5264
npm run dev          # mode watch (auto-restart saat file berubah)
```

Variabel lingkungan opsional: `PORT` (default `5264`), `HOST` (default `0.0.0.0`).

## Rute

| Metode | Path | Keterangan |
| --- | --- | --- |
| `GET` | `/` | Halaman web + form upload |
| `POST` | `/` | Upload via form (balas HTML berisi pesan) |
| `POST` | `/api` | Upload via API (balas JSON) |
| `GET` | `/docs` | Dokumentasi API |
| `GET` | `/uploads/...` | Akses file hasil unggahan |

> `/api.php` dan `/docs.php` tetap dilayani sebagai alias agar kompatibel dengan versi PHP lama.

## API

`POST /api` dengan `Content-Type: multipart/form-data`:

- `file` — file yang diunggah (wajib, maks. 50MB)

Semua unggahan bersifat sementara dan dihapus otomatis dalam 1 jam.

```bash
curl -X POST http://localhost:5264/api \
  -F "file=@/path/ke/file.jpg"
```

Respons sukses:

```json
{
  "success": true,
  "message": "File berhasil diunggah.",
  "filename": "a1b2c3d4.jpg",
  "url": "http://localhost:5264/uploads/temp/a1b2c3d4.jpg",
  "type": "temporary",
  "expires_in": 3600
}
```

## Catatan teknis

- Nama file diacak jadi 8 karakter heksadesimal, ekstensi asli dipertahankan.
- File sementara (`uploads/temp/`) dibersihkan otomatis tiap kali ada upload, untuk file yang umurnya > 1 jam.
- Batas ukuran 50MB diberlakukan di server (Fastify multipart) maupun di sisi klien (JavaScript).

## Struktur

```
server.js        — server Fastify, routing, handler error
lib/storage.js   — logika simpan file, penamaan acak, cleanup, validasi
views/           — template EJS (index, docs, error)
uploads/temp/    — file sementara (semua unggahan), dihapus otomatis tiap > 1 jam
```
