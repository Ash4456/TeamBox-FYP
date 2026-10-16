<?php
$active = 'projects';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
/** @var mysqli $conn */


// =========================================================
// 1. Active Projects（未归档的）
// =========================================================
$activeProjects = array();

$sql = "
    SELECT
        p.id,
        p.name,
        p.category,
        p.status,
        p.progress,
        p.period_end,
        p.period_start,
        u.username AS supervisor_name,
        (SELECT COUNT(*) FROM `Groups` g
            WHERE g.project_id = p.id) AS team_count,
        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
            FROM `Groups` g
            WHERE g.project_id = p.id) AS team_names
    FROM `Projects` p
    LEFT JOIN `Teachers` t ON t.id = p.supervisor_id
    LEFT JOIN `Users`    u ON u.id = t.user_id
    WHERE p.status <> 'Archived'
    ORDER BY p.id
";

$rs = mysqli_query($conn, $sql);
if ($rs) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $activeProjects[] = $row;
    }
    mysqli_free_result($rs);
}


// =========================================================
// 2. Archived Projects
// =========================================================
$archivedProjects = array();

$sql = "
    SELECT
        p.id,
        p.name,
        p.category,
        p.progress,
        p.period_end,
        u.username AS supervisor_name,
        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
            FROM `Groups` g
            WHERE g.project_id = p.id) AS team_names
    FROM `Projects` p
    LEFT JOIN `Teachers` t ON t.id = p.supervisor_id
    LEFT JOIN `Users`    u ON u.id = t.user_id
    WHERE p.status = 'Archived'
    ORDER BY p.id DESC
";

$rs = mysqli_query($conn, $sql);
if ($rs) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $archivedProjects[] = $row;
    }
    mysqli_free_result($rs);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<main class="main">
    <div class="content">

        <h1>Projects</h1>
        <p class="subtitle">All final year projects under your supervision · 2026/27</p>

        <!-- Active Projects -->
        <h2>Active Projects</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Project Name</th>
                    <th>Category</th>
                    <th>Team</th>
                    <th>Progress</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th></th>
                </tr>

                <?php if (count($activeProjects) === 0): ?>
                    <tr>
                        <td colspan="7" class="muted" style="text-align: center; padding: 30px;">
                            No active projects.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($activeProjects as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td>
                            <?php if (!empty($p['team_names'])): ?>
                                <?php echo htmlspecialchars($p['team_names']); ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$p['progress']; ?>%</td>
                        <td class="muted">
                            <?php echo $p['period_end'] ? date('d M Y', strtotime($p['period_end'])) : '—'; ?>
                        </td>
                        <td><span class="pill"><?php echo htmlspecialchars($p['status']); ?></span></td>
                        <td>
                            <a href="project_detail.php?id=<?php echo (int)$p['id']; ?>" class="link">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Archived Projects -->
        <h2>Archived Projects</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Project Name</th>
                    <th>Team</th>
                    <th>Progress</th>
                    <th>Completed</th>
                    <th></th>
                </tr>

                <?php if (count($archivedProjects) === 0): ?>
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center; padding: 30px;">
                            No archived projects.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($archivedProjects as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td>
                            <?php if (!empty($p['team_names'])): ?>
                                <?php echo htmlspecialchars($p['team_names']); ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$p['progress']; ?>%</td>
                        <td class="muted">
                            <?php echo $p['period_end'] ? date('M Y', strtotime($p['period_end'])) : '—'; ?>
                        </td>
                        <td>
                            <a href="project_detail.php?id=<?php echo (int)$p['id']; ?>" class="link">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</main>

</body>
</html>