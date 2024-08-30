<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];

        // Önce notları silin
        $stmt = $baglanti->prepare("DELETE FROM notes WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Önce time table silin
        $stmt = $baglanti->prepare("DELETE FROM timetable WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Önce weeklytable table silin
        $stmt = $baglanti->prepare("DELETE FROM weeklytable WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

    // Şimdi öğrenciyi silin
    $stmt = $baglanti->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
} else {
    echo "Geçersiz öğrenci ID'si.";
    exit();
}
