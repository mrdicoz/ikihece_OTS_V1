<?php
session_start();
require_once '../includes/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fotoğraf yükleme fonksiyonu (student_add.php'deki gibi)
function uploadPhoto($file, $defaultPhoto = '/assets/images/user.jpg', $maxWidth = 500, $maxHeight = 500) {
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            list($width, $height) = getimagesize($file['tmp_name']);
            $ratio = $width / $height;

            if ($maxWidth / $maxHeight > $ratio) {
                $newWidth = $maxHeight * $ratio;
                $newHeight = $maxHeight;
            } else {
                $newHeight = $maxWidth / $ratio;
                $newWidth = $maxWidth;
            }

            $src = imagecreatefromstring(file_get_contents($file['tmp_name']));
            $dst = imagecreatetruecolor($newWidth, $newHeight);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $fileInfo = pathinfo($file['name']);
            $fileName = time() . '_' . uniqid() . '.' . $fileInfo['extension'];
            $destination = '../foto/' . $fileName;

            switch ($fileType) {
                case 'image/jpeg':
                    imagejpeg($dst, $destination, 90);
                    break;
                case 'image/png':
                    imagepng($dst, $destination, 9);
                    break;
                case 'image/gif':
                    imagegif($dst, $destination);
                    break;
                default:
                    return $defaultPhoto;
            }

            imagedestroy($src);
            imagedestroy($dst);

            return $destination;
        } else {
            return $defaultPhoto;
        }
    }
    return $defaultPhoto;
}

// Dosya yükleme fonksiyonu (student_add.php'deki gibi)
function uploadFile($file, $allowedTypes = ['application/pdf'], $destinationDir = '../raporlar/', $defaultFile = null) {
    if (!file_exists($destinationDir)) {
        mkdir($destinationDir, 0777, true);
    }

    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $fileType = mime_content_type($file['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            $fileInfo = pathinfo($file['name']);
            $fileName = time() . '_' . uniqid() . '.' . $fileInfo['extension'];
            $destination = $destinationDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return $destination;
            }
        }
    }
    return $defaultFile;
}

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'secretary')) {
    header("Location: login.php");
    exit();
}

// Mevcut öğrenci verilerini çek
$student_id = intval($_GET['id']);
$stmt = $baglanti->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Timetable'dan teacher_id, days ve hours bilgilerini çekelim
$teacher_stmt = $baglanti->prepare("SELECT teacher_id FROM timetable WHERE student_id = ? LIMIT 1");
$teacher_stmt->bind_param("i", $student_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$timetable_teacher = $teacher_result->fetch_assoc();
$teacher_id = $timetable_teacher ? $timetable_teacher['teacher_id'] : null;

$days = [];
$hours = [];
$days_stmt = $baglanti->prepare("SELECT day FROM timetable WHERE student_id = ?");
$days_stmt->bind_param("i", $student_id);
$days_stmt->execute();
$days_result = $days_stmt->get_result();
while ($row = $days_result->fetch_assoc()) {
    $days[] = $row['day'];
}

$hours_stmt = $baglanti->prepare("SELECT hour FROM timetable WHERE student_id = ?");
$hours_stmt->bind_param("i", $student_id);
$hours_stmt->execute();
$hours_result = $hours_stmt->get_result();
while ($row = $hours_result->fetch_assoc()) {
    $hours[] = $row['hour'];
}

// Form gönderimi kontrolü
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Form verilerini al
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $tc_no = $_POST['tc_no'];
        $gender = $_POST['gender'];
        $disability_type = $_POST['disability_type'];
        $education_program = $_POST['education_program'];
        $birthdate = $_POST['birthdate'];
        $birthplace = $_POST['birthplace'];
        $student_info = $_POST['student_info'];
        $guardian_name = $_POST['guardian_name'];
        $guardian_phone = $_POST['guardian_phone'];
        $address = $_POST['address'];
        $distance = $_POST['distance'];
        $transportation = $_POST['transportation'];
        $location = $_POST['location'];
        $guardian_info = $_POST['guardian_info'];
        $second_contact_name = $_POST['second_contact_name'];
        $second_contact_phone = $_POST['second_contact_phone'];
        $teacher_id = $_POST['teacher_id']; // Öğretmen seçimi

        // RAM raporunu yükle
        $ram_report = uploadFile($_FILES['ram_report'], ['application/pdf'], '../raporlar/', $student['ram_report']);

        // Eğer yeni bir dosya yüklendiyse, eski dosyayı sil
        if ($ram_report && $ram_report !== $student['ram_report'] && file_exists($student['ram_report'])) {
            unlink($student['ram_report']);
        }

        // Fotoğraf yükleme işlevini çağır
        $student_photo = uploadPhoto($_FILES['student_photo'], $student['student_photo']);
        $guardian_photo = uploadPhoto($_FILES['guardian_photo'], $student['guardian_photo']);

        // Transaction başlat
        $baglanti->begin_transaction();

        // Öğrenci verilerini güncelle
        $stmt = $baglanti->prepare("UPDATE students SET 
        student_photo = ?, 
        first_name = ?, 
        last_name = ?, 
        tc_no = ?, 
        gender = ?, 
        disability_type = ?, 
        education_program = ?, 
        birthdate = ?, 
        birthplace = ?, 
        student_info = ?, 
        guardian_photo = ?, 
        guardian_name = ?, 
        guardian_phone = ?, 
        address = ?, 
        distance = ?, 
        transportation = ?, 
        location = ?, 
        guardian_info = ?, 
        second_contact_name = ?, 
        second_contact_phone = ?, 
        ram_report = ? 
        WHERE id = ?");
        $stmt->bind_param("sssssssssssssssssssssi", $student_photo, $first_name, $last_name, $tc_no, $gender, $disability_type, $education_program, $birthdate, $birthplace, $student_info, $guardian_photo, $guardian_name, $guardian_phone, $address, $distance, $transportation, $location, $guardian_info, $second_contact_name, $second_contact_phone, $ram_report, $student_id);

        if ($stmt->execute()) {
            // Gün ve saat bilgilerini güncelle (önce mevcut kayıtları sil)
            $delete_timetable_stmt = $baglanti->prepare("DELETE FROM timetable WHERE student_id = ?");
            $delete_timetable_stmt->bind_param("i", $student_id);
            $delete_timetable_stmt->execute();

            if (!empty($_POST['days']) && !empty($_POST['hours'])) {
                $time_stmt = $baglanti->prepare("INSERT INTO timetable (student_id, day, hour, teacher_id) VALUES (?, ?, ?, ?)");
                foreach ($_POST['days'] as $day) {
                    foreach ($_POST['hours'] as $hour) {
                        $time_stmt->bind_param("issi", $student_id, $day, $hour, $teacher_id);
                        $time_stmt->execute();
                    }
                }
            }

            // Transaction'ı onayla
            $baglanti->commit();
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception("Öğrenci güncellenirken bir hata oluştu.");
        }
    } catch (Exception $e) {
        // Hata durumunda işlemi geri al ve hata mesajını göster
        $baglanti->rollback();
        echo "Hata: " . $e->getMessage();
    }
}
?>

<?php $pageTitle = "Öğrenci Düzenle"; include '../includes/header.php'; ?>

<form method="post" enctype="multipart/form-data">
    <!-- Öğrenci Bilgileri -->
    <div class="row">
        <h3>Öğrenci Bilgileri</h3>
        <div class="col-12 input-group mb-3">
            <input type="file" class="form-control" name="student_photo" accept="image/*" capture="environment" id="student_photo">
            <label class="input-group-text" for="student_photo">Öğrenci Fotoğrafı &nbsp <?php if ($student['student_photo']) { ?>
                <img src="<?php echo htmlspecialchars($student['student_photo']); ?>" alt="Öğrenci Fotoğrafı"style="max-height: 20px;">
            <?php } ?></label>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Adı" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                <label for="first_name">Adı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Soyadı" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                <label for="last_name">Soyadı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="tc_no" name="tc_no" placeholder="T.C. Kimlik No" value="<?php echo htmlspecialchars($student['tc_no']); ?>" required>
                <label for="tc_no">T.C. Kimlik No</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="gender" name="gender" required>
                    <option value="male" <?php echo $student['gender'] == 'male' ? 'selected' : ''; ?>>Erkek</option>
                    <option value="female" <?php echo $student['gender'] == 'female' ? 'selected' : ''; ?>>Kız</option>
                </select>
                <label for="gender">Cinsiyet</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="disability_type" name="disability_type" placeholder="Engel Tipi" value="<?php echo htmlspecialchars($student['disability_type']); ?>">
                <label for="disability_type">Engel Tipi</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="education_program" name="education_program" placeholder="Eğitim Programı" value="<?php echo htmlspecialchars($student['education_program']); ?>">
                <label for="education_program">Eğitim Programı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="date" class="form-control" id="birthdate" name="birthdate" placeholder="Doğum Tarihi" value="<?php echo htmlspecialchars($student['birthdate']); ?>" required>
                <label for="birthdate">Doğum Tarihi</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="birthplace" name="birthplace" placeholder="Doğum Yeri" value="<?php echo htmlspecialchars($student['birthplace']); ?>">
                <label for="birthplace">Doğum Yeri</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-floating">
                <textarea class="form-control" id="student_info" name="student_info" placeholder="Öğrenci Hakkında"><?php echo htmlspecialchars($student['student_info']); ?></textarea>
                <label for="student_info">Öğrenci Hakkında</label>
            </div>
        </div>


        <!-- Veli Bilgileri -->
        <h3>Veli Bilgileri</h3>
        <div class="col-12 input-group mb-3">
            <input type="file" class="form-control" name="guardian_photo" accept="image/*" capture="environment" id="guardian_photo">
            <label class="input-group-text" for="guardian_photo">Veli Fotoğrafı &nbsp <?php if ($student['guardian_photo']) { ?>
                <img src="<?php echo htmlspecialchars($student['guardian_photo']); ?>" alt="Veli Fotoğrafı" style="max-height: 20px;">
            <?php } ?></label>

        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="guardian_name" name="guardian_name" placeholder="Veli Adı Soyadı" value="<?php echo htmlspecialchars($student['guardian_name']); ?>" required>
                <label for="guardian_name">Veli Adı Soyadı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" placeholder="Cep Telefonu" value="<?php echo htmlspecialchars($student['guardian_phone']); ?>" required>
                <label for="guardian_phone">Cep Telefonu</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <textarea class="form-control" id="address" name="address" placeholder="Adres" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                <label for="address">Adres</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="distance" name="distance" required>
                    <option value="near" <?php echo $student['distance'] == 'near' ? 'selected' : ''; ?>>Civar Bölge</option>
                    <option value="medium" <?php echo $student['distance'] == 'medium' ? 'selected' : ''; ?>>Yakın Bölge</option>
                    <option value="far" <?php echo $student['distance'] == 'far' ? 'selected' : ''; ?>>Uzak Bölge</option>
                </select>
                <label for="distance">Mesafe</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="transportation" name="transportation" required>
                    <option value="service" <?php echo $student['transportation'] == 'service' ? 'selected' : ''; ?>>Servis</option>
                    <option value="self" <?php echo $student['transportation'] == 'self' ? 'selected' : ''; ?>>Kendisi</option>
                </select>
                <label for="transportation">Ulaşım</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="location" name="location" placeholder="Konum" value="<?php echo htmlspecialchars($student['location']); ?>">
                <label for="location">Konum</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-floating">
                <textarea class="form-control" id="guardian_info" name="guardian_info" placeholder="Veli Hakkında"><?php echo htmlspecialchars($student['guardian_info']); ?></textarea>
                <label for="guardian_info">Veli Hakkında</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="second_contact_name" name="second_contact_name" placeholder="İkinci Kişi" value="<?php echo htmlspecialchars($student['second_contact_name']); ?>">
                <label for="second_contact_name">İkinci Kişi</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="second_contact_phone" name="second_contact_phone" placeholder="İkinci Kişi Tel." value="<?php echo htmlspecialchars($student['second_contact_phone']); ?>">
                <label for="second_contact_phone">İkinci Kişi Tel.</label>
            </div>
        </div>

        <!-- RAM Raporu -->
        <div class="col-12 input-group mb-3">
            <input type="file" class="form-control" name="ram_report" accept=".pdf">
            <label class="input-group-text" for="ram_report">RAM Raporu (PDF)</label>
            <?php if ($student['ram_report']) { ?>
                <a href="<?php echo htmlspecialchars($student['ram_report']); ?>" class="btn btn-success" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Mevcut RAM Raporu</a>
            <?php } ?>
        </div>
    </div>

    <!-- Öğretmen Seçimi -->
    <div class="col-12 mb-3">
        <div class="form-floating mb-3">
            <select class="form-select" id="teacher_id" name="teacher_id">
                <option value="">Öğretmen Seç</option>
                <?php
                $teacherQuery = "SELECT id, first_name, last_name FROM users WHERE role='teacher'";
                $teacherResult = mysqli_query($baglanti, $teacherQuery);
                while ($teacherRow = mysqli_fetch_assoc($teacherResult)) {
                    $selected = ($teacherRow['id'] == $teacher_id) ? 'selected' : '';
                    echo "<option value='{$teacherRow['id']}' {$selected}>{$teacherRow['first_name']} {$teacherRow['last_name']}</option>";
                }
                ?>
            </select>
            <label for="teacher_id">Öğretmen Seç</label>
        </div>
    </div>

    <!-- Gün ve Saat Seçimi -->
<div class="row">
    <div class="col-6 mb-3">
        <label for="days" class="form-label">Hangi Günler Okula Gelecek</label>
        <select class="form-select tom-select" id="days" name="days[]" multiple>
            <?php
            $allDays = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
            foreach ($allDays as $day) {
                $selected = in_array($day, $days) ? 'selected' : '';
                echo "<option value='{$day}' {$selected}>{$day}</option>";
            }
            ?>
        </select>
    </div>

    <div class="col-6 mb-3">
        <label for="hours" class="form-label">Hangi Saatlerde Ders Alacak</label>
        <select class="form-select tom-select" id="hours" name="hours[]" multiple>
            <?php
            $allHours = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00'];
            foreach ($allHours as $hour) {
                $selected2 = in_array($hour, $hours) ? 'selected' : '';
                echo "<option value='{$hour}' {$selected2}>{$hour}</option>";
            }
            ?>
        </select>
    </div>
</div>

    <div class="text-center">
        <button type="submit" class="btn btn-success">Güncelle</button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var elements = document.querySelectorAll('.tom-select');
        elements.forEach(function(el) {
            new TomSelect(el, {
                maxItems: null,  // Sınırsız sayıda seçim
                plugins: ['remove_button'],  // Seçilen öğrenciyi kaldırma butonu ekle
                create: false  // Yeni öğe oluşturulmasın
            });
        });
    });
</script>
