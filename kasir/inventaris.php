<?php
session_start();

if (!isset($_SESSION["id_user"])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "kasir") {
    http_response_code(403);
    exit("Akses ditolak.");
}

require_once __DIR__ . "/../config/database.php";

$nama = $_SESSION["nama"] ?? "Kasir";
$cari = trim($_GET["cari"] ?? "");

try {
    $sql = "SELECT
                p.id_product,
                p.kode_produk,
                p.nama_produk,
                p.stok,
                c.nama_kategori
            FROM products p
            LEFT JOIN categories c
                ON p.id_category = c.id_category";

    if ($cari !== "") {
        $sql .= " WHERE p.nama_produk LIKE :cari
                  OR p.kode_produk LIKE :cari";
    }

    $sql .= " ORDER BY p.id_product DESC";

    $stmt = $conn->prepare($sql);

    if ($cari !== "") {
        $stmt->bindValue(":cari", "%" . $cari . "%");
    }

    $stmt->execute();
    $produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $produk = [];
    $error = "Data inventaris gagal dimuat.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventaris | M'Vapor Store</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .content-card {
            background: #202020;
            padding: 25px;
            border-radius: 10px;
            margin-top: 25px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            background: #292929;
            border: 1px solid #444;
            border-radius: 6px;
            color: white;
        }

        .search-box button {
            padding: 12px 20px;
            background: #d4af37;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #383838;
            font-size: 13px;
        }

        th {
            color: #d4af37;
        }

        .stock {
            color: #d4af37;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #aaa;
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">M'VAPOR</div>
    <div class="brand-subtitle">CASHIER MANAGEMENT</div>

    <div class="menu">
        <div class="menu-label">MENU UTAMA</div>
        <a href="dashboard.php">▦ &nbsp; Dashboard</a>

        <div class="menu-label">OPERASIONAL</div>
        <a href="inventaris.php" class="active">▤ &nbsp; Informasi Inventaris</a>
        <a href="pelanggan.php">♧ &nbsp; Data Pelanggan</a>

        <div class="menu-label">INFORMASI</div>
        <a href="aktivitas.php">▥ &nbsp; Aktivitas Kasir</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Informasi Inventaris</h2>
            <p>Informasi data persediaan barang</p>
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

    <h3 class="section-title">Daftar Inventaris</h3>

    <section class="content-card">

        <form method="GET" class="search-box">
            <input
                type="text"
                name="cari"
                placeholder="Cari nama atau kode barang..."
                value="<?= htmlspecialchars($cari) ?>"
            >
            <button type="submit">Cari</button>
        </form>

        <?php if (isset($error)): ?>
            <p class="empty"><?= htmlspecialchars($error) ?></p>
        <?php else: ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($produk) > 0): ?>
                        <?php foreach ($produk as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($item["kode_produk"] ?? "-") ?></td>
                                <td><?= htmlspecialchars($item["nama_produk"]) ?></td>
                                <td><?= htmlspecialchars($item["nama_kategori"] ?? "-") ?></td>
                                <td class="stock">
                                    <?= number_format((int)$item["stok"]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Data inventaris belum tersedia.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

    </section>

    <footer class="footer">
        © <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>
</body>
</html>
