<?php
$active = 'students';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
/** @var mysqli $conn */

// 当前老师的 Teachers.id
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
// 汇总
// =========================================================

// Total Students
$totalStudents = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT ce.student_id) AS c
    FROM `course_enrollments` ce
    INNER JOIN `projects` p ON p.course_id = ce.course_id
    WHERE p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$totalStudents = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Assigned
$assignedStudents = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT ce.student_id) AS c
    FROM `course_enrollments` ce
    INNER JOIN `projects` p ON p.course_id = ce.course_id
    INNER JOIN `group_members` gm ON gm.student_id = ce.student_id
    WHERE p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$assignedStudents = (int)$rc['c'];
mysqli_stmt_close($stmt);

$unassignedStudents = $totalStudents - $assignedStudents;

// At Risk
$atRisk = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT t.assigned_to) AS c
    FROM `tasks` t
    INNER JOIN `course_enrollments` ce ON ce.student_id = t.assigned_to
    INNER JOIN `projects` p ON p.course_id = ce.course_id
    WHERE p.supervisor_id = ?
      AND t.status <> 'Completed'
      AND t.due_date < CURDATE()
      AND t.assigned_to IS NOT NULL
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$atRisk = (int)$rc['c'];
mysqli_stmt_close($stmt);


// =========================================================
// Assigned Students
// =========================================================
$assigned = array();

$sql = "
    SELECT DISTINCT
        s.id                AS student_id,
        s.student_number,
        s.major,
        s.grade_level,
        u.username          AS name,
        g.name              AS team_name,
        gm.role             AS team_role,
        (SELECT COUNT(*) FROM `tasks` t
            WHERE t.assigned_to = s.id
              AND t.status = 'Completed') AS done_tasks,
        (SELECT COUNT(*) FROM `tasks` t2
            WHERE t2.assigned_to = s.id) AS total_tasks
    FROM `course_enrollments` ce
    INNER JOIN `projects` p ON p.course_id = ce.course_id
    INNER JOIN `students` s ON s.id = ce.student_id
    INNER JOIN `users`    u ON u.id = s.user_id
    INNER JOIN `group_members` gm ON gm.student_id = s.id
    INNER JOIN `groups` g ON g.id = gm.group_id
    WHERE p.supervisor_id = ?
    ORDER BY g.id, s.id
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) { $assigned[] = $row; }
mysqli_stmt_close($stmt);


// =========================================================
// Unassigned Students
// =========================================================
$unassigned = array();

$sql = "
    SELECT DISTINCT
        s.id                AS student_id,
        s.student_number,
        s.major,
        s.grade_level,
        u.username          AS name
    FROM `course_enrollments` ce
    INNER JOIN `projects` p ON p.course_id = ce.course_id
    INNER JOIN `students` s ON s.id = ce.student_id
    INNER JOIN `users`    u ON u.id = s.user_id
    WHERE p.supervisor_id = ?
      AND s.id NOT IN (SELECT DISTINCT student_id FROM `group_members`)
    ORDER BY s.id
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) { $unassigned[] = $row; }
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<main class="main">
    <div class="content">

        <h1>Students</h1>
        <p class="subtitle">Students under your courses · <?php echo $totalStudents; ?> students</p>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Students</div>
                <div class="value"><?php echo $totalStudents; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Assigned to Teams</div>
                <div class="value"><?php echo $assignedStudents; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Unassigned</div>
                <div class="value"><?php echo $unassignedStudents; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">At Risk</div>
                <div class="value"><?php echo $atRisk; ?></div>
            </div>
        </div>

        <!-- Assigned Students -->
        <h2>Assigned Students</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Student Number</th>
                    <th>Class</th>
                    <th>Team</th>
                    <th>Role</th>
                    <th>Tasks</th>
                    <th></th>
                </tr>

                <?php if (count($assigned) === 0): ?>
                    <tr>
                        <td colspan="7" class="muted" style="text-align: center; padding: 30px;">
                            No assigned students yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($assigned as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo htmlspecialchars($s['student_number']); ?></td>
                        <td><?php echo htmlspecialchars(formatClass($s['major'], $s['grade_level'])); ?></td>
                        <td><?php echo htmlspecialchars($s['team_name']); ?></td>
                        <td>
                            <?php if ($s['team_role'] === 'leader'): ?>
                                <span class="pill">Leader</span>
                            <?php else: ?>
                                <span class="muted">Member</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$s['done_tasks']; ?> / <?php echo (int)$s['total_tasks']; ?></td>
                        <td><a href="#" class="link">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Unassigned Students -->
        <h2>Unassigned Students</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Student Number</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th></th>
                </tr>

                <?php if (count($unassigned) === 0): ?>
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center; padding: 30px;">
                            All students are assigned to teams. 🎉
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($unassigned as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo htmlspecialchars($s['student_number']); ?></td>
                        <td><?php echo htmlspecialchars(formatClass($s['major'], $s['grade_level'])); ?></td>
                        <td><span class="pill">Unassigned</span></td>
                        <td><button class="link">Assign</button></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</main>

</body>
</html>