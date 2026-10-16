<?php
session_start();

// if (!isset($_SESSION['studentID'])) {
//     header('Location: student_login.php');
//     exit;
// }

$currentUser = "Student A";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
    >

    <title>My Tasks | TeamBox</title>

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

        /* Top Navigation Bar */

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

        .nav-link.active {
            color: white;
            font-weight: bold;
            border-bottom: 2px solid white;
        }

        .logout-link {
            color: #ff8888;
        }

        .logout-link:hover {
            color: #ff4444;
        }

        /* Main Content */

        .main-content {
            width: 90%;
            max-width: 1250px;
            margin: 40px auto;
        }

        .page-heading {
            padding: 30px;
            margin-bottom: 25px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .page-heading h1 {
            margin-bottom: 10px;
            font-size: 35px;
        }

        .page-heading p {
            color: #666;
            font-size: 17px;
            line-height: 1.6;
        }

        .role-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 18px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-leader {
            background: black;
            color: white;
        }

        .badge-teacher {
            background: #e5f0ff;
            color: #245889;
        }

        .badge-groupmate {
            background: #d9f5df;
            color: #176b2c;
        }

        /* Buttons */

        .create-task-btn {
            min-width: 160px;
            height: 48px;
            padding: 0 20px;

            background: black;
            color: white;
            border: 2px solid black;
            border-radius: 9px;

            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .create-task-btn:hover {
            background: #333;
        }

        .create-task-btn:active {
            transform: scale(0.98);
        }

        /* Summary Cards */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            min-height: 145px;
            padding: 24px;

            background: white;
            border-left: 5px solid black;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);

            transition: transform 0.3s, box-shadow 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .summary-card h2 {
            margin-bottom: 15px;
            color: #666;
            font-size: 16px;
        }

        .summary-number {
            color: black;
            font-size: 38px;
            font-weight: bold;
        }

        .summary-description {
            margin-top: 7px;
            color: #777;
            font-size: 13px;
        }

        /* Log My Contribution */

        .log-contribution-section {
            padding: 28px;
            margin-bottom: 25px;

            background: white;
            border-radius: 15px;
            border-left: 6px solid #245889;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .log-contribution-section h2 {
            padding-bottom: 15px;
            margin-bottom: 22px;

            border-bottom: 2px solid #eeeeee;
            font-size: 23px;
        }

        .log-contribution-section .section-subtitle {
            margin-top: -10px;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .voluntary-badge {
            display: inline-block;
            padding: 5px 10px;
            margin-left: 8px;

            background: #e5f0ff;
            color: #245889;
            border-radius: 15px;

            font-size: 11px;
            font-weight: bold;
        }

        /* Form Elements */

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;

            color: #333;
            font-size: 14px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0 13px;

            background: white;
            color: #222;
            border: 2px solid #cccccc;
            border-radius: 8px;

            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input,
        .form-group select {
            height: 47px;
        }

        .form-group textarea {
            min-height: 100px;
            padding-top: 12px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: black;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .cancel-btn {
            min-width: 130px;
            height: 46px;
            padding: 0 16px;

            background: white;
            color: black;
            border: 2px solid black;
            border-radius: 8px;

            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .cancel-btn:hover {
            background: black;
            color: white;
        }

        .submit-btn {
            min-width: 150px;
            height: 46px;
            padding: 0 16px;

            background: black;
            color: white;
            border: 2px solid black;
            border-radius: 8px;

            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: #333;
        }

        /* Task Section */

        .task-section {
            padding: 28px;
            margin-bottom: 25px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .section-heading {
            padding-bottom: 18px;
            margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .section-heading h2 {
            font-size: 24px;
        }

        .toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-input,
        .filter-select {
            height: 44px;
            padding: 0 13px;

            background: white;
            color: #222;
            border: 2px solid #cccccc;
            border-radius: 8px;

            font-size: 14px;
            outline: none;
        }

        .search-input {
            width: 220px;
        }

        .filter-select {
            min-width: 145px;
            cursor: pointer;
        }

        .search-input:focus,
        .filter-select:focus {
            border-color: black;
        }

        /* Task Cards */

        .task-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .task-card {
            padding: 22px;

            background: #fafafa;
            border: 1px solid #dddddd;
            border-left: 5px solid black;
            border-radius: 12px;

            transition: transform 0.3s, box-shadow 0.3s;
        }

        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }

        .task-top {
            margin-bottom: 16px;

            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .task-title {
            flex: 1;
        }

        .task-title h3 {
            margin-bottom: 7px;
            font-size: 20px;
        }

        .task-title p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .task-status {
            padding: 7px 12px;
            border-radius: 20px;

            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-pending {
            background: #eeeeee;
            color: #555;
        }

        .status-progress {
            background: #fff1c2;
            color: #765900;
        }

        .status-completed {
            background: #d9f5df;
            color: #176b2c;
        }

        .task-details {
            padding-top: 16px;
            border-top: 1px solid #e4e4e4;

            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .detail-box h4 {
            margin-bottom: 6px;
            color: #777;
            font-size: 13px;
        }

        .detail-box p {
            color: #222;
            font-size: 14px;
            font-weight: bold;
        }

        .priority {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 15px;

            font-size: 12px;
            font-weight: bold;
        }

        .priority-high {
            background: #ffe1e1;
            color: #a21b1b;
        }

        .priority-medium {
            background: #fff1c2;
            color: #765900;
        }

        .priority-low {
            background: #e5f0ff;
            color: #245889;
        }

        /* My Progress Selector */

        .my-progress {
            margin-top: 18px;
            padding: 16px;

            background: white;
            border: 2px dashed #cccccc;
            border-radius: 10px;
        }

        .my-progress h4 {
            margin-bottom: 12px;
            font-size: 14px;
            color: #555;
        }

        .progress-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .progress-btn {
            min-width: 120px;
            height: 40px;
            padding: 0 14px;

            background: white;
            color: #555;
            border: 2px solid #cccccc;
            border-radius: 8px;

            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .progress-btn:hover {
            border-color: black;
            color: black;
        }

        .progress-btn.active-pending {
            background: #eeeeee;
            color: #333;
            border-color: #999999;
        }

        .progress-btn.active-progress {
            background: #fff1c2;
            color: #765900;
            border-color: #e0c060;
        }

        .progress-btn.active-completed {
            background: #d9f5df;
            color: #176b2c;
            border-color: #8bc79a;
        }

        /* Task Actions */

        .task-actions {
            margin-top: 18px;

            display: flex;
            justify-content: flex-end;
            gap: 18px;
        }

        .action-link {
            color: black;
            font-size: 15px;
            text-decoration: underline;

            transition: color 0.3s;
        }

        .action-link:hover {
            color: #555555;
        }

        .action-link.disabled {
            color: #aaaaaa;
            cursor: not-allowed;
            text-decoration: none;
        }

        /* Team Progress */

        .team-progress-section {
            padding: 28px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .team-progress-section h2 {
            padding-bottom: 15px;
            margin-bottom: 22px;

            border-bottom: 2px solid #eeeeee;
            font-size: 23px;
        }

        .progress-info {
            margin-bottom: 12px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-info strong {
            font-size: 17px;
        }

        .progress-info span {
            font-size: 17px;
            font-weight: bold;
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

        .progress-note {
            margin-top: 12px;
            color: #777;
            font-size: 14px;
        }

        .task-note {
            padding: 18px;
            margin-top: 22px;

            background: #f7f7f7;
            border-left: 5px solid black;
            border-radius: 8px;

            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        /* =========================
           Modal (Create Task)
        ========================= */

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;

            background: rgba(0, 0, 0, 0.55);

            display: none;
            justify-content: center;
            align-items: center;

            padding: 20px;
            z-index: 1000;

            overflow-y: auto;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            width: 100%;
            max-width: 700px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);

            max-height: 90vh;
            overflow-y: auto;

            animation: modalFadeIn 0.25s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 22px 28px;

            border-bottom: 2px solid #eeeeee;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 22px;
        }

        .modal-close {
            width: 38px;
            height: 38px;

            background: white;
            color: #555;
            border: 2px solid #cccccc;
            border-radius: 8px;

            font-size: 20px;
            font-weight: bold;
            cursor: pointer;

            display: flex;
            justify-content: center;
            align-items: center;

            transition: 0.3s;
        }

        .modal-close:hover {
            background: #c62828;
            color: white;
            border-color: #c62828;
        }

        .modal-body {
            padding: 28px;
        }

        .modal-footer {
            padding: 20px 28px;

            border-top: 2px solid #eeeeee;

            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Responsive */

        @media (max-width: 1000px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .toolbar {
                width: 100%;
            }

            .search-input {
                flex: 1;
                width: auto;
            }

            .task-details {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 650px) {
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

            .main-content {
                width: 94%;
                margin: 25px auto;
            }

            .page-heading h1 {
                font-size: 28px;
            }

            .page-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .create-task-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .search-input,
            .filter-select {
                width: 100%;
            }

            .task-top {
                flex-direction: column;
            }

            .task-details {
                grid-template-columns: 1fr;
            }

            .task-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-actions,
            .modal-footer {
                flex-direction: column;
            }

            .cancel-btn,
            .submit-btn {
                width: 100%;
            }

            .progress-options {
                flex-direction: column;
            }

            .progress-btn {
                width: 100%;
            }

            .modal-box {
                max-height: 95vh;
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

    <div class="function-menu">

        <a href="student_index.php" class="nav-link">
            Dashboard
        </a>
        <a href="student_create_group.php" class="nav-link">
            Create Group
        </a>

        <a href="my_team.php" class="nav-link">
            My Team
        </a>

        <a href="student_task.php" class="nav-link active">
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

        <a href="logout.php" class="nav-link logout-link">
            Log Out
        </a>

    </div>

</nav>

<!-- Main Content -->

<main class="main-content">

    <!-- Page Heading -->

    <section class="page-heading">

        <div>
            <div class="role-badges">
                <span class="role-badge badge-leader">Team Leader</span>
                <span class="role-badge badge-teacher">Teacher</span>
                <span class="role-badge badge-groupmate">Groupmate</span>
            </div>

            <h1>My Tasks</h1>

            <p>
                Create tasks, assign them to team members, update your
                own progress, and log voluntary contributions.
            </p>
        </div>

        <button
                type="button"
                class="create-task-btn"
                onclick="openCreateTaskModal()"
        >
            + Create Task
        </button>

    </section>

    <!-- Task Summary -->

    <section class="summary-grid">

        <div class="summary-card">
            <h2>Total Assigned Tasks</h2>
            <p class="summary-number">6</p>
            <p class="summary-description">All tasks in this team</p>
        </div>

        <div class="summary-card">
            <h2>Pending Tasks</h2>
            <p class="summary-number">2</p>
            <p class="summary-description">Tasks waiting to be started</p>
        </div>

        <div class="summary-card">
            <h2>In Progress</h2>
            <p class="summary-number">2</p>
            <p class="summary-description">Tasks currently being completed</p>
        </div>

        <div class="summary-card">
            <h2>Completed Tasks</h2>
            <p class="summary-number">2</p>
            <p class="summary-description">Tasks successfully completed</p>
        </div>

    </section>

    <!-- Log My Contribution -->

    <section class="log-contribution-section">

        <h2>
            Log My Contribution
            <span class="voluntary-badge">Voluntary</span>
        </h2>

        <p class="section-subtitle">
            Did something useful for the team? Record it here. No deadline
            needed. Your entry will be counted towards your contribution score.
        </p>

        <form action="#" method="post">

            <div class="form-group">
                <label for="contributionTitle">
                    What did you do?
                </label>

                <input
                        type="text"
                        id="contributionTitle"
                        name="contributionTitle"
                        placeholder="e.g. Fixed login bug, wrote meeting minutes, helped teammate with UI"
                        required
                >
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="contributionType">Type</label>

                    <select id="contributionType" name="contributionType" required>
                        <option value="">-- Select Type --</option>
                        <option value="Development">Development</option>
                        <option value="Documentation">Documentation</option>
                        <option value="Design">Design</option>
                        <option value="Testing">Testing</option>
                        <option value="Discussion">Discussion</option>
                        <option value="Helping Teammate">Helping Teammate</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contributionDate">Date</label>

                    <input
                            type="date"
                            id="contributionDate"
                            name="contributionDate"
                            required
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="contributionNotes">
                    Notes (optional)
                </label>

                <textarea
                        id="contributionNotes"
                        name="contributionNotes"
                        placeholder="Any extra details you want to add"
                ></textarea>
            </div>

            <div class="form-actions">

                <button type="reset" class="cancel-btn">
                    Clear
                </button>

                <button type="submit" class="submit-btn">
                    Log Contribution
                </button>

            </div>

        </form>

    </section>

    <!-- Task List -->

    <section class="task-section">

        <div class="section-heading">

            <h2>Assigned Task List</h2>

            <div class="toolbar">

                <input
                        type="search"
                        class="search-input"
                        placeholder="Search tasks"
                        aria-label="Search tasks"
                >

                <select
                        class="filter-select"
                        aria-label="Filter task status"
                >
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in-progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>

            </div>

        </div>

        <div class="task-list">

            <!-- Task 1 -->

            <article class="task-card">

                <div class="task-top">

                    <div class="task-title">
                        <h3>Develop Student Dashboard</h3>
                        <p>
                            Create the student dashboard interface with project,
                            task, team progress and notification information.
                        </p>
                    </div>

                    <span class="task-status status-progress">
                        In Progress
                    </span>

                </div>

                <div class="task-details">

                    <div class="detail-box">
                        <h4>Assigned To</h4>
                        <p>Student B</p>
                    </div>

                    <div class="detail-box">
                        <h4>Created By</h4>
                        <p>Student A (Leader)</p>
                    </div>

                    <div class="detail-box">
                        <h4>Deadline</h4>
                        <p>10 October 2026</p>
                    </div>

                    <div class="detail-box">
                        <h4>Priority</h4>
                        <span class="priority priority-high">High</span>
                    </div>

                </div>

                <div class="my-progress">

                    <h4>Mark My Progress (Student A)</h4>

                    <form action="#" method="post" class="progress-options">

                        <button type="submit" name="myProgress" value="pending" class="progress-btn">
                            Not Started
                        </button>

                        <button type="submit" name="myProgress" value="in_progress" class="progress-btn active-progress">
                            Working On It
                        </button>

                        <button type="submit" name="myProgress" value="completed" class="progress-btn">
                            I Completed It
                        </button>

                    </form>

                </div>

                <div class="task-actions">

                    <a href="#" class="action-link">
                        View Details
                    </a>

                    <a href="#" class="action-link">
                        Edit Task
                    </a>

                </div>

            </article>

            <!-- Task 2 -->

            <article class="task-card">

                <div class="task-top">

                    <div class="task-title">
                        <h3>Create My Team Page</h3>
                        <p>
                            Develop a page showing the team leader, members,
                            team progress, assigned tasks and recent activities.
                        </p>
                    </div>

                    <span class="task-status status-progress">
                        In Progress
                    </span>

                </div>

                <div class="task-details">

                    <div class="detail-box">
                        <h4>Assigned To</h4>
                        <p>Student C</p>
                    </div>

                    <div class="detail-box">
                        <h4>Created By</h4>
                        <p>Student B (Groupmate)</p>
                    </div>

                    <div class="detail-box">
                        <h4>Deadline</h4>
                        <p>15 October 2026</p>
                    </div>

                    <div class="detail-box">
                        <h4>Priority</h4>
                        <span class="priority priority-medium">Medium</span>
                    </div>

                </div>

                <div class="my-progress">

                    <h4>Mark My Progress (Student A)</h4>

                    <form action="#" method="post" class="progress-options">

                        <button type="submit" name="myProgress" value="pending" class="progress-btn">
                            Not Started
                        </button>

                        <button type="submit" name="myProgress" value="in_progress" class="progress-btn active-progress">
                            Working On It
                        </button>

                        <button type="submit" name="myProgress" value="completed" class="progress-btn">
                            I Completed It
                        </button>

                    </form>

                </div>

                <div class="task-actions">

                    <a href="#" class="action-link">
                        View Details
                    </a>

                    <a href="#" class="action-link">
                        Edit Task
                    </a>

                </div>

            </article>

            <!-- Task 3 -->

            <article class="task-card">

                <div class="task-top">

                    <div class="task-title">
                        <h3>Prepare System Testing Document</h3>
                        <p>
                            Prepare test cases for the login, project management
                            and task management functions.
                        </p>
                    </div>

                    <span class="task-status status-pending">
                        Pending
                    </span>

                </div>

                <div class="task-details">

                    <div class="detail-box">
                        <h4>Assigned To</h4>
                        <p>Student D</p>
                    </div>

                    <div class="detail-box">
                        <h4>Created By</h4>
                        <p>Mr. Tan (Teacher)</p>
                    </div>

                    <div class="detail-box">
                        <h4>Deadline</h4>
                        <p>20 October 2026</p>
                    </div>

                    <div class="detail-box">
                        <h4>Priority</h4>
                        <span class="priority priority-medium">Medium</span>
                    </div>

                </div>

                <div class="my-progress">

                    <h4>Mark My Progress (Student A)</h4>

                    <form action="#" method="post" class="progress-options">

                        <button type="submit" name="myProgress" value="pending" class="progress-btn active-pending">
                            Not Started
                        </button>

                        <button type="submit" name="myProgress" value="in_progress" class="progress-btn">
                            Working On It
                        </button>

                        <button type="submit" name="myProgress" value="completed" class="progress-btn">
                            I Completed It
                        </button>

                    </form>

                </div>

                <div class="task-actions">

                    <a href="#" class="action-link">
                        View Details
                    </a>

                    <a href="#" class="action-link">
                        Edit Task
                    </a>

                </div>

            </article>

            <!-- Task 4 -->

            <article class="task-card">

                <div class="task-top">

                    <div class="task-title">
                        <h3>Create Student Login Page</h3>
                        <p>
                            Design the Student ID, password and human
                            verification login interface.
                        </p>
                    </div>

                    <span class="task-status status-completed">
                        Completed
                    </span>

                </div>

                <div class="task-details">

                    <div class="detail-box">
                        <h4>Assigned To</h4>
                        <p>Student A</p>
                    </div>

                    <div class="detail-box">
                        <h4>Created By</h4>
                        <p>Student A (Leader)</p>
                    </div>

                    <div class="detail-box">
                        <h4>Completed Date</h4>
                        <p>22 September 2026</p>
                    </div>

                    <div class="detail-box">
                        <h4>Priority</h4>
                        <span class="priority priority-high">High</span>
                    </div>

                </div>

                <div class="my-progress">

                    <h4>Mark My Progress (Student A)</h4>

                    <form action="#" method="post" class="progress-options">

                        <button type="submit" name="myProgress" value="pending" class="progress-btn">
                            Not Started
                        </button>

                        <button type="submit" name="myProgress" value="in_progress" class="progress-btn">
                            Working On It
                        </button>

                        <button type="submit" name="myProgress" value="completed" class="progress-btn active-completed">
                            I Completed It
                        </button>

                    </form>

                </div>

                <div class="task-actions">

                    <a href="#" class="action-link">
                        View Details
                    </a>

                    <span class="action-link disabled">
                        Task Completed
                    </span>

                </div>

            </article>

        </div>

    </section>

    <!-- Team Progress -->

    <section class="team-progress-section">

        <h2>Team Progress</h2>

        <div class="progress-info">
            <strong>Overall Completion</strong>
            <span>50% (2 / 4 tasks)</span>
        </div>

        <div class="progress-bar">
            <div class="progress-value"></div>
        </div>

        <p class="progress-note">
            The progress bar updates automatically when tasks are marked
            as completed by assigned members.
        </p>

    </section>

    <div class="task-note">
        Teachers, team leaders and groupmates can all create and assign
        tasks. Each member can mark their own task progress using the
        buttons above. You can also log voluntary contributions that are
        not tied to a task — those will still count towards your
        contribution score.
    </div>

</main>

<!-- =========================
     Create Task Modal
========================= -->

<div class="modal-overlay" id="createTaskModal">

    <div class="modal-box">

        <div class="modal-header">

            <h2>Create New Task</h2>

            <button
                    type="button"
                    class="modal-close"
                    onclick="closeCreateTaskModal()"
            >
                ✕
            </button>

        </div>

        <form action="#" method="post">

            <div class="modal-body">

                <div class="form-group">
                    <label for="taskTitle">Task Title</label>

                    <input
                            type="text"
                            id="taskTitle"
                            name="taskTitle"
                            placeholder="e.g. Complete Project Proposal Draft"
                            required
                    >
                </div>

                <div class="form-group">
                    <label for="taskDescription">Description</label>

                    <textarea
                            id="taskDescription"
                            name="taskDescription"
                            placeholder="Describe what needs to be done"
                            required
                    ></textarea>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="assignedTo">Assign To</label>

                        <select id="assignedTo" name="assignedTo" required>
                            <option value="">-- Select Member --</option>
                            <option value="self">Myself (<?php echo htmlspecialchars($currentUser); ?>)</option>
                            <option value="A">Student A</option>
                            <option value="B">Student B</option>
                            <option value="C">Student C</option>
                            <option value="D">Student D</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="taskDeadline">Deadline</label>

                        <input
                                type="date"
                                id="taskDeadline"
                                name="taskDeadline"
                                required
                        >
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="taskPriority">Priority</label>

                        <select id="taskPriority" name="taskPriority" required>
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="taskCategory">Category</label>

                        <select id="taskCategory" name="taskCategory">
                            <option value="Development">Development</option>
                            <option value="Documentation">Documentation</option>
                            <option value="Design">Design</option>
                            <option value="Testing">Testing</option>
                        </select>
                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                        type="button"
                        class="cancel-btn"
                        onclick="closeCreateTaskModal()"
                >
                    Cancel
                </button>

                <button type="submit" class="submit-btn">
                    Create Task
                </button>

            </div>

        </form>

    </div>

</div>

<!-- =========================
     JavaScript
========================= -->

<script>
    function openCreateTaskModal() {
        document.getElementById('createTaskModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeCreateTaskModal() {
        document.getElementById('createTaskModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // 点遮罩关闭
    document.getElementById('createTaskModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeCreateTaskModal();
        }
    });

    // 按 ESC 关闭
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeCreateTaskModal();
        }
    });
</script>

</body>
</html>