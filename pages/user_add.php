<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fotoğraf yükleme işlevi
function uploadPhoto($file, $defaultPhoto = '/assets/images/user.jpg') {
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            $fileInfo = pathinfo($file['name']);
            $fileName = 'user_' . time() . '_' . uniqid() . '.' . $fileInfo['extension']; // Benzersiz isim
            $destination = '../user_foto/' . $fileName;  // Fotoğrafları kökteki "user_foto" klasörüne kaydet

            // Fotoğrafı optimize edin
            list($width, $height) = getimagesize($file['tmp_name']);
            $newWidth = 500;
            $newHeight = 500;
            $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($fileType == 'image/jpeg') {
                $source = imagecreatefromjpeg($file['tmp_name']);
            } elseif ($fileType == 'image/png') {
                $source = imagecreatefrompng($file['tmp_name']);
            } elseif ($fileType == 'image/gif') {
                $source = imagecreatefromgif($file['tmp_name']);
            }
            
            imagecopyresampled($tmpImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagejpeg($tmpImage, $destination, 90);

            return $destination;
        } else {
            return $defaultPhoto; // Geçersiz dosya türü olduğunda varsayılan fotoğrafı döndür
        }
    }
    return $defaultPhoto; // Dosya yüklenmediyse varsayılan fotoğrafı döndür
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $title = $_POST['title'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $photoPath = uploadPhoto($_FILES['photo']);

    $stmt = $baglanti->prepare("INSERT INTO users (username, password, role, title, first_name, last_name, photo, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $username, $password, $role, $title, $first_name, $last_name, $photoPath, $is_active);

    if ($stmt->execute()) {
        $message = "Kullanıcı başarıyla oluşturuldu!";
    } else {
        $error = "Hata: " . $stmt->error;
    }
}
?>

<?php $pageTitle = "Kullanıcı Ekle"; include '../includes/header.php'; ?>
<h3>Kullanıcı Ekle</h3>
<form action="user_add.php" method="post" enctype="multipart/form-data">

<div class="row">

<div class="col-12 mb-3">
            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">

    </div>

    <div class="col-6 mb-3">
        <div class="form-floating">
            <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" required>
            <label for="username">Kullanıcı Adı</label>
        </div>
    </div>

    <div class="col-6 mb-3">
        <div class="form-floating">
            <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
            <label for="password">Şifre</label>
        </div>
    </div>

    <div class="col-6 mb-3">
        <div class="form-floating">
            <select class="form-select" id="role" name="role" required>
                <option value="admin">Admin</option>
                <option value="teacher">Öğretmen</option>
                <option value="secretary">Sekreter</option>
                <option value="driver">Şoför</option>
            </select>
            <label for="role">Rol</label>
        </div>
    </div>

    <div class="col-6 mb-3">
        <div class="form-floating">
            <input type="text" class="form-control" id="title" name="title" placeholder="Ünvan" required>
            <label for="title">Ünvan</label>
        </div>
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



    <div class="col-12 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
            <label class="form-check-label" for="is_active">Aktif</label>
        </div>
    </div>
</div>  
<div class="text-center"> 
    <button type="submit" class="btn btn-success">Kullanıcıyı Ekle</button>
</div>

    <?php if (isset($message)) { echo "<p class='text-success'>$message</p>"; } ?>
    <?php if (isset($error)) { echo "<p class='text-danger'>$error</p>"; } ?>
  
</form>

<?php include '../includes/footer.php'; ?>
