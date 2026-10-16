<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID   = (int) $_SESSION['userID'];
$inviteID = (int) (isset($_POST['inviteID']) ? $_POST['inviteID'] : 0);

if ($inviteID === 0) {
    header('Location: student_create_group.php?msg=error');
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

// 取邀請 + group 狀態
$stmt = mysqli_prepare($conn, "
    SELECT
        i.id,
        i.group_id,
        i.inviter_id,
        i.status AS inv_status,
        g.status AS group_status,
        g.max_members,
        (SELECT COUNT(*) FROM `group_members` gm WHERE gm.group_id = g.id) AS member_count
    FROM `invitations` i
    JOIN `groups` g ON g.id = i.group_id
    WHERE i.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $inviteID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$inv = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$inv) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// 只有 inviter 本人可以取消
if ((int)$inv['inviter_id'] !== $myStudentID) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// group 已 lock → 唔可以取消
if ($inv['group_status'] !== 'Recruiting') {
    header('Location: student_create_group.php?msg=locked&group_id=' . (int)$inv['group_id']);
    exit;
}

// 只有 Pending 可以取消
if ($inv['inv_status'] !== 'Pending') {
    header('Location: student_create_group.php?msg=error&group_id=' . (int)$inv['group_id']);
    exit;
}

// =========================================================
// 更新 → Cancelled
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `invitations`
    SET status = 'Cancelled', responded_at = NOW()
    WHERE id = ? AND status = 'Pending'
");
mysqli_stmt_bind_param($stmt, "i", $inviteID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header('Location: student_create_group.php?msg=cancel&group_id=' . (int)$inv['group_id']);
exit;