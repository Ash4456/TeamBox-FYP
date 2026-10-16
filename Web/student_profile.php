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
// 0. 未讀通知數量
// =========================================================
$unreadNotifications = 0;

$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `notifications`
    WHERE user_id = ? AND is_read = 0
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$unreadNotifications = (int)$rc['c'];
mysqli_stmt_close($stmt);

// =========================================================
// 1. 取 student + user 資料
// =========================================================
$student = array(
        'student_id'     => 0,
        'student_number' => '',
        'full_name'      => '',
        'email'          => '',
        'major'          => '',
        'grade_level'    => '',
        'role'           => '',
        'created_at'     => '',
);

$stmt = mysqli_prepare($conn, "
    SELECT
        s.id             AS student_id,
        s.student_number,
        s.major,
        s.grade_level,
        u.username       AS full_name,
        u.email,
        u.role,
        u.created_at
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE u.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$row) {
    header('Location: logout.php');
    exit;
}

$student = $row;
$studentID = (int) $student['student_id'];

// =========================================================
// 2. Change Password
// =========================================================
$pwdMsg  = '';
$pwdType = '';

if (isset($_POST['savePassword'])) {

    $current = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
    $new     = isset($_POST['newPassword'])     ? $_POST['newPassword']     : '';
    $confirm = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

    if ($current === '' || $new === '' || $confirm === '') {
        $pwdMsg  = 'Please fill in all password fields.';
        $pwdType = 'error';
    } elseif ($new !== $confirm) {
        $pwdMsg  = 'New passwords do not match.';
        $pwdType = 'error';
    } elseif (strlen($new) < 6) {
        $pwdMsg  = 'New password must be at least 6 characters.';
        $pwdType = 'error';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT password_hash FROM `users` WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        $rowPwd = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($stmt);

        if (!$rowPwd || $current !== $rowPwd['password_hash']) {
            $pwdMsg  = 'Current password is incorrect.';
            $pwdType = 'error';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE `users` SET password_hash = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new, $userID);

            if (mysqli_stmt_execute($stmt)) {
                $pwdMsg  = 'Password updated successfully.';
                $pwdType = 'success';
            } else {
                $pwdMsg  = 'Update failed: ' . mysqli_error($conn);
                $pwdType = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// =========================================================
// 3. 統計數字
// =========================================================
$activeProjects = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT g.project_id) AS c
    FROM `group_members` gm
    JOIN `groups` g ON g.id = gm.group_id
    WHERE gm.student_id = ?
      AND g.status IN ('Voting', 'Executing')
");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$activeProjects = (int)$rc['c'];
mysqli_stmt_close($stmt);

$completedTasks = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `tasks`
    WHERE assigned_to = ? AND status = 'Completed'
");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$completedTasks = (int)$rc['c'];
mysqli_stmt_close($stmt);

$filesUploaded = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `files` WHERE uploader_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$filesUploaded = (int)$rc['c'];
mysqli_stmt_close($stmt);

$teamContribution = 0;
$stmt = mysqli_prepare($conn, "
    SELECT
        (SELECT COUNT(*) FROM `tasks` t
            WHERE t.group_id IN (
                SELECT group_id FROM `group_members` WHERE student_id = ?
            )
              AND t.status = 'Completed') AS team_done,
        (SELECT COUNT(*) FROM `tasks` t2
            WHERE t2.group_id IN (
                SELECT group_id FROM `group_members` WHERE student_id = ?
            )) AS team_total
");
mysqli_stmt_bind_param($stmt, "ii", $studentID, $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

$teamDone  = (int)$rc['team_done'];
$teamTotal = (int)$rc['team_total'];
if ($teamTotal > 0) {
    $teamContribution = (int) round($teamDone / $teamTotal * 100);
}

// =========================================================
// 4. Academic Info — 拎所有已 submit 嘅 group
// =========================================================
$myProjectGroups = array();

$stmt = mysqli_prepare($conn, "
    SELECT
        g.id            AS group_id,
        g.name          AS team_name,
        p.id            AS project_id,
        p.name          AS project_name
    FROM `group_members` gm
    JOIN `groups` g ON g.id = gm.group_id
    LEFT JOIN `projects` p ON p.id = g.project_id
    WHERE gm.student_id = ?
      AND g.status IN ('Voting', 'Executing')
    ORDER BY g.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($rowG = mysqli_fetch_assoc($rs)) {
    $myProjectGroups[] = $rowG;
}
mysqli_stmt_close($stmt);

// -------- 當前選擇 --------
$selectedGroupID = 0;

if (isset($_GET['project_group'])) {
    $selectedGroupID = (int) $_GET['project_group'];
}

$currentProjectGroup = null;

if ($selectedGroupID > 0) {
    foreach ($myProjectGroups as $g) {
        if ((int)$g['group_id'] === $selectedGroupID) {
            $currentProjectGroup = $g;
            break;
        }
    }
}

if (!$currentProjectGroup && count($myProjectGroups) > 0) {
    $currentProjectGroup = $myProjectGroups[0];
    $selectedGroupID     = (int) $currentProjectGroup['group_id'];
}

$currentProject = '—';
$currentTeam    = '—';

if ($currentProjectGroup) {
    $currentProject = $currentProjectGroup['project_name'] ?: '—';
    $currentTeam    = $currentProjectGroup['team_name']    ?: '—';
}

// =========================================================
// 5. 顏色 palette
// =========================================================
$colorPalette = array(
    // [project_bg, project_fg, team_bg, team_fg]
        array('#dbeafe', '#1e40af', '#bfdbfe', '#1e3a8a'), // 藍
        array('#dcfce7', '#166534', '#bbf7d0', '#14532d'), // 綠
        array('#fef3c7', '#92400e', '#fde68a', '#78350f'), // 黃
        array('#fce7f3', '#9d174d', '#fbcfe8', '#831843'), // 粉紅
        array('#e9d5ff', '#6b21a8', '#d8b4fe', '#581c87'), // 紫
        array('#ffedd5', '#9a3412', '#fed7aa', '#7c2d12'), // 橙
        array('#cffafe', '#155e75', '#a5f3fc', '#164e63'), // 青
        array('#f3e8ff', '#7e22ce', '#e9d5ff', '#6b21a8'), // 淡紫
);

$groupColors = array();
foreach ($myProjectGroups as $i => $g) {
    $groupColors[(int)$g['group_id']] = $colorPalette[$i % count($colorPalette)];
}

$defaultColors = array('#f0f0f0', '#555', '#f0f0f0', '#555');

// =========================================================
// 6. Academic Year / Programme / Department
// =========================================================
$academicYear = '—';
if (!empty($student['grade_level'])) {
    $gl = (int)$student['grade_level'];
    $yearNum = ($gl >= 12) ? ($gl - 12 + 1) : $gl;
    $academicYear = '2026/2027 · Year ' . $yearNum;
}

$programme  = !empty($student['major']) ? $student['major'] : '—';
$department = 'Department of AI & Data';
$accountStatus = 'Active';

$nameParts = preg_split('/\s+/', trim($student['full_name']));
$initials  = '';
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
} elseif (count($nameParts) === 1) {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}

// =========================================================
// 7. Recent Account Activity（連 project / group）
// =========================================================
$accountActivities = array();

$stmt = mysqli_prepare($conn, "
    SELECT
        a.action,
        a.detail,
        a.created_at,
        a.project_id,
        a.group_id,
        p.name AS project_name,
        g.name AS team_name
    FROM `activities` a
    LEFT JOIN `projects` p ON p.id = a.project_id
    LEFT JOIN `groups`   g ON g.id = a.group_id
    WHERE a.actor_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($rowA = mysqli_fetch_assoc($rs)) {
    $accountActivities[] = $rowA;
}
mysqli_stmt_close($stmt);

// =========================================================
// 8. Helper
// =========================================================
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return '—';
        $ts   = strtotime($datetime);
        $diff = time() - $ts;
        if ($diff < 60)         return 'Just now';
        if ($diff < 3600)       return floor($diff / 60) . 'm ago';
        if ($diff < 86400)      return floor($diff / 3600) . 'h ago';
        if ($diff < 86400 * 2)  return 'Yesterday';
        if ($diff < 86400 * 7)  return floor($diff / 86400) . 'd ago';
        return date('d M Y', $ts);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | TeamBox</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: #f5f5f5; color: #222; }

        /* Navbar */
        .navbar {
            width: 100%; padding: 15px 30px; background: black;
            display: flex; flex-direction: column; align-items: center; gap: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
        }
        .navbar-top {
            position: relative; width: 100%; min-height: 65px;
            display: flex; justify-content: center; align-items: center;
        }
        .logo-area { display: flex; justify-content: center; align-items: center; gap: 12px; }
        .logo-area img { width: 65px; height: 65px; object-fit: contain; display: block; }
        .logo-area span { color: white; font-size: 30px; font-weight: bold; }

        .bell-link {
            position: absolute; right: 0; top: 50%;
            transform: translateY(-50%);
            display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px;
            border-radius: 50%; transition: background 0.3s;
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
            width: 100%; display: flex; justify-content: center; align-items: center;
            flex-wrap: wrap; gap: 22px;
        }
        .nav-link {
            color: white; font-size: 15px; text-decoration: none;
            padding: 6px 2px; transition: color 0.3s;
        }
        .nav-link:hover { color: #bbbbbb; }
        .nav-link.active { color: white; font-weight: bold; border-bottom: 2px solid white; }
        .logout-link { color: #ff8888; }
        .logout-link:hover { color: #ff4444; }

        .main-content { width: 90%; max-width: 1200px; margin: 40px auto; }

        .page-heading {
            padding: 30px; margin-bottom: 25px;
            background: white; border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .page-heading h1 { margin-bottom: 10px; font-size: 35px; }
        .page-heading p { color: #666; font-size: 17px; line-height: 1.6; }

        /* Profile Header */
        .profile-header {
            padding: 30px; margin-bottom: 25px;
            background: white; border-left: 6px solid black;
            border-radius: 15px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
            display: flex; align-items: center; gap: 25px;
        }
        .profile-avatar {
            width: 110px; height: 110px; flex-shrink: 0;
            background: black; color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 35px; font-weight: bold;
        }
        .profile-summary { flex: 1; min-width: 0; }
        .profile-summary h2 { margin-bottom: 8px; font-size: 28px; }
        .profile-summary p { margin-bottom: 6px; color: #666; font-size: 15px; }
        .account-status {
            display: inline-block; padding: 7px 13px; margin-top: 7px;
            background: #d9f5df; color: #176b2c; border-radius: 18px;
            font-size: 13px; font-weight: bold;
        }

        /* Summary */
        .summary-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 20px; margin-bottom: 25px;
        }
        .summary-card {
            min-height: 135px; padding: 23px;
            background: white; border-left: 5px solid black;
            border-radius: 15px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .summary-card h3 { margin-bottom: 14px; color: #666; font-size: 15px; }
        .summary-number { font-size: 35px; font-weight: bold; }
        .summary-description { margin-top: 7px; color: #777; font-size: 13px; }

        /* Content */
        .information-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 25px; margin-bottom: 25px;
        }
        .content-box {
            padding: 28px; background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .content-box h2 {
            padding-bottom: 15px; margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee; font-size: 23px;
        }

        .profile-detail {
            padding: 14px 0; border-bottom: 1px solid #eeeeee;
            display: grid; grid-template-columns: 150px 1fr;
            gap: 15px;
            align-items: center;
        }
        .profile-detail:last-child { border-bottom: none; }
        .profile-detail strong { color: #666; font-size: 14px; }
        .profile-detail span { color: #222; font-size: 15px; word-break: break-word; }

        /* Form */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { margin-bottom: 19px; }
        .form-group label {
            display: block; margin-bottom: 8px;
            color: #333; font-size: 14px; font-weight: bold;
        }
        .form-group input {
            width: 100%; height: 47px; padding: 0 13px;
            background: white; color: #222;
            border: 2px solid #cccccc; border-radius: 8px;
            font-size: 14px; outline: none; transition: 0.3s;
        }
        .form-group input:focus { border-color: black; }

        .form-actions { display: flex; justify-content: flex-end; gap: 12px; }
        .form-btn {
            min-width: 130px; height: 46px; padding: 0 16px;
            background: white; color: black;
            border: 2px solid black; border-radius: 8px;
            font-size: 14px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }
        .form-btn:hover { background: black; color: white; }
        .save-btn { background: black; color: white; }
        .save-btn:hover { background: #333; }

        .password-information {
            padding: 16px; margin-bottom: 20px;
            background: #f7f7f7; border-left: 5px solid black;
            border-radius: 8px;
            color: #666; font-size: 14px; line-height: 1.6;
        }

        /* =========================================================
           Activity item（左文字 / 右 chips）
           ========================================================= */
        .activity-item {
            padding: 16px 0;
            border-bottom: 1px solid #eeeeee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        .activity-item:last-child { border-bottom: none; }

        .activity-left {
            display: flex;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }
        .activity-dot {
            width: 13px; height: 13px; margin-top: 5px; flex-shrink: 0;
            background: black; border-radius: 50%;
        }
        .activity-content { min-width: 0; }
        .activity-content h3 { margin-bottom: 5px; font-size: 15px; }
        .activity-content p { color: #777; font-size: 13px; line-height: 1.5; }

        /* 右邊 chips */
        .activity-chips {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .activity-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            border: 1.5px solid transparent;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .activity-chip .chip-icon {
            font-size: 10px;
            font-weight: bold;
            opacity: 0.75;
            letter-spacing: 0.04em;
        }

        .muted { color: #777; font-size: 14px; }

        /* Alert */
        .alert {
            padding: 14px 18px; margin-bottom: 20px;
            border-radius: 8px; font-size: 14px;
            border-left: 4px solid;
        }
        .alert-success { background: #eaf7ee; color: #176b2c; border-color: #2e9d4d; }
        .alert-error   { background: #fdeeee; color: #a21b1b; border-color: #d32f2f; }

        /* Responsive */
        @media (max-width: 950px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .information-grid { grid-template-columns: 1fr; }
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

            .profile-header { flex-direction: column; text-align: center; }
            .summary-grid { grid-template-columns: 1fr; }
            .profile-detail { grid-template-columns: 1fr; gap: 6px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .form-actions { flex-direction: column; }
            .form-btn { width: 100%; }

            .activity-item { flex-direction: column; align-items: flex-start; }
            .activity-chips { width: 100%; justify-content: flex-start; }
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
        <a href="student_create_group.php" class="nav-link">Create Group</a>
        <a href="my_team.php" class="nav-link">My Team</a>
        <a href="student_task.php" class="nav-link">Tasks</a>
        <a href="student_files.php" class="nav-link">Files</a>
        <a href="student_chat.php" class="nav-link">Group Chat</a>
        <a href="student_contribution.php" class="nav-link">Contribution</a>
        <a href="student_notifications.php" class="nav-link">Notifications</a>
        <a href="student_profile.php" class="nav-link active">Profile</a>
        <a href="logout.php" class="nav-link logout-link">Log Out</a>
    </div>
</nav>

<main class="main-content">

    <section class="page-heading">
        <h1>Student Profile</h1>
        <p>
            View your personal information, academic details,
            account security and TeamBox activities.
        </p>
    </section>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="profile-avatar">
            <?php echo htmlspecialchars($initials ?: 'ST'); ?>
        </div>
        <div class="profile-summary">
            <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p>Student ID: <?php echo htmlspecialchars($student['student_number']); ?></p>
            <p><?php echo htmlspecialchars($programme); ?></p>
            <span class="account-status">
                <?php echo htmlspecialchars($accountStatus); ?> Account
            </span>
        </div>
    </section>

    <!-- Summary -->
    <section class="summary-grid">
        <div class="summary-card">
            <h3>Active Projects</h3>
            <p class="summary-number"><?php echo $activeProjects; ?></p>
            <p class="summary-description">Current project participation</p>
        </div>
        <div class="summary-card">
            <h3>Completed Tasks</h3>
            <p class="summary-number"><?php echo $completedTasks; ?></p>
            <p class="summary-description">Tasks completed by you</p>
        </div>
        <div class="summary-card">
            <h3>Team Contributions</h3>
            <p class="summary-number"><?php echo $teamContribution; ?>%</p>
            <p class="summary-description">Current project contribution</p>
        </div>
        <div class="summary-card">
            <h3>Files Uploaded</h3>
            <p class="summary-number"><?php echo $filesUploaded; ?></p>
            <p class="summary-description">Files shared with your team</p>
        </div>
    </section>

    <!-- Personal + Academic -->
    <section class="information-grid">
        <div class="content-box">
            <h2>Personal Information</h2>

            <div class="profile-detail">
                <strong>Full Name</strong>
                <span><?php echo htmlspecialchars($student['full_name']); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Student ID</strong>
                <span><?php echo htmlspecialchars($student['student_number']); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Email Address</strong>
                <span><?php echo htmlspecialchars($student['email']); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Account Role</strong>
                <span><?php echo htmlspecialchars(ucfirst($student['role'])); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Account Status</strong>
                <span><?php echo htmlspecialchars($accountStatus); ?></span>
            </div>
        </div>

        <div class="content-box">
            <h2>Academic Information</h2>

            <div class="profile-detail">
                <strong>Programme</strong>
                <span><?php echo htmlspecialchars($programme); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Department</strong>
                <span><?php echo htmlspecialchars($department); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Academic Year</strong>
                <span><?php echo htmlspecialchars($academicYear); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Current Project</strong>
                <span><?php echo htmlspecialchars($currentProject); ?></span>
            </div>
            <div class="profile-detail">
                <strong>Team</strong>
                <span><?php echo htmlspecialchars($currentTeam); ?></span>
            </div>
        </div>
    </section>

    <!-- Change Password -->
    <section class="content-box" style="margin-bottom: 25px;">
        <h2>Change Password</h2>

        <div class="password-information">
            For your account security, choose a strong password with at
            least 6 characters, including letters and numbers.
        </div>

        <?php if ($pwdMsg !== ''): ?>
            <div class="alert <?php echo $pwdType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($pwdMsg); ?>
            </div>
        <?php endif; ?>

        <form action="student_profile.php" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword"
                           placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword"
                           placeholder="Enter new password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword"
                       placeholder="Re-enter new password" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="savePassword" class="form-btn save-btn">
                    Update Password
                </button>
            </div>
        </form>
    </section>

    <!-- =========================================================
         Recent Activity（每條右邊有 project + team chip）
         ========================================================= -->
    <section class="content-box" style="margin-bottom: 25px;">
        <h2>Recent Account Activity</h2>

        <?php if (count($accountActivities) === 0): ?>
            <p class="muted">No recent activity.</p>
        <?php endif; ?>

        <?php foreach ($accountActivities as $a): ?>
            <?php
            // 拎對應顏色（如果有 group_id）
            $gid = (int)$a['group_id'];
            $colors = isset($groupColors[$gid]) ? $groupColors[$gid] : $defaultColors;
            ?>
            <div class="activity-item">
                <div class="activity-left">
                    <div class="activity-dot"></div>
                    <div class="activity-content">
                        <h3><?php echo htmlspecialchars($a['action']); ?></h3>
                        <p>
                            <?php echo htmlspecialchars($a['detail']); ?>
                            · <span class="muted"><?php echo timeAgo($a['created_at']); ?></span>
                        </p>
                    </div>
                </div>

                <!-- 右邊：project chip + team chip -->
                <div class="activity-chips">

                    <?php if (!empty($a['project_name'])): ?>
                        <span class="activity-chip"
                              style="background: <?php echo $colors[0]; ?>;
                                      color: <?php echo $colors[1]; ?>;
                                      border-color: <?php echo $colors[1]; ?>;">

                            <?php echo htmlspecialchars($a['project_name']); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($a['team_name'])): ?>
                        <span class="activity-chip"
                              style="background: <?php echo $colors[2]; ?>;
                                      color: <?php echo $colors[3]; ?>;
                                      border-color: <?php echo $colors[3]; ?>;">
                            <?php echo htmlspecialchars($a['team_name']); ?>
                        </span>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </section>

</main>

</body>
</html>