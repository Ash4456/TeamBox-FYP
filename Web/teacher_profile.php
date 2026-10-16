<?php
$active = 'profile';

// ===== session + 数据库 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

// 没登录 → 回登录页
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];


// =========================================================
// 1. 处理「Edit Profile」提交
// =========================================================
$profileMsg  = '';
$profileType = '';   // success / error

if (isset($_POST['saveProfile'])) {

    $fullName = trim(isset($_POST['fullName']) ? $_POST['fullName'] : '');
    $email    = trim(isset($_POST['email'])    ? $_POST['email']    : '');

    if ($fullName === '' || $email === '') {
        $profileMsg  = 'Name and email cannot be empty.';
        $profileType = 'error';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileMsg  = 'Please enter a valid email address.';
        $profileType = 'error';

    } else {
        // 检查 email 有没有被别人占用
        $stmt = mysqli_prepare($conn, "SELECT id FROM `Users` WHERE email = ? AND id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $email, $userID);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        $dup = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($stmt);

        if ($dup) {
            $profileMsg  = 'That email is already used by another account.';
            $profileType = 'error';
        } else {
            $stmt = mysqli_prepare($conn, "
                UPDATE `Users`
                SET username = ?, email = ?
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ssi", $fullName, $email, $userID);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['username'] = $fullName;
                $_SESSION['email']    = $email;
                $profileMsg  = 'Profile updated successfully.';
                $profileType = 'success';
            } else {
                $profileMsg  = 'Update failed: ' . mysqli_error($conn);
                $profileType = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
}


// =========================================================
// 2. 处理「Change Password」提交
// =========================================================
$pwdMsg  = '';
$pwdType = '';

if (isset($_POST['savePassword'])) {

    $current = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
    $new     = isset($_POST['newPassword'])     ? $_POST['newPassword']     : '';
    $confirm = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

    if ($current === '' || $new === '' || $confirm === '') {
        $pwdMsg  = 'Please fill in all password fields.';
        $pwdType = 'error';

    } elseif ($new !== $confirm) {
        $pwdMsg  = 'New passwords do not match.';
        $pwdType = 'error';

    } elseif (strlen($new) < 6) {
        $pwdMsg  = 'New password must be at least 6 characters.';
        $pwdType = 'error';

    } else {
        // 先查现有密码（UI 阶段：明文比较）
        $stmt = mysqli_prepare($conn, "SELECT password_hash FROM `Users` WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userID);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($stmt);

        if (!$row || $current !== $row['password_hash']) {
            $pwdMsg  = 'Current password is incorrect.';
            $pwdType = 'error';
        } else {
            // UI 阶段：直接存明文
            // 正式阶段：$hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE `Users` SET password_hash = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new, $userID);

            if (mysqli_stmt_execute($stmt)) {
                $pwdMsg  = 'Password updated successfully.';
                $pwdType = 'success';
            } else {
                $pwdMsg  = 'Update failed: ' . mysqli_error($conn);
                $pwdType = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
}


// =========================================================
// 3. 从数据库读当前老师信息
// =========================================================
$teacher = array(
        'username'   => '',
        'email'      => '',
        'role'       => '',
        'department' => '',
        'title'      => '',
        'hire_date'  => '',
);

$stmt = mysqli_prepare($conn, "
    SELECT u.username, u.email, u.role,
           t.department, t.title, t.hire_date
    FROM `Users` u
    LEFT JOIN `Teachers` t ON t.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($rs);
mysqli_stmt_close($stmt);

if ($row) {
    $teacher = $row;
} else {
    // session 里的 userID 有问题
    header('Location: logout.php');
    exit;
}

// 处理 hire_date 显示格式
$hireDate = '—';
if (!empty($teacher['hire_date'])) {
    $hireDate = date('d M Y', strtotime($teacher['hire_date']));
}

// 显示用 role
$displayRole = 'Teacher';

// 显示用 title
$displayTitle = !empty($teacher['title']) ? $teacher['title'] : '—';

// 显示用 department
$displayDept = !empty($teacher['department']) ? $teacher['department'] : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | TeamBox</title>
    <link rel="stylesheet" href="teacher.css">
</head>
<body>

<?php include 'teacher_nav.php'; ?>

<main class="main">
    <div class="content">

        <h1>Profile</h1>
        <p class="subtitle">Your instructor account information</p>

        <!-- =========================================================
             Account 信息
             ========================================================= -->
        <h2>Account</h2>
        <div class="card">
            <table>
                <tr>
                    <td style="width:40%; color:#6b6f78;">Full Name</td>
                    <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                </tr>
                <tr>
                    <td style="color:#6b6f78;">Email</td>
                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                </tr>
                <tr>
                    <td style="color:#6b6f78;">Role</td>
                    <td><?php echo htmlspecialchars($displayRole); ?></td>
                </tr>
                <tr>
                    <td style="color:#6b6f78;">Department</td>
                    <td><?php echo htmlspecialchars($displayDept); ?></td>
                </tr>
                <tr>
                    <td style="color:#6b6f78;">Title</td>
                    <td><?php echo htmlspecialchars($displayTitle); ?></td>
                </tr>
                <tr>
                    <td style="color:#6b6f78;">Hire Date</td>
                    <td><?php echo htmlspecialchars($hireDate); ?></td>
                </tr>
            </table>
        </div>

        <!-- =========================================================
             Edit Profile
             ========================================================= -->
        <h2>Edit Profile</h2>
        <div class="card">
            <form method="post" action="teacher_profile.php" class="announcement-block stack">

                <?php if ($profileMsg !== ''): ?>
                    <div style="padding:12px 16px; margin-bottom:16px; border-radius:8px;
                            background: <?php echo $profileType==='success' ? '#eaf7ee' : '#fdeeee'; ?>;
                            color: <?php echo $profileType==='success' ? '#176b2c' : '#a21b1b'; ?>;
                            border-left: 4px solid <?php echo $profileType==='success' ? '#2e9d4d' : '#d32f2f'; ?>;">
                        <?php echo htmlspecialchars($profileMsg); ?>
                    </div>
                <?php endif; ?>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="fullName">Full Name</label>
                    <input
                            type="text"
                            id="fullName"
                            name="fullName"
                            value="<?php echo htmlspecialchars($teacher['username']); ?>"
                            required
                    >
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="email">Email</label>
                    <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($teacher['email']); ?>"
                            required
                    >
                </div>

                <button type="submit" name="saveProfile" class="btn-primary">Save Changes</button>

            </form>
        </div>

        <!-- =========================================================
             Change Password
             ========================================================= -->
        <h2>Change Password</h2>
        <div class="card">
            <form method="post" action="teacher_profile.php" class="announcement-block stack">

                <?php if ($pwdMsg !== ''): ?>
                    <div style="padding:12px 16px; margin-bottom:16px; border-radius:8px;
                            background: <?php echo $pwdType==='success' ? '#eaf7ee' : '#fdeeee'; ?>;
                            color: <?php echo $pwdType==='success' ? '#176b2c' : '#a21b1b'; ?>;
                            border-left: 4px solid <?php echo $pwdType==='success' ? '#2e9d4d' : '#d32f2f'; ?>;">
                        <?php echo htmlspecialchars($pwdMsg); ?>
                    </div>
                <?php endif; ?>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>

                <button type="submit" name="savePassword" class="btn-primary">Update Password</button>

            </form>
        </div>

    </div>
</main>

</body>
</html>