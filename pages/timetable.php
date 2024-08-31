<?php
session_start();
require_once '../config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    header("Location: login.php");
    exit();
}

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;

if (!$teacher_id) {
    die("Geçerli bir öğretmen ID'si sağlanmadı.");
}

$stmt_teacher = $baglanti->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt_teacher->bind_param("i", $teacher_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    die("Öğretmen bulunamadı.");
}

// Ders programı bilgilerini öğrenci ID üzerinden alıyoruz
$stmt_schedule = $baglanti->prepare("
    SELECT timetable.id, timetable.teacher_id, timetable.student_id, timetable.day, timetable.hour, students.first_name AS student_first_name, students.last_name AS student_last_name
    FROM timetable
    JOIN students ON timetable.student_id = students.id
    WHERE timetable.teacher_id = ?
");
$stmt_schedule->bind_param("i", $teacher_id);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();

$schedule_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    // Her hücrede birden fazla öğrenci olabileceğinden, bir dizi yapısı kullanıyoruz
    $schedule_data[$row['day']][$row['hour']][] = $row['student_id'];
}
$stmt_schedule->close();

// Öğrencileri al (Tom Select için)
$students = [];
$stmt_students = $baglanti->prepare("SELECT id, first_name, last_name FROM students");
$stmt_students->execute();
$students_result = $stmt_students->get_result();
while ($student = $students_result->fetch_assoc()) {
    $students[] = $student;
}

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

// Bugünün gününü almak için
$today = date('N') - 1;
$todayIndex = $today >= 0 && $today < count($days) ? $today : 0;
?>

<?php $pageTitle = "Ders Programı"; include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Ders Programı</h2>

    <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['alert_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['alert_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        // Alert mesajını gösterdikten sonra session'dan silin
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
        ?>
    <?php endif; ?>

    <form action="timetable_save.php" method="post">
        <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
        <div class="table-responsive">
            <table class="table caption-top table-hover table-bordered">
                <caption><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></caption>
                <thead>
                    <tr>
                        <th>Saat</th>
                        <?php foreach ($days as $index => $day) { ?>
                            <th class="<?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>"><?php echo $day; ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hours as $db_hour => $display_hour) { ?>
                        <tr>
                            <td><?php echo $display_hour; ?></td>
                            <?php foreach ($days as $index => $day) { 
                                $isActive = isset($schedule_data[$day][$db_hour]) ? 'table-danger' : ''; ?>
                                <td class="<?php echo $isActive; ?> <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>">
                                    <select class="form-select tom-select" name="schedule[<?php echo $day; ?>][<?php echo $db_hour; ?>][]" multiple="multiple">
                                        <option value="">Öğrenci Seç</option>
                                        <?php foreach ($students as $student) { 
                                            $selected = isset($schedule_data[$day][$db_hour]) && in_array($student['id'], $schedule_data[$day][$db_hour]) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-3">
            <button type="submit" class="btn btn-success">Kaydet</button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var elements = document.querySelectorAll('.tom-select');
        elements.forEach(function(el) {
            new TomSelect(el, {
                maxItems: null,  // Sınırsız sayıda seçim yapılabilsin
                plugins: ['remove_button'],  // Seçilen öğeleri kaldırmak için buton ekle
                create: false  // Yeni öğe oluşturulmasın
            });
        });
    });
</script>
