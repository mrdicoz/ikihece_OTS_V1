<?php
session_start();

// Kullanıcının oturum açıp açmadığını kontrol et
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(function(registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                }).catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        }

        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            // Yükleme olayını kaydediyoruz
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('installButton').style.display = 'inline-block';
        });

        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Kullanıcı uygulamayı yüklemeyi kabul etti');
                    } else {
                        console.log('Kullanıcı uygulamayı yüklemeyi reddetti');
                    }
                    deferredPrompt = null;
                });
            }
        }
    </script>
</head>
<body>
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="text-center">
            <img src="assets/images/logo.png" alt="Logo" class="mb-4" style="max-width: 150px;">
            <h1 class="h3 mb-3">Öğrenci Yönetim Sistemi</h1>
            <p class="mb-4">OTS Web App'a hoş geldiniz</p>
            <div class="d-grid gap-2">
                <a href="pages/login.php" class="btn btn-success">Browserdan Devam Et</a>
                <button id="installButton" class="btn btn-success" style="display: none;" onclick="installApp()">Uygulamayı Yükle</button>
            </div>
        </div>
    </div>
</body>
</html>
