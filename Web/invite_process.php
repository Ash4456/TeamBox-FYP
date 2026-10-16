<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($conn) || !$conn) {
    die('Database connection not available. Check db.php.');
}

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

// =========================================================
// 取表單
// =========================================================
$groupID         = (int) (isset($_POST['groupID'])        ? $_POST['groupID']        : 0);
$inviteStudentNo = trim(isset($_POST['inviteStudentNo']) ? $_POST['inviteStudentNo'] : '');

if ($groupID === 0 || $inviteStudentNo === '') {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// =========================================================
// 1. 取自己的 student_id + 名字
// =========================================================
$inviterID   = 0;
$inviterName = '';

$stmt = mysqli_prepare($conn, "
    SELECT s.id AS student_id, u.username
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE s.user_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$rc) {
    header('Location: logout.php');
    exit;
}

$inviterID   = (int) $rc['student_id'];
$inviterName = $rc['username'];

// =========================================================
// 2. 找被邀請人（用 student_number 查）
// =========================================================
$inviteeID     = 0;
$inviteeUserID = 0;
$inviteeName   = '';

$stmt = mysqli_prepare($conn, "
    SELECT s.id AS student_id, s.user_id, u.username
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE s.student_number = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "s", $inviteStudentNo);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$rc) {
    header('Location: student_create_group.php?msg=notfound');
    exit;
}

$inviteeID     = (int) $rc['student_id'];
$inviteeUserID = (int) $rc['user_id'];
$inviteeName   = $rc['username'];

// 不能邀請自己
if ($inviteeID === $inviterID) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// =========================================================
// 3. 檢查組是否存在 + 是否仍可邀請 + 取組名
// =========================================================
$groupName       = '';
$groupStatus     = '';
$groupMaxMembers = 0;
$groupMemberCnt  = 0;
$groupPendingCnt = 0;

$stmt = mysqli_prepare($conn, "
    SELECT
        g.name,
        g.status,
        g.max_members,
        (SELECT COUNT(*) FROM `group_members` gm WHERE gm.group_id = g.id) AS member_count,
        (SELECT COUNT(*) FROM `invitations` i
            WHERE i.group_id = g.id AND i.status = 'Pending') AS pending_count
    FROM `groups` g
    WHERE g.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $groupID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$rc) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

$groupName       = $rc['name'];
$groupStatus     = $rc['status'];
$groupMaxMembers = (int) $rc['max_members'];
$groupMemberCnt  = (int) $rc['member_count'];
$groupPendingCnt = (int) $rc['pending_count'];

// 只有 Recruiting 可以邀請
if ($groupStatus !== 'Recruiting') {
    header('Location: student_create_group.php?msg=locked&group_id=' . $groupID);
    exit;
}

// =========================================================
// 【新增】檢查「目前成員 + Pending 邀請」有冇超過 max
// =========================================================
if (($groupMemberCnt + $groupPendingCnt) >= $groupMaxMembers) {
    header('Location: student_create_group.php?msg=full&group_id=' . $groupID);
    exit;
}

// =========================================================
// 4. 檢查是否已經邀請過（Pending / Accepted）
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT id, status FROM `invitations`
    WHERE group_id = ? AND invitee_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $inviteeID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$exists = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($exists) {
    // 如果係 Pending → 唔重複邀請
    // 如果係 Accepted → 已經係組員
    // 如果係 Rejected / Cancelled → 可以再邀請（重新 INSERT）
    if ($exists['status'] === 'Pending' || $exists['status'] === 'Accepted') {
        header('Location: student_create_group.php?msg=error&group_id=' . $groupID);
        exit;
    }

    // Rejected / Cancelled → 更新返做 Pending
    $stmt = mysqli_prepare($conn, "
        UPDATE `invitations`
        SET status = 'Pending', sent_at = NOW(), responded_at = NULL
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $exists['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $newInviteID = (int) $exists['id'];

} else {
    // =========================================================
    // 5. 寫邀請（invitations）
    // =========================================================
    $stmt = mysqli_prepare($conn, "
        INSERT INTO `invitations` (group_id, inviter_id, invitee_id, status)
        VALUES (?, ?, ?, 'Pending')
    ");
    mysqli_stmt_bind_param($stmt, "iii", $groupID, $inviterID, $inviteeID);
    mysqli_stmt_execute($stmt);
    $newInviteID = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
}

// =========================================================
// 6. 寫通知（notifications）
// =========================================================
$notifyTitle   = 'Group Invitation';
$notifyMessage = $inviterName . ' invited you to join group "' . $groupName . '".';
$notifyLink    = 'accept_invite.php?id=' . $newInviteID;

$stmt = mysqli_prepare($conn, "
    INSERT INTO `notifications` (user_id, type, title, message, link, is_read)
    VALUES (?, 'team', ?, ?, ?, 0)
");
mysqli_stmt_bind_param(
    $stmt,
    "isss",
    $inviteeUserID,
    $notifyTitle,
    $notifyMessage,
    $notifyLink
);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 7. 回頁面
// =========================================================
header('Location: student_create_group.php?msg=invited&group_id=' . $groupID);
exit;