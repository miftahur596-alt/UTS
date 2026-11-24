<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

function wajibLogin()
{
    if (!isset($_SESSION['id_user'])) {
        header("Location: ../login.php");
        exit;
    }
}

function wajibRole($role)
{
    wajibLogin();

    if ($_SESSION['role'] !== $role) {
        header("Location: ../index.php");
        exit;
    }
}

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
