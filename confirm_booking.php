<?php
// File: confirm_booking.php
// Proses konfirmasi pemesanan melalui QR code

// Mulai session
session_start();

// Include koneksi database
require_once 'includes/db.php';

// Fungsi untuk validasi data pemesanan
function validateBookingData($data)
{
    return !empty($data['booking_id']) &&
        !empty($data['guest_name']) &&
        !empty($data['room_number']) &&
        !empty($data['check_in']) &&
        !empty($data['check_out']);
}

// Variabel untuk menyimpan pesan dan jenis alert
$alertType = '';
$alertMessage = '';
$bookingData = null;

// Cek apakah user login atau tidak
$isLoggedIn = isset($_SESSION['user_id']);

// Jika user tidak login dan tidak ada parameter "data" di URL
if (!$isLoggedIn && !isset($_GET['data'])) {
    header("Location: login.html");
    exit();
}

// Proses data dari QR code (jika ada)
if (isset($_GET['data'])) {
    try {
        // Decode data dari base64
        $decodedData = base64_decode($_GET['data']);

        // Parse JSON
        $bookingData = json_decode($decodedData, true);

        // Validasi data
        if (!validateBookingData($bookingData)) {
            throw new Exception("Data pemesanan tidak valid.");
        }

        // Cek keberadaan booking di database
        $booking_id = mysqli_real_escape_string($koneksi, $bookingData['booking_id']);
        $query = "SELECT b.id, b.status, b.check_in_date, b.check_out_date, 
                 g.name as guest_name, r.room_number, r.id as room_id 
                 FROM bookings b 
                 JOIN guests g ON b.guest_id = g.id
                 JOIN rooms r ON b.room_id = r.id
                 WHERE b.id = '$booking_id'";

        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) == 0) {
            throw new Exception("Pemesanan tidak ditemukan.");
        }

        $booking = mysqli_fetch_assoc($result);
        $bookingData = $booking; // Ganti data dari QR dengan data dari database

    } catch (Exception $e) {
        $alertType = 'error';
        $alertMessage = "Error: " . $e->getMessage();
    }
}

// Proses tindakan check-in, check-out, dll
if ($isLoggedIn && isset($_GET['id']) && isset($_GET['action'])) {
    $booking_id = mysqli_real_escape_string($koneksi, $_GET['id']);
    $action = $_GET['action'];

    // Query untuk mendapatkan data booking
    $queryBooking = "SELECT b.id, b.status, b.check_in_date, b.check_out_date, 
                    g.name as guest_name, r.room_number, r.id as room_id 
                    FROM bookings b 
                    JOIN guests g ON b.guest_id = g.id
                    JOIN rooms r ON b.room_id = r.id
                    WHERE b.id = '$booking_id'";

    $resultBooking = mysqli_query($koneksi, $queryBooking);

    if (mysqli_num_rows($resultBooking) > 0) {
        $booking = mysqli_fetch_assoc($resultBooking);
        $room_id = $booking['room_id'];
        $currentStatus = $booking['status'];
        $newStatus = '';

        // Tentukan status baru dan tindakan berdasarkan aksi yang dipilih
        switch ($action) {
            case 'confirm':
                if ($currentStatus == 'pending') {
                    $newStatus = 'confirmed';
                    $alertType = 'success';
                    $alertMessage = "Pemesanan berhasil dikonfirmasi.";
                } else {
                    $alertType = 'warning';
                    $alertMessage = "Pemesanan sudah dalam status " . $currentStatus;
                }
                break;

            case 'checkin':
                if ($currentStatus == 'confirmed') {
                    $newStatus = 'checked_in';
                    $alertType = 'success';
                    $alertMessage = "Check-in berhasil dilakukan.";

                    // Update status kamar menjadi 'occupied'
                    $updateRoomQuery = "UPDATE rooms SET status = 'occupied' WHERE id = $room_id";
                    mysqli_query($koneksi, $updateRoomQuery);
                } else {
                    $alertType = 'warning';
                    $alertMessage = "Pemesanan harus dalam status confirmed untuk check-in.";
                }
                break;

            case 'checkout':
                if ($currentStatus == 'checked_in') {
                    $newStatus = 'checked_out';
                    $alertType = 'success';
                    $alertMessage = "Check-out berhasil dilakukan.";

                    // Update status kamar menjadi 'available'
                    $updateRoomQuery = "UPDATE rooms SET status = 'available' WHERE id = $room_id";
                    mysqli_query($koneksi, $updateRoomQuery);
                } else {
                    $alertType = 'warning';
                    $alertMessage = "Pemesanan harus dalam status checked_in untuk check-out.";
                }
                break;

            case 'cancel':
                if (in_array($currentStatus, ['pending', 'confirmed'])) {
                    $newStatus = 'cancelled';
                    $alertType = 'success';
                    $alertMessage = "Pemesanan berhasil dibatalkan.";
                } else {
                    $alertType = 'warning';
                    $alertMessage = "Pemesanan tidak dapat dibatalkan dalam status " . $currentStatus;
                }
                break;

            default:
                $alertType = 'error';
                $alertMessage = "Tindakan tidak valid.";
                break;
        }

        // Update status booking jika diperlukan
        if (!empty($newStatus)) {
            $updateBookingQuery = "UPDATE bookings SET status = '$newStatus', updated_at = NOW() WHERE id = '$booking_id'";

            if (mysqli_query($koneksi, $updateBookingQuery)) {
                // Tambahkan ke booking_history untuk audit trail
                $user_id = $_SESSION['user_id'];
                $historyQuery = "INSERT INTO booking_history (booking_id, user_id, old_status, new_status, change_time, notes) 
                                VALUES ('$booking_id', $user_id, '$currentStatus', '$newStatus', NOW(), 'Status updated via QR scan')";
                mysqli_query($koneksi, $historyQuery);

                // Refresh data booking
                $resultBooking = mysqli_query($koneksi, $queryBooking);
                $booking = mysqli_fetch_assoc($resultBooking);
                $bookingData = $booking;
            } else {
                $alertType = 'error';
                $alertMessage = "Gagal mengupdate status pemesanan.";
            }
        }
    } else {
        $alertType = 'error';
        $alertMessage = "Pemesanan tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pemesanan - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Konfirmasi Pemesanan</h1>
            <?php if ($isLoggedIn): ?>
                <div class="user-info">
                    <span>Selamat datang, <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($isLoggedIn): ?>
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
            <?php if (!empty($alertType) && !empty($alertMessage)): ?>
                <div class="alert alert-<?php echo $alertType; ?>">
                    <p><?php echo $alertMessage; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($bookingData): ?>
                <section class="booking-confirmation">
                    <h2>Detail Pemesanan</h2>

                    <div class="booking-details">
                        <table class="detail-table">
                            <tr>
                                <td>ID Pemesanan:</td>
                                <td><strong><?php echo $bookingData['id']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>Nama Tamu:</td>
                                <td><?php echo $bookingData['guest_name']; ?></td>
                            </tr>
                            <tr>
                                <td>Nomor Kamar:</td>
                                <td><?php echo $bookingData['room_number']; ?></td>
                            </tr>
                            <tr>
                                <td>Tanggal Check-in:</td>
                                <td><?php echo date('d/m/Y', strtotime($bookingData['check_in_date'])); ?></td>
                            </tr>
                            <tr>
                                <td>Tanggal Check-out:</td>
                                <td><?php echo date('d/m/Y', strtotime($bookingData['check_out_date'])); ?></td>
                            </tr>
                            <tr>
                                <td>Status:</td>
                                <td><span class="status-badge status-<?php echo $bookingData['status']; ?>"><?php echo $bookingData['status']; ?></span></td>
                            </tr>
                        </table>
                    </div>

                    <?php if ($isLoggedIn): ?>
                        <div class="action-buttons">
                            <h3>Tindakan</h3>

                            <?php if ($bookingData['status'] == 'pending'): ?>
                                <a href="confirm_booking.php?id=<?php echo $bookingData['id']; ?>&action=confirm" class="btn btn-confirm">Konfirmasi Pemesanan</a>
                                <a href="confirm_booking.php?id=<?php echo $bookingData['id']; ?>&action=cancel" class="btn btn-cancel">Batalkan Pemesanan</a>
                            <?php endif; ?>

                            <?php if ($bookingData['status'] == 'confirmed'): ?>
                                <a href="confirm_booking.php?id=<?php echo $bookingData['id']; ?>&action=checkin" class="btn btn-checkin">Proses Check-in</a>
                                <a href="confirm_booking.php?id=<?php echo $bookingData['id']; ?>&action=cancel" class="btn btn-cancel">Batalkan Pemesanan</a>
                            <?php endif; ?>

                            <?php if ($bookingData['status'] == 'checked_in'): ?>
                                <a href="confirm_booking.php?id=<?php echo $bookingData['id']; ?>&action=checkout" class="btn btn-checkout">Proses Check-out</a>
                            <?php endif; ?>

                            <a href="booking_details.php?id=<?php echo $bookingData['id']; ?>" class="btn">Lihat Detail Lengkap</a>

                            <?php if ($bookingData['status'] == 'checked_out' || $bookingData['status'] == 'cancelled'): ?>
                                <p class="info-text">Pemesanan ini telah <?php echo ($bookingData['status'] == 'checked_out') ? 'selesai' : 'dibatalkan'; ?>. Tidak ada tindakan yang diperlukan.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="guest-view">
                            <p>Silakan tunjukkan QR Code ini saat check-in di hotel.</p>
                            <?php if ($bookingData['status'] == 'confirmed'): ?>
                                <div class="confirmation-message">
                                    <p>Pemesanan Anda telah dikonfirmasi. Silakan datang sesuai tanggal check-in.</p>
                                </div>
                            <?php elseif ($bookingData['status'] == 'checked_in'): ?>
                                <div class="confirmation-message">
                                    <p>Anda telah melakukan check-in. Silakan kunjungi resepsionis jika butuh bantuan.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="scan-error">
                    <h2>Data Pemesanan Tidak Ditemukan</h2>
                    <p>QR Code tidak valid atau pemesanan tidak ditemukan. Silakan coba lagi atau hubungi resepsionis hotel.</p>

                    <?php if ($isLoggedIn): ?>
                        <div class="action-buttons">
                            <a href="scan.php" class="btn">Kembali ke Pemindai</a>
                            <a href="bookings.php" class="btn">Lihat Daftar Pemesanan</a>
                        </div>
                    <?php else: ?>
                        <div class="action-buttons">
                            <a href="index.php" class="btn">Kembali ke Beranda</a>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2025 Sistem Pemindai Pemesanan Hotel</p>
        </footer>
    </div>
</body>

</html>