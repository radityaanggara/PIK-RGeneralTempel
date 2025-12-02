<?php
// save_konseling.php
// Menyimpan pesan konseling ke file database.txt (JSON per baris).
// Mengembalikan JSON response untuk AJAX; jika diakses via browser langsung, tampilkan pesan sederhana.

// CONFIG
$file = __DIR__ . '/database.txt';
$min_masalah_len = 5;
$min_age = 10;
$max_age = 120;

// Helpers
function send_json($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize($s) {
    $s = trim($s);
    $s = strip_tags($s);
    return $s;
}

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If browser visit, show simple HTML
    if (php_sapi_name() !== 'cli') {
        echo "<p>Endpoint untuk menerima POST dari form.</p>";
        echo "<p>Kembali ke <a href='../contact.html'>Contact</a></p>";
        exit;
    }
    http_response_code(405);
    send_json(['status'=>'error','message'=>'Invalid request method']);
}

// Read inputs (support both application/x-www-form-urlencoded and fetch FormData)
$nama = isset($_POST['nama']) ? sanitize($_POST['nama']) : '';
$umur = isset($_POST['umur']) ? trim($_POST['umur']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : (isset($_POST['kontak']) ? sanitize($_POST['kontak']) : '');
$masalah = isset($_POST['masalah']) ? trim($_POST['masalah']) : '';

// Basic validation
if ($umur === '' || $masalah === '') {
    send_json(['status'=>'error','message'=>'Field umur dan masalah wajib diisi.']);
}
if (!is_numeric($umur) || intval($umur) < $min_age || intval($umur) > $max_age) {
    send_json(['status'=>'error','message'=>"Umur harus angka antara $min_age - $max_age."]);
}
if (mb_strlen($masalah) < $min_masalah_len) {
    send_json(['status'=>'error','message'=>"Masalah terlalu singkat (min $min_masalah_len karakter)."]);
}

// Prepare entry
$entry = [
    'nama' => $nama,
    'umur' => intval($umur),
    'email' => $email,
    'masalah' => $masalah,
    'created_at' => date('Y-m-d H:i:s'),
];

// Assign incremental ID (read last line quickly)
$id = 1;
if (file_exists($file) && filesize($file) > 0) {
    $fp = fopen($file, 'rb');
    if ($fp) {
        // Seek to end, read backward to find last line
        $pos = -1;
        $lastLine = '';
        fseek($fp, $pos, SEEK_END);
        while(ftell($fp) > 0) {
            $char = fgetc($fp);
            if ($char === "\n") {
                // skip trailing eol
                $pos--;
                fseek($fp, $pos, SEEK_END);
            } else {
                break;
            }
        }
        // read backwards until newline
        while (ftell($fp) > 0) {
            fseek($fp, $pos, SEEK_END);
            $char = fgetc($fp);
            if ($char === "\n") {
                break;
            }
            $lastLine = $char . $lastLine;
            $pos--;
        }
        // if file cursor at start, maybe lastLine empty -> read from start
        if ($lastLine === '') {
            // try reading whole file and take last non-empty line
            rewind($fp);
            $all = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($all && count($all) > 0) $lastLine = array_pop($all);
        }
        fclose($fp);
        if ($lastLine) {
            $decoded = json_decode($lastLine, true);
            if (isset($decoded['id'])) $id = intval($decoded['id']) + 1;
        }
    }
}
$entry['id'] = $id;

// Write to file (append) with lock
$line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
if (file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
    send_json(['status'=>'error','message'=>'Gagal menyimpan data. Pastikan folder backend writable.']);
}

// Success response
send_json(['status'=>'success','message'=>'Pesan konseling berhasil dikirim.', 'id' => $id]);
