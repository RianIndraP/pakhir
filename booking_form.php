<?php
// File: booking_form.php
// Form pemesanan kamar hotel

// Mulai session
session_start();

// Include koneksi database
require_once 'includes/db.php';

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']);

// Redirect jika belum login
if (!$isLoggedIn) {
    header("Location: login.html");
    exit();
}

// Query untuk mendapatkan kamar yang tersedia
$queryRooms = "SELECT id, room_number, room_type, capacity, price_per_night FROM rooms WHERE status = 'available' ORDER BY room_number";
$resultRooms = mysqli_query($koneksi, $queryRooms);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/booking_form.css">
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
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i>Pemesanan</a></li>
                <li><a href="booking_form.php" class="active"><i class="fas fa-plus-circle"></i>Pemesanan Baru</a></li>
                <li><a href="scan.php"><i class="fas fa-qrcode"></i>Scan QR</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users"></i>Manajemen User</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i>Laporan</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Pengaturan</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <header>
            <div class="header-title">
                <h1>Form Pemesanan Kamar</h1>
            </div>
            <div class="user-info">
                <span>Selamat datang, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <section class="booking-form">
                <h2><i class="fas fa-calendar-plus"></i> Isi Data Pemesanan</h2>

                <?php if (mysqli_num_rows($resultRooms) == 0): ?>
                    <div class="alert alert-warning">
                        <p><i class="fas fa-exclamation-triangle"></i> Maaf, saat ini tidak ada kamar yang tersedia untuk dipesan. Silakan coba lagi nanti.</p>
                        <a href="bookings.php" class="btn">Kembali</a>
                    </div>
                <?php else: ?>
                    <form action="process_booking.php" method="post">
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Data Tamu</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Nama Lengkap</label>
                                    <input type="text" id="name" name="name" placeholder="Masukkan nama lengkap tamu" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" placeholder="contoh@email.com" required>
                                </div>

                                <div class="form-group">
                                    <label for="phone">No. Telepon</label>
                                    <input type="tel" id="phone" name="phone" placeholder="08xxxxxxxxxx" required>
                                </div>

                                <div class="form-group">
                                    <label for="id_type">Jenis Identitas</label>
                                    <select id="id_type" name="id_type" required>
                                        <option value="">Pilih Jenis Identitas</option>
                                        <option value="KTP">KTP</option>
                                        <option value="Passport">Passport</option>
                                        <option value="SIM">SIM</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="id_number">Nomor Identitas</label>
                                    <input type="text" id="id_number" name="id_number" placeholder="Masukkan nomor identitas" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3><i class="fas fa-calendar-alt"></i> Detail Pemesanan</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="room_id">Pilih Kamar</label>
                                    <select id="room_id" name="room_id" required>
                                        <option value="">Pilih Kamar</option>
                                        <?php while ($room = mysqli_fetch_assoc($resultRooms)): ?>
                                            <option value="<?php echo $room['id']; ?>" data-price="<?php echo $room['price_per_night']; ?>">
                                                Kamar <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?>
                                                (Kapasitas: <?php echo $room['capacity']; ?> orang, Harga: Rp <?php echo number_format($room['price_per_night'], 0, ',', '.'); ?>/malam)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="num_guests">Jumlah Tamu</label>
                                    <input type="number" id="num_guests" name="num_guests" min="1" max="5" value="1" required>
                                </div>

                                <div class="form-group">
                                    <label for="check_in_date">Tanggal Check-in</label>
                                    <input type="date" id="check_in_date" name="check_in_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="check_out_date">Tanggal Check-out</label>
                                    <input type="date" id="check_out_date" name="check_out_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                            </div>

                            <div id="price-calculation" class="price-calculation">
                                <div class="price-row">
                                    <span><i class="fas fa-tag"></i> Harga per malam:</span>
                                    <span id="price-per-night">Rp 0</span>
                                </div>
                                <div class="price-row">
                                    <span><i class="fas fa-moon"></i> Jumlah malam:</span>
                                    <span id="num-nights">0</span>
                                </div>
                                <div class="price-row total">
                                    <span><i class="fas fa-money-bill-wave"></i> Total harga:</span>
                                    <span id="total-price">Rp 0</span>
                                </div>
                                <input type="hidden" id="total_price" name="total_price" value="0">
                            </div>
                        </div>

                        <div class="form-section">
                            <h3><i class="fas fa-sticky-note"></i> Catatan Tambahan</h3>
                            <div class="form-group">
                                <label for="notes">Catatan</label>
                                <textarea id="notes" name="notes" rows="4" placeholder="Masukkan catatan khusus jika ada (opsional)"></textarea>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <a href="bookings.php" class="btn btn-cancel"><i class="fas fa-times"></i> Batal</a>
                            <button type="submit" class="btn btn-submit"><i class="fas fa-check"></i> Pesan Sekarang</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Sistem Pemindai Pemesanan Hotel | Dibuat dengan <i class="fas fa-heart" style="color: #e74c3c;"></i></p>
        </footer>
    </div>

    <script>
        // Fungsi untuk menghitung harga total
        function calculatePrice() {
            const roomSelect = document.getElementById('room_id');
            const checkInDate = document.getElementById('check_in_date').value;
            const checkOutDate = document.getElementById('check_out_date').value;

            if (roomSelect.value && checkInDate && checkOutDate) {
                const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                const pricePerNight = parseInt(selectedOption.dataset.price);

                // Hitung jumlah malam
                const checkIn = new Date(checkInDate);
                const checkOut = new Date(checkOutDate);
                const diffTime = checkOut.getTime() - checkIn.getTime();
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 0) {
                    // Update tampilan
                    document.getElementById('price-per-night').textContent = `Rp ${pricePerNight.toLocaleString('id-ID')}`;
                    document.getElementById('num-nights').textContent = diffDays;

                    const totalPrice = pricePerNight * diffDays;
                    document.getElementById('total-price').textContent = `Rp ${totalPrice.toLocaleString('id-ID')}`;
                    document.getElementById('total_price').value = totalPrice;
                }
            }
        }

        // Event listeners untuk perhitungan harga
        document.getElementById('room_id').addEventListener('change', calculatePrice);
        document.getElementById('check_in_date').addEventListener('change', function() {
            const checkInDate = this.value;
            if (checkInDate) {
                const nextDay = new Date(checkInDate);
                nextDay.setDate(nextDay.getDate() + 1);
                const nextDayStr = nextDay.toISOString().split('T')[0];
                document.getElementById('check_out_date').min = nextDayStr;

                const checkOutDate = document.getElementById('check_out_date').value;
                if (checkOutDate && new Date(checkOutDate) <= new Date(checkInDate)) {
                    document.getElementById('check_out_date').value = nextDayStr;
                }

                calculatePrice();
            }
        });
        document.getElementById('check_out_date').addEventListener('change', calculatePrice);

        // Animasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.form-section');
            sections.forEach((section, index) => {
                setTimeout(() => {
                    section.style.opacity = '0';
                    section.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        section.style.transition = 'all 0.5s ease';
                        section.style.opacity = '1';
                        section.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 150);
            });

            // Batas maksimal input nomor telepon
            document.getElementById('phone').setAttribute('maxlength', '13');

            // Batas maksimal nomor identitas berdasarkan jenis
            document.getElementById('id_type').addEventListener('change', function() {
                const idType = this.value;
                const idNumberInput = document.getElementById('id_number');

                // Kosongkan input saat jenis identitas diubah
                idNumberInput.value = '';
                idNumberInput.setCustomValidity(''); // Reset pesan error

                // Set maxlength dan placeholder
                if (idType === 'KTP') {
                    idNumberInput.setAttribute('maxlength', '16');
                    idNumberInput.setAttribute('placeholder', 'Masukkan nomor KTP (16 digit)');
                } else if (idType === 'Passport') {
                    idNumberInput.setAttribute('maxlength', '9');
                    idNumberInput.setAttribute('placeholder', 'Masukkan nomor Passport (9 karakter)');
                } else if (idType === 'SIM') {
                    idNumberInput.setAttribute('maxlength', '12');
                    idNumberInput.setAttribute('placeholder', 'Masukkan nomor SIM (12 digit)');
                } else {
                    idNumberInput.removeAttribute('maxlength');
                    idNumberInput.setAttribute('placeholder', 'Masukkan nomor identitas');
                }
            });

            // Validasi saat user mengetik atau saat form disubmit
            document.getElementById('id_number').addEventListener('input', function() {
                const idType = document.getElementById('id_type').value;
                const value = this.value;
                let regex;

                if (idType === 'KTP') {
                    regex = /^\d{16}$/;
                } else if (idType === 'Passport') {
                    regex = /^[A-Z0-9]{9}$/i;
                } else if (idType === 'SIM') {
                    regex = /^\d{12}$/;
                }

                if (regex && !regex.test(value)) {
                    this.setCustomValidity('Format nomor identitas tidak sesuai');
                } else {
                    this.setCustomValidity('');
                }
            });

            // --- Tambahan ---
            // Cegah huruf/simbol saat mengetik nomor identitas
            document.getElementById('id_number').addEventListener('keypress', function(e) {
                const idType = document.getElementById('id_type').value;
                const char = String.fromCharCode(e.which);

                if (idType === 'KTP' || idType === 'SIM') {
                    if (!/\d/.test(char)) {
                        e.preventDefault();
                    }
                } else if (idType === 'Passport') {
                    if (!/[a-zA-Z0-9]/.test(char)) {
                        e.preventDefault();
                    }
                }
            });

            // Validasi nomor telepon
            document.getElementById('phone').addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which);
                if (!/\d/.test(char)) {
                    e.preventDefault();
                }
            });

            document.getElementById('phone').addEventListener('input', function() {
                const value = this.value;
                const regex = /^08\d{8,11}$/;
                if (!regex.test(value)) {
                    this.setCustomValidity('Nomor telepon harus dimulai dengan 08 dan panjang 10-13 digit');
                } else {
                    this.setCustomValidity('');
                }
            });


            // Sinkronisasi jumlah tamu dengan kapasitas kamar saat halaman dimuat dan saat kamar diubah
            const syncGuestLimit = () => {
                const roomSelect = document.getElementById('room_id');
                const selected = roomSelect.options[roomSelect.selectedIndex].text;
                const match = selected.match(/Kapasitas:\s*(\d+)/);
                const maxGuests = match ? parseInt(match[1]) : 5;

                const numGuestsInput = document.getElementById('num_guests');
                numGuestsInput.setAttribute('max', maxGuests);

                if (parseInt(numGuestsInput.value) > maxGuests) {
                    numGuestsInput.value = maxGuests;
                }
            };

            // Inisialisasi saat halaman dimuat
            syncGuestLimit();

            // Jalankan ulang saat pilihan kamar berubah
            document.getElementById('room_id').addEventListener('change', syncGuestLimit);
        });
    </script>

</body>

</html>