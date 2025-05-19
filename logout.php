<?php
// Memulai atau melanjutkan sesi
session_start();

// Simpan informasi pengguna untuk pesan logout
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Log aktivitas logout ke dalam file log (opsional)
$log_file = 'logs/user_activity.log';
if (isset($_SESSION['username'])) {
    $log_message = date('[Y-m-d H:i:s]') . " - User '{$_SESSION['username']}' ({$_SESSION['role']}) logged out.\n";

    // Pastikan direktori logs ada
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }

    // Tulis ke file log
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Hapus semua variabel sesi
$_SESSION = array();

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Redirect ke halaman login dengan pesan berhasil logout
header("Location: login.html?logout=success&user=" . urlencode($username));
exit();
