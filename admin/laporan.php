<?php
ob_start();
session_start();

if (!isset($_SESSION["id_user"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$nama = $_SESSION["nama"] ?? "Administrator";
$inisial = function_exists("mb_substr")
    ? mb_substr($nama, 0, 1, "UTF-8")
    : substr($nama, 0, 1);

$filterKategori = $_GET["kategori"] ?? "";
$statusStok     = $_GET["status_stok"] ?? "";

$totalUsers           = 0;
$totalPelanggan       = 0;
$totalAdminKasir      = 0;
$totalProduk          = 0;
$totalStok            = 0;
$totalNilaiInventaris = 0.0;
$daftarKategori       = [];
$daftarLaporan        = [];
$pesanError           = "";

try {
    $totalUsers     = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPelanggan = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'pelanggan'")->fetchColumn();
    $totalAdminKasir = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'kasir')")->fetchColumn();

    $adaTabelKategori = (bool)$conn->query("SHOW TABLES LIKE 'categories'")->fetchColumn();
    if ($adaTabelKategori) {
        $daftarKategori = $conn->query(
            "SELECT id_category, nama_kategori FROM categories ORDER BY nama_kategori ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    $adaTabelProduk = (bool)$conn->query("SHOW TABLES LIKE 'products'")->fetchColumn();
    if ($adaTabelProduk) {
        if ($adaTabelKategori) {
            $sql = "SELECT p.id_product, p.kode_produk, p.nama_produk,
                           p.harga, p.stok, c.nama_kategori
                    FROM products p
                    LEFT JOIN categories c ON p.id_category = c.id_category
                    WHERE 1=1";
        } else {
            $sql = "SELECT id_product, kode_produk, nama_produk,
                           harga, stok, '-' AS nama_kategori
                    FROM products
                    WHERE 1=1";
        }

        $params = [];

        if ($filterKategori !== "") {
            $sql .= " AND p.id_category = ?";
            $params[] = (int)$filterKategori;
        }

        if ($statusStok === "tersedia") {
            $sql .= " AND p.stok > 0";
        } elseif ($statusStok === "habis") {
            $sql .= " AND p.stok <= 0";
        }

        $sql .= " ORDER BY p.nama_produk ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $daftarLaporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalProduk = count($daftarLaporan);
        foreach ($daftarLaporan as $row) {
            $stokItem  = (int)($row["stok"] ?? 0);
            $hargaItem = (float)($row["harga"] ?? 0);
            $totalStok += $stokItem;
            $totalNilaiInventaris += ($stokItem * $hargaItem);
        }
    }
} catch (PDOException $e) {
    $pesanError = "Gagal memuat laporan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Sistem | Admin M'Vapor</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .alert-error {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #482727;
            color: #f0a0a0;
            border: 1px solid #7a3c3c;
        }
        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
            align-items: center;
        }
        .filter-bar select {
            background: #1b1b1b;
            color: #eee;
            border: 1px solid #383838;
            border-radius: 7px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 12px;
        }
        .btn-gold {
            padding: 12px 18px;
            border: 0;
            border-radius: 7px;
            background: #d4af37;
            color: #151515;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .btn-outline {
            padding: 11px 16px;
            border-radius: 7px;
            border: 1px solid #d4af37;
            background: transparent;
            color: #d4af37;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .table-wrap {
            background: #1b1b1b;
            border: 1px solid #333;
            border-radius: 10px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #2b2b2b;
            vertical-align: middle;
        }
        th {
            color: #d4af37;
            font-size: 11px;
            text-transform: uppercase;
            background: #161616;
        }
        @media print {
            .sidebar, .filter-bar, .btn-outline, .btn-gold {
                display: none !important;
            }
            body, .main, .table-wrap, th, td {
                background: #fff !important;
                color: #000 !important;
                border-color: #ccc !important;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">ADMIN MANAGEMENT</div>

    <div class="menu">
        <div class="menu-label">MENU UTAMA</div>
        <a href="dashboard.php">▦ &nbsp; Dashboard</a>

        <div class="menu-label">MANAJEMEN</div>
        <a href="pengguna.php">♙ &nbsp; Pengguna</a>
        <a href="inventaris.php">▤ &nbsp; Inventaris</a>
        <a href="pelanggan.php">♧ &nbsp; Pelanggan</a>

        <div class="menu-label">INFORMASI</div>
        <a href="laporan.php" class="active">▥ &nbsp; Laporan</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Laporan Sistem &amp; Inventaris</h2>
            <p>Rekapitulasi pengguna, pelanggan, stok barang, dan estimasi nilai persediaan</p>
        </div>

        <div class="user-profile">
            <div class="avatar"><?= htmlspecialchars(strtoupper($inisial)) ?></div>
            <div class="user-info">
                <strong><?= htmlspecialchars($nama) ?></strong>
                <span>Administrator</span>
            </div>
        </div>
    </header>

    <?php if ($pesanError !== ""): ?>
        <div class="alert-error"><?= htmlspecialchars($pesanError) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <div class="icon">♙</div>
            <p>Total Pengguna</p>
            <h3><?= $totalUsers ?> Akun</h3>
        </div>

        <div class="stat-card">
            <div class="icon">♧</div>
            <p>Pelanggan / Staff</p>
            <h3><?= $totalPelanggan ?> Plg &bull; <?= $totalAdminKasir ?> Staff</h3>
        </div>

        <div class="stat-card">
            <div class="icon">▤</div>
            <p>Total Stok Terfilter</p>
            <h3><?= $totalStok ?> Unit (<?= $totalProduk ?> Item)</h3>
        </div>

        <div class="stat-card">
            <div class="icon">▥</div>
            <p>Estimasi Nilai Stok</p>
            <h3>Rp <?= number_format($totalNilaiInventaris, 0, ",", ".") ?></h3>
        </div>
    </section>

    <h3 class="section-title">Filter &amp; Cetak Laporan Inventaris</h3>

    <form method="GET" class="filter-bar">
        <select name="kategori">
            <option value="">Semua Kategori</option>
            <?php foreach ($daftarKategori as $kat): ?>
                <option
                    value="<?= (int)$kat["id_category"] ?>"
                    <?= (string)$filterKategori === (string)$kat["id_category"] ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($kat["nama_kategori"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status_stok">
            <option value="">Semua Status Stok</option>
            <option value="tersedia" <?= $statusStok === "tersedia" ? "selected" : "" ?>>Stok Tersedia (&gt; 0)</option>
            <option value="habis" <?= $statusStok === "habis" ? "selected" : "" ?>>Stok Habis (0)</option>
        </select>

        <button type="submit" class="btn-gold">TERAPKAN FILTER</button>
        <button type="button" class="btn-outline" onclick="window.print()">CETAK / SIMPAN PDF</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Harga Satuan</th>
                    <th>Stok</th>
                    <th>Subtotal Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftarLaporan) > 0): ?>
                    <?php foreach ($daftarLaporan as $idx => $item): ?>
                        <?php
                        $stokItem  = (int)($item["stok"] ?? 0);
                        $hargaItem = (float)($item["harga"] ?? 0);
                        $subtotal  = $stokItem * $hargaItem;
                        ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($item["kode_produk"] ?? "-") ?></td>
                            <td><strong><?= htmlspecialchars($item["nama_produk"] ?? "-") ?></strong></td>
                            <td><?= htmlspecialchars($item["nama_kategori"] ?? "-") ?></td>
                            <td>Rp <?= number_format($hargaItem, 0, ",", ".") ?></td>
                            <td><?= $stokItem ?></td>
                            <td>Rp <?= number_format($subtotal, 0, ",", ".") ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#999;">Tidak ada data laporan sesuai filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer class="footer">
        &copy; <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>
<?php ob_end_flush(); ?>
