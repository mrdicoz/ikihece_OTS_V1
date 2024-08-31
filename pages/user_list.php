<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$query = "SELECT id, username, role, first_name, last_name, title, is_active, photo FROM users";
$result = mysqli_query($baglanti, $query);

function getUserPhoto($photoPath) {
    if (empty($photoPath) || !file_exists($photoPath)) {
        return '/assets/images/user.jpg'; // Varsayılan fotoğraf
    }
    return $photoPath;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];

    try {
        if ($delete_user_id != 1) {
            $stmt = $baglanti->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $delete_user_id);

            if ($stmt->execute()) {
                header("Location: user_list.php");
                exit();
            }
        } else {
            $error = "ID 1 olan kullanıcı silinemez.";
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $error = "Bu kullanıcı eklemiş olduğu notlar sebebi ile silinemiyor.";
        } else {
            $error = "Kullanıcı silinirken bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<?php $pageTitle = "Kullanıcı Listesi"; include '../includes/header.php'; ?>
<h3>Kullanıcı Listesi</h3>

<?php if (isset($error)) { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<div class="table-responsive">
    <table class="table table-striped table-light align-middle table-hover">
        <thead>
            <tr>
                <th class="col-1">Fotoğraf</th>
                <th class="col-2">Adı</th>
                <th class="col-2">Soyadı</th>
                <th class="col-2 d-none d-lg-table-cell">Kullanıcı Adı</th>
                <th class="col-2 d-none d-lg-table-cell">Ünvan</th>
                <th class="col-1 d-none d-lg-table-cell">Durum</th>
                <th class="col-2" style="text-align:right;">İşlemler</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td class="col-1"><img src="<?php echo htmlspecialchars(getUserPhoto($row['photo'])); ?>" alt="Kullanıcı Fotoğrafı" width="50" height="50" class="img-thumbnail"></td>
                    <td class="col-2"><?php echo htmlspecialchars($row['first_name'] ?? ''); ?></td>
                    <td class="col-2"><?php echo htmlspecialchars($row['last_name'] ?? ''); ?></td>
                    <td class="col-2 d-none d-lg-table-cell"><?php echo htmlspecialchars($row['username'] ?? ''); ?></td>
                    <td class="col-2 d-none d-lg-table-cell"><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                    <td class="col-1 d-none d-lg-table-cell">
                        <?php if ($row['is_active'] == 1) { ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php } else { ?>
                            <span class="badge bg-secondary">Pasif</span>
                        <?php } ?>
                    </td>
                    <td class="col-2" style="text-align:right;">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Small button group">
                            <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-gear"></i></a>
                            <?php if ($row['id'] != 1) { ?>
                            <form action="user_list.php" method="post" style="display:inline;">
                                <input type="hidden" name="delete_user_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php } else { ?>
                                <button class="btn btn-secondary btn-sm" disabled><i class="bi bi-exclamation-circle-fill"></i></button>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
