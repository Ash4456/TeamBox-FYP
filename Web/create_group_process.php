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
// 1. 取 student_id
// =========================================================
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
$studentID = (int) $rc['id'];

// =========================================================
// 2. 取表单
// =========================================================
$groupName  = trim(isset($_POST['newGroupName'])    ? $_POST['newGroupName']    : '');
$courseID   = (int) (isset($_POST['courseID'])      ? $_POST['courseID']        : 0);
$projectID  = (int) (isset($_POST['projectID'])     ? $_POST['projectID']       : 0);
$maxMembers = (int) (isset($_POST['newMaxMembers']) ? $_POST['newMaxMembers']   : 4);

if ($groupName === '' || $courseID === 0 || $projectID === 0 || $maxMembers < 2) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// =========================================================
// 3. 验证学生选了这门课
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT 1 FROM `course_enrollments`
    WHERE course_id = ? AND student_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $courseID, $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$enrolled = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$enrolled) {
    header('Location: student_create_group.php?msg=notenrolled');
    exit;
}

// =========================================================
// 4. 验证 project 属于该 course
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT 1 FROM `projects`
    WHERE id = ? AND course_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $projectID, $courseID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$validProject = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$validProject) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// =========================================================
// 5. 建组（creator_id = 自己）
// =========================================================
$stmt = mysqli_prepare($conn, "
    INSERT INTO `groups`
        (project_id, course_id, name, leader_id, creator_id, max_members, status)
    VALUES (?, ?, ?, NULL, ?, ?, 'Recruiting')
");
mysqli_stmt_bind_param(
    $stmt, "iisii",
    $projectID, $courseID, $groupName,
    $studentID, $maxMembers
);
mysqli_stmt_execute($stmt);
$groupID = (int) mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// =========================================================
// 6. 自己加进组（role = member）
// =========================================================
$stmt = mysqli_prepare($conn, "
    INSERT INTO `group_members` (group_id, student_id, role)
    VALUES (?, ?, 'member')
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $studentID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header('Location: student_create_group.php?msg=created&group_id=' . $groupID);
exit;