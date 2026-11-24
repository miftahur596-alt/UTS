<?php
session_start();

if (!isset($_SESSION["id_user"]) || ($_SESSION["role"] ?? "") !== "pelanggan") {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$nama = $_SESSION["nama"] ?? "Pelanggan";
$cari = trim($_GET["cari"] ?? "");
$kategori = $_GET["kategori"] ?? "";

$sql = "SELECT p.id_product, p.kode_produk, p.nama_produk,
               p.deskripsi, p.harga, p.stok, p.gambar,
               c.nama_kategori
        FROM products p
        LEFT JOIN categories c ON p.id_category = c.id_category
        WHERE 1=1";

$params = [];

if ($cari !== "") {
    $sql .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

if ($kategori !== "") {
    $sql .= " AND p.id_category = ?";
    $params[] = $kategori;
}

$sql .= " ORDER BY p.nama_produk ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$produk = $stmt->fetchAll();

$kategoriStmt = $conn->query(
    "SELECT id_category, nama_kategori FROM categories ORDER BY nama_kategori"
);
$daftarKategori = $kategoriStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etalase Barang | M'Vapor Store</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .filter-form input,
        .filter-form select {
            background: #1b1b1b;
            color: #eee;
            border: 1px solid #383838;
            border-radius: 7px;
            padding: 13px;
            font-family: inherit;
            font-size: 12px;
        }

        .filter-form input {
            flex: 1;
            min-width: 180px;
        }

        .filter-form button {
            padding: 13px 20px;
            border: 0;
            border-radius: 7px;
            background: #d4af37;
            color: #151515;
            font-weight: 800;
            cursor: pointer;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .product-card {
            background: #1b1b1b;
            border: 1px solid #333;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .product-image {
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: #292929;
        }

        .product-info {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .product-info h3 {
            font-size: 13px;
            margin: 6px 0 6px;
        }

        .product-price {
            font-size: 13px;
            font-weight: 800;
            color: #d4af37;
            margin-bottom: 8px;
        }

        .product-info p {
            font-size: 11px;
            color: #999;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .product-category {
            color: #d4af37;
            font-size: 10px;
        }

        .product-stock {
            display: inline-block;
            width: fit-content;
            font-size: 10px;
            padding: 6px 9px;
            border-radius: 5px;
            background: #183b2a;
            color: #8ee0a8;
        }

        .product-stock.empty {
            background: #482727;
            color: #f0a0a0;
        }

        .detail-link {
            display: block;
            margin-top: auto;
            padding: 11px;
            border: 1px solid #d4af37;
            border-radius: 6px;
            text-align: center;
            color: #d4af37;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
        }

        .detail-link:hover {
            background: #d4af37;
            color: #151515;
        }

        .empty-state {
            background: #1b1b1b;
            padding: 30px;
            border-radius: 10px;
            color: #aaa;
            text-align: center;
            grid-column: 1 / -1;
        }

        @media(max-width: 900px) {
            .product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media(max-width: 600px) {
            .product-grid {
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
            <h2>Etalase Barang</h2>
            <p>Lihat informasi katalog dan ketersediaan stok</p>
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
        <small>PRODUCT CATALOG</small>
        <h1>Etalase Barang</h1>
        <p>Temukan informasi barang dan periksa ketersediaannya.</p>
    </section>

    <form method="GET" class="filter-form">
        <input
            type="text"
            name="cari"
            placeholder="Cari nama atau kode barang..."
            value="<?= htmlspecialchars($cari) ?>"
        >

        <select name="kategori">
            <option value="">Semua Kategori</option>
            <?php foreach ($daftarKategori as $kat): ?>
                <option
                    value="<?= (int)$kat["id_category"] ?>"
                    <?= (string)$kategori === (string)$kat["id_category"] ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($kat["nama_kategori"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">CARI BARANG</button>
    </form>

    <h3 class="section-title">
        Daftar Barang (<?= count($produk) ?>)
    </h3>

    <section class="product-grid">
        <?php if (count($produk) > 0): ?>
            <?php foreach ($produk as $item): ?>
                <article class="product-card">
                    <?php
                    $namaFileGambar = basename(trim($item["gambar"] ?? ""));
                    $pathFisikGambar = __DIR__ . "/../assets/images/" . $namaFileGambar;
                    $urlGambar = "../assets/images/" . rawurlencode($namaFileGambar);
                    ?>

                    <?php if ($namaFileGambar !== "" && is_file($pathFisikGambar)): ?>
                        <img
                            src="<?= htmlspecialchars($urlGambar) ?>"
                            class="product-image"
                            alt="<?= htmlspecialchars($item["nama_produk"]) ?>"
                        >
                    <?php else: ?>
                        <div class="product-image"></div>
                    <?php endif; ?>

                    <div class="product-info">
                        <span class="product-category">
                            <?= htmlspecialchars($item["nama_kategori"] ?? "Tanpa kategori") ?>
                        </span>

                        <h3><?= htmlspecialchars($item["nama_produk"]) ?></h3>

                        <div class="product-price">
                            Rp <?= number_format((float)($item["harga"] ?? 0), 0, ",", ".") ?>
                        </div>

                        <p>Kode: <?= htmlspecialchars($item["kode_produk"]) ?></p>

                        <p><?= htmlspecialchars($item["deskripsi"] ?? "Tidak ada deskripsi.") ?></p>

                        <?php if ((int)$item["stok"] > 0): ?>
                            <span class="product-stock">
                                Tersedia: <?= (int)$item["stok"] ?>
                            </span>
                        <?php else: ?>
                            <span class="product-stock empty">
                                Stok tidak tersedia
                            </span>
                        <?php endif; ?>

                        <a
                            class="detail-link"
                            href="detail-barang.php?id=<?= (int)$item["id_product"] ?>"
                        >
                            LIHAT DETAIL &amp; BELI
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                Barang tidak ditemukan.
            </div>
        <?php endif; ?>
    </section>

    <footer class="footer">
        &copy; <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>