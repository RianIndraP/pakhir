<?php
// File: booking_success.php
// Halaman setelah pemesanan berhasil dibuat yang menampilkan info pemesanan dan QR Code
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Mulai session
session_start();

// Include koneksi database
require_once 'includes/db.php';

// Cek apakah ada booking_id di parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Query untuk mendapatkan data pemesanan
$query = "SELECT b.id, b.booking_date, b.check_in_date, b.check_out_date, b.total_price, 
          b.status, g.name as guest_name, g.email as guest_email, g.phone as guest_phone,
          r.room_number, r.type as room_type
          FROM bookings b 
          JOIN guests g ON b.guest_id = g.id
          JOIN rooms r ON b.room_id = r.id
          WHERE b.id = '$booking_id'";

$result = mysqli_query($koneksi, $query);

// Jika data tidak ditemukan
if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$booking = mysqli_fetch_assoc($result);

// Cek apakah user yang login adalah admin/staff atau guest yang memiliki booking ini
$isAuthorized = isset($_SESSION['user_id']) ||
    (isset($_SESSION['guest_email']) && $_SESSION['guest_email'] == $booking['guest_email']);

if (!$isAuthorized) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Berhasil - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Pemesanan Berhasil</h1>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-info">
                    <span>Selamat datang, <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            <?php endif; ?>
        </header>

        <?php if (isset($_SESSION['user_id'])): ?>
            <nav>
                <ul>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="rooms.php">Manajemen Kamar</a></li>
                    <?php endif; ?>
                    <li><a href="bookings.php">Pemesanan</a></li>
                    <li><a href="scan.php">Scan QR</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin/users.php">Manajemen User</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <main>
            <section class="booking-success">
                <div class="success-icon">
                    <img src="assets/images/success-icon.png" alt="Sukses">
                </div>

                <div class="success-message">
                    <h2>Pemesanan Berhasil Dibuat!</h2>
                    <p>Terima kasih, <strong><?php echo $booking['guest_name']; ?></strong>! Pemesanan kamar Anda telah berhasil dibuat.</p>
                </div>

                <div class="booking-details">
                    <h3>Detail Pemesanan</h3>
                    <table class="detail-table">
                        <tr>
                            <td>ID Pemesanan:</td>
                            <td><strong><?php echo $booking['id']; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Nama Tamu:</td>
                            <td><?php echo $booking['guest_name']; ?></td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><?php echo $booking['guest_email']; ?></td>
                        </tr>
                        <tr>
                            <td>Telepon:</td>
                            <td><?php echo $booking['guest_phone']; ?></td>
                        </tr>
                        <tr>
                            <td>Nomor Kamar:</td>
                            <td><?php echo $booking['room_number']; ?> (<?php echo $booking['room_type']; ?>)</td>
                        </tr>
                        <tr>
                            <td>Tanggal Check-in:</td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal Check-out:</td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                        </tr>
                        <tr>
                            <td>Total Harga:</td>
                            <td>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>Status:</td>
                            <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo $booking['status']; ?></span></td>
                        </tr>
                    </table>
                </div>

                <div class="qr-code-section">
                    <h3>QR Code Pemesanan</h3>
                    <p>Tunjukkan QR Code ini saat check-in di hotel untuk mempercepat proses.</p>
                    <div class="qr-code">
                        <img src="generate_qr.php?id=<?php echo $booking['id']; ?>" alt="QR Code">
                    </div>
                    <div class="download-buttons">
                        <a href="generate_qr.php?id=<?php echo $booking['id']; ?>&download=1" class="btn btn-download">Unduh QR Code</a>
                        <a href="mailto:?subject=Pemesanan Hotel&body=Detail pemesanan Anda:%0D%0AID: <?php echo $booking['id']; ?>%0D%0ANama: <?php echo $booking['guest_name']; ?>%0D%0AKamar: <?php echo $booking['room_number']; ?>%0D%0ACheck-in: <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>%0D%0ACheck-out: <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>%0D%0A%0D%0ASilakan buka link berikut untuk melihat QR Code Anda: <?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" class="btn btn-email">Kirim Via Email</a>
                    </div>
                </div>

                <div class="note-section">
                    <h3>Catatan Penting</h3>
                    <ul>
                        <li>Silakan tiba di hotel pada tanggal check-in yang telah ditentukan.</li>
                        <li>Tunjukkan QR Code dan identitas diri Anda kepada resepsionis.</li>
                        <li>Checkout sebelum pukul 12.00 pada tanggal checkout.</li>
                        <li>Untuk informasi lebih lanjut, hubungi hotel di <strong>0812-3456-7890</strong>.</li>
                    </ul>
                </div>

                <div class="action-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="bookings.php" class="btn">Kembali ke Daftar Pemesanan</a>
                    <?php else: ?>
                        <a href="index.php" class="btn">Kembali ke Beranda</a>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Sistem Pemindai Pemesanan Hotel</p>
        </footer>
    </div>
</body>

</html>