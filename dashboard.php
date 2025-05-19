<?php
// File: dashboard.php
// Dashboard admin setelah login

// Mulai session
session_start();

// Include koneksi database
require_once 'includes/db.php';

// Cek status login, redirect jika belum login atau bukan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Query untuk mendapatkan data statistik
// Jumlah kamar
$queryRooms = "SELECT COUNT(*) as total_rooms FROM rooms";
$resultRooms = mysqli_query($koneksi, $queryRooms);
$totalRooms = mysqli_fetch_assoc($resultRooms)['total_rooms'];

// Jumlah kamar berdasarkan status
$queryRoomStatus = "SELECT status, COUNT(*) as count FROM rooms GROUP BY status";
$resultRoomStatus = mysqli_query($koneksi, $queryRoomStatus);
$roomStatus = [];
while ($row = mysqli_fetch_assoc($resultRoomStatus)) {
    $roomStatus[$row['status']] = $row['count'];
}

// Jumlah pemesanan aktif
$queryActiveBookings = "SELECT COUNT(*) as active_bookings FROM bookings WHERE status IN ('confirmed', 'checked_in')";
$resultActiveBookings = mysqli_query($koneksi, $queryActiveBookings);
$activeBookings = mysqli_fetch_assoc($resultActiveBookings)['active_bookings'];

// Pemesanan terbaru
$queryRecentBookings = "SELECT b.id, b.check_in_date, b.check_out_date, b.status, 
                        g.name as guest_name, r.room_number 
                        FROM bookings b 
                        JOIN guests g ON b.guest_id = g.id
                        JOIN rooms r ON b.room_id = r.id
                        ORDER BY b.check_in_date DESC LIMIT 5";
$resultRecentBookings = mysqli_query($koneksi, $queryRecentBookings);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="brand">
                <i class="fas fa-hotel"></i>
                <h2>Hotel System</h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="rooms.php"><i class="fas fa-door-closed"></i>Manajemen Kamar</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i>Pemesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i>Manajemen User</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i>Laporan</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i>Pengaturan</a></li>
            </ul>
        </div>

        <header>
            <div class="header-title">
                <h1>Dashboard Admin</h1>
            </div>
            <div class="user-info">
                <span>Selamat datang, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <section class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Kamar</h3>
                    <p class="stat-number"><?php echo $totalRooms; ?></p>
                    <i class="fas fa-door-closed icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Kamar Tersedia</h3>
                    <p class="stat-number"><?php echo isset($roomStatus['available']) ? $roomStatus['available'] : 0; ?></p>
                    <i class="fas fa-check-circle icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Kamar Terisi</h3>
                    <p class="stat-number"><?php echo isset($roomStatus['occupied']) ? $roomStatus['occupied'] : 0; ?></p>
                    <i class="fas fa-bed icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Pemesanan Aktif</h3>
                    <p class="stat-number"><?php echo $activeBookings; ?></p>
                    <i class="fas fa-calendar-alt icon"></i>
                </div>
            </section>

            <section class="recent-bookings">
                <h2>Pemesanan Terbaru</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Tamu</th>
                            <th>No. Kamar</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultRecentBookings) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($resultRecentBookings)): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo $booking['guest_name']; ?></td>
                                    <td><?php echo $booking['room_number']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo $booking['status']; ?></span></td>
                                    <td>
                                        <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn-small">Detail</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">Tidak ada data pemesanan terbaru</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="view-all">
                    <a href="bookings.php" class="btn">Lihat Semua Pemesanan</a>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Sistem Pemindai Pemesanan Hotel | Dibuat dengan <i class="fas fa-heart" style="color: #e74c3c;"></i></p>
        </footer>
    </div>

    <script>
        // Anda bisa menambahkan JavaScript di sini jika diperlukan
        document.addEventListener('DOMContentLoaded', function() {
            // Contoh animasi sederhana untuk stat-card
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>

</html>