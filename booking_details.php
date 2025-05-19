<?php
session_start();
// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Include database connection
require_once 'includes/db.php';

// Cek apakah ada ID booking yang dikirimkan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bookings.php");
    exit();
}

$booking_id = $_GET['id'];

// Query untuk mendapatkan detail booking dengan JOIN ke tabel terkait
$sql = "SELECT b.*, g.*, r.*, 
        DATEDIFF(b.checkout_date, b.checkin_date) as length_of_stay,
        (DATEDIFF(b.checkout_date, b.checkin_date) * r.price) as total_price
        FROM bookings b
        LEFT JOIN guests g ON b.guest_id = g.id
        LEFT JOIN rooms r ON b.room_id = r.id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Tidak ada pemesanan dengan ID tersebut
    header("Location: bookings.php?error=notfound");
    exit();
}

$booking = $result->fetch_assoc();

// Query untuk mendapatkan riwayat perubahan status booking
$history_sql = "SELECT * FROM booking_history WHERE booking_id = ? ORDER BY timestamp DESC";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $booking_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemesanan - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-hotel"></i> Sistem Pemindai Pemesanan Hotel</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Pemesanan</a></li>
                    <li><a href="scan.php"><i class="fas fa-qrcode"></i> Scan QR</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="booking-details">
                <h2><i class="fas fa-info-circle"></i> Detail Pemesanan #<?php echo $booking_id; ?></h2>

                <!-- Tombol Kembali dan Aksi -->
                <div class="action-buttons">
                    <a href="bookings.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    <?php if ($booking['status'] == 'pending'): ?>
                        <a href="confirm_booking.php?id=<?php echo $booking_id; ?>&action=confirm" class="btn btn-success"><i class="fas fa-check"></i> Konfirmasi</a>
                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                        <a href="confirm_booking.php?id=<?php echo $booking_id; ?>&action=checkin" class="btn btn-primary"><i class="fas fa-door-open"></i> Check-in</a>
                    <?php elseif ($booking['status'] == 'checked_in'): ?>
                        <a href="confirm_booking.php?id=<?php echo $booking_id; ?>&action=checkout" class="btn btn-warning"><i class="fas fa-door-closed"></i> Check-out</a>
                    <?php endif; ?>

                    <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'completed'): ?>
                        <a href="confirm_booking.php?id=<?php echo $booking_id; ?>&action=cancel" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan pemesanan ini?');"><i class="fas fa-times"></i> Batalkan</a>
                    <?php endif; ?>

                    <!-- Tombol Cetak/Lihat QR Code -->
                    <a href="generate_qr.php?booking_id=<?php echo $booking_id; ?>&show=true" class="btn btn-info"><i class="fas fa-qrcode"></i> Lihat QR Code</a>
                </div>

                <div class="detail-card">
                    <div class="detail-section">
                        <h3><i class="fas fa-calendar-alt"></i> Informasi Pemesanan</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">ID Pemesanan:</span>
                                <span class="value"><?php echo $booking_id; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Status:</span>
                                <span class="value status-badge status-<?php echo $booking['status']; ?>">
                                    <?php
                                    $status_map = [
                                        'pending' => 'Menunggu',
                                        'confirmed' => 'Terkonfirmasi',
                                        'checked_in' => 'Check-in',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan'
                                    ];
                                    echo $status_map[$booking['status']] ?? $booking['status'];
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Tanggal Check-in:</span>
                                <span class="value"><?php echo date('d F Y', strtotime($booking['checkin_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Tanggal Check-out:</span>
                                <span class="value"><?php echo date('d F Y', strtotime($booking['checkout_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Lama Menginap:</span>
                                <span class="value"><?php echo $booking['length_of_stay']; ?> malam</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Tanggal Pemesanan:</span>
                                <span class="value"><?php echo date('d F Y H:i', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Catatan Khusus:</span>
                                <span class="value"><?php echo empty($booking['notes']) ? '-' : $booking['notes']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-user"></i> Informasi Tamu</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">Nama Lengkap:</span>
                                <span class="value"><?php echo $booking['name']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Email:</span>
                                <span class="value"><?php echo $booking['email']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Telepon:</span>
                                <span class="value"><?php echo $booking['phone']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Nomor Identitas:</span>
                                <span class="value"><?php echo $booking['id_number']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Alamat:</span>
                                <span class="value"><?php echo $booking['address']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-bed"></i> Informasi Kamar</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">Nomor Kamar:</span>
                                <span class="value"><?php echo $booking['room_number']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Tipe Kamar:</span>
                                <span class="value"><?php echo $booking['type']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Kapasitas:</span>
                                <span class="value"><?php echo $booking['capacity']; ?> orang</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Harga per Malam:</span>
                                <span class="value">Rp. <?php echo number_format($booking['price'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Total Biaya:</span>
                                <span class="value total-price">Rp. <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Status Pemesanan -->
                <div class="booking-history">
                    <h3><i class="fas fa-history"></i> Riwayat Status Pemesanan</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal & Waktu</th>
                                <th>Status</th>
                                <th>Oleh</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history_result->num_rows > 0): ?>
                                <?php while ($history = $history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($history['timestamp'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $history['status']; ?>">
                                                <?php
                                                echo $status_map[$history['status']] ?? $history['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo $history['user_id']; ?></td>
                                        <td><?php echo empty($history['notes']) ? '-' : $history['notes']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-data">Tidak ada data riwayat</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistem Pemindai Pemesanan Hotel. All rights reserved.</p>
        </footer>
    </div>
</body>

</html>