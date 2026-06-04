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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
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
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            backdrop-filter: blur(10px);
        }
        h2 {
            margin-bottom: 10px;
            font-weight: 600;
            color: #2d3748;
            font-size: 24px;
        }
        p.subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 14px;
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
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 40px 20px;
            background: #f7fafc;
            transition: all 0.3s ease;
        }
        .file-upload-wrapper:hover .file-upload-box {
            border-color: #667eea;
            background: #ebf4ff;
        }
        .file-upload-box svg {
            width: 48px;
            height: 48px;
            fill: #a0aec0;
            margin-bottom: 10px;
            transition: fill 0.3s ease;
        }
        .file-upload-wrapper:hover .file-upload-box svg {
            fill: #667eea;
        }
        .file-upload-box span {
            display: block;
            color: #4a5568;
            font-weight: 600;
        }
        .file-upload-box small {
            color: #a0aec0;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            text-align: left;
            animation: fadeIn 0.5s ease;
        }
        .success {
            background-color: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }
        .error {
            background-color: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }
        .btn-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #48bb78;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
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
            font-size: 14px;
            color: #4a5568;
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
            font-weight: 600;
            font-size: 14px;
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
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
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
            background: #f7fafc;
            border: 2px solid #cbd5e0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .radio-card span.title {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .radio-card span.desc {
            font-size: 11px;
            color: #718096;
        }
        .upload-type input[type="radio"]:checked + .radio-card {
            border-color: #667eea;
            background: #ebf4ff;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.2);
        }
        .upload-type input[type="radio"]:checked + .radio-card span.title {
            color: #4c51bf;
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
                <small>Maksimal ukuran file: 150MB</small>
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

            // Check file size (limit to 150MB)
            if ($_FILES["fileToUpload"]["size"] > 150000000) {
                echo "<div class='message error'><strong>Maaf!</strong> Ukuran file terlalu besar. Maksimal 150MB.</div>";
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
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var fileInput = document.getElementById('fileToUpload');
        if (fileInput.files.length === 0) return;
        
        e.preventDefault();
        
        var submitBtn = document.getElementById('submit-btn');
        var loadingContainer = document.getElementById('loading-container');
        var progressText = document.getElementById('progress-text');
        var progressBar = document.getElementById('progress-bar');
        var messageContainer = document.getElementById('message-container');
        
        submitBtn.style.display = 'none';
        loadingContainer.style.display = 'flex';
        messageContainer.innerHTML = '';
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
            } else {
                messageContainer.innerHTML = "<div class='message error'><strong>Error!</strong> Terjadi kesalahan koneksi ke server.</div>";
                submitBtn.style.display = 'block';
                loadingContainer.style.display = 'none';
            }
        };
        
        xhr.onerror = function() {
            messageContainer.innerHTML = "<div class='message error'><strong>Error!</strong> Terjadi kesalahan koneksi.</div>";
            submitBtn.style.display = 'block';
            loadingContainer.style.display = 'none';
        };
        
        xhr.send(formData);
    });
</script>
</body>
</html>