<?php
session_start();

require_once __DIR__ . '/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = (int) $_SESSION['userID'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Group Chat | TeamBox</title>

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

        /* =========================
           Main Content
        ========================= */

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

        /* =========================
           Chat Layout
        ========================= */

        .chat-container {
            height: 680px;

            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);

            display: grid;
            grid-template-columns: 290px 1fr;
        }

        /* Member Sidebar */

        .member-sidebar {
            background: #f7f7f7;
            border-right: 1px solid #dddddd;

            display: flex;
            flex-direction: column;
        }

        .sidebar-heading {
            padding: 23px;
            border-bottom: 1px solid #dddddd;
        }

        .sidebar-heading h2 {
            margin-bottom: 6px;
            font-size: 21px;
        }

        .sidebar-heading p {
            color: #777;
            font-size: 13px;
        }

        .member-list {
            padding: 15px;
            overflow-y: auto;
        }

        .member-item {
            width: 100%;
            padding: 13px;
            margin-bottom: 8px;

            background: transparent;
            border: none;
            border-radius: 9px;

            display: flex;
            align-items: center;
            gap: 12px;

            text-align: left;
            cursor: pointer;
            transition: 0.3s;
        }

        .member-item:hover {
            background: #e8e8e8;
        }

        .member-item.active {
            background: black;
            color: white;
        }

        .member-avatar {
            width: 42px;
            height: 42px;
            flex-shrink: 0;

            background: #333;
            color: white;
            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 14px;
            font-weight: bold;
        }

        .member-item.active .member-avatar {
            background: white;
            color: black;
        }

        .member-details {
            flex: 1;
        }

        .member-details h3 {
            margin-bottom: 4px;
            font-size: 15px;
        }

        .member-details p {
            color: #777;
            font-size: 12px;
        }

        .member-item.active .member-details p {
            color: #dddddd;
        }

        .member-status {
            width: 10px;
            height: 10px;
            flex-shrink: 0;

            background: #2e9d4d;
            border-radius: 50%;
        }

        .offline {
            background: #999999;
        }

        /* Chat Area */

        .chat-area {
            min-width: 0;

            display: flex;
            flex-direction: column;
        }

        .chat-header {
            min-height: 81px;
            padding: 18px 24px;

            border-bottom: 1px solid #dddddd;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .chat-title h2 {
            margin-bottom: 5px;
            font-size: 22px;
        }

        .chat-title p {
            color: #777;
            font-size: 13px;
        }

        /* Header button (kept as button) */

        .header-button {
            min-width: 120px;
            height: 42px;
            padding: 0 14px;

            background: white;
            color: black;
            border: 2px solid black;
            border-radius: 8px;

            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }

        .header-button:hover {
            background: black;
            color: white;
        }

        /* Messages */

        .message-area {
            flex: 1;
            padding: 24px;

            background: #fcfcfc;
            overflow-y: auto;
        }

        .date-separator {
            margin: 12px 0 24px;

            display: flex;
            align-items: center;
            gap: 13px;

            color: #888;
            font-size: 12px;
        }

        .date-separator::before,
        .date-separator::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #dddddd;
        }

        .message-row {
            margin-bottom: 20px;

            display: flex;
            align-items: flex-end;
            gap: 11px;
        }

        .message-row.my-message {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 38px;
            height: 38px;
            flex-shrink: 0;

            background: #333;
            color: white;
            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 12px;
            font-weight: bold;
        }

        .message-content {
            max-width: 65%;
        }

        .sender-name {
            margin-bottom: 6px;

            color: #666;
            font-size: 12px;
        }

        .my-message .sender-name {
            text-align: right;
        }

        .message-bubble {
            padding: 13px 16px;

            background: #eeeeee;
            color: #222;
            border-radius: 4px 15px 15px 15px;

            font-size: 14px;
            line-height: 1.55;
            word-break: break-word;
        }

        .my-message .message-bubble {
            background: black;
            color: white;
            border-radius: 15px 4px 15px 15px;
        }

        .message-time {
            display: block;
            margin-top: 6px;

            color: #888;
            font-size: 11px;
        }

        .my-message .message-time {
            text-align: right;
        }

        /* File Message */

        .shared-file {
            min-width: 260px;
            padding: 13px;

            background: white;
            border: 1px solid #cccccc;
            border-radius: 9px;

            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;

            background: black;
            color: white;
            border-radius: 7px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 11px;
            font-weight: bold;
        }

        .file-details {
            flex: 1;
            min-width: 0;
        }

        .file-details h4 {
            margin-bottom: 4px;
            overflow: hidden;

            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-details p {
            color: #777;
            font-size: 11px;
        }

        /* Download as text link */

        .download-link {
            color: black;
            font-size: 13px;
            text-decoration: underline;

            white-space: nowrap;

            transition: color 0.3s;
        }

        .download-link:hover {
            color: #555555;
        }

        /* Message Input */

        .message-input-area {
            padding: 17px 20px;

            background: white;
            border-top: 1px solid #dddddd;

            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .attachment-input {
            display: none;
        }

        .attachment-button {
            width: 46px;
            height: 46px;
            flex-shrink: 0;

            background: white;
            color: black;
            border: 2px solid black;
            border-radius: 8px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 24px;
            cursor: pointer;
            transition: 0.3s;
        }

        .attachment-button:hover {
            background: black;
            color: white;
        }

        .message-input {
            min-height: 46px;
            max-height: 110px;
            flex: 1;
            padding: 12px 14px;

            background: white;
            color: #222;
            border: 2px solid #cccccc;
            border-radius: 8px;

            font-size: 14px;
            line-height: 1.4;
            outline: none;
            resize: vertical;
        }

        .message-input:focus {
            border-color: black;
        }

        .send-button {
            min-width: 100px;
            height: 46px;
            padding: 0 16px;

            background: black;
            color: white;
            border: 2px solid black;
            border-radius: 8px;

            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .send-button:hover {
            background: #333;
        }

        .send-button:active {
            transform: scale(0.97);
        }

        /* Information Notice */

        .chat-note {
            padding: 18px;
            margin-top: 25px;

            background: white;
            border-left: 5px solid black;
            border-radius: 8px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);

            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Responsive Design */

        @media (max-width: 850px) {
            .chat-container {
                height: auto;
                grid-template-columns: 1fr;
            }

            .member-sidebar {
                border-right: none;
                border-bottom: 1px solid #dddddd;
            }

            .member-list {
                display: flex;
                overflow-x: auto;
            }

            .member-item {
                min-width: 190px;
            }

            .chat-area {
                height: 600px;
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

            .chat-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-button {
                width: 100%;
            }

            .message-content {
                max-width: 80%;
            }

            .shared-file {
                min-width: 210px;
            }

            .message-input-area {
                flex-wrap: wrap;
            }

            .message-input {
                min-width: calc(100% - 60px);
            }

            .send-button {
                width: 100%;
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

        <a href="my_team.php" class="nav-link">
            My Team
        </a>

        <a href="student_task.php" class="nav-link">
            Tasks
        </a>

        <a href="student_files.php" class="nav-link">
            Files
        </a>

        <a href="student_chat.php" class="nav-link active">
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

    <section class="page-heading">
        <h1>Group Chat</h1>
        <p>
            Communicate with your team members, discuss project tasks,
            share updates and exchange project files.
        </p>
    </section>

    <section class="chat-container">

        <!-- Member Sidebar -->

        <aside class="member-sidebar">

            <div class="sidebar-heading">
                <h2>Team A</h2>
                <p>4 members in this group</p>
            </div>

            <div class="member-list">

                <button type="button" class="member-item active">
                    <div class="member-avatar">GRP</div>

                    <div class="member-details">
                        <h3>Team A Group Chat</h3>
                        <p>Project discussion</p>
                    </div>

                    <span class="member-status"></span>
                </button>

                <button type="button" class="member-item">
                    <div class="member-avatar">SA</div>

                    <div class="member-details">
                        <h3>Student A</h3>
                        <p>Team Leader</p>
                    </div>

                    <span class="member-status"></span>
                </button>

                <button type="button" class="member-item">
                    <div class="member-avatar">SB</div>

                    <div class="member-details">
                        <h3>Student B</h3>
                        <p>Team Member</p>
                    </div>

                    <span class="member-status"></span>
                </button>

                <button type="button" class="member-item">
                    <div class="member-avatar">SC</div>

                    <div class="member-details">
                        <h3>Student C</h3>
                        <p>Team Member</p>
                    </div>

                    <span class="member-status offline"></span>
                </button>

                <button type="button" class="member-item">
                    <div class="member-avatar">SD</div>

                    <div class="member-details">
                        <h3>Student D</h3>
                        <p>Team Member</p>
                    </div>

                    <span class="member-status"></span>
                </button>

            </div>

        </aside>

        <!-- Chat Area -->

        <div class="chat-area">

            <header class="chat-header">

                <div class="chat-title">
                    <h2>Team A Group Chat</h2>
                    <p>3 members online</p>
                </div>

                <button type="button" class="header-button">
                    View Members
                </button>

            </header>

            <div class="message-area">

                <div class="date-separator">
                    23 September 2026
                </div>

                <!-- Student A -->

                <div class="message-row">
                    <div class="message-avatar">SA</div>

                    <div class="message-content">
                        <p class="sender-name">Student A · Team Leader</p>

                        <div class="message-bubble">
                            Please update your assigned task progress before
                            the team meeting.
                        </div>

                        <span class="message-time">09:15</span>
                    </div>
                </div>

                <!-- You -->

                <div class="message-row my-message">
                    <div class="message-content">
                        <p class="sender-name">You</p>

                        <div class="message-bubble">
                            I have completed the student login page and I am
                            currently developing the student dashboard.
                        </div>

                        <span class="message-time">09:18</span>
                    </div>
                </div>

                <!-- Student B -->

                <div class="message-row">
                    <div class="message-avatar">SB</div>

                    <div class="message-content">
                        <p class="sender-name">Student B</p>

                        <div class="message-bubble">
                            I have uploaded the latest project proposal to the
                            shared files page.
                        </div>

                        <span class="message-time">09:22</span>
                    </div>
                </div>

                <!-- Shared File -->

                <div class="message-row">
                    <div class="message-avatar">SB</div>

                    <div class="message-content">
                        <p class="sender-name">Student B</p>

                        <div class="shared-file">

                            <div class="file-icon">PDF</div>

                            <div class="file-details">
                                <h4>Project_Proposal.pdf</h4>
                                <p>2.4 MB · Version 3</p>
                            </div>

                            <a href="#" class="download-link">
                                Download
                            </a>

                        </div>

                        <span class="message-time">09:23</span>
                    </div>
                </div>

                <!-- Student D -->

                <div class="message-row">
                    <div class="message-avatar">SD</div>

                    <div class="message-content">
                        <p class="sender-name">Student D</p>

                        <div class="message-bubble">
                            What time will the next team meeting begin?
                        </div>

                        <span class="message-time">09:30</span>
                    </div>
                </div>

                <!-- Student A again -->

                <div class="message-row">
                    <div class="message-avatar">SA</div>

                    <div class="message-content">
                        <p class="sender-name">Student A · Team Leader</p>

                        <div class="message-bubble">
                            The meeting will begin at 3:30 PM today. Please
                            prepare your current progress and questions.
                        </div>

                        <span class="message-time">09:32</span>
                    </div>
                </div>

            </div>

            <!-- Message Input -->

            <form
                action="student_chat_process.php"
                method="post"
                enctype="multipart/form-data"
                class="message-input-area"
            >

                <label
                    for="chatFile"
                    class="attachment-button"
                    title="Attach file"
                >
                    +
                </label>

                <input
                    type="file"
                    id="chatFile"
                    name="chatFile"
                    class="attachment-input"
                >

                <textarea
                    name="message"
                    class="message-input"
                    placeholder="Type your message"
                    aria-label="Type your message"
                    required
                ></textarea>

                <button type="submit" class="send-button">
                    Send
                </button>

            </form>

        </div>

    </section>

    <div class="chat-note">
        This page currently demonstrates the group chat interface only.
        Sending messages, uploading files and displaying real-time updates
        will require PHP, a database and a real-time communication service.
    </div>

</main>

</body>
</html>