<?php


session_start();
// Include database connection
require_once 'includes/db.php';

// Fungsi untuk mencegah SQL Injection
function sanitize($data)
{
    global $koneksi;
    return mysqli_real_escape_string($koneksi, trim($data));
}

// Cek apakah form telah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi data tamu
    $errors = [];

    // Validasi nama tamu
    if (empty($_POST['guest_name'])) {
        $errors[] = "Nama tamu harus diisi";
    } else {
        $guest_name = sanitize($_POST['guest_name']);
    }

    // Validasi email tamu
    if (empty($_POST['guest_email'])) {
        $errors[] = "Email tamu harus diisi";
    } elseif (!filter_var($_POST['guest_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } else {
        $guest_email = sanitize($_POST['guest_email']);
    }

    // Validasi telepon tamu
    if (empty($_POST['guest_phone'])) {
        $errors[] = "Nomor telepon tamu harus diisi";
    } else {
        $guest_phone = sanitize($_POST['guest_phone']);
    }

    // Validasi nomor identitas
    if (empty($_POST['guest_id_number'])) {
        $errors[] = "Nomor identitas tamu harus diisi";
    } else {
        $guest_id_number = sanitize($_POST['guest_id_number']);
    }

    // Validasi alamat
    $guest_address = null;

    // Validasi kamar
    if (empty($_POST['room_id'])) {
        $errors[] = "Kamar harus dipilih";
    } else {
        $room_id = sanitize($_POST['room_id']);

        // Periksa ketersediaan kamar
        $room_check_sql = "SELECT * FROM rooms WHERE id = ? AND status = 'available'";
        $room_check_stmt = $koneksi->prepare($room_check_sql);
        $room_check_stmt->bind_param("i", $room_id);
        $room_check_stmt->execute();
        $room_result = $room_check_stmt->get_result();

        if ($room_result->num_rows == 0) {
            $errors[] = "Kamar tidak tersedia atau tidak valid";
        }
    }

    // Validasi tanggal check-in
    if (empty($_POST['checkin_date'])) {
        $errors[] = "Tanggal check-in harus diisi";
    } else {
        $checkin_date = sanitize($_POST['checkin_date']);
        $current_date = date('Y-m-d');

        if (strtotime($checkin_date) < strtotime($current_date)) {
            $errors[] = "Tanggal check-in tidak boleh kurang dari hari ini";
        }
    }

    // Validasi tanggal check-out
    if (empty($_POST['checkout_date'])) {
        $errors[] = "Tanggal check-out harus diisi";
    } else {
        $checkout_date = sanitize($_POST['checkout_date']);

        if (strtotime($checkout_date) <= strtotime($checkin_date)) {
            $errors[] = "Tanggal check-out harus setelah tanggal check-in";
        }
    }

    // Validasi catatan (opsional)
    $booking_notes = isset($_POST['booking_notes']) ? sanitize($_POST['booking_notes']) : '';

    // Jika tidak ada error, lanjutkan proses penyimpanan
    if (empty($errors)) {
        // Mulai transaksi
        $koneksi->begin_transaction();

        try {
            // 1. Simpan data tamu ke database
            $guest_sql = "INSERT INTO guests (name, email, phone, id_number) 
              VALUES (?, ?, ?, ?)";
            $guest_stmt = $koneksi->prepare($guest_sql);
            $guest_stmt->bind_param("ssss", $guest_name, $guest_email, $guest_phone, $guest_id_number);
            $guest_stmt->execute();

            // Dapatkan ID tamu yang baru saja dimasukkan
            $guest_id = $koneksi->insert_id;

            // 2. Simpan data pemesanan
            $booking_sql = "INSERT INTO bookings (guest_id, room_id, checkin_date, checkout_date, booking_date, status, notes) 
                            VALUES (?, ?, ?, ?, NOW(), 'pending', ?)";

            $booking_stmt = $koneksi->prepare($booking_sql);
            $booking_stmt->bind_param("iisss", $guest_id, $room_id, $checkin_date, $checkout_date, $booking_notes);
            $booking_stmt->execute();

            // Dapatkan ID booking yang baru saja dimasukkan
            $booking_id = $koneksi->insert_id;

            // 3. Update status kamar menjadi 'booked'
            $room_update_sql = "UPDATE rooms SET status = 'booked' WHERE id = ?";
            $room_update_stmt = $koneksi->prepare($room_update_sql);
            $room_update_stmt->bind_param("i", $room_id);
            $room_update_stmt->execute();

            // 4. Log perubahan status booking
            $history_sql = "INSERT INTO booking_history (booking_id, status, user_id, notes) 
                           VALUES (?, 'pending', ?, 'Pemesanan baru dibuat')";

            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // 0 untuk sistem atau tamu

            $history_stmt = $koneksi->prepare($history_sql);
            $history_stmt->bind_param("ii", $booking_id, $user_id);
            $history_stmt->execute();

            // Commit transaksi jika semua operasi berhasil
            $koneksi->commit();

            // Redirect ke halaman sukses dengan ID booking
            header("Location: booking_success.php?id=" . $booking_id);
            exit();
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            $koneksi->rollback();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
} else if (isset($_GET['room_id'])) {
    // Jika ada parameter room_id, ambil informasi kamar tersebut
    $room_id = sanitize($_GET['room_id']);

    $room_sql = "SELECT * FROM rooms WHERE id = ? AND status = 'available'";
    $room_stmt = $koneksi->prepare($room_sql);
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();

    if (mysqli_num_rows($result) == 0) {
        die("<h2>Data pemesanan tidak ditemukan.</h2>");
    }


    // Set room_id untuk pre-select di form
    $selected_room_id = $room_id;
}

// Ambil daftar kamar yang tersedia untuk dropdown
$available_rooms_sql = "SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number";
$available_rooms_result = $koneksi->query($available_rooms_sql);
?>

<!-- Respons JSON untuk Ajax -->
<?php
// Jika ini adalah permintaan Ajax untuk mendapatkan detail kamar
if (isset($_GET['get_room_details']) && isset($_GET['room_id'])) {
    $room_id = sanitize($_GET['room_id']);

    $room_details_sql = "SELECT * FROM rooms WHERE id = ?";
    $room_details_stmt = $koneksi->prepare($room_details_sql);
    $room_details_stmt->bind_param("i", $room_id);
    $room_details_stmt->execute();
    $room_details_result = $room_details_stmt->get_result();

    if ($room_details_result->num_rows > 0) {
        $room_details = $room_details_result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => $room_details
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kamar tidak ditemukan'
        ]);
    }

    exit();
}

// Jika ini adalah permintaan Ajax untuk menghitung total biaya
if (isset($_GET['calculate_price']) && isset($_GET['room_id']) && isset($_GET['checkin']) && isset($_GET['checkout'])) {
    $room_id = sanitize($_GET['room_id']);
    $checkin = sanitize($_GET['checkin']);
    $checkout = sanitize($_GET['checkout']);

    // Hitung jumlah hari
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $interval = $date1->diff($date2);
    $days = $interval->days;

    // Jika tanggal checkout sama dengan checkin, hitung sebagai 1 hari
    if ($days == 0) {
        $days = 1;
    }

    // Ambil harga kamar
    $price_sql = "SELECT price FROM rooms WHERE id = ?";
    $price_stmt = $koneksi->prepare($price_sql);
    $price_stmt->bind_param("i", $room_id);
    $price_stmt->execute();
    $price_result = $price_stmt->get_result();

    if ($price_result->num_rows > 0) {
        $room_data = $price_result->fetch_assoc();
        $price_per_night = $room_data['price'];
        $total_price = $price_per_night * $days;

        echo json_encode([
            'success' => true,
            'data' => [
                'price_per_night' => $price_per_night,
                'days' => $days,
                'total_price' => $total_price,
                'formatted_price' => number_format($total_price, 0, ',', '.')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kamar tidak ditemukan'
        ]);
    }

    exit();
}
