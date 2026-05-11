<?php
// Session start di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

// Cek login
checkLogin();

// Query statistik
$query_parkir_aktif = "SELECT COUNT(*) as total FROM transaksi WHERE tgl_keluar IS NULL";
$result_parkir_aktif = mysqli_query($conn, $query_parkir_aktif);
$parkir_aktif = $result_parkir_aktif ? mysqli_fetch_assoc($result_parkir_aktif) : ['total' => 0];

$query_hari_ini = "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tgl_masuk) = CURDATE()";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$hari_ini = $result_hari_ini ? mysqli_fetch_assoc($result_hari_ini) : ['total' => 0];

$query_pendapatan = "SELECT SUM(biaya) as total FROM transaksi WHERE DATE(tgl_masuk) = CURDATE() AND biaya IS NOT NULL";
$result_pendapatan = mysqli_query($conn, $query_pendapatan);
$pendapatan_data = $result_pendapatan ? mysqli_fetch_assoc($result_pendapatan) : ['total' => 0];
$pendapatan = $pendapatan_data['total'] ?? 0;

$query_kendaraan = "SELECT k.jenis, COUNT(t.id) as jumlah 
                   FROM kendaraan k 
                   LEFT JOIN transaksi t ON k.id = t.id_kendaraan AND t.tgl_keluar IS NULL 
                   GROUP BY k.id";
$result_kendaraan = mysqli_query($conn, $query_kendaraan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Parkir Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS tetap sama seperti sebelumnya */
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
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
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
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            border-left: 5px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 3em;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        .stat-card h3 {
            color: #666;
            margin-bottom: 15px;
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-container h2 {
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-btn {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .action-btn i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .action-btn h3 {
            margin-bottom: 10px;
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="transaksi.php"><i class="fas fa-car"></i> Transaksi Parkir</a></li>
            <li><a href="data_transaksi.php"><i class="fas fa-history"></i> Data Transaksi</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="manajemen.php"><i class="fas fa-cog"></i> Manajemen</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Dashboard Sistem Parkir</h1>
                <p>Selamat datang di sistem manajemen parkir online</p>
            </div>
            <div style="color: #666;">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-car-side"></i>
                <h3>Parkir Aktif</h3>
                <div class="number"><?php echo $parkir_aktif['total']; ?></div>
                <p>Kendaraan sedang parkir</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-exchange-alt"></i>
                <h3>Transaksi Hari Ini</h3>
                <div class="number"><?php echo $hari_ini['total']; ?></div>
                <p>Total transaksi hari ini</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Pendapatan Hari Ini</h3>
                <div class="number"><?php echo formatRupiah($pendapatan); ?></div>
                <p>Total pendapatan hari ini</p>
            </div>
        </div>
        
        <div class="chart-container">
            <h2><i class="fas fa-chart-pie"></i> Distribusi Kendaraan Parkir</h2>
            <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
                <div style="width: 100%; max-width: 500px;">
                    <?php 
                    if ($result_kendaraan) {
                        while($row = mysqli_fetch_assoc($result_kendaraan)): 
                            $percentage = $parkir_aktif['total'] > 0 ? ($row['jumlah'] / $parkir_aktif['total']) * 100 : 0;
                    ?>
                    <div style="margin: 15px 0; display: flex; align-items: center;">
                        <div style="width: 100px; text-align: right; margin-right: 15px;">
                            <?php echo htmlspecialchars($row['jenis']); ?>
                        </div>
                        <div style="flex: 1; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); 
                                      width: <?php echo $percentage; ?>%; 
                                      height: 30px; display: flex; align-items: center; padding-left: 15px; color: white; font-weight: bold;">
                                <?php echo $row['jumlah']; ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    } else {
                        echo "<p style='text-align: center; color: #666;'>Tidak ada data kendaraan</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="transaksi.php?action=masuk" class="action-btn">
                <i class="fas fa-sign-in-alt"></i>
                <h3>Transaksi Masuk</h3>
                <p>Input kendaraan masuk</p>
            </a>
            
            <a href="transaksi.php?action=keluar" class="action-btn">
                <i class="fas fa-sign-out-alt"></i>
                <h3>Transaksi Keluar</h3>
                <p>Proses kendaraan keluar</p>
            </a>
            
            <a href="data_transaksi.php" class="action-btn">
                <i class="fas fa-list"></i>
                <h3>Data Transaksi</h3>
                <p>Lihat riwayat transaksi</p>
            </a>
            
            <a href="laporan.php" class="action-btn">
                <i class="fas fa-chart-line"></i>
                <h3>Laporan</h3>
                <p>Analisis dan laporan</p>
            </a>
        </div>
    </div>
</body>
</html>