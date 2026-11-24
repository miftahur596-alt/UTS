<?php
require_once __DIR__ . "/config/database.php";

$nama = "Administrator";
$email = "admin@mvapor.local";
$password = password_hash("Admin12345", PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare(
        "INSERT INTO users (nama,email,password,role)
         VALUES (?,?,?,'admin')"
    );

    $stmt->execute([$nama, $email, $password]);

    echo "Akun admin berhasil dibuat.";

} catch (PDOException $e) {
    echo "Akun gagal dibuat atau email sudah digunakan.";
}
