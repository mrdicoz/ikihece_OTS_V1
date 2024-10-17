<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Sayfalama için gerekli değişkenler
$limit = 25;  // Her sayfada gösterilecek öğrenci sayısı
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;  // Mevcut sayfa numarası
$offset = ($page - 1) * $limit;  // Kaç kaydı atlayacağımızı hesapla

// Varsayılan SQL sorgusu
$sql = "
    SELECT students.*, 
           GROUP_CONCAT(DISTINCT timetable.day ORDER BY timetable.day ASC SEPARATOR ', ') AS days 
    FROM students
    LEFT JOIN timetable ON students.id = timetable.student_id
    WHERE 1=1
";

// Gelen filtrelere göre SQL sorgusunu oluşturma
if (!empty($_GET['first_name'])) {
    $first_name = $baglanti->real_escape_string($_GET['first_name']);
    $sql .= " AND first_name LIKE '%$first_name%'";
}

if (!empty($_GET['last_name'])) {
    $last_name = $baglanti->real_escape_string($_GET['last_name']);
    $sql .= " AND last_name LIKE '%$last_name%'";
}

if (!empty($_GET['tc_no'])) {
    $tc_no = $baglanti->real_escape_string($_GET['tc_no']);
    $sql .= " AND tc_no LIKE '%$tc_no%'";
}

// Gün filtrelemesi için
$dayCondition = [];

if (!empty($_GET['days']) && !in_array("", $_GET['days'])) { // Eğer "Tüm Günler" seçilmemişse
    foreach ($_GET['days'] as $day) {
        $day = $baglanti->real_escape_string($day);
        $dayCondition[] = "timetable.day = '$day'";
    }
}

// Eğer herhangi bir gün filtresi eklendiyse, sorguya OR koşulunu ekleyin
if (!empty($dayCondition)) {
    $sql .= " AND (" . implode(" OR ", $dayCondition) . ")";
}

// Mesafe filtrelemesi
if (!empty($_GET['distance']) && $_GET['distance'] !== "") {
    $distance = $baglanti->real_escape_string($_GET['distance']);
    $sql .= " AND distance = '$distance'";
}

// Toplam kayıt sayısını almak için COUNT(*) sorgusu (Filtreler dahil)
$count_sql = "
    SELECT COUNT(DISTINCT students.id) AS total 
    FROM students 
    LEFT JOIN timetable ON students.id = timetable.student_id 
    WHERE 1=1
";

// Gelen filtrelere göre COUNT(*) sorgusunu oluşturma
if (!empty($_GET['first_name'])) {
    $first_name = $baglanti->real_escape_string($_GET['first_name']);
    $count_sql .= " AND first_name LIKE '%$first_name%'";
}

if (!empty($_GET['last_name'])) {
    $last_name = $baglanti->real_escape_string($_GET['last_name']);
    $count_sql .= " AND last_name LIKE '%$last_name%'";
}

if (!empty($_GET['tc_no'])) {
    $tc_no = $baglanti->real_escape_string($_GET['tc_no']);
    $count_sql .= " AND tc_no LIKE '%$tc_no%'";
}

// Gün filtrelemesi için
if (!empty($dayCondition)) {
    $count_sql .= " AND (" . implode(" OR ", $dayCondition) . ")";
}

// Mesafe filtrelemesi
if (!empty($_GET['distance']) && $_GET['distance'] !== "") {
    $distance = $baglanti->real_escape_string($_GET['distance']);
    $count_sql .= " AND distance = '$distance'";
}

// Sorguyu çalıştır ve toplam öğrenci sayısını al
$count_result = $baglanti->query($count_sql);
$total_students = $count_result->fetch_assoc()['total'];

// Sayfa sayısını hesapla
$total_pages = ceil($total_students / $limit);

$sql .= " GROUP BY students.id LIMIT $limit OFFSET $offset";

// Sorguyu çalıştırma
$result = $baglanti->query($sql);

if (!$result) {
    die("Sorgu Hatası: " . $baglanti->error);
}

function translateDistance($distance) {
    switch ($distance) {
        case 'near':
            return 'Civar Bölge';
        case 'medium':
            return 'Yakın Bölge';
        case 'far':
            return 'Uzak Bölge';
        default:
            return $distance;
    }
}

// Fotoğraf yolunu kontrol eden bir fonksiyon ekliyoruz
function getStudentPhoto($photoPath) {
    if (empty($photoPath) || !file_exists($photoPath)) {
        return '/assets/images/user.jpg'; // Varsayılan fotoğraf
    }
    return $photoPath;
}

function formatPhoneNumberForWhatsApp($phone) {
    // Telefon numarasından parantezleri, boşlukları ve diğer karakterleri kaldır
    return preg_replace('/[^\d]/', '', $phone);
}

?>

<?php $pageTitle = "Öğrenci Arama Sonuçları"; include '../includes/header.php'; ?>
<h3>Öğrenci Arama Sonuçları</h3>

<div class="table-responsive mt-3">
    <table class="table table-striped table-light align-middle table-hover">
        <thead>
            <tr>
                <th class="col-1">Fotoğraf</th>
                <th class="col-1">Adı</th>
                <th class="col-1">Soyadı</th>
                <th class="col-1 d-none d-lg-table-cell">Veli</th>
                <th class="col-2 d-none d-lg-table-cell">Veli Adı</th>
                <th class="col-2 d-none d-lg-table-cell">Telefon</th>
                <th class="col-2 d-none d-lg-table-cell">Günler</th>
                <th class="col-2 d-none d-lg-table-cell">Mesafe</th>
                <th class="col-1" style="text-align:right;">İşlemler</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td class="col-1"><img src="<?php echo htmlspecialchars(getStudentPhoto($row['student_photo'])); ?>" alt="Öğrenci Fotoğrafı" width="50" height="50" class="img-thumbnail"></td>
                        <td class="col-1"><?php echo htmlspecialchars($row['first_name']); ?></td>
                        <td class="col-1"><?php echo htmlspecialchars($row['last_name']); ?></td>
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
                        <td class="col-2 d-none d-lg-table-cell"><?php echo htmlspecialchars($row['days']); ?></td>
                        <td class="col-2 d-none d-lg-table-cell"><?php echo htmlspecialchars(translateDistance($row['distance'])); ?></td>
                        <td class="col-1" style="text-align:right;">
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
            <?php } else { ?>
                <tr>
                    <td colspan="9">Arama sonuçları bulunamadı.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>    

<!-- Sayfalama -->
<nav>
    <ul class="pagination justify-content-center mt-3">
        <?php
        // Mevcut sayfa URL'sini al ve sayfa numarası hariç diğer parametreleri koru
        $query_params = $_GET; // Mevcut GET parametrelerini al
        for ($i = 1; $i <= $total_pages; $i++):
            $query_params['page'] = $i; // Sayfa numarasını güncelle
            $query_string = http_build_query($query_params); // Tüm parametreleri string olarak birleştir
        ?>
            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                <a class="page-link link-success" href="student_search.php?<?= $query_string; ?>"><?= $i; ?></a>
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
