<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Cek level akses - hanya admin yang bisa akses
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM petugas WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

if ($user_data['username'] != 'admin') {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman manajemen!";
    redirect('dashboard.php');
}

// Inisialisasi variabel
$success = '';
$error = '';

// === MANAJEMEN KENDARAAN ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kendaraan'])) {
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    
    $query = "INSERT INTO kendaraan (jenis) VALUES ('$jenis')";
    if (mysqli_query($conn, $query)) {
        $success = "Jenis kendaraan berhasil ditambahkan!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

if (isset($_GET['hapus_kendaraan'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_kendaraan']);
    $query = "DELETE FROM kendaraan WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        $success = "Jenis kendaraan berhasil dihapus!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// === MANAJEMEN TARIF ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_tarif'])) {
    $id_kendaraan = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);
    $biaya = mysqli_real_escape_string($conn, $_POST['biaya']);
    $setting = mysqli_real_escape_string($conn, $_POST['setting']);
    
    // Cek apakah tarif sudah ada
    $cek_query = "SELECT * FROM tarif WHERE id_kendaraan = '$id_kendaraan'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) > 0) {
        $error = "Tarif untuk jenis kendaraan ini sudah ada!";
    } else {
        $query = "INSERT INTO tarif (biaya, id_kendaraan, setting) VALUES ('$biaya', '$id_kendaraan', '$setting')";
        if (mysqli_query($conn, $query)) {
            $success = "Tarif berhasil ditambahkan!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_tarif'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $biaya = mysqli_real_escape_string($conn, $_POST['biaya']);
    $setting = mysqli_real_escape_string($conn, $_POST['setting']);
    
    $query = "UPDATE tarif SET biaya = '$biaya', setting = '$setting' WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        $success = "Tarif berhasil diupdate!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

if (isset($_GET['hapus_tarif'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_tarif']);
    $query = "DELETE FROM tarif WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        $success = "Tarif berhasil dihapus!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// === MANAJEMEN PETUGAS ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_petugas'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Cek apakah username sudah ada
    $cek_query = "SELECT * FROM petugas WHERE username = '$username'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) > 0) {
        $error = "Username sudah digunakan!";
    } else {
        $query = "INSERT INTO petugas (nama, username, password) VALUES ('$nama', '$username', '$password')";
        if (mysqli_query($conn, $query)) {
            $success = "Petugas berhasil ditambahkan!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_petugas'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "UPDATE petugas SET nama = '$nama', username = '$username', password = '$password' WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        $success = "Data petugas berhasil diupdate!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

if (isset($_GET['hapus_petugas'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_petugas']);
    
    // Cek apakah petugas sedang digunakan di transaksi
    $cek_query = "SELECT * FROM transaksi WHERE id_petugas = '$id'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) > 0) {
        $error = "Petugas tidak dapat dihapus karena memiliki riwayat transaksi!";
    } else {
        $query = "DELETE FROM petugas WHERE id = '$id'";
        if (mysqli_query($conn, $query)) {
            $success = "Petugas berhasil dihapus!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Ambil data untuk ditampilkan
$kendaraan_query = "SELECT * FROM kendaraan ORDER BY jenis";
$kendaraan_result = mysqli_query($conn, $kendaraan_query);

$tarif_query = "SELECT t.*, k.jenis FROM tarif t JOIN kendaraan k ON t.id_kendaraan = k.id ORDER BY k.jenis";
$tarif_result = mysqli_query($conn, $tarif_query);

$petugas_query = "SELECT * FROM petugas ORDER BY nama";
$petugas_result = mysqli_query($conn, $petugas_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen - Sistem Parkir Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background: #f5f5f5;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 0;
        }
        
        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin-bottom: 5px;
            font-size: 1.5em;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9em;
        }
        
        .user-info {
            padding: 20px;
            background: rgba(255,255,255,0.1);
            margin: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .user-info i {
            font-size: 2em;
            margin-bottom: 10px;
            display: block;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 15px;
        }
        
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .management-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-header h2 {
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: black;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.9em;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #e1e1e1;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2,
            .sidebar-header p,
            .user-info h3,
            .user-info p,
            .sidebar-menu span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar-menu i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-parking"></i> Parkir Online</h2>
            <p>Sistem Manajemen Parkir</p>
        </div>
        
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <h3><?php echo htmlspecialchars($_SESSION['nama']); ?></h3>
            <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="transaksi.php"><i class="fas fa-car"></i> Transaksi Parkir</a></li>
            <li><a href="data_transaksi.php"><i class="fas fa-history"></i> Data Transaksi</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="manajemen.php" class="active"><i class="fas fa-cog"></i> Manajemen</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Manajemen Sistem</h1>
                <p>Kelola data master sistem parkir</p>
            </div>
            <div style="color: #666;">
                <i class="fas fa-shield-alt"></i> Mode Administrator
            </div>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" onclick="showTab('kendaraan')">
                    <i class="fas fa-car"></i> Jenis Kendaraan
                </div>
                <div class="tab" onclick="showTab('tarif')">
                    <i class="fas fa-money-bill-wave"></i> Tarif Parkir
                </div>
                <div class="tab" onclick="showTab('petugas')">
                    <i class="fas fa-users"></i> Data Petugas
                </div>
            </div>
            
            <!-- Tab Jenis Kendaraan -->
            <div id="kendaraan" class="tab-content active">
                <div class="management-section">
                    <div class="section-header">
                        <h2><i class="fas fa-car-side"></i> Manajemen Jenis Kendaraan</h2>
                    </div>
                    
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="jenis"><i class="fas fa-car"></i> Jenis Kendaraan Baru</label>
                            <input type="text" id="jenis" name="jenis" required 
                                   placeholder="Contoh: Sepeda, Bus, dll">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="tambah_kendaraan" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Jenis
                            </button>
                        </div>
                    </form>
                    
                    <?php if (mysqli_num_rows($kendaraan_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Jenis Kendaraan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($kendaraan = mysqli_fetch_assoc($kendaraan_result)): ?>
                            <tr>
                                <td><?php echo $kendaraan['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($kendaraan['jenis']); ?></strong></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?hapus_kendaraan=<?php echo $kendaraan['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Hapus jenis kendaraan <?php echo $kendaraan['jenis']; ?>?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-car"></i>
                        <h3>Belum Ada Jenis Kendaraan</h3>
                        <p>Tambahkan jenis kendaraan terlebih dahulu</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab Tarif Parkir -->
            <div id="tarif" class="tab-content">
                <div class="management-section">
                    <div class="section-header">
                        <h2><i class="fas fa-money-bill-wave"></i> Manajemen Tarif Parkir</h2>
                    </div>
                    
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="id_kendaraan"><i class="fas fa-car"></i> Jenis Kendaraan</label>
                            <select id="id_kendaraan" name="id_kendaraan" required>
                                <option value="">Pilih Jenis Kendaraan</option>
                                <?php 
                                mysqli_data_seek($kendaraan_result, 0);
                                while($kendaraan = mysqli_fetch_assoc($kendaraan_result)): 
                                ?>
                                <option value="<?php echo $kendaraan['id']; ?>">
                                    <?php echo htmlspecialchars($kendaraan['jenis']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="biaya"><i class="fas fa-money-bill"></i> Biaya per Jam</label>
                            <input type="number" id="biaya" name="biaya" required 
                                   placeholder="Contoh: 5000" min="1000">
                        </div>
                        <div class="form-group">
                            <label for="setting"><i class="fas fa-cog"></i> Setting</label>
                            <input type="text" id="setting" name="setting" required 
                                   placeholder="Contoh: Per Jam" value="Per Jam">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="tambah_tarif" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Tarif
                            </button>
                        </div>
                    </form>
                    
                    <?php if (mysqli_num_rows($tarif_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Jenis Kendaraan</th>
                                <th>Biaya per Jam</th>
                                <th>Setting</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($tarif = mysqli_fetch_assoc($tarif_result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tarif['jenis']); ?></strong></td>
                                <td>Rp <?php echo number_format($tarif['biaya'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($tarif['setting']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="editTarif(<?php echo $tarif['id']; ?>, '<?php echo $tarif['jenis']; ?>', <?php echo $tarif['biaya']; ?>, '<?php echo $tarif['setting']; ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?hapus_tarif=<?php echo $tarif['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Hapus tarif untuk <?php echo $tarif['jenis']; ?>?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>Belum Ada Data Tarif</h3>
                        <p>Tambahkan tarif parkir terlebih dahulu</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab Data Petugas -->
            <div id="petugas" class="tab-content">
                <div class="management-section">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> Manajemen Data Petugas</h2>
                    </div>
                    
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="nama"><i class="fas fa-user"></i> Nama Petugas</label>
                            <input type="text" id="nama" name="nama" required 
                                   placeholder="Nama lengkap petugas">
                        </div>
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user-tag"></i> Username</label>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Username untuk login">
                        </div>
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Password untuk login">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="tambah_petugas" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Petugas
                            </button>
                        </div>
                    </form>
                    
                    <?php if (mysqli_num_rows($petugas_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($petugas = mysqli_fetch_assoc($petugas_result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($petugas['nama']); ?></strong></td>
                                <td><?php echo htmlspecialchars($petugas['username']); ?></td>
                                <td>••••••••</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="editPetugas(<?php echo $petugas['id']; ?>, '<?php echo $petugas['nama']; ?>', '<?php echo $petugas['username']; ?>', '<?php echo $petugas['password']; ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($petugas['username'] != 'admin'): ?>
                                        <a href="?hapus_petugas=<?php echo $petugas['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Hapus petugas <?php echo $petugas['nama']; ?>?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users"></i>
                        <h3>Belum Ada Data Petugas</h3>
                        <p>Tambahkan data petugas terlebih dahulu</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Tarif -->
    <div id="editTarifModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; width: 500px; max-width: 90%;">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-edit"></i> Edit Tarif Parkir
            </h3>
            <form method="POST" id="editTarifForm">
                <input type="hidden" name="id" id="edit_tarif_id">
                <div class="form-group">
                    <label for="edit_biaya">Biaya per Jam</label>
                    <input type="number" id="edit_biaya" name="biaya" required min="1000">
                </div>
                <div class="form-group">
                    <label for="edit_setting">Setting</label>
                    <input type="text" id="edit_setting" name="setting" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_tarif" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editTarifModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Petugas -->
    <div id="editPetugasModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; width: 500px; max-width: 90%;">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-edit"></i> Edit Data Petugas
            </h3>
            <form method="POST" id="editPetugasForm">
                <input type="hidden" name="id" id="edit_petugas_id">
                <div class="form-group">
                    <label for="edit_nama">Nama Petugas</label>
                    <input type="text" id="edit_nama" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit_password">Password</label>
                    <input type="password" id="edit_password" name="password" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_petugas" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editPetugasModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk tabs
        function showTab(tabName) {
            // Sembunyikan semua tab content
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Hapus active dari semua tabs
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Tampilkan tab yang dipilih
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        // Fungsi edit tarif
        function editTarif(id, jenis, biaya, setting) {
            document.getElementById('edit_tarif_id').value = id;
            document.getElementById('edit_biaya').value = biaya;
            document.getElementById('edit_setting').value = setting;
            
            // Update judul modal
            document.querySelector('#editTarifModal h3').innerHTML = 
                '<i class="fas fa-edit"></i> Edit Tarif - ' + jenis;
            
            document.getElementById('editTarifModal').style.display = 'flex';
        }
        
        // Fungsi edit petugas
        function editPetugas(id, nama, username, password) {
            document.getElementById('edit_petugas_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_password').value = password;
            
            document.getElementById('editPetugasModal').style.display = 'flex';
        }
        
        // Fungsi tutup modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Tutup modal ketika klik di luar
        window.onclick = function(event) {
            if (event.target.id === 'editTarifModal') {
                closeModal('editTarifModal');
            }
            if (event.target.id === 'editPetugasModal') {
                closeModal('editPetugasModal');
            }
        }
    </script>
</body>
</html>