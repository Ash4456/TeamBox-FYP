<?php
$active = 'review';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
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
// 0. 处理 Accept / Reject 提交
// =========================================================
if (isset($_POST['action']) && isset($_POST['requestId'])) {

    $action    = $_POST['action'];
    $requestId = (int) $_POST['requestId'];
    $note      = isset($_POST['reviewNote']) ? trim($_POST['reviewNote']) : '';

    if ($action === 'accept') {
        $stmt = mysqli_prepare($conn, "
            UPDATE `group_requests`
            SET status = 'Accepted',
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $teacherId, $requestId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } elseif ($action === 'reject' && $note !== '') {
        $stmt = mysqli_prepare($conn, "
            UPDATE `group_requests`
            SET status = 'Rejected',
                review_note = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "sii", $note, $teacherId, $requestId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: teacher_review.php#requests');
    exit;
}


// =========================================================
// 1. 汇总数字（只看我负责项目下的文件 / 申请）
// =========================================================

// Pending Files
$pendingFiles = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `files` f
    INNER JOIN `projects` p ON p.id = f.project_id
    WHERE f.status = 'Unviewed' AND p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$pendingFiles = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Approved Files
$approvedFiles = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `files` f
    INNER JOIN `projects` p ON p.id = f.project_id
    WHERE f.status = 'Accepted' AND p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$approvedFiles = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Rejected Files
$rejectedFiles = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `files` f
    INNER JOIN `projects` p ON p.id = f.project_id
    WHERE f.status = 'Rejected' AND p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$rejectedFiles = (int)$rc['c'];
mysqli_stmt_close($stmt);

// Group Requests（我负责项目下的 Pending）
$groupRequestCount = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `group_requests` r
    INNER JOIN `projects` p ON p.id = r.project_id
    WHERE r.status = 'Pending' AND p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$groupRequestCount = (int)$rc['c'];
mysqli_stmt_close($stmt);


// =========================================================
// 2. Tab 1 — All Files（只看我的项目 + 筛选）
// =========================================================
$filterFrom   = isset($_GET['from'])   ? trim($_GET['from'])   : '';
$filterTo     = isset($_GET['to'])     ? trim($_GET['to'])     : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$filterSearch = isset($_GET['q'])      ? trim($_GET['q'])      : '';

$where  = array("p.supervisor_id = ?");
$params = array($teacherId);
$types  = 'i';

if ($filterStatus !== 'all' && $filterStatus !== '') {
    $where[]  = "f.status = ?";
    $params[] = $filterStatus;
    $types   .= 's';
}

if ($filterFrom !== '') {
    $where[]  = "DATE(f.uploaded_at) >= ?";
    $params[] = $filterFrom;
    $types   .= 's';
}

if ($filterTo !== '') {
    $where[]  = "DATE(f.uploaded_at) <= ?";
    $params[] = $filterTo;
    $types   .= 's';
}

if ($filterSearch !== '') {
    $where[]  = "(f.file_name LIKE ? OR g.name LIKE ? OR u.username LIKE ?)";
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
    $types   .= 'sss';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$allFiles = array();

$sql = "
    SELECT
        f.id,
        f.file_name,
        f.file_size,
        f.file_type,
        f.status,
        f.uploaded_at,
        g.id     AS group_id,
        g.name   AS team_name,
        p.name   AS project_name,
        s.id     AS uploader_id,
        s.student_number,
        u.username AS uploader_name
    FROM `files` f
    INNER JOIN `projects` p ON p.id = f.project_id
    LEFT JOIN `groups`   g ON g.id = f.group_id
    LEFT JOIN `students` s ON s.id = f.uploader_id
    LEFT JOIN `users`    u ON u.id = s.user_id
    $whereSql
    ORDER BY f.uploaded_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) { $allFiles[] = $row; }
mysqli_stmt_close($stmt);

// Total Files（我的项目下的）
$totalFiles = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `files` f
    INNER JOIN `projects` p ON p.id = f.project_id
    WHERE p.supervisor_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$totalFiles = (int)$rc['c'];
mysqli_stmt_close($stmt);

$filteredCount = count($allFiles);

$rangeText = 'All dates';
if ($filterFrom !== '' || $filterTo !== '') {
    $rangeText = ($filterFrom !== '' ? $filterFrom : '…') . ' – ' . ($filterTo !== '' ? $filterTo : '…');
}


// =========================================================
// 3. Tab 2 — Unviewed Files
// =========================================================
$unviewedFiles = array();

$sql = "
    SELECT
        f.id,
        f.file_name,
        f.file_size,
        f.file_type,
        f.uploaded_at,
        g.name     AS team_name,
        u.username AS uploader_name
    FROM `files` f
    INNER JOIN `projects` p ON p.id = f.project_id
    LEFT JOIN `groups`   g ON g.id = f.group_id
    LEFT JOIN `students` s ON s.id = f.uploader_id
    LEFT JOIN `users`    u ON u.id = s.user_id
    WHERE f.status = 'Unviewed'
      AND p.supervisor_id = ?
    ORDER BY f.uploaded_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) { $unviewedFiles[] = $row; }
mysqli_stmt_close($stmt);


// =========================================================
// 4. Tab 3 — Group Requests（只看我的项目）
// =========================================================
$groupRequests = array();

$sql = "
    SELECT
        r.id,
        r.group_name,
        r.status,
        r.submitted_at,
        p.name AS project_name,
        u.username AS submitted_by_name
    FROM `group_requests` r
    INNER JOIN `projects` p ON p.id = r.project_id
    LEFT JOIN `students` s ON s.id = r.submitted_by
    LEFT JOIN `users`    u ON u.id = s.user_id
    WHERE r.status = 'Pending'
      AND p.supervisor_id = ?
    ORDER BY r.submitted_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($rs)) {

    $members = array();
    $stmtM = mysqli_prepare($conn, "
        SELECT
            s.id AS student_id,
            s.student_number,
            u.username AS name,
            rm.is_leader
        FROM `request_members` rm
        INNER JOIN `students` s ON s.id = rm.student_id
        INNER JOIN `users`    u ON u.id = s.user_id
        WHERE rm.request_id = ?
        ORDER BY rm.is_leader DESC, s.id
    ");
    mysqli_stmt_bind_param($stmtM, "i", $row['id']);
    mysqli_stmt_execute($stmtM);
    $rsM = mysqli_stmt_get_result($stmtM);
    while ($m = mysqli_fetch_assoc($rsM)) {
        $members[] = $m;
    }
    mysqli_stmt_close($stmtM);

    $row['members'] = $members;
    $groupRequests[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Center | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">

    <style>
        /* ===== Tabs ===== */
        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e6e8eb;
        }

        .tab {
            padding: 10px 18px;
            font-size: 14px;
            color: #6b6f78;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            display: inline-block;
        }

        .tab:hover { color: #2c2f36; }

        .tab.active {
            color: #175196;
            font-weight: 600;
            border-bottom-color: #175196;
        }

        .tab .tab-count {
            display: inline-block;
            margin-left: 6px;
            background: #f2f3f5;
            color: #6b6f78;
            font-size: 11px;
            font-weight: 600;
            padding: 1px 7px;
            border-radius: 10px;
        }

        .tab.active .tab-count {
            background: #175196;
            color: #fff;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ===== Filter bar ===== */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 14px;
            padding: 18px 20px;
            margin-bottom: 16px;
            background: #fff;
            border: 1px solid #e6e8eb;
            border-radius: 10px;
        }

        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-field label {
            font-size: 12px;
            color: #6b6f78;
            font-weight: 500;
            letter-spacing: 0.03em;
        }

        .filter-field input,
        .filter-field select {
            height: 36px;
            padding: 0 10px;
            background: #fff;
            border: 1px solid #e6e8eb;
            border-radius: 6px;
            font-size: 13px;
            color: #2c2f36;
            font-family: inherit;
            outline: none;
        }

        .filter-field input:focus,
        .filter-field select:focus { border-color: #175196; }

        .filter-field.search input { width: 240px; }
        .filter-field.date input   { width: 160px; }
        .filter-field.status select{ width: 160px; }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .btn-secondary {
            background: #fff;
            color: #175196;
            border: 1px solid #175196;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            display: inline-block;
            height: 36px;
            line-height: 18px;
        }

        .btn-secondary:hover { background: #f2f7fd; }

        /* ===== Result info ===== */
        .result-info {
            font-size: 13px;
            color: #6b6f78;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .result-info .range-text { color: #8b8f98; }

        /* ===== File table ===== */
        .file-table { table-layout: auto; }

        .file-table th,
        .file-table td {
            white-space: normal;
            word-break: break-word;
        }

        .file-name-cell { max-width: 320px; }

        .file-name-link {
            color: #175196;
            font-weight: 500;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
        }

        .file-name-link:hover { text-decoration: underline; }

        .row-actions {
            display: flex;
            gap: 12px;
            white-space: nowrap;
        }

        /* ===== Status pill ===== */
        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pill.unviewed {
            background: #f7f8fa;
            color: #6b6f78;
            border: 1px solid #e6e8eb;
        }

        .status-pill.accepted {
            background: #eaf7ee;
            color: #176b2c;
            border: 1px solid #8bc79a;
        }

        .status-pill.rejected {
            background: #fdeeee;
            color: #a21b1b;
            border: 1px solid #e6a0a0;
        }

        /* ===== Group Request card ===== */
        .request-card {
            border: 1px solid #e6e8eb;
            border-left: 4px solid #175196;
            border-radius: 10px;
            background: #fff;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .request-head {
            padding: 18px 22px;
            background: #f7f8fa;
            border-bottom: 1px solid #e6e8eb;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .request-head h3 {
            font-size: 16px;
            font-weight: 600;
            color: #2c2f36;
            margin-bottom: 4px;
        }

        .request-head .request-meta {
            font-size: 13px;
            color: #8b8f98;
        }

        .request-body { padding: 6px 0; }

        .request-body table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .request-body table th {
            text-align: left;
            font-size: 12px;
            font-weight: 500;
            color: #6b6f78;
            background: #fbfbfc;
            padding: 10px 22px;
            border-bottom: 1px solid #f2f3f5;
        }

        .request-body table td {
            padding: 12px 22px;
            border-bottom: 1px solid #f2f3f5;
            color: #2c2f36;
            white-space: normal;
        }

        .request-body table tr:last-child td { border-bottom: none; }

        .request-foot {
            padding: 16px 22px;
            background: #fafbfc;
            border-top: 1px solid #e6e8eb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 900px) {
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-field.search input,
            .filter-field.date input,
            .filter-field.status select { width: 100%; }
            .filter-actions { margin-left: 0; }
            .filter-actions .btn-primary,
            .filter-actions .btn-secondary { flex: 1; text-align: center; }
        }

        @media (max-width: 700px) {
            .request-head { flex-direction: column; gap: 10px; }
            .request-foot { flex-direction: column; }
            .row-actions { flex-direction: column; gap: 4px; }
        }
    </style>
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<span class="status-pill unviewed" hidden></span>
<span class="status-pill accepted" hidden></span>
<span class="status-pill rejected" hidden></span>
<div class="modal-overlay open" hidden></div>

<main class="main">
    <div class="content">

        <h1>Review Center</h1>
        <p class="subtitle">Review student files and approve group formation requests</p>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Pending Files</div>
                <div class="value"><?php echo $pendingFiles; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Approved Files</div>
                <div class="value"><?php echo $approvedFiles; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Rejected Files</div>
                <div class="value"><?php echo $rejectedFiles; ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Group Requests</div>
                <div class="value"><?php echo $groupRequestCount; ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="#" class="tab active" data-tab="files">
                All Files
                <span class="tab-count"><?php echo $totalFiles; ?></span>
            </a>
            <a href="#" class="tab" data-tab="unviewed">
                Unviewed Files
                <span class="tab-count"><?php echo count($unviewedFiles); ?></span>
            </a>
            <a href="#" class="tab" data-tab="requests">
                Group Requests
                <span class="tab-count"><?php echo count($groupRequests); ?></span>
            </a>
        </div>

        <!-- =========================================================
             TAB 1 — ALL FILES
        ========================================================= -->
        <div class="tab-panel active" id="panel-files">

            <form method="get" action="teacher_review.php" class="filter-bar">

                <div class="filter-field search">
                    <label for="q">Search</label>
                    <input type="search" id="q" name="q"
                           placeholder="File, team or uploader"
                           value="<?php echo htmlspecialchars($filterSearch); ?>">
                </div>

                <div class="filter-field date">
                    <label for="from">From date</label>
                    <input type="date" id="from" name="from"
                           value="<?php echo htmlspecialchars($filterFrom); ?>">
                </div>

                <div class="filter-field date">
                    <label for="to">To date</label>
                    <input type="date" id="to" name="to"
                           value="<?php echo htmlspecialchars($filterTo); ?>">
                </div>

                <div class="filter-field status">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all"      <?php echo $filterStatus==='all'?'selected':''; ?>>All Status</option>
                        <option value="Unviewed" <?php echo $filterStatus==='Unviewed'?'selected':''; ?>>Unviewed</option>
                        <option value="Accepted" <?php echo $filterStatus==='Accepted'?'selected':''; ?>>Accepted</option>
                        <option value="Rejected" <?php echo $filterStatus==='Rejected'?'selected':''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="teacher_review.php" class="btn-secondary">Reset</a>
                </div>

            </form>

            <div class="result-info">
                <span>
                    Showing <strong><?php echo $filteredCount; ?></strong>
                    of <?php echo $totalFiles; ?> files
                </span>
                <span class="range-text">
                    Date range: <?php echo htmlspecialchars($rangeText); ?>
                </span>
            </div>

            <div class="card">
                <table class="file-table">
                    <tr>
                        <th>File</th>
                        <th>Team</th>
                        <th>Uploaded By</th>
                        <th>Uploaded On</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                    <?php if ($filteredCount === 0): ?>
                        <tr>
                            <td colspan="7" class="muted" style="text-align: center; padding: 30px;">
                                No files match your filter.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($allFiles as $f): ?>
                        <?php $statusLower = strtolower($f['status']); ?>
                        <tr>
                            <td class="file-name-cell">
                                <a href="file_view.php?id=<?php echo (int)$f['id']; ?>"
                                   class="file-name-link"
                                   title="<?php echo htmlspecialchars($f['file_name']); ?>">
                                    <?php echo htmlspecialchars($f['file_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($f['team_name']); ?></td>
                            <td><?php echo htmlspecialchars($f['uploader_name']); ?></td>
                            <td class="muted"><?php echo date('Y-m-d', strtotime($f['uploaded_at'])); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($f['file_size']); ?></td>
                            <td>
                                <span class="status-pill <?php echo $statusLower; ?>">
                                    <?php echo htmlspecialchars($f['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="file_view.php?id=<?php echo (int)$f['id']; ?>" class="link">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        </div>

        <!-- =========================================================
             TAB 2 — UNVIEWED FILES
        ========================================================= -->
        <div class="tab-panel" id="panel-unviewed">

            <h2>Unviewed Files</h2>
            <p class="muted" style="margin-bottom: 16px;">
                Files that students uploaded but you have not reviewed yet.
                Open one to accept or reject it.
            </p>

            <div class="card">
                <table>
                    <tr>
                        <th>File</th>
                        <th>Team</th>
                        <th>Uploaded By</th>
                        <th>Uploaded On</th>
                        <th>Size</th>
                        <th>Action</th>
                    </tr>

                    <?php if (count($unviewedFiles) === 0): ?>
                        <tr>
                            <td colspan="6" class="muted" style="text-align: center; padding: 30px;">
                                Nothing to review — all files have been viewed.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($unviewedFiles as $f): ?>
                        <tr>
                            <td class="file-name-cell">
                                <a href="file_view.php?id=<?php echo (int)$f['id']; ?>"
                                   class="file-name-link"
                                   title="<?php echo htmlspecialchars($f['file_name']); ?>">
                                    <?php echo htmlspecialchars($f['file_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($f['team_name']); ?></td>
                            <td><?php echo htmlspecialchars($f['uploader_name']); ?></td>
                            <td class="muted"><?php echo date('Y-m-d', strtotime($f['uploaded_at'])); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($f['file_size']); ?></td>
                            <td>
                                <a href="file_view.php?id=<?php echo (int)$f['id']; ?>" class="link">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        </div>

        <!-- =========================================================
             TAB 3 — GROUP REQUESTS
        ========================================================= -->
        <div class="tab-panel" id="panel-requests">

            <h2>Group Formation Requests</h2>
            <p class="muted" style="margin-bottom: 16px;">
                Each request lists the group members. The team is only formed
                after the teacher accepts the request.
            </p>

            <?php if (count($groupRequests) === 0): ?>
                <div class="card">
                    <div class="muted" style="text-align: center; padding: 30px;">
                        No pending group requests.
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($groupRequests as $req): ?>
                <div class="request-card">

                    <div class="request-head">
                        <div>
                            <h3>
                                <?php echo htmlspecialchars($req['group_name']); ?>
                                ·
                                <span class="muted" style="font-weight: normal;">
                                    <?php echo htmlspecialchars($req['project_name']); ?>
                                </span>
                            </h3>
                            <div class="request-meta">
                                Request #<?php echo (int)$req['id']; ?> ·
                                Submitted by <?php echo htmlspecialchars($req['submitted_by_name']); ?> ·
                                <?php echo date('Y-m-d H:i', strtotime($req['submitted_at'])); ?>
                            </div>
                        </div>
                        <span class="pill">Pending Review</span>
                    </div>

                    <div class="request-body">
                        <table>
                            <tr>
                                <th>Name</th>
                                <th>Student Number</th>
                                <th>Role</th>
                            </tr>
                            <?php foreach ($req['members'] as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['student_number']); ?></td>
                                    <td>
                                        <?php if ((int)$m['is_leader'] === 1): ?>
                                            <span class="pill">Leader</span>
                                        <?php else: ?>
                                            <span class="muted">Member</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <div class="request-foot">
                        <button type="button" class="btn-primary js-accept"
                                data-id="<?php echo (int)$req['id']; ?>"
                                data-name="<?php echo htmlspecialchars($req['group_name']); ?>">
                            Accept
                        </button>
                        <button type="button" class="btn-ghost js-reject"
                                data-id="<?php echo (int)$req['id']; ?>"
                                data-name="<?php echo htmlspecialchars($req['group_name']); ?>">
                            Reject
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    </div>
</main>

<!-- Accept Modal -->
<div class="modal-overlay" id="acceptModal">
    <div class="modal">
        <h3>Accept Group Request</h3>
        <p>
            Accept the group request
            <strong id="acceptGroupName">—</strong>?<br>
            The team will be officially formed.
        </p>
        <form method="post" action="teacher_review.php#requests" id="acceptForm">
            <input type="hidden" name="action" value="accept">
            <input type="hidden" name="requestId" id="acceptRequestId" value="">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="acceptCancel">Cancel</button>
                <button type="submit" class="btn-primary">Yes, Accept</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <h3>Reject Group Request</h3>
        <p>
            Reject the group request
            <strong id="rejectGroupName">—</strong>?<br>
            Please provide a reason below.
        </p>
        <form method="post" action="teacher_review.php#requests" id="rejectForm">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="requestId" id="rejectRequestId" value="">

            <div class="comment-block" style="margin-bottom:18px;">
                <label for="rejectNote">Reason for rejection <span style="color:#d32f2f;">*</span></label>
                <textarea id="rejectNote" name="reviewNote" required
                          placeholder="Explain why this group request is rejected..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="rejectCancel">Cancel</button>
                <button type="submit" class="btn-primary">Yes, Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(function (tab) {
        tab.addEventListener('click', function (e) {
            e.preventDefault();

            document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });

            tab.classList.add('active');
            const id = tab.getAttribute('data-tab');
            const panel = document.getElementById('panel-' + id);
            if (panel) { panel.classList.add('active'); }

            history.replaceState(null, '', '#' + id);
        });
    });

    (function () {
        const hash = window.location.hash.replace('#', '');
        if (!hash) return;
        const tabEl = document.querySelector('.tab[data-tab="' + hash + '"]');
        if (tabEl) { tabEl.click(); }
    })();

    // Accept / Reject Modal
    (function () {
        const acceptModal  = document.getElementById('acceptModal');
        const acceptName   = document.getElementById('acceptGroupName');
        const acceptReqId  = document.getElementById('acceptRequestId');
        const acceptCancel = document.getElementById('acceptCancel');

        document.querySelectorAll('.js-accept').forEach(function (btn) {
            btn.addEventListener('click', function () {
                acceptName.textContent = btn.getAttribute('data-name');
                acceptReqId.value      = btn.getAttribute('data-id');
                acceptModal.classList.add('open');
            });
        });
        acceptCancel.addEventListener('click', function () {
            acceptModal.classList.remove('open');
        });

        const rejectModal  = document.getElementById('rejectModal');
        const rejectName   = document.getElementById('rejectGroupName');
        const rejectReqId  = document.getElementById('rejectRequestId');
        const rejectCancel = document.getElementById('rejectCancel');
        const rejectNote   = document.getElementById('rejectNote');

        document.querySelectorAll('.js-reject').forEach(function (btn) {
            btn.addEventListener('click', function () {
                rejectName.textContent = btn.getAttribute('data-name');
                rejectReqId.value      = btn.getAttribute('data-id');
                rejectNote.value       = '';
                rejectModal.classList.add('open');
            });
        });
        rejectCancel.addEventListener('click', function () {
            rejectModal.classList.remove('open');
        });

        [acceptModal, rejectModal].forEach(function (m) {
            m.addEventListener('click', function (e) {
                if (e.target === m) { m.classList.remove('open'); }
            });
        });
    })();
</script>

</body>
</html>