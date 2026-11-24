<?php
session_start();

// Pemeriksaan login
if (!isset($_SESSION["id_user"])) {
    header("Location: ../login.php");
    exit;
}

// Pemeriksaan hak akses
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "kasir") {
    http_response_code(403);
    exit("Akses ditolak. Halaman ini khusus untuk kasir.");
}

$nama = $_SESSION["nama"] ?? "Kasir";
$email = $_SESSION["email"] ?? "";

// Mengambil data dari database
require_once __DIR__ . "/../config/database.php";

try {
    // Jumlah produk
    $stmtProduk = $conn->query("SELECT COUNT(*) FROM products");
    $jumlahProduk = $stmtProduk->fetchColumn();

    // Jumlah produk dengan stok tersedia
    $stmtStok = $conn->query(
        "SELECT COUNT(*) FROM products WHERE stok > 0"
    );
    $jumlahStok = $stmtStok->fetchColumn();

    // Jumlah pelanggan
    $stmtPelanggan = $conn->query(
        "SELECT COUNT(*) FROM users WHERE role = 'pelanggan'"
    );
    $jumlahPelanggan = $stmtPelanggan->fetchColumn();

} catch (PDOException $e) {
    $jumlahProduk = 0;
    $jumlahStok = 0;
    $jumlahPelanggan = 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard Kasir | M'Vapor Store</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .menu a,
        .quick-card {
            transition: 0.3s ease;
        }

        .menu a:hover {
            background: rgba(212, 175, 55, 0.12);
            color: #d4af37;
            transform: translateX(4px);
        }

        .quick-card {
            display: block;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .quick-card:hover {
            border-color: #d4af37;
            transform: translateY(-4px);
        }

        .quick-card strong {
            color: #d4af37;
        }

        .stat-card h3 {
            font-size: 21px;
        }

        .user-info span {
            display: block;
            font-size: 11px;
            color: #d4af37;
            margin-top: 4px;
        }

        .welcome {
            position: relative;
            overflow: hidden;
        }

        .welcome small {
            color: #d4af37;
            letter-spacing: 2px;
        }

        .footer {
            margin-top: 40px;
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">CASHIER MANAGEMENT</div>

    <div class="menu">

        <div class="menu-label">MENU UTAMA</div>

        <a href="dashboard.php" class="active">
            ▦ &nbsp; Dashboard
        </a>

        <div class="menu-label">OPERASIONAL</div>

        <a href="inventaris.php">
            ▤ &nbsp; Informasi Inventaris
        </a>

        <a href="pelanggan.php">
            ♧ &nbsp; Data Pelanggan
        </a>

        <div class="menu-label">INFORMASI</div>

        <a href="aktivitas.php">
            ▥ &nbsp; Aktivitas Kasir
        </a>

    </div>

    <a href="../logout.php" class="logout">
        ↪ Logout
    </a>

</aside>


<!-- KONTEN UTAMA -->
<main class="main">

    <!-- HEADER -->
    <header class="topbar">

        <div>
            <h2>Dashboard Kasir</h2>
            <p>Panel informasi operasional</p>
        </div>

        <div class="user-profile">

            <div class="avatar">
                <?= htmlspecialchars(strtoupper(substr($nama, 0, 1))) ?>
            </div>

            <div class="user-info">
                <strong><?= htmlspecialchars($nama) ?></strong>
                <span>Kasir</span>
            </div>

        </div>

    </header>


    <!-- WELCOME -->
    <section class="welcome">

        <small>CASHIER PANEL</small>

        <h1>
            Selamat Bekerja, <?= htmlspecialchars($nama) ?>!
        </h1>

        <p>
            Selamat datang di sistem informasi M'Vapor Store.
            Melalui halaman ini, Anda dapat melihat informasi
            inventaris, data pelanggan, dan aktivitas operasional.
        </p>

    </section>


    <!-- INFORMASI -->
    <h3 class="section-title">Informasi Operasional</h3>

    <section class="stats">

        <div class="stat-card">
            <div class="icon">▤</div>
            <p>Total Produk</p>
            <h3><?= number_format((int)$jumlahProduk) ?></h3>
        </div>

        <div class="stat-card">
            <div class="icon">◈</div>
            <p>Produk Tersedia</p>
            <h3><?= number_format((int)$jumlahStok) ?></h3>
        </div>

        <div class="stat-card">
            <div class="icon">♧</div>
            <p>Total Pelanggan</p>
            <h3><?= number_format((int)$jumlahPelanggan) ?></h3>
        </div>

        <div class="stat-card">
            <div class="icon">◷</div>
            <p>Status Akun</p>
            <h3>Aktif</h3>
        </div>

    </section>


    <!-- MENU OPERASIONAL -->
    <h3 class="section-title">Menu Operasional</h3>

    <section class="quick-grid">

        <a href="inventaris.php" class="quick-card">
            <strong>Informasi Inventaris</strong>
            <p>
                Melihat daftar produk, informasi barang,
                dan jumlah persediaan.
            </p>
        </a>

        <a href="pelanggan.php" class="quick-card">
            <strong>Data Pelanggan</strong>
            <p>
                Melihat informasi dan data pelanggan
                yang telah terdaftar.
            </p>
        </a>

        <a href="aktivitas.php" class="quick-card">
            <strong>Aktivitas Kasir</strong>
            <p>
                Melihat informasi aktivitas
                operasional akun kasir.
            </p>
        </a>

    </section>


    <!-- INFORMASI AKUN -->
    <h3 class="section-title">Informasi Akun</h3>

    <section class="stat-card">

        <p>Nama Kasir</p>
        <h3><?= htmlspecialchars($nama) ?></h3>

        <p style="margin-top: 12px;">Email</p>
        <strong><?= htmlspecialchars($email) ?></strong>

        <p style="margin-top: 12px;">Hak Akses</p>
        <strong>Kasir</strong>

    </section>


    <!-- FOOTER -->
    <footer class="footer">
        © <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>

</body>
</html>
