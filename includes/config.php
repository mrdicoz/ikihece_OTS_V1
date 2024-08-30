<?php
$baglanti = mysqli_connect("localhost", "u799021432_sergemserdivan", "Mrd25756922536+", "u799021432_sergemserdivan");

if (mysqli_connect_errno()) {
    die("Hata: " . mysqli_connect_error());
}

// UTF-8 karakter setini ayarla
mysqli_set_charset($baglanti, "utf8");
?>
