<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

// =========================================================
// 未讀數（鈴檔用）
// =========================================================
$unreadNotifications = 0;

$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c
    FROM `notifications`
    WHERE user_id = ? AND is_read = 0
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$unreadNotifications = (int) $rc['c'];
mysqli_stmt_close($stmt);

// =========================================================
// 系統提示（?msg=）
// =========================================================
$systemMsg = '';
$msgType   = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'accepted') {
        $systemMsg = 'Invitation accepted. You have joined the group.';
        $msgType   = 'success';
    } elseif ($_GET['msg'] === 'rejected') {
        $systemMsg = 'Invitation rejected.';
        $msgType   = 'error';
    } elseif ($_GET['msg'] === 'error') {
        $systemMsg = 'Something went wrong.';
        $msgType   = 'error';
    }
}

// =========================================================
// Summary 卡片統計
// =========================================================
$totalNotifications = 0;
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM `notifications` WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$totalNotifications = (int) $rc['c'];
mysqli_stmt_close($stmt);

$taskNotifications = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `notifications`
    WHERE user_id = ? AND type = 'task'
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$taskNotifications = (int) $rc['c'];
mysqli_stmt_close($stmt);

$deadlineNotifications = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM `notifications`
    WHERE user_id = ? AND type = 'system'
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rc = mysqli_fetch_assoc($rs);
$deadlineNotifications = (int) $rc['c'];
mysqli_stmt_close($stmt);

// =========================================================
// 通知列表（帶 filter）
// =========================================================
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$sql = "
    SELECT id, type, title, message, link, is_read, created_at
    FROM `notifications`
    WHERE user_id = ?
";

switch ($filter) {
    case 'unread':
        $sql .= " AND is_read = 0";
        break;
    case 'tasks':
        $sql .= " AND type = 'task'";
        break;
    case 'project':
        $sql .= " AND type = 'project'";
        break;
    case 'team':
        $sql .= " AND type = 'team'";
        break;
    case 'files':
        $sql .= " AND type = 'file'";
        break;
    case 'all':
    default:
        break;
}

$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

$notifications = array();
while ($row = mysqli_fetch_assoc($rs)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);

// =========================================================
// 辅助函数
// =========================================================
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

if (!function_exists('typeClass')) {
    function typeClass($type) {
        switch ($type) {
            case 'task':    return 'type-task';
            case 'team':    return 'type-team';
            case 'file':    return 'type-file';
            case 'project': return 'type-project';
            default:        return 'type-alert';
        }
    }
}

if (!function_exists('typeLabel')) {
    function typeLabel($type) {
        switch ($type) {
            case 'task':    return 'Task';
            case 'team':    return 'Team';
            case 'file':    return 'File';
            case 'project': return 'Project';
            default:        return 'System';
        }
    }
}

if (!function_exists('typeIcon')) {
    function typeIcon($type) {
        switch ($type) {
            case 'task':    return 'TASK';
            case 'team':    return 'TEAM';
            case 'file':    return 'FILE';
            case 'project': return 'PROJ';
            default:        return 'SYS';
        }
    }
}

if (!function_exists('extractInviteID')) {
    function extractInviteID($link) {
        if (empty($link)) return 0;
        $q = array();
        parse_str(parse_url($link, PHP_URL_QUERY), $q);
        return isset($q['id']) ? (int)$q['id'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | TeamBox</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: #f5f5f5; color: #222; }

        .navbar {
            width: 100%; padding: 15px 30px; background: black;
            display: flex; flex-direction: column; gap: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
        }
        .navbar-top {
            position: relative; width: 100%; min-height: 65px;
            display: flex; justify-content: center; align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-area img { width: 65px; height: 65px; object-fit: contain; display: block; }
        .logo-area span { color: white; font-size: 30px; font-weight: bold; }

        .bell-link {
            position: absolute; right: 0; top: 50%;
            transform: translateY(-50%);
            display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px;
            border-radius: 50%;
            transition: background 0.3s;
        }
        .bell-link:hover { background: rgba(255, 255, 255, 0.15); }
        .bell-link img { width: 28px; height: 28px; object-fit: contain; display: block; }
        .bell-badge {
            position: absolute; top: 2px; right: 2px;
            min-width: 18px; height: 18px; padding: 0 5px;
            background: #d93025; color: white;
            border-radius: 9px;
            font-size: 11px; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid black;
        }

        .function-menu {
            width: 100%; display: flex; justify-content: center; align-items: center;
            flex-wrap: wrap; gap: 22px;
        }
        .nav-link { color: white; font-size: 15px; text-decoration: none; padding: 6px 2px; transition: color 0.3s; }
        .nav-link:hover { color: #bbbbbb; }
        .nav-link.active { color: white; font-weight: bold; border-bottom: 2px solid white; }
        .logout-link { color: #ff8888; }
        .logout-link:hover { color: #ff4444; }

        .main-content { width: 90%; max-width: 1200px; margin: 40px auto; }

        .page-heading {
            padding: 30px; margin-bottom: 25px;
            background: white; border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .page-heading h1 { margin-bottom: 10px; font-size: 35px; }
        .page-heading p { color: #666; font-size: 17px; line-height: 1.6; }

        .alert {
            padding: 14px 18px; margin-bottom: 20px;
            border-radius: 8px; font-size: 14px;
            border-left: 4px solid;
        }
        .alert-success { background: #eaf7ee; color: #176b2c; border-color: #2e9d4d; }
        .alert-error   { background: #fdeeee; color: #a21b1b; border-color: #d32f2f; }

        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .summary-card {
            min-height: 140px; padding: 24px;
            background: white; border-left: 5px solid black;
            border-radius: 15px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .summary-card:hover { transform: translateY(-5px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15); }
        .summary-card h2 { margin-bottom: 14px; color: #666; font-size: 16px; }
        .summary-number { font-size: 38px; font-weight: bold; }
        .summary-description { margin-top: 7px; color: #777; font-size: 13px; }

        .notification-section {
            padding: 28px; background: white; border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        .section-header {
            padding-bottom: 18px; margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee;
            display: flex; justify-content: space-between; align-items: center;
            gap: 20px;
        }
        .section-header h2 { font-size: 24px; }
        .header-actions { display: flex; align-items: center; gap: 10px; }

        .filter-select {
            min-width: 165px; height: 43px; padding: 0 12px;
            background: white; color: black;
            border: 2px solid #cccccc; border-radius: 8px;
            font-size: 14px; outline: none; cursor: pointer;
        }
        .filter-select:focus { border-color: black; }

        .mark-read-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 155px; height: 43px; padding: 0 14px;
            background: white; color: black;
            border: 2px solid black; border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer; transition: 0.3s;
        }
        .mark-read-btn:hover { background: black; color: white; }

        .notification-tabs { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px; }

        .tab-btn {
            min-width: 100px; height: 40px; padding: 0 14px;
            background: white; color: black;
            border: 1px solid #bbbbbb; border-radius: 20px;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center;
            transition: 0.3s;
        }
        .tab-btn:hover,
        .tab-btn.active {
            background: black; color: white; border-color: black;
        }

        .notification-list { display: flex; flex-direction: column; gap: 14px; }

        .notification-item {
            padding: 20px;
            background: #fafafa;
            border: 1px solid #dddddd;
            border-radius: 12px;

            display: grid;
            grid-template-columns: 54px 1fr auto;
            align-items: center;
            gap: 17px;

            transition: transform 0.3s, box-shadow 0.3s;
        }
        .notification-item:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .notification-item.unread { background: white; border-left: 6px solid black; }

        .notification-icon {
            width: 50px; height: 50px;
            background: black; color: white; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 13px; font-weight: bold;
        }
        .read-icon { background: #777777; }

        .notification-content h3 { margin-bottom: 7px; font-size: 17px; }
        .notification-content p { color: #666; font-size: 14px; line-height: 1.5; }
        .notification-time { display: block; margin-top: 8px; color: #888; font-size: 12px; }

        .notification-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 9px; }

        .unread-dot {
            width: 11px; height: 11px;
            background: black; border-radius: 50%;
        }

        .notification-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 130px; height: 38px; padding: 0 12px;
            background: white; color: black;
            border: 2px solid black; border-radius: 7px;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer; transition: 0.3s;
        }
        .notification-btn:hover { background: black; color: white; }

        .notification-type {
            display: inline-block; padding: 5px 9px; margin-top: 9px;
            background: #eeeeee; color: #555; border-radius: 15px;
            font-size: 12px; font-weight: bold;
        }
        .type-task    { background: #fff1c2; color: #765900; }
        .type-team    { background: #e5f0ff; color: #245889; }
        .type-file    { background: #e9e1ff; color: #553192; }
        .type-project { background: #d9f5df; color: #176b2c; }
        .type-alert   { background: #ffe1e1; color: #a21b1b; }

        .muted { color: #777; font-size: 14px; }

        .notification-note {
            padding: 18px; margin-top: 22px;
            background: #f7f7f7; border-left: 5px solid black;
            border-radius: 8px;
            color: #666; font-size: 14px; line-height: 1.6;
        }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            display: none; justify-content: center; align-items: center;
            padding: 20px; z-index: 1000; overflow-y: auto;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            width: 100%; max-width: 480px;
            background: white; border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.25s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 22px 28px;
            border-bottom: 2px solid #eeeeee;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h2 { font-size: 22px; }

        .modal-close {
            width: 38px; height: 38px;
            background: white; color: #555;
            border: 2px solid #cccccc; border-radius: 8px;
            font-size: 20px; font-weight: bold; cursor: pointer;
            display: flex; justify-content: center; align-items: center;
            transition: 0.3s;
        }
        .modal-close:hover { background: #c62828; color: white; border-color: #c62828; }

        .modal-body {
            padding: 28px;
            font-size: 15px;
            line-height: 1.6;
            color: #444;
        }
        .modal-body .muted { margin-top: 10px; color: #777; font-size: 14px; }

        /* =========================================================
           Invitation Details Box（新）
           ========================================================= */
        .invite-details {
            margin-top: 16px;
            padding: 14px 16px;
            background: #f7f8fa;
            border-radius: 10px;
            border: 1px solid #e6e8eb;
        }

        .invite-detail-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0;
            font-size: 14px;
            border-bottom: 1px solid #eef0f3;
        }
        .invite-detail-row:last-child { border-bottom: none; }

        .invite-detail-label {
            color: #6b6f78;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .invite-detail-value {
            color: #2c2f36;
            font-weight: 600;
            text-align: right;
            word-break: break-word;
        }

        .modal-footer {
            padding: 20px 28px;
            border-top: 2px solid #eeeeee;
            display: flex; justify-content: flex-end; gap: 12px;
        }

        .modal-btn {
            min-width: 120px; height: 46px; padding: 0 18px;
            border-radius: 8px;
            font-size: 15px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }
        .modal-btn-agree {
            background: black; color: white;
            border: 2px solid black;
        }
        .modal-btn-agree:hover { background: #333; }

        .modal-btn-reject {
            background: white; color: #c62828;
            border: 2px solid #c62828;
        }
        .modal-btn-reject:hover { background: #c62828; color: white; }

        @media (max-width: 950px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .section-header { align-items: flex-start; flex-direction: column; }
            .header-actions { width: 100%; }
            .filter-select, .mark-read-btn { flex: 1; }
        }
        @media (max-width: 650px) {
            .navbar { padding: 15px; }
            .logo-area img { width: 55px; height: 55px; }
            .logo-area span { font-size: 26px; }
            .bell-link { width: 38px; height: 38px; }
            .bell-link img { width: 22px; height: 22px; }
            .function-menu { flex-direction: column; gap: 12px; }
            .main-content { width: 94%; margin: 25px auto; }
            .page-heading h1 { font-size: 28px; }
            .summary-grid { grid-template-columns: 1fr; }
            .header-actions { align-items: stretch; flex-direction: column; }
            .filter-select, .mark-read-btn { width: 100%; }
            .notification-item { grid-template-columns: 48px 1fr; align-items: flex-start; }
            .notification-icon { width: 45px; height: 45px; }
            .notification-actions { grid-column: 1 / -1; align-items: stretch; }
            .notification-btn { width: 100%; }

            .modal-footer { flex-direction: column; }
            .modal-btn    { width: 100%; }
        }
    </style>
</head>

<body>

<nav class="navbar">
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

    <div class="function-menu">
        <a href="student_index.php" class="nav-link">Dashboard</a>
        <a href="student_create_group.php" class="nav-link">Create Group</a>
        <a href="my_team.php" class="nav-link">My Team</a>
        <a href="student_task.php" class="nav-link">Tasks</a>
        <a href="student_files.php" class="nav-link">Files</a>
        <a href="student_chat.php" class="nav-link">Group Chat</a>
        <a href="student_contribution.php" class="nav-link">Contribution</a>
        <a href="student_notifications.php" class="nav-link active">Notifications</a>
        <a href="student_profile.php" class="nav-link">Profile</a>
        <a href="logout.php" class="nav-link logout-link">Log Out</a>
    </div>
</nav>

<main class="main-content">

    <section class="page-heading">
        <h1>Notifications</h1>
        <p>
            View your task reminders, project updates, team activities,
            file notifications and important deadline alerts.
        </p>
    </section>

    <?php if ($systemMsg !== ''): ?>
        <div class="alert <?php echo $msgType === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo htmlspecialchars($systemMsg); ?>
        </div>
    <?php endif; ?>

    <section class="summary-grid">
        <div class="summary-card">
            <h2>All Notifications</h2>
            <p class="summary-number"><?php echo $totalNotifications; ?></p>
            <p class="summary-description">Total notifications received</p>
        </div>
        <div class="summary-card">
            <h2>Unread</h2>
            <p class="summary-number"><?php echo $unreadNotifications; ?></p>
            <p class="summary-description">Notifications not yet read</p>
        </div>
        <div class="summary-card">
            <h2>Task Alerts</h2>
            <p class="summary-number"><?php echo $taskNotifications; ?></p>
            <p class="summary-description">Task assignments and reminders</p>
        </div>
        <div class="summary-card">
            <h2>System Alerts</h2>
            <p class="summary-number"><?php echo $deadlineNotifications; ?></p>
            <p class="summary-description">System and deadline alerts</p>
        </div>
    </section>

    <section class="notification-section">

        <div class="section-header">
            <h2>Recent Notifications</h2>

            <div class="header-actions">
                <form method="get" action="student_notifications.php" style="margin:0;">
                    <select name="filter" class="filter-select" onchange="this.form.submit()">
                        <option value="all"     <?php echo $filter==='all'     ? 'selected' : ''; ?>>All Notifications</option>
                        <option value="unread"  <?php echo $filter==='unread'  ? 'selected' : ''; ?>>Unread Only</option>
                        <option value="tasks"   <?php echo $filter==='tasks'   ? 'selected' : ''; ?>>Task Notifications</option>
                        <option value="project" <?php echo $filter==='project' ? 'selected' : ''; ?>>Project Updates</option>
                        <option value="team"    <?php echo $filter==='team'    ? 'selected' : ''; ?>>Team Activities</option>
                        <option value="files"   <?php echo $filter==='files'   ? 'selected' : ''; ?>>File Updates</option>
                    </select>
                </form>

                <a href="mark_all_read.php" class="mark-read-btn">Mark All as Read</a>
            </div>
        </div>

        <div class="notification-tabs">
            <a href="student_notifications.php?filter=all"     class="tab-btn <?php echo $filter==='all'     ? 'active' : ''; ?>">All</a>
            <a href="student_notifications.php?filter=tasks"   class="tab-btn <?php echo $filter==='tasks'   ? 'active' : ''; ?>">Tasks</a>
            <a href="student_notifications.php?filter=project" class="tab-btn <?php echo $filter==='project' ? 'active' : ''; ?>">Projects</a>
            <a href="student_notifications.php?filter=team"    class="tab-btn <?php echo $filter==='team'    ? 'active' : ''; ?>">Team</a>
            <a href="student_notifications.php?filter=files"   class="tab-btn <?php echo $filter==='files'   ? 'active' : ''; ?>">Files</a>
        </div>

        <div class="notification-list">

            <?php if (count($notifications) === 0): ?>
                <p class="muted">No notifications yet.</p>
            <?php endif; ?>

            <?php foreach ($notifications as $n): ?>
                <?php
                $isInvite = ($n['type'] === 'team'
                        && !empty($n['link'])
                        && strpos($n['link'], 'accept_invite.php') === 0);

                $inviteID = $isInvite ? extractInviteID($n['link']) : 0;
                ?>

                <article class="notification-item <?php echo ((int)$n['is_read'] === 0) ? 'unread' : ''; ?>">

                    <div class="notification-icon <?php echo ((int)$n['is_read'] === 1) ? 'read-icon' : ''; ?>">
                        <?php echo htmlspecialchars(typeIcon($n['type'])); ?>
                    </div>

                    <div class="notification-content">
                        <h3><?php echo htmlspecialchars($n['title']); ?></h3>
                        <p><?php echo htmlspecialchars($n['message']); ?></p>

                        <span class="notification-type <?php echo typeClass($n['type']); ?>">
                            <?php echo htmlspecialchars(typeLabel($n['type'])); ?>
                        </span>

                        <span class="notification-time">
                            <?php echo htmlspecialchars(timeAgo($n['created_at'])); ?>
                        </span>
                    </div>

                    <div class="notification-actions">
                        <?php if ((int)$n['is_read'] === 0): ?>
                            <span class="unread-dot" title="Unread"></span>
                        <?php endif; ?>

                        <?php if ($isInvite && $inviteID > 0): ?>
                            <button
                                    type="button"
                                    class="notification-btn"
                                    onclick="openInviteModal(<?php echo (int)$inviteID; ?>);"
                            >
                                View Invitation
                            </button>
                        <?php elseif (!empty($n['link'])): ?>
                            <a href="<?php echo htmlspecialchars($n['link']); ?>" class="notification-btn">
                                Open
                            </a>
                        <?php else: ?>
                            <a href="student_notifications.php" class="notification-btn">
                                View
                            </a>
                        <?php endif; ?>
                    </div>

                </article>
            <?php endforeach; ?>

        </div>

        <div class="notification-note">
            Notifications are loaded from the database.
            Click "Mark All as Read" to clear the unread badge.
        </div>

    </section>

</main>

<!-- =========================================================
     Invitation Modal（顯示 Project / Group / Course / Invited by）
     ========================================================= -->
<div class="modal-overlay" id="inviteModal">

    <div class="modal-box">

        <div class="modal-header">
            <h2>Group Invitation</h2>
            <button type="button" class="modal-close" onclick="closeInviteModal();">✕</button>
        </div>

        <div class="modal-body">
            <p>Do you want to join this group?</p>

            <div class="invite-details">
                <div class="invite-detail-row">
                    <span class="invite-detail-label">Project</span>
                    <span class="invite-detail-value" id="inviteProject">—</span>
                </div>
                <div class="invite-detail-row">
                    <span class="invite-detail-label">Group</span>
                    <span class="invite-detail-value" id="inviteGroup">—</span>
                </div>
                <div class="invite-detail-row">
                    <span class="invite-detail-label">Course</span>
                    <span class="invite-detail-value" id="inviteCourse">—</span>
                </div>
                <div class="invite-detail-row">
                    <span class="invite-detail-label">Invited by</span>
                    <span class="invite-detail-value" id="inviteInviter">—</span>
                </div>
            </div>

            <p class="muted" style="margin-top: 16px;">
                Click <strong>Agree</strong> to join, or <strong>Reject</strong> to decline.
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-reject" onclick="rejectInvitation();">
                Reject
            </button>
            <button type="button" class="modal-btn modal-btn-agree" onclick="agreeInvitation();">
                Agree
            </button>
        </div>

    </div>

</div>

<script>
    var currentInviteID = 0;

    function openInviteModal(inviteID) {
        currentInviteID = inviteID;
        document.getElementById('inviteModal').classList.add('active');
        document.body.style.overflow = 'hidden';

        // Reset fields
        document.getElementById('inviteProject').textContent = 'Loading...';
        document.getElementById('inviteGroup').textContent   = 'Loading...';
        document.getElementById('inviteCourse').textContent  = 'Loading...';
        document.getElementById('inviteInviter').textContent = 'Loading...';

        // Fetch invite details
        fetch('get_invite_details.php?id=' + inviteID)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    document.getElementById('inviteProject').textContent = '—';
                    document.getElementById('inviteGroup').textContent   = '—';
                    document.getElementById('inviteCourse').textContent  = '—';
                    document.getElementById('inviteInviter').textContent = '—';
                    return;
                }

                document.getElementById('inviteProject').textContent =
                    data.project_name || '—';

                document.getElementById('inviteGroup').textContent =
                    data.group_name || '—';

                if (data.course_code || data.course_name) {
                    document.getElementById('inviteCourse').textContent =
                        (data.course_code ? data.course_code + ' - ' : '') + (data.course_name || '');
                } else {
                    document.getElementById('inviteCourse').textContent = '—';
                }

                document.getElementById('inviteInviter').textContent =
                    data.inviter_name || '—';
            })
            .catch(function () {
                document.getElementById('inviteProject').textContent = '—';
                document.getElementById('inviteGroup').textContent   = '—';
                document.getElementById('inviteCourse').textContent  = '—';
                document.getElementById('inviteInviter').textContent = '—';
            });
    }

    function closeInviteModal() {
        document.getElementById('inviteModal').classList.remove('active');
        document.body.style.overflow = '';
        currentInviteID = 0;
    }

    function agreeInvitation() {
        if (currentInviteID > 0) {
            window.location.href = 'accept_invite.php?id=' + currentInviteID;
        }
    }

    function rejectInvitation() {
        if (currentInviteID > 0) {
            window.location.href = 'reject_invite.php?id=' + currentInviteID;
        }
    }

    document.getElementById('inviteModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeInviteModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeInviteModal();
        }
    });
</script>

</body>
</html>