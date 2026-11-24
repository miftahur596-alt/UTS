<?php
session_start();

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pelanggan') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stmt = $conn->prepare(
    "SELECT u.nama, u.email, u.created_at, c.no_hp, c.alamat
     FROM users u
     LEFT JOIN customers c ON u.id_user = c.id_user
     WHERE u.id_user = ?
     LIMIT 1"
);

$stmt->execute([$_SESSION['id_user']]);
$user = $stmt->fetch();

if (!$user) {
    exit("Data akun tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Pelanggan | M'Vapor Store</title>
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
        <a href="profil.php" class="active">♙ &nbsp; Profil Saya</a>
        <a href="riwayat.php">◷ &nbsp; Riwayat Aktivitas</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Profil Saya</h2>
            <p>Informasi akun pelanggan</p>
        </div>
    </header>

    <section class="welcome">
        <small>ACCOUNT PROFILE</small>
        <h1><?= htmlspecialchars($user['nama'] ?? '-') ?></h1>
        <p>Berikut adalah informasi akun yang terdaftar pada sistem.</p>
    </section>

    <div class="content-card">
        <h3>Informasi Pribadi</h3>
        <p>Nama lengkap: <?= htmlspecialchars($user['nama'] ?? '-') ?></p>
        <p>Email: <?= htmlspecialchars($user['email'] ?? '-') ?></p>
        <p>Nomor telepon: <?= htmlspecialchars($user['no_hp'] ?? '-') ?></p>
        <p>Alamat: <?= htmlspecialchars($user['alamat'] ?? '-') ?></p>
        <p>Tanggal terdaftar: <?= htmlspecialchars($user['created_at'] ?? '-') ?></p>
    </div>

    <footer class="footer">
        &copy; <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>