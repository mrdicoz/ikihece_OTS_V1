<?php
session_start();
require_once '../includes/config.php';

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

// Ders programı bilgilerini al
$stmt_schedule = $baglanti->prepare("
    SELECT timetable.day, timetable.hour, students.first_name AS student_first_name, students.last_name AS student_last_name, students.id AS student_id, students.student_photo
    FROM timetable
    JOIN students ON timetable.student_id = students.id
    WHERE timetable.teacher_id = ?
");
$stmt_schedule->bind_param("i", $teacher_id);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();

$schedule_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule_data[$row['day']][$row['hour']] = $row;
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

// Bugünün gününü almak için
$today = date('N') - 1; // 0 (Pazartesi) ile 5 (Cumartesi) arasında
$todayIndex = $today >= 0 && $today < count($days) ? $today : 0; // Eğer gün dışıysa (örneğin Pazar), Pazartesi'yi göster
?>

<?php $pageTitle = "Ders Programı Detayı"; include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Ders Programı Detayı</h2>
    <div class="table-responsive">
        <table class="table table-striped-columns caption-top table-hover table-bordered text-center">
            <caption><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></caption>
            <thead>
                <tr>
                    <th style="width: 10%;">Saat</th>
                    <?php foreach ($days as $index => $day) { ?>
                        <th class="col <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>" style="width: <?php echo 90 / count($days); ?>%;"><?php echo $day; ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hours as $db_hour => $display_hour) { ?>
                    <tr>
                        <td class="align-middle"><?php echo $display_hour; ?></td>
                        <?php foreach ($days as $index => $day) { 
                            $isActive = isset($schedule_data[$day][$db_hour]) ? 'table-active ' : ''; ?>
                            <td class="<?php echo $isActive; ?> <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>">
                                <?php
                                    if (isset($schedule_data[$day][$db_hour])) {
                                        $student = $schedule_data[$day][$db_hour];
                                        $photoPath = !empty($student['student_photo']) ? $student['student_photo'] : '/assets/images/user.jpg';
                                        echo '<a href="student_detail.php?id=' . $student['student_id'] . '" class="link-success link-underline link-underline-opacity-0">';
                                        echo '<img src="' . htmlspecialchars($photoPath) . '" alt="Fotoğraf" width="30" height="30" class="rounded-circle me-2">';
                                        echo htmlspecialchars($student['student_first_name'] . ' ' . $student['student_last_name']);
                                        echo '</a>';
                                    } else {
                                        echo 'Boş';
                                    }
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
