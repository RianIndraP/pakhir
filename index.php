<?php

/**
 * Sistem Pemindai Pemesanan Hotel
 * 
 * File: index.php
 * Deskripsi: Halaman utama yang mengalihkan pengguna ke halaman login
 *           atau dashboard berdasarkan status login
 */

// Mulai session untuk pengelolaan login
session_start();

// Sertakan file koneksi database
require_once 'includes/db.php';

// Periksa apakah pengguna sudah login
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Jika pengguna sudah login, alihkan ke dashboard
    $role = $_SESSION['role'] ?? 'staff';

    if ($role === 'admin') {
        // Jika pengguna adalah admin, alihkan ke dashboard admin
        header("Location: dashboard.php");
    } else {
        // Jika pengguna adalah staff, alihkan ke halaman booking
        header("Location: bookings.php");
    }
    exit;
} else {
    // Jika pengguna belum login, alihkan ke halaman login
    header("Location: login.html");
    exit;
}
