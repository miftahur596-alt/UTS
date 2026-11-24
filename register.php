
<?php
require_once __DIR__ . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $no_hp = trim($_POST["no_hp"] ?? "");

    if ($nama === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {

        $error = "Data tidak valid. Password minimal 8 karakter.";

    } else {

        try {
            // Periksa apakah email sudah terdaftar
            $cek = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
            $cek->execute([$email]);

            if ($cek->rowCount() > 0) {

                $error = "Email sudah terdaftar. Silakan gunakan email lain.";

            } else {

                $conn->beginTransaction();

                // Simpan akun pengguna
                $stmt = $conn->prepare(
                    "INSERT INTO users (nama, email, password, role)
                     VALUES (?, ?, ?, 'pelanggan')"
                );

                $stmt->execute([
                    $nama,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                $id_user = $conn->lastInsertId();

                // Simpan data pelanggan
                $stmt = $conn->prepare(
                    "INSERT INTO customers (id_user, no_hp)
                     VALUES (?, ?)"
                );

                $stmt->execute([$id_user, $no_hp]);

                $conn->commit();

                // Kembali ke halaman login
                header("Location: login.php");
                exit;
            }

        } catch (PDOException $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = "Registrasi gagal. Periksa kembali koneksi dan struktur database.";
        }
    }
}

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Registrasi - M'Vapor Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">

<div class="login-card">

    <h2 class="brand text-center mb-2">M'Vapor</h2>

    <p class="text-secondary text-center mb-4">
        Buat akun pelanggan
    </p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <input
            class="form-control mb-3"
            type="text"
            name="nama"
            placeholder="Nama lengkap"
            value="<?= h($_POST["nama"] ?? "") ?>"
            required
        >

        <input
            class="form-control mb-3"
            type="email"
            name="email"
            placeholder="Email"
            value="<?= h($_POST["email"] ?? "") ?>"
            required
        >

        <input
            class="form-control mb-3"
            type="text"
            name="no_hp"
            placeholder="Nomor telepon"
            value="<?= h($_POST["no_hp"] ?? "") ?>"
        >

        <input
            class="form-control mb-3"
            type="password"
            name="password"
            placeholder="Password minimal 8 karakter"
            minlength="8"
            required
        >

        <button type="submit" class="btn btn-dark w-100">
            Daftar
        </button>

    </form>

    <p class="text-center mt-3">
        Sudah memiliki akun?
        <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>
