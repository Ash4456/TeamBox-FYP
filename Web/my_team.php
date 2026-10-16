<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($conn) || !$conn) {
    die('Database connection not available. Check db.php.');
}

// 沒登入 → 回登入頁
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

// =========================================================
// 1. 取 student_id
// =========================================================
$studentID = 0;

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
// 2. 取我所有 group
//    只顯示已 send 去 supervisor 之後嘅 group：
//      Recruiting   → 唔顯示
//      Waiting_Send → 唔顯示
//      Voting       → 顯示
//      Executing    → 顯示
//      Completed    → 唔顯示
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
        g.formed_at,
        g.created_at,
        p.name          AS project_name,
        p.category      AS project_category,
        p.period_start,
        p.period_end,
        p.progress      AS project_progress,
        p.description   AS project_description,
        p.supervisor_id,
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
      AND g.status IN ('Voting', 'Executing')
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
// 2.5 額外查：有冇「未 send」嘅 group
// =========================================================
$pendingGroupCount = 0;

$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `group_members` gm
    JOIN `groups` g ON g.id = gm.group_id
    WHERE gm.student_id = ?
      AND g.status IN ('Recruiting', 'Waiting_Send')
");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$pendingGroupCount = (int)$rc['c'];
mysqli_stmt_close($stmt);

// =========================================================
// 3. 決定 active group
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
// 4. 如果有 group → 拎 group 詳細資料
// =========================================================
$groupMembers = array();
$tasks        = array();
$activities   = array();
$progress     = array(
        'total' => 0,
        'done'  => 0,
        'pct'   => 0,
);

if ($myGroup) {

    $groupID = (int) $myGroup['group_id'];

    // -------- 4.1 Group members --------
    $stmt = mysqli_prepare($conn, "
        SELECT
            s.id            AS student_id,
            s.student_number,
            s.major,
            s.grade_level,
            u.username      AS name,
            gm.role,
            gm.joined_at,
            (SELECT COUNT(*) FROM `tasks` t
                WHERE t.assigned_to = s.id
                  AND t.status = 'Completed') AS done_tasks,
            (SELECT COUNT(*) FROM `tasks` t2
                WHERE t2.assigned_to = s.id) AS total_tasks
        FROM `group_members` gm
        JOIN `students` s ON s.id = gm.student_id
        JOIN `users`    u ON u.id = s.user_id
        WHERE gm.group_id = ?
        ORDER BY gm.role DESC, s.id
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($rs)) {
        $groupMembers[] = $row;
    }
    mysqli_stmt_close($stmt);

    // -------- 4.2 Group tasks --------
    $stmt = mysqli_prepare($conn, "
        SELECT
            t.id,
            t.title,
            t.status,
            t.priority,
            t.due_date,
            t.completed_at,
            t.assigned_to,
            au.username AS owner_name
        FROM `tasks` t
        LEFT JOIN `students` s ON s.id = t.assigned_to
        LEFT JOIN `users`    au ON au.id = s.user_id
        WHERE t.group_id = ?
        ORDER BY
            CASE t.status
                WHEN 'In Progress' THEN 1
                WHEN 'Pending'     THEN 2
                WHEN 'Completed'   THEN 3
                ELSE 4
            END,
            t.due_date
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($rs)) {
        $tasks[] = $row;
        $progress['total']++;
        if ($row['status'] === 'Completed') {
            $progress['done']++;
        }
    }
    mysqli_stmt_close($stmt);

    if ($progress['total'] > 0) {
        $progress['pct'] = (int) round($progress['done'] / $progress['total'] * 100);
    }

    // -------- 4.3 Recent activity --------
    $stmt = mysqli_prepare($conn, "
        SELECT
            a.action,
            a.detail,
            a.created_at,
            u.username AS actor_name
        FROM `activities` a
        LEFT JOIN `users` u ON u.id = a.actor_id
        WHERE a.group_id = ?
        ORDER BY a.created_at DESC
        LIMIT 6
    ");
    mysqli_stmt_bind_param($stmt, "i", $groupID);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($rs)) {
        $activities[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// =========================================================
// 5. Helper functions
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
    <title>My Team &amp; Project | TeamBox</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: #f5f5f5; color: #222; }

        .navbar {
            width: 100%;
            padding: 15px 30px;
            background: black;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
        }

        .logo-area { display: flex; justify-content: center; align-items: center; gap: 12px; }
        .logo-area img { width: 65px; height: 65px; object-fit: contain; display: block; }
        .logo-area span { color: white; font-size: 30px; font-weight: bold; }

        .function-menu {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 22px;
        }

        .nav-link {
            color: white; font-size: 15px; text-decoration: none;
            padding: 6px 2px; transition: color 0.3s;
        }
        .nav-link:hover { color: #bbbbbb; }
        .nav-link.active { color: white; font-weight: bold; border-bottom: 2px solid white; }
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

        /* Group switcher */
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

        /* Empty state */
        .empty-state {
            padding: 60px 40px;
            background: white;
            border-radius: 15px;
            border-left: 6px solid black;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .empty-state .empty-icon {
            width: 70px; height: 70px; margin: 0 auto 20px;
            background: #f0f0f0; color: #555;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 30px; font-weight: bold;
        }
        .empty-state h2 { font-size: 24px; margin-bottom: 12px; color: #222; }
        .empty-state p {
            color: #666; font-size: 15px; line-height: 1.6;
            max-width: 560px; margin: 0 auto 28px;
        }
        .empty-state .create-group-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 200px; height: 50px; padding: 0 24px;
            background: black; color: white;
            border: 2px solid black; border-radius: 9px;
            font-size: 16px; font-weight: bold;
            text-decoration: none;
            cursor: pointer; transition: 0.3s;
        }
        .empty-state .create-group-btn:hover { background: #333; }
        .empty-state .create-group-btn:active { transform: scale(0.98); }

        /* Team Overview */
        .team-overview {
            padding: 30px; margin-bottom: 25px;
            background: white; border-left: 6px solid black;
            border-radius: 15px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .team-header {
            margin-bottom: 25px;
            display: flex; justify-content: space-between;
            align-items: flex-start; gap: 20px;
        }
        .team-title h2 { margin-bottom: 8px; font-size: 28px; }
        .team-title p { color: #666; font-size: 16px; line-height: 1.6; }

        .team-info-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 18px; margin-bottom: 28px;
        }
        .team-info-card {
            padding: 20px; background: #f7f7f7;
            border: 1px solid #dddddd; border-radius: 10px;
        }
        .team-info-card h3 { margin-bottom: 10px; color: #777; font-size: 14px; }
        .team-info-card p { color: #222; font-size: 17px; font-weight: bold; }

        /* Project */
        .project-section {
            padding: 30px; margin-bottom: 25px;
            background: white; border-radius: 15px;
            border-left: 6px solid black;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .project-section h2 {
            padding-bottom: 15px; margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee; font-size: 23px;
        }
        .info-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin-bottom: 25px;
        }
        .info-box {
            padding: 20px; background: #fafafa;
            border: 1px solid #eeeeee; border-radius: 12px;
        }
        .info-box h3 {
            color: #555; font-size: 14px;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .info-box p { font-size: 17px; font-weight: bold; }

        .project-description {
            padding-top: 20px; border-top: 2px solid #eeeeee;
            color: #666; font-size: 15px; line-height: 1.7;
        }

        .team-progress { padding-top: 22px; border-top: 2px solid #eeeeee; }
        .progress-heading {
            margin-bottom: 12px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .progress-heading strong { font-size: 18px; }
        .progress-heading span { font-size: 17px; font-weight: bold; }
        .progress-bar {
            width: 100%; height: 16px;
            background: #dddddd; border-radius: 12px; overflow: hidden;
        }
        .progress-value {
            height: 100%; background: black; border-radius: 12px;
        }
        .progress-note { margin-top: 10px; color: #777; font-size: 14px; }

        /* Content boxes */
        .information-grid {
            display: grid; grid-template-columns: 2fr 1fr;
            gap: 25px; margin-bottom: 25px;
        }
        .content-box {
            padding: 28px; background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .content-box h2 {
            padding-bottom: 15px; margin-bottom: 20px;
            border-bottom: 2px solid #eeeeee; font-size: 23px;
        }

        /* Members */
        .member-list {
            display: grid; grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .member-card {
            padding: 20px; background: #f8f8f8;
            border: 1px solid #dddddd; border-radius: 12px;
            display: flex; align-items: center; gap: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .member-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }
        .member-avatar {
            width: 52px; height: 52px; flex-shrink: 0;
            background: black; color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 18px; font-weight: bold;
        }
        .member-information { flex: 1; min-width: 0; }
        .member-information h3 {
            margin-bottom: 5px; font-size: 17px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .member-information p { margin-bottom: 4px; color: #777; font-size: 13px; }
        .member-role {
            display: inline-block; padding: 5px 9px; margin-top: 4px;
            background: #eeeeee; color: #555; border-radius: 15px;
            font-size: 12px; font-weight: bold;
        }
        .leader-role { background: black; color: white; }

        /* Details */
        .detail-item { margin-bottom: 20px; }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-item h3 { margin-bottom: 7px; color: #555; font-size: 15px; }
        .detail-item p { color: #222; font-size: 16px; line-height: 1.5; }

        /* Tasks */
        .task-list { display: flex; flex-direction: column; }
        .task-item {
            padding: 17px 0; border-bottom: 1px solid #eeeeee;
            display: flex; justify-content: space-between;
            align-items: center; gap: 20px;
        }
        .task-item:last-child { border-bottom: none; }
        .task-information { flex: 1; min-width: 0; }
        .task-information h3 { margin-bottom: 6px; font-size: 17px; }
        .task-information p { margin-bottom: 5px; color: #777; font-size: 14px; }
        .task-status {
            padding: 7px 12px; border-radius: 20px;
            font-size: 13px; font-weight: bold; white-space: nowrap;
        }
        .status-completed { background: #d9f5df; color: #176b2c; }
        .status-progress  { background: #fff1c2; color: #765900; }
        .status-pending   { background: #eeeeee; color: #555; }

        /* Activity */
        .activity-item {
            padding: 15px 0; border-bottom: 1px solid #eeeeee;
            display: flex; gap: 15px;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-dot {
            width: 13px; height: 13px; margin-top: 5px; flex-shrink: 0;
            background: black; border-radius: 50%;
        }
        .activity-information h3 { margin-bottom: 5px; font-size: 15px; }
        .activity-information p { color: #777; font-size: 13px; line-height: 1.5; }

        .muted { color: #777; font-size: 14px; }

        /* Responsive */
        @media (max-width: 1000px) {
            .team-info-grid { grid-template-columns: repeat(2, 1fr); }
            .info-grid { grid-template-columns: repeat(2, 1fr); }
            .information-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .navbar { padding: 15px; }
            .logo-area img { width: 55px; height: 55px; }
            .logo-area span { font-size: 26px; }
            .function-menu { flex-direction: column; gap: 12px; }
            .main-content { width: 94%; margin: 25px auto; }
            .page-heading h1 { font-size: 28px; }

            .group-switcher { flex-direction: column; align-items: stretch; }
            .group-switcher select { min-width: 100%; }

            .team-header { flex-direction: column; }
            .team-info-grid,
            .info-grid,
            .member-list { grid-template-columns: 1fr; }
            .task-item { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo-area">
        <img src="TeamBox_logo_black.png" alt="TeamBox Logo">
        <span>TeamBox</span>
    </div>

    <div class="function-menu">
        <a href="student_index.php" class="nav-link">Dashboard</a>
        <a href="student_create_group.php" class="nav-link">Create Group</a>
        <a href="my_team.php" class="nav-link active">My Team</a>
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
        <h1>My Team &amp; Project</h1>
        <p>
            View your team information, project details, members, current
            progress, assigned tasks and recent team activities — all in
            one page.
        </p>
    </section>

    <?php if (count($myGroups) === 0): ?>

        <!-- =========================================================
             未 send 去 supervisor → 顯示提示 + Create Group 按鈕
             ========================================================= -->
        <div class="empty-state">
            <div class="empty-icon">+</div>

            <?php if ($pendingGroupCount > 0): ?>

                <h2>Your team has not been submitted yet</h2>
                <p>
                    You have a team, but it has not been sent to your
                    supervisor. Once the group creator sends it to the
                    supervisor, your team will appear here automatically.
                    Go to <strong>Create Group</strong> to manage your
                    team and submit it.
                </p>
                <a href="student_create_group.php" class="create-group-btn">
                    Manage My Group
                </a>

            <?php else: ?>

                <h2>You are not in any group yet</h2>
                <p>
                    You haven't joined or created a group for this course.
                    Create a new group to start your final year project,
                    invite classmates and submit to your supervisor.
                </p>
                <a href="student_create_group.php" class="create-group-btn">
                    Create Group
                </a>

            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- =========================================================
             多過一個已 submit group → 顯示切換下拉
             （下拉唔再顯示 (Executing) / (Voting)）
             ========================================================= -->
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
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <script>
            function switchGroup(groupId) {
                window.location.href = 'my_team.php?group_id=' + groupId;
            }
        </script>
    <?php endif; ?>

        <!-- =========================================================
             Team Overview（移除右上角狀態 pill）
             ========================================================= -->
        <section class="team-overview">
            <div class="team-header">
                <div class="team-title">
                    <h2><?php echo htmlspecialchars($myGroup['group_name']); ?></h2>
                    <p>
                        <?php echo htmlspecialchars($myGroup['project_name'] ?: '—'); ?>
                        <?php if (!empty($myGroup['project_description'])): ?>
                            · <?php echo htmlspecialchars($myGroup['project_description']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="team-info-grid">
                <div class="team-info-card">
                    <h3>Project</h3>
                    <p><?php echo htmlspecialchars($myGroup['project_name'] ?: '—'); ?></p>
                </div>
                <div class="team-info-card">
                    <h3>Team Leader</h3>
                    <p>
                        <?php
                        $leaderName = '—';
                        foreach ($groupMembers as $m) {
                            if ((int)$m['student_id'] === (int)$myGroup['leader_id']) {
                                $leaderName = $m['name'];
                                break;
                            }
                        }
                        echo htmlspecialchars($leaderName);
                        ?>
                    </p>
                </div>
                <div class="team-info-card">
                    <h3>Team Members</h3>
                    <p><?php echo count($groupMembers); ?> Members</p>
                </div>
                <div class="team-info-card">
                    <h3>Supervisor</h3>
                    <p><?php echo htmlspecialchars($myGroup['supervisor_name'] ?: '—'); ?></p>
                </div>
            </div>

            <div class="team-progress">
                <div class="progress-heading">
                    <strong>Overall Team Progress</strong>
                    <span><?php echo $progress['pct']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-value" style="width: <?php echo $progress['pct']; ?>%;"></div>
                </div>
                <p class="progress-note">
                    <?php echo $progress['done']; ?> out of <?php echo $progress['total']; ?> team tasks have been completed.
                </p>
            </div>
        </section>

        <!-- =========================================================
             Project Details
             ========================================================= -->
        <section class="project-section">
            <h2>Project Details</h2>

            <div class="info-grid">
                <div class="info-box">
                    <h3>Project ID</h3>
                    <p>PRJ-<?php echo str_pad((int)$myGroup['project_id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="info-box">
                    <h3>Course</h3>
                    <p>
                        <?php
                        echo htmlspecialchars(
                                !empty($myGroup['course_code'])
                                        ? $myGroup['course_code'] . ' - ' . $myGroup['course_name']
                                        : '—'
                        );
                        ?>
                    </p>
                </div>
                <div class="info-box">
                    <h3>Supervisor</h3>
                    <p><?php echo htmlspecialchars($myGroup['supervisor_name'] ?: '—'); ?></p>
                </div>
                <div class="info-box">
                    <h3>Start Date</h3>
                    <p><?php echo $myGroup['period_start'] ? date('d M Y', strtotime($myGroup['period_start'])) : '—'; ?></p>
                </div>
                <div class="info-box">
                    <h3>Deadline</h3>
                    <p><?php echo $myGroup['period_end'] ? date('d M Y', strtotime($myGroup['period_end'])) : '—'; ?></p>
                </div>
                <div class="info-box">
                    <h3>Members</h3>
                    <p><?php echo count($groupMembers); ?> Students</p>
                </div>
            </div>

            <?php if (!empty($myGroup['project_description'])): ?>
                <p class="project-description">
                    <?php echo htmlspecialchars($myGroup['project_description']); ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- =========================================================
             Members + Team Details
             （移除「Current Project Stage」同「Team Formation Status」）
             ========================================================= -->
        <section class="information-grid">

            <div class="content-box">
                <h2>Team Members</h2>

                <?php if (count($groupMembers) === 0): ?>
                    <p class="muted">No members yet.</p>
                <?php endif; ?>

                <div class="member-list">
                    <?php foreach ($groupMembers as $m): ?>
                        <div class="member-card">
                            <div class="member-avatar">
                                <?php echo htmlspecialchars(initials($m['name'])); ?>
                            </div>
                            <div class="member-information">
                                <h3><?php echo htmlspecialchars($m['name']); ?></h3>
                                <p>Student ID: <?php echo htmlspecialchars($m['student_number']); ?></p>
                                <?php if ($m['role'] === 'leader'): ?>
                                    <span class="member-role leader-role">Team Leader</span>
                                <?php else: ?>
                                    <span class="member-role">Team Member</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="content-box">
                <h2>Team Details</h2>

                <div class="detail-item">
                    <h3>Team Created</h3>
                    <p><?php echo $myGroup['created_at'] ? date('d M Y', strtotime($myGroup['created_at'])) : '—'; ?></p>
                </div>

                <div class="detail-item">
                    <h3>Leader Election</h3>
                    <p>
                        <?php
                        if (!empty($myGroup['leader_id'])) {
                            echo 'Completed';
                        } else {
                            echo 'In Progress';
                        }
                        ?>
                    </p>
                </div>

                <div class="detail-item">
                    <h3>Formed At</h3>
                    <p><?php echo $myGroup['formed_at'] ? date('d M Y H:i', strtotime($myGroup['formed_at'])) : '—'; ?></p>
                </div>
            </div>

        </section>

        <!-- =========================================================
             Tasks + Activity
             ========================================================= -->
        <section class="information-grid">

            <div class="content-box">
                <h2>Team Tasks</h2>

                <?php if (count($tasks) === 0): ?>
                    <p class="muted">No tasks assigned yet.</p>
                <?php endif; ?>

                <div class="task-list">
                    <?php foreach ($tasks as $t): ?>
                        <?php
                        if ($t['status'] === 'Completed')      { $cls = 'status-completed'; }
                        elseif ($t['status'] === 'In Progress') { $cls = 'status-progress'; }
                        else                                     { $cls = 'status-pending'; }
                        ?>
                        <div class="task-item">
                            <div class="task-information">
                                <h3><?php echo htmlspecialchars($t['title']); ?></h3>
                                <p>Deadline: <?php echo $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—'; ?></p>
                                <p>Assigned to: <?php echo htmlspecialchars($t['owner_name'] ?: '—'); ?></p>
                            </div>
                            <span class="task-status <?php echo $cls; ?>">
                                <?php echo htmlspecialchars($t['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="content-box">
                <h2>Recent Activity</h2>

                <?php if (count($activities) === 0): ?>
                    <p class="muted">No activity yet.</p>
                <?php endif; ?>

                <?php foreach ($activities as $a): ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-information">
                            <h3>
                                <?php if (!empty($a['actor_name'])): ?>
                                    <?php echo htmlspecialchars($a['actor_name']); ?>
                                <?php else: ?>
                                    System
                                <?php endif; ?>
                            </h3>
                            <p>
                                <?php echo htmlspecialchars($a['detail']); ?>
                                <br>
                                <span class="muted"><?php echo timeAgo($a['created_at']); ?></span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </section>

    <?php endif; ?>

</main>

</body>
</html>