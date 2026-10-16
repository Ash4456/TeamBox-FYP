<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(array('error' => 'not_logged_in'));
    exit;
}

$userID   = (int) $_SESSION['userID'];
$inviteID = (int) (isset($_GET['id']) ? $_GET['id'] : 0);

if ($inviteID === 0) {
    echo json_encode(array('error' => 'invalid_id'));
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
    echo json_encode(array('error' => 'not_student'));
    exit;
}
$myStudentID = (int) $rc['id'];

// 查邀請 + group + project + course + inviter
$stmt = mysqli_prepare($conn, "
    SELECT
        i.id            AS invite_id,
        i.status        AS invite_status,
        g.name          AS group_name,
        g.max_members,
        g.status        AS group_status,
        p.name          AS project_name,
        p.category      AS project_category,
        c.code          AS course_code,
        c.name          AS course_name,
        inviter_u.username AS inviter_name
    FROM `invitations` i
    JOIN `groups` g   ON g.id = i.group_id
    LEFT JOIN `projects` p ON p.id = g.project_id
    LEFT JOIN `courses`  c ON c.id = g.course_id
    JOIN `students` inviter_s ON inviter_s.id = i.inviter_id
    JOIN `users`    inviter_u ON inviter_u.id = inviter_s.user_id
    WHERE i.id = ?
      AND i.invitee_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $inviteID, $myStudentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$inv = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$inv) {
    echo json_encode(array('error' => 'not_found'));
    exit;
}

echo json_encode($inv);
