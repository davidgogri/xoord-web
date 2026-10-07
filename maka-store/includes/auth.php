<?php
session_start();
include 'db.php';

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['rol'] === 'admin';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}

function checkAuth($rol_requerido = null) {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../login.php");
        exit;
    }

    if ($rol_requerido && $_SESSION['rol'] !== $rol_requerido) {
        die("No tienes permiso para acceder a esta página.");
    }
}
?>