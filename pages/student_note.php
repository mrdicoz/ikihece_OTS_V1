<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'secretary')) {
    header("Location: login.php");
    exit();
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($student_id === 0) {
    echo "Geçersiz öğrenci ID'si.";
    exit();
}

// Not ekleme işlemi sadece öğretmenler için
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION['role'] == 'teacher') {
    $note = $_POST['note'];
    $teacher_id = $_SESSION['user_id']; // Şu anki öğretmen ID'si

    $stmt = $baglanti->prepare("INSERT INTO notes (teacher_id, student_id, note) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $teacher_id, $student_id, $note);

    if ($stmt->execute()) {
        header("Location: student_note.php?id=" . $student_id);
        exit();
    } else {
        echo "Hata: " . $stmt->error;
    }
}

// Öğrenci notlarını getirme
$query = "SELECT notes.*, users.first_name, users.last_name FROM notes INNER JOIN users ON notes.teacher_id = users.id WHERE notes.student_id = ? ORDER BY notes.created_at DESC";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$notes_result = $stmt->get_result();

$query = "SELECT * FROM students WHERE id = ?";
$stmt = $baglanti->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "Öğrenci bulunamadı.";
    exit();
}

?>

<?php $pageTitle = "Öğrenci Notları"; include '../includes/header.php'; ?>
<h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> İçin Notlar</h2>

<?php if ($_SESSION['role'] == 'teacher'): ?>
<!-- Not ekleme formu sadece öğretmenler için -->
<form action="student_note.php?id=<?php echo $student_id; ?>" method="post">
    <div class="mb-3">
        <label for="note" class="form-label">Yeni Not Ekle</label>
        <textarea class="form-control" id="note" name="note" rows="3" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Not Ekle</button>
</form>
<?php endif; ?>

<h3>Mevcut Notlar</h3>
<?php if ($notes_result->num_rows > 0): ?>
    <ul class="list-group">
        <?php while($note = $notes_result->fetch_assoc()): ?>
            <li class="list-group-item">
                <strong><?php echo htmlspecialchars($note['first_name'] . ' ' . $note['last_name']); ?>:</strong>
                <p><?php echo htmlspecialchars($note['note']); ?></p>
                <small><?php echo $note['created_at']; ?></small>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>Henüz not eklenmemiş.</p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
