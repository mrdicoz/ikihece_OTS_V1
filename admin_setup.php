<?php
$success = false;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen verileri al
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];
    $admin_username = $_POST['admin_username'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];

    // config.php dosyasını oluştur
    $config_content = "<?php\n";
    $config_content .= "\$baglanti = mysqli_connect('$db_host', '$db_user', '$db_pass', '$db_name');\n\n";
    $config_content .= "if (mysqli_connect_errno()) {\n";
    $config_content .= "    die('Hata: ' . mysqli_connect_error());\n";
    $config_content .= "}\n";
    $config_content .= "?>";

    // config.php dosyasını includes klasörüne kaydet
    if (file_put_contents('config.php', $config_content) === false) {
        die("Config dosyası oluşturulamadı. Lütfen dosya izinlerini kontrol edin.");
    }

    // Veritabanına bağlan
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($mysqli->connect_error) {
        die("Veritabanı bağlantı hatası: " . $mysqli->connect_error);
    }

    // Gerekli tabloları oluştur
    $tables = [
        // Users Table
        "CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(255) NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'teacher', 'secretary', 'driver', 'reporter') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Students Table
        "CREATE TABLE IF NOT EXISTS students (
            id INT(11) NOT NULL AUTO_INCREMENT,
            student_photo VARCHAR(255) DEFAULT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            tc_no VARCHAR(11) NOT NULL,
            gender ENUM('male', 'female') NOT NULL,
            disability_type VARCHAR(255) DEFAULT NULL,
            education_program VARCHAR(255) DEFAULT NULL,
            birthdate DATE NOT NULL,
            birthplace VARCHAR(255) DEFAULT NULL,
            registration_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            student_info TEXT DEFAULT NULL,
            guardian_photo VARCHAR(255) DEFAULT NULL,
            guardian_name VARCHAR(255) NOT NULL,
            guardian_phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            distance ENUM('near', 'medium', 'far') NOT NULL,
            transportation ENUM('service', 'self') NOT NULL,
            location VARCHAR(255) DEFAULT NULL,
            guardian_info TEXT DEFAULT NULL,
            second_contact_name VARCHAR(255) DEFAULT NULL,
            second_contact_phone VARCHAR(25) DEFAULT NULL,
            ram_report VARCHAR(255) DEFAULT NULL,
            teacher_id INT(11) DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Notes Table
        "CREATE TABLE IF NOT EXISTS notes (
            id INT(11) NOT NULL AUTO_INCREMENT,
            teacher_id INT(11) NOT NULL,
            student_id INT(11) NOT NULL,
            note TEXT NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY teacher_id (teacher_id),
            KEY student_id (student_id),
            CONSTRAINT notes_ibfk_1 FOREIGN KEY (teacher_id) REFERENCES users(id),
            CONSTRAINT notes_ibfk_2 FOREIGN KEY (student_id) REFERENCES students(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Timetable Table
        "CREATE TABLE timetable (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT(11) NOT NULL,
        student_id INT(11) NOT NULL,
        day VARCHAR(20) NOT NULL,
        hour VARCHAR(20) NOT NULL,
        UNIQUE (teacher_id, student_id, day, hour)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // WeeklyTable
        "CREATE TABLE weeklytable (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT(11) NOT NULL,
        student_id INT(11) NOT NULL,
        day VARCHAR(20) NOT NULL,
        hour VARCHAR(20) NOT NULL,
        UNIQUE (teacher_id, student_id, day, hour)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($tables as $table_query) {
        if (!$mysqli->query($table_query)) {
            die("Tablo oluşturma hatası: " . $mysqli->error);
        }
    }

    // İlk admin kullanıcısını ekle
    $stmt = $mysqli->prepare("INSERT INTO users (username, password, first_name, last_name, title, role) VALUES (?, ?, ?, ?,'Sistem Yöneticisi', 'admin')");
    $stmt->bind_param('ssss', $admin_username, $admin_password, $first_name, $last_name);

    if ($stmt->execute()) {
        $success = true;
    } else {
        echo "<div class='alert alert-danger text-center mt-4'>Admin hesabı oluşturulurken bir hata oluştu: " . $stmt->error . "</div>";
    }

    $stmt->close();
    $mysqli->close();

    // Başarılı kurulumdan sonra admin_setup.php dosyasını sil
    if ($success) {
        $setup_file = __FILE__; // Bu, şu an çalışmakta olan dosyanın yolunu alır
        unlink($setup_file); // Dosyayı siler
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Takip Sistemi Kurulumu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-10">
            <div class="card mt-5 shadow-lg">
                <div class="card-header text-center">
                    <h3 class="card-title">OTS Kurulumuna Hoşgeldiniz!</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center">Kurulum başarıyla tamamlandı! Admin hesabı oluşturuldu. Lütfen "admin_setup.php" dosyasını siliniz!</div>
                        <div class="text-center">
                            <a href="pages/login.php" class="btn btn-primary mt-3">Giriş Yap</a>
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <h4 class="mb-3">Veritabanı Bilgileri</h4>
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Veritabanı Sunucusu:</label>
                                <input type="text" name="db_host" id="db_host" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="db_user" class="form-label">Veritabanı Kullanıcı Adı:</label>
                                <input type="text" name="db_user" id="db_user" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="db_pass" class="form-label">Veritabanı Şifresi:</label>
                                <input type="password" name="db_pass" id="db_pass" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Veritabanı Adı:</label>
                                <input type="text" name="db_name" id="db_name" class="form-control" required>
                            </div>

                            <h4 class="mb-3">İlk Admin Hesabı Bilgileri</h4>
                            <div class="mb-3">
                                <label for="admin_username" class="form-label">Kullanıcı Adı:</label>
                                <input type="text" name="admin_username" id="admin_username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="admin_password" class="form-label">Şifre:</label>
                                <input type="password" name="admin_password" id="admin_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Adı:</label>
                                <input type="text" name="first_name" id="first_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Soyadı:</label>
                                <input type="text" name="last_name" id="last_name" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Kurulumu Tamamla</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
