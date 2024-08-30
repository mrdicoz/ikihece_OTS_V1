<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
ob_start(); // Çıktıyı tamponlamak için ob_start() kullanıyoruz

require_once '../includes/config.php';

if (!isset($_SESSION['uploaded_csv_file']) || !isset($_SESSION['column_mapping'])) {
    header('Location: upload_csv.php');
    exit();
}

$uploaded_csv_file = $_SESSION['uploaded_csv_file'];
$column_mapping = $_SESSION['column_mapping'];

// Dosyayı açma ve işlem yapma
if (($handle = fopen($uploaded_csv_file, 'r')) !== FALSE) {
    // İlk satır başlık satırıdır, bunu atlıyoruz
    fgetcsv($handle, 1000, ",");

    // CSV satırlarını okuma
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $data = [];
        foreach ($column_mapping as $db_column => $csv_column_index) {
            $data[$db_column] = $row[$csv_column_index];
        }

        // Doğum tarihini formatlama ve kontrol etme (d.m.Y formatı)
        if (isset($data['birthdate']) && !empty($data['birthdate'])) {
            $date = DateTime::createFromFormat('d.m.Y', $data['birthdate']);
            $data['birthdate'] = $date ? $date->format('Y-m-d') : null;
        } else {
            $data['birthdate'] = null; // Doğum tarihi boş ise null yap
        }

          // Eğer 'first_name' boşsa, bu satırı atla
    if (empty($data['first_name'])) {
        continue; // Bu satırı atla ve bir sonraki satıra geç
    }

        // Veritabanına ekleme işlemi
        $stmt = $baglanti->prepare("
            INSERT INTO students (first_name, last_name, tc_no, gender, birthdate, guardian_name, guardian_phone, address, distance, transportation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt === false) {
            die('Prepare failed: ' . $baglanti->error);
        }

        $stmt->bind_param(
            "ssssssssss",
            $data['first_name'],
            $data['last_name'],
            $data['tc_no'],
            $data['gender'],
            $data['birthdate'],
            $data['guardian_name'],
            $data['guardian_phone'],
            $data['address'],
            $data['distance'],
            $data['transportation']
        );

        if (!$stmt->execute()) {
            die('Execute failed: ' . $stmt->error);
        }
    }

    fclose($handle);

    // Başarılı yönlendirme
    unset($_SESSION['uploaded_csv_file']);
    unset($_SESSION['column_mapping']);
    header('Location: success.php');
    exit();
} else {
    echo "Dosya açılamadı!";
}
ob_end_flush();
?>
