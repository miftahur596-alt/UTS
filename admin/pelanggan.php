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

$cari = trim($_GET["cari"] ?? "");
$daftarPelanggan = [];
$pesanError = "";

try {
    // Cek apakah tabel customers tersedia; jika tidak, tetap tampilkan data dari users
    $adaTabelCustomers = (bool)$conn->query("SHOW TABLES LIKE 'customers'")->fetchColumn();

    if ($adaTabelCustomers) {
        $sql = "SELECT u.id_user, u.nama, u.email, u.created_at, c.no_hp, c.alamat
                FROM users u
                LEFT JOIN customers c ON u.id_user = c.id_user
                WHERE u.role = 'pelanggan'";
        $params = [];

        if ($cari !== "") {
            $sql .= " AND (u.nama LIKE ? OR u.email LIKE ? OR c.no_hp LIKE ?)";
            $params[] = "%$cari%";
            $params[] = "%$cari%";
            $params[] = "%$cari%";
        }
    } else {
        $sql = "SELECT id_user, nama, email, created_at, '-' AS no_hp, '-' AS alamat
                FROM users
                WHERE role = 'pelanggan'";
        $params = [];

        if ($cari !== "") {
            $sql .= " AND (nama LIKE ? OR email LIKE ?)";
            $params[] = "%$cari%";
            $params[] = "%$cari%";
        }
    }

    $sql .= " ORDER BY u.id_user DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $daftarPelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pesanError = "Gagal memuat data pelanggan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pelanggan | Admin M'Vapor</title>
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
        .search-form {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .search-form input {
            flex: 1;
            min-width: 220px;
            background: #1b1b1b;
            color: #eee;
            border: 1px solid #383838;
            border-radius: 7px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 12px;
        }
        .search-form button {
            padding: 12px 20px;
            border: 0;
            border-radius: 7px;
            background: #d4af37;
            color: #151515;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
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
        <a href="pelanggan.php" class="active">♧ &nbsp; Pelanggan</a>

        <div class="menu-label">INFORMASI</div>
        <a href="laporan.php">▥ &nbsp; Laporan</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Data Pelanggan</h2>
            <p>Lihat informasi akun, kontak, dan alamat pelanggan terdaftar</p>
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

    <form method="GET" class="search-form">
        <input
            type="text"
            name="cari"
            placeholder="Cari nama, email, atau nomor HP pelanggan..."
            value="<?= htmlspecialchars($cari) ?>"
        >
        <button type="submit">CARI PELANGGAN</button>
    </form>

    <h3 class="section-title">Daftar Pelanggan (<?= count($daftarPelanggan) ?>)</h3>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Terdaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftarPelanggan) > 0): ?>
                    <?php foreach ($daftarPelanggan as $plg): ?>
                        <tr>
                            <td><?= (int)$plg["id_user"] ?></td>
                            <td><strong><?= htmlspecialchars($plg["nama"] ?? "-") ?></strong></td>
                            <td><?= htmlspecialchars($plg["email"] ?? "-") ?></td>
                            <td><?= htmlspecialchars($plg["no_hp"] ?? "-") ?></td>
                            <td><?= htmlspecialchars($plg["alamat"] ?? "-") ?></td>
                            <td><?= htmlspecialchars($plg["created_at"] ?? "-") ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#999;">Belum ada data pelanggan.</td>
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