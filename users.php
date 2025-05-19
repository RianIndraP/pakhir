<?php
session_start();
require_once '../includes/db.php';

// Cek login dan peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Proses tambah/edit/hapus user
$message = '';
$edit_user = null;

// Hapus user
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Cek apakah user yang akan dihapus bukan user yang sedang login
    if ($_SESSION['user_id'] != $user_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $message = "User berhasil dihapus!";
        } else {
            $message = "Gagal menghapus user: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Anda tidak dapat menghapus akun yang sedang aktif!";
    }
}

// Edit user
if (isset($_GET['edit'])) {
    $user_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
    $stmt->close();
}

// Proses form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_user']) || isset($_POST['update_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Validasi
        if (empty($username) || (isset($_POST['add_user']) && empty($password)) || empty($role)) {
            $message = "Semua field harus diisi!";
        } else {
            // Update user
            if (isset($_POST['update_user'])) {
                $user_id = $_POST['user_id'];
                
                if (!empty($password)) {
                    // Update dengan password baru
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $username, $password, $role, $user_id);
                } else {
                    // Update tanpa ubah password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $username, $role, $user_id);
                }
                
                if ($stmt->execute()) {
                    $message = "User berhasil diupdate!";
                    $edit_user = null; // Reset form
                } else {
                    $message = "Gagal update user: " . $conn->error;
                }
                $stmt->close();
            } 
            // Tambah user baru
            else {
                // Cek apakah username sudah ada
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $message = "Username sudah digunakan!";
                } else {
                    $stmt->close();
                    
                    // Insert user baru
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $password, $role);
                    
                    if ($stmt->execute()) {
                        $message = "User baru berhasil ditambahkan!";
                    } else {
                        $message = "Gagal menambahkan user: " . $conn->error;
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Ambil daftar user
$users = [];
$result = $conn->query("SELECT id, username, role FROM users ORDER BY username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Hotel Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manajemen User</h1>
            <nav>
                <ul>
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <li><a href="../bookings.php">Pemesanan</a></li>
                    <li><a href="../rooms.php">Kamar</a></li>
                    <li><a href="users.php" class="active">Users</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <?php if (!empty($message)): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <section class="form-section">
                <h2><?php echo $edit_user ? 'Edit User' : 'Tambah User Baru'; ?></h2>
                <form method="post" action="users.php">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo $edit_user ? $edit_user['username'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:<?php echo $edit_user ? ' (Kosongkan jika tidak ingin mengubah)' : ''; ?></label>
                        <input type="password" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="staff" <?php echo ($edit_user && $edit_user['role'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($edit_user): ?>
                            <button type="submit" name="update_user" class="btn primary">Update User</button>
                            <a href="users.php" class="btn secondary">Batal</a>
                        <?php else: ?>
                            <button type="submit" name="add_user" class="btn primary">Tambah User</button>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
            
            <section class="data-table">
                <h2>Daftar User</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Tidak ada data user</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                    <td class="actions">
                                        <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn small">Edit</a>
                                        <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn small danger" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                        <?php else: ?>
                                            <span class="btn small disabled" title="Tidak dapat menghapus akun sendiri">Hapus</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Hotel Booking System</p>
        </footer>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>