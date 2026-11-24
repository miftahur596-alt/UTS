<?php
session_start();

if (!isset($_SESSION["id_user"]) || $_SESSION["role"] !== "pelanggan") {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$stmt = $conn->prepare(
    "SELECT nama, created_at
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);

$stmt->execute([$_SESSION["id_user"]]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Aktivitas | M'Vapor Store</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">CUSTOMER AREA</div>

    <div class="menu">
        <div class="menu-label">MENU UTAMA</div>
        <a href="dashboard.php">▦ &nbsp; Dashboard</a>
        <a href="etalase.php">▤ &nbsp; Etalase Barang</a>

        <div class="menu-label">AKUN SAYA</div>
        <a href="profil.php">♙ &nbsp; Profil Saya</a>
        <a href="riwayat.php" class="active">◷ &nbsp; Riwayat Aktivitas</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Riwayat Aktivitas</h2>
            <p>Informasi aktivitas akun pelanggan</p>
        </div>
    </header>

    <section class="welcome">
        <small>ACCOUNT HISTORY</small>
        <h1>Riwayat Akun</h1>
        <p>Informasi tanggal pendaftaran akun Anda.</p>
    </section>

    <div class="content-card">
        <h3>Akun Terdaftar</h3>

        <?php if ($user): ?>
            <p>Nama: <?= htmlspecialchars($user["nama"]) ?></p>
            <p>Tanggal pendaftaran: <?= htmlspecialchars($user["created_at"] ?? "-") ?></p>
            <p>Status: Akun terdaftar</p>
        <?php else: ?>
            <p>Informasi akun tidak ditemukan.</p>
        <?php endif; ?>
    </div>

    <footer class="footer">
        © <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>
