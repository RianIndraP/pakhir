<?php
// File: bookings.php
// Halaman utama untuk staf hotel mengelola pemesanan

session_start();

// Include koneksi database
require_once 'includes/db.php';

// Cek status login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Ambil filter dari URL atau set default
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Query dasar untuk ambil data pemesanan dengan JOIN ke tamu dan kamar
// PERBAIKAN: Mengubah b.booking_date menjadi b.created_at karena pada database tidak ada kolom booking_date
$query = "SELECT b.id, b.booking_number, DATE(b.created_at) as booking_date, b.check_in_date, b.check_out_date, 
          b.booking_status AS status, g.name AS guest_name, g.phone AS guest_phone, 
          r.room_number, r.room_type
          FROM bookings b
          JOIN guests g ON b.guest_id = g.id
          JOIN rooms r ON b.room_id = r.id";

// Siapkan array kondisi WHERE
$whereConditions = [];
if ($statusFilter != 'all') {
    $statusFilterEscaped = mysqli_real_escape_string($koneksi, $statusFilter);
    $whereConditions[] = "b.booking_status = '$statusFilterEscaped'";
}

if (!empty($searchTerm)) {
    $searchTermEscaped = mysqli_real_escape_string($koneksi, $searchTerm);
    $whereConditions[] = "(g.name LIKE '%$searchTermEscaped%' OR g.phone LIKE '%$searchTermEscaped%' OR r.room_number LIKE '%$searchTermEscaped%')";
}

// Gabungkan kondisi WHERE jika ada
if (count($whereConditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

// Urutkan berdasarkan tanggal booking terbaru
$query .= " ORDER BY b.created_at DESC";

// Jalankan query
$result = mysqli_query($koneksi, $query);
if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Status pilihan untuk filter dropdown
$statuses = [
    'all' => 'Semua Status',
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'checked_in' => 'Checked In',
    'checked_out' => 'Checked Out',
    'cancelled' => 'Cancelled'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pemesanan - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/bookings.css">
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="brand">
                <i class="fas fa-hotel"></i>
                <h2>Hotel System</h2>
            </div>
            <ul class="sidebar-nav">
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                    <li><a href="rooms.php"><i class="fas fa-door-closed"></i>Manajemen Kamar</a></li>
                <?php endif; ?>
                <li><a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i>Pemesanan</a></li>
                <li><a href="scan.php"><i class="fas fa-qrcode"></i>Scan QR</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin/users.php"><i class="fas fa-users"></i>Manajemen User</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i>Laporan</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Pengaturan</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <header>
            <div class="header-title">
                <h1>Manajemen Pemesanan</h1>
            </div>
            <div class="user-info">
                <span>Selamat datang, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <section class="booking-actions">
                <a href="booking_form.php" class="btn"><i class="fas fa-plus"></i> Tambah Pemesanan Baru</a>
                <a href="scan.php" class="btn btn-scan"><i class="fas fa-qrcode"></i> Scan QR Code</a>
            </section>

            <section class="booking-filters">
                <form action="bookings.php" method="get" class="filter-form">
                    <div class="filter-group">
                        <label for="status">Filter Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($statusFilter == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group search-group">
                        <label for="search">Cari:</label>
                        <div class="search-input">
                            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Nama/Telepon/No Kamar">
                            <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="recent-bookings booking-list">
                <h2><i class="fas fa-list"></i> Daftar Pemesanan</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Tamu</th>
                            <th>Telepon</th>
                            <th>No. Kamar</th>
                            <th>Tipe Kamar</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo $booking['guest_name']; ?></td>
                                    <td><?php echo $booking['guest_phone']; ?></td>
                                    <td><?php echo $booking['room_number']; ?></td>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo $booking['status']; ?></span></td>
                                    <td class="actions">
                                        <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn-small"><i class="fas fa-eye"></i> Detail</a>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="confirm_booking.php?id=<?php echo $booking['id']; ?>" class="btn-small btn-confirm"><i class="fas fa-check"></i> Konfirmasi</a>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                            <a href="confirm_booking.php?id=<?php echo $booking['id']; ?>&action=checkin" class="btn-small btn-checkin"><i class="fas fa-sign-in-alt"></i> Check-in</a>
                                        <?php endif; ?>
                                        <?php if ($booking['status'] == 'checked_in'): ?>
                                            <a href="confirm_booking.php?id=<?php echo $booking['id']; ?>&action=checkout" class="btn-small btn-checkout"><i class="fas fa-sign-out-alt"></i> Check-out</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data">Tidak ada data pemesanan yang sesuai dengan filter</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Sistem Pemindai Pemesanan Hotel | Dibuat dengan <i class="fas fa-heart" style="color: #e74c3c;"></i></p>
        </footer>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Animasi untuk tabel
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '1';
                        row.style.transform = 'translateX(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>

</html>