<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['print_id'])) {
    header("Location: transaksi.php");
    exit();
}

$id_transaksi = $_SESSION['print_id'];
$query = "SELECT t.*, k.jenis, p.nama as petugas 
          FROM transaksi t 
          JOIN kendaraan k ON t.id_kendaraan = k.id 
          JOIN petugas p ON t.id_petugas = p.id 
          WHERE t.id = '$id_transaksi'";
$result = mysqli_query($conn, $query);
$transaksi = mysqli_fetch_assoc($result);

unset($_SESSION['print_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Tiket Parkir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', monospace;
        }
        
        body {
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .ticket {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 350px;
            text-align: center;
            border: 2px dashed #333;
        }
        
        .ticket-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .ticket-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .ticket-info {
            margin: 20px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .info-label {
            font-weight: bold;
            text-align: left;
        }
        
        .info-value {
            text-align: right;
        }
        
        .total {
            background: #333;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .barcode {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            font-family: 'Libre Barcode 128', cursive;
            font-size: 36px;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .ticket {
                box-shadow: none;
                border: 1px solid #000;
                width: 100%;
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #333;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1>TIKET PARKIR</h1>
            <p>SISTEM PARKIR ONLINE</p>
        </div>
        
        <div class="ticket-info">
            <div class="info-row">
                <span class="info-label">ID Transaksi:</span>
                <span class="info-value"><?php echo str_pad($transaksi['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">No. Polisi:</span>
                <span class="info-value"><?php echo $transaksi['nopol']; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Jenis Kendaraan:</span>
                <span class="info-value"><?php echo $transaksi['jenis']; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Tanggal Masuk:</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($transaksi['tgl_masuk'])); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Jam Masuk:</span>
                <span class="info-value"><?php echo $transaksi['jam_masuk']; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Tanggal Keluar:</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($transaksi['tgl_keluar'])); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Jam Keluar:</span>
                <span class="info-value"><?php echo $transaksi['jam_keluar']; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Lama Parkir:</span>
                <span class="info-value"><?php echo $transaksi['lama']; ?> Jam</span>
            </div>
        </div>
        
        <div class="total">
            TOTAL BIAYA: Rp <?php echo number_format($transaksi['biaya'], 0, ',', '.'); ?>
        </div>
        
        <div class="barcode">
            *<?php echo str_pad($transaksi['id'], 6, '0', STR_PAD_LEFT); ?>*
        </div>
        
        <div class="footer">
            <p>Terima kasih atas kunjungan Anda</p>
            <p>Petugas: <?php echo $transaksi['petugas']; ?></p>
            <p><?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <button class="print-btn no-print" onclick="window.print()">Cetak Tiket</button>
        <a href="transaksi.php" class="no-print" style="display: block; margin-top: 10px; color: #333;">Kembali ke Transaksi</a>
    </div>
</body>
</html>