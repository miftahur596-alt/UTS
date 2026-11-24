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

$pesanSukses = "";
$pesanError  = "";
$daftarUsers = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "ubah_role") {
    $idTarget     = (int)($_POST["id_user"] ?? 0);
    $roleBaru     = trim($_POST["role"] ?? "");
    $allowedRoles = ["admin", "kasir", "pelanggan"];

    if ($idTarget <= 0 || !in_array($roleBaru, $allowedRoles, true)) {
        $pesanError = "Data role tidak valid.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id_user = ?");
            $stmt->execute([$roleBaru, $idTarget]);

            if ($idTarget === (int)$_SESSION["id_user"]) {
                $_SESSION["role"] = $roleBaru;
                if ($roleBaru !== "admin") {
                    header("Location: ../login.php");
                    exit;
                }
            }

            $pesanSukses = "Role pengguna berhasil diperbarui.";
        } catch (PDOException $e) {
            $pesanError = "Gagal mengubah role: " . $e->getMessage();
        }
    }
}

try {
    $daftarUsers = $conn->query(
        "SELECT id_user, nama, email, role, created_at
         FROM users
         ORDER BY id_user DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pesanError = "Gagal mengambil data pengguna: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Pengguna | Admin M'Vapor</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .alert-success {
            background: #183b2a;
            color: #8ee0a8;
            border: 1px solid #2b6649;
        }
        .alert-error {
            background: #482727;
            color: #f0a0a0;
            border: 1px solid #7a3c3c;
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
        .role-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .role-form select {
            background: #141414;
            color: #eee;
            border: 1px solid #383838;
            border-radius: 6px;
            padding: 7px 10px;
            font-family: inherit;
            font-size: 11px;
        }
        .btn-gold-sm {
            padding: 7px 12px;
            border: 0;
            border-radius: 6px;
            background: #d4af37;
            color: #151515;
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
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
        <a href="pengguna.php" class="active">♙ &nbsp; Pengguna</a>
        <a href="inventaris.php">▤ &nbsp; Inventaris</a>
        <a href="pelanggan.php">♧ &nbsp; Pelanggan</a>

        <div class="menu-label">INFORMASI</div>
        <a href="laporan.php">▥ &nbsp; Laporan</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Manajemen Pengguna</h2>
            <p>Kelola daftar akun dan hak akses (role) pengguna sistem</p>
        </div>

        <div class="user-profile">
            <div class="avatar"><?= htmlspecialchars(strtoupper($inisial)) ?></div>
            <div class="user-info">
                <strong><?= htmlspecialchars($nama) ?></strong>
                <span>Administrator</span>
            </div>
        </div>
    </header>

    <?php if ($pesanSukses !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesanSukses) ?></div>
    <?php endif; ?>

    <?php if ($pesanError !== ""): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesanError) ?></div>
    <?php endif; ?>

    <h3 class="section-title">Daftar Pengguna (<?= count($daftarUsers) ?>)</h3>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Terdaftar</th>
                    <th>Ubah Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftarUsers) > 0): ?>
                    <?php foreach ($daftarUsers as $u): ?>
                        <tr>
                            <td><?= (int)$u["id_user"] ?></td>
                            <td><strong><?= htmlspecialchars($u["nama"] ?? "-") ?></strong></td>
                            <td><?= htmlspecialchars($u["email"] ?? "-") ?></td>
                            <td><?= htmlspecialchars($u["role"] ?? "-") ?></td>
                            <td><?= htmlspecialchars($u["created_at"] ?? "-") ?></td>
                            <td>
                                <form method="POST" class="role-form">
                                    <input type="hidden" name="aksi" value="ubah_role">
                                    <input type="hidden" name="id_user" value="<?= (int)$u["id_user"] ?>">
                                    <select name="role">
                                        <option value="pelanggan" <?= ($u["role"] ?? "") === "pelanggan" ? "selected" : "" ?>>pelanggan</option>
                                        <option value="kasir" <?= ($u["role"] ?? "") === "kasir" ? "selected" : "" ?>>kasir</option>
                                        <option value="admin" <?= ($u["role"] ?? "") === "admin" ? "selected" : "" ?>>admin</option>
                                    </select>
                                    <button type="submit" class="btn-gold-sm">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#999;">Belum ada data pengguna.</td>
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