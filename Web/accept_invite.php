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

// 取邀請
$stmt = mysqli_prepare($conn, "
    SELECT id, group_id, invitee_id, status
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

$groupID = (int) $inv['group_id'];

// =========================================================
// 檢查 group 是否仍然可以加入
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT
        g.id,
        g.name          AS group_name,
        g.status,
        g.max_members,
        g.project_id,
        (SELECT COUNT(*) FROM `group_members` gm WHERE gm.group_id = g.id) AS member_count
    FROM `groups` g
    WHERE g.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $groupID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$grp = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$grp) {
    header('Location: student_notifications.php?msg=error');
    exit;
}

if ($grp['status'] !== 'Recruiting'
    || (int)$grp['member_count'] >= (int)$grp['max_members']) {

    // 標記為 Cancelled
    $stmt = mysqli_prepare($conn, "
        UPDATE `invitations`
        SET status = 'Cancelled', responded_at = NOW()
        WHERE id = ? AND status = 'Pending'
    ");
    mysqli_stmt_bind_param($stmt, "i", $inviteID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        UPDATE `notifications` SET is_read = 1
        WHERE user_id = ? AND link = ?
    ");
    $link = 'accept_invite.php?id=' . $inviteID;
    mysqli_stmt_bind_param($stmt, "is", $userID, $link);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: student_notifications.php?msg=error');
    exit;
}

// =========================================================
// 1. 更新邀請 → Accepted
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `invitations`
    SET status = 'Accepted', responded_at = NOW()
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $inviteID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 2. 加進 group_members
// =========================================================
$stmt = mysqli_prepare($conn, "
    INSERT IGNORE INTO `group_members` (group_id, student_id, role)
    VALUES (?, ?, 'member')
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $myStudentID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 3. 檢查滿員 → 自動 Lock → Waiting_Send
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT
        g.id,
        g.name          AS group_name,
        g.status,
        g.max_members,
        g.project_id,
        (SELECT COUNT(*) FROM `group_members` gm WHERE gm.group_id = g.id) AS member_count
    FROM `groups` g
    WHERE g.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $groupID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$grp = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($grp
    && $grp['status'] === 'Recruiting'
    && (int)$grp['member_count'] >= (int)$grp['max_members']) {

    // 3.1 自動鎖定 → Waiting_Send
    $stmt = mysqli_prepare($conn, "
        UPDATE `groups`
        SET status = 'Waiting_Send'
        WHERE id = ? AND status = 'Recruiting'
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 3.2 同步 project
    $stmt = mysqli_prepare($conn, "
        UPDATE `projects`
        SET status = 'Waiting_Send'
        WHERE id = ? AND status = 'Recruiting'
    ");
    mysqli_stmt_bind_param($stmt, "i", $grp['project_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 3.3 取消所有 Pending 邀請
    $stmt = mysqli_prepare($conn, "
        UPDATE `invitations`
        SET status = 'Cancelled', responded_at = NOW()
        WHERE group_id = ? AND status = 'Pending'
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 3.4 通知所有組員
    $stmt = mysqli_prepare($conn, "
        SELECT s.user_id
        FROM `group_members` gm
        JOIN `students` s ON s.id = gm.student_id
        WHERE gm.group_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    $rsMembers = mysqli_stmt_get_result($stmt);

    $memberUserIDs = array();
    while ($m = mysqli_fetch_assoc($rsMembers)) {
        $memberUserIDs[] = (int)$m['user_id'];
    }
    mysqli_stmt_close($stmt);

    if (count($memberUserIDs) > 0) {
        $notifyTitle   = 'Team Full';
        $notifyMessage = 'Your group "' . $grp['group_name'] . '" is now full. Waiting for the group creator to send it to the supervisor before voting starts.';
        $notifyLink    = 'student_create_group.php?group_id=' . $groupID;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO `notifications` (user_id, type, title, message, link, is_read)
            VALUES (?, 'team', ?, ?, ?, 0)
        ");
        foreach ($memberUserIDs as $uid) {
            mysqli_stmt_bind_param($stmt, "isss", $uid, $notifyTitle, $notifyMessage, $notifyLink);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// =========================================================
// 4. 通知已讀
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `notifications` SET is_read = 1
    WHERE user_id = ? AND link = ?
");
$link = 'accept_invite.php?id=' . $inviteID;
mysqli_stmt_bind_param($stmt, "is", $userID, $link);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 5. 跳去 Current Group（原本係 student_notifications.php）
// =========================================================
header('Location: student_create_group.php?msg=accepted&group_id=' . $groupID);
exit;