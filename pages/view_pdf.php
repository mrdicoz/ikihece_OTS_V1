<?php
if (isset($_GET['file'])) {
    $file = '../raporlar/' . $_GET['file'];

    if (file_exists($file)) {
        // PDF'nin tarayıcıda açılması için gerekli başlıklar
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));

        // PDF'yi çıktı olarak gönder
        readfile($file);
        exit();
    } else {
        echo "Dosya bulunamadı.";
    }
} else {
    echo "Geçersiz dosya.";
}
?>
