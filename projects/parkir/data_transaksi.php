<?php
// Session start di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

// Cek login
checkLogin();

// Query data transaksi dengan JOIN yang benar
$query = "SELECT 
            t.id,
            t.nopol,
            k.jenis as jenis_kendaraan,
            t.tgl_masuk,
            t.jam_masuk,
            t.tgl_keluar,
            t.jam_keluar,
            t.lama,
            t.biaya,
            p.nama as nama_petugas,
            CASE 
                WHEN t.tgl_keluar IS NULL THEN 'PARKIR'
                ELSE 'SELESAI'
            END as status
          FROM transaksi t 
          JOIN kendaraan k ON t.id_kendaraan = k.id 
          JOIN petugas p ON t.id_petugas = p.id 
          ORDER BY t.id DESC";

$result = mysqli_query($conn, $query);

// Cek jika query error
if (!$result) {
    die("Error dalam query: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Transaksi - Sistem Parkir Online</title>
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
        
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-header h2 {
            color: var(--dark);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 10px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        
        .status-parkir {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-selesai {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-print {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: black;
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
        
        @media (max-width: 768px) {
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
            <li><a href="data_transaksi.php" class="active"><i class="fas fa-history"></i> Data Transaksi</a></li>
            <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li><a href="manajemen.php"><i class="fas fa-cog"></i> Manajemen</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Data Transaksi Parkir</h1>
                <p>Riwayat semua transaksi parkir</p>
            </div>
            <div style="color: #666;">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
            </div>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-table"></i> Daftar Transaksi</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari no. polisi atau ID...">
                    <button onclick="searchTable()"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
            
            <?php 
            // Cek jika ada data
            if (mysqli_num_rows($result) > 0): 
            ?>
            <table id="transactionsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>No. Polisi</th>
                        <th>Jenis Kendaraan</th>
                        <th>Tanggal Masuk</th>
                        <th>Jam Masuk</th>
                        <th>Tanggal Keluar</th>
                        <th>Jam Keluar</th>
                        <th>Lama (Jam)</th>
                        <th>Biaya</th>
                        <th>Status</th>
                        <th>Petugas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($row = mysqli_fetch_assoc($result)): 
                        // Format data
                        $id_formatted = str_pad($row['id'], 6, '0', STR_PAD_LEFT);
                        $tgl_masuk = date('d/m/Y', strtotime($row['tgl_masuk']));
                        $tgl_keluar = $row['tgl_keluar'] ? date('d/m/Y', strtotime($row['tgl_keluar'])) : '-';
                        $jam_keluar = $row['jam_keluar'] ? $row['jam_keluar'] : '-';
                        $lama = $row['lama'] ? $row['lama'] . ' Jam' : '-';
                        $biaya = $row['biaya'] ? formatRupiah($row['biaya']) : '-';
                        $status_class = $row['status'] == 'PARKIR' ? 'status-parkir' : 'status-selesai';
                    ?>
                    <tr>
                        <td><?php echo $id_formatted; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nopol']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['jenis_kendaraan']); ?></td>
                        <td><?php echo $tgl_masuk; ?></td>
                        <td><?php echo $row['jam_masuk']; ?></td>
                        <td><?php echo $tgl_keluar; ?></td>
                        <td><?php echo $jam_keluar; ?></td>
                        <td><?php echo $lama; ?></td>
                        <td><strong><?php echo $biaya; ?></strong></td>
                        <td>
                            <span class="status <?php echo $status_class; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['nama_petugas']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if($row['status'] == 'SELESAI'): ?>
                                <a href="cetak_tiket.php?id=<?php echo $row['id']; ?>" class="btn btn-print" target="_blank">
                                    <i class="fas fa-print"></i> Print
                                </a>
                                <?php else: ?>
                                <button class="btn btn-edit" onclick="prosesKeluar(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-sign-out-alt"></i> Keluar
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>Belum Ada Data Transaksi</h3>
                <p>Silakan lakukan transaksi parkir terlebih dahulu</p>
                <a href="transaksi.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 8px;">
                    <i class="fas fa-plus"></i> Tambah Transaksi
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fungsi pencarian
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('transactionsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const tdId = tr[i].getElementsByTagName('td')[0];
                const tdNopol = tr[i].getElementsByTagName('td')[1];
                let found = false;
                
                if (tdId || tdNopol) {
                    const txtValueId = tdId.textContent || tdId.innerText;
                    const txtValueNopol = tdNopol.textContent || tdNopol.innerText;
                    
                    if (txtValueId.toUpperCase().indexOf(filter) > -1 || 
                        txtValueNopol.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
        
        // Enter key untuk search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTable();
            }
        });
        
        // Fungsi proses keluar
        function prosesKeluar(id) {
            if (confirm('Apakah Anda yakin ingin memproses transaksi keluar untuk ID ' + id + '?')) {
                window.location.href = 'transaksi.php?action=keluar&id=' + id;
            }
        }
        
        // Auto refresh setiap 30 detik untuk data real-time
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>