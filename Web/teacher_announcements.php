<?php
$active = 'announcements';

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


// =========================================================
// 当前老师的 Teachers.id
// =========================================================
$teacherId = 0;
$stmtT = mysqli_prepare($conn, "SELECT id FROM `teachers` WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtT, "i", $userID);
mysqli_stmt_execute($stmtT);
$rsT = mysqli_stmt_get_result($stmtT);
$rowT = mysqli_fetch_assoc($rsT);
mysqli_stmt_close($stmtT);
if ($rowT) { $teacherId = (int)$rowT['id']; }


// =========================================================
// 1. 处理「Send Announcement」提交
// =========================================================
$sendMsg  = '';
$sendType = '';

if (isset($_POST['sendAnnouncement'])) {

    $title          = trim(isset($_POST['annTitle'])   ? $_POST['annTitle']   : '');
    $message        = trim(isset($_POST['annMessage']) ? $_POST['annMessage'] : '');
    $recipientsJson = isset($_POST['recipients']) ? $_POST['recipients'] : '[]';
    $recipients     = json_decode($recipientsJson, true);

    if ($title === '' || $message === '') {
        $sendMsg  = 'Title and message cannot be empty.';
        $sendType = 'error';

    } elseif (!is_array($recipients) || count($recipients) === 0) {
        $sendMsg  = 'Please select at least one recipient.';
        $sendType = 'error';

    } elseif ($teacherId === 0) {
        $sendMsg  = 'Your teacher profile was not found.';
        $sendType = 'error';

    } else {

        // 1. 插 announcements
        $stmt = mysqli_prepare($conn, "
            INSERT INTO `announcements` (teacher_id, title, message)
            VALUES (?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iss", $teacherId, $title, $message);
        $ok    = mysqli_stmt_execute($stmt);
        $annID = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        if ($ok && $annID > 0) {

            // 2. 循环插 announcement_targets
            $stmtT2 = mysqli_prepare($conn, "
                INSERT INTO `announcement_targets`
                    (announcement_id, target_type, target_id)
                VALUES (?, ?, ?)
            ");

            $saved = 0;

            foreach ($recipients as $r) {
                if (!isset($r['type'], $r['id'])) continue;

                $type = $r['type'];
                $id   = (int)$r['id'];

                if (!in_array($type, array('course', 'group', 'student'))) continue;
                if ($id <= 0) continue;

                mysqli_stmt_bind_param($stmtT2, "isi", $annID, $type, $id);
                if (mysqli_stmt_execute($stmtT2)) {
                    $saved++;
                }
            }
            mysqli_stmt_close($stmtT2);

            $sendMsg  = 'Announcement sent to ' . $saved . ' recipient(s).';
            $sendType = 'success';

        } else {
            $sendMsg  = 'Failed to send: ' . mysqli_error($conn);
            $sendType = 'error';
        }
    }
}


// =========================================================
// 2. 加载「我负责项目下的」可选收件人
// =========================================================

// 我负责项目的 course_ids
$myCourseIds = array();
$stmt = mysqli_prepare($conn, "
    SELECT DISTINCT course_id
    FROM `projects`
    WHERE supervisor_id = ?
      AND course_id IS NOT NULL
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $myCourseIds[] = (int)$row['course_id'];
}
mysqli_stmt_close($stmt);

// ① 学生
$allStudents = array();
if (count($myCourseIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($myCourseIds), '?'));
    $types = str_repeat('i', count($myCourseIds));

    $sql = "
        SELECT DISTINCT
            s.id            AS sid,
            s.student_number,
            u.username      AS name
        FROM `course_enrollments` ce
        INNER JOIN `students` s ON s.id = ce.student_id
        INNER JOIN `users`    u ON u.id = s.user_id
        WHERE ce.course_id IN ($placeholders)
        ORDER BY u.username
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$myCourseIds);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($rs)) {
        $allStudents[] = array(
                'type' => 'student',
                'id'   => (int)$row['sid'],
                'name' => $row['name'],
                'meta' => 'Student · ' . $row['student_number'],
        );
    }
    mysqli_stmt_close($stmt);
}

// ② 组
$allGroups = array();
$stmt = mysqli_prepare($conn, "
    SELECT g.id, g.name, p.name AS project_name
    FROM `groups` g
    INNER JOIN `projects` p ON p.id = g.project_id
    WHERE p.supervisor_id = ?
    ORDER BY g.name
");
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($rs)) {
    $allGroups[] = array(
            'type' => 'group',
            'id'   => (int)$row['id'],
            'name' => $row['name'],
            'meta' => 'Group · ' . $row['project_name'],
    );
}
mysqli_stmt_close($stmt);

// ③ 课程
$allCourses = array();
if (count($myCourseIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($myCourseIds), '?'));
    $types = str_repeat('i', count($myCourseIds));

    $sql = "
        SELECT id, code, name
        FROM `courses`
        WHERE id IN ($placeholders)
        ORDER BY code
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$myCourseIds);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($rs)) {
        $allCourses[] = array(
                'type' => 'course',
                'id'   => (int)$row['id'],
                'name' => $row['code'],
                'meta' => 'Course · ' . $row['name'],
        );
    }
    mysqli_stmt_close($stmt);
}


// =========================================================
// 3. 加载 Sent History
// =========================================================
$sentHistory = array();

$sql = "
    SELECT id, title, message, created_at
    FROM `announcements`
    WHERE teacher_id = ?
    ORDER BY created_at DESC
    LIMIT 20
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $teacherId);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($rs)) {

    $stmtT3 = mysqli_prepare($conn, "
        SELECT target_type, target_id
        FROM `announcement_targets`
        WHERE announcement_id = ?
    ");
    mysqli_stmt_bind_param($stmtT3, "i", $row['id']);
    mysqli_stmt_execute($stmtT3);
    $rsT3 = mysqli_stmt_get_result($stmtT3);

    $counts = array('course' => 0, 'group' => 0, 'student' => 0);
    while ($t = mysqli_fetch_assoc($rsT3)) {
        $counts[$t['target_type']]++;
    }
    mysqli_stmt_close($stmtT3);

    $parts = array();
    if ($counts['group']   > 0) $parts[] = $counts['group']   . ' Group'   . ($counts['group']   > 1 ? 's' : '');
    if ($counts['course']  > 0) $parts[] = $counts['course']  . ' Course'  . ($counts['course']  > 1 ? 's' : '');
    if ($counts['student'] > 0) $parts[] = $counts['student'] . ' Student' . ($counts['student'] > 1 ? 's' : '');

    $row['sent_to'] = count($parts) > 0 ? implode(' · ', $parts) : '—';

    $sentHistory[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">

    <style>
        /* ===== Recipient picker ===== */
        .recipient-picker {
            position: relative;
        }

        .recipient-box {
            min-height: 48px;
            padding: 6px 10px;
            background: #fff;
            border: 1px solid #e6e8eb;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            cursor: text;
            transition: border-color 0.15s;
        }

        .recipient-box:focus-within {
            border-color: #175196;
        }

        .recipient-box input {
            flex: 1;
            min-width: 160px;
            height: 30px;
            border: none;
            outline: none;
            font-size: 13px;
            font-family: inherit;
            background: transparent;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #eef4fb;
            color: #175196;
            border: 1px solid #cfe0f5;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }

        .chip .chip-x {
            cursor: pointer;
            font-weight: 700;
            color: #6b6f78;
            padding: 0 2px;
        }

        .chip .chip-x:hover { color: #d32f2f; }

        .chip.chip-group  { background: #eaf7ee; border-color: #b7e0c2; color: #176b2c; }
        .chip.chip-course { background: #fef7e2; border-color: #f1dda4; color: #765900; }

        .recipient-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            max-height: 260px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e6e8eb;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            z-index: 50;
            display: none;
        }

        .recipient-dropdown.open { display: block; }

        .recipient-item {
            padding: 10px 14px;
            font-size: 13px;
            color: #2c2f36;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f2f3f5;
        }

        .recipient-item:last-child { border-bottom: none; }

        .recipient-item:hover { background: #f7f9fc; }

        .recipient-item .item-name { font-weight: 500; }

        .recipient-item .item-meta { color: #8b8f98; font-size: 12px; }

        .recipient-item.empty {
            color: #a8acb4;
            cursor: default;
            justify-content: center;
        }

        .recipient-item.empty:hover { background: #fff; }

        /* 快捷项高亮 */
        .recipient-item.shortcut {
            background: #f0f7ff;
        }

        .recipient-item.shortcut:hover {
            background: #e4f0ff;
        }
    </style>
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<main class="main">
    <div class="content">

        <h1>Announcements</h1>
        <p class="subtitle">Send messages to your groups, courses or students</p>

        <!-- =========================================================
             New Announcement
             ========================================================= -->
        <h2>New Announcement</h2>
        <div class="card">
            <form method="post" action="teacher_announcements.php"
                  class="announcement-block stack"
                  id="annForm">

                <?php if ($sendMsg !== ''): ?>
                    <div style="padding:12px 16px; margin-bottom:16px; border-radius:8px;
                            background: <?php echo $sendType==='success' ? '#eaf7ee' : '#fdeeee'; ?>;
                            color: <?php echo $sendType==='success' ? '#176b2c' : '#a21b1b'; ?>;
                            border-left: 4px solid <?php echo $sendType==='success' ? '#2e9d4d' : '#d32f2f'; ?>;">
                        <?php echo htmlspecialchars($sendMsg); ?>
                    </div>
                <?php endif; ?>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="annTitle">Title</label>
                    <input
                            type="text"
                            id="annTitle"
                            name="annTitle"
                            placeholder="e.g. Progress Report Deadline"
                            required
                    >
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="annMessage">Message</label>
                    <textarea
                            id="annMessage"
                            name="annMessage"
                            placeholder="Write your announcement here..."
                            required
                    ></textarea>
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="recipientInput">Send To</label>

                    <div class="recipient-picker">
                        <div class="recipient-box" id="recipientBox">
                            <!-- chips go here -->
                            <input
                                    type="text"
                                    id="recipientInput"
                                    placeholder="Type a name, group or course... (try 'all')"
                                    autocomplete="off"
                            >
                        </div>
                        <div class="recipient-dropdown" id="recipientDropdown"></div>
                    </div>

                    <p class="muted" style="margin-top:6px; font-size:12px;">
                        Type to search students, groups or courses. Type <strong>all</strong> to select all your students.
                    </p>
                </div>

                <!-- hidden: JSON of recipients -->
                <input type="hidden" name="recipients" id="recipientsInput" value="[]">

                <button type="submit" name="sendAnnouncement" class="btn-primary">Send Announcement</button>

            </form>
        </div>

        <!-- =========================================================
             Sent History
             ========================================================= -->
        <h2>Sent History</h2>
        <div class="card">
            <table>
                <tr>
                    <th>Title</th>
                    <th>Sent To</th>
                    <th>Sent On</th>
                    <th></th>
                </tr>

                <?php if (count($sentHistory) === 0): ?>
                    <tr>
                        <td colspan="4" class="muted" style="text-align: center; padding: 30px;">
                            You haven't sent any announcements yet.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($sentHistory as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['title']); ?></td>
                        <td class="muted"><?php echo htmlspecialchars($a['sent_to']); ?></td>
                        <td class="muted"><?php echo timeAgo($a['created_at']); ?></td>
                        <td><a href="#" class="link">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</main>

<script>
    // ===== 所有可选收件人（PHP 注入） =====
    const ALL_RECIPIENTS = [
        <?php
        $parts = array();
        foreach (array_merge($allStudents, $allGroups, $allCourses) as $r) {
            $parts[] = json_encode($r);
        }
        echo implode(",\n        ", $parts);
        ?>
    ];

    // ===== "All My Students" 快捷项 =====
    const ALL_STUDENTS_ITEM = {
        type: 'all_students',
        id: 0,
        name: 'All My Students',
        meta: 'Shortcut · ' + ALL_RECIPIENTS.filter(r => r.type === 'student').length + ' students'
    };

    // ===== 状态 =====
    let selected = [];

    const box       = document.getElementById('recipientBox');
    const input     = document.getElementById('recipientInput');
    const dropdown  = document.getElementById('recipientDropdown');
    const hidden    = document.getElementById('recipientsInput');

    // ===== 渲染 chips =====
    function renderChips() {
        box.querySelectorAll('.chip').forEach(el => el.remove());

        selected.forEach((r, idx) => {
            const chip = document.createElement('span');
            chip.className = 'chip ' + (r.type === 'group'  ? 'chip-group' :
                r.type === 'course' ? 'chip-course' : '');

            const label = document.createElement('span');
            label.textContent = r.name;

            const x = document.createElement('span');
            x.className = 'chip-x';
            x.textContent = '×';
            x.addEventListener('click', () => {
                selected.splice(idx, 1);
                renderChips();
                updateHidden();
            });

            chip.appendChild(label);
            chip.appendChild(x);
            box.insertBefore(chip, input);
        });
    }

    // ===== 更新 hidden =====
    function updateHidden() {
        hidden.value = JSON.stringify(
            selected.map(r => ({ type: r.type, id: r.id }))
        );
    }

    // ===== 搜索 =====
    function searchRecipients(query) {
        query = query.trim().toLowerCase();
        if (query === '') return [];

        // 特殊：all
        if (query === 'all' || query === 'all students') {
            return [ALL_STUDENTS_ITEM];
        }

        const selectedKeys = selected.map(r => r.type + ':' + r.id);

        return ALL_RECIPIENTS.filter(r => {
            const key = r.type + ':' + r.id;
            if (selectedKeys.indexOf(key) !== -1) return false;
            const hay = (r.name + ' ' + r.meta).toLowerCase();
            return hay.indexOf(query) !== -1;
        }).slice(0, 8);
    }

    // ===== 渲染下拉 =====
    function renderDropdown(items) {
        dropdown.innerHTML = '';

        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'recipient-item empty';
            empty.textContent = 'No match';
            dropdown.appendChild(empty);
        } else {
            items.forEach(r => {
                const item = document.createElement('div');
                item.className = 'recipient-item' + (r.type === 'all_students' ? ' shortcut' : '');

                const name = document.createElement('span');
                name.className = 'item-name';
                name.textContent = r.name;

                const meta = document.createElement('span');
                meta.className = 'item-meta';
                meta.textContent = r.meta;

                item.appendChild(name);
                item.appendChild(meta);

                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    addRecipient(r);
                });

                dropdown.appendChild(item);
            });
        }

        dropdown.classList.add('open');
    }

    // ===== 添加收件人 =====
    function addRecipient(r) {
        // "All My Students" 快捷
        if (r.type === 'all_students') {
            const allStudents  = ALL_RECIPIENTS.filter(x => x.type === 'student');
            const selectedKeys = selected.map(x => x.type + ':' + x.id);

            allStudents.forEach(s => {
                const key = s.type + ':' + s.id;
                if (selectedKeys.indexOf(key) === -1) {
                    selected.push(s);
                }
            });

            input.value = '';
            renderChips();
            updateHidden();
            dropdown.classList.remove('open');
            input.focus();
            return;
        }

        // 普通
        selected.push(r);
        input.value = '';
        renderChips();
        updateHidden();
        dropdown.classList.remove('open');
        input.focus();
    }

    // ===== 事件 =====
    input.addEventListener('input', () => {
        const q = input.value;
        if (q.trim() === '') {
            dropdown.classList.remove('open');
            dropdown.innerHTML = '';
            return;
        }
        renderDropdown(searchRecipients(q));
    });

    input.addEventListener('focus', () => {
        if (input.value.trim() !== '') {
            renderDropdown(searchRecipients(input.value));
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && input.value === '' && selected.length > 0) {
            selected.pop();
            renderChips();
            updateHidden();
        }
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    box.addEventListener('click', () => input.focus());

    // ===== 提交前检查 =====
    document.getElementById('annForm').addEventListener('submit', (e) => {
        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one recipient.');
            input.focus();
        }
    });
</script>

</body>
</html>