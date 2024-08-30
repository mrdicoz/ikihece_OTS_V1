<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Eğer rol 'driver' ise 'service.php' sayfasına yönlendir
if ($role == 'driver') {
    header("Location: service.php");
    exit();
}

// Eğer rol 'teacher' ise öğretmenin id'si ile 'weeklytable_detail.php' sayfasına yönlendir
if ($role == 'teacher') {
    $teacher_id = $_SESSION['user_id'];
    header("Location: weeklytable_detail.php?teacher_id=" . $teacher_id);
    exit();
}

// Sayfalama ayarları
$students_per_page = 25;  // Her sayfada kaç öğrenci gösterilecek
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;  // Mevcut sayfa numarası
$offset = ($page - 1) * $students_per_page;  // SQL sorgusunda kullanılacak offset

// Toplam öğrenci sayısını al
$total_students_query = "SELECT COUNT(*) AS total FROM students";
$total_students_result = mysqli_query($baglanti, $total_students_query);
$total_students_row = mysqli_fetch_assoc($total_students_result);
$total_students = $total_students_row['total'];  // Toplam öğrenci sayısı

// Toplam sayfa sayısını hesapla
$total_pages = ceil($total_students / $students_per_page);

// Belirli sayfa için öğrenci kayıtlarını al
$query = "SELECT id, student_photo, first_name, last_name, guardian_photo, guardian_name, guardian_phone 
          FROM students 
          LIMIT $offset, $students_per_page";
$result = mysqli_query($baglanti, $query);

function getStudentPhoto($photoPath) {
    if (empty($photoPath) || !file_exists($photoPath)) {
        return '/assets/images/user.jpg';  // Varsayılan fotoğraf
    }
    return $photoPath;
}

function formatPhoneNumberForWhatsApp($phone) {
    // Telefon numarasından parantezleri, boşlukları ve diğer karakterleri kaldır
    return preg_replace('/[^\d]/', '', $phone);
}
?>


<?php $pageTitle = "Ana Sayfa"; include '../includes/header.php'; ?>
<h3>Hoşgeldiniz, <?php echo htmlspecialchars(ucfirst($_SESSION['first_name']) . " " . ucfirst($_SESSION['last_name'])); ?></h3>

<div class="table-responsive">
    <table class="table table-striped table-light align-middle table-hover">
        <thead>
            <tr>
                <th class="col-1">Fotoğraf</th>
                <th class="col-2">Adı</th>
                <th class="col-2">Soyadı</th>
                <th class="col-1 d-none d-lg-table-cell">Veli</th>
                <th class="col-2 d-none d-lg-table-cell">Veli Adı</th>
                <th class="col-2 d-none d-lg-table-cell">Telefon</th>
                <th class="col-2" style="text-align:right;">İşlemler</th> <!-- İşlemler sütunu her boyutta görünür -->
            </tr>
        </thead>
        <tbody class="table-group-divider">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td class="col-1"><img src="<?php echo htmlspecialchars(getStudentPhoto($row['student_photo'])); ?>" alt="Öğrenci Fotoğrafı" width="50" height="50" class="img-thumbnail"></td>
                    <td class="col-2"><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td class="col-2"><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td class="col-1 d-none d-lg-table-cell"><img src="<?php echo htmlspecialchars(getStudentPhoto($row['guardian_photo'])); ?>" alt="Veli Fotoğrafı" width="50" height="50" class="img-thumbnail"></td>
                    <td class="col-2 d-none d-lg-table-cell"><?php echo htmlspecialchars($row['guardian_name']); ?></td>
                    <td class="col-2 d-none d-lg-table-cell">
                        <?php 
                            $formattedPhone = formatPhoneNumberForWhatsApp($row['guardian_phone']); 
                        ?>
                        <a href="https://wa.me/+90<?php echo htmlspecialchars($formattedPhone); ?>" target="_blank" class="link-success link-underline link-underline-opacity-0">
                            <?php echo htmlspecialchars($row['guardian_phone']); ?>
                        </a>
                    </td>
                    <td class="col-2" style="text-align:right;">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Small button group">
                            <a href="student_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" data-bs-toggle="popover" data-bs-trigger="hover focus" title="Detaylar" data-bs-content="Öğrenci detaylarını görüntüleyin">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <?php if ($role == 'admin' || $role == 'secretary') { ?>
                                <a href="student_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" data-bs-toggle="popover" data-bs-trigger="hover focus" title="Düzenle" data-bs-content="Öğrenci bilgilerini düzenleyin">
                                    <i class="bi bi-gear"></i>
                                </a>
                            <?php } ?>
                            <?php if ($role == 'admin') { ?>
                                <a href="student_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" data-bs-toggle="popover" data-bs-trigger="hover focus" title="Sil" data-bs-content="Öğrenciyi silin" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php } ?>
                            <?php if ($role == 'admin' || $role == 'teacher' || $role == 'secretary') { ?>
                                <a href="student_note.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm" data-bs-toggle="popover" data-bs-trigger="hover focus" title="Bilgi Gir" data-bs-content="Öğrenci hakkında bilgi girin">
                                    <i class="bi bi-info-circle"></i>
                                </a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Sayfalama -->
<nav>
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                <a class="page-link link-success" href="dashboard.php?page=<?= $i; ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl)
        })
    });
</script>
