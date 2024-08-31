<?php
session_start();
require_once '../config.php';

// Bugünkü günü almak için
$today = date('N'); // Pazartesi 1, Pazar 7 olarak döner
$days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
$todayDayName = $days[$today - 1]; // Gün adı (Pazartesi, Salı vb.)

// weeklytable ve students tablosundan bugünkü gün için servis kullanan öğrencilerin ilk dersini al
$query = "
    SELECT students.id, students.first_name, students.last_name, students.student_photo, students.address, students.guardian_phone, MIN(weeklytable.hour) AS first_hour
    FROM weeklytable
    JOIN students ON weeklytable.student_id = students.id
    WHERE weeklytable.day = ? AND students.transportation = 'service'
    GROUP BY students.id
    ORDER BY first_hour ASC";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("s", $todayDayName);
$stmt->execute();
$students_result = $stmt->get_result();

// Öğrencileri saatlerine göre gruplamak için dizi
$grouped_students = [];
while ($student = $students_result->fetch_assoc()) {
    $hour = $student['first_hour'];
    $grouped_students[$hour][] = $student;
}

?>

<?php $pageTitle = "Servis Kullanıcıları"; include '../includes/header.php'; ?>

<!-- Yazdırma Stilini Tanımlayın -->
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printTable, #printTable * {
            visibility: visible;
        }
        #printTable {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }
        /* Yazdırma sırasında gizli sütunları göster */
        .print-visible {
            display: table-cell !important;
        }
    }

    /* Normal görünümde mobilde gizlenmiş sütunlar gizli kalsın */
    .print-visible {
        display: none;
    }
</style>

<div class="container mt-5">
    <h3 class="text-center mb-4">Bugünkü Servis Kullanıcıları - <?= htmlspecialchars($todayDayName) ?></h3>
    
    <?php if (!empty($grouped_students)): ?>
        <div class="table-responsive" id="printTable">
            <table class="table table-bordered table-hover text-center">
                <thead class="table-light">
                    <tr>
                        <th>Saat</th>
                        <th>Fotoğraf</th>
                        <th>Öğrenci Adı</th>
                        <th class="d-none d-lg-table-cell print-visible">Adres</th>
                        <th class="d-none d-lg-table-cell print-visible">Telefon</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grouped_students as $hour => $students): ?>
                        <?php $first = true; ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <?php if ($first): ?>
                                    <td rowspan="<?= count($students); ?>" style="vertical-align: middle;"><?= htmlspecialchars($hour); ?></td>
                                    <?php $first = false; ?>
                                <?php endif; ?>
                                <td style="vertical-align: middle;">
                                    <?php
                                    // Öğrenci fotoğrafı yoksa varsayılan fotoğrafı göster
                                    $photo = !empty($student['student_photo']) ? htmlspecialchars('../' . $student['student_photo']) : '/assets/images/user.jpg';
                                    ?>
                                    <img src="<?= $photo; ?>" class="img-thumbnail" alt="Fotoğraf" style="width: 50px; height: 50px;">
                                </td>
                                <td style="vertical-align: middle;"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td class="d-none d-lg-table-cell print-visible" style="vertical-align: middle;"><?= htmlspecialchars($student['address']); ?></td>
                                <td class="d-none d-lg-table-cell print-visible" style="vertical-align: middle;"><?= htmlspecialchars($student['guardian_phone']); ?></td>
                                <td class="text-end" style="vertical-align: middle;">
                                    <a href="student_detail.php?id=<?= htmlspecialchars($student['id']); ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Yazdırma Butonu (Sadece admin ve sekreterler için görünür) -->
        <?php if (in_array($_SESSION['role'], ['admin', 'secretary'])): ?>
            <div class="text-center mt-4">
                <button class="btn btn-success" onclick="window.print();">Tabloyu Yazdır</button>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p class="text-center">Bugün servis kullanan öğrenci bulunmamaktadır.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
