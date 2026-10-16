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

// 取 group + project + supervisor
$stmt = mysqli_prepare($conn, "
    SELECT
        g.id, g.name, g.status,
        g.creator_id, g.leader_id,
        g.project_id, g.max_members,
        p.name           AS project_name,
        p.supervisor_id,
        t.user_id        AS supervisor_user_id,
        tu.username      AS supervisor_name,
        (SELECT COUNT(*) FROM `group_members` gm WHERE gm.group_id = g.id) AS member_count
    FROM `groups` g
    LEFT JOIN `projects` p  ON p.id = g.project_id
    LEFT JOIN `teachers` t  ON t.id = p.supervisor_id
    LEFT JOIN `users`    tu ON tu.id = t.user_id
    WHERE g.id = ?
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

// 必須係 Waiting_Send
if ($grp['status'] !== 'Waiting_Send') {
    header('Location: student_create_group.php?msg=notwaiting&group_id=' . $groupID);
    exit;
}

// 必須已滿員
if ((int)$grp['member_count'] < (int)$grp['max_members']) {
    header('Location: student_create_group.php?msg=notfull&group_id=' . $groupID);
    exit;
}

// 必須係 creator
if ((int)$grp['creator_id'] !== $myStudentID) {
    header('Location: student_create_group.php?msg=notcreator&group_id=' . $groupID);
    exit;
}

// =========================================================
// 1. 寫入 group_requests
// =========================================================
$stmt = mysqli_prepare($conn, "
    INSERT INTO `group_requests` (group_name, project_id, submitted_by, status)
    VALUES (?, ?, ?, 'Accepted')
");
mysqli_stmt_bind_param($stmt, "sii", $grp['name'], $grp['project_id'], $myStudentID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 2. 更新 group → Voting
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `groups`
    SET status = 'Voting', formed_at = NOW()
    WHERE id = ? AND status = 'Waiting_Send'
");
mysqli_stmt_bind_param($stmt, "i", $groupID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 3. 更新 project → Voting
// =========================================================
$stmt = mysqli_prepare($conn, "
    UPDATE `projects`
    SET status = 'Voting'
    WHERE id = ? AND status = 'Waiting_Send'
");
mysqli_stmt_bind_param($stmt, "i", $grp['project_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 4. 寫入 activities
// =========================================================
$stmt = mysqli_prepare($conn, "
    INSERT INTO `activities` (project_id, group_id, actor_id, action, detail)
    VALUES (?, ?, ?, 'submitted', ?)
");
$detail = 'submitted group "' . $grp['name'] . '" to supervisor ' . $grp['supervisor_name'];
mysqli_stmt_bind_param($stmt, "iiis", $grp['project_id'], $groupID, $userID, $detail);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 5. 通知所有組員（開始投票）
// =========================================================
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
    $notifyTitle   = 'Voting Started';
    $notifyMessage = 'Group "' . $grp['name'] . '" has been sent to ' . $grp['supervisor_name'] . '. Please vote for the group leader.';
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

// =========================================================
// 6. 通知導師
// =========================================================
if (!empty($grp['supervisor_user_id'])) {
    $notifyTitle   = 'New Group Submission';
    $notifyMessage = 'Group "' . $grp['name'] . '" has submitted for project "' . $grp['project_name'] . '".';
    $notifyLink    = 'teacher_teams.php';

    $stmt = mysqli_prepare($conn, "
        INSERT INTO `notifications` (user_id, type, title, message, link, is_read)
        VALUES (?, 'project', ?, ?, ?, 0)
    ");
    $supUID = (int)$grp['supervisor_user_id'];
    mysqli_stmt_bind_param($stmt, "isss", $supUID, $notifyTitle, $notifyMessage, $notifyLink);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location: student_create_group.php?msg=sent&group_id=' . $groupID);
exit;