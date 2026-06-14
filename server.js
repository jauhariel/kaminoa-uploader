import path from 'node:path';
import { fileURLToPath } from 'node:url';
import Fastify from 'fastify';
import fastifyMultipart from '@fastify/multipart';
import fastifyStatic from '@fastify/static';
import fastifyView from '@fastify/view';
import ejs from 'ejs';
import {
  ensureDirs,
  handleUpload,
  isMedia,
  UploadError,
  MAX_FILE_SIZE,
} from './lib/storage.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
// Port khas untuk Kaminoa: 5264 = "KAMI" pada keypad telepon (K=5, A=2, M=6, I=4).
const PORT = process.env.PORT || 5264;
const HOST = process.env.HOST || '0.0.0.0';

const app = Fastify({ logger: true });

await app.register(fastifyMultipart, {
  // throwFileSizeLimit: false → file yang melewati batas ditandai `truncated`
  // (bukan melempar error), supaya kita bisa balas 413 dengan pesan yang rapi.
  throwFileSizeLimit: false,
  limits: { fileSize: MAX_FILE_SIZE, files: 1 },
});

await app.register(fastifyView, {
  engine: { ejs },
  root: path.join(__dirname, 'views'),
  viewExt: 'ejs',
});

// Serve file hasil unggahan di /uploads/...
await app.register(fastifyStatic, {
  root: path.join(__dirname, 'uploads'),
  prefix: '/uploads/',
  index: false,
  list: false,
});

// Susun base URL absolut dari request (pengganti dirname(SCRIPT_NAME) di PHP).
function baseUrl(req) {
  const proto = req.headers['x-forwarded-proto'] || req.protocol || 'http';
  const host = req.headers['x-forwarded-host'] || req.headers.host;
  return `${proto}://${host}`;
}

// ---- Halaman web ----

app.get('/', async (req, reply) => {
  return reply.view('index', { message: null });
});

// Upload lewat form web — balas HTML berisi blok .message (dipakai AJAX & non-AJAX).
app.post('/', async (req, reply) => {
  let message;
  try {
    const result = await handleUpload(req.parts());
    const downloadAttr = isMedia(result.ext) ? '' : 'download';
    const tempMsg =
      result.uploadType === 'temporary'
        ? " <br><small style='color:#e53e3e;'>(File ini akan dihapus otomatis dalam 1 jam)</small>"
        : '';
    message = {
      type: 'success',
      html:
        `<strong>Berhasil!</strong> File ${result.name} telah diunggah.${tempMsg}<br>` +
        `<a href='/${result.relative}' target='_blank' ${downloadAttr} class='btn-link'>Buka / Download File</a>`,
    };
  } catch (err) {
    const text =
      err instanceof UploadError ? err.message : 'Terjadi kesalahan saat mengunggah file Anda.';
    message = { type: 'error', html: `<strong>Gagal!</strong> ${text}` };
  }
  return reply.view('index', { message });
});

// ---- API JSON ----

async function apiUpload(req, reply) {
  try {
    const result = await handleUpload(req.parts());
    return reply.code(200).send({
      success: true,
      message: 'File berhasil diunggah.',
      filename: result.name,
      url: `${baseUrl(req)}/${result.relative}`,
      type: result.uploadType,
      expires_in: result.uploadType === 'temporary' ? 3600 : null,
    });
  } catch (err) {
    const status = err instanceof UploadError ? err.status : 500;
    const text =
      err instanceof UploadError ? err.message : 'Terjadi kesalahan saat menyimpan file di server.';
    return reply.code(status).send({ success: false, error: text });
  }
}

app.post('/api', apiUpload);
app.post('/api.php', apiUpload); // kompatibilitas dengan dokumentasi lama

// Metode selain POST ke endpoint API → 405 (sesuai dokumentasi).
async function apiMethodNotAllowed(req, reply) {
  return reply.code(405).send({
    success: false,
    error: 'Metode tidak diizinkan. Gunakan POST.',
  });
}
app.get('/api', apiMethodNotAllowed);
app.get('/api.php', apiMethodNotAllowed);

// ---- Dokumentasi ----

async function docs(req, reply) {
  return reply.view('docs', { apiUrl: `${baseUrl(req)}/api` });
}
app.get('/docs', docs);
app.get('/docs.php', docs);

// ---- Halaman error ----

const ERRORS = {
  403: { emoji: '🔒', title: 'Akses Ditolak', desc: 'Kamu tidak punya izin untuk membuka direktori ini. Isinya bersifat privat.' },
  404: { emoji: '🧭', title: 'Halaman Tidak Ditemukan', desc: 'Halaman yang kamu cari tidak ada atau mungkin sudah dipindahkan.' },
  413: { emoji: '📦', title: 'File Terlalu Besar', desc: 'Ukuran file melebihi batas maksimal 50MB. Silakan pilih file yang lebih kecil.' },
  500: { emoji: '⚙️', title: 'Kesalahan Server', desc: 'Terjadi kesalahan di sisi server. Coba lagi beberapa saat lagi.' },
};

function renderError(reply, code) {
  const c = ERRORS[code] ? code : 404;
  return reply.code(c).view('error', { code: c, ...ERRORS[c] });
}

app.get('/error', async (req, reply) => {
  const code = parseInt(req.query.code, 10) || 404;
  return renderError(reply, code);
});

// 404 untuk route yang tidak dikenal.
app.setNotFoundHandler(async (req, reply) => renderError(reply, 404));

// Handler error global — petakan limit ukuran file ke halaman/JSON yang tepat.
app.setErrorHandler(async (err, req, reply) => {
  req.log.error(err);
  const tooBig = err.statusCode === 413 || err.code === 'FST_REQ_FILE_TOO_LARGE';
  const code = tooBig ? 413 : err.statusCode || 500;
  // Request ke API balas JSON, selain itu balas halaman HTML.
  if (req.url.startsWith('/api')) {
    return reply.code(code).send({
      success: false,
      error: tooBig ? 'Ukuran file terlalu besar. Maksimal 50MB.' : 'Terjadi kesalahan server.',
    });
  }
  return renderError(reply, ERRORS[code] ? code : 500);
});

await ensureDirs();
app.listen({ port: PORT, host: HOST }, (err, address) => {
  if (err) {
    app.log.error(err);
    process.exit(1);
  }
  app.log.info(`Kaminoa Uploader berjalan di ${address}`);
});
