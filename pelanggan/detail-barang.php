<?php
session_start();

if (!isset($_SESSION["id_user"]) || $_SESSION["role"] !== "pelanggan") {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: etalase.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT p.id_product, p.kode_produk, p.nama_produk,
            p.deskripsi, p.stok, p.gambar, c.nama_kategori
     FROM products p
     LEFT JOIN categories c ON p.id_category = c.id_category
     WHERE p.id_product = ?
     LIMIT 1"
);

$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    header("Location: etalase.php");
    exit;
}

$gambar = trim($produk["gambar"] ?? "");
$pathGambar = __DIR__ . "/../assets/images/" . $gambar;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Barang | M'Vapor Store</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .detail-box {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
            gap: 25px;
            background: #1b1b1b;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }

        .detail-image {
            width: 100%;
            min-height: 260px;
            max-height: 350px;
            object-fit: cover;
            background: #292929;
            border-radius: 8px;
        }

        .detail-info h1 {
            font-size: 22px;
            margin: 12px 0;
        }

        .detail-info p {
            color: #aaa;
            font-size: 12px;
            line-height: 1.8;
            margin-bottom: 14px;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #d4af37;
            text-decoration: none;
            font-size: 12px;
        }

        @media(max-width: 700px) {
            .detail-box {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">CUSTOMER AREA</div>

    <div class="menu">
        <div class="menu-label">MENU UTAMA</div>
        <a href="dashboard.php">▦ &nbsp; Dashboard</a>
        <a href="etalase.php" class="active">▤ &nbsp; Etalase Barang</a>

        <div class="menu-label">AKUN SAYA</div>
        <a href="profil.php">♙ &nbsp; Profil Saya</a>
        <a href="riwayat.php">◷ &nbsp; Riwayat Aktivitas</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Detail Barang</h2>
            <p>Informasi barang pada katalog</p>
        </div>
        <div class="user-profile">
            <div class="avatar">
                <?= strtoupper(substr($_SESSION["nama"], 0, 1)) ?>
            </div>
            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION["nama"]) ?></strong>
                <span>Pelanggan</span>
            </div>
        </div>
    </header>

    <section class="detail-box">

        <?php if ($gambar !== "" && is_file($pathGambar)): ?>
            <img
                src="../assets/images/<?= rawurlencode($gambar) ?>"
                class="detail-image"
                alt="<?= htmlspecialchars($produk["nama_produk"]) ?>"
            >
        <?php else: ?>
            <div class="detail-image"></div>
        <?php endif; ?>

        <div class="detail-info">

            <span style="color:#d4af37;font-size:11px;">
                <?= htmlspecialchars($produk["nama_kategori"] ?? "Tanpa kategori") ?>
            </span>

            <h1><?= htmlspecialchars($produk["nama_produk"]) ?></h1>

            <p>
                Kode barang:
                <?= htmlspecialchars($produk["kode_produk"]) ?>
            </p>

            <p>
                Deskripsi:
                <?= nl2br(htmlspecialchars($produk["deskripsi"] ?? "Tidak ada deskripsi.")) ?>
            </p>

            <?php if ((int)$produk["stok"] > 0): ?>
                <span class="product-stock">
                    Stok tersedia: <?= (int)$produk["stok"] ?>
                </span>
            <?php else: ?>
                <span class="product-stock empty">
                    Stok tidak tersedia
                </span>
            <?php endif; ?>

            <br>

            <a href="etalase.php" class="back-link">
                ← Kembali ke Etalase
            </a>

        </div>

    </section>

    <footer class="footer">
        © <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>
