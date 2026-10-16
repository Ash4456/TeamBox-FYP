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
// Unread notifications
// =========================================================
$unreadNotifications = 0;

$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `notifications`
    WHERE user_id = ?
      AND is_read = 0
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$unreadNotifications = (int) $rc['c'];
mysqli_stmt_close($stmt);

// =========================================================
// 1. Get student_id
// =========================================================
$studentID = 0;

$stmt = mysqli_prepare($conn, "SELECT id FROM `students` WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($rc) {
    $studentID = (int) $rc['id'];
} else {
    header('Location: logout.php');
    exit;
}

// =========================================================
// 2. Get all groups this student has joined
// =========================================================
$myGroups = array();

$stmt = mysqli_prepare($conn, "
    SELECT
        g.id            AS group_id,
        g.name          AS group_name,
        g.status,
        g.max_members,
        g.project_id,
        g.course_id,
        g.leader_id,
        g.creator_id,
        g.created_at,
        p.name          AS project_name,
        p.supervisor_id AS supervisor_id,
        t.user_id       AS supervisor_user_id,
        tu.username     AS supervisor_name,
        c.code          AS course_code,
        c.name          AS course_name,
        (SELECT COUNT(*) FROM `group_members` gm2 WHERE gm2.group_id = g.id) AS member_count
    FROM `group_members` gm
    JOIN `groups` g ON g.id = gm.group_id
    LEFT JOIN `projects` p  ON p.id = g.project_id
    LEFT JOIN `teachers` t  ON t.id = p.supervisor_id
    LEFT JOIN `users`    tu ON tu.id = t.user_id
    LEFT JOIN `courses`  c  ON c.id = g.course_id
    WHERE gm.student_id = ?
      AND g.status <> 'Completed'
    ORDER BY g.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($rs)) {
    $myGroups[] = $row;
}
mysqli_stmt_close($stmt);

// =========================================================
// 3. Decide active group
// =========================================================
$activeGroupID = 0;

if (isset($_GET['group_id'])) {
    $activeGroupID = (int) $_GET['group_id'];
}

$myGroup = null;

if ($activeGroupID > 0) {
    foreach ($myGroups as $g) {
        if ((int)$g['group_id'] === $activeGroupID) {
            $myGroup = $g;
            break;
        }
    }
}

if (!$myGroup && count($myGroups) > 0) {
    $myGroup = $myGroups[0];
    $activeGroupID = (int) $myGroup['group_id'];
}

// =========================================================
// 3.5 檢查投票超時（48 小時）
// =========================================================
if ($myGroup && $myGroup['status'] === 'Voting') {
    require_once __DIR__ . '/check_voting_timeout.php';
    finalizeGroupVoting($conn, (int)$myGroup['group_id']);

    $stmtRefresh = mysqli_prepare($conn, "
        SELECT
            g.id            AS group_id,
            g.name          AS group_name,
            g.status,
            g.max_members,
            g.project_id,
            g.course_id,
            g.leader_id,
            g.creator_id,
            g.created_at,
            p.name          AS project_name,
            p.supervisor_id AS supervisor_id,
            t.user_id       AS supervisor_user_id,
            tu.username     AS supervisor_name,
            c.code          AS course_code,
            c.name          AS course_name,
            (SELECT COUNT(*) FROM `group_members` gm2 WHERE gm2.group_id = g.id) AS member_count
        FROM `groups` g
        LEFT JOIN `projects` p  ON p.id = g.project_id
        LEFT JOIN `teachers` t  ON t.id = p.supervisor_id
        LEFT JOIN `users`    tu ON tu.id = t.user_id
        LEFT JOIN `courses`  c  ON c.id = g.course_id
        WHERE g.id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmtRefresh, "i", $myGroup['group_id']);
    mysqli_stmt_execute($stmtRefresh);
    $rsRefresh = mysqli_stmt_get_result($stmtRefresh);
    $refreshed = mysqli_fetch_assoc($rsRefresh);
    mysqli_stmt_close($stmtRefresh);

    if ($refreshed) {
        $myGroup = $refreshed;

        foreach ($myGroups as $idx => $g) {
            if ((int)$g['group_id'] === (int)$myGroup['group_id']) {
                $myGroups[$idx] = $myGroup;
                break;
            }
        }
    }
}

// =========================================================
// 4. Group members
// =========================================================
$groupMembers = array();

if ($myGroup) {
    $stmt = mysqli_prepare($conn, "
        SELECT
            s.id AS student_id,
            s.student_number,
            u.username,
            gm.role,
            gm.joined_at
        FROM `group_members` gm
        JOIN `students` s ON s.id = gm.student_id
        JOIN `users`    u ON u.id = s.user_id
        WHERE gm.group_id = ?
        ORDER BY gm.joined_at
    ");
    mysqli_stmt_bind_param($stmt, "i", $myGroup['group_id']);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($rs)) {
        $groupMembers[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =========================================================
// 5. Invitations
// =========================================================
$invitations = array();

if ($myGroup) {
    $stmt = mysqli_prepare($conn, "
        SELECT
            i.id,
            i.status,
            i.sent_at,
            i.responded_at,
            s.id AS invitee_student_id,
            s.student_number,
            u.username
        FROM `invitations` i
        JOIN `students` s ON s.id = i.invitee_id
        JOIN `users`    u ON u.id = s.user_id
        WHERE i.group_id = ?
        ORDER BY i.sent_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $myGroup['group_id']);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($rs)) {
        $invitations[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =========================================================
// 6. Vote results
// =========================================================
$voteResults = array();

if ($myGroup) {
    $stmt = mysqli_prepare($conn, "
        SELECT
            s.id AS student_id,
            s.student_number,
            u.username,
            COUNT(v.id) AS vote_count
        FROM `leader_votes` v
        JOIN `students` s ON s.id = v.candidate_id
        JOIN `users`    u ON u.id = s.user_id
        WHERE v.group_id = ?
        GROUP BY s.id, s.student_number, u.username
        ORDER BY vote_count DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $myGroup['group_id']);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($rs)) {
        $voteResults[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =========================================================
// 6.5 總票數 + 投票截止時間
// =========================================================
$totalVotes = 0;
foreach ($voteResults as $vr) {
    $totalVotes += (int)$vr['vote_count'];
}

$votingDeadline = null;

if ($myGroup && $myGroup['status'] === 'Voting') {
    $stmt = mysqli_prepare($conn, "
        SELECT formed_at FROM `groups` WHERE id = ? LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $myGroup['group_id']);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $rowTime = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);

    if ($rowTime && !empty($rowTime['formed_at'])) {
        $startTs  = strtotime($rowTime['formed_at']);
        $deadline = $startTs + 48 * 3600;
        $votingDeadline = date('d M Y, H:i', $deadline);
    }
}

// =========================================================
// 6.6 查自己投咗邊個
// =========================================================
$myVoteCandidateID = 0;

if ($myGroup) {
    $stmt = mysqli_prepare($conn, "
        SELECT candidate_id
        FROM `leader_votes`
        WHERE group_id = ? AND voter_id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "ii", $myGroup['group_id'], $studentID);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $rowMyVote = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);

    if ($rowMyVote) {
        $myVoteCandidateID = (int)$rowMyVote['candidate_id'];
    }
}

$hasVoted = ($myVoteCandidateID > 0);

// =========================================================
// 7. Helper functions
// =========================================================
if (!function_exists('initials')) {
    function initials($name) {
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

if (!function_exists('statusClass')) {
    function statusClass($status) {
        switch ($status) {
            case 'Accepted':  return 'status-accepted';
            case 'Rejected':  return 'status-rejected';
            case 'Cancelled': return 'status-cancelled';
            case 'Leave':     return 'status-leave';
            default:          return 'status-pending';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Group | TeamBox</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: #f5f5f5; color: #222; }

        .navbar {
            width: 100%;
            padding: 15px 30px;
            background: black;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
        }

        .navbar-top {
            position: relative;
            width: 100%;
            min-height: 65px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-area img { width: 65px; height: 65px; object-fit: contain; display: block; }
        .logo-area span { color: white; font-size: 30px; font-weight: bold; }

        .bell-link {
            position: absolute;
            right: 0; top: 50%;
            transform: translateY(-50%);
            display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px;
            border-radius: 50%;
            transition: background 0.3s;
        }
        .bell-link:hover { background: rgba(255, 255, 255, 0.15); }
        .bell-link img { width: 28px; height: 28px; object-fit: contain; display: block; }
        .bell-badge {
            position: absolute; top: 2px; right: 2px;
            min-width: 18px; height: 18px; padding: 0 5px;
            background: #d93025; color: white;
            border-radius: 9px;
            font-size: 11px; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid black;
        }

        .function-menu {
            width: 100%;
            display: flex; justify-content: center; align-items: center;
            flex-wrap: wrap; gap: 22px;
        }
        .nav-link {
            color: white; font-size: 15px; text-decoration: none;
            padding: 6px 2px; transition: color 0.3s;
        }
        .nav-link:hover { color: #bbbbbb; }
        .nav-link.active {
            color: white; font-weight: bold;
            border-bottom: 2px solid white;
        }
        .logout-link { color: #ff8888; }
        .logout-link:hover { color: #ff4444; }

        .main-content { width: 90%; max-width: 1250px; margin: 40px auto; }

        .page-heading {
            padding: 30px; margin-bottom: 25px;
            background: white; border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .page-heading h1 { margin-bottom: 10px; font-size: 35px; }
        .page-heading p { color: #666; font-size: 17px; line-height: 1.6; }

        .alert { padding: 14px 18px; margin-bottom: 20px; border-radius: 8px; font-size: 14px; border-left: 4px solid; }
        .alert-success { background: #eaf7ee; color: #176b2c; border-color: #2e9d4d; }
        .alert-error   { background: #fdeeee; color: #a21b1b; border-color: #d32f2f; }

        .section-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }

        .content-box {
            padding: 28px; background: white; border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .content-box h2 {
            padding-bottom: 15px; margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee; font-size: 23px;
        }

        .form-group { margin-bottom: 19px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-size: 14px; font-weight: bold; }
        .form-group input,
        .form-group select {
            width: 100%; height: 47px; padding: 0 13px;
            background: white; color: #222;
            border: 2px solid #cccccc; border-radius: 8px;
            font-size: 14px; outline: none; transition: 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus { border-color: black; }

        .create-group-section {
            padding: 28px; margin-bottom: 25px;
            background: white; border-radius: 15px;
            border-left: 6px solid black;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .create-group-section h2 {
            padding-bottom: 15px; margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee; font-size: 23px;
        }
        .create-group-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

        .primary-btn {
            width: 100%; height: 50px; padding: 0 20px; margin-top: 15px;
            background: black; color: white; border: 2px solid black;
            border-radius: 9px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }
        .primary-btn:hover { background: #333; }
        .primary-btn:disabled { background: #cccccc; border-color: #cccccc; color: #888; cursor: not-allowed; }

        .invite-form {
            padding: 18px; margin-bottom: 22px;
            background: #fafafa; border: 2px dashed #cccccc; border-radius: 12px;
        }
        .invite-form-row { display: grid; grid-template-columns: 1fr; gap: 14px; margin-bottom: 14px; }
        .invite-form-row.two-cols { grid-template-columns: 1fr 1fr; gap: 14px; }
        .invite-form .form-group { margin-bottom: 0; }

        .readonly-input {
            background: #f0f0f0 !important;
            color: #555 !important;
            cursor: not-allowed;
        }

        .invite-btn-main {
            width: 100%; height: 47px; padding: 0 20px;
            background: black; color: white; border: 2px solid black;
            border-radius: 8px; font-size: 15px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }
        .invite-btn-main:hover { background: #333; }

        .invite-history-title { font-size: 14px; font-weight: bold; color: #555; margin-bottom: 12px; }
        .invite-history { display: flex; flex-direction: column; gap: 10px; }

        .invite-item {
            padding: 14px; background: #fafafa;
            border: 1px solid #dddddd; border-radius: 10px;
            display: flex; align-items: center; gap: 12px;
        }
        .invite-avatar {
            width: 38px; height: 38px; flex-shrink: 0;
            background: black; color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 13px; font-weight: bold;
        }
        .invite-info { flex: 1; }
        .invite-info h3 { font-size: 15px; margin-bottom: 3px; }
        .invite-info p { color: #777; font-size: 12px; }

        .invite-status { padding: 5px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; white-space: nowrap; }
        .status-pending   { background: #fff1c2; color: #765900; }
        .status-accepted  { background: #d9f5df; color: #176b2c; }
        .status-rejected  { background: #ffe1e1; color: #a21b1b; }
        .status-cancelled { background: #eeeeee; color: #777; }
        .status-leave     { background: #ffe1e1; color: #a21b1b; }

        .cancel-invite-btn {
            background: white; color: #c62828;
            border: 2px solid #c62828; border-radius: 7px;
            padding: 5px 9px; font-size: 11px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
            white-space: nowrap;
        }
        .cancel-invite-btn:hover { background: #c62828; color: white; }

        .group-info { margin-bottom: 18px; color: #555; font-size: 14px; line-height: 1.8; }

        .status-badge {
            display: inline-block; padding: 6px 12px; margin-left: 5px;
            background: #e5f0ff; color: #245889; border-radius: 18px;
            font-size: 12px; font-weight: bold;
        }

        .member-item {
            padding: 14px 0; border-bottom: 1px solid #eeeeee;
            display: flex; justify-content: space-between; align-items: center; gap: 15px;
        }
        .member-item:last-child { border-bottom: none; }
        .member-item h3 { font-size: 16px; margin-bottom: 4px; }
        .member-item p { color: #777; font-size: 13px; }

        .member-status { padding: 6px 12px; border-radius: 18px; font-size: 12px; font-weight: bold; white-space: nowrap; }
        .status-accepted-member { background: #d9f5df; color: #176b2c; }
        .status-leader          { background: black; color: white; }
        .status-creator         { background: #245889; color: white; }

        .leave-group-btn {
            padding: 6px 12px;
            background: white;
            color: #c62828;
            border: 2px solid #c62828;
            border-radius: 7px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            white-space: nowrap;
        }
        .leave-group-btn:hover {
            background: #c62828;
            color: white;
        }

        /* =========================================================
           Vote Option
        ========================================================= */
        .vote-option {
            padding: 16px; margin-bottom: 12px;
            background: #fafafa; border: 2px solid #dddddd; border-radius: 10px;
            display: flex; align-items: center; gap: 14px;
            cursor: pointer; transition: 0.3s;
        }
        .vote-option:hover { border-color: black; background: white; }
        .vote-option-info { flex: 1; }
        .vote-option-info h3 { font-size: 16px; margin-bottom: 4px; }
        .vote-option-info p { color: #777; font-size: 13px; }

        /* 自訂 radio */
        .vote-option input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #999;
            border-radius: 50%;
            position: relative;
            cursor: pointer;
            flex-shrink: 0;
            background: white;
            transition: 0.2s;
        }

        .vote-option input[type="radio"]:checked {
            border-color: black;
            background: white;
        }

        .vote-option input[type="radio"]:checked::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: black;
            border-radius: 50%;
        }

        /* 已投票 → 全部 disabled（灰 + 淡） */
        .vote-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .vote-option.disabled input[type="radio"] {
            border-color: #ccc;
            cursor: not-allowed;
        }

        .vote-option.disabled input[type="radio"]:checked {
            border-color: #999;
            background: white;
        }

        .vote-option.disabled input[type="radio"]:checked::after {
            background: #999;
        }

        /* 已投票：你投嗰個 → 黑邊框 + 黑 radio + 黑字 */
        .vote-option.my-vote {
            opacity: 1;
            border-color: black;
            border-width: 3px;
            background: white;
            cursor: not-allowed;
        }

        .vote-option.my-vote input[type="radio"] {
            border-color: black !important;
            background: white !important;
            opacity: 1 !important;
            cursor: not-allowed;
        }

        .vote-option.my-vote input[type="radio"]:checked::after {
            background: black !important;
        }

        /* 已投嗰個：文字全部黑色 */
        .vote-option.my-vote .vote-option-info h3,
        .vote-option.my-vote .vote-option-info p {
            color: black !important;
        }

        /* =========================================================
           Vote Result
        ========================================================= */
        .result-item { padding: 14px 0; border-bottom: 1px solid #eeeeee; }
        .result-item:last-child { border-bottom: none; }
        .result-header {
            margin-bottom: 8px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .result-header strong { font-size: 15px; }
        .result-header span { font-size: 14px; font-weight: bold; }
        .result-bar {
            width: 100%; height: 12px; background: #dddddd;
            border-radius: 10px; overflow: hidden;
        }
        .result-value { height: 100%; background: black; border-radius: 10px; }

        .voting-countdown {
            padding: 14px 18px;
            margin-bottom: 18px;
            background: #fff8e1;
            border-left: 4px solid #e0a030;
            border-radius: 8px;
            font-size: 14px;
            color: #765900;
            line-height: 1.6;
        }
        .voting-countdown strong { color: #222; }

        .group-switcher {
            padding: 20px 24px;
            margin-bottom: 25px;
            background: white;
            border-radius: 15px;
            border-left: 6px solid #245889;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .group-switcher label { font-size: 15px; color: #333; font-weight: bold; }
        .group-switcher select {
            flex: 1; min-width: 260px; height: 45px; padding: 0 14px;
            background: white; color: #222;
            border: 2px solid #cccccc; border-radius: 8px;
            font-size: 14px; cursor: pointer; outline: none;
        }
        .group-switcher select:focus { border-color: black; }

        .muted { color: #777; font-size: 14px; }

        .invite-form.disabled {
            background: #f0f0f0;
            border-color: #cccccc;
            opacity: 0.65;
            pointer-events: none;
        }
        .invite-form.disabled input,
        .invite-form.disabled button {
            background: #e8e8e8 !important;
            color: #999 !important;
            border-color: #cccccc !important;
            cursor: not-allowed !important;
        }
        .invite-form.disabled input::placeholder { color: #aaaaaa; }

        .invite-locked-note {
            padding: 12px 16px;
            margin-bottom: 18px;
            background: #fdeeee;
            border-left: 4px solid #d32f2f;
            border-radius: 6px;
            color: #a21b1b;
            font-size: 13px;
            line-height: 1.5;
        }
        .invite-locked-note.success {
            background: #eaf7ee;
            border-left-color: #2e9d4d;
            color: #176b2c;
        }

        .invite-progress {
            margin-bottom: 18px;
            padding: 14px 18px;
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
        }
        .invite-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 13px;
            color: #555;
        }
        .invite-progress-header strong { color: #222; font-size: 14px; }
        .invite-progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        .invite-progress-fill {
            height: 100%;
            background: black;
            border-radius: 6px;
            transition: width 0.3s;
        }
        .invite-progress-fill.full { background: #2e9d4d; }
        .invite-progress-note {
            margin-top: 10px;
            font-size: 12px;
            text-align: right;
            color: #777;
        }

        @media (max-width: 1000px) {
            .create-group-grid { grid-template-columns: 1fr; gap: 0; }
        }
        @media (max-width: 900px) {
            .section-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 650px) {
            .navbar { padding: 15px; }
            .logo-area img { width: 55px; height: 55px; }
            .logo-area span { font-size: 26px; }

            .bell-link { width: 38px; height: 38px; }
            .bell-link img { width: 22px; height: 22px; }

            .function-menu { flex-direction: column; gap: 12px; }
            .main-content { width: 94%; margin: 25px auto; }
            .page-heading h1 { font-size: 28px; }

            .group-switcher { flex-direction: column; align-items: stretch; }
            .group-switcher select { min-width: 100%; }

            .invite-form-row.two-cols { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <div class="navbar-top">

        <div class="logo-area">
            <img src="TeamBox_logo_black.png" alt="TeamBox Logo">
            <span>TeamBox</span>
        </div>

        <a href="student_notifications.php" class="bell-link" title="Notifications">
            <img src="bell.png" alt="Notifications">

            <?php if ($unreadNotifications > 0): ?>
                <span class="bell-badge"><?php echo $unreadNotifications; ?></span>
            <?php endif; ?>
        </a>

    </div>

    <div class="function-menu">
        <a href="student_index.php" class="nav-link">Dashboard</a>
        <a href="student_create_group.php" class="nav-link active">Create Group</a>
        <a href="my_team.php" class="nav-link">My Team</a>
        <a href="student_task.php" class="nav-link">Tasks</a>
        <a href="student_files.php" class="nav-link">Files</a>
        <a href="student_chat.php" class="nav-link">Group Chat</a>
        <a href="student_contribution.php" class="nav-link">Contribution</a>
        <a href="student_notifications.php" class="nav-link">Notifications</a>
        <a href="student_profile.php" class="nav-link">Profile</a>
        <a href="logout.php" class="nav-link logout-link">Log Out</a>
    </div>

</nav>

<main class="main-content">

    <section class="page-heading">
        <h1>Create Group</h1>
        <p>
            Name your group, choose the course and project,
            invite classmates by Student ID, then send the group
            to your supervisor once it is full. Voting starts after that.
        </p>
    </section>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'accepted'): ?>
            <div class="alert alert-success">Invitation accepted. You have joined the group.</div>
        <?php elseif ($_GET['msg'] === 'created'): ?>
            <div class="alert alert-success">Group created successfully.</div>
        <?php elseif ($_GET['msg'] === 'invited'): ?>
            <div class="alert alert-success">Invitation sent.</div>
        <?php elseif ($_GET['msg'] === 'cancel'): ?>
            <div class="alert alert-success">Invitation cancelled.</div>
        <?php elseif ($_GET['msg'] === 'locked'): ?>
            <div class="alert alert-error">This group has been locked. Invitations are no longer allowed.</div>
        <?php elseif ($_GET['msg'] === 'full'): ?>
            <div class="alert alert-error">This group is already full. You cannot send more invitations.</div>
        <?php elseif ($_GET['msg'] === 'voted'): ?>
            <div class="alert alert-success">Your vote has been recorded.</div>
        <?php elseif ($_GET['msg'] === 'sent'): ?>
            <div class="alert alert-success">Group has been submitted. Voting is now open (48-hour window).</div>
        <?php elseif ($_GET['msg'] === 'notfound'): ?>
            <div class="alert alert-error">Student ID not found.</div>
        <?php elseif ($_GET['msg'] === 'notfull'): ?>
            <div class="alert alert-error">Team is not full yet.</div>
        <?php elseif ($_GET['msg'] === 'notenrolled'): ?>
            <div class="alert alert-error">You are not enrolled in this course.</div>
        <?php elseif ($_GET['msg'] === 'notcreator'): ?>
            <div class="alert alert-error">Only the group creator can send to supervisor.</div>
        <?php elseif ($_GET['msg'] === 'notwaiting'): ?>
            <div class="alert alert-error">The group is not in the correct state to send to supervisor.</div>
        <?php elseif ($_GET['msg'] === 'left'): ?>
            <div class="alert alert-success">You have left the group.</div>
        <?php elseif ($_GET['msg'] === 'cantleave'): ?>
            <div class="alert alert-error">You cannot leave the group at this stage.</div>
        <?php elseif ($_GET['msg'] === 'creatorcannotleave'): ?>
            <div class="alert alert-error">The group creator cannot leave the group.</div>
        <?php elseif ($_GET['msg'] === 'error'): ?>
            <div class="alert alert-error">Something went wrong. Please try again.</div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="create-group-section">

        <h2>Create Group</h2>

        <form action="create_group_process.php" method="post">

            <div class="create-group-grid">

                <div class="form-group">
                    <label for="newGroupName">Group Name</label>
                    <input type="text" id="newGroupName" name="newGroupName"
                           placeholder="e.g. Team A" required>
                </div>

                <div class="form-group">
                    <label for="courseID">Course</label>
                    <select id="courseID" name="courseID" required>
                        <option value="">-- Select Course --</option>
                        <?php
                        $stmtCourse = mysqli_prepare($conn, "
                            SELECT c.id, c.code, c.name
                            FROM `course_enrollments` ce
                            JOIN `courses` c ON c.id = ce.course_id
                            WHERE ce.student_id = ?
                            ORDER BY c.code
                        ");
                        mysqli_stmt_bind_param($stmtCourse, "i", $studentID);
                        mysqli_stmt_execute($stmtCourse);
                        $rsCourse = mysqli_stmt_get_result($stmtCourse);

                        while ($c = mysqli_fetch_assoc($rsCourse)) {
                            echo '<option value="' . (int)$c['id'] . '">'
                                    . htmlspecialchars($c['code'] . ' - ' . $c['name'])
                                    . '</option>';
                        }
                        mysqli_stmt_close($stmtCourse);
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="projectID">Project</label>
                    <select id="projectID" name="projectID" required>
                        <option value="">-- Select Project --</option>
                        <?php
                        $rs = mysqli_query($conn, "
                            SELECT p.id, p.name, c.code AS course_code
                            FROM `projects` p
                            LEFT JOIN `courses` c ON c.id = p.course_id
                            WHERE p.status <> 'Archived'
                            ORDER BY p.name
                        ");
                        while ($p = mysqli_fetch_assoc($rs)) {
                            echo '<option value="' . (int)$p['id'] . '">'
                                    . htmlspecialchars($p['name'] . ($p['course_code'] ? ' (' . $p['course_code'] . ')' : ''))
                                    . '</option>';
                        }
                        mysqli_free_result($rs);
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="newMaxMembers">Maximum Members</label>
                    <select id="newMaxMembers" name="newMaxMembers" required>
                        <option value="2">2 Members</option>
                        <option value="3">3 Members</option>
                        <option value="4" selected>4 Members</option>
                        <option value="5">5 Members</option>
                        <option value="6">6 Members</option>
                    </select>
                </div>

            </div>

            <button type="submit" class="primary-btn">Create Group</button>

        </form>

    </section>

    <?php if (count($myGroups) > 1): ?>
        <div class="group-switcher">
            <label for="groupSwitcher">Switch Project / Group:</label>
            <select id="groupSwitcher" onchange="switchGroup(this.value)">
                <?php foreach ($myGroups as $g): ?>
                    <option value="<?php echo (int)$g['group_id']; ?>"
                            <?php echo ((int)$g['group_id'] === $activeGroupID) ? 'selected' : ''; ?>>
                        <?php
                        echo htmlspecialchars(
                                ($g['course_code'] ? $g['course_code'] . ' - ' : '')
                                . $g['project_name']
                                . ' / ' . $g['group_name']
                                . ' (' . $g['status'] . ')'
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <script>
            function switchGroup(groupId) {
                window.location.href = 'student_create_group.php?group_id=' + groupId;
            }
        </script>
    <?php endif; ?>

    <?php if ($myGroup): ?>

        <section class="section-grid">

            <!-- Invite -->
            <div class="content-box">

                <?php
                $memberCount  = count($groupMembers);
                $maxMembers   = (int)$myGroup['max_members'];

                $pendingCount = 0;
                foreach ($invitations as $inv) {
                    if ($inv['status'] === 'Pending') {
                        $pendingCount++;
                    }
                }

                $slotsLeft    = $maxMembers - $memberCount - $pendingCount;
                $isFull       = ($memberCount >= $maxMembers);
                $isRecruiting = ($myGroup['status'] === 'Recruiting');
                $canInvite    = ($isRecruiting && !$isFull && $slotsLeft > 0);
                $pct          = $maxMembers > 0 ? round($memberCount / $maxMembers * 100) : 0;
                ?>

                <h2>Invite Classmate</h2>

                <div class="invite-progress">
                    <div class="invite-progress-header">
                        <span>Members</span>
                        <strong><?php echo $memberCount; ?> / <?php echo $maxMembers; ?></strong>
                    </div>
                    <div class="invite-progress-bar">
                        <div class="invite-progress-fill <?php echo $isFull ? 'full' : ''; ?>"
                             style="width: <?php echo $pct; ?>%;"></div>
                    </div>
                    <?php if ($isRecruiting): ?>
                        <p class="invite-progress-note">
                            <?php if ($slotsLeft > 0): ?>
                                <?php echo $slotsLeft; ?> slot<?php echo $slotsLeft > 1 ? 's' : ''; ?> left
                            <?php else: ?>
                                All slots reserved
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (!$canInvite): ?>
                    <div class="invite-locked-note">
                        <?php if ($isFull): ?>
                            <strong>🔒 Group is full.</strong>
                            You cannot invite more members
                            (<?php echo $memberCount; ?> / <?php echo $maxMembers; ?>).
                        <?php elseif ($slotsLeft <= 0): ?>
                            <strong>🔒 All slots reserved.</strong>
                            You have pending invitations for all remaining slots.
                        <?php else: ?>
                            <strong>🔒 Group is locked.</strong>
                            Invitations are no longer allowed.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="invite_process.php" method="post"
                      class="invite-form <?php echo $canInvite ? '' : 'disabled'; ?>">

                    <input type="hidden" name="groupID" value="<?php echo (int)$myGroup['group_id']; ?>">

                    <div class="invite-form-row two-cols">

                        <div class="form-group">
                            <label for="inviteStudentNo">Student ID</label>
                            <input type="text" id="inviteStudentNo" name="inviteStudentNo"
                                   placeholder="<?php echo $canInvite ? 'e.g. S15942736' : 'Locked'; ?>"
                                   autocomplete="off"
                                    <?php echo $canInvite ? 'required' : 'disabled'; ?>>
                        </div>

                        <div class="form-group">
                            <label for="inviteStudentName">Student Name</label>
                            <input type="text" id="inviteStudentName" name="inviteStudentName"
                                   placeholder=""
                                   class="readonly-input"
                                   readonly
                                   disabled>
                        </div>

                    </div>

                    <button type="submit" class="invite-btn-main"
                            <?php echo $canInvite ? '' : 'disabled'; ?>>
                        <?php echo $canInvite ? 'Invite to Group' : 'Invitation Locked'; ?>
                    </button>

                </form>

                <p class="invite-history-title">Invitation History</p>

                <div class="invite-history">

                    <?php if (count($invitations) === 0): ?>
                        <p class="muted">No invitations yet.</p>
                    <?php endif; ?>

                    <?php foreach ($invitations as $inv): ?>
                        <div class="invite-item">
                            <div class="invite-avatar">
                                <?php echo htmlspecialchars(initials($inv['username'])); ?>
                            </div>

                            <div class="invite-info">
                                <h3><?php echo htmlspecialchars($inv['username']); ?></h3>
                                <p>Student ID: <?php echo htmlspecialchars($inv['student_number']); ?></p>
                            </div>

                            <span class="invite-status <?php echo statusClass($inv['status']); ?>">
                                <?php echo htmlspecialchars($inv['status']); ?>
                            </span>

                            <?php if ($inv['status'] === 'Pending'): ?>
                                <form action="cancel_invite_process.php" method="post" style="margin:0;">
                                    <input type="hidden" name="inviteID" value="<?php echo (int)$inv['id']; ?>">
                                    <button type="submit" class="cancel-invite-btn">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>

            </div>

            <!-- Current Group -->
            <div class="content-box">

                <h2>Current Group</h2>

                <div class="group-info">
                    <strong>Group Name:</strong>
                    <?php echo htmlspecialchars($myGroup['group_name']); ?><br>

                    <strong>Course:</strong>
                    <?php
                    echo htmlspecialchars(
                            !empty($myGroup['course_code'])
                                    ? $myGroup['course_code'] . ' - ' . $myGroup['course_name']
                                    : '-'
                    );
                    ?><br>

                    <strong>Project:</strong>
                    <?php echo htmlspecialchars($myGroup['project_name']); ?><br>

                    <strong>Supervisor:</strong>
                    <?php echo htmlspecialchars($myGroup['supervisor_name'] ? $myGroup['supervisor_name'] : '-'); ?><br>

                    <strong>Maximum Members:</strong>
                    <?php echo (int)$myGroup['max_members']; ?><br>

                    <strong>Members:</strong>
                    <?php echo count($groupMembers); ?> / <?php echo (int)$myGroup['max_members']; ?><br>

                    <strong>Status:</strong>
                    <span class="status-badge"><?php echo htmlspecialchars($myGroup['status']); ?></span>
                </div>

                <?php foreach ($groupMembers as $m): ?>
                    <?php
                    $isThisCreator = ((int)$m['student_id'] === (int)$myGroup['creator_id']);
                    $isThisLeader  = ((int)$m['student_id'] === (int)$myGroup['leader_id']);
                    $isMe          = ((int)$m['student_id'] === (int)$studentID);

                    $canLeave = (
                            $isMe
                            && !$isThisCreator
                            && in_array($myGroup['status'], array('Recruiting', 'Waiting_Send'), true)
                    );
                    ?>
                    <div class="member-item">
                        <div>
                            <h3><?php echo htmlspecialchars($m['username']); ?></h3>
                            <p>Student ID: <?php echo htmlspecialchars($m['student_number']); ?></p>
                        </div>

                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="member-status
                                <?php
                            if ($isThisCreator) {
                                echo 'status-creator';
                            } elseif ($isThisLeader) {
                                echo 'status-leader';
                            } else {
                                echo 'status-accepted-member';
                            }
                            ?>">
                                <?php
                                if ($isThisCreator) {
                                    echo 'Creator';
                                } elseif ($isThisLeader) {
                                    echo 'Leader';
                                } else {
                                    echo 'Member';
                                }
                                ?>
                            </span>

                            <?php if ($canLeave): ?>
                                <button
                                        type="button"
                                        class="leave-group-btn"
                                        onclick="confirmLeaveGroup(<?php echo (int)$myGroup['group_id']; ?>);"
                                >
                                    Leave
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php
                $isFullNow = (count($groupMembers) >= (int)$myGroup['max_members']);
                $isCreator = ((int)$myGroup['creator_id'] === (int)$studentID);
                ?>

                <?php if (!$isFullNow): ?>

                    <button type="button" class="primary-btn" disabled style="margin-top: 20px;">
                        Waiting for Members
                        (<?php echo count($groupMembers); ?> / <?php echo (int)$myGroup['max_members']; ?>)
                    </button>
                    <p class="muted" style="margin-top: 12px; font-size: 13px;">
                        The group will be locked automatically once it is full.
                    </p>

                <?php elseif ($myGroup['status'] === 'Waiting_Send' && $isCreator): ?>

                    <button type="button" class="primary-btn" disabled style="margin-top: 20px;">
                        🔒 Team Full — Ready to Submit
                    </button>

                    <form action="send_to_supervisor_process.php" method="post" style="margin-top: 12px;"
                          onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Sending...';">
                        <input type="hidden" name="groupID" value="<?php echo (int)$myGroup['group_id']; ?>">
                        <button type="submit" class="primary-btn">
                            Send to Supervisor
                            <?php if (!empty($myGroup['supervisor_name'])): ?>
                                (<?php echo htmlspecialchars($myGroup['supervisor_name']); ?>)
                            <?php endif; ?>
                        </button>
                    </form>

                <?php elseif ($myGroup['status'] === 'Waiting_Send' && !$isCreator): ?>

                    <button type="button" class="primary-btn" disabled style="margin-top: 20px;">
                        🔒 Team Full — Waiting for Creator
                    </button>
                    <p class="muted" style="margin-top: 12px; font-size: 13px;">
                        Only the group creator can send the group to the supervisor.
                        Voting will start after that.
                    </p>

                <?php endif; ?>

            </div>

        </section>

        <!-- =========================================================
             Vote Group Leader + Vote Result（並排）
        ========================================================= -->
        <?php
        $hasVoteForm   = ($myGroup['status'] === 'Voting');
        $hasVoteResult = ($hasVoted && $totalVotes > 0);
        $boxCount      = ($hasVoteForm ? 1 : 0) + ($hasVoteResult ? 1 : 0);

        $gridCols = '1fr';
        if ($boxCount === 2) {
            $gridCols = '1fr 1fr';
        }
        ?>

        <?php if ($boxCount > 0): ?>

            <section class="section-grid" style="grid-template-columns: <?php echo $gridCols; ?>;">

                <?php if ($hasVoteForm): ?>

                    <!-- =========================================================
                         Vote Group Leader
                    ========================================================= -->
                    <div class="content-box">

                        <h2>Vote Group Leader</h2>

                        <?php if ($votingDeadline): ?>
                            <div class="voting-countdown">
                                <strong>Voting closes at:</strong> <?php echo htmlspecialchars($votingDeadline); ?>
                                <br>
                                <span style="font-size: 13px;">
                                    If not all members vote within 48 hours:
                                    <br>
                                    · If no one has voted → the group creator becomes leader.
                                    <br>
                                    · If some voted → the member with the most votes becomes leader.
                                    <br>
                                    · Ties are broken randomly.
                                </span>
                            </div>
                        <?php endif; ?>

                        <p style="margin-bottom: 18px; color: #666; font-size: 14px;">
                            Each member casts <strong>one anonymous vote</strong>. The member
                            with the most votes becomes the group leader.
                        </p>

                        <form action="vote_leader_process.php" method="post">

                            <input type="hidden" name="groupID" value="<?php echo (int)$myGroup['group_id']; ?>">

                            <?php foreach ($groupMembers as $m): ?>
                                <?php $isMyVote = ((int)$m['student_id'] === $myVoteCandidateID); ?>
                                <label class="vote-option <?php echo $hasVoted ? 'disabled' : ''; ?> <?php echo $isMyVote ? 'my-vote' : ''; ?>">
                                    <input type="radio" name="candidateID"
                                           value="<?php echo (int)$m['student_id']; ?>"
                                            <?php echo $isMyVote ? 'checked' : ''; ?>
                                            <?php echo $hasVoted ? 'disabled' : 'required'; ?>>

                                    <div class="vote-option-info">
                                        <h3><?php echo htmlspecialchars($m['username']); ?></h3>
                                        <p>Student ID: <?php echo htmlspecialchars($m['student_number']); ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>

                            <?php if (!$hasVoted): ?>
                                <button type="submit" class="primary-btn" style="margin-top: 10px;">
                                    Submit My Vote
                                </button>
                            <?php endif; ?>

                        </form>

                    </div>

                <?php endif; ?>

                <?php if ($hasVoteResult): ?>

                    <!-- =========================================================
                         Vote Result
                    ========================================================= -->
                    <div class="content-box">

                        <h2>Vote Result</h2>

                        <?php foreach ($voteResults as $vr): ?>
                            <?php
                            $pct = $totalVotes > 0 ? round($vr['vote_count'] / $totalVotes * 100) : 0;
                            ?>
                            <div class="result-item">
                                <div class="result-header">
                                    <strong><?php echo htmlspecialchars($vr['username']); ?></strong>
                                    <span><?php echo (int)$vr['vote_count']; ?> votes</span>
                                </div>
                                <div class="result-bar">
                                    <div class="result-value" style="width: <?php echo $pct; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    <?php endif; ?>

</main>

<!-- Hidden form for leaving group -->
<form id="leaveGroupForm" action="leave_group_process.php" method="post" style="display:none;">
    <input type="hidden" name="groupID" id="leaveGroupID" value="">
</form>

<script>
    // Auto-fill Student Name
    (function () {
        var inputId   = document.getElementById('inviteStudentNo');
        var inputName = document.getElementById('inviteStudentName');
        var timer     = null;

        if (!inputId || !inputName) return;

        inputId.addEventListener('input', function () {
            var no = inputId.value.trim();

            inputName.value = '';
            inputName.style.background = '';
            inputName.style.color = '';

            if (no === '') return;

            clearTimeout(timer);
            timer = setTimeout(function () {

                fetch('get_student_name.php?student_no=' + encodeURIComponent(no))
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.name) {
                            inputName.value = data.name;
                            inputName.style.background = '#eaf7ee';
                            inputName.style.color = '#176b2c';
                        } else {
                            inputName.value = 'Not found';
                            inputName.style.background = '#fdeeee';
                            inputName.style.color = '#a21b1b';
                        }
                    })
                    .catch(function () {
                        inputName.value = '';
                    });

            }, 300);
        });
    })();

    // Leave Group（單次確認）
    function confirmLeaveGroup(groupID) {
        if (!confirm('Leave this group?\n\nAre you sure?')) {
            return;
        }
        document.getElementById('leaveGroupID').value = groupID;
        document.getElementById('leaveGroupForm').submit();
    }
</script>

</body>
</html>