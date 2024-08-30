<?php
session_start();
ob_start();

require_once '../includes/config.php';
require_once '../includes/header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $csv_file = $_FILES['csv_file'];

    // Dosya kontrolü
    if ($csv_file['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $csv_file['tmp_name'];
        $filename = basename($csv_file['name']);
        $destination = "/tmp/" . $filename;

        if (move_uploaded_file($tmp_name, $destination)) {
            $_SESSION['uploaded_csv_file'] = $destination;

            // CSV dosyasının başlıklarını al
            if (($handle = fopen($destination, 'r')) !== FALSE) {
                $csv_columns = fgetcsv($handle, 1000, ",");
                $_SESSION['csv_columns'] = $csv_columns;
                fclose($handle);

                // Sütunları eşleştirme sayfasına yönlendir
                header('Location: map_columns.php');
                exit();
            } else {
                echo "Dosya açma hatası!";
            }
        } else {
            echo "Dosya taşınamadı!";
        }
    } else {
        echo "Dosya yüklenirken bir hata oluştu!";
    }
}
ob_end_flush();
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">CSV Dosyası Yükle</h2>
    
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="csv_file" class="form-label">CSV Dosyası Seçin:</label>
            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Yükle</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
