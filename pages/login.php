<?php
session_start();
require_once '../includes/config.php';

// Aktif bir oturum varsa, kullanıcıyı dashboard.php sayfasına yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $baglanti->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if ($user['is_active'] == 1) { // Kullanıcı aktif mi kontrol et
            if (password_verify($password, $user['password'])) {
                // Kullanıcı bilgilerini oturumda sakla
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Geçersiz kullanıcı adı veya şifre";
            }
        } else {
            $error = "Hesabınız pasif durumda. Lütfen yönetici ile iletişime geçin.";
        }
    } else {
        $error = "Geçersiz kullanıcı adı veya şifre";
    }
}
?>

<?php $pageTitle = "Giriş Yap"; include '../includes/header.php'; ?>

<div class="row">
    <form class="col-10 col-md-5 col-xl-3 position-absolute top-50 start-50 translate-middle" id="login" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <div class="d-flex justify-content-center align-items-center">
            <img src="/assets/images/logo.png" alt="Logo" class="mb-4" style="max-width: 150px;">
        </div>

        <div class="d-grid">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <div class="input-group flex-nowrap">
                <span class="input-group-text" id="addon-wrapping"><i class="bi bi-envelope-at-fill"></i></span>
                <input type="text" class="form-control" name="username" placeholder="Kullanıcı Adı" required>
            </div>
            <div class="input-group flex-nowrap mt-3">
                <span class="input-group-text" id="addon-wrapping"><i class="bi bi-key-fill"></i></span>
                <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Şifre" required>
                <button type="button" id="togglePassword" class="btn btn-secondary"><i class="bi bi-eye"></i></button>
            </div>
            <button type="submit" name="login" id="login" class="btn btn-success mt-3">Oturum aç</button>
        </div>
    </form>
</div>

<script>
    // Şifre Görünürlük işlevi
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('passwordInput');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                togglePassword.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                togglePassword.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
