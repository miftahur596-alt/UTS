<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$nama = $_SESSION["nama"] ?? "Administrator";
$inisial = function_exists("mb_substr")
    ? mb_substr($nama, 0, 1, "UTF-8")
    : substr($nama, 0, 1);

// Ambil ringkasan data dari database (dengan fallback aman jika tabel belum siap)
$totalUsers = 0;
$totalProduk = 0;
$totalStok = 0;
$totalPelanggan = 0;

try {
    $totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPelanggan = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'pelanggan'")->fetchColumn();

    $prodStat = $conn->query("SELECT COUNT(*) AS jml_produk, COALESCE(SUM(stok), 0) AS total_stok FROM products")->fetch(PDO::FETCH_ASSOC);
    if ($prodStat) {
        $totalProduk = (int)($prodStat["jml_produk"] ?? 0);
        $totalStok = (int)($prodStat["total_stok"] ?? 0);
    }
} catch (PDOException $e) {
    // Biarkan nilai 0 jika query belum sesuai dengan nama kolom/tabel tertentu
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin | M'Vapor Store</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">ADMIN MANAGEMENT</div>

    <div class="menu">
        <div class="menu-label">MENU UTAMA</div>
        <a href="dashboard.php" class="active">▦ &nbsp; Dashboard</a>

        <div class="menu-label">MANAJEMEN</div>
        <a href="pengguna.php">♙ &nbsp; Pengguna</a>
        <a href="inventaris.php">▤ &nbsp; Inventaris &amp; Upload</a>
        <a href="pelanggan.php">♧ &nbsp; Pelanggan</a>

        <div class="menu-label">INFORMASI</div>
        <a href="#">▥ &nbsp; Laporan</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Dashboard Admin</h2>
            <p>Panel pengelolaan sistem M'Vapor Store</p>
        </div>

        <div class="user-profile">
            <div class="avatar">
                <?= htmlspecialchars(strtoupper($inisial)) ?>
            </div>
            <div class="user-info">
                <strong><?= htmlspecialchars($nama) ?></strong>
                <span>Administrator</span>
            </div>
        </div>
    </header>

    <section class="welcome">
        <small>ADMINISTRATOR PANEL</small>
        <h1>Selamat Datang, <?= htmlspecialchars($nama) ?>!</h1>
        <p>
            Kelola barang, upload foto produk ke etalase, pantau persediaan,
            serta atur pengguna dan laporan sistem melalui panel admin.
        </p>
    </section>

    <h3 class="section-title">Ringkasan Manajemen</h3>

    <section class="stats">
        <div class="stat-card">
            <div class="icon">♙</div>
            <p>Total Pengguna</p>
            <h3><?= $totalUsers ?> Akun</h3>
        </div>

        <div class="stat-card">
            <div class="icon">▤</div>
            <p>Inventaris Barang</p>
            <h3><?= $totalProduk ?> Item (<?= $totalStok ?> Stok)</h3>
        </div>

        <div class="stat-card">
            <div class="icon">♧</div>
            <p>Data Pelanggan</p>
            <h3><?= $totalPelanggan ?> Pelanggan</h3>
        </div>

        <div class="stat-card">
            <div class="icon">▥</div>
            <p>Laporan Sistem</p>
            <h3>Rekapitulasi</h3>
        </div>
    </section>

    <h3 class="section-title">Akses Cepat</h3>

    <section class="quick-grid">
        <a href="inventaris.php" class="quick-card">
            <strong>Upload &amp; Kelola Barang</strong>
            <p>Tambah produk baru, upload gambar ke etalase, edit, dan hapus barang.</p>
        </a>

        <a href="pengguna.php" class="quick-card">
            <strong>Manajemen Pengguna</strong>
            <p>Pengaturan akun dan hak akses pengguna.</p>
        </a>

        <a href="pelanggan.php" class="quick-card">
            <strong>Data Pelanggan</strong>
            <p>Lihat daftar pelanggan, kontak, dan alamat terdaftar.</p>
        </a>
    </section>

    <footer class="footer">
        &copy; <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>