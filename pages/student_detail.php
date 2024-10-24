<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($student_id === 0) {
    echo "Geçersiz öğrenci ID'si.";
    exit();
}

$query = "SELECT * FROM students WHERE id = ?";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "Öğrenci bulunamadı.";
    exit();
}

// Şoför kullanıcılarının göreceği bilgiler
$isDriver = ($_SESSION['role'] == 'driver');

// Varsayılan fotoğraf URL'leri
$default_student_photo = '/assets/images/user.jpg';
$default_guardian_photo = '/assets/images/user.jpg';

// Öğrenci ve veli fotoğrafı kontrolü
$student_photo = !empty($student['student_photo']) ? $student['student_photo'] : $default_student_photo;
$guardian_photo = !empty($student['guardian_photo']) ? $student['guardian_photo'] : $default_guardian_photo;
?>

<?php $pageTitle = "Öğrenci Detayları"; include '../includes/header.php'; ?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <h3>Öğrenci Detayları</h3>
            <div class="card mb-3 shadow-sm">
                <img src="../<?= htmlspecialchars($student_photo) ?>" class="card-img-top img-thumbnail" alt="Öğrenci Fotoğrafı" style="width: 100%; height: auto;">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                    <?php if (!$isDriver): ?>
                        <p class="card-text"><strong>Doğum Tarihi:</strong> <?= htmlspecialchars($student['birthdate']) ?></p>
                        <p class="card-text"><strong>T.C. Kimlik No:</strong> <?= htmlspecialchars($student['tc_no']) ?></p>
                        <p class="card-text"><strong>Cinsiyet:</strong> <?= $student['gender'] == 'male' ? 'Erkek' : 'Kız'; ?></p>
                        <p class="card-text"><strong>Engel Tipi:</strong> <?= htmlspecialchars($student['disability_type']) ?></p>
                        <p class="card-text"><strong>Eğitim Programı:</strong> <?= htmlspecialchars($student['education_program']) ?></p>
                        <p class="card-text"><strong>Doğum Yeri:</strong> <?= htmlspecialchars($student['birthplace']) ?></p>
                        <p class="card-text"><strong>Öğrenci Hakkında:</strong> <?= htmlspecialchars($student['student_info']) ?></p>
                        <?php endif; ?>
                        <p class="card-text"><strong>Adres:</strong> <?= htmlspecialchars($student['address']) ?></p>
                        <p class="card-text "><strong>Cep Telefonu:</strong><a href="tel:+9<?= htmlspecialchars($student['guardian_phone']) ?>" class="link-success link-underline link-underline-opacity-0"><?= htmlspecialchars($student['guardian_phone']) ?></a></p>

                    <p> <a href="<?= htmlspecialchars($student['location']) ?>" target="_blank" class="btn btn-success w-100"><i class="bi bi-geo-alt-fill"></i> Konum</a></p>
                    
                </div>
            </div>
        </div>

        <?php if (!$isDriver): ?>
            <div class="col-md-6">
                <h3>Veli Detayları</h3>
                <div class="card mb-3 shadow-sm">
                    <img src="../<?= htmlspecialchars($guardian_photo) ?>" class="card-img-top img-thumbnail" alt="Veli Fotoğrafı" style="width: 100%; height: auto;">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($student['guardian_name']) ?></h5>
                        <p class="card-text"><strong>Adres:</strong> <?= htmlspecialchars($student['address']) ?></p>
                        <p class="card-text"><strong>Mesafe:</strong> <?= htmlspecialchars($student['distance']) ?></p>
                        <p class="card-text"><strong>Ulaşım:</strong> <?= htmlspecialchars($student['transportation']) ?></p>
                        <p class="card-text"><strong>İkinci iletişim:</strong> <?= htmlspecialchars($student['second_contact_name']) ?></p>
                        <p class="card-text"><strong>İkinci Numara:</strong> <?= htmlspecialchars($student['second_contact_phone']) ?></p>
                    </div>
                </div>

                <!-- Butonlar Mobilde Alt Alta, Masaüstünde Yan Yana -->
                <div class="d-flex flex-column flex-md-row gap-2">
                <?php if (in_array($_SESSION['role'], ['admin', 'secretary'])): ?>
                        <a href="student_edit.php?id=<?= $student_id; ?>" class="btn btn-warning w-100"><i class="bi bi-gear"></i> Düzenle</a>
                        <?php endif; ?>
                <?php if (in_array($_SESSION['role'], ['admin', 'secretary', 'teacher','reporter'])): ?>
                        <a href="student_note.php?id=<?= $student_id; ?>" class="btn btn-secondary w-100"><i class="bi bi-info-circle"></i> Hakkında</a>
                        <a href="view_pdf.php?file=<?= urlencode($student['ram_report']); ?>" target="_blank" class="btn btn-info w-100"><i class="bi bi-file-earmark-pdf"></i> RAM Raporu</a>
                <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
