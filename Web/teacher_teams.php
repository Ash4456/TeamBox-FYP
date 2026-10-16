<?php
$active = 'teams';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
/** @var mysqli $conn */


// =========================================================
// 当前老师的 Teachers.id
// =========================================================
$teacherId = 0;
if (isset($_SESSION['userID'])) {
    $stmtT = mysqli_prepare($conn, "SELECT id FROM `teachers` WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtT, "i", $_SESSION['userID']);
    mysqli_stmt_execute($stmtT);
    $rsT = mysqli_stmt_get_result($stmtT);
    $rowT = mysqli_fetch_assoc($rsT);
    mysqli_stmt_close($stmtT);
    if ($rowT) { $teacherId = (int)$rowT['id']; }
}


// =========================================================
// 1. 汇总数字（只看我负责项目下的组）
// =========================================================

// Total Teams
$totalTeams = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `groups` g
    INNER JOIN `projects` p ON p.id = g.project_id
    WHERE p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$totalTeams = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Formed Teams
$formedTeams = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `groups` g
    INNER JOIN `projects` p ON p.id = g.project_id
    WHERE p.supervisor_id = ?
      AND g.status IN ('Voting','Executing','Completed')
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$formedTeams = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Recruiting
$recruiting = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `groups` g
    INNER JOIN `projects` p ON p.id = g.project_id
    WHERE p.supervisor_id = ?
      AND g.status = 'Recruiting'
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$recruiting = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Past Due Tasks（我负责项目下）
$pastDue = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `tasks` t
    INNER JOIN `projects` p ON p.id = t.project_id
    WHERE p.supervisor_id = ?
      AND t.status <> 'Completed'
      AND t.due_date < CURDATE()
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$pastDue = (int)$rc['c'];
mysqli_stmt_close($stmt);


// =========================================================
// 2. 搜索 / 筛选
// =========================================================
$search = isset($_GET['q'])      ? trim($_GET['q'])      : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

$where  = array("p.supervisor_id = ?");
$params = array($teacherId);
$types  = 'i';

if ($status !== 'all' && $status !== '') {
    $where[]  = "g.status = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($search !== '') {
    $where[]  = "(g.name LIKE ? OR u.username LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types   .= 'ss';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);


// =========================================================
// 3. Teams 列表（只看我负责项目下的组）
// =========================================================
$teams = array();

$sql = "
    SELECT
        g.id,
        g.name,
        g.status,
        g.formed_at,
        g.max_members,
        p.name AS project_name,
        u.username AS leader_name,
        (SELECT COUNT(*) FROM `group_members` gm WHERE gm.group_id = g.id) AS member_count,
        (SELECT COUNT(*) FROM `tasks` t
            WHERE t.group_id = g.id
              AND t.status <> 'Completed'
              AND t.due_date < CURDATE()) AS overdue_count,
        (SELECT COUNT(*) FROM `tasks` t2
            WHERE t2.group_id = g.id
              AND t2.status = 'Completed') AS completed_tasks,
        (SELECT COUNT(*) FROM `tasks` t3
            WHERE t3.group_id = g.id) AS total_tasks,
        (SELECT MAX(a.created_at) FROM `activities` a WHERE a.group_id = g.id) AS last_activity
    FROM `groups` g
    INNER JOIN `projects` p ON p.id = g.project_id
    LEFT JOIN `students` s ON s.id = g.leader_id
    LEFT JOIN `users`    u ON u.id = s.user_id
    $whereSql
    ORDER BY g.id
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $teams[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<main class="main">
    <div class="content">

        <h1>Teams</h1>
        <p class="subtitle">Project teams under your supervision · 2026/27</p>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Teams</div>
                <div class="value"><?php echo $totalTeams; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Formed Teams</div>
                <div class="value"><?php echo $formedTeams; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Recruiting</div>
                <div class="value"><?php echo $recruiting; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Past Due Tasks</div>
                <div class="value"><?php echo $pastDue; ?></div>
            </div>
        </div>

        <!-- Filter -->
        <form method="get" action="teacher_teams.php" class="toolbar">

            <div class="field field-search">
                <label for="teamSearch">Search team</label>
                <input
                        type="search"
                        id="teamSearch"
                        name="q"
                        placeholder="e.g. Team 01 or CHAN Ho Yin"
                        value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <div class="field field-status">
                <label for="teamStatus">Filter by status</label>
                <select id="teamStatus" name="status">
                    <option value="all"        <?php echo $status==='all'?'selected':''; ?>>All Status</option>
                    <option value="Recruiting" <?php echo $status==='Recruiting'?'selected':''; ?>>Recruiting</option>
                    <option value="Voting"     <?php echo $status==='Voting'?'selected':''; ?>>Voting</option>
                    <option value="Executing"  <?php echo $status==='Executing'?'selected':''; ?>>Executing</option>
                    <option value="Completed"  <?php echo $status==='Completed'?'selected':''; ?>>Completed</option>
                </select>
            </div>

            <div class="field">
                <label for="submitFilter">&nbsp;</label>
                <button type="submit" id="submitFilter" class="btn-primary">Filter</button>
            </div>

            <div class="field">
                <label for="resetFilter">&nbsp;</label>
                <a href="teacher_teams.php" id="resetFilter" class="btn-secondary">Reset</a>
            </div>

        </form>

        <!-- Team list -->
        <div class="card">
            <table>
                <tr>
                    <th>Team Name</th>
                    <th>Project</th>
                    <th>Leader</th>
                    <th>Members</th>
                    <th>Progress</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                    <th>Overdue</th>
                    <th></th>
                </tr>

                <?php if (count($teams) === 0): ?>
                    <tr>
                        <td colspan="9" class="muted" style="text-align: center; padding: 30px;">
                            No teams match your filter.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($teams as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                        <td><?php echo htmlspecialchars($t['project_name'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($t['leader_name'] ?: '—'); ?></td>
                        <td>
                            <?php echo (int)$t['member_count']; ?> / <?php echo (int)$t['max_members']; ?>
                        </td>
                        <td>
                            <?php
                            $done  = (int)$t['completed_tasks'];
                            $total = (int)$t['total_tasks'];
                            echo $total > 0 ? ($done . ' / ' . $total) : '—';
                            ?>
                        </td>
                        <td class="muted"><?php echo timeAgo($t['last_activity']); ?></td>
                        <td><span class="pill"><?php echo htmlspecialchars($t['status']); ?></span></td>
                        <td>
                            <?php if ((int)$t['overdue_count'] > 0): ?>
                                <span style="color:#d32f2f; font-weight:600;"><?php echo (int)$t['overdue_count']; ?></span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="team_detail.php?id=<?php echo (int)$t['id']; ?>" class="link">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</main>

</body>
</html>