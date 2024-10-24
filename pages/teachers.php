<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Yalnızca aktif öğretmenleri listele (is_active = 1)
$query = "SELECT id, first_name, last_name, title, photo FROM users WHERE role = 'teacher' AND is_active = 1";
$result = mysqli_query($baglanti, $query);

?>

<?php $pageTitle = "Öğretmenler"; include '../includes/header.php'; ?>
<div class="container">
    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="col-12 col-md-6 col-xl-4 my-2">
                <div class="card">
                    <div class="row g-0">
                        <div class="col-4">
                            <img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="Profil Fotoğrafı" class="img-fluid rounded-start">
                        </div>
                        <div class="col-8">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($row['title']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-start">
                        <?php if ($_SESSION['role'] == 'admin') { ?>
                            <a href="timetable.php?teacher_id=<?php echo $row['id']; ?>" class="btn btn-success  btn-sm me-2"><i class="bi bi-calendar-heart-fill"></i> Sabit P.</a>
                        <?php } ?>    
                        <a href="weeklytable.php?teacher_id=<?php echo $row['id']; ?>" class="btn btn-success  btn-sm me-2"><i class="bi bi-calendar-event-fill"></i> Ders P.</a>
                        <a href="weeklytable_detail.php?teacher_id=<?php echo $row['id']; ?>" class="btn btn-secondary  btn-sm"><i class="bi bi-eye-fill"></i> Görüntüle</a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
