<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID  = (int) $_SESSION['userID'];
$groupID = (int) (isset($_POST['groupID']) ? $_POST['groupID'] : 0);

if ($groupID === 0) {
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

// 取 group
$stmt = mysqli_prepare($conn, "
    SELECT id, name, status, creator_id, leader_id, project_id
    FROM `groups`
    WHERE id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $groupID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$grp = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$grp) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// 檢查 1：只有 Recruiting / Waiting_Send 可以離開
if (!in_array($grp['status'], array('Recruiting', 'Waiting_Send'), true)) {
    header('Location: student_create_group.php?msg=cantleave&group_id=' . $groupID);
    exit;
}

// 檢查 2：Creator 唔可以離開
if ((int)$grp['creator_id'] === $myStudentID) {
    header('Location: student_create_group.php?msg=creatorcannotleave&group_id=' . $groupID);
    exit;
}

// 檢查 3：確認自己係組員
$stmt = mysqli_prepare($conn, "
    SELECT 1 FROM `group_members`
    WHERE group_id = ? AND student_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $myStudentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$isMember = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$isMember) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// =========================================================
// 【新增】1. 將自己嘅 invitation 標記為 Leave
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `invitations`
    SET status = 'Leave', responded_at = NOW()
    WHERE group_id = ? AND invitee_id = ? AND status = 'Accepted'
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $myStudentID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 【新增】2. 取消離開者發出嘅所有 Pending 邀請
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `invitations`
    SET status = 'Cancelled', responded_at = NOW()
    WHERE group_id = ? AND inviter_id = ? AND status = 'Pending'
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $myStudentID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 3. 移出 group_members
// =========================================================
$stmt = mysqli_prepare($conn, "
    DELETE FROM `group_members`
    WHERE group_id = ? AND student_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $myStudentID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 4. 如果係 leader → 清除 leader_id
// =========================================================
if ((int)$grp['leader_id'] === $myStudentID) {
    $stmt = mysqli_prepare($conn, "
        UPDATE `groups`
        SET leader_id = NULL
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// =========================================================
// 5. 如果 group 係 Waiting_Send → 退回 Recruiting
// =========================================================
if ($grp['status'] === 'Waiting_Send') {
    $stmt = mysqli_prepare($conn, "
        UPDATE `groups`
        SET status = 'Recruiting'
        WHERE id = ? AND status = 'Waiting_Send'
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        UPDATE `projects`
        SET status = 'Recruiting'
        WHERE id = ? AND status = 'Waiting_Send'
    ");
    mysqli_stmt_bind_param($stmt, "i", $grp['project_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// =========================================================
// 6. 通知 creator
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT u.id AS user_id
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE s.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $grp['creator_id']);
mysqli_stmt_execute($stmt);
$rsCreator = mysqli_stmt_get_result($stmt);
$creatorUser = mysqli_fetch_assoc($rsCreator);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT u.username
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE s.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $myStudentID);
mysqli_stmt_execute($stmt);
$rsMe = mysqli_stmt_get_result($stmt);
$me = mysqli_fetch_assoc($rsMe);
mysqli_stmt_close($stmt);

if ($creatorUser && $me) {
    $notifyTitle   = 'Member Left Group';
    $notifyMessage = $me['username'] . ' has left your group "' . $grp['name'] . '".';
    $notifyLink    = 'student_create_group.php?group_id=' . $groupID;

    $stmt = mysqli_prepare($conn, "
        INSERT INTO `notifications` (user_id, type, title, message, link, is_read)
        VALUES (?, 'team', ?, ?, ?, 0)
    ");
    $cUID = (int)$creatorUser['user_id'];
    mysqli_stmt_bind_param($stmt, "isss", $cUID, $notifyTitle, $notifyMessage, $notifyLink);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location: student_create_group.php?msg=left&group_id=' . $groupID);
exit;