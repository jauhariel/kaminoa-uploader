import 'dotenv/config';
import { randomBytes } from 'node:crypto';
import { createWriteStream } from 'node:fs';
import { mkdir, readdir, stat, unlink, rename } from 'node:fs/promises';
import { pipeline } from 'node:stream/promises';
import path from 'node:path';
import { S3Client, GetObjectCommand, DeleteObjectCommand, ListObjectsV2Command } from '@aws-sdk/client-s3';
import { Upload } from '@aws-sdk/lib-storage';

const S3_ENDPOINT = process.env.S3_ENDPOINT;
const S3_REGION = process.env.S3_REGION || 'auto';
const S3_ACCESS_KEY_ID = process.env.S3_ACCESS_KEY_ID;
const S3_SECRET_ACCESS_KEY = process.env.S3_SECRET_ACCESS_KEY;
const S3_BUCKET_NAME = process.env.S3_BUCKET_NAME;

const useS3 = S3_ENDPOINT && S3_ACCESS_KEY_ID && S3_SECRET_ACCESS_KEY && S3_BUCKET_NAME;

export const bucketName = S3_BUCKET_NAME;
export const s3 = useS3 ? new S3Client({
  region: S3_REGION,
  endpoint: S3_ENDPOINT,
  credentials: {
    accessKeyId: S3_ACCESS_KEY_ID,
    secretAccessKey: S3_SECRET_ACCESS_KEY,
  }
}) : null;

// Batas ukuran file, sesuai janji UI: maksimal 50MB per file.
export const MAX_FILE_SIZE = 50_000_000;

// File sementara dihapus setelah lebih dari 1 jam.
export const TEMP_MAX_AGE_MS = 3600 * 1000;

const UPLOAD_DIR = path.resolve('uploads');
const TEMP_DIR = path.join(UPLOAD_DIR, 'temp');
const PERM_DIR = path.join(UPLOAD_DIR, 'perm');

// Pastikan folder uploads/ dan uploads/temp/ ada saat server start.
export async function ensureDirs() {
  await mkdir(TEMP_DIR, { recursive: true });
  await mkdir(PERM_DIR, { recursive: true });
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
  if (useS3) {
    try {
      const data = await s3.send(new ListObjectsV2Command({
        Bucket: bucketName,
        Prefix: 'uploads/temp/'
      }));
      
      const now = Date.now();
      if (data.Contents) {
        for (const item of data.Contents) {
          if (now - item.LastModified.getTime() >= TEMP_MAX_AGE_MS) {
            await s3.send(new DeleteObjectCommand({
              Bucket: bucketName,
              Key: item.Key
            })).catch(() => {});
          }
        }
      }
    } catch (err) {
      // Abaikan error cleanup S3 agar tidak mengganggu proses upload
    }
  }

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
async function finalize(stagedPath, originalName, isPermanent = false) {
  const ext = path.extname(originalName).slice(1).toLowerCase();
  const targetDir = isPermanent ? PERM_DIR : TEMP_DIR;
  const targetFolder = isPermanent ? 'uploads/perm' : 'uploads/temp';
  
  const { name, target } = await uniqueTarget(targetDir, ext);
  await rename(stagedPath, target);
  const relative = `${targetFolder}/${name}`;
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

  let staged = null;
  let s3Result = null;
  let s3Truncated = false;
  let s3Key = null;
  let isPermanent = false;

  try {
    for await (const part of parts) {
      if (part.type === 'field' && part.fieldname === 'uploadType') {
        isPermanent = part.value === 'permanent';
        continue;
      }
      if (part.type === 'file') {
        if (!part.filename) {
          part.file.resume();
          continue;
        }
        
        const targetDir = isPermanent ? PERM_DIR : TEMP_DIR;
        const targetFolder = isPermanent ? 'uploads/perm' : 'uploads/temp';
        
        if (useS3) {
          const ext = path.extname(part.filename).slice(1).toLowerCase();
          const { name } = await uniqueTarget(targetDir, ext);
          const key = `${targetFolder}/${name}`;
          s3Key = key;
          
          const upload = new Upload({
            client: s3,
            params: {
              Bucket: bucketName,
              Key: key,
              Body: part.file,
              ContentType: part.mimetype || 'application/octet-stream'
            }
          });
          
          await upload.done();
          
          s3Truncated = part.file.truncated;
          s3Result = { name, relative: `${targetFolder}/${name}`, ext };
          break; // Hentikan proses file berikutnya
        } else {
          const tmpName = randomBytes(8).toString('hex') + '.part';
          const tmpPath = path.join(TEMP_DIR, tmpName);
          await pipeline(part.file, createWriteStream(tmpPath));
          staged = {
            path: tmpPath,
            originalName: part.filename,
            truncated: part.file.truncated,
          };
          break; // Hentikan proses file berikutnya
        }
      }
      // Field non-file diabaikan
    }

    if (useS3) {
      if (!s3Result) {
        throw new UploadError('Tidak ada file yang dikirim.', 400);
      }
      if (s3Truncated) {
        await s3.send(new DeleteObjectCommand({ Bucket: bucketName, Key: s3Key }));
        throw new UploadError('Ukuran file terlalu besar. Maksimal 50MB.', 413);
      }
      return s3Result;
    }

    if (!staged) {
      throw new UploadError('Tidak ada file yang dikirim.', 400);
    }

    if (staged.truncated) {
      throw new UploadError('Ukuran file terlalu besar. Maksimal 50MB.', 413);
    }

    const result = await finalize(staged.path, staged.originalName, isPermanent);
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
