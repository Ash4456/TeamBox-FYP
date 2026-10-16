<?php
session_start();

// if (!isset($_SESSION['studentID'])) {
//     header('Location: student_login.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Project | TeamBox</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f5f5f5;
            color: #222;
        }

        /* =========================
           Top Navigation Bar
        ========================= */

        .navbar {
            width: 100%;
            padding: 15px 30px;
            background: black;

            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;

            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
        }

        .logo-area {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .logo-area img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            display: block;
        }

        .logo-area span {
            color: white;
            font-size: 30px;
            font-weight: bold;
        }

        /* Navigation text links */

        .function-menu {
            width: 100%;

            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 22px;
        }

        .nav-link {
            color: white;
            font-size: 15px;
            text-decoration: none;

            padding: 6px 2px;

            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #bbbbbb;
        }

        .nav-link:active {
            color: #ffffff;
        }

        /* Current page */
        .nav-link.active {
            color: white;
            font-weight: bold;
            border-bottom: 2px solid white;
        }

        /* Log out */
        .logout-link {
            color: #ff8888;
        }

        .logout-link:hover {
            color: #ff4444;
        }

        /* =========================
           Main Content
        ========================= */

        .main-content {
            width: 90%;
            max-width: 1250px;
            margin: 40px auto;
        }

        .page-header {
            padding: 30px;
            margin-bottom: 30px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            margin-bottom: 10px;
            font-size: 35px;
        }

        .page-header p {
            color: #666;
            font-size: 17px;
            line-height: 1.6;
        }

        /* =========================
           Project Card
        ========================= */

        .project-card {
            padding: 30px;
            margin-bottom: 30px;

            background: white;
            border-radius: 15px;
            border-left: 6px solid black;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }

        .project-header h2 {
            font-size: 27px;
            margin-bottom: 8px;
        }

        .project-header p {
            color: #777;
            font-size: 15px;
        }

        .project-status {
            padding: 8px 14px;
            border-radius: 20px;

            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .in-progress {
            background: #fff1c2;
            color: #765900;
        }

        /* =========================
           Info Grid
        ========================= */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            padding: 20px;

            background: #fafafa;
            border: 1px solid #eeeeee;
            border-radius: 12px;
        }

        .info-box h3 {
            color: #555;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .info-box p {
            font-size: 17px;
            font-weight: bold;
        }

        /* =========================
           Project Progress
        ========================= */

        .project-progress {
            padding: 20px 0 0;
            border-top: 2px solid #eeeeee;
        }

        .project-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .project-progress-header strong {
            font-size: 17px;
        }

        .progress-bar {
            width: 100%;
            height: 18px;

            background: #dddddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-value {
            width: 50%;
            height: 100%;
            background: black;
            border-radius: 10px;
        }

        .project-description {
            margin-top: 20px;
            color: #666;
            font-size: 15px;
            line-height: 1.7;
        }

        /* =========================
           Two-column section
        ========================= */

        .section-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .content-box {
            padding: 28px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .content-box h2 {
            padding-bottom: 15px;
            margin-bottom: 20px;

            border-bottom: 2px solid #eeeeee;
            font-size: 22px;
        }

        /* Member list */

        .member-item {
            padding: 14px 0;
            border-bottom: 1px solid #eeeeee;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .member-item:last-child {
            border-bottom: none;
        }

        .member-item h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        .member-item p {
            color: #777;
            font-size: 13px;
        }

        .member-role {
            padding: 6px 12px;
            background: #eeeeee;
            border-radius: 20px;

            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .leader-role {
            background: black;
            color: white;
        }

        /* =========================
           Project Actions (text links)
        ========================= */

        .quick-links {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .text-link {
            color: black;
            font-size: 16px;
            text-decoration: underline;

            transition: color 0.3s;
        }

        .text-link:hover {
            color: #555555;
        }

        .text-link:active {
            color: #000000;
        }

        /* =========================
           Responsive Design
        ========================= */

        @media (max-width: 900px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .section-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 15px;
            }

            .logo-area img {
                width: 55px;
                height: 55px;
            }

            .logo-area span {
                font-size: 26px;
            }

            .function-menu {
                flex-direction: column;
                gap: 12px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                width: 94%;
                margin: 25px auto;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .project-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<!-- Top Navigation Bar -->

<nav class="navbar">

    <div class="logo-area">
        <img src="TeamBox_logo_black.png" alt="TeamBox Logo">

        <span>TeamBox</span>
    </div>

    <!-- Navigation text links -->

    <div class="function-menu">

        <a href="student_index.php" class="nav-link">
            Dashboard
        </a>
        <a href="student_create_group.php" class="nav-link">Create Group</a>
        <a href="my_project.php" class="nav-link active">
            My Project
        </a>

        <a href="my_team.php" class="nav-link">
            My Team
        </a>

        <a href="student_task.php" class="nav-link">
            Tasks
        </a>

        <a href="student_files.php" class="nav-link">
            Files
        </a>

        <a href="student_chat.php" class="nav-link">
            Group Chat
        </a>
        <a href="student_contribution.php" class="nav-link">
            Contribution
        </a>
        <a href="student_notifications.php" class="nav-link">
            Notifications
        </a>

        <a href="student_profile.php" class="nav-link">
            Profile
        </a>

        <a href="login.php" class="nav-link logout-link">
            Log Out
        </a>

    </div>

</nav>

<!-- Main Content -->

<main class="main-content">

    <section class="page-header">

        <h1>My Project</h1>

        <p>
            View your current project details, team members, and
            overall progress. This project is shared with your team.
        </p>

    </section>

    <section class="project-card">

        <div class="project-header">

            <div>
                <h2>TeamBox System Development</h2>
                <p>Course: Software Engineering Project</p>
            </div>

            <span class="project-status in-progress">
                In Progress
            </span>

        </div>

        <div class="info-grid">

            <div class="info-box">
                <h3>Project ID</h3>
                <p>PRJ-2026-001</p>
            </div>

            <div class="info-box">
                <h3>Team</h3>
                <p>Team A</p>
            </div>

            <div class="info-box">
                <h3>Supervisor</h3>
                <p>Dr. Tan</p>
            </div>

            <div class="info-box">
                <h3>Start Date</h3>
                <p>01 September 2026</p>
            </div>

            <div class="info-box">
                <h3>Deadline</h3>
                <p>30 November 2026</p>
            </div>

            <div class="info-box">
                <h3>Members</h3>
                <p>4 Students</p>
            </div>

        </div>

        <div class="project-progress">

            <div class="project-progress-header">
                <strong>Project Progress</strong>
                <span>50%</span>
            </div>

            <div class="progress-bar">
                <div class="progress-value"></div>
            </div>

            <p class="project-description">
                The TeamBox project is currently at 50% completion.
                The requirement analysis and project proposal have been
                finished. The next phase focuses on system interface
                design, database structure, and core feature development.
            </p>

        </div>

    </section>

    <section class="section-grid">

        <div class="content-box">

            <h2>Team Members</h2>

            <div class="member-item">
                <div>
                    <h3>Ali bin Ahmad</h3>
                    <p>Student ID: S1001</p>
                </div>
                <span class="member-role leader-role">Leader</span>
            </div>

            <div class="member-item">
                <div>
                    <h3>Mei Ling Tan</h3>
                    <p>Student ID: S1002</p>
                </div>
                <span class="member-role">Member</span>
            </div>

            <div class="member-item">
                <div>
                    <h3>Kumar Raj</h3>
                    <p>Student ID: S1003</p>
                </div>
                <span class="member-role">Member</span>
            </div>

            <div class="member-item">
                <div>
                    <h3>Sarah Lee</h3>
                    <p>Student ID: S1004</p>
                </div>
                <span class="member-role">Member</span>
            </div>

        </div>

        <!-- Project Actions (text links) -->

        <div class="content-box">

            <h2>Project Actions</h2>

            <div class="quick-links">

                <a href="student_task.php" class="text-link">
                    View Project Tasks
                </a>

                <a href="my_team.php" class="text-link">
                    View Team Details
                </a>

                <a href="student_files.php" class="text-link">
                    Open Project Files
                </a>

                <a href="student_chat.php" class="text-link">
                    Open Group Chat
                </a>

                <a href="student_index.php" class="text-link">
                    Back to Dashboard
                </a>

            </div>

        </div>

    </section>

</main>

</body>
</html>
