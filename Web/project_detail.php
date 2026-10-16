<?php
$active = 'projects';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
/** @var mysqli $conn */

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 1;


// =========================================================
// 1. Project 基本信息
// =========================================================
$project = null;

$sql = "
    SELECT
        p.id,
        p.name,
        p.category,
        p.status,
        p.progress,
        p.period_start,
        p.period_end,
        p.description,
        u.username AS supervisor_name
    FROM `Projects` p
    LEFT JOIN `Teachers` t ON t.id = p.supervisor_id
    LEFT JOIN `Users`    u ON u.id = t.user_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$project) {
    header('Location: teacher_projects.php');
    exit;
}

$project['supervisor'] = $project['supervisor_name'];


// =========================================================
// 2. Teams
// =========================================================
$teams = array();

$sql = "
    SELECT
        g.id,
        g.name,
        g.status,
        g.max_members,
        u.username AS leader_name,
        (SELECT COUNT(*) FROM `Group_Members` gm WHERE gm.group_id = g.id) AS member_count,
        (SELECT COUNT(*) FROM `Tasks` t
            WHERE t.group_id = g.id AND t.status = 'Completed') AS done_tasks,
        (SELECT COUNT(*) FROM `Tasks` t2
            WHERE t2.group_id = g.id) AS total_tasks
    FROM `Groups` g
    LEFT JOIN `Students` s ON s.id = g.leader_id
    LEFT JOIN `Users`    u ON u.id = s.user_id
    WHERE g.project_id = ?
    ORDER BY g.id
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $teams[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 3. Milestones
// =========================================================
$milestones = array();

$sql = "
    SELECT
        id,
        name,
        start_date,
        end_date,
        status,
        progress
    FROM `Milestones`
    WHERE project_id = ?
    ORDER BY start_date
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $milestones[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 4. Tasks
// =========================================================
$tasks = array();

$sql = "
    SELECT
        t.title,
        t.status,
        t.due_date,
        g.name     AS team_name,
        u.username AS owner_name
    FROM `Tasks` t
    LEFT JOIN `Groups`   g ON g.id = t.group_id
    LEFT JOIN `Students` s ON s.id = t.assigned_to
    LEFT JOIN `Users`    u ON u.id = s.user_id
    WHERE t.project_id = ?
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
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $tasks[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 5. Files by Team
// =========================================================
$fileGroups = array();

foreach ($teams as $t) {

    $fileCount = 0;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM `Files` WHERE group_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $t['id']);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $rc = mysqli_fetch_assoc($rs);
    $fileCount = (int)$rc['c'];
    mysqli_stmt_close($stmt);

    if ($fileCount === 0) {
        continue;
    }

    $items = array();
    $stmt = mysqli_prepare($conn, "
        SELECT
            f.file_name,
            f.file_size,
            f.uploaded_at,
            u.username AS owner_name
        FROM `Files` f
        LEFT JOIN `Students` s ON s.id = f.uploader_id
        LEFT JOIN `Users`    u ON u.id = s.user_id
        WHERE f.group_id = ?
        ORDER BY f.uploaded_at DESC
        LIMIT 3
    ");
    mysqli_stmt_bind_param($stmt, "i", $t['id']);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($rs)) {
        $items[] = $row;
    }
    mysqli_stmt_close($stmt);

    $lastUpload = '—';
    if (count($items) > 0) {
        $lastUpload = $items[0]['file_name'] . ' · ' . timeAgo($items[0]['uploaded_at']);
    }

    $fileGroups[] = array(
            'team'        => $t['name'],
            'team_id'     => $t['id'],
            'file_count'  => $fileCount,
            'total_size'  => '—',
            'last_upload' => $lastUpload,
            'items'       => $items,
    );
}


// =========================================================
// 6. Activities
// =========================================================
$activities = array();

$sql = "
    SELECT
        a.detail,
        a.created_at,
        u.username AS actor_name
    FROM `Activities` a
    LEFT JOIN `Users` u ON u.id = a.actor_id
    WHERE a.project_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $activities[] = $row;
}
mysqli_stmt_close($stmt);


// =========================================================
// 7. 甘特图计算
// =========================================================
$projStart = strtotime($project['period_start']);
$projEnd   = strtotime($project['period_end']);
$projSpan  = $projEnd - $projStart;
if ($projSpan <= 0) { $projSpan = 1; }

foreach ($milestones as &$m) {
    $ms = strtotime($m['start_date']);
    $me = strtotime($m['end_date']);
    $m['offset'] = (($ms - $projStart) / $projSpan) * 100;
    $m['width']  = (($me - $ms) / $projSpan) * 100;
    if ($m['width'] < 2) { $m['width'] = 2; }

    $today = strtotime(date('Y-m-d'));
    $m['is_late'] = ($today > $me && $m['status'] !== 'Completed');

    $m['start'] = $m['start_date'];
    $m['end']   = $m['end_date'];
}
unset($m);

$todayTs      = strtotime(date('Y-m-d'));
$todayPercent = (($todayTs - $projStart) / $projSpan) * 100;
if ($todayPercent < 0)   { $todayPercent = 0; }
if ($todayPercent > 100) { $todayPercent = 100; }
$todayInRange = ($todayTs >= $projStart && $todayTs <= $projEnd);

$monthTicks = array();
$cursor = strtotime(date('Y-m-01', $projStart));
$lastMonth = date('Y-m', $projEnd);

while (date('Y-m', $cursor) <= $lastMonth) {
    $monthStart = max($cursor, $projStart);
    $percent    = (($monthStart - $projStart) / $projSpan) * 100;

    $monthTicks[] = array(
            'label'   => date('M', $cursor),
            'year'    => date('Y', $cursor),
            'percent' => $percent,
    );

    $cursor = strtotime('+1 month', $cursor);
}

$lateCount = 0;
foreach ($milestones as $m) {
    if (!empty($m['is_late'])) { $lateCount++; }
}


// =========================================================
// 8. 完成 / 总任务
// =========================================================
$doneTasks  = 0;
$totalTasks = 0;
foreach ($tasks as $t) {
    $totalTasks++;
    if ($t['status'] === 'Completed') { $doneTasks++; }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> | TeamBox</title>
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

        .file-groups {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .file-group {
            border: 1px solid #e6e8eb;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .file-group-head {
            padding: 16px 18px;
            background: #f7f8fa;
            border-bottom: 1px solid #e6e8eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .file-group-head h3 {
            font-size: 14px;
            font-weight: 600;
            color: #2c2f36;
        }

        .file-group-head .file-group-meta {
            font-size: 12px;
            color: #8b8f98;
        }

        .file-group-body { padding: 4px 0; }

        .file-group-body ul {
            list-style: none;
            font-size: 13px;
        }

        .file-group-body ul li {
            padding: 10px 18px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #f2f3f5;
        }

        .file-group-body ul li:last-child { border-bottom: none; }
        .file-group-body ul li span:first-child { color: #2c2f36; }

        .file-group-foot {
            padding: 10px 18px;
            background: #fafbfc;
            border-top: 1px solid #e6e8eb;
            font-size: 12px;
            color: #8b8f98;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .gantt-wrap { padding: 24px; }

        .gantt-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #8b8f98;
            margin-bottom: 12px;
            padding-left: 160px;
        }

        .gantt-header .gantt-start,
        .gantt-header .gantt-end {
            font-weight: 600;
            color: #6b6f78;
        }

        .gantt-months {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #e6e8eb;
            padding-bottom: 8px;
        }

        .gantt-months-label {
            width: 160px;
            flex-shrink: 0;
        }

        .gantt-months-track {
            position: relative;
            flex: 1;
            height: 18px;
        }

        .gantt-month {
            position: absolute;
            top: 0;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: 600;
            color: #6b6f78;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .gantt-month:first-child { transform: translateX(0); }

        .gantt-body { position: relative; }

        .gantt-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            min-height: 32px;
        }

        .gantt-row:last-child { margin-bottom: 0; }

        .gantt-label {
            width: 160px;
            flex-shrink: 0;
            font-size: 13px;
            color: #2c2f36;
            padding-right: 12px;
        }

        .gantt-label .gantt-label-status {
            display: block;
            font-size: 11px;
            color: #8b8f98;
            margin-top: 2px;
        }

        .gantt-track {
            position: relative;
            flex: 1;
            height: 22px;
            background: #f2f3f5;
            border-radius: 6px;
            overflow: hidden;
        }

        .gantt-bar {
            position: absolute;
            top: 0;
            height: 100%;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding: 0 8px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            z-index: 2;
        }

        .gantt-bar.done    { background: #175196; }
        .gantt-bar.active  { background: #e0a030; color: #2c2f36; }
        .gantt-bar.pending { background: #c9ccd1; color: #2c2f36; }
        .gantt-bar.late    { background: #d32f2f; color: #fff; }

        .gantt-month-line {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 1px;
            background: #eef0f3;
            z-index: 1;
            pointer-events: none;
        }

        .gantt-today-line {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #d32f2f;
            z-index: 5;
            pointer-events: none;
        }

        .gantt-today-line::before {
            content: "";
            position: absolute;
            top: -4px;
            left: -3px;
            width: 8px;
            height: 8px;
            background: #d32f2f;
            border-radius: 50%;
        }

        .gantt-today-label {
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            background: #d32f2f;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .gantt-late-text {
            color: #d32f2f;
            font-weight: 600;
        }

        .gantt-legend {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #f2f3f5;
            font-size: 12px;
            color: #6b6f78;
        }

        .gantt-legend .dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            margin-right: 6px;
            vertical-align: middle;
        }

        .gantt-legend .dot.done    { background: #175196; }
        .gantt-legend .dot.active  { background: #e0a030; }
        .gantt-legend .dot.pending { background: #c9ccd1; }
        .gantt-legend .dot.late    { background: #d32f2f; }
        .gantt-legend .dot.today   { background: #d32f2f; border-radius: 50%; }

        .review-cta {
            padding: 26px 28px;
            background: #fff;
            border: 1px solid #e6e8eb;
            border-left: 4px solid #175196;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .review-cta h3 {
            font-size: 16px;
            font-weight: 600;
            color: #2c2f36;
            margin-bottom: 6px;
        }

        .review-cta p {
            font-size: 13px;
            color: #8b8f98;
            line-height: 1.5;
            max-width: 620px;
        }

        .review-cta .cta-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        @media (max-width: 900px) {
            .file-groups { grid-template-columns: 1fr; }
            .gantt-header { padding-left: 0; }
            .gantt-months { display: none; }
            .gantt-month-line { display: none; }
            .gantt-today-line { display: none; }
            .gantt-row { flex-direction: column; align-items: stretch; }
            .gantt-label { width: 100%; padding: 0 0 6px 0; }
            .review-cta { flex-direction: column; align-items: flex-start; }
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

        <a href="teacher_projects.php" class="back">← Back to Projects</a>

        <!-- 页面头部 -->
        <div class="page-head">
            <div>
                <h1><?php echo htmlspecialchars($project['name']); ?></h1>
                <div class="meta">
                    <?php echo htmlspecialchars($project['category']); ?> ·
                    Supervisor: <?php echo htmlspecialchars($project['supervisor']); ?>
                    <?php if ($lateCount > 0): ?>
                        · <span class="gantt-late-text"><?php echo $lateCount; ?> milestone<?php echo $lateCount > 1 ? 's' : ''; ?> late</span>
                    <?php endif; ?>
                </div>
            </div>
            <span class="pill"><?php echo htmlspecialchars($project['status']); ?></span>
        </div>

        <!-- Overview -->
        <h2>Overview</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Category</th>
                    <th>Supervisor</th>
                    <th>Project Period</th>
                    <th>Teams</th>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars($project['category']); ?></td>
                    <td><?php echo htmlspecialchars($project['supervisor']); ?></td>
                    <td>
                        <?php echo date('M Y', $projStart); ?>
                        –
                        <?php echo date('M Y', $projEnd); ?>
                    </td>
                    <td><?php echo count($teams); ?> teams</td>
                </tr>
            </table>
        </div>

        <p class="muted" style="margin: -10px 0 30px;">
            <?php echo htmlspecialchars($project['description']); ?>
        </p>

        <!-- Review Center CTA -->
        <div class="review-cta">
            <div>
                <h3>Review Center</h3>
                <p>
                    Check files uploaded by this project's teams, approve or
                    reject them, and review group formation requests. Rejected
                    files will be sent back to the students for revision.
                </p>
            </div>
            <div class="cta-actions">
                <a href="teacher_review.php" class="btn-primary">Open Review Center</a>
            </div>
        </div>

        <!-- Teams -->
        <h2>Teams in this Project</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Team Name</th>
                    <th>Leader</th>
                    <th>Members</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th></th>
                </tr>

                <?php if (count($teams) === 0): ?>
                    <tr>
                        <td colspan="6" class="muted" style="text-align: center; padding: 30px;">
                            No teams in this project yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($teams as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                        <td><?php echo htmlspecialchars($t['leader_name'] ?: '—'); ?></td>
                        <td>
                            <?php echo (int)$t['member_count']; ?> / <?php echo (int)$t['max_members']; ?>
                        </td>
                        <td>
                            <?php
                            $done  = (int)$t['done_tasks'];
                            $total = (int)$t['total_tasks'];
                            echo $total > 0 ? ($done . ' / ' . $total) : '—';
                            ?>
                        </td>
                        <td><span class="pill"><?php echo htmlspecialchars($t['status']); ?></span></td>
                        <td><a href="team_detail.php?id=<?php echo (int)$t['id']; ?>" class="link">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Progress + Milestones -->
        <div class="info-grid">
            <div>
                <h2>Progress</h2>
                <div class="card">
                    <table class="progress-table">
                        <tr>
                            <td>Overall</td>
                            <td>
                                <span class="progress">
                                    <span class="bar">
                                        <span style="width: <?php echo (int)$project['progress']; ?>%"></span>
                                    </span>
                                    <?php echo (int)$project['progress']; ?>%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Teams Involved</td>
                            <td><?php echo count($teams); ?></td>
                        </tr>
                        <tr>
                            <td>Tasks Completed</td>
                            <td><?php echo $doneTasks; ?> / <?php echo $totalTasks; ?></td>
                        </tr>
                        <tr>
                            <td>Milestones Late</td>
                            <td>
                                <?php if ($lateCount > 0): ?>
                                    <span class="gantt-late-text"><?php echo $lateCount; ?></span>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Start Date</td>
                            <td><?php echo date('d M Y', $projStart); ?></td>
                        </tr>
                        <tr>
                            <td>Deadline</td>
                            <td><?php echo date('d M Y', $projEnd); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div>
                <h2>Milestones</h2>
                <div class="card">
                    <table class="progress-table">
                        <?php if (count($milestones) === 0): ?>
                            <tr>
                                <td colspan="2" class="muted" style="text-align: center;">
                                    No milestones.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($milestones as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($m['status']); ?>
                                    <span class="muted">· <?php echo date('d M Y', strtotime($m['end'])); ?></span>
                                    <?php if ($m['is_late']): ?>
                                        · <span class="gantt-late-text">Late</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gantt -->
        <h2>Project Timeline (Gantt)</h2>
        <div class="card">
            <div class="gantt-wrap">

                <div class="gantt-header">
                    <span class="gantt-start"><?php echo date('d M Y', $projStart); ?></span>
                    <span class="gantt-end"><?php echo date('d M Y', $projEnd); ?></span>
                </div>

                <div class="gantt-months">
                    <div class="gantt-months-label"></div>
                    <div class="gantt-months-track">
                        <?php foreach ($monthTicks as $tick): ?>
                            <div class="gantt-month"
                                 style="left: <?php echo round($tick['percent'], 2); ?>%;"
                                 title="<?php echo htmlspecialchars($tick['label'] . ' ' . $tick['year']); ?>">
                                <?php echo htmlspecialchars($tick['label']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="gantt-body">

                    <?php if ($todayInRange): ?>
                        <div class="gantt-today-line"
                             style="left: calc(160px + (100% - 160px) * <?php echo round($todayPercent / 100, 4); ?>);"
                             title="Today · <?php echo date('d M Y'); ?>">
                            <span class="gantt-today-label">Today</span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($monthTicks as $i => $tick): ?>
                        <?php if ($i === 0) continue; ?>
                        <div class="gantt-month-line"
                             style="left: calc(160px + (100% - 160px) * <?php echo round($tick['percent'] / 100, 4); ?>);"></div>
                    <?php endforeach; ?>

                    <?php foreach ($milestones as $m): ?>
                        <?php
                        if ($m['status'] === 'Completed')        { $cls = 'done'; }
                        elseif ($m['is_late'])                    { $cls = 'late'; }
                        elseif ($m['status'] === 'In Progress')   { $cls = 'active'; }
                        else                                      { $cls = 'pending'; }
                        ?>
                        <div class="gantt-row">
                            <div class="gantt-label">
                                <?php echo htmlspecialchars($m['name']); ?>
                                <span class="gantt-label-status">
                                    <?php echo date('d M', strtotime($m['start'])); ?>
                                    –
                                    <?php echo date('d M Y', strtotime($m['end'])); ?>
                                    <?php if ($m['is_late']): ?>
                                        · <span class="gantt-late-text">Late</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="gantt-track">
                                <div class="gantt-bar <?php echo $cls; ?>"
                                     style="left: <?php echo round($m['offset'], 2); ?>%;
                                             width: <?php echo round($m['width'], 2); ?>%;"
                                     title="<?php echo htmlspecialchars($m['name']); ?> · <?php echo (int)$m['progress']; ?>%">
                                    <?php echo (int)$m['progress']; ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="gantt-legend">
                    <span><span class="dot done"></span>Completed</span>
                    <span><span class="dot active"></span>In Progress</span>
                    <span><span class="dot pending"></span>Pending</span>
                    <span><span class="dot late"></span>Late</span>
                    <span><span class="dot today"></span>Today</span>
                </div>

            </div>
        </div>

        <!-- Tasks -->
        <h2>Tasks</h2>
        <div class="card">
            <?php if (count($tasks) === 0): ?>
                <div class="muted" style="text-align: center; padding: 30px;">
                    No tasks yet.
                </div>
            <?php endif; ?>

            <?php foreach ($tasks as $t): ?>
                <div class="task-row">
                    <div>
                        <div class="task-name"><?php echo htmlspecialchars($t['title']); ?></div>
                        <div class="task-owner">
                            <?php echo htmlspecialchars($t['team_name'] ?: '—'); ?> ·
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

        <!-- Files by Team -->
        <h2>Files by Team</h2>

        <?php if (count($fileGroups) === 0): ?>
            <div class="card">
                <div class="muted" style="text-align: center; padding: 30px;">
                    No files uploaded yet.
                </div>
            </div>
        <?php endif; ?>

        <div class="file-groups">
            <?php foreach ($fileGroups as $g): ?>
                <div class="file-group">

                    <div class="file-group-head">
                        <h3><?php echo htmlspecialchars($g['team']); ?></h3>
                        <span class="file-group-meta">
                            <?php echo (int)$g['file_count']; ?> files
                        </span>
                    </div>

                    <div class="file-group-body">
                        <ul>
                            <?php foreach ($g['items'] as $f): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($f['file_name']); ?></span>
                                    <span class="muted">
                                        <?php echo htmlspecialchars($f['owner_name']); ?> ·
                                        <?php echo htmlspecialchars($f['file_size']); ?> ·
                                        <?php echo timeAgo($f['uploaded_at']); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="file-group-foot">
                        <span>Last: <?php echo htmlspecialchars($g['last_upload']); ?></span>
                        <a href="teacher_files.php" class="link">View all</a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Activity -->
        <h2 style="margin-top: 30px;">Activity</h2>
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