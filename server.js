import 'dotenv/config';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import Fastify from 'fastify';
import fastifyMultipart from '@fastify/multipart';
import fastifyRateLimit from '@fastify/rate-limit';
import fastifyStatic from '@fastify/static';
import fastifyView from '@fastify/view';
import ejs from 'ejs';
import {
  ensureDirs,
  handleUpload,
  isMedia,
  UploadError,
  MAX_FILE_SIZE,
  s3,
  bucketName,
} from './lib/storage.js';
import { GetObjectCommand } from '@aws-sdk/client-s3';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
// Port khas untuk Kaminoa: 5264 = "KAMI" pada keypad telepon (K=5, A=2, M=6, I=4).
const PORT = process.env.PORT || 5264;
const HOST = process.env.HOST || '0.0.0.0';

const app = Fastify({ logger: true });

// ---- Error Handling ----
const ERRORS = {
  403: { emoji: '🔒', title: 'Akses Ditolak', desc: 'Kamu tidak punya izin untuk membuka direktori ini. Isinya bersifat privat.' },
  404: { emoji: '🧭', title: 'Halaman Tidak Ditemukan', desc: 'Halaman yang kamu cari tidak ada atau mungkin sudah dipindahkan.' },
  413: { emoji: '📦', title: 'File Terlalu Besar', desc: 'Ukuran file melebihi batas maksimal 50MB. Silakan pilih file yang lebih kecil.' },
  429: { emoji: '🛑', title: 'Terlalu Banyak Permintaan', desc: 'Sistem mendeteksi aktivitas unggahan yang tidak wajar. Akses kamu ditangguhkan sementara waktu.' },
  500: { emoji: '⚙️', title: 'Kesalahan Server', desc: 'Terjadi kesalahan di sisi server. Coba lagi beberapa saat lagi.' },
};

function renderError(reply, code) {
  const c = ERRORS[code] ? code : 404;
  return reply.code(c).view('error', { code: c, ...ERRORS[c] });
}

const bannedIPs = new Map();

// Daftar IP yang kebal dari limitasi (Admin/Trusted API)
const whitelisted = (process.env.WHITELISTED_IPS || '127.0.0.1').split(',').map(ip => ip.trim());
const ALLOW_LIST = new Set(whitelisted);

// Hook anti-spam: berjalan sebelum rate-limit
app.addHook('onRequest', async (req, reply) => {
  // Hanya blokir akses unggahan
  if (req.method === 'POST') {
    const ip = req.headers['x-forwarded-for'] || req.ip;
    
    // Bypass limit untuk IP terpercaya
    if (ALLOW_LIST.has(ip)) return;
    
    const banExpire = bannedIPs.get(ip);
    
    if (banExpire) {
      if (Date.now() < banExpire) {
        if (req.url.startsWith('/api')) {
          return reply.code(429).send({ success: false, error: 'Sistem mendeteksi spam. Kamu diblokir selama 10 menit.' });
        }
        ERRORS[429].desc = 'Sistem mendeteksi spam. Kamu diblokir dari mengunggah file selama 10 menit.';
        return renderError(reply, 429);
      } else {
        bannedIPs.delete(ip);
      }
    }
  }
});

// Batasi akses: 30 request per 1 menit untuk tiap IP
await app.register(fastifyRateLimit, {
  max: 30,
  timeWindow: '1 minute',
  allowList: (req) => {
    const ip = req.headers['x-forwarded-for'] || req.ip;
    return ALLOW_LIST.has(ip);
  },
  keyGenerator: (req) => req.headers['x-forwarded-for'] || req.ip,
  onExceeded: (req, key) => {
    // Saat melebihi batas, blokir IP ini selama 10 menit (600000 ms)
    bannedIPs.set(key, Date.now() + 10 * 60 * 1000);
  }
});

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

if (s3) {
  // Serve dari S3
  app.get('/uploads/temp/:filename', async (req, reply) => {
    const { filename } = req.params;
    const key = `uploads/temp/${filename}`;
    
    try {
      const data = await s3.send(new GetObjectCommand({ Bucket: bucketName, Key: key }));
      
      const ext = path.extname(filename).slice(1).toLowerCase();
      if (!isMedia(ext)) {
        reply.header('Content-Disposition', 'attachment');
        reply.header('X-Content-Type-Options', 'nosniff');
      }
      
      if (data.ContentType) reply.header('Content-Type', data.ContentType);
      if (data.ContentLength) reply.header('Content-Length', data.ContentLength);
      
      return reply.send(data.Body);
    } catch (err) {
      if (err.name === 'NoSuchKey' || err.name === 'NotFound' || err.$metadata?.httpStatusCode === 404) {
        return renderError(reply, 404);
      }
      req.log.error(err);
      return renderError(reply, 500);
    }
  });
} else {
  // Serve file hasil unggahan di /uploads/...
  await app.register(fastifyStatic, {
    root: path.join(__dirname, 'uploads'),
    prefix: '/uploads/',
    index: false,
    list: false,
    // File media (gambar/video) boleh tampil inline di browser; selain itu paksa
    // download. Ini mencegah HTML/SVG di-render di origin kita (phishing/XSS) dan
    // memastikan file seperti .html benar-benar ter-download, bukan jadi hosting.
    setHeaders(res, filePath) {
      const ext = path.extname(filePath).slice(1).toLowerCase();
      if (!isMedia(ext)) {
        res.setHeader('Content-Disposition', 'attachment');
        res.setHeader('X-Content-Type-Options', 'nosniff');
      }
    },
  });
}

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
    message = {
      type: 'success',
      html:
        `<strong>Berhasil!</strong> File ${result.name} telah diunggah.` +
        " <br><small style='color:#e53e3e;'>(File ini akan dihapus otomatis dalam 1 jam)</small><br>" +
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
      type: 'temporary',
      expires_in: 3600,
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

// ---- SEO: robots.txt & sitemap.xml ----

app.get('/robots.txt', async (req, reply) => {
  const body =
    'User-agent: *\n' +
    'Allow: /$\n' +
    'Allow: /docs\n' +
    // File unggahan bersifat privat & sementara — jangan di-index.
    'Disallow: /uploads/\n' +
    'Disallow: /api\n' +
    'Disallow: /error\n\n' +
    `Sitemap: ${baseUrl(req)}/sitemap.xml\n`;
  return reply.type('text/plain').send(body);
});

app.get('/sitemap.xml', async (req, reply) => {
  const base = baseUrl(req);
  const body =
    '<?xml version="1.0" encoding="UTF-8"?>\n' +
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
    `  <url><loc>${base}/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>\n` +
    `  <url><loc>${base}/docs</loc><changefreq>monthly</changefreq><priority>0.8</priority></url>\n` +
    '</urlset>\n';
  return reply.type('application/xml').send(body);
});

// ---- Halaman error ----

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
    let errorMessage = 'Terjadi kesalahan server.';
    if (tooBig) errorMessage = 'Ukuran file terlalu besar. Maksimal 50MB.';
    if (code === 429) errorMessage = 'Terlalu banyak permintaan unggahan. Coba lagi nanti.';
    
    return reply.code(code).send({
      success: false,
      error: errorMessage,
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
