<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Login | TeamBox</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
            height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 1000px;
            height: 550px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);

            display: flex;
        }

        /* Left logo section */
        .logo-section {
            width: 50%;
            background: black;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-section img {
            width: 85%;
            max-width: 450px;
            height: auto;
        }

        /* Right login section */
        .login-section {
            width: 50%;
            padding: 60px;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-section h1 {
            color: #222;
            font-size: 42px;
            margin-bottom: 12px;
        }

        .subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 35px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            color: #222;
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .form-group input {
            width: 100%;
            height: 52px;
            padding: 0 15px;

            background: white;
            color: black;
            border: 2px solid #cccccc;
            border-radius: 9px;

            font-size: 17px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: black;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        }

        /* Login button */
        .login-btn {
            width: 100%;
            height: 58px;
            margin-top: 8px;

            background: white;
            color: black;
            border: 2px solid black;
            border-radius: 10px;

            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: black;
            color: white;
        }

        .login-btn:active {
            background: #333;
            color: white;
            transform: scale(0.98);
        }

        /* Back link styled like the main page buttons */
        .back-btn {
            width: 100%;
            height: 52px;
            margin-top: 18px;

            background: white;
            color: black;
            border: 2px solid black;
            border-radius: 10px;

            font-size: 17px;
            text-decoration: none;

            display: flex;
            justify-content: center;
            align-items: center;

            cursor: pointer;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: black;
            color: white;
        }

        .back-btn:active {
            background: #333;
            color: white;
            transform: scale(0.98);
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Logo section -->
    <div class="logo-section">
        <img src="TeamBox_logo_black.png" alt="TeamBox Logo">
    </div>

    <!-- Student login section -->
    <div class="login-section">

        <h1>Student Login</h1>

        <p class="subtitle">
            Log in to your TeamBox student account
        </p>

        <form action="student_index.php" method="post">

            <div class="form-group">
                <label for="studentID">
                    Student ID
                </label>

                <input
                    type="text"
                    id="studentID"
                    name="studentID"
                    placeholder="Enter your student ID"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button
                type="submit"
                class="login-btn"
            >
                Log In
            </button>

        </form>

        <!-- Back to login selection -->
        <a href="login.php" class="back-btn">
            ← Back to Login Selection
        </a>

    </div>

</div>

</body>
</html>