import { randomBytes } from 'node:crypto';
import { createWriteStream } from 'node:fs';
import { mkdir, readdir, stat, unlink, rename } from 'node:fs/promises';
import { pipeline } from 'node:stream/promises';
import path from 'node:path';

// Batas ukuran file, sesuai janji UI: maksimal 50MB per file.
export const MAX_FILE_SIZE = 50_000_000;

// File sementara dihapus setelah lebih dari 1 jam.
export const TEMP_MAX_AGE_MS = 3600 * 1000;

const UPLOAD_DIR = path.resolve('uploads');
const TEMP_DIR = path.join(UPLOAD_DIR, 'temp');

// Pastikan folder uploads/ dan uploads/temp/ ada saat server start.
export async function ensureDirs() {
  await mkdir(TEMP_DIR, { recursive: true });
}

// Nama file dipendekkan jadi 8 karakter acak (cegah nama asli yang terlalu panjang),
// ekstensi asli tetap dipertahankan.
function randomFilename(ext) {
  const suffix = ext ? '.' + ext : '';
  return randomBytes(4).toString('hex') + suffix;
}

// Buat path file unik di dalam dir; diulang kalau kebetulan sudah ada.
async function uniqueTarget(dir, ext) {
  for (;;) {
    const name = randomFilename(ext);
    const target = path.join(dir, name);
    try {
      await stat(target);
      // File ada — coba nama lain.
    } catch {
      return { name, target };
    }
  }
}

// Hapus file sementara yang umurnya sudah lebih dari 1 jam.
// Dipanggil setiap ada upload (meniru perilaku versi PHP).
export async function cleanupTemp() {
  let entries;
  try {
    entries = await readdir(TEMP_DIR);
  } catch {
    return;
  }
  const now = Date.now();
  await Promise.all(
    entries.map(async (entry) => {
      const file = path.join(TEMP_DIR, entry);
      try {
        const info = await stat(file);
        if (info.isFile() && now - info.mtimeMs >= TEMP_MAX_AGE_MS) {
          await unlink(file);
        }
      } catch {
        // Abaikan file yang gagal di-stat/hapus (mis. sudah hilang).
      }
    })
  );
}

// Pindahkan file staging (yang sudah ditulis ke disk) ke folder tujuan final
// dengan nama acak yang unik. Semua unggahan bersifat sementara: disimpan di
// uploads/temp/ dan dihapus otomatis setelah 1 jam.
async function finalize(stagedPath, originalName) {
  const ext = path.extname(originalName).slice(1).toLowerCase();
  const { name, target } = await uniqueTarget(TEMP_DIR, ext);
  await rename(stagedPath, target);
  const relative = `uploads/temp/${name}`;
  return { name, relative, ext };
}

// Error khusus agar route bisa memetakan ke kode status & pesan yang tepat.
export class UploadError extends Error {
  constructor(message, status = 400) {
    super(message);
    this.status = status;
  }
}

// Proses satu request multipart: stream file ke staging, kumpulkan field,
// validasi, lalu pindahkan ke folder final. `parts` adalah async iterator
// dari @fastify/multipart (request.parts()).
export async function handleUpload(parts) {
  await cleanupTemp();

  let staged = null; // { path, originalName, truncated }

  try {
    for await (const part of parts) {
      if (part.type === 'file') {
        // Hanya proses file pertama yang berisi nama; abaikan field file kosong.
        if (!part.filename) {
          part.file.resume();
          continue;
        }
        const tmpName = randomBytes(8).toString('hex') + '.part';
        const tmpPath = path.join(TEMP_DIR, tmpName);
        await pipeline(part.file, createWriteStream(tmpPath));
        staged = {
          path: tmpPath,
          originalName: part.filename,
          truncated: part.file.truncated,
        };
      }
      // Field non-file (mis. upload_type lama) diabaikan — semua unggahan sementara.
    }

    if (!staged) {
      throw new UploadError('Tidak ada file yang dikirim.', 400);
    }

    // @fastify/multipart menandai file terpotong saat melewati batas ukuran.
    if (staged.truncated) {
      throw new UploadError('Ukuran file terlalu besar. Maksimal 50MB.', 413);
    }

    const result = await finalize(staged.path, staged.originalName);
    staged = null; // sudah dipindah, jangan dihapus di finally.

    return result;
  } finally {
    // Bersihkan file staging kalau terjadi error sebelum dipindahkan.
    if (staged) {
      try {
        await unlink(staged.path);
      } catch {
        /* abaikan */
      }
    }
  }
}

// Jenis file media yang bisa langsung dibuka di browser (tanpa atribut download).
const MEDIA_EXT = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg']);
export function isMedia(ext) {
  return MEDIA_EXT.has(ext);
}
