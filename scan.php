<?php
// File: scan.php
// Halaman untuk memindai QR Code pemesanan

// Mulai session
session_start();

// Include koneksi database
require_once 'includes/db.php';

// Cek status login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - Sistem Pemindai Pemesanan Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <!-- Tambahkan library html5-qrcode untuk pemindaian QR code -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        /* Tambahan style khusus untuk halaman pemindai */
        .scan-section {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .scan-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 2rem 0;
        }

        #reader {
            width: 100%;
            max-width: 500px;
            border: 2px solid var(--primary-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .scan-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .scan-result {
            width: 100%;
            max-width: 500px;
            background-color: var(--gray-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-top: 1.5rem;
            text-align: center;
        }

        .loading-spinner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .scan-instructions {
            background-color: var(--light-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .scan-instructions h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .scan-instructions ol {
            padding-left: 1.5rem;
        }

        .scan-instructions li {
            margin-bottom: 0.5rem;
        }

        .scan-alternatives {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .scan-alternatives h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .search-form input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid var(--gray-color);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        .btn-cancel {
            background-color: var(--danger-color);
        }

        .btn-cancel:hover {
            background-color: #c0392b;
        }
    </style>
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
                <li><a href="scan.php" class="active"><i class="fas fa-qrcode"></i>Scan QR</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users"></i>Manajemen User</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i>Laporan</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i>Pengaturan</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <header>
            <div class="header-title">
                <h1>Pemindai QR Code</h1>
            </div>
            <div class="user-info">
                <span>Selamat datang, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main>
            <section class="scan-section">
                <h2><i class="fas fa-qrcode"></i> Pindai QR Code Pemesanan</h2>
                <p>Arahkan kamera ke QR Code untuk memindai data pemesanan.</p>

                <div class="scan-container">
                    <!-- Container untuk tampilan kamera pemindai -->
                    <div id="reader"></div>

                    <!-- Tombol untuk mengaktifkan pemindai -->
                    <div class="scan-actions">
                        <button id="startScan" class="btn"><i class="fas fa-camera"></i> Mulai Pemindaian</button>
                        <button id="stopScan" class="btn btn-cancel" style="display: none;"><i class="fas fa-stop"></i> Hentikan Pemindaian</button>
                    </div>

                    <!-- Area hasil pemindaian -->
                    <div id="scanResult" class="scan-result" style="display: none;">
                        <h3>Hasil Pemindaian</h3>
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Memproses data...</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="scan-instructions">
                <h3><i class="fas fa-info-circle"></i> Petunjuk:</h3>
                <ol>
                    <li>Klik tombol "Mulai Pemindaian" untuk mengaktifkan kamera.</li>
                    <li>Pastikan QR Code terlihat jelas dan berada dalam kotak pemindaian.</li>
                    <li>Sistem akan otomatis memproses data setelah QR Code berhasil dipindai.</li>
                    <li>Jika pemindaian gagal, coba sesuaikan posisi QR Code atau pencahayaan.</li>
                </ol>
            </div>

            <div class="scan-alternatives">
                <h3><i class="fas fa-search"></i> Alternatif:</h3>
                <p>Jika pemindaian tidak berfungsi, Anda dapat mencari pemesanan secara manual:</p>
                <div class="search-form">
                    <form action="bookings.php" method="get">
                        <input type="text" name="search" placeholder="Masukkan ID Pemesanan atau Nama Tamu">
                        <button type="submit" class="btn-small"><i class="fas fa-search"></i> Cari</button>
                    </form>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Sistem Pemindai Pemesanan Hotel | Dibuat dengan <i class="fas fa-heart" style="color: #e74c3c;"></i></p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi variabel pemindai
            let html5QrCode;
            const qrboxSize = 250;

            // Element references
            const startButton = document.getElementById('startScan');
            const stopButton = document.getElementById('stopScan');
            const scanResultDiv = document.getElementById('scanResult');

            // Animasi sederhana untuk scan-section saat halaman dimuat
            const scanSection = document.querySelector('.scan-section');
            scanSection.style.opacity = '0';
            scanSection.style.transform = 'translateY(20px)';
            setTimeout(() => {
                scanSection.style.transition = 'all 0.5s ease';
                scanSection.style.opacity = '1';
                scanSection.style.transform = 'translateY(0)';
            }, 100);

            // Fungsi untuk memulai pemindaian
            function startScanning() {
                html5QrCode = new Html5Qrcode("reader");

                html5QrCode.start({
                        facingMode: "user"
                    }, // Gunakan kamera depan
                    {
                        fps: 10,
                        qrbox: {
                            width: qrboxSize,
                            height: qrboxSize
                        },
                        aspectRatio: 1.0
                    },
                    onScanSuccess,
                    onScanFailure
                ).then(() => {
                    // Pemindaian dimulai
                    startButton.style.display = 'none';
                    stopButton.style.display = 'inline-block';
                }).catch((err) => {
                    alert(`Error saat memulai pemindaian: ${err}`);
                });
            }

            // Fungsi yang dijalankan saat QR code berhasil dipindai
            function onScanSuccess(decodedText, decodedResult) {
                // Hentikan pemindaian setelah berhasil
                html5QrCode.stop().then(() => {
                    // Tampilkan UI hasil pemindaian
                    scanResultDiv.style.display = 'block';
                    stopButton.style.display = 'none';
                    startButton.style.display = 'inline-block';
                    startButton.textContent = 'Pindai Ulang';
                    startButton.innerHTML = '<i class="fas fa-redo"></i> Pindai Ulang';
                    // Redirect ke halaman konfirmasi dengan data hasil pemindaian
                    window.location.href = decodedText;
                }).catch((err) => {
                    console.error("Error saat menghentikan pemindaian:", err);
                });
            }
            // Fungsi yang dijalankan jika ada error saat pemindaian
            function onScanFailure(error) {
                // Kita bisa mengabaikan error karena biasanya hanya berarti QR Code belum terdeteksi
                // console.warn(`Pemindaian QR Code gagal: ${error}`);
            }

            // Fungsi untuk menghentikan pemindaian
            function stopScanning() {
                if (html5QrCode) {
                    html5QrCode.stop().then(() => {
                        // Reset UI
                        stopButton.style.display = 'none';
                        startButton.style.display = 'inline-block';
                    }).catch((err) => {
                        console.error("Error saat menghentikan pemindaian:", err);
                    });
                }
            }

            // Event listeners
            startButton.addEventListener('click', startScanning);
            stopButton.addEventListener('click', stopScanning);
        });
    </script>
</body>

</html>