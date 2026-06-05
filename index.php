<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('max_input_time', -1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaminoa Uploader</title>
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
            padding: 52px 42px 52px 50px;
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
        /* Bingkai garis emas ganda (debossed) */
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
        /* Pastikan isi tampil di atas bingkai */
        .container > * {
            position: relative;
            z-index: 1;
        }
        h2 {
            margin-bottom: 8px;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 40px;
            line-height: 1.15;
            letter-spacing: 0.5px;
            color: #e8c87a;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.55), 0 0 16px rgba(232, 200, 122, 0.25);
        }
        p.subtitle {
            font-family: 'EB Garamond', serif;
            font-style: italic;
            color: #cdbf97;
            margin-bottom: 30px;
            font-size: 18px;
        }
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-upload-box {
            border: 2px dashed rgba(232, 200, 122, 0.5);
            border-radius: 10px;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.06);
            transition: all 0.3s ease;
        }
        .file-upload-wrapper:hover .file-upload-box {
            border-color: #e8c87a;
            background: rgba(232, 200, 122, 0.12);
        }
        .file-upload-box svg {
            width: 48px;
            height: 48px;
            fill: rgba(232, 200, 122, 0.7);
            margin-bottom: 10px;
            transition: fill 0.3s ease;
        }
        .file-upload-wrapper:hover .file-upload-box svg {
            fill: #e8c87a;
        }
        .file-upload-box span {
            display: block;
            color: #f3e9cf;
            font-weight: 600;
            font-size: 18px;
        }
        .file-upload-box small {
            color: #b8ad88;
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #f1d589 0%, #d9b65f 50%, #c69d48 100%);
            color: #163d3a;
            border: none;
            border-radius: 8px;
            font-family: 'EB Garamond', serif;
            font-size: 20px;
            letter-spacing: 0.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 3px 0 #a8842f;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .message {
            position: relative;
            margin-top: 26px;
            padding: 18px 20px;
            border-radius: 4px;
            font-family: 'EB Garamond', serif;
            font-size: 18px;
            line-height: 1.5;
            text-align: left;
            box-shadow: 3px 5px 12px rgba(0, 0, 0, 0.18);
            transform: rotate(-1.2deg);
            animation: fadeIn 0.5s ease;
        }
        /* Selotip di tengah atas memo */
        .message::before {
            content: "";
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%) rotate(2deg);
            width: 80px;
            height: 22px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px dashed rgba(0, 0, 0, 0.12);
        }
        .message strong {
            font-size: 19px;
        }
        .success {
            background-color: #d7f5c2;
            color: #2f6b1f;
            border-left: 5px solid #5fb83d;
        }
        .error {
            background-color: #ffd6d6;
            color: #9b2c2c;
            border-left: 5px solid #f56565;
            transform: rotate(1.2deg);
        }
        .btn-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #48bb78;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-family: 'EB Garamond', serif;
            font-weight: 600;
            font-size: 17px;
            transition: background 0.3s;
        }
        .btn-link:hover {
            background: #38a169;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        #file-name {
            font-size: 16px;
            color: #e8c87a;
            font-weight: 600;
            word-break: break-all;
            margin-bottom: 15px;
        }
        #file-name:empty {
            display: none;
        }
        .loader {
            border: 3px solid #cbd5e0;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #loading-container {
            display: none;
            background: #ebf4ff;
            padding: 15px;
            border-radius: 8px;
            color: #4c51bf;
            font-family: 'EB Garamond', serif;
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .progress-wrapper {
            width: 100%;
            background-color: #cbd5e0;
            border-radius: 4px;
            margin-top: 12px;
            overflow: hidden;
        }
        .progress-bar {
            height: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.1s linear;
        }
        #message-container {
            width: 100%;
        }
        .duration-title {
            font-family: 'EB Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            color: #cdbf97;
            margin-bottom: 10px;
            text-align: left;
        }
        .upload-type {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .upload-type label {
            flex: 1;
            position: relative;
            cursor: pointer;
        }
        .upload-type input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .radio-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
            background: rgba(255, 255, 255, 0.06);
            border: 2px solid rgba(232, 200, 122, 0.35);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .radio-card span.title {
            font-weight: 600;
            color: #f3e9cf;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .radio-card span.desc {
            font-size: 13px;
            color: #b8ad88;
        }
        .upload-type input[type="radio"]:checked + .radio-card {
            border-color: #e8c87a;
            background: rgba(232, 200, 122, 0.15);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .upload-type input[type="radio"]:checked + .radio-card span.title {
            color: #f6e6b8;
        }
        .faq {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid rgba(232, 200, 122, 0.25);
            text-align: left;
        }
        .faq-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 22px;
            color: #e8c87a;
            margin-bottom: 14px;
            text-align: center;
        }
        .faq details {
            border-bottom: 1px solid rgba(232, 200, 122, 0.18);
            padding: 4px 0;
        }
        .faq summary {
            cursor: pointer;
            list-style: none;
            padding: 10px 28px 10px 4px;
            position: relative;
            font-size: 18px;
            font-weight: 600;
            color: #f3e9cf;
            transition: color 0.2s ease;
        }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary:hover { color: #f6e6b8; }
        /* Tanda + yang berubah jadi × saat terbuka */
        .faq summary::after {
            content: "+";
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            color: #e8c87a;
            transition: transform 0.2s ease;
        }
        .faq details[open] summary::after {
            content: "×";
        }
        .faq details p {
            padding: 0 4px 12px;
            font-size: 16px;
            line-height: 1.55;
            color: #cdbf97;
        }
        .faq details p a {
            color: #e8c87a;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px dotted rgba(232, 200, 122, 0.6);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Kaminoa Uploader</h2>
    <p class="subtitle">Pilih file yang ingin Anda unggah ke server</p>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="file-upload-wrapper">
            <div class="file-upload-box">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                <span>Klik atau Seret File ke Sini</span>
                <small>Maksimal ukuran file: 50MB</small>
            </div>
            <input type="file" name="fileToUpload" id="fileToUpload" onchange="document.getElementById('file-name').textContent = this.files[0] ? 'File terpilih: ' + this.files[0].name : ''">
        </div>
        <div id="file-name"></div>
        <div class="duration-title">Pilih Durasi File:</div>
        <div class="upload-type">
            <label>
                <input type="radio" name="upload_type" value="permanent">
                <div class="radio-card">
                    <span class="title">Permanen</span>
                    <span class="desc">Disimpan selamanya</span>
                </div>
            </label>
            <label>
                <input type="radio" name="upload_type" value="temporary" checked>
                <div class="radio-card">
                    <span class="title">Sementara</span>
                    <span class="desc">Dihapus dalam 1 jam</span>
                </div>
            </label>
        </div>
        <button type="submit" name="submit" id="submit-btn">Unggah File</button>
        <div id="loading-container">
            <div style="display: flex; align-items: center;">
                <div class="loader"></div>
                <span id="progress-text">Sedang mengunggah... 0%</span>
            </div>
            <div class="progress-wrapper">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
        </div>
    </form>
    <div id="message-container"></div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $upload_type = isset($_POST['upload_type']) ? $_POST['upload_type'] : 'permanent';
        $target_dir = "uploads/";
        
        // Hapus file sementara yang lebih dari 1 jam (berjalan setiap ada upload apapun)
        $temp_dir_check = "uploads/temp/";
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
            $target_dir = "uploads/temp/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
                file_put_contents($target_dir . "index.php", "<?php header('Location: ../../'); exit; ?>");
            }
        }

        $extension = pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION);
        // Nama file dipendekkan jadi 8 karakter acak (cegah nama asli yang terlalu panjang).
        // Diulang kalau kebetulan sudah ada, supaya tetap unik.
        do {
            $new_filename = bin2hex(random_bytes(4)) . ($extension ? '.' . $extension : '');
            $target_file = $target_dir . $new_filename;
        } while (file_exists($target_file));
        $uploadOk = 1;
        $fileType = strtolower($extension);

        // Check if file was selected
        if (empty($_FILES["fileToUpload"]["name"])) {
            echo "<div class='message error'><strong>Error!</strong> Silakan pilih file untuk diunggah.</div>";
            $uploadOk = 0;
        } elseif ($_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
            $uploadError = $_FILES["fileToUpload"]["error"];
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'File yang diunggah melebihi batas upload_max_filesize di php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'File yang diunggah melebihi batas MAX_FILE_SIZE di form HTML.',
                UPLOAD_ERR_PARTIAL    => 'File hanya terunggah sebagian.',
                UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diunggah.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                UPLOAD_ERR_EXTENSION  => 'Ekstensi PHP menghentikan proses unggah file.',
            ];
            $message = isset($errorMessages[$uploadError]) ? $errorMessages[$uploadError] : 'Kesalahan unggah tidak diketahui.';
            echo "<div class='message error'><strong>Gagal!</strong> " . $message . "</div>";
            $uploadOk = 0;
        } else {
            // Check if file already exists
            if (file_exists($target_file)) {
                echo "<div class='message error'><strong>Maaf!</strong> File sudah ada.</div>";
                $uploadOk = 0;
            }

            // Check file size (limit to 50MB)
            if ($_FILES["fileToUpload"]["size"] > 50000000) {
                echo "<div class='message error'><strong>Maaf!</strong> Ukuran file terlalu besar. Maksimal 50MB.</div>";
                $uploadOk = 0;
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "<div class='message error'><strong>Gagal!</strong> File Anda tidak dapat diunggah.</div>";
            // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                    $fileName = htmlspecialchars($new_filename);
                    $isMedia = in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg']);
                    $downloadAttr = $isMedia ? '' : 'download';
                    $tempMsg = ($upload_type === 'temporary') ? " <br><small style='color:#e53e3e;'>(File ini akan dihapus otomatis dalam 1 jam)</small>" : "";
                    echo "<div class='message success'><strong>Berhasil!</strong> File ". $fileName . " telah diunggah." . $tempMsg . "<br>";
                    echo "<a href='". $target_file ."' target='_blank' ". $downloadAttr ." class='btn-link'>Buka / Download File</a></div>";
                } else {
                    echo "<div class='message error'><strong>Maaf!</strong> Terjadi kesalahan saat mengunggah file Anda.</div>";
                }
            }
        }
    }
    ?>

    <div class="faq">
        <h3 class="faq-title">Pertanyaan Umum</h3>
        <details>
            <summary>Apa itu Kaminoa Uploader?</summary>
            <p>Kaminoa Uploader adalah layanan unggah file sederhana untuk berbagi file dengan cepat. Pilih file, tentukan durasinya, lalu dapatkan tautan untuk dibagikan.</p>
        </details>
        <details>
            <summary>Berapa lama file saya disimpan?</summary>
            <p>File <strong>Sementara</strong> dihapus otomatis dalam 1 jam. File <strong>Permanen</strong> disimpan selama mungkin tanpa batas waktu.</p>
        </details>
        <details>
            <summary>Berapa ukuran file maksimal?</summary>
            <p>Maksimal 50MB per file. File yang lebih besar dari itu akan ditolak.</p>
        </details>
        <details>
            <summary>Apakah nama file asli saya tetap dipakai?</summary>
            <p>Tidak. Nama file diganti dengan 8 karakter acak demi privasi dan kerapian, tapi ekstensinya tetap dipertahankan.</p>
        </details>
        <details>
            <summary>Apakah ada batasan jenis file?</summary>
            <p>Tidak ada batasan jenis file. Gambar dan video bisa langsung dibuka di browser, file lain otomatis diunduh.</p>
        </details>
        <details>
            <summary>Bisakah saya mengunggah lewat kode/program?</summary>
            <p>Bisa. Lihat <a href="docs.php">halaman API &amp; dokumentasi</a> untuk contoh cURL dan JavaScript.</p>
        </details>
    </div>
</div>

<script>
    // Batas ukuran file, harus sama dengan pengecekan di PHP (50MB).
    var MAX_FILE_SIZE = 50000000;

    function scrollToMessage() {
        var el = document.querySelector('.message');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function showError(msg) {
        document.getElementById('message-container').innerHTML =
            "<div class='message error'><strong>Maaf!</strong> " + msg + "</div>";
        scrollToMessage();
    }

    // Bersihkan pesan sukses/error lama begitu user memilih file baru,
    // baik pesan dari AJAX (#message-container) maupun pesan render PHP (.message).
    document.getElementById('fileToUpload').addEventListener('change', function() {
        document.getElementById('message-container').innerHTML = '';
        document.querySelectorAll('.message').forEach(function(el) { el.remove(); });

        // Validasi ukuran langsung saat file dipilih, tanpa perlu upload dulu.
        if (this.files[0] && this.files[0].size > MAX_FILE_SIZE) {
            this.value = '';
            document.getElementById('file-name').textContent = '';
            showError('Ukuran file terlalu besar. Maksimal 50MB.');
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        var fileInput = document.getElementById('fileToUpload');
        if (fileInput.files.length === 0) return;

        // Cegah upload kalau file melebihi batas (pengaman kedua selain saat memilih).
        if (fileInput.files[0].size > MAX_FILE_SIZE) {
            e.preventDefault();
            fileInput.value = '';
            document.getElementById('file-name').textContent = '';
            showError('Ukuran file terlalu besar. Maksimal 50MB.');
            return;
        }

        e.preventDefault();
        
        var submitBtn = document.getElementById('submit-btn');
        var loadingContainer = document.getElementById('loading-container');
        var progressText = document.getElementById('progress-text');
        var progressBar = document.getElementById('progress-bar');
        var messageContainer = document.getElementById('message-container');
        
        submitBtn.style.display = 'none';
        loadingContainer.style.display = 'flex';
        messageContainer.innerHTML = '';
        // Hapus juga pesan lama yang dirender PHP (di luar message-container) agar tidak nyangkut.
        document.querySelectorAll('.message').forEach(function(el) { el.remove(); });
        progressText.textContent = 'Sedang mengunggah... 0%';
        progressBar.style.width = '0%';
        
        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();
        
        xhr.open('POST', '', true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percentComplete = Math.round((e.loaded / e.total) * 100);
                progressText.textContent = 'Sedang mengunggah... ' + percentComplete + '%';
                progressBar.style.width = percentComplete + '%';
                
                if (percentComplete === 100) {
                    progressText.textContent = 'Memproses file di server...';
                }
            }
        };
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(xhr.responseText, 'text/html');
                var message = doc.querySelector('.message');

                if (message) {
                    messageContainer.innerHTML = message.outerHTML;
                }

                submitBtn.style.display = 'block';
                loadingContainer.style.display = 'none';
                fileInput.value = '';
                document.getElementById('file-name').textContent = '';
                scrollToMessage();
            } else {
                messageContainer.innerHTML = "<div class='message error'><strong>Error!</strong> Terjadi kesalahan koneksi ke server.</div>";
                submitBtn.style.display = 'block';
                loadingContainer.style.display = 'none';
                scrollToMessage();
            }
        };

        xhr.onerror = function() {
            messageContainer.innerHTML = "<div class='message error'><strong>Error!</strong> Terjadi kesalahan koneksi.</div>";
            submitBtn.style.display = 'block';
            loadingContainer.style.display = 'none';
            scrollToMessage();
        };
        
        xhr.send(formData);
    });

    // Jika pesan sudah ada saat halaman dimuat (submit non-AJAX), langsung scroll ke sana.
    window.addEventListener('load', scrollToMessage);
</script>
</body>
</html>