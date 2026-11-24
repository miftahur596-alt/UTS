
<?php
session_start();

if (!isset($_SESSION["id_user"]) || $_SESSION["role"] !== "pelanggan") {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];

$stmt = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProduk = $stmt->fetch()["total"];

$stmt = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stok > 0");
$produkTersedia = $stmt->fetch()["total"];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Pelanggan | M'Vapor Store</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">CUSTOMER AREA</div>

    <div class="menu">
        <div class="menu-label">MENU UTAMA</div>
        <a href="dashboard.php" class="active">▦ &nbsp; Dashboard</a>
        <a href="etalase.php">▤ &nbsp; Etalase Barang</a>

        <div class="menu-label">AKUN SAYA</div>
        <a href="profil.php">♙ &nbsp; Profil Saya</a>
        <a href="riwayat.php">◷ &nbsp; Riwayat Aktivitas</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Dashboard Pelanggan</h2>
            <p>Informasi akun dan katalog barang</p>
        </div>

        <div class="user-profile">
            <div class="avatar">
                <?= strtoupper(substr($nama, 0, 1)) ?>
            </div>
            <div class="user-info">
                <strong><?= htmlspecialchars($nama) ?></strong>
                <span>Pelanggan</span>
            </div>
        </div>
    </header>

    <section class="welcome">
        <small>CUSTOMER AREA</small>
        <h1>Selamat Datang, <?= htmlspecialchars($nama) ?>!</h1>
        <p>
            Selamat datang di M'Vapor Store. Silakan lihat etalase
            untuk mengetahui informasi barang dan ketersediaan stok.
        </p>
        <br>
        <a href="etalase.php" class="customer-button">LIHAT ETALASE →</a>
    </section>

    <h3 class="section-title">Informasi Etalase</h3>

    <section class="stats">
        <div class="stat-card">
            <div class="icon">▤</div>
            <p>Total Barang</p>
            <h3><?= (int)$totalProduk ?></h3>
        </div>

        <div class="stat-card">
            <div class="icon">✓</div>
            <p>Barang Tersedia</p>
            <h3><?= (int)$produkTersedia ?></h3>
        </div>

        <div class="stat-card">
            <div class="icon">♙</div>
            <p>Nama Pelanggan</p>
            <h3><?= htmlspecialchars($nama) ?></h3>
        </div>

        <div class="stat-card">
            <div class="icon">✉</div>
            <p>Email Akun</p>
            <h3><?= htmlspecialchars($email) ?></h3>
        </div>
    </section>

    <h3 class="section-title">Menu Pelanggan</h3>

    <section class="quick-grid">
        <a href="etalase.php" class="quick-card">
            <strong>Etalase Barang</strong>
            <p>Lihat katalog dan informasi ketersediaan barang.</p>
        </a>

        <a href="profil.php" class="quick-card">
            <strong>Profil Saya</strong>
            <p>Lihat informasi akun pelanggan.</p>
        </a>

        <a href="riwayat.php" class="quick-card">
            <strong>Riwayat Aktivitas</strong>
            <p>Lihat informasi aktivitas akun.</p>
        </a>
    </section>

    <footer class="footer">
        © <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>