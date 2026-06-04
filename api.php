<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('max_input_time', -1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Balas preflight CORS langsung tanpa proses lebih lanjut.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Helper: kirim respons JSON lalu hentikan eksekusi.
function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'error'   => 'Metode tidak diizinkan. Gunakan POST.',
    ]);
}

// Field file bisa pakai "file" (umum di API) atau "fileToUpload" (sama dengan form web).
$file_field = isset($_FILES['file']) ? 'file' : 'fileToUpload';

if (empty($_FILES[$file_field]['name'])) {
    respond(400, [
        'success' => false,
        'error'   => 'Tidak ada file yang dikirim. Kirim file pada field "file".',
    ]);
}

$upload_type = isset($_POST['upload_type']) ? $_POST['upload_type'] : 'temporary';
if (!in_array($upload_type, ['permanent', 'temporary'], true)) {
    $upload_type = 'temporary';
}

$target_dir = 'uploads/';

// Bersihkan file sementara yang sudah lebih dari 1 jam (sama seperti di index.php).
$temp_dir_check = 'uploads/temp/';
if (file_exists($temp_dir_check)) {
    $files = glob($temp_dir_check . '*');
    $now = time();
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.php') {
            if ($now - filemtime($file) >= 3600) {
                unlink($file);
            }
        }
    }
}

if ($upload_type === 'temporary') {
    $target_dir = 'uploads/temp/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
        file_put_contents($target_dir . 'index.php', "<?php header('Location: ../../'); exit; ?>");
    }
}

$upload = $_FILES[$file_field];

// Tangani error bawaan PHP saat upload.
if ($upload['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File melebihi batas upload_max_filesize di php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL    => 'File hanya terunggah sebagian.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diunggah.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        UPLOAD_ERR_EXTENSION  => 'Ekstensi PHP menghentikan proses unggah file.',
    ];
    $message = isset($errorMessages[$upload['error']]) ? $errorMessages[$upload['error']] : 'Kesalahan unggah tidak diketahui.';
    respond(400, ['success' => false, 'error' => $message]);
}

// Batasi ukuran file ke 50MB, sama dengan form web.
if ($upload['size'] > 50000000) {
    respond(413, [
        'success' => false,
        'error'   => 'Ukuran file terlalu besar. Maksimal 50MB.',
    ]);
}

// Nama file dipendekkan jadi 8 karakter acak, diulang sampai unik.
$extension = pathinfo($upload['name'], PATHINFO_EXTENSION);
do {
    $new_filename = bin2hex(random_bytes(4)) . ($extension ? '.' . $extension : '');
    $target_file = $target_dir . $new_filename;
} while (file_exists($target_file));

if (!move_uploaded_file($upload['tmp_name'], $target_file)) {
    respond(500, [
        'success' => false,
        'error'   => 'Terjadi kesalahan saat menyimpan file di server.',
    ]);
}

// Susun URL absolut ke file hasil unggahan.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$url = $scheme . '://' . $host . $base . '/' . $target_file;

respond(200, [
    'success'    => true,
    'message'    => 'File berhasil diunggah.',
    'filename'   => $new_filename,
    'url'        => $url,
    'size'       => (int) $upload['size'],
    'type'       => $upload_type,
    'expires_in' => $upload_type === 'temporary' ? 3600 : null,
]);
