<?php
session_start();
ob_start();

require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isset($_SESSION['uploaded_csv_file']) || !isset($_SESSION['csv_columns'])) {
    header('Location: upload_csv.php');
    exit();
}

$csv_columns = $_SESSION['csv_columns'];

// Veritabanı sütunları (students tablosuna göre)
$database_columns = [
    'first_name' => 'Öğrenci Adı',
    'last_name' => 'Öğrenci Soyadı',
    'tc_no' => 'T.C. Kimlik No',
    'gender' => 'Cinsiyet',
    'birthdate' => 'Doğum Tarihi',
    'guardian_name' => 'Veli Adı',
    'guardian_phone' => 'Veli Telefonu',
    'address' => 'Adres',
    'distance' => 'Mesafe',
    'transportation' => 'Taşıma'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $column_mapping = $_POST['column_mapping'];

    // CSV işleme ve veritabanına ekleme için verileri gönder
    $_SESSION['column_mapping'] = $column_mapping;
    header('Location: process_csv.php');
    exit();
}
ob_end_flush();
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Sütunları Eşleştirin</h2>
    
    <form method="post">
        <?php foreach ($database_columns as $db_column => $db_column_label): ?>
            <div class="mb-3">
                <label for="<?= $db_column ?>" class="form-label"><?= $db_column_label ?>:</label>
                <select name="column_mapping[<?= $db_column ?>]" id="<?= $db_column ?>" class="form-select">
                    <option value="">-- Seçin --</option>
                    <?php foreach ($csv_columns as $index => $csv_column): ?>
                        <option value="<?= $index ?>"><?= htmlspecialchars($csv_column) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-success w-100">Eşleştir</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
