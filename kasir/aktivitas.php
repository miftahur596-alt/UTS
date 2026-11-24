
<?php
session_start();

// Pemeriksaan login
if (!isset($_SESSION["id_user"])) {
    header("Location: ../login.php");
    exit;
}

// Pemeriksaan hak akses
if (($_SESSION["role"] ?? "") !== "kasir") {
    http_response_code(403);
    exit("Akses ditolak. Halaman ini khusus untuk kasir.");
}

require_once __DIR__ . "/../config/database.php";

$nama = $_SESSION["nama"] ?? "Kasir";

// Mengambil seluruh riwayat aktivitas pengguna
try {
    $stmt = $conn->prepare(
        "SELECT
            sm.id_movement,
            sm.id_product,
            sm.jenis,
            sm.jumlah,
            sm.keterangan,
            sm.tanggal,
            p.nama_produk,
            u.nama AS nama_pengguna,
            u.role AS role_pengguna
         FROM stock_movements sm
         LEFT JOIN products p
            ON sm.id_product = p.id_product
         LEFT JOIN users u
            ON sm.id_user = u.id_user
         ORDER BY sm.tanggal DESC"
    );

    $stmt->execute();
    $aktivitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $aktivitas = [];
    $error = "Riwayat aktivitas gagal dimuat. Periksa struktur tabel database.";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Aktivitas Kasir | M'Vapor Store</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .content-card {
            background: #202020;
            padding: 25px;
            border-radius: 10px;
            margin-top: 25px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #383838;
            font-size: 12px;
        }

        th {
            color: #d4af37;
            font-weight: 700;
            white-space: nowrap;
        }

        td {
            color: #ddd;
        }

        .jenis {
            color: #d4af37;
            font-weight: 700;
        }

        .role-admin {
            color: #d4af37;
            font-weight: 700;
        }

        .role-kasir {
            color: #7db8ff;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #aaa;
        }

        .activity-info {
            margin-bottom: 20px;
            color: #aaa;
            font-size: 13px;
            line-height: 1.7;
        }

        .activity-count {
            color: #d4af37;
            font-weight: 700;
        }
    </style>

</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">M'VAPOR</div>

    <div class="brand-subtitle">
        CASHIER MANAGEMENT
    </div>

    <div class="menu">

        <div class="menu-label">MENU UTAMA</div>

        <a href="dashboard.php">
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

        <a href="aktivitas.php" class="active">
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
            <h2>Aktivitas Kasir</h2>
            <p>Riwayat perubahan inventaris</p>
        </div>

        <div class="user-profile">

            <div class="avatar">
                <?= htmlspecialchars(strtoupper(substr($nama, 0, 1))) ?>
            </div>

            <div class="user-info">
                <strong>
                    <?= htmlspecialchars($nama) ?>
                </strong>

                <span>Kasir</span>
            </div>

        </div>

    </header>


    <!-- INFORMASI AKTIVITAS -->
    <section class="welcome">

        <small>ACTIVITY MONITORING</small>

        <h1>Riwayat Aktivitas</h1>

        <p>
            Halaman ini menampilkan riwayat perubahan inventaris
            yang tercatat dalam sistem, termasuk aktivitas
            yang dilakukan oleh Admin dan Kasir.
        </p>

    </section>


    <!-- DAFTAR AKTIVITAS -->
    <h3 class="section-title">
        Catatan Aktivitas Inventaris
    </h3>

    <section class="content-card">

        <p class="activity-info">
            Total aktivitas tercatat:
            <span class="activity-count">
                <?= count($aktivitas) ?> aktivitas
            </span>
        </p>

        <?php if (isset($error)): ?>

            <p class="empty">
                <?= htmlspecialchars($error) ?>
            </p>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Barang</th>
                            <th>Jenis Aktivitas</th>
                            <th>Jumlah</th>
                            <th>Pelaksana</th>
                            <th>Hak Akses</th>
                            <th>Keterangan</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($aktivitas) > 0): ?>

                            <?php foreach ($aktivitas as $i => $item): ?>

                                <tr>

                                    <td>
                                        <?= $i + 1 ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $item["nama_produk"] ?? "-"
                                        ) ?>
                                    </td>

                                    <td class="jenis">
                                        <?= htmlspecialchars(
                                            $item["jenis"] ?? "-"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (int)$item["jumlah"]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $item["nama_pengguna"] ?? "-"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php
                                        $role = $item["role_pengguna"] ?? "-";
                                        ?>

                                        <span class="<?= $role === 'admin' ? 'role-admin' : ($role === 'kasir' ? 'role-kasir' : '') ?>">
                                            <?= htmlspecialchars(ucfirst($role)) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $item["keterangan"] ?? "-"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $item["tanggal"] ?? "-"
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="empty">
                                    Belum ada aktivitas inventaris yang tercatat.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>


    <!-- FOOTER -->
    <footer class="footer">
        © <?= date("Y") ?> M'Vapor Store Management System.
    </footer>

</main>

</body>
</html>
