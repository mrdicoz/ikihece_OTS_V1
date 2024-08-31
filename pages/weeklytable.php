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
    SELECT weeklytable.day, weeklytable.hour, students.first_name AS student_first_name, students.last_name AS student_last_name, students.id AS student_id
    FROM weeklytable
    JOIN students ON weeklytable.student_id = students.id
    WHERE weeklytable.teacher_id = ?
");
$stmt_schedule->bind_param("i", $teacher_id);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();

$schedule_data = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule_data[$row['day']][$row['hour']][] = $row; // Bir hücrede birden fazla öğrenci olabilir
}
$stmt_schedule->close();

// Timetable'den gelen öneri verileri
$stmt_timetable = $baglanti->prepare("
    SELECT timetable.day, timetable.hour, students.first_name AS student_first_name, students.last_name AS student_last_name, students.id AS student_id
    FROM timetable
    JOIN students ON timetable.student_id = students.id
    WHERE timetable.teacher_id = ?
");
$stmt_timetable->bind_param("i", $teacher_id);
$stmt_timetable->execute();
$timetable_result = $stmt_timetable->get_result();

$timetable_data = [];
while ($row = $timetable_result->fetch_assoc()) {
    $timetable_data[$row['day']][$row['hour']][] = $row; // Bir hücrede birden fazla öğrenci olabilir
}
$stmt_timetable->close();

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

<?php $pageTitle = "Haftalık Ders Programı"; include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Haftalık Ders Programı</h2>

    <!-- Mobil ekranlar için ileri geri butonları -->
    <div class="d-flex justify-content-between mb-3 d-md-none">
        <button id="prevDay" class="btn btn-success">Önceki Gün</button>
        <button id="nextDay" class="btn btn-success">Sonraki Gün</button>
    </div>

    <form action="weeklytable_save.php" method="post">
        <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
        <div class="table-responsive">
            <table class="table table-striped-columns caption-top table-hover table-bordered">
                <caption><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></caption>
                <thead>
                    <tr>
                        <th>Saat</th>
                        <?php foreach ($days as $index => $day) { ?>
                            <th class="day-column <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>" data-day-index="<?php echo $index; ?>"><?php echo $day; ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hours as $db_hour => $display_hour) { ?>
                        <tr>
                            <td><?php echo $display_hour; ?></td>
                            <?php foreach ($days as $index => $day) { 
                                $isActive = isset($schedule_data[$day][$db_hour]) ? 'table-success' : '';
                                $isTimetable = isset($timetable_data[$day][$db_hour]) ? 'table-danger' : ''; ?>
                                <td class="schedule-cell <?php echo $isActive . ' ' . $isTimetable; ?> <?php echo $index == $todayIndex ? 'd-block' : 'd-none d-md-table-cell'; ?>" data-day-index="<?php echo $index; ?>">
                                    <select class="form-select tom-select" name="schedule[<?php echo $day; ?>][<?php echo $db_hour; ?>][]" multiple>
                                        <option value="">Öğrenci Seç</option>
                                        <?php
                                        // Eğer schedule_data'da öğrenci varsa, onları seçili olarak göster
                                        if (isset($schedule_data[$day][$db_hour])) {
                                            foreach ($schedule_data[$day][$db_hour] as $schedule_student) {
                                                echo '<option value="' . htmlspecialchars($schedule_student['student_id']) . '" selected>';
                                                echo htmlspecialchars($schedule_student['student_first_name'] . ' ' . $schedule_student['student_last_name']);
                                                echo '</option>';
                                            }
                                        }

                                        // Timetable'den gelen öğrenci varsa, ona özel bir data attribute ve stil ekleyelim
                                        if (isset($timetable_data[$day][$db_hour])) {
                                            foreach ($timetable_data[$day][$db_hour] as $timetable_student) {
                                                echo '<option value="' . htmlspecialchars($timetable_student['student_id']) . '" data-from-timetable="true">';
                                                echo htmlspecialchars($timetable_student['student_first_name'] . ' ' . $timetable_student['student_last_name']);
                                                echo '</option>';
                                            }
                                        }

                                        // Diğer tüm öğrencileri seçenek olarak ekle
                                        foreach ($students as $student) {
                                            echo '<option value="' . htmlspecialchars($student['id']) . '">';
                                            echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                                            echo '</option>';
                                        }
                                        ?>
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

<?php include '../includes/footer.php';?>

<style>
    /* Timetable'den gelen öğrencilere kırmızı renk uygulayalım */
    .option-from-timetable {
        color: red !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var elements = document.querySelectorAll('.tom-select');
    function initializeTomSelect() {
        elements.forEach(function(el) {
            if (el.tomselect) {
                el.tomselect.destroy(); // Önceki Tom Select instance'ını yok et
            }
            new TomSelect(el, {
                maxItems: null,  // Sınırsız seçim yapılabilsin
                plugins: ['remove_button'],  // Seçilen öğeleri kaldırmak için buton ekle
                create: false,  // Yeni seçenek oluşturulmasın
                render: {
                    option: function(data, escape) {
                        // Timetable'den gelen öğrenciye özel stil ekleyelim
                        var customClass = data.fromTimetable ? 'option-from-timetable' : '';
                        return '<div class="' + customClass + '">' + escape(data.text) + '</div>';
                    }
                },
                onInitialize: function() {
                    // Timetable'den gelen öğrencilere data attribute ekle
                    var options = this.options;
                    for (var value in options) {
                        if (options[value].fromTimetable) {
                            var option = this.getOption(value);
                            if (option) {  // Option öğesi gerçekten varsa
                                option.setAttribute('data-from-timetable', 'true');
                            }
                        }
                    }
                }
            });
        });
    }

    // Tom Select başlatıcıyı ilk başta çağır
    initializeTomSelect();

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

        // Tom Select'i yeniden başlat
        elements = document.querySelectorAll('.tom-select');  // Güncellenen elementleri seç
        initializeTomSelect();
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
