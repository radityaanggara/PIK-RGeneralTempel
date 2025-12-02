<?php
// admin.php - Admin panel sederhana untuk melihat pesan konseling
// WARNING: panel ini menggunakan login berbasis session dengan password yang disetel di variabel $ADMIN_PASSWORD.
// Untuk security produksi, pastikan mengganti password default dan membatasi akses via .htaccess atau IP.

// CONFIG - ubah password ini segera!
$ADMIN_PASSWORD = 'admin123'; // ganti ini sebelum produksi

session_start();
$file = __DIR__ . '/database.txt';

// Helpers
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function redirect($url){ header("Location: $url"); exit; }

// Handle login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $pw = $_POST['password'] ?? '';
    if ($pw === $ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        // CSRF token
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        redirect(basename(__FILE__));
    } else {
        $error = "Password salah.";
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    redirect(basename(__FILE__));
}

// Require login
if (!($_SESSION['is_admin'] ?? false)) {
    // Show login form
    ?>
    <!doctype html>
    <html><head><meta charset="utf-8"><title>Admin Login</title>
    <style>body{font-family:Arial;padding:30px;background:#f6f8fb}form{max-width:360px;margin:30px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,0.06)}input{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:6px}button{padding:10px 14px;background:#0b81d6;color:#fff;border:none;border-radius:6px}</style>
    </head><body>
    <h2>Admin Panel — PIK-R Konseling</h2>
    <?php if (!empty($error)): ?><p style="color:red"><?php echo h($error);?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="action" value="login">
      <label>Password Admin</label>
      <input type="password" name="password" required>
      <div style="text-align:right"><button type="submit">Login</button></div>
    </form>
    <p>Note: ganti password default pada <code>admin.php</code> sebelum dipublikasi.</p>
    </body></html>
    <?php
    exit;
}

// From here admin is authenticated
$csrf = $_SESSION['csrf'] ?? '';

// Handle delete (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        die('CSRF token invalid');
    }
    $del_id = intval($_POST['id'] ?? 0);
    if ($del_id > 0 && file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $out = [];
        foreach ($lines as $ln) {
            $obj = json_decode($ln, true);
            if (!$obj) continue;
            if (intval($obj['id'] ?? 0) === $del_id) {
                // skip (delete)
                continue;
            }
            $out[] = $ln;
        }
        file_put_contents($file, implode(PHP_EOL, $out) . (count($out) ? PHP_EOL : ''), LOCK_EX);
        $msg = "Entry ID $del_id dihapus.";
    }
}

// Handle export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=pesan_konseling.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','nama','umur','email','masalah','created_at']);
        foreach ($lines as $ln) {
            $obj = json_decode($ln, true);
            if (!$obj) continue;
            fputcsv($out, [
                $obj['id'] ?? '',
                $obj['nama'] ?? '',
                $obj['umur'] ?? '',
                $obj['email'] ?? '',
                $obj['masalah'] ?? '',
                $obj['created_at'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    } else {
        $msg = "Tidak ada data untuk diekspor.";
    }
}

// Read entries
$rows = [];
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $ln) {
        $obj = json_decode($ln, true);
        if ($obj) $rows[] = $obj;
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Pesan Konseling</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;margin:18px;background:#f4f7fb}
.container{max-width:1100px;margin:0 auto;background:#fff;padding:16px;border-radius:10px;box-shadow:0 10px 30px rgba(2,6,23,0.06)}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #e6eef5;padding:8px;text-align:left;vertical-align:top}
th{background:#f7fbff}
.actions{display:flex;gap:8px}
.btn{padding:8px 10px;border-radius:6px;border:none;cursor:pointer}
.btn.del{background:#ff6b6b;color:#fff}
.btn.csv{background:#0b81d6;color:#fff}
.top-actions{display:flex;justify-content:space-between;align-items:center}
</style>
</head>
<body>
<div class="container">
  <div class="top-actions">
    <h2>Data Konseling (Total: <?php echo count($rows); ?>)</h2>
    <div>
      <a class="btn csv" href="?export=csv">Export CSV</a>
      <a class="btn" href="?action=logout" style="margin-left:8px;background:#ccc">Logout</a>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <p style="color:green"><?php echo h($msg); ?></p>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <p>Tidak ada data.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th><th>Nama</th><th>Umur</th><th>Kontak</th><th>Masalah</th><th>Waktu</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo h($r['id'] ?? ''); ?></td>
          <td><?php echo h($r['nama'] ?? ''); ?></td>
          <td><?php echo h($r['umur'] ?? ''); ?></td>
          <td><?php echo h($r['email'] ?? ''); ?></td>
          <td style="max-width:420px;white-space:pre-wrap"><?php echo h($r['masalah'] ?? ''); ?></td>
          <td><?php echo h($r['created_at'] ?? ''); ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Hapus data ini?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo intval($r['id'] ?? 0); ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <button type="submit" class="btn del">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <p style="margin-top:14px;color:#666">Note: panel admin ini sederhana. Untuk produksi, tambahkan proteksi tambahan (HTTPS, IP whitelist, atau autentikasi lebih kuat).</p>
</div>
</body>
</html>
