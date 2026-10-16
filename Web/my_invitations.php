<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];

// 未读数（给铃铛用）
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

// 取自己的 student_id
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
$myStudentID = (int) $rc['id'];

// 查我被邀请的（Pending）
$invitations = array();

$stmt = mysqli_prepare($conn, "
    SELECT
        i.id AS invite_id,
        i.status,
        i.sent_at,
        g.id   AS group_id,
        g.name AS group_name,
        g.max_members,
        g.status AS group_status,
        p.name AS project_name,
        c.code AS course_code,
        inviter_u.username AS inviter_name
    FROM `invitations` i
    JOIN `groups` g   ON g.id = i.group_id
    LEFT JOIN `projects` p ON p.id = g.project_id
    LEFT JOIN `courses`  c ON c.id = g.course_id
    JOIN `students` inviter_s ON inviter_s.id = i.inviter_id
    JOIN `users`    inviter_u ON inviter_u.id = inviter_s.user_id
    WHERE i.invitee_id = ?
      AND i.status = 'Pending'
    ORDER BY i.sent_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $myStudentID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($rs)) {
    $invitations[] = $row;
}
mysqli_stmt_close($stmt);

// helper
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return '—';
        $ts = strtotime($datetime);
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
    <title>My Invitations | TeamBox</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: #f5f5f5; color: #222; }

        .navbar { width: 100%; padding: 15px 30px; background: black; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2); }
        .navbar-top { position: relative; width: 100%; min-height: 65px; display: flex; justify-content: center; align-items: center; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-area img { width: 65px; height: 65px; object-fit: contain; display: block; }
        .logo-area span { color: white; font-size: 30px; font-weight: bold; }

        .bell-link { position: absolute; right: 0; top: 50%; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; transition: background 0.3s; }
        .bell-link:hover { background: rgba(255, 255, 255, 0.15); }
        .bell-link img { width: 28px; height: 28px; object-fit: contain; display: block; }
        .bell-badge { position: absolute; top: 2px; right: 2px; min-width: 18px; height: 18px; padding: 0 5px; background: #d93025; color: white; border-radius: 9px; font-size: 11px; font-weight: bold; display: flex; align-items: center; justify-content: center; border: 2px solid black; }

        .function-menu { width: 100%; display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 22px; }
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

        .invite-list { display: flex; flex-direction: column; gap: 14px; }

        .invite-card {
            padding: 22px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            border-left: 6px solid #245889;
        }

        .invite-card h3 { font-size: 18px; margin-bottom: 8px; }
        .invite-card p { color: #666; font-size: 14px; margin-bottom: 6px; }
        .invite-card .muted { color: #888; font-size: 12px; }

        .invite-actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
        }

        .accept-btn,
        .reject-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 110px;
            height: 42px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .accept-btn {
            background: black;
            color: white;
            border: 2px solid black;
        }
        .accept-btn:hover { background: #333; }

        .reject-btn {
            background: white;
            color: #c62828;
            border: 2px solid #c62828;
        }
        .reject-btn:hover { background: #c62828; color: white; }

        .muted-note {
            padding: 18px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            color: #777;
            font-size: 14px;
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
            .invite-actions { flex-direction: column; }
            .accept-btn, .reject-btn { width: 100%; }
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
        <a href="my_invitations.php" class="nav-link active">Invitations</a>
        <a href="student_notifications.php" class="nav-link">Notifications</a>
        <a href="student_profile.php" class="nav-link">Profile</a>
        <a href="logout.php" class="nav-link logout-link">Log Out</a>
    </div>
</nav>

<main class="main-content">

    <section class="page-heading">
        <h1>My Invitations</h1>
        <p>Group invitations you have received. Accept to join the group.</p>
    </section>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'accepted'): ?>
            <div style="padding:14px 18px; margin-bottom:20px; border-radius:8px; background:#eaf7ee; color:#176b2c; border-left:4px solid #2e9d4d;">
                You have joined the group.
            </div>
        <?php elseif ($_GET['msg'] === 'rejected'): ?>
            <div style="padding:14px 18px; margin-bottom:20px; border-radius:8px; background:#fdeeee; color:#a21b1b; border-left:4px solid #d32f2f;">
                Invitation rejected.
            </div>
        <?php elseif ($_GET['msg'] === 'error'): ?>
            <div style="padding:14px 18px; margin-bottom:20px; border-radius:8px; background:#fdeeee; color:#a21b1b; border-left:4px solid #d32f2f;">
                Something went wrong.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="invite-list">

        <?php if (count($invitations) === 0): ?>
            <div class="muted-note">You have no pending invitations.</div>
        <?php endif; ?>

        <?php foreach ($invitations as $inv): ?>
            <div class="invite-card">
                <h3><?php echo htmlspecialchars($inv['group_name']); ?></h3>
                <p><strong>Project:</strong> <?php echo htmlspecialchars($inv['project_name']); ?></p>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($inv['course_code']); ?></p>
                <p><strong>Invited by:</strong> <?php echo htmlspecialchars($inv['inviter_name']); ?></p>
                <p class="muted">Sent: <?php echo htmlspecialchars(timeAgo($inv['sent_at'])); ?></p>

                <div class="invite-actions">
                    <a href="accept_invite_process.php?id=<?php echo (int)$inv['invite_id']; ?>" class="accept-btn">
                        Accept
                    </a>
                    <a href="reject_invite_process.php?id=<?php echo (int)$inv['invite_id']; ?>" class="reject-btn">
                        Reject
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</main>

</body>
</html>
