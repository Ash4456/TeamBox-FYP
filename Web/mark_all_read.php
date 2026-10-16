<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

$stmt = mysqli_prepare($conn, "
    UPDATE `notifications`
    SET is_read = 1
    WHERE user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// 回上一页，或去 notifications 页
$back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'student_notifications.php';
header('Location: ' . $back);
exit;