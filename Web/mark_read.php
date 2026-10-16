<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID  = (int) $_SESSION['userID'];
$notifyID = (int) (isset($_GET['id']) ? $_GET['id'] : 0);

if ($notifyID > 0) {
    $stmt = mysqli_prepare($conn, "
        UPDATE `notifications`
        SET is_read = 1
        WHERE id = ? AND user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $notifyID, $userID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location: student_notifications.php');
exit;
