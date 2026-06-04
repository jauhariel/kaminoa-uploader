<?php
$code = isset($_GET['code']) ? (int) $_GET['code'] : 404;

$errors = [
    403 => [
        'emoji' => '🔒',
        'title' => 'Akses Ditolak',
        'desc'  => 'Kamu tidak punya izin untuk membuka direktori ini. Isinya bersifat privat.',
    ],
    404 => [
        'emoji' => '🧭',
        'title' => 'Halaman Tidak Ditemukan',
        'desc'  => 'Halaman yang kamu cari tidak ada atau mungkin sudah dipindahkan.',
    ],
    413 => [
        'emoji' => '📦',
        'title' => 'File Terlalu Besar',
        'desc'  => 'Ukuran file melebihi batas maksimal 150MB. Silakan pilih file yang lebih kecil.',
    ],
    500 => [
        'emoji' => '⚙️',
        'title' => 'Kesalahan Server',
        'desc'  => 'Terjadi kesalahan di sisi server. Coba lagi beberapa saat lagi.',
    ],
];

if (!isset($errors[$code])) {
    $code = 404;
}
$e = $errors[$code];
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $code . ' — ' . htmlspecialchars($e['title']); ?> | Kaminoa Uploader</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            padding: 20px;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 450px;
            padding: 50px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease;
        }
        .emoji {
            font-size: 56px;
            line-height: 1;
            margin-bottom: 16px;
        }
        .code {
            font-size: 72px;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 22px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }
        p {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .btn-home {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }
        .btn-home:active {
            transform: translateY(0);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="emoji"><?php echo $e['emoji']; ?></div>
        <div class="code"><?php echo $code; ?></div>
        <h1><?php echo htmlspecialchars($e['title']); ?></h1>
        <p><?php echo htmlspecialchars($e['desc']); ?></p>
        <a href="/" class="btn-home">← Kembali ke Beranda</a>
    </div>
</body>
</html>
