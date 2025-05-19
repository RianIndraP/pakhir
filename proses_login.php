<?php
session_start();
require_once 'db.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($password)) {
        header("Location: ../login.html?error=empty_fields");
        exit();
    }

    // Cek user di database
    $stmt = $koneksi->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password (tanpa hash)
        if ($password === $user['password']) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Log login berhasil
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $log_stmt = $koneksi->prepare("INSERT INTO login_logs (user_id, username, ip, user_agent, status) VALUES (?, ?, ?, ?, 'success')");
            $log_stmt->bind_param("isss", $user['id'], $user['username'], $ip, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();

            // Redirect berdasarkan role
            header("Location: ../dashboard.php");
            exit();
        } else {
            // Log login gagal - password salah
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $log_stmt = $koneksi->prepare("INSERT INTO login_logs (username, ip, user_agent, status) VALUES (?, ?, ?, 'failed_password')");
            $log_stmt->bind_param("sss", $username, $ip, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();

            header("Location: ../login.html?error=invalid_password");
            exit();
        }
    } else {
        // Log login gagal - username tidak ditemukan
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $log_stmt = $koneksi->prepare("INSERT INTO login_logs (username, ip, user_agent, status) VALUES (?, ?, ?, 'failed_username')");
        $log_stmt->bind_param("sss", $username, $ip, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();

        header("Location: ../login.html?error=invalid_username");
        exit();
    }

    $stmt->close();
}

// Jika bukan POST, redirect ke halaman login
header("Location: ../login.html");
exit();
