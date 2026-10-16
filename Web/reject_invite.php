<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID   = (int) $_SESSION['userID'];
$inviteID = (int) (isset($_GET['id']) ? $_GET['id'] : 0);

if ($inviteID === 0) {
    header('Location: student_notifications.php?msg=error');
    exit;
}

// 取自己 student_id
$stmt = mysqli_prepare($conn, "SELECT id FROM `students` WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$rc) {
    header('Location: logout.php');
    exit;
}
$myStudentID = (int) $rc['id'];

// 取邀请
$stmt = mysqli_prepare($conn, "
    SELECT id, invitee_id, status
    FROM `invitations`
    WHERE id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $inviteID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$inv = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$inv || (int)$inv['invitee_id'] !== $myStudentID) {
    header('Location: student_notifications.php?msg=error');
    exit;
}

if ($inv['status'] !== 'Pending') {
    header('Location: student_notifications.php?msg=error');
    exit;
}

// 更新状态为 Rejected
$stmt = mysqli_prepare($conn, "
    UPDATE `invitations`
    SET status = 'Rejected', responded_at = NOW()
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $inviteID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// 把通知标为已读
$stmt = mysqli_prepare($conn, "
    UPDATE `notifications`
    SET is_read = 1
    WHERE user_id = ? AND link = ?
");
$link = 'accept_invite.php?id=' . $inviteID;
mysqli_stmt_bind_param($stmt, "is", $userID, $link);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header('Location: student_notifications.php?msg=rejected');
exit;
