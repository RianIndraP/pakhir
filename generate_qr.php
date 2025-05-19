<?php
// File: generate_qr.php
// Script untuk menghasilkan kode QR dari data pemesanan

// Include library QR Code
require_once 'vendor/autoload.php';

// Use library
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\Result\ResultInterface;

// Include koneksi database
require_once 'includes/db.php';

// Cek apakah ada booking_id di parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Jika tidak ada, tampilkan gambar error
    header('Content-Type: image/png');
    echo file_get_contents('assets/images/qr-error.png');
    exit();
}

$booking_id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Query untuk mendapatkan data pemesanan
$query = "SELECT b.id, b.booking_date, b.check_in_date, b.check_out_date, 
          g.name as guest_name, r.room_number
          FROM bookings b 
          JOIN guests g ON b.guest_id = g.id
          JOIN rooms r ON b.room_id = r.id
          WHERE b.id = '$booking_id'";

$result = mysqli_query($koneksi, $query);

// Jika data tidak ditemukan
if (mysqli_num_rows($result) == 0) {
    // Tampilkan gambar error
    header('Content-Type: image/png');
    echo file_get_contents('assets/images/qr-error.png');
    exit();
}

$booking = mysqli_fetch_assoc($result);

// Buat data untuk QR Code
$data = json_encode([
    'booking_id' => $booking['id'],
    'guest_name' => $booking['guest_name'],
    'room_number' => $booking['room_number'],
    'check_in' => $booking['check_in_date'],
    'check_out' => $booking['check_out_date']
]);

// URL untuk konfirmasi pemesanan
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$confirmUrl = $baseUrl . "/confirm_booking.php?data=" . base64_encode($data);

// Buat QR Code
$qrCode = QrCode::create($confirmUrl)
    ->setEncoding(new Encoding('UTF-8'))
    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
    ->setSize(300)
    ->setMargin(10)
    ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
    ->setForegroundColor(new Color(0, 0, 0))
    ->setBackgroundColor(new Color(255, 255, 255));

// Optional Logo
// $logo = Logo::create('assets/images/logo.png')
//     ->setResizeToWidth(50);

// Tambahkan label (opsional)
$label = Label::create('Booking ID: ' . $booking['id'])
    ->setTextColor(new Color(0, 0, 0));

// Tulis QR code ke PNG
$writer = new PngWriter();
$result = $writer->write($qrCode, null, $label);

// Simpan QR Code (opsional)
$qrPath = 'qrcodes/booking_' . $booking['id'] . '.png';
file_put_contents($qrPath, $result->getString());

// Cek apakah QR Code ingin diunduh
if (isset($_GET['download']) && $_GET['download'] == 1) {
    // Set header untuk download
    header('Content-Type: ' . $result->getMimeType());
    header('Content-Disposition: attachment; filename="booking_' . $booking['id'] . '.png"');
    echo $result->getString();
} else {
    // Tampilkan QR Code
    header('Content-Type: ' . $result->getMimeType());
    echo $result->getString();
}
