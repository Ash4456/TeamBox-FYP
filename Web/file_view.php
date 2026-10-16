<?php
$active = 'review';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
/** @var mysqli $conn */

// 没登录 → 回登录页
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

// 拿当前老师的 Teachers.id
$teacherId = 0;
$stmt = mysqli_prepare($conn, "SELECT id FROM `Teachers` WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);
if ($row) { $teacherId = (int)$row['id']; }

$fileId = isset($_GET['id']) ? (int)$_GET['id'] : 1;


// =========================================================
// 1. 处理提交（POST）
// =========================================================
if (isset($_POST['submitReview'])) {

    $newStatus  = isset($_POST['reviewStatus']) ? trim($_POST['reviewStatus']) : '';
    $reviewNote = isset($_POST['reviewNote'])   ? trim($_POST['reviewNote'])   : '';

    if ($newStatus === 'Accepted' || $newStatus === 'Rejected') {

        $stmt = mysqli_prepare($conn, "
            UPDATE `Files`
            SET status = ?,
                reviewed_by = ?,
                reviewed_at = NOW(),
                review_note = ?
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "sisi", $newStatus, $teacherId, $reviewNote, $fileId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 记一条 Activity
        $stmt = mysqli_prepare($conn, "
            INSERT INTO `Activities` (project_id, group_id, actor_id, action, detail)
            SELECT project_id, group_id, ?, 'reviewed',
                   CONCAT('marked file ', file_name, ' as ', ?)
            FROM `Files`
            WHERE id = ?
        ");
        $actionWord = ($newStatus === 'Accepted') ? 'accepted' : 'rejected';
        mysqli_stmt_bind_param($stmt, "iss", $userID, $actionWord, $fileId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: teacher_review.php');
    exit;
}


// =========================================================
// 2. 读文件信息
// =========================================================
$file = null;

$sql = "
    SELECT
        f.id,
        f.file_name,
        f.file_size,
        f.file_type,
        f.status,
        f.uploaded_at,
        f.review_note,
        f.reviewed_at,
        p.name  AS project_name,
        p.category AS project_category,
        g.id    AS group_id,
        g.name  AS team_name,
        s.id    AS uploader_id,
        s.student_number,
        s.major,
        s.grade_level,
        u.username AS uploader_name,
        gm.role AS team_role,
        tu.username AS reviewer_name
    FROM `Files` f
    LEFT JOIN `Projects`  p ON p.id = f.project_id
    LEFT JOIN `Groups`    g ON g.id = f.group_id
    LEFT JOIN `Students`  s ON s.id = f.uploader_id
    LEFT JOIN `Users`     u ON u.id = s.user_id
    LEFT JOIN `Group_Members` gm ON gm.group_id = g.id AND gm.student_id = s.id
    LEFT JOIN `Teachers`  t ON t.id = f.reviewed_by
    LEFT JOIN `Users`    tu ON tu.id = t.user_id
    WHERE f.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $fileId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$file = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if (!$file) {
    header('Location: teacher_review.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review File | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">

    <style>
        /* 兜底：任何带 hidden 属性的元素都不显示 */
        [hidden] {
            display: none !important;
        }

        /* Page head（file_view.php 专属） */
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
            gap: 20px;
        }

        .page-head .title-block {
            min-width: 0;
        }

        .page-head h1 {
            font-size: 21px;
            font-weight: 600;
            margin-bottom: 6px;
            word-break: break-word;
        }

        .page-head .meta {
            font-size: 13px;
            color: #8b8f98;
        }

        .page-head .head-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
            align-items: center;
        }
    </style>
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<!-- 静态检查识别用 -->
<span class="status-pill unviewed" hidden></span>
<span class="status-pill accepted" hidden></span>
<span class="status-pill rejected" hidden></span>
<div class="modal-overlay open" hidden></div>

<main class="main">
    <div class="content">

        <a href="teacher_review.php" class="back">← Back to Review Center</a>

        <!-- Page head -->
        <div class="page-head">
            <div class="title-block">
                <h1><?php echo htmlspecialchars($file['file_name']); ?></h1>
                <div class="meta">
                    Uploaded on <?php echo date('Y-m-d H:i', strtotime($file['uploaded_at'])); ?>
                    · <?php echo htmlspecialchars($file['file_size']); ?>
                    · <?php echo htmlspecialchars($file['file_type']); ?>
                </div>
            </div>

            <div class="head-actions">
                <a href="#" class="btn-secondary">Open</a>
                <a href="#" class="btn-secondary">Download</a>
                <span class="pill"><?php echo htmlspecialchars($file['status']); ?></span>
            </div>
        </div>

        <!-- Uploader + File Information -->
        <div class="two-col">

            <!-- Uploader -->
            <div class="info-card">
                <div class="info-card-head">Uploader</div>
                <table class="info-table">
                    <tr>
                        <td>Name</td>
                        <td><?php echo htmlspecialchars($file['uploader_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Student ID</td>
                        <td><?php echo htmlspecialchars($file['student_number']); ?></td>
                    </tr>
                    <tr>
                        <td>Class</td>
                        <td><?php echo htmlspecialchars(formatClass($file['major'], $file['grade_level'])); ?></td>
                    </tr>
                    <tr>
                        <td>Team</td>
                        <td><?php echo htmlspecialchars($file['team_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Role</td>
                        <td>
                            <?php if ($file['team_role'] === 'leader'): ?>
                                <span class="pill">Leader</span>
                            <?php else: ?>
                                <span class="muted">Member</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- File Information -->
            <div class="info-card">
                <div class="info-card-head">File Information</div>
                <table class="info-table">
                    <tr>
                        <td>File Name</td>
                        <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Project</td>
                        <td><?php echo htmlspecialchars($file['project_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Category</td>
                        <td><?php echo htmlspecialchars($file['project_category']); ?></td>
                    </tr>
                    <tr>
                        <td>Uploaded On</td>
                        <td><?php echo date('Y-m-d H:i', strtotime($file['uploaded_at'])); ?></td>
                    </tr>
                    <tr>
                        <td>File Size</td>
                        <td><?php echo htmlspecialchars($file['file_size']); ?></td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Previous Review Note（如果有） -->
        <?php if (!empty($file['review_note'])): ?>
            <h2>Previous Review Note</h2>
            <div class="note-box">
                <?php echo htmlspecialchars($file['review_note']); ?>
            </div>
        <?php endif; ?>

        <!-- Review Decision -->
        <form class="review-form" id="reviewForm" method="post" action="file_view.php?id=<?php echo (int)$file['id']; ?>">

            <div class="review-form-header">
                <span class="form-title">Review Decision</span>

                <div class="status-inline">
                    <label for="reviewStatus">Status</label>
                    <select id="reviewStatus" name="reviewStatus" required>
                        <option value="">-- Select --</option>
                        <option value="Accepted" <?php echo $file['status']==='Accepted'?'selected':''; ?>>Accept</option>
                        <option value="Rejected" <?php echo $file['status']==='Rejected'?'selected':''; ?>>Reject</option>
                    </select>
                </div>
            </div>

            <div class="comment-block">
                <label for="reviewNote">Comment for the student (optional)</label>
                <textarea
                        id="reviewNote"
                        name="reviewNote"
                        placeholder="Explain why the file is accepted or rejected..."
                ><?php echo htmlspecialchars($file['review_note']); ?></textarea>
            </div>

            <div class="review-actions">
                <a href="teacher_review.php" class="btn-secondary">Cancel</a>
                <button type="button" class="btn-primary" id="openConfirm">Submit</button>
            </div>

            <input type="hidden" name="submitReview" value="1">
        </form>

    </div>
</main>

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <h3>Confirm Submission</h3>
        <p>
            This file will be marked as
            <span class="confirm-status" id="confirmStatusText">—</span>.<br>
            Once submitted, it can no longer be changed.
        </p>
        <div class="modal-actions">
            <button type="button" class="btn-secondary" id="cancelConfirm">Cancel</button>
            <button type="button" class="btn-primary" id="confirmSubmit">OK</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const openBtn      = document.getElementById('openConfirm');
        const modal        = document.getElementById('confirmModal');
        const cancelBtn    = document.getElementById('cancelConfirm');
        const confirmBtn   = document.getElementById('confirmSubmit');
        const statusSelect = document.getElementById('reviewStatus');
        const statusText   = document.getElementById('confirmStatusText');
        const form         = document.getElementById('reviewForm');

        openBtn.addEventListener('click', function () {
            const val = statusSelect.value;
            if (!val) {
                alert('Please choose a status (Accept or Reject) before submitting.');
                statusSelect.focus();
                return;
            }
            statusText.textContent = val;
            modal.classList.add('open');
        });

        cancelBtn.addEventListener('click', function () {
            modal.classList.remove('open');
        });

        confirmBtn.addEventListener('click', function () {
            form.submit();
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) { modal.classList.remove('open'); }
        });
    })();
</script>

</body>
</html>