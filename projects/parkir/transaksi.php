<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Ambil data kendaraan untuk dropdown
$kendaraan_query = "SELECT * FROM kendaraan";
$kendaraan_result = mysqli_query($conn, $kendaraan_query);

// Ambil data tarif untuk informasi
$tarif_query = "SELECT k.jenis, t.biaya, t.setting 
                FROM tarif t 
                JOIN kendaraan k ON t.id_kendaraan = k.id";
$tarif_result = mysqli_query($conn, $tarif_query);
$tarif_data = [];
while($tarif = mysqli_fetch_assoc($tarif_result)) {
    $tarif_data[] = $tarif;
}

// Proses transaksi masuk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['masuk'])) {
    $nopol = mysqli_real_escape_string($conn, $_POST['nopol']);
    $id_kendaraan = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);
    $tgl_masuk = mysqli_real_escape_string($conn, $_POST['tgl_masuk']);
    $jam_masuk = mysqli_real_escape_string($conn, $_POST['jam_masuk']);
    $id_petugas = $_SESSION['user_id'];
    
    // Konversi format tanggal dari d/m/Y ke Y-m-d
    $tgl_masuk_sql = DateTime::createFromFormat('d/m/Y', $tgl_masuk)->format('Y-m-d');
    
    $query = "INSERT INTO transaksi (tgl_masuk, nopol, id_kendaraan, jam_masuk, id_petugas) 
              VALUES ('$tgl_masuk_sql', '$nopol', '$id_kendaraan', '$jam_masuk', '$id_petugas')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Transaksi masuk berhasil! ID Transaksi: " . mysqli_insert_id($conn);
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Proses transaksi keluar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['keluar'])) {
    $id_transaksi = mysqli_real_escape_string($conn, $_POST['id_transaksi']);
    $tgl_keluar = mysqli_real_escape_string($conn, $_POST['tgl_keluar']);
    $jam_keluar = mysqli_real_escape_string($conn, $_POST['jam_keluar']);
    
    // Ambil data transaksi
    $transaksi_query = "SELECT t.*, k.jenis, p.nama as petugas 
                       FROM transaksi t 
                       JOIN kendaraan k ON t.id_kendaraan = k.id 
                       JOIN petugas p ON t.id_petugas = p.id 
                       WHERE t.id='$id_transaksi'";
    $transaksi_result = mysqli_query($conn, $transaksi_query);
    
    if (mysqli_num_rows($transaksi_result) == 1) {
        $transaksi = mysqli_fetch_assoc($transaksi_result);
        
        // Hitung lama parkir dan biaya
        $jam_masuk = strtotime($transaksi['tgl_masuk'] . ' ' . $transaksi['jam_masuk']);
        
        // Konversi format tanggal keluar dari d/m/Y ke Y-m-d
        $tgl_keluar_sql = DateTime::createFromFormat('d/m/Y', $tgl_keluar)->format('Y-m-d');
        $jam_keluar_timestamp = strtotime($tgl_keluar_sql . ' ' . $jam_keluar);
        
        $lama = ceil(($jam_keluar_timestamp - $jam_masuk) / 3600); // dalam jam
        
        // Ambil tarif
        $tarif_query = "SELECT * FROM tarif WHERE id_kendaraan='{$transaksi['id_kendaraan']}'";
        $tarif_result = mysqli_query($conn, $tarif_query);
        $tarif = mysqli_fetch_assoc($tarif_result);
        
        $biaya = $lama * $tarif['biaya'];
        
        // Update transaksi
        $update_query = "UPDATE transaksi SET 
                        tgl_keluar = '$tgl_keluar_sql',
                        jam_keluar = '$jam_keluar',
                        lama = '$lama',
                        biaya = '$biaya'
                        WHERE id = '$id_transaksi'";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['print_id'] = $id_transaksi;
            redirect('cetak_tiket.php');
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Transaksi tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Parkir - Sistem Parkir Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        
        .transaction-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-section h2 {
            margin-bottom: 25px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section h2 i {
            color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.95em;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group .readonly-input {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e1e1e1;
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
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
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tarif-list {
            list-style: none;
            padding-left: 0;
        }
        
        .tarif-list li {
            padding: 8px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #bbdefb;
        }
        
        .tarif-list li:last-child {
            border-bottom: none;
        }
        
        .tarif-list li i {
            color: #1976d2;
            font-size: 0.9em;
        }
        
        .tarif-jenis {
            font-weight: 600;
            min-width: 80px;
        }
        
        .tarif-biaya {
            font-weight: 600;
            color: var(--success);
        }
        
        .checkbox-list {
            list-style: none;
            padding-left: 0;
        }
        
        .checkbox-list li {
            padding: 8px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-list li i {
            color: var(--success);
            font-size: 0.9em;
        }
        
        .flatpickr-input {
            background: white !important;
        }
        
        @media (max-width: 1024px) {
            .transaction-container {
                grid-template-columns: 1fr;
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
            <li><a href="transaksi.php" class="active"><i class="fas fa-car"></i> Transaksi Parkir</a></li>
            <li><a href="data_transaksi.php"><i class="fas fa-history"></i> Data Transaksi</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="manajemen.php"><i class="fas fa-cog"></i> Manajemen</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Transaksi Parkir</h1>
                <p>Kelola transaksi masuk dan keluar kendaraan</p>
            </div>
            <div style="color: #666;">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y H:i:s'); ?>
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
        
        <div class="transaction-container">
            <!-- Form Transaksi Masuk -->
            <div class="form-section">
                <h2><i class="fas fa-sign-in-alt"></i> Transaksi Masuk</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nopol"><i class="fas fa-car"></i> Nomor Polisi</label>
                        <input type="text" id="nopol" name="nopol" required 
                               placeholder="Contoh: B 1234 ABC"
                               pattern="[A-Za-z0-9\s]+"
                               title="Masukkan nomor polisi yang valid">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_kendaraan"><i class="fas fa-car-side"></i> Jenis Kendaraan</label>
                        <select id="id_kendaraan" name="id_kendaraan" required>
                            <option value="">Pilih Jenis Kendaraan</option>
                            <?php 
                            mysqli_data_seek($kendaraan_result, 0);
                            while($row = mysqli_fetch_assoc($kendaraan_result)): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['jenis']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tgl_masuk"><i class="fas fa-calendar"></i> Tanggal Masuk</label>
                        <input type="text" id="tgl_masuk" name="tgl_masuk" required
                               class="flatpickr-input"
                               placeholder="Pilih Tanggal Masuk"
                               value="<?php echo date('d/m/Y'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="jam_masuk"><i class="fas fa-clock"></i> Jam Masuk</label>
                        <input type="time" id="jam_masuk" name="jam_masuk" required
                               value="<?php echo date('H:i'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="petugas"><i class="fas fa-user"></i> Petugas</label>
                        <input type="text" id="petugas" name="petugas" readonly
                               class="readonly-input"
                               value="<?php echo htmlspecialchars($_SESSION['nama']); ?>">
                    </div>
                    
                    <button type="submit" name="masuk" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Transaksi Masuk
                    </button>
                </form>
                
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Informasi Transaksi Masuk</h4>
                    <ul class="checkbox-list">
                        <li><i class="fas fa-check"></i> Isi semua data dengan benar</li>
                        <li><i class="fas fa-check"></i> ID transaksi akan digenerate secara otomatis</li>
                        <li><i class="fas fa-check"></i> Pastikan data kendaraan sudah benar</li>
                    </ul>
                </div>
            </div>
            
            <!-- Form Transaksi Keluar -->
            <div class="form-section">
                <h2><i class="fas fa-sign-out-alt"></i> Transaksi Keluar</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="id_transaksi"><i class="fas fa-id-card"></i> ID Transaksi</label>
                        <input type="text" id="id_transaksi" name="id_transaksi" required 
                               placeholder="Masukkan ID Transaksi"
                               pattern="[0-9]+"
                               title="Masukkan ID transaksi yang valid">
                    </div>

                    <div class="form-group">
                        <label for="tgl_keluar"><i class="fas fa-calendar"></i> Tanggal Keluar</label>
                        <input type="text" id="tgl_keluar" name="tgl_keluar" required
                               class="flatpickr-input"
                               placeholder="Pilih Tanggal Keluar"
                               value="<?php echo date('d/m/Y'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="jam_keluar"><i class="fas fa-clock"></i> Jam Keluar</label>
                        <input type="time" id="jam_keluar" name="jam_keluar" required
                               value="<?php echo date('H:i'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="lama_jam"><i class="fas fa-hourglass"></i> Lama Parkir (Jam)</label>
                        <input type="number" id="lama_jam" name="lama_jam" required
                               placeholder="Masukkan lama parkir dalam jam"
                               min="1" max="24"
                               onchange="hitungBiaya()">
                    </div>

                    <div class="form-group">
                        <label for="biaya"><i class="fas fa-money-bill-wave"></i> Biaya Parkir</label>
                        <input type="text" id="biaya" name="biaya" required
                               placeholder="Biaya akan terhitung otomatis"
                               readonly
                               class="readonly-input">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Informasi Tarif</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e1e1e1;">
                            <ul class="tarif-list">
                                <?php foreach($tarif_data as $tarif): ?>
                                <li>
                                    <i class="fas fa-car"></i>
                                    <span class="tarif-jenis"><?php echo $tarif['jenis']; ?>:</span>
                                    <span class="tarif-biaya">Rp <?php echo number_format($tarif['biaya'], 0, ',', '.'); ?></span>
                                    <span style="color: #666; font-size: 0.9em;">/ <?php echo $tarif['setting']; ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <button type="submit" name="keluar" class="btn btn-success">
                        <i class="fas fa-calculator"></i> Proses Keluar & Cetak Tiket
                    </button>
                </form>
                
                <div class="info-box">
                    <h4><i class="fas fa-calculator"></i> Perhitungan Biaya</h4>
                    <ul class="checkbox-list">
                        <li><i class="fas fa-check"></i> Biaya sesuai jenis kendaraan</li>
                        <li><i class="fas fa-check"></i> Tiket akan dicetak otomatis</li>
                        <li><i class="fas fa-check"></i> Lama parkir dihitung per jam</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    <script>
        // Inisialisasi Flatpickr untuk tanggal
        flatpickr("#tgl_masuk", {
            dateFormat: "d/m/Y",
            locale: "id",
            allowInput: true
        });

        flatpickr("#tgl_keluar", {
            dateFormat: "d/m/Y",
            locale: "id",
            allowInput: true
        });

        // Auto capitalize untuk nomor polisi
        document.getElementById('nopol').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
        
        // Fungsi untuk menghitung biaya
        function hitungBiaya() {
            const lamaJam = document.getElementById('lama_jam').value;
            const jenisKendaraan = document.getElementById('id_kendaraan').value;
            
            if (lamaJam && jenisKendaraan) {
                // Tarif berdasarkan jenis kendaraan (dalam PHP sudah diambil dari database)
                const tarif = {
                    '1': 5000, // Mobil
                    '2': 3000, // Motor
                    '3': 10000 // Truk
                };
                
                const biaya = lamaJam * tarif[jenisKendaraan];
                document.getElementById('biaya').value = 'Rp ' + biaya.toLocaleString('id-ID');
            }
        }
        
        // Event listener untuk jenis kendaraan
        document.getElementById('id_kendaraan').addEventListener('change', hitungBiaya);
        
        // Validasi form
        document.querySelector('form').addEventListener('submit', function(e) {
            const nopol = document.getElementById('nopol').value;
            if (nopol.length < 3) {
                alert('Nomor polisi harus diisi dengan minimal 3 karakter');
                e.preventDefault();
            }
        });
        
        // Focus ke input nomor polisi
        document.getElementById('nopol').focus();
    </script>
</body>
</html>