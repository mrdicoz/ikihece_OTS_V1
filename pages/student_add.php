<?php
session_start();
require_once '../includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

function uploadFile($file, $allowedTypes = ['application/pdf'], $destinationDir = '../raporlar/', $defaultFile = null) {
    // Klasör yoksa oluştur
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

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'secretary')) {
    header("Location: login.php");
    exit();
}

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
        $days = $_POST['days']; // Gün seçimi
        $hours = $_POST['hours']; // Saat seçimi
        
        // RAM raporunu yükle
        $ram_report = uploadFile($_FILES['ram_report'], ['application/pdf']);

        // Fotoğraf yükleme işlevini çağır
        $student_photo = uploadPhoto($_FILES['student_photo']);
        $guardian_photo = uploadPhoto($_FILES['guardian_photo']);

        // Transaction başlat
        $baglanti->begin_transaction();

        // Öğrenci verilerini students tablosuna ekle
        $stmt = $baglanti->prepare("INSERT INTO students (student_photo, first_name, last_name, tc_no, gender, disability_type, education_program, birthdate, birthplace, student_info, guardian_photo, guardian_name, guardian_phone, address, distance, transportation, location, guardian_info, second_contact_name, second_contact_phone, ram_report) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssss", $student_photo, $first_name, $last_name, $tc_no, $gender, $disability_type, $education_program, $birthdate, $birthplace, $student_info, $guardian_photo, $guardian_name, $guardian_phone, $address, $distance, $transportation, $location, $guardian_info, $second_contact_name, $second_contact_phone, $ram_report);

        if ($stmt->execute()) {
            // Yeni eklenen öğrencinin ID'sini al
            $student_id = $stmt->insert_id;

            // timetable tablosuna ekleme yap
            if (!empty($teacher_id) && !empty($days) && !empty($hours)) {
                foreach ($days as $day) {
                    foreach ($hours as $hour) {
                        $stmt_timetable = $baglanti->prepare("INSERT INTO timetable (teacher_id, student_id, day, hour) VALUES (?, ?, ?, ?)");
                        $stmt_timetable->bind_param("iiss", $teacher_id, $student_id, $day, $hour);
                        $stmt_timetable->execute();
                    }
                }
            }

            // Transaction'ı onayla
            $baglanti->commit();
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception("Öğrenci eklenirken bir hata oluştu.");
        }
    } catch (Exception $e) {
        // Hata durumunda işlemi geri al ve hata mesajını göster
        $baglanti->rollback();
        echo "Hata: " . $e->getMessage();
    }
}
?>

<?php $pageTitle = "Öğrenci Ekle"; include '../includes/header.php'; ?>

<form method="post" enctype="multipart/form-data">
    <!-- Öğrenci Bilgileri -->
    <div class="row">
        <h3>Öğrenci Bilgileri</h3>
        <div class="col-12 input-group  mb-3">
            <input type="file" class="form-control" name="student_photo" accept="image/*" capture="environment" id="student_photo">
            <label class="input-group-text" for="student_photo">Öğrenci Fotoğrafı</label>
        </div> 

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Adı" required>
                <label for="first_name">Adı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Soyadı" required>
                <label for="last_name">Soyadı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="tc_no" name="tc_no" placeholder="T.C. Kimlik No" required>
                <label for="tc_no">T.C. Kimlik No</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="gender" name="gender" required>
                    <option value="male">Erkek</option>
                    <option value="female">Kız</option>
                </select>
                <label for="gender">Cinsiyet</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="disability_type" name="disability_type" placeholder="Engel Tipi">
                <label for="disability_type">Engel Tipi</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="education_program" name="education_program" placeholder="Eğitim Programı">
                <label for="education_program">Eğitim Programı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="date" class="form-control" id="birthdate" name="birthdate" placeholder="Doğum Tarihi" required>
                <label for="birthdate">Doğum Tarihi</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="birthplace" name="birthplace" placeholder="Doğum Yeri">
                <label for="birthplace">Doğum Yeri</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-floating">
                <textarea class="form-control" id="student_info" name="student_info" placeholder="Öğrenci Hakkında"></textarea>
                <label for="student_info">Öğrenci Hakkında</label>
            </div>
        </div>

        <!-- Veli Bilgileri -->
        <h3>Veli Bilgileri</h3>
        <div class="col-12 input-group  mb-3">
            <input type="file" class="form-control" name="guardian_photo" accept="image/*" capture="environment" id="guardian_photo">
            <label class="input-group-text" for="guardian_photo">Veli Fotoğrafı</label>
        </div> 
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="guardian_name" name="guardian_name" placeholder="Veli Adı Soyadı" required>
                <label for="guardian_name">Veli Adı Soyadı</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="guardian_phone" name="guardian_phone" placeholder="Cep Telefonu" required>
                <label for="guardian_phone">Cep Telefonu</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <textarea class="form-control" id="address" name="address" placeholder="Adres" required></textarea>
                <label for="address">Adres</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="distance" name="distance" required>
                    <option value="near">Civar Bölge</option>
                    <option value="medium">Yakın Bölge</option>
                    <option value="far">Uzak Bölge</option>
                </select>
                <label for="distance">Mesafe</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="transportation" name="transportation" required>
                    <option value="service">Servis</option>
                    <option value="self">Kendisi</option>
                </select>
                <label for="transportation">Ulaşım</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="location" name="location" placeholder="Konum">
                <label for="location">Konum</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-floating">
                <textarea class="form-control" id="guardian_info" name="guardian_info" placeholder="Veli Hakkında"></textarea>
                <label for="guardian_info">Veli Hakkında</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="second_contact_name" name="second_contact_name" placeholder="İkinci Kişi">
                <label for="second_contact_name">İkinci Kişi</label>
            </div>
        </div>
        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="second_contact_phone" name="second_contact_phone" placeholder="İkinci Kişi Tel.">
                <label for="second_contact_phone">İkinci Kişi Tel.</label>
            </div>
        </div>

        <!-- RAM Raporu -->
        <div class="col-12 input-group mb-3">
            <input type="file" class="form-control" name="ram_report" accept=".pdf">
            <label class="input-group-text" for="ram_report">RAM Raporu (PDF)</label>
        </div>
    </div>

    <div class="col-12 mb-3">
    <div class="form-floating mb-3">
        <select class="form-select" id="teacher_id" name="teacher_id">
            <option value="">Öğretmen Seç</option>
            <?php
            $teacherQuery = "SELECT id, first_name, last_name FROM users WHERE role='teacher'";
            $teacherResult = mysqli_query($baglanti, $teacherQuery);
            while ($teacherRow = mysqli_fetch_assoc($teacherResult)) {
                echo "<option value='{$teacherRow['id']}'>{$teacherRow['first_name']} {$teacherRow['last_name']}</option>";
            }
            ?>
        </select>
        <label for="teacher_id">Öğretmen Seç</label>
    </div>
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label for="days" class="form-label">Hangi Günler Okula Gelecek</label>
        <select class="form-select tom-select" name="days[]" multiple="multiple">
    <option value="Pazartesi">Pazartesi</option>
    <option value="Salı">Salı</option>
    <option value="Çarşamba">Çarşamba</option>
    <option value="Perşembe">Perşembe</option>
    <option value="Cuma">Cuma</option>
    <option value="Cumartesi">Cumartesi</option>
</select>
    </div>

    <div class="col-6 mb-3">
        <label for="hours" class="form-label">Hangi Saatlerde Ders Alacak</label>
        <select class="form-select tom-select" name="hours[]" multiple="multiple">
    <option value="08:00">08:00</option>
    <option value="09:00">09:00</option>
    <option value="10:00">10:00</option>
    <option value="11:00">11:00</option>
    <option value="12:00">12:00</option>
    <option value="13:00">13:00</option>
    <option value="14:00">14:00</option>
    <option value="15:00">15:00</option>
    <option value="16:00">16:00</option>
    <option value="17:00">17:00</option>
    <option value="18:00">18:00</option>
</select>
    </div>
</div>   

<div class="text-center"> 
    <button type="submit" class="btn btn-success">Kaydet</button>
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
