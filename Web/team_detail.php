<?php
$active = 'teams';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
/** @var mysqli $conn */

$teamId = isset($_GET['id']) ? (int)$_GET['id'] : 1;


// =========================================================
// 1. Team + Project 信息
// =========================================================
$team = null;

$sql = "
    SELECT
        g.id,
        g.name            AS team_name,
        g.status,
        g.max_members,
        g.formed_at,
        g.leader_id,
        p.id              AS project_id,
        p.name            AS project_name,
        p.category        AS project_category,
        p.period_start,
        p.period_end,
        tu.username       AS project_supervisor
    FROM `Groups` g
    LEFT JOIN `Projects` p ON p.id = g.project_id
    LEFT JOIN `Teachers` t ON t.id = p.supervisor_id
    LEFT JOIN `Users`    tu ON tu.id = t.user_id
    WHERE g.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$team = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$team) {
    // 找不到 team → 回列表
    header('Location: teacher_teams.php');
    exit;
}


// =========================================================
// 2. Members
// =========================================================
$members = array();

$sql = "
    SELECT
        s.id            AS student_id,
        s.student_number,
        s.major,
        s.grade_level,
        u.username      AS name,
        gm.role,
        (SELECT COUNT(*) FROM `Tasks` t
            WHERE t.assigned_to = s.id
              AND t.status = 'Completed') AS done_tasks,
        (SELECT COUNT(*) FROM `Tasks` t2
            WHERE t2.assigned_to = s.id) AS total_tasks
    FROM `Group_Members` gm
    INNER JOIN `Students` s ON s.id = gm.student_id
    INNER JOIN `Users`    u ON u.id = s.user_id
    WHERE gm.group_id = ?
    ORDER BY gm.role DESC, s.id
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $members[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 3. Progress
// =========================================================

// Overall：完成任务 / 总任务
$doneTasks = 0;
$rs = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `Tasks` WHERE group_id = " . $teamId . " AND status = 'Completed'");
if ($rs) { $rc = mysqli_fetch_assoc($rs); $doneTasks = (int)$rc['c']; mysqli_free_result($rs); }

$totalTasks = 0;
$rs = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `Tasks` WHERE group_id = " . $teamId);
if ($rs) { $rc = mysqli_fetch_assoc($rs); $totalTasks = (int)$rc['c']; mysqli_free_result($rs); }

$overallPercent = $totalTasks > 0 ? (int)round($doneTasks / $totalTasks * 100) : 0;

// Roster Locked：有 leader 就认为锁了
$rosterLocked = !empty($team['leader_id']) ? $team['formed_at'] : null;

// Leader Elected：从 Activities 里找 'elected' 记录
$leaderElectedAt = null;
$stmt = mysqli_prepare($conn, "
    SELECT created_at FROM `Activities`
    WHERE group_id = ? AND action = 'elected'
    ORDER BY created_at DESC LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($rs);
if ($row) { $leaderElectedAt = $row['created_at']; }
mysqli_stmt_close($stmt);

// Last Activity
$lastActivity = null;
$stmt = mysqli_prepare($conn, "
    SELECT MAX(created_at) AS last_at FROM `Activities` WHERE group_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($rs);
if ($row && !empty($row['last_at'])) { $lastActivity = $row['last_at']; }
mysqli_stmt_close($stmt);

// 组装 progress 数组
$progress = array(
        array('label'=>'Overall',         'value'=>'<span class="progress"><span class="bar"><span style="width: ' . $overallPercent . '%"></span></span>' . $overallPercent . '%</span>', 'raw'=>true),
        array('label'=>'Tasks Completed', 'value'=>$doneTasks . ' / ' . $totalTasks),
        array('label'=>'Team Formed',     'value'=>$team['formed_at'] ? date('d M Y', strtotime($team['formed_at'])) : '—'),
        array('label'=>'Roster Locked',   'value'=>$rosterLocked ? date('d M Y', strtotime($rosterLocked)) : '—'),
        array('label'=>'Leader Elected',  'value'=>$leaderElectedAt ? date('d M Y', strtotime($leaderElectedAt)) : '—'),
        array('label'=>'Last Activity',   'value'=>$lastActivity ? date('H:i', strtotime($lastActivity)) . ' today' : '—'),
);


// =========================================================
// 4. Tasks
// =========================================================
$tasks = array();

$sql = "
    SELECT
        t.title,
        t.status,
        t.due_date,
        t.assigned_to,
        au.username AS owner_name
    FROM `Tasks` t
    LEFT JOIN `Students` s ON s.id = t.assigned_to
    LEFT JOIN `Users`    au ON au.id = s.user_id
    WHERE t.group_id = ?
    ORDER BY
        CASE t.status
            WHEN 'In Progress' THEN 1
            WHEN 'Pending'     THEN 2
            WHEN 'Completed'   THEN 3
            ELSE 4
        END,
        t.due_date
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $tasks[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 5. Files
// =========================================================
$files = array();

$sql = "
    SELECT
        f.id,
        f.file_name,
        f.file_size,
        f.uploaded_at,
        u.username AS owner_name
    FROM `Files` f
    LEFT JOIN `Students` s ON s.id = f.uploader_id
    LEFT JOIN `Users`    u ON u.id = s.user_id
    WHERE f.group_id = ?
    ORDER BY f.uploaded_at DESC
    LIMIT 10
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $files[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 6. Member Activity（客观数量，不评分）
// =========================================================
$memberActivity = array();

$sql = "
    SELECT
        s.id            AS student_id,
        u.username      AS name,
        (SELECT COUNT(*) FROM `Tasks` t
            WHERE t.assigned_to = s.id
              AND t.status = 'Completed') AS tasks,
        (SELECT COUNT(*) FROM `Files` f
            WHERE f.uploader_id = s.id) AS files,
        (SELECT COUNT(*) FROM `Messages` m
            WHERE m.sender_id = s.id) AS messages
    FROM `Group_Members` gm
    INNER JOIN `Students` s ON s.id = gm.student_id
    INNER JOIN `Users`    u ON u.id = s.user_id
    WHERE gm.group_id = ?
    ORDER BY s.id
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $memberActivity[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 7. Recent Activity
// =========================================================
$activities = array();

$sql = "
    SELECT
        a.detail,
        a.action,
        a.created_at,
        u.username AS actor_name
    FROM `Activities` a
    LEFT JOIN `Users` u ON u.id = a.actor_id
    WHERE a.group_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teamId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $activities[] = $row;
}
mysqli_stmt_close($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">

    <style>
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 34px;
            gap: 20px;
        }

        .page-head h1 {
            font-size: 21px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .page-head .meta {
            font-size: 13px;
            color: #8b8f98;
        }

        .back {
            display: inline-block;
            font-size: 13px;
            color: #8b8f98;
            text-decoration: none;
            margin-bottom: 22px;
            cursor: pointer;
        }

        .back:hover { color: #2c2f36; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .progress-table td { white-space: normal; }

        .progress-table td:first-child {
            width: 40%;
            background: #175196;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.03em;
        }

        .progress-table td:last-child { color: #2c2f36; }

        .task-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 16px;
            border-bottom: 1px solid #f2f3f5;
            font-size: 14px;
        }

        .task-row:last-child { border-bottom: none; }

        .task-row .task-name { color: #2c2f36; }

        .task-row .task-owner {
            font-size: 13px;
            color: #8b8f98;
            margin-top: 3px;
        }

        .task-row .task-right {
            text-align: right;
            white-space: nowrap;
        }

        .task-row .task-due {
            font-size: 12px;
            color: #a8acb4;
            margin-top: 3px;
        }

        .file-list {
            list-style: none;
            font-size: 14px;
        }

        .file-list li {
            padding: 13px 16px;
            border-bottom: 1px solid #f2f3f5;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .file-list li:last-child { border-bottom: none; }

        .activity-list {
            list-style: none;
            font-size: 14px;
        }

        .activity-list li {
            padding: 12px 16px;
            border-bottom: 1px solid #f2f3f5;
            display: flex;
            justify-content: space-between;
        }

        .activity-list li:last-child { border-bottom: none; }

        .activity-table td { white-space: normal; }
        .activity-table td:first-child { color: #2c2f36; }
        .activity-table td:not(:first-child) {
            text-align: right;
            color: #2c2f36;
            font-weight: 500;
        }
        .activity-table th:not(:first-child) { text-align: right; }

        .leader-mark {
            font-size: 11px;
            color: #6b6f78;
            margin-left: 6px;
            letter-spacing: 0.03em;
        }

        @media (max-width: 800px) {
            .info-grid { grid-template-columns: 1fr; gap: 0; }
        }

        @media (max-width: 700px) {
            .page-head { flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<main class="main">
    <div class="content">

        <a href="teacher_teams.php" class="back">← Back to Teams</a>

        <div class="page-head">
            <div>
                <h1><?php echo htmlspecialchars($team['team_name']); ?></h1>
                <div class="meta">
                    <?php echo htmlspecialchars($team['project_name']); ?> ·
                    <?php echo htmlspecialchars($team['project_category']); ?>
                </div>
            </div>
            <span class="pill"><?php echo htmlspecialchars($team['status']); ?></span>
        </div>

        <!-- Project -->
        <h2>Project</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Project Name</th>
                    <th>Category</th>
                    <th>Supervisor</th>
                    <th>Project Period</th>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars($team['project_name']); ?></td>
                    <td><?php echo htmlspecialchars($team['project_category']); ?></td>
                    <td><?php echo htmlspecialchars($team['project_supervisor']); ?></td>
                    <td>
                        <?php echo date('M Y', strtotime($team['period_start'])); ?>
                        –
                        <?php echo date('M Y', strtotime($team['period_end'])); ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Members -->
        <h2>Members</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Class</th>
                    <th>Role</th>
                    <th>Tasks Done</th>
                </tr>

                <?php if (count($members) === 0): ?>
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center; padding: 30px;">
                            No members yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($members as $m): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($m['name']); ?>
                            <?php if ($m['role'] === 'leader'): ?>
                                <span class="leader-mark">LEADER</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($m['student_number']); ?></td>
                        <td><?php echo htmlspecialchars(formatClass($m['major'], $m['grade_level'])); ?></td>
                        <td>
                            <?php if ($m['role'] === 'leader'): ?>
                                <span class="pill">Leader</span>
                            <?php else: ?>
                                <span class="muted">Member</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$m['done_tasks']; ?> / <?php echo (int)$m['total_tasks']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Progress + Division -->
        <div class="info-grid">
            <div>
                <h2>Progress</h2>
                <div class="card">
                    <table class="progress-table">
                        <?php foreach ($progress as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['label']); ?></td>
                                <td>
                                    <?php if (!empty($p['raw'])): ?>
                                        <?php echo $p['value']; ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($p['value']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div>
                <h2>Team Details</h2>
                <div class="card">
                    <table class="progress-table">
                        <tr>
                            <td>Team ID</td>
                            <td>#<?php echo (int)$team['id']; ?></td>
                        </tr>
                        <tr>
                            <td>Max Members</td>
                            <td><?php echo (int)$team['max_members']; ?></td>
                        </tr>
                        <tr>
                            <td>Current Members</td>
                            <td><?php echo count($members); ?></td>
                        </tr>
                        <tr>
                            <td>Formed</td>
                            <td><?php echo $team['formed_at'] ? date('d M Y', strtotime($team['formed_at'])) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td>Last Activity</td>
                            <td><?php echo timeAgo($lastActivity); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tasks -->
        <h2>Tasks</h2>
        <div class="card">
            <?php if (count($tasks) === 0): ?>
                <div class="muted" style="text-align: center; padding: 30px;">
                    No tasks assigned yet.
                </div>
            <?php endif; ?>

            <?php foreach ($tasks as $t): ?>
                <div class="task-row">
                    <div>
                        <div class="task-name"><?php echo htmlspecialchars($t['title']); ?></div>
                        <div class="task-owner">
                            <?php echo htmlspecialchars($t['owner_name'] ?: '—'); ?>
                        </div>
                    </div>
                    <div class="task-right">
                        <div><?php echo htmlspecialchars($t['status']); ?></div>
                        <div class="task-due">
                            <?php echo $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Files -->
        <h2>Files</h2>
        <div class="card">
            <ul class="file-list">
                <?php if (count($files) === 0): ?>
                    <li class="muted">No files uploaded yet.</li>
                <?php endif; ?>

                <?php foreach ($files as $f): ?>
                    <li>
                        <span>
                            <a href="file_view.php?id=<?php echo (int)$f['id']; ?>" class="link">
                                <?php echo htmlspecialchars($f['file_name']); ?>
                            </a>
                            <span class="muted">· <?php echo htmlspecialchars($f['owner_name']); ?></span>
                        </span>
                        <span class="muted">
                            <?php echo htmlspecialchars($f['file_size']); ?> ·
                            <?php echo timeAgo($f['uploaded_at']); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Member Activity -->
        <h2>Member Activity</h2>
        <p class="muted" style="margin-bottom: 12px;">
            Objective activity counts only — no scores, no ranking.
        </p>
        <div class="card">
            <table class="activity-table">
                <tr>
                    <th>Member</th>
                    <th>Tasks Completed</th>
                    <th>Files Uploaded</th>
                    <th>Messages</th>
                </tr>

                <?php if (count($memberActivity) === 0): ?>
                    <tr>
                        <td colspan="4" class="muted" style="text-align: center; padding: 30px;">
                            No activity yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($memberActivity as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['name']); ?></td>
                        <td><?php echo (int)$a['tasks']; ?></td>
                        <td><?php echo (int)$a['files']; ?></td>
                        <td><?php echo (int)$a['messages']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Activity -->
        <h2>Activity</h2>
        <div class="card">
            <ul class="activity-list">
                <?php if (count($activities) === 0): ?>
                    <li class="muted">No activity yet.</li>
                <?php endif; ?>

                <?php foreach ($activities as $a): ?>
                    <li>
                        <span>
                            <?php if (!empty($a['actor_name'])): ?>
                                <strong><?php echo htmlspecialchars($a['actor_name']); ?></strong>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($a['detail']); ?>
                        </span>
                        <span class="muted"><?php echo timeAgo($a['created_at']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>
</main>

</body>
</html>