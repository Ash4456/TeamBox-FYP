<?php
session_start();

// ===== session + 数据库 =====
require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

// 没登录 → 回登录页
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];


// =========================================================
// 1. 取得当前学生资料（含 student_id, 名字, 主修）
// =========================================================
$student = array(
        'student_id'    => 0,
        'student_no'    => '',
        'full_name'     => '',
        'major'         => '',
        'grade_level'   => '',
);

$sql = "
    SELECT s.id AS student_id,
           s.student_number,
           s.major,
           s.grade_level,
           u.username,
           u.email
    FROM `students` s
    JOIN `users` u ON u.id = s.user_id
    WHERE u.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($row) {
    $student['student_id']  = (int) $row['student_id'];
    $student['student_no']  = $row['student_number'];
    $student['full_name']   = $row['username'];
    $student['major']       = $row['major'];
    $student['grade_level'] = $row['grade_level'];
} else {
    header('Location: logout.php');
    exit;
}

$studentID = $student['student_id'];


// =========================================================
// 2. 汇总卡片
// =========================================================

// Active Projects
$activeProjects = 0;
$sql = "
    SELECT COUNT(DISTINCT g.project_id) AS c
    FROM `group_members` gm
    JOIN `groups` g ON g.id = gm.group_id
    WHERE gm.student_id = ?
      AND g.status IN ('Executing','Voting')
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$activeProjects = (int) $rc['c'];
mysqli_stmt_close($stmt);

// Assigned Tasks
$assignedTasks = 0;
$sql = "
    SELECT COUNT(*) AS c
    FROM `tasks`
    WHERE assigned_to = ?
      AND status <> 'Completed'
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$assignedTasks = (int) $rc['c'];
mysqli_stmt_close($stmt);

// Completed Tasks
$completedTasks = 0;
$sql = "
    SELECT COUNT(*) AS c
    FROM `tasks`
    WHERE assigned_to = ?
      AND status = 'Completed'
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$completedTasks = (int) $rc['c'];
mysqli_stmt_close($stmt);

// Notifications
$unreadNotifications = 0;
$sql = "
    SELECT COUNT(*) AS c
    FROM `notifications`
    WHERE user_id = ?
      AND is_read = 0
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$unreadNotifications = (int) $rc['c'];
mysqli_stmt_close($stmt);


// =========================================================
// 3. Recent Tasks
// =========================================================
$recentTasks = array();

$sql = "
    SELECT id, title, status, due_date
    FROM `tasks`
    WHERE assigned_to = ?
    ORDER BY created_at DESC
    LIMIT 3
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $recentTasks[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 4. Team Progress
// =========================================================
$teamProgress = 0;
$teamDone     = 0;
$teamTotal    = 0;

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Completed') AS done
    FROM `tasks`
    WHERE group_id IN (
        SELECT group_id FROM `group_members` WHERE student_id = ?
    )
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($rc) {
    $teamTotal = (int) $rc['total'];
    $teamDone  = (int) $rc['done'];
    if ($teamTotal > 0) {
        $teamProgress = round($teamDone / $teamTotal * 100);
    }
}


// =========================================================
// 5. 辅助函数
// =========================================================
if (!function_exists('statusClass')) {
    function statusClass($status) {
        switch ($status) {
            case 'Completed':   return 'completed';
            case 'In Progress': return 'in-progress';
            default:            return 'pending';
        }
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        if (empty($date)) return '—';
        return date('d M Y', strtotime($date));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | TeamBox</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f5f5f5;
            color: #222;
        }

        /* Top Navigation Bar */

        .navbar {
            width: 100%;
            padding: 15px 30px;
            background: black;

            display: flex;
            flex-direction: column;
            gap: 15px;

            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
        }

        /* First row: logo (left) + bell (right) */
        .navbar-top {
            position: relative;
            width: 100%;
            min-height: 65px;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-area img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            display: block;
        }

        .logo-area span {
            color: white;
            font-size: 30px;
            font-weight: bold;
        }

        /* Bell icon */
        .bell-link {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);

            display: flex;
            align-items: center;
            justify-content: center;

            width: 44px;
            height: 44px;

            border-radius: 50%;
            transition: background 0.3s;
        }

        .bell-link:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .bell-link img {
            width: 28px;
            height: 28px;
            object-fit: contain;
            display: block;
        }

        /* Unread badge (optional) */
        .bell-badge {
            position: absolute;
            top: 2px;
            right: 2px;

            min-width: 18px;
            height: 18px;
            padding: 0 5px;

            background: #d93025;
            color: white;
            border-radius: 9px;

            font-size: 11px;
            font-weight: bold;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 2px solid black;
        }
        /* Second row: Navigation */
        .function-menu {
            width: 100%;

            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 22px;
        }

        .nav-link {
            color: white;
            font-size: 15px;
            text-decoration: none;
            padding: 6px 2px;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #bbbbbb;
        }

        .nav-link.active {
            color: white;
            font-weight: bold;
            border-bottom: 2px solid white;
        }

        .logout-link {
            color: #ff8888;
        }

        .logout-link:hover {
            color: #ff4444;
        }

        /* Main Content */

        .main-content {
            width: 90%;
            max-width: 1250px;
            margin: 40px auto;
        }

        .welcome-section {
            padding: 30px;
            margin-bottom: 30px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .welcome-section h1 {
            margin-bottom: 10px;
            font-size: 35px;
        }

        .welcome-section p {
            color: #666;
            font-size: 17px;
            line-height: 1.6;
        }

        /* Dashboard Cards */

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            min-height: 155px;
            padding: 25px;

            background: white;
            border-left: 5px solid black;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);

            transition: transform 0.3s, box-shadow 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card h2 {
            margin-bottom: 18px;
            color: #555;
            font-size: 17px;
        }

        .dashboard-card .number {
            color: black;
            font-size: 38px;
            font-weight: bold;
        }

        .dashboard-card .description {
            margin-top: 8px;
            color: #777;
            font-size: 14px;
        }

        /* Lower Information Section */

        .information-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .content-box {
            padding: 28px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .content-box h2 {
            padding-bottom: 15px;
            margin-bottom: 20px;

            border-bottom: 2px solid #eeeeee;
            font-size: 23px;
        }

        /* Recent Tasks */

        .task-item {
            padding: 16px 0;
            border-bottom: 1px solid #eeeeee;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .task-item:last-of-type {
            border-bottom: none;
        }

        .task-information h3 {
            margin-bottom: 6px;
            font-size: 17px;
        }

        .task-information p {
            color: #777;
            font-size: 14px;
        }

        .task-status {
            padding: 7px 12px;
            border-radius: 20px;

            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .in-progress {
            background: #fff1c2;
            color: #765900;
        }

        .completed {
            background: #d9f5df;
            color: #176b2c;
        }

        .pending {
            background: #eeeeee;
            color: #555;
        }

        .muted {
            color: #777;
            font-size: 14px;
        }

        /* Team Progress */

        .progress-container {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #eeeeee;
        }

        .progress-information {
            margin-bottom: 12px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-information strong {
            font-size: 17px;
        }

        .progress-information span {
            font-weight: bold;
        }

        .progress-bar {
            width: 100%;
            height: 15px;

            background: #dddddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-value {
            height: 100%;
            background: black;
            border-radius: 10px;
        }

        .progress-description {
            margin-top: 10px;
            color: #777;
            font-size: 14px;
        }

        /* Quick Access */

        .quick-links {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .text-link {
            color: black;
            font-size: 16px;
            text-decoration: underline;
            transition: color 0.3s;
        }

        .text-link:hover {
            color: #555555;
        }

        .text-link:active {
            color: #000000;
        }

        .quick-note {
            margin-top: 16px;
            color: #777;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Responsive */

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .information-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 15px;
            }

            .logo-area img {
                width: 55px;
                height: 55px;
            }

            .logo-area span {
                font-size: 26px;
            }

            .bell-link {
                width: 38px;
                height: 38px;
            }

            .bell-link img {
                width: 22px;
                height: 22px;
            }

            .function-menu {
                flex-direction: column;
                gap: 12px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                width: 94%;
                margin: 25px auto;
            }

            .welcome-section h1 {
                font-size: 28px;
            }

            .task-item {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<!-- Top Navigation Bar -->

<nav class="navbar">

    <!-- First row: Logo (left) + Bell (right) -->
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

    <!-- Second row: Navigation -->
    <div class="function-menu">

        <a href="student_index.php" class="nav-link active">
            Dashboard
        </a>
        <a href="student_create_group.php" class="nav-link">
            Create Group
        </a>

        <a href="my_team.php" class="nav-link">
            My Team
        </a>

        <a href="student_task.php" class="nav-link">
            Tasks
        </a>

        <a href="student_files.php" class="nav-link">
            Files
        </a>

        <a href="student_chat.php" class="nav-link">
            Group Chat
        </a>

        <a href="student_contribution.php" class="nav-link">
            Contribution
        </a>
        <a href="student_notifications.php" class="nav-link">
            Notifications
        </a>

        <a href="student_profile.php" class="nav-link">
            Profile
        </a>

        <a href="logout.php" class="nav-link logout-link">
            Log Out
        </a>

    </div>

</nav>

<!-- Main Dashboard Content -->

<main class="main-content">

    <section class="welcome-section">

        <h1>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></h1>

        <p>
            This is your TeamBox student dashboard. You can view
            your current project, assigned tasks, team progress,
            shared files and recent notifications.
        </p>

    </section>

    <section class="dashboard-grid">

        <div class="dashboard-card">
            <h2>Active Projects</h2>
            <p class="number"><?php echo $activeProjects; ?></p>
            <p class="description">Projects currently in progress</p>
        </div>

        <div class="dashboard-card">
            <h2>Assigned Tasks</h2>
            <p class="number"><?php echo $assignedTasks; ?></p>
            <p class="description">Tasks assigned to you</p>
        </div>

        <div class="dashboard-card">
            <h2>Completed Tasks</h2>
            <p class="number"><?php echo $completedTasks; ?></p>
            <p class="description">Tasks completed by you</p>
        </div>

        <div class="dashboard-card">
            <h2>Notifications</h2>
            <p class="number"><?php echo $unreadNotifications; ?></p>
            <p class="description">Unread notifications</p>
        </div>

    </section>

    <section class="information-grid">

        <div class="content-box">

            <h2>Recent Tasks</h2>

            <?php if (count($recentTasks) === 0): ?>
                <p class="muted">No tasks yet.</p>
            <?php endif; ?>

            <?php foreach ($recentTasks as $task): ?>
                <div class="task-item">
                    <div class="task-information">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p>
                            Deadline:
                            <?php echo htmlspecialchars(formatDate($task['due_date'])); ?>
                        </p>
                    </div>

                    <span class="task-status <?php echo statusClass($task['status']); ?>">
                        <?php echo htmlspecialchars($task['status']); ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="progress-container">

                <div class="progress-information">
                    <strong>Team Progress</strong>
                    <span><?php echo $teamProgress; ?>%</span>
                </div>

                <div class="progress-bar">
                    <div class="progress-value" style="width: <?php echo $teamProgress; ?>%;"></div>
                </div>

                <p class="progress-description">
                    <?php echo $teamDone; ?> out of <?php echo $teamTotal; ?> team tasks completed.
                </p>

            </div>

        </div>

        <div class="content-box">

            <h2>Quick Access</h2>

            <div class="quick-links">

                <a href="my_team.php" class="text-link">
                    View My Team
                </a>

                <a href="student_task.php" class="text-link">
                    View My Tasks
                </a>

                <a href="student_files.php" class="text-link">
                    Open Shared Files
                </a>

                <a href="student_chat.php" class="text-link">
                    Open Group Chat
                </a>

            </div>

            <p class="quick-note">
                Click any link to open the matching page.
            </p>

        </div>

    </section>

</main>

</body>
</html>