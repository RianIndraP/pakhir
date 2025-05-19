<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff')) {
    header("Location: login.html");
    exit();
}

include 'includes/db.php';

// Proses tambah kamar baru
if (isset($_POST['add_room'])) {
    $room_number = filter_var($_POST['room_number'], FILTER_SANITIZE_STRING);
    $room_type = filter_var($_POST['room_type'], FILTER_SANITIZE_STRING);
    $capacity = filter_var($_POST['capacity'], FILTER_SANITIZE_NUMBER_INT);
    $price_per_night = isset($_POST['price_per_night'])
        ? filter_var($_POST['price_per_night'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
        : 0;
    $status = 'available'; // Default status

    // Validasi input
    if (empty($room_number) || empty($room_type) || empty($capacity) || empty($price_per_night)) {
        $error = "Semua kolom harus diisi!";
    } else {
        // Cek apakah nomor kamar sudah ada
        $check_query = "SELECT * FROM rooms WHERE room_number = ?";
        $check_stmt = $koneksi->prepare($check_query);
        $check_stmt->bind_param("s", $room_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Nomor kamar sudah ada!";
        } else {
            // Insert kamar baru
            $insert_query = "INSERT INTO rooms (room_number, room_type, capacity, price_per_night, status) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $koneksi->prepare($insert_query);
            $insert_stmt->bind_param("ssids", $room_number, $room_type, $capacity, $price_per_night, $status);

            if ($insert_stmt->execute()) {
                $success = "Kamar berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan kamar: " . $koneksi->error;
            }
        }
    }
}

// Proses update kamar
if (isset($_POST['update_room'])) {
    $room_id = filter_var($_POST['room_id'], FILTER_SANITIZE_NUMBER_INT);
    $room_number = filter_var($_POST['room_number'], FILTER_SANITIZE_STRING);
    $room_type = filter_var($_POST['room_type'], FILTER_SANITIZE_STRING);
    $capacity = filter_var($_POST['capacity'], FILTER_SANITIZE_NUMBER_INT);
    $price_per_night = isset($_POST['price_per_night'])
        ? filter_var($_POST['price_per_night'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
        : 0;
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    // Validasi input
    if (empty($room_number) || empty($room_type) || empty($capacity) || empty($price_per_night)) {
        $error = "Semua kolom harus diisi!";
    } else {
        // Cek apakah nomor kamar sudah ada pada kamar lain
        $check_query = "SELECT * FROM rooms WHERE room_number = ? AND id != ?";
        $check_stmt = $koneksi->prepare($check_query);
        $check_stmt->bind_param("si", $room_number, $room_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Nomor kamar sudah digunakan oleh kamar lain!";
        } else {
            // Update data kamar
            $update_query = "UPDATE rooms SET room_number = ?, room_type = ?, capacity = ?, price_per_night = ?, status = ? WHERE id = ?";
            $update_stmt = $koneksi->prepare($update_query);
            $update_stmt->bind_param("ssidsi", $room_number, $room_type, $capacity, $price_per_night, $status, $room_id);

            if ($update_stmt->execute()) {
                $success = "Data kamar berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui data kamar: " . $koneksi->error;
            }
        }
    }
}

// Proses hapus kamar
if (isset($_GET['delete']) && $_SESSION['role'] == 'admin') {
    $room_id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);

    // Cek apakah kamar sedang digunakan dalam pemesanan aktif
    $check_query = "SELECT * FROM bookings WHERE room_id = ? AND (status = 'confirmed' OR status = 'checked_in')";
    $check_stmt = $koneksi->prepare($check_query);
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Kamar tidak dapat dihapus karena sedang digunakan dalam pemesanan aktif!";
    } else {
        // Hapus kamar
        $delete_query = "DELETE FROM rooms WHERE id = ?";
        $delete_stmt = $koneksi->prepare($delete_query);
        $delete_stmt->bind_param("i", $room_id);

        if ($delete_stmt->execute()) {
            $success = "Kamar berhasil dihapus!";
        } else {
            $error = "Gagal menghapus kamar: " . $koneksi->error;
        }
    }
}

// Ambil parameter dari URL dengan default
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'room_number';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Validasi parameter sort dan order untuk mencegah SQL Injection
$allowed_sort = ['room_number', 'room_type', 'capacity', 'price_per_night', 'status'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'room_number';
}

$order = strtoupper($order);
if ($order !== 'ASC' && $order !== 'DESC') {
    $order = 'ASC';
}

// Buat query dengan LIKE di 3 kolom string
$query = "SELECT * FROM rooms WHERE 
          room_number LIKE ? OR 
          room_type LIKE ? OR 
          status LIKE ? 
          ORDER BY $sort_by $order";

$stmt = $koneksi->prepare($query);

$search_param = "%$search%";
$stmt->bind_param("sss", $search_param, $search_param, $search_param);

$stmt->execute();
$result = $stmt->get_result();

// Hitung statistik kamar
$stats_query = "SELECT 
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                COUNT(*) as total
                FROM rooms";

$stats_result = $koneksi->query($stats_query);
$stats = $stats_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kamar - Sistem Pemesanan Hotel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hotel mr-2"></i>Sistem Pemesanan Hotel
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">Pemesanan</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="rooms.php">Kamar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="scan.php">Scan QR</a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/users.php">Pengguna</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-bed mr-2"></i>Manajemen Kamar</h2>
            </div>
            <div class="col-md-6 text-right">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addRoomModal">
                    <i class="fas fa-plus mr-2"></i>Tambah Kamar
                </button>
            </div>
        </div>

        <!-- Notifikasi -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Statistik Kamar -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Tersedia</h5>
                        <h2><?php echo $stats['available']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Terisi</h5>
                        <h2><?php echo $stats['occupied']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pemeliharaan</h5>
                        <h2><?php echo $stats['maintenance']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Kamar</h5>
                        <h2><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter dan Pencarian -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="rooms.php" class="form-row align-items-center">
                    <div class="col-md-6 mb-2">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Cari kamar..." value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Urutkan</span>
                            </div>
                            <select name="sort" class="form-control">
                                <option value="room_number" <?php echo $sort_by == 'room_number' ? 'selected' : ''; ?>>Nomor Kamar</option>
                                <option value="room_type" <?php echo $sort_by == 'room_type' ? 'selected' : ''; ?>>Tipe Kamar</option>
                                <option value="capacity" <?php echo $sort_by == 'capacity' ? 'selected' : ''; ?>>Kapasitas</option>
                                <option value="price_per_night" <?php echo $sort_by == 'price_per_night' ? 'selected' : ''; ?>>Harga</option>
                                <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="order" class="form-control">
                            <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Naik</option>
                            <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Turun</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Kamar -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nomor Kamar</th>
                                <th>Tipe Kamar</th>
                                <th>Kapasitas</th>
                                <th>Harga/Malam</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($room = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td><?php echo htmlspecialchars($room['capacity']); ?> orang</td>
                                        <td>Rp <?php echo isset($room['price_per_night']) ? number_format($room['price_per_night'], 0, ',', '.') : '0'; ?></td>
                                        <td>
                                            <?php if ($room['status'] == 'available'): ?>
                                                <span class="badge badge-success">Tersedia</span>
                                            <?php elseif ($room['status'] == 'occupied'): ?>
                                                <span class="badge badge-danger">Terisi</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pemeliharaan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editRoomBtn"
                                                data-id="<?php echo $room['id']; ?>"
                                                data-room_number="<?php echo $room['room_number']; ?>"
                                                data-room_type="<?php echo $room['room_type']; ?>"
                                                data-capacity="<?php echo $room['capacity']; ?>"
                                                data-price_per_night="<?php echo isset($room['price_per_night']) ? $room['price_per_night'] : 0; ?>"
                                                data-status="<?php echo $room['status']; ?>"
                                                data-toggle="modal"
                                                data-target="#editRoomModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                                <a href="rooms.php?delete=<?php echo $room['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kamar ini?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data kamar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Kamar -->
    <div class="modal fade" id="addRoomModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>Tambah Kamar Baru
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="rooms.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="room_number">Nomor Kamar:</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="form-group">
                            <label for="room_type">Tipe Kamar:</label>
                            <select class="form-control" id="room_type" name="room_type" required>
                                <option value="">Pilih Tipe Kamar</option>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                                <option value="Executive">Executive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="capacity">Kapasitas (orang):</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="price_per_night">Harga per Malam (Rp):</label>
                            <input type="number" class="form-control" id="price_per_night" name="price_per_night" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="add_room" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kamar -->
    <div class="modal fade" id="editRoomModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit mr-2"></i>Edit Data Kamar
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="rooms.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_room_id" name="room_id">
                        <div class="form-group">
                            <label for="edit_room_number">Nomor Kamar:</label>
                            <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_room_type">Tipe Kamar:</label>
                            <select class="form-control" id="edit_room_type" name="room_type" required>
                                <option value="">Pilih Tipe Kamar</option>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                                <option value="Executive">Executive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_capacity">Kapasitas (orang):</label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_price_per_night">Harga per Malam (Rp):</label>
                            <input type="number" class="form-control" id="edit_price_per_night" name="price_per_night" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status:</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="available">Tersedia</option>
                                <option value="occupied">Terisi</option>
                                <option value="maintenance">Pemeliharaan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="update_room" class="btn btn-info">Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.editRoomBtn').click(function() {
                $('#edit_room_id').val($(this).data('id'));
                $('#edit_room_number').val($(this).data('room_number'));
                $('#edit_room_type').val($(this).data('room_type'));
                $('#edit_capacity').val($(this).data('capacity'));
                $('#edit_price_per_night').val($(this).data('price_per_night'));
                $('#edit_status').val($(this).data('status'));
            });
        });
    </script>
</body>

</html>