<?php
// Pastikan session_start() dipanggil sebelum output apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "parkir_online";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi helper
function formatRupiah($angka) {
    if ($angka === null) return 'Rp 0';
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Cek session dan redirect
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

// Cek jika sudah login
function checkAlreadyLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        redirect('dashboard.php');
    }
}
// Fungsi untuk mendapatkan data transaksi
function getTransaksiData($conn) {
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
    
    if (!$result) {
        die("Error: " . mysqli_error($conn));
    }
    
    return $result;
}
?>