<?php
session_start();
require_once '../includes/config.php';

$alert_message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : [];

    if ($teacher_id) {
        $baglanti->begin_transaction();
        try {
            // Mevcut timetable verilerini sil
            $stmt_delete = $baglanti->prepare("DELETE FROM timetable WHERE teacher_id = ?");
            if (!$stmt_delete) {
                throw new Exception("Silme işlemi hazırlanamıyor: " . $baglanti->error);
            }
            $stmt_delete->bind_param("i", $teacher_id);
            if (!$stmt_delete->execute()) {
                throw new Exception("Silme işlemi başarısız: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            // Eğer schedule boş değilse, yeni verileri ekleyin
            if (!empty($schedule)) {
                $stmt_insert = $baglanti->prepare("INSERT INTO timetable (teacher_id, student_id, day, hour) VALUES (?, ?, ?, ?)");
                if (!$stmt_insert) {
                    throw new Exception("Ekleme işlemi hazırlanamıyor: " . $baglanti->error);
                }

                foreach ($schedule as $day => $hours) {
                    foreach ($hours as $hour => $student_ids) {
                        if (!empty($student_ids)) {
                            foreach ($student_ids as $student_id) {
                                if (!empty($student_id)) {
                                    $stmt_insert->bind_param("iiss", $teacher_id, $student_id, $day, $hour);
                                    if (!$stmt_insert->execute()) {
                                        throw new Exception("Ekleme işlemi başarısız: " . $stmt_insert->error);
                                    }
                                }
                            }
                        }
                    }
                }

                $stmt_insert->close();
            }

            // İşlemler başarılı olursa commit yap
            $baglanti->commit();

            // Başarılı olduğunda yönlendirme
            header("Location: timetable.php?teacher_id=" . $teacher_id);
            exit();

        } catch (Exception $e) {
            $baglanti->rollback();
            echo "Hata: " . $e->getMessage();
        }
    } else {
        echo "Geçerli bir öğretmen ID'si ve program verisi sağlanmadı.";
    }
} else {
    echo "Geçersiz istek.";
}
