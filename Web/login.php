<?php
session_start();

// 已登录 → 直接跳转
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'teacher') {
        header('Location: teacher_index.php');
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: student_index.php');
        exit;
    }
}

$error = '';

if (isset($_POST['userId'])) {

    require_once 'db.php';
    /** @var mysqli $conn */

    $userId   = trim($_POST['userId']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($userId === '' || $password === '') {
        $error = 'Please enter both User ID and password.';
    } else {

        $firstChar = strtoupper(substr($userId, 0, 1));

        $stmt = null;

        if ($firstChar === 'T') {

            // 老师：用 Teachers.teacher_code 登录
            $sql = "
                SELECT u.id, u.username, u.email, u.password_hash, u.role
                FROM `Teachers` t
                INNER JOIN `Users` u ON u.id = t.user_id
                WHERE t.teacher_code = ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $userId);
            }

        } elseif ($firstChar === 'S') {

            // 学生：用 Students.student_number 登录
            $sql = "
                SELECT u.id, u.username, u.email, u.password_hash, u.role
                FROM `Students` s
                INNER JOIN `Users` u ON u.id = s.user_id
                WHERE s.student_number = ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $userId);
            }

        } else {
            $error = 'Invalid User ID. It should start with "T" (teacher) or "S" (student).';
        }

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $rs = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($rs) <= 0) {
                $error = 'Invalid User ID or password.';
            } else {
                $rc = mysqli_fetch_assoc($rs);

                // UI 阶段：明文比较
                if ($password !== $rc['password_hash']) {
                    $error = 'Invalid User ID or password.';
                } else {
                    $_SESSION['userID']   = $rc['id'];
                    $_SESSION['username'] = $rc['username'];
                    $_SESSION['email']    = $rc['email'];
                    $_SESSION['role']     = $rc['role'];

                    mysqli_free_result($rs);
                    mysqli_stmt_close($stmt);

                    if ($rc['role'] === 'teacher') {
                        header('Location: teacher_index.php');
                    } else {
                        header('Location: student_index.php');
                    }
                    exit;
                }
            }

            mysqli_free_result($rs);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TeamBox</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

        body {
            background: #f5f5f5;
            height: 100vh;
            display: flex; justify-content: center; align-items: center;
        }

        .container {
            width: 1000px; height: 550px;
            background: #fff;
            border-radius: 20px; overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            display: flex;
        }

        .logo-section {
            width: 50%; background: black;
            display: flex; justify-content: center; align-items: center;
        }

        .logo-section img { width: 85%; max-width: 450px; height: auto; }

        .login-section {
            width: 50%; padding: 60px;
            display: flex; flex-direction: column; justify-content: center;
        }

        .login-section h1 { color: #222; font-size: 42px; margin-bottom: 12px; }
        .subtitle { color: #666; font-size: 18px; margin-bottom: 35px; }

        .error-message {
            padding: 12px 16px; margin-bottom: 20px;
            background: #ffe1e1; color: #a21b1b;
            border-left: 4px solid #a21b1b; border-radius: 8px;
            font-size: 14px; line-height: 1.5;
        }

        .form-group { margin-bottom: 22px; }

        .form-group label {
            display: block; color: #222;
            font-size: 17px; font-weight: bold; margin-bottom: 9px;
        }

        .form-group input {
            width: 100%; height: 52px; padding: 0 15px;
            background: #fff; color: #000;
            border: 2px solid #ccc; border-radius: 9px;
            font-size: 17px; outline: none;
            transition: 0.3s;
            font-family: inherit;
        }

        .form-group input:focus {
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.08);
        }

        .login-btn {
            width: 100%; height: 58px; margin-top: 8px;
            background: #fff; color: #000;
            border: 2px solid #000; border-radius: 10px;
            font-size: 20px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }

        .login-btn:hover { background: #000; color: #fff; }
        .login-btn:active { background: #333; color: #fff; transform: scale(0.98); }

        .login-hint {
            padding: 14px 16px; margin-top: 20px;
            background: #f7f7f7; border-left: 4px solid #000; border-radius: 8px;
            color: #666; font-size: 13px; line-height: 1.6;
        }

        .login-hint strong { color: #222; }

        @media (max-width: 900px) {
            .container { width: 95%; height: auto; flex-direction: column; }
            .logo-section { width: 100%; height: 220px; }
            .login-section { width: 100%; padding: 40px 25px; }
            .login-section h1 { font-size: 32px; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="logo-section">
        <img src="TeamBox_logo_black.png" alt="TeamBox Logo">
    </div>

    <div class="login-section">

        <h1>Login</h1>
        <p class="subtitle">Log in with your TeamBox account</p>

        <?php if ($error !== ''): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">

            <div class="form-group">
                <label for="userId">User ID</label>
                <input
                        type="text"
                        id="userId"
                        name="userId"
                        placeholder="Teacher: T48291 · Student: S48291537"
                        autocomplete="username"
                        required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                >
            </div>

            <button type="submit" class="login-btn">Log In</button>
        </form>

        <div class="login-hint">
            <strong>Test Accounts:</strong><br>
            Teacher → <strong>T48291</strong> / <strong>12345</strong><br>
            Student → <strong>S48291537</strong> / <strong>12345</strong>
        </div>

    </div>

</div>

</body>
</html>