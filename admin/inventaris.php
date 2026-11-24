<?php
ob_start();
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
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
$folderUpload = __DIR__ . "/../assets/images/";

if (!is_dir($folderUpload)) {
    @mkdir($folderUpload, 0755, true);
}

function prosesUploadGambar(string $fieldName, string $folderTujuan, string &$errorMsg): ?string
{
    if (empty($_FILES[$fieldName]["name"])) {
        return null;
    }

    if (($_FILES[$fieldName]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errorMsg = "Terjadi kesalahan saat mengupload gambar.";
        return null;
    }

    if (($_FILES[$fieldName]["size"] ?? 0) > 20 * 1024 * 1024) {
        $errorMsg = "Ukuran gambar maksimal 20 MB.";
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$fieldName]["tmp_name"]);

    $allowedMime = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
    ];

    if (!isset($allowedMime[$mime])) {
        $errorMsg = "Format gambar harus JPG, PNG, atau WEBP.";
        return null;
    }

    $namaFileBaru = "vapor_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $allowedMime[$mime];
    $pathTujuan = rtrim($folderTujuan, "/\\") . DIRECTORY_SEPARATOR . $namaFileBaru;

    if (!move_uploaded_file($_FILES[$fieldName]["tmp_name"], $pathTujuan)) {
        $errorMsg = "Gagal menyimpan file gambar ke folder tujuan.";
        return null;
    }

    return $namaFileBaru;
}

function hapusFileGambar(?string $namaFile, string $folderTujuan): void
{
    $bersih = basename(trim((string)$namaFile));
    if ($bersih === "") {
        return;
    }
    $path = rtrim($folderTujuan, "/\\") . DIRECTORY_SEPARATOR . $bersih;
    if (is_file($path)) {
        @unlink($path);
    }
}

$daftarKategori = [];
try {
    $kategoriStmt = $conn->query(
        "SELECT id_category, nama_kategori FROM categories ORDER BY nama_kategori ASC"
    );
    $daftarKategori = $kategoriStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pesanError = "Gagal memuat kategori: " . $e->getMessage();
}

// Proses aksi POST admin (tambah, edit, hapus)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";

    // 1. TAMBAH BARANG
    if ($aksi === "tambah") {
        $kodeProduk = trim($_POST["kode_produk"] ?? "");
        $namaProduk = trim($_POST["nama_produk"] ?? "");
        $idKategori = trim($_POST["id_category"] ?? "");
        $harga      = (float)($_POST["harga"] ?? 0);
        $stok       = (int)($_POST["stok"] ?? 0);
        $deskripsi  = trim($_POST["deskripsi"] ?? "");
        $namaGambar = null;

        if ($kodeProduk === "" || $namaProduk === "") {
            $pesanError = "Kode barang dan nama barang wajib diisi.";
        } elseif ($harga < 0 || $stok < 0) {
            $pesanError = "Harga dan stok tidak boleh bernilai negatif.";
        }

        if ($pesanError === "") {
            $namaGambar = prosesUploadGambar("gambar", $folderUpload, $pesanError);
        }

        if ($pesanError === "") {
            try {
                $sqlInsert = "INSERT INTO products
                              (kode_produk, nama_produk, id_category, harga, stok, deskripsi, gambar)
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = $conn->prepare($sqlInsert);
                $stmtInsert->execute([
                    $kodeProduk,
                    $namaProduk,
                    $idKategori !== "" ? (int)$idKategori : null,
                    $harga,
                    $stok,
                    $deskripsi !== "" ? $deskripsi : null,
                    $namaGambar,
                ]);

                $pesanSukses = "Barang berhasil ditambahkan ke inventaris dan etalase.";
            } catch (PDOException $e) {
                hapusFileGambar($namaGambar, $folderUpload);
                $pesanError = "Gagal menyimpan barang. Pastikan kode barang tidak duplikat.";
            }
        }
    }

    // 2. EDIT BARANG
    if ($aksi === "edit") {
        $idProduct  = (int)($_POST["id_product"] ?? 0);
        $kodeProduk = trim($_POST["kode_produk"] ?? "");
        $namaProduk = trim($_POST["nama_produk"] ?? "");
        $idKategori = trim($_POST["id_category"] ?? "");
        $harga      = (float)($_POST["harga"] ?? 0);
        $stok       = (int)($_POST["stok"] ?? 0);
        $deskripsi  = trim($_POST["deskripsi"] ?? "");

        try {
            $cekStmt = $conn->prepare("SELECT gambar FROM products WHERE id_product = ? LIMIT 1");
            $cekStmt->execute([$idProduct]);
            $produkLama = $cekStmt->fetch(PDO::FETCH_ASSOC);

            if (!$produkLama) {
                $pesanError = "Data barang tidak ditemukan.";
            } elseif ($kodeProduk === "" || $namaProduk === "") {
                $pesanError = "Kode barang dan nama barang wajib diisi.";
            } elseif ($harga < 0 || $stok < 0) {
                $pesanError = "Harga dan stok tidak boleh bernilai negatif.";
            }

            $gambarLama = $produkLama["gambar"] ?? null;
            $gambarFinal = $gambarLama;
            $uploadBaru = null;

            if ($pesanError === "" && !empty($_FILES["gambar"]["name"])) {
                $uploadBaru = prosesUploadGambar("gambar", $folderUpload, $pesanError);
                if ($pesanError === "" && $uploadBaru !== null) {
                    $gambarFinal = $uploadBaru;
                }
            }

            if ($pesanError === "") {
                $sqlUpdate = "UPDATE products
                              SET kode_produk = ?, nama_produk = ?, id_category = ?,
                                  harga = ?, stok = ?, deskripsi = ?, gambar = ?
                              WHERE id_product = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    $kodeProduk,
                    $namaProduk,
                    $idKategori !== "" ? (int)$idKategori : null,
                    $harga,
                    $stok,
                    $deskripsi !== "" ? $deskripsi : null,
                    $gambarFinal,
                    $idProduct,
                ]);

                if ($uploadBaru !== null && $gambarLama !== $uploadBaru) {
                    hapusFileGambar($gambarLama, $folderUpload);
                }

                $pesanSukses = "Data barang berhasil diperbarui.";
            } else {
                hapusFileGambar($uploadBaru, $folderUpload);
            }
        } catch (PDOException $e) {
            $pesanError = "Gagal memperbarui barang: " . $e->getMessage();
        }
    }

    // 3. HAPUS BARANG
    if ($aksi === "hapus") {
        $idProduct = (int)($_POST["id_product"] ?? 0);
        try {
            $cekStmt = $conn->prepare("SELECT gambar FROM products WHERE id_product = ? LIMIT 1");
            $cekStmt->execute([$idProduct]);
            $produkHapus = $cekStmt->fetch(PDO::FETCH_ASSOC);

            if ($produkHapus) {
                $delStmt = $conn->prepare("DELETE FROM products WHERE id_product = ?");
                $delStmt->execute([$idProduct]);
                hapusFileGambar($produkHapus["gambar"] ?? null, $folderUpload);
                $pesanSukses = "Barang dan file gambarnya berhasil dihapus.";
            } else {
                $pesanError = "Barang tidak ditemukan.";
            }
        } catch (PDOException $e) {
            $pesanError = "Gagal menghapus barang: " . $e->getMessage();
        }
    }
}

$dataEdit = null;
if (isset($_GET["edit"])) {
    $idEdit = (int)$_GET["edit"];
    try {
        $editStmt = $conn->prepare("SELECT * FROM products WHERE id_product = ? LIMIT 1");
        $editStmt->execute([$idEdit]);
        $dataEdit = $editStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        $pesanError = "Gagal memuat data edit: " . $e->getMessage();
    }
}

$daftarProduk = [];
try {
    $sqlProduk = "SELECT p.id_product, p.kode_produk, p.nama_produk,
                         p.deskripsi, p.harga, p.stok, p.gambar,
                         c.nama_kategori
                  FROM products p
                  LEFT JOIN categories c ON p.id_category = c.id_category
                  ORDER BY p.id_product DESC";
    $produkStmt = $conn->query($sqlProduk);
    $daftarProduk = $produkStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pesanError = "Gagal memuat daftar barang: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Inventaris | M'Vapor Store</title>
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

        .form-card {
            background: #1b1b1b;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: #d4af37;
            letter-spacing: 0.4px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background: #141414;
            color: #eee;
            border: 1px solid #383838;
            border-radius: 7px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 12px;
        }

        .form-group textarea {
            min-height: 85px;
            resize: vertical;
        }

        .btn-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .btn-gold {
            padding: 13px 22px;
            border: 0;
            border-radius: 7px;
            background: #d4af37;
            color: #151515;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-gold:hover {
            opacity: 0.92;
        }

        .btn-cancel {
            padding: 12px 18px;
            border-radius: 7px;
            border: 1px solid #555;
            color: #ddd;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
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

        .thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 7px;
            background: #292929;
            display: block;
        }

        .aksi-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-edit {
            padding: 7px 12px;
            border-radius: 6px;
            border: 1px solid #d4af37;
            color: #d4af37;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-del {
            padding: 7px 12px;
            border-radius: 6px;
            border: 1px solid #a34343;
            background: #482727;
            color: #f4aaaa;
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
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
        <a href="inventaris.php" class="active">▤ &nbsp; Inventaris</a>
        <a href="pelanggan.php">♧ &nbsp; Pelanggan</a>

        <div class="menu-label">INFORMASI</div>
        <a href="laporan.php">▥ &nbsp; Laporan</a>
    </div>

    <a href="../logout.php" class="logout">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h2>Manajemen Inventaris</h2>
            <p>Tambah, edit, upload gambar produk, dan hapus barang inventaris</p>
        </div>

        <div class="user-profile">
            <div class="avatar">
                <?= htmlspecialchars(strtoupper($inisial)) ?>
            </div>
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

    <h3 class="section-title">
        <?= $dataEdit ? "Edit Barang #" . (int)$dataEdit["id_product"] : "Tambah Barang Baru" ?>
    </h3>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="<?= $dataEdit ? "edit" : "tambah" ?>">
            <?php if ($dataEdit): ?>
                <input type="hidden" name="id_product" value="<?= (int)$dataEdit["id_product"] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="kode_produk">KODE BARANG</label>
                    <input
                        type="text"
                        id="kode_produk"
                        name="kode_produk"
                        value="<?= htmlspecialchars($dataEdit["kode_produk"] ?? "") ?>"
                        placeholder="Contoh: VPR-001"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="nama_produk">NAMA BARANG</label>
                    <input
                        type="text"
                        id="nama_produk"
                        name="nama_produk"
                        value="<?= htmlspecialchars($dataEdit["nama_produk"] ?? "") ?>"
                        placeholder="Contoh: Pod Mod Kit 80W"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="id_category">KATEGORI</label>
                    <select id="id_category" name="id_category">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($daftarKategori as $kat): ?>
                            <option
                                value="<?= (int)$kat["id_category"] ?>"
                                <?= (string)($dataEdit["id_category"] ?? "") === (string)$kat["id_category"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($kat["nama_kategori"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="harga">HARGA (RP)</label>
                    <input
                        type="number"
                        id="harga"
                        name="harga"
                        min="0"
                        step="100"
                        value="<?= htmlspecialchars((string)($dataEdit["harga"] ?? "")) ?>"
                        placeholder="Contoh: 250000"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="stok">STOK</label>
                    <input
                        type="number"
                        id="stok"
                        name="stok"
                        min="0"
                        value="<?= htmlspecialchars((string)($dataEdit["stok"] ?? "")) ?>"
                        placeholder="Contoh: 15"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="gambar">
                        <?= $dataEdit ? "GANTI GAMBAR (KOSONGKAN JIKA TETAP)" : "GAMBAR PRODUK (JPG, PNG, WEBP - MAX 2MB)" ?>
                    </label>
                    <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="form-group full">
                    <label for="deskripsi">DESKRIPSI BARANG</label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        placeholder="Tulis spesifikasi atau keterangan produk..."
                    ><?= htmlspecialchars($dataEdit["deskripsi"] ?? "") ?></textarea>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn-gold">
                    <?= $dataEdit ? "SIMPAN PERUBAHAN" : "SIMPAN &amp; UPLOAD BARANG" ?>
                </button>
                <?php if ($dataEdit): ?>
                    <a href="inventaris.php" class="btn-cancel">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h3 class="section-title">Daftar Barang Tersimpan (<?= count($daftarProduk) ?>)</h3>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daftarProduk) > 0): ?>
                    <?php foreach ($daftarProduk as $prod): ?>
                        <?php
                        $fileImg = basename(trim($prod["gambar"] ?? ""));
                        $fisikImg = $folderUpload . $fileImg;
                        $urlImg = "../assets/images/" . rawurlencode($fileImg);
                        ?>
                        <tr>
                            <td>
                                <?php if ($fileImg !== "" && is_file($fisikImg)): ?>
                                    <img src="<?= htmlspecialchars($urlImg) ?>" class="thumb" alt="<?= htmlspecialchars($prod["nama_produk"]) ?>">
                                <?php else: ?>
                                    <div class="thumb"></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($prod["kode_produk"] ?? "-") ?></td>
                            <td><strong><?= htmlspecialchars($prod["nama_produk"] ?? "-") ?></strong></td>
                            <td><?= htmlspecialchars($prod["nama_kategori"] ?? "-") ?></td>
                            <td>Rp <?= number_format((float)($prod["harga"] ?? 0), 0, ",", ".") ?></td>
                            <td><?= (int)($prod["stok"] ?? 0) ?></td>
                            <td>
                                <div class="aksi-group">
                                    <a class="btn-edit" href="inventaris.php?edit=<?= (int)$prod["id_product"] ?>">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Hapus barang ini beserta gambarnya?');">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_product" value="<?= (int)$prod["id_product"] ?>">
                                        <button type="submit" class="btn-del">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#999;">Belum ada barang yang tersimpan.</td>
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