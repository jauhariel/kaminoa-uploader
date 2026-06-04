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
        'desc'  => 'Ukuran file melebihi batas maksimal 50MB. Silakan pilih file yang lebih kecil.',
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700;900&family=EB+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'EB Garamond', serif;
            background: #2b2520;
            background-image:
                radial-gradient(circle at 50% 18%, rgba(255, 255, 255, 0.06) 0, transparent 55%),
                linear-gradient(135deg, #3a312a 0%, #1f1b17 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f3e9cf;
            padding: 20px;
        }
        .container {
            position: relative;
            width: 100%;
            max-width: 460px;
            padding: 56px 42px 56px 50px;
            text-align: center;
            color: #f3e9cf;
            /* Sampul buku: kain hijau-teal tua dengan tekstur tenun halus */
            background:
                repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.05) 0 1px, transparent 1px 3px),
                repeating-linear-gradient(90deg, rgba(0, 0, 0, 0.05) 0 1px, transparent 1px 3px),
                linear-gradient(135deg, #2a6f6a 0%, #1d4f4b 60%, #163d3a 100%);
            border-radius: 3px 12px 12px 3px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        /* Punggung buku (spine) di tepi kiri */
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 16px;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.05));
            border-right: 1px solid rgba(232, 200, 122, 0.3);
        }
        /* Bingkai garis emas ganda */
        .container::after {
            content: "";
            position: absolute;
            top: 16px;
            right: 16px;
            bottom: 16px;
            left: 30px;
            border: 1px solid rgba(232, 200, 122, 0.55);
            box-shadow: inset 0 0 0 3px rgba(232, 200, 122, 0.16);
            border-radius: 3px;
            pointer-events: none;
        }
        .container > * {
            position: relative;
            z-index: 1;
        }
        .emoji {
            font-size: 56px;
            line-height: 1;
            margin-bottom: 16px;
        }
        .code {
            font-family: 'Playfair Display', serif;
            font-size: 80px;
            font-weight: 900;
            line-height: 1;
            color: #e8c87a;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.55), 0 0 18px rgba(232, 200, 122, 0.3);
            margin-bottom: 10px;
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            color: #f3e9cf;
            margin-bottom: 12px;
        }
        p {
            font-family: 'EB Garamond', serif;
            color: #cdbf97;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-home {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #f1d589 0%, #d9b65f 50%, #c69d48 100%);
            color: #163d3a;
            text-decoration: none;
            border-radius: 8px;
            font-family: 'EB Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 0 #a8842f;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
        .btn-home:active {
            transform: translateY(0);
            box-shadow: 0 1px 0 #a8842f;
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
