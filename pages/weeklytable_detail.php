<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Öğretmen ID'sini al
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;

if (!$teacher_id) {
    die("Geçerli bir öğretmen ID'si sağlanmadı.");
}

// Öğretmen bilgilerini al
$stmt_teacher = $baglanti->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt_teacher->bind_param("i", $teacher_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    die("Öğretmen bulunamadı.");
}

// WeeklyTable verilerini al
$stmt_schedule = $baglanti->prepare("
    SELECT weeklytable.day, weeklytable.hour, students.first_name AS student_first_name, students.last_name AS student_last_name, students.id AS student_id, students.student_photo
    FROM weeklytable
    JOIN students ON weeklytable.student_id = students.id
    WHERE weeklytable.teacher_id = ?
");
$stmt_schedule->bind_param("i", $teacher_id);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();

$schedule_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule_data[$row['day']][$row['hour']][] = $row; // Bir hücrede birden fazla öğrenci olabileceğinden diziyi kullanıyoruz
}
$stmt_schedule->close();

// Günler ve saatler
$days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$hours = [
    '08:00:00' => '08:00',
    '09:00:00' => '09:00',
    '10:00:00' => '10:00',
    '11:00:00' => '11:00',
    '12:00:00' => '12:00',
    '13:00:00' => '13:00',
    '14:00:00' => '14:00',
    '15:00:00' => '15:00',
    '16:00:00' => '16:00',
    '17:00:00' => '17:00',
    '18:00:00' => '18:00'
];

// Bugünün gününü al (1= Pazartesi, 7= Pazar)
$today = date('N');
$todayIndex = ($today == 7) ? 5 : $today - 1; // Pazar günleri Cumartesi gösterilecek
?>

<?php $pageTitle = "Haftalık Tablo Detayları"; include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Haftalık Tablo Detayları</h2>

    <!-- Mobil ekranlar için ileri geri butonları -->
    <div class="d-flex justify-content-between mb-3 d-md-none">
        <button id="prevDay" class="btn btn-success">Önceki Gün</button>
        <button id="nextDay" class="btn btn-success">Sonraki Gün</button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm">
            <caption><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></caption>
            <thead>
                <tr>
                    <th class="text-center">Saat</th>
                    <?php foreach ($days as $index => $day) { ?>
                        <th class="text-center day-column <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>" data-day-index="<?php echo $index; ?>"><?php echo $day; ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hours as $db_hour => $display_hour) { ?>
                    <tr>
                        <td class="text-center"><?php echo $display_hour; ?></td>
                        <?php foreach ($days as $index => $day) { 
                            $students = isset($schedule_data[$day][$db_hour]) ? $schedule_data[$day][$db_hour] : null;
                            ?>
                            <td class="text-center schedule-cell <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>" data-day-index="<?php echo $index; ?>">
                                <?php if ($students) { ?>
                                    <div class="student-info d-flex align-items-center justify-content-center flex-column">
                                        <?php foreach ($students as $student_info) { 
                                            $photo = !empty($student_info['student_photo']) ? htmlspecialchars($student_info['student_photo']) : '/assets/images/user.jpg';
                                            ?>
                                            <div class="mb-2 d-flex align-items-center">
                                                <img src="<?php echo $photo; ?>" alt="Fotoğraf" class="rounded-circle" style="width: 25px; height: 25px;">
                                                <a href="student_detail.php?id=<?php echo htmlspecialchars($student_info['student_id']); ?>" class="ms-2 link-success link-underline link-underline-opacity-0">
                                                    <?php echo htmlspecialchars($student_info['student_first_name'] . ' ' . $student_info['student_last_name']); ?>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    Boş
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dayIndex = <?php echo $todayIndex; ?>;
    var totalDays = <?php echo count($days); ?>;

    function updateDayColumns() {
        document.querySelectorAll('.day-column, .schedule-cell').forEach(function(cell) {
            cell.classList.remove('d-block', 'd-none');
            var cellDayIndex = cell.getAttribute('data-day-index');
            if (cellDayIndex == dayIndex) {
                cell.classList.add('d-block');
            } else {
                cell.classList.add('d-none', 'd-md-table-cell');
            }
        });
    }

    document.getElementById('prevDay').addEventListener('click', function() {
        dayIndex = (dayIndex - 1 + totalDays) % totalDays;
        updateDayColumns();
    });

    document.getElementById('nextDay').addEventListener('click', function() {
        dayIndex = (dayIndex + 1) % totalDays;
        updateDayColumns();
    });
});
</script>
