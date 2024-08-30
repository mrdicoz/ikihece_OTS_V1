<?php
session_start();
require_once '../includes/config.php';

function handleFileUpload($fileKey) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] != UPLOAD_ERR_OK) {
        return 'uploads/user.jpg'; // Varsayılan resim
    }

    $file = $_FILES[$fileKey]['name'];
    $fileTmp = $_FILES[$fileKey]['tmp_name'];
    $fileExt = pathinfo($file, PATHINFO_EXTENSION);
    $fileName = "uploads/sergem_" . date("dmYHis") . "." . $fileExt;

    if (move_uploaded_file($fileTmp, $fileName)) {
        return $fileName;
    } else {
        return 'uploads/user.jpg'; // Varsayılan resim
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : null;
    $student_photo = handleFileUpload('student_photo');
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $tc_no = $_POST['tc_no'];
    $gender = $_POST['gender'];
    $disability_type = $_POST['disability_type'];
    $education_program = $_POST['education_program'];
    $birthdate = $_POST['birthdate'];
    $birthplace = $_POST['birthplace'];
    $student_info = $_POST['student_info'];
    $guardian_photo = handleFileUpload('guardian_photo');
    $guardian_name = $_POST['guardian_name'];
    $guardian_phone = $_POST['guardian_phone'];
    $address = $_POST['address'];
    $distance = $_POST['distance'];
    $transportation = $_POST['transportation'];
    $location = $_POST['location'];
    $guardian_info = $_POST['guardian_info'];
    $second_contact_name = $_POST['second_contact_name'];
    $second_contact_phone = $_POST['second_contact_phone'];
    $days = implode(',', $_POST['days']);
    $hours = implode(',', $_POST['hours']);

    if ($student_id) {
        $stmt = $baglanti->prepare("UPDATE students SET student_photo = ?, first_name = ?, last_name = ?, tc_no = ?, gender = ?, disability_type = ?, education_program = ?, birthdate = ?, birthplace = ?, student_info = ?, guardian_photo = ?, guardian_name = ?, guardian_phone = ?, address = ?, distance = ?, transportation = ?, location = ?, guardian_info = ?, second_contact_name = ?, second_contact_phone = ?, days = ?, hours = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssssssssssssssi", $student_photo, $first_name, $last_name, $tc_no, $gender, $disability_type, $education_program, $birthdate, $birthplace, $student_info, $guardian_photo, $guardian_name, $guardian_phone, $address, $distance, $transportation, $location, $guardian_info, $second_contact_name, $second_contact_phone, $days, $hours, $student_id);
    } else {
        $stmt = $baglanti->prepare("INSERT INTO students (student_photo, first_name, last_name, tc_no, gender, disability_type, education_program, birthdate, birthplace, student_info, guardian_photo, guardian_name, guardian_phone, address, distance, transportation, location, guardian_info, second_contact_name, second_contact_phone, days, hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssssss", $student_photo, $first_name, $last_name, $tc_no, $gender, $disability_type, $education_program, $birthdate, $birthplace, $student_info, $guardian_photo, $guardian_name, $guardian_phone, $address, $distance, $transportation, $location, $guardian_info, $second_contact_name, $second_contact_phone, $days, $hours);
    }

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Hata: " . $stmt->error;
    }
}
?>
