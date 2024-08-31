<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Kullanıcı verilerini çekme
    $stmt = $baglanti->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $role = $_POST['role'];
        $title = $_POST['title'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Şifre güncellenmişse hashle
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $baglanti->prepare("UPDATE users SET username = ?, password = ?, role = ?, title = ?, first_name = ?, last_name = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssssii", $username, $password, $role, $title, $first_name, $last_name, $is_active, $user_id);
        } else {
            $stmt = $baglanti->prepare("UPDATE users SET username = ?, role = ?, title = ?, first_name = ?, last_name = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $username, $role, $title, $first_name, $last_name, $is_active, $user_id);
        }

        // Fotoğraf yükleme işlemi
        function uploadPhoto($file, $defaultPhoto = '/assets/images/user.jpg') {
            if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($file['tmp_name']);

                if (in_array($fileType, $allowedTypes)) {
                    $fileInfo = pathinfo($file['name']);
                    $fileName = 'user_' . time() . '_' . uniqid() . '.' . $fileInfo['extension'];
                    $destination = '../user_foto/' . $fileName;

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
                    return $defaultPhoto;
                }
            }
            return $defaultPhoto;
        }

        // Eğer yeni bir fotoğraf yüklendiyse, eski fotoğrafı değiştir
        if (!empty($_FILES['photo']['name'])) {
            $photoPath = uploadPhoto($_FILES['photo'], $user['photo']);
            $stmt = $baglanti->prepare("UPDATE users SET photo = ? WHERE id = ?");
            $stmt->bind_param("si", $photoPath, $user_id);
            $stmt->execute();
        }

        if ($stmt->execute()) {
            header("Location: user_list.php");
            exit();
        } else {
            $error = "Hata: " . $stmt->error;
        }
    }
} else {
    header("Location: user_list.php");
    exit();
}
?>

<?php $pageTitle = "Kullanıcı Düzenle"; include '../includes/header.php'; ?>
<h3>Kullanıcı Düzenle</h3>
<form action="user_edit.php?id=<?php echo $user_id; ?>" method="post" enctype="multipart/form-data">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="input-group">
                <input type="file" class="form-control" name="photo" accept="image/*" capture="environment" id="photo">
                <label class="input-group-text" for="photo">Profil Fotoğrafı &nbsp 
                    <?php if (isset($user["photo"])) : ?>
                        <img src="<?php echo $user["photo"]; ?>" style="max-height: 20px;">
                    <?php endif; ?>
                </label>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <label for="username">Kullanıcı Adı</label>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Yeni Şifre">
                <label for="password">Yeni Şifre (değiştirmek istemiyorsanız boş bırakın)</label>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <select class="form-select" id="role" name="role" required>
                    <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="teacher" <?php if ($user['role'] == 'teacher') echo 'selected'; ?>>Öğretmen</option>
                    <option value="secretary" <?php if ($user['role'] == 'secretary') echo 'selected'; ?>>Sekreter</option>
                    <option value="driver" <?php if ($user['role'] == 'driver') echo 'selected'; ?>>Şoför</option>
                </select>
                <label for="role">Rol</label>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="title" name="title" placeholder="Ünvan" value="<?php echo htmlspecialchars($user['title']); ?>" required>
                <label for="title">Ünvan</label>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Adı" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                <label for="first_name">Adı</label>
            </div>
        </div>

        <div class="col-6 mb-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Soyadı" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                <label for="last_name">Soyadı</label>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php if ($user['is_active'] == 1) echo 'checked'; ?>>
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
        </div>
    </div>
           
    <div class="text-center"><button type="submit" class="btn btn-success">Kullanıcıyı Güncelle</button></div>
    <?php if (isset($error)) { echo "<p class='text-danger'>$error</p>"; } ?>
</form>
<?php include '../includes/footer.php'; ?>
