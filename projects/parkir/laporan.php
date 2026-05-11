<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Set default date range (bulan ini)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$jenis_kendaraan = isset($_GET['jenis_kendaraan']) ? $_GET['jenis_kendaraan'] : '';

// Query untuk laporan
$where_conditions = ["DATE(t.tgl_masuk) BETWEEN '$start_date' AND '$end_date'"];
if ($jenis_kendaraan) {
    $where_conditions[] = "k.jenis = '$jenis_kendaraan'";
}
$where_clause = implode(' AND ', $where_conditions);

// Total pendapatan
$pendapatan_query = "SELECT SUM(t.biaya) as total_pendapatan 
                     FROM transaksi t 
                     JOIN kendaraan k ON t.id_kendaraan = k.id 
                     WHERE $where_clause AND t.biaya IS NOT NULL";
$pendapatan_result = mysqli_query($conn, $pendapatan_query);
$total_pendapatan = mysqli_fetch_assoc($pendapatan_result)['total_pendapatan'] ?? 0;

// Total transaksi
$transaksi_query = "SELECT COUNT(*) as total_transaksi 
                    FROM transaksi t 
                    JOIN kendaraan k ON t.id_kendaraan = k.id 
                    WHERE $where_clause";
$transaksi_result = mysqli_query($conn, $transaksi_query);
$total_transaksi = mysqli_fetch_assoc($transaksi_result)['total_transaksi'] ?? 0;

// Rata-rata pendapatan per hari
$avg_query = "SELECT AVG(daily_income) as avg_pendapatan 
              FROM (SELECT DATE(t.tgl_masuk) as day, SUM(t.biaya) as daily_income 
                    FROM transaksi t 
                    JOIN kendaraan k ON t.id_kendaraan = k.id 
                    WHERE $where_clause AND t.biaya IS NOT NULL 
                    GROUP BY DATE(t.tgl_masuk)) as daily";
$avg_result = mysqli_query($conn, $avg_query);
$avg_pendapatan = mysqli_fetch_assoc($avg_result)['avg_pendapatan'] ?? 0;

// Data untuk chart (pendapatan per hari)
$chart_query = "SELECT DATE(t.tgl_masuk) as tanggal, 
                       SUM(t.biaya) as pendapatan,
                       COUNT(*) as jumlah_transaksi
                FROM transaksi t 
                JOIN kendaraan k ON t.id_kendaraan = k.id 
                WHERE $where_clause AND t.biaya IS NOT NULL 
                GROUP BY DATE(t.tgl_masuk) 
                ORDER BY tanggal";
$chart_result = mysqli_query($conn, $chart_query);

// Data kendaraan untuk filter
$kendaraan_query = "SELECT DISTINCT jenis FROM kendaraan";
$kendaraan_result = mysqli_query($conn, $kendaraan_query);

// Data transaksi detail
$detail_query = "SELECT t.*, k.jenis as jenis_kendaraan, p.nama as petugas 
                 FROM transaksi t 
                 JOIN kendaraan k ON t.id_kendaraan = k.id 
                 JOIN petugas p ON t.id_petugas = p.id 
                 WHERE $where_clause 
                 ORDER BY t.tgl_masuk DESC, t.jam_masuk DESC 
                 LIMIT 100";
$detail_result = mysqli_query($conn, $detail_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Parkir - Sistem Parkir Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
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
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .stat-card h3 {
            color: #666;
            margin-bottom: 10px;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: var(--dark);
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            height: 400px;
        }
        
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 1024px) {
            .filter-form {
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
            <li><a href="laporan.php" class="active"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="manajemen.php"><i class="fas fa-cog"></i> Manajemen</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Laporan Parkir</h1>
                <p>Analisis dan statistik transaksi parkir</p>
            </div>
            <div style="color: #666;">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label for="start_date"><i class="fas fa-calendar"></i> Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date"><i class="fas fa-calendar"></i> Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="jenis_kendaraan"><i class="fas fa-car"></i> Jenis Kendaraan</label>
                    <select id="jenis_kendaraan" name="jenis_kendaraan">
                        <option value="">Semua Jenis</option>
                        <?php while($kendaraan = mysqli_fetch_assoc($kendaraan_result)): ?>
                            <option value="<?php echo $kendaraan['jenis']; ?>" 
                                <?php echo $jenis_kendaraan == $kendaraan['jenis'] ? 'selected' : ''; ?>>
                                <?php echo $kendaraan['jenis']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter Laporan
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Statistik -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Total Pendapatan</h3>
                <div class="number">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-exchange-alt"></i>
                <h3>Total Transaksi</h3>
                <div class="number"><?php echo number_format($total_transaksi, 0, ',', '.'); ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <h3>Rata-rata Per Hari</h3>
                <div class="number">Rp <?php echo number_format($avg_pendapatan, 0, ',', '.'); ?></div>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="chart-container">
            <h3 style="margin-bottom: 20px; color: var(--dark);">
                <i class="fas fa-chart-bar"></i> Grafik Pendapatan Harian
            </h3>
            <canvas id="revenueChart"></canvas>
        </div>
        
        <!-- Detail Transaksi -->
        <div class="table-container">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: var(--dark);">
                    <i class="fas fa-table"></i> Detail Transaksi
                </h3>
                <div class="export-buttons">
                    <button class="btn btn-success" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-primary" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            
            <?php if (mysqli_num_rows($detail_result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>No. Polisi</th>
                        <th>Jenis Kendaraan</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Lama</th>
                        <th>Biaya</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($detail_result)): ?>
                    <tr>
                        <td><?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['tgl_masuk'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nopol']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['jenis_kendaraan']); ?></td>
                        <td><?php echo $row['jam_masuk']; ?></td>
                        <td><?php echo $row['jam_keluar'] ?: '-'; ?></td>
                        <td><?php echo $row['lama'] ? $row['lama'] . ' Jam' : '-'; ?></td>
                        <td><strong><?php echo $row['biaya'] ? 'Rp ' . number_format($row['biaya'], 0, ',', '.') : '-'; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['petugas']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-inbox" style="font-size: 3em; margin-bottom: 15px; color: #ddd;"></i>
                <h3>Tidak Ada Data Transaksi</h3>
                <p>Tidak ada transaksi pada periode yang dipilih</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Data untuk chart
        const chartData = {
            labels: [
                <?php 
                mysqli_data_seek($chart_result, 0);
                while($chart = mysqli_fetch_assoc($chart_result)) {
                    echo "'" . date('d M', strtotime($chart['tanggal'])) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Pendapatan Harian',
                data: [
                    <?php 
                    mysqli_data_seek($chart_result, 0);
                    while($chart = mysqli_fetch_assoc($chart_result)) {
                        echo $chart['pendapatan'] . ',';
                    }
                    ?>
                ],
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                tension: 0.4
            }]
        };

        // Inisialisasi chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Fungsi export (placeholder)
        function exportToPDF() {
            alert('Fitur export PDF akan segera tersedia!');
            // Implementasi export PDF bisa menggunakan library seperti jsPDF
        }

        function exportToExcel() {
            alert('Fitur export Excel akan segera tersedia!');
            // Implementasi export Excel bisa menggunakan library seperti SheetJS
        }

        // Validasi tanggal
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate > endDate) {
                document.getElementById('end_date').value = this.value;
            }
        });
    </script>
</body>
</html>