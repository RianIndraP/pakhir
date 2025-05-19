<?php
// File: includes/db.php
// Koneksi ke database MySQL

// Parameter koneksi
$host = "localhost"; // Nama host database
$username = "root";  // Username database
$password = "";      // Password database (kosong untuk XAMPP default)
$database = "hotel_booking_scanner"; // Nama database

// Membuat koneksi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set karakter koneksi ke UTF-8
mysqli_set_charset($koneksi, "utf8");
