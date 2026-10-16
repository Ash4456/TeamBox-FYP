<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($conn) || !$conn) {
    echo json_encode(array('name' => ''));
    exit;
}

$studentNo = isset($_GET['student_no']) ? trim($_GET['student_no']) : '';

if ($studentNo === '') {
    echo json_encode(array('name' => ''));
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT u.username
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE s.student_number = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "s", $studentNo);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($rc) {
    echo json_encode(array('name' => $rc['username']));
} else {
    echo json_encode(array('name' => ''));
}
