<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_voting_timeout.php';
/** @var mysqli $conn */

if (!isset($conn) || !$conn) {
    die('Database connection not available. Check db.php.');
}

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

$groupID     = (int) (isset($_POST['groupID'])     ? $_POST['groupID']     : 0);
$candidateID = (int) (isset($_POST['candidateID']) ? $_POST['candidateID'] : 0);

if ($groupID === 0 || $candidateID === 0) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// 取自己的 student_id
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

// 检查 group 状态
$stmt = mysqli_prepare($conn, "SELECT id, status FROM `groups` WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $groupID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$grp = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$grp || $grp['status'] !== 'Voting') {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// 我是不是组员
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

// 候选人是不是组员
$stmt = mysqli_prepare($conn, "
    SELECT 1 FROM `group_members`
    WHERE group_id = ? AND student_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $candidateID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$isCandidate = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$isCandidate) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// 有没有投过
$stmt = mysqli_prepare($conn, "
    SELECT id FROM `leader_votes`
    WHERE group_id = ? AND voter_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $groupID, $myStudentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$alreadyVoted = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($alreadyVoted) {
    header('Location: student_create_group.php?msg=error');
    exit;
}

// 写票
$stmt = mysqli_prepare($conn, "
    INSERT INTO `leader_votes` (group_id, voter_id, candidate_id)
    VALUES (?, ?, ?)
");
mysqli_stmt_bind_param($stmt, "iii", $groupID, $myStudentID, $candidateID);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// =========================================================
// 檢查係咪要結算
// 情況 1：所有人投完 → 立即計票
// 情況 2：未投完但 48 小時已過 → 都結算
// =========================================================
finalizeGroupVoting($conn, $groupID);

header('Location: student_create_group.php?msg=voted&group_id=' . $groupID);
exit;