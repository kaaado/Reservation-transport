<?php
session_start();
// Redirect based on session
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'client') {
        header("Location: client/dashboard.php");
    } elseif ($_SESSION['role'] === 'transporter') {
        header("Location: transporter/dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: auth/login.php");
    }
} else {
    header("Location: auth/login.php");
}
exit();
