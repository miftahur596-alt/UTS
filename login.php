<?php
session_start();

require_once __DIR__ . "/config/database.php";

// Pengalihan jika pengguna sudah login
if (isset($_SESSION["id_user"], $_SESSION["role"])) {

    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION["role"] === "kasir") {
        header("Location: kasir/dashboard.php");
        exit;
    } elseif ($_SESSION["role"] === "pelanggan") {
        header("Location: pelanggan/dashboard.php");
        exit;
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email dan password wajib diisi.";
    } else {

        try {
            $stmt = $conn->prepare(
                "SELECT id_user, nama, email, password, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, (string)$user["password"])) {

                $role = strtolower(trim($user["role"]));

                if ($role === "admin") {
                    $halaman = "admin/dashboard.php";
                } elseif ($role === "kasir") {
                    $halaman = "kasir/dashboard.php";
                } elseif ($role === "pelanggan") {
                    $halaman = "pelanggan/dashboard.php";
                } else {
                    $error = "Role akun tidak dikenali.";
                    $halaman = "";
                }

                if ($halaman !== "") {

                    if (is_file(__DIR__ . "/" . $halaman)) {

                        session_regenerate_id(true);

                        $_SESSION["id_user"] = $user["id_user"];
                        $_SESSION["nama"] = $user["nama"];
                        $_SESSION["email"] = $user["email"];
                        $_SESSION["role"] = $role;

                        header("Location: " . $halaman);
                        exit;

                    } else {
                        $error = "Halaman dashboard tidak ditemukan: " . $halaman;
                    }
                }

            } else {
                $error = "Email atau password tidak sesuai.";
            }

        } catch (PDOException $e) {
            $error = "Terjadi kesalahan saat mengakses database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | M'Vapor Store</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="auth-page">

<div class="auth-container">

    <div class="auth-brand">
        <img src="assets/images/logo.png" alt="M'Vapor Store">
        <h1>M'VAPOR STORE</h1>
        <p>MANAGEMENT SYSTEM</p>
    </div>

    <div class="auth-form">

        <div class="auth-heading">
            <span>WELCOME BACK</span>
            <h2>Sign In</h2>
            <p>Silakan masuk menggunakan akun Anda.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Masukkan email Anda"
                value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Masukkan password"
                required
            >

            <button type="submit">
                SIGN IN TO SYSTEM &rarr;
            </button>

        </form>

        <div class="signup-link">
            <p>Belum memiliki akun?</p>
            <a href="register.php">SIGN UP</a>
        </div>

        <div class="auth-back">
            <a href="index.php">
                &larr; Kembali ke halaman utama
            </a>
        </div>

    </div>

    <div class="auth-footer">
        Universitas Teknologi Digital Indonesia &bull; Teknik Komputer.
    </div>

</div>

</body>
</html>
