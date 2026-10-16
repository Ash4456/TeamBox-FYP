<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Contribution Analysis | TeamBox</title>

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

        .export-btn {
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

        .export-btn:hover {
            background: #333;
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

            transition:
                    transform 0.3s,
                    box-shadow 0.3s;
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
            font-size: 36px;
            font-weight: bold;
        }

        .summary-description {
            margin-top: 7px;
            color: #777;
            font-size: 13px;
        }

        /* Content Box */

        .content-box {
            padding: 28px;
            margin-bottom: 25px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .content-box h2 {
            padding-bottom: 15px;
            margin-bottom: 22px;

            border-bottom: 2px solid #eeeeee;
            font-size: 23px;
        }

        /* =========================
           Pie Chart Section
        ========================= */

        .chart-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 50px;
            flex-wrap: wrap;
        }

        .pie-chart {
            width: 280px;
            height: 280px;
            flex-shrink: 0;
        }

        /* Legend */

        .chart-legend {
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-width: 260px;
        }

        .legend-item {
            padding: 12px 14px;

            background: #fafafa;
            border: 1px solid #dddddd;
            border-radius: 10px;

            display: flex;
            align-items: center;
            gap: 12px;
        }

        .legend-color {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .legend-info {
            flex: 1;
        }

        .legend-info h3 {
            font-size: 15px;
            margin-bottom: 3px;
        }

        .legend-info p {
            color: #777;
            font-size: 13px;
        }

        .legend-percent {
            font-size: 16px;
            font-weight: bold;
            white-space: nowrap;
        }

        /* Colors */

        .color-a { background: #000000; }
        .color-b { background: #555555; }
        .color-c { background: #999999; }
        .color-d { background: #cccccc; }

        /* =========================
           Member Contribution Rows
        ========================= */

        .member-contrib {
            padding: 18px 0;
            border-bottom: 1px solid #eeeeee;
        }

        .member-contrib:last-child {
            border-bottom: none;
        }

        .member-header {
            margin-bottom: 10px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .member-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .member-avatar {
            width: 44px;
            height: 44px;
            flex-shrink: 0;

            background: black;
            color: white;
            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 15px;
            font-weight: bold;
        }

        .member-info h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        .member-info p {
            color: #777;
            font-size: 13px;
        }

        .member-score {
            font-size: 22px;
            font-weight: bold;
            white-space: nowrap;
        }

        .contrib-bar {
            width: 100%;
            height: 14px;

            background: #dddddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .contrib-value {
            height: 100%;
            background: black;
            border-radius: 10px;
        }

        .contrib-details {
            margin-top: 8px;

            display: flex;
            justify-content: space-between;
            color: #777;
            font-size: 12px;
        }

        /* =========================
           Weight Breakdown
        ========================= */

        .weight-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .weight-card {
            padding: 20px;

            background: #fafafa;
            border: 2px solid #dddddd;
            border-radius: 12px;

            text-align: center;
        }

        .weight-percent {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .weight-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .weight-desc {
            color: #777;
            font-size: 13px;
            line-height: 1.5;
        }

        /* =========================
           Activity Log
        ========================= */

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eeeeee;

            display: flex;
            gap: 14px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-dot {
            width: 13px;
            height: 13px;
            margin-top: 5px;
            flex-shrink: 0;

            background: black;
            border-radius: 50%;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h3 {
            margin-bottom: 5px;
            font-size: 15px;
        }

        .activity-content p {
            color: #777;
            font-size: 13px;
            line-height: 1.5;
        }

        .activity-points {
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        /* Info Note */

        .info-note {
            padding: 18px;

            background: #f7f7f7;
            border-left: 5px solid black;
            border-radius: 8px;

            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Responsive */

        @media (max-width: 1000px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .weight-grid {
                grid-template-columns: 1fr;
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

            .export-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .chart-wrapper {
                gap: 30px;
            }

            .pie-chart {
                width: 220px;
                height: 220px;
            }

            .chart-legend {
                min-width: 100%;
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


        <a href="my_team.php" class="nav-link">
            My Team
        </a>

        <a href="student_create_group.php" class="nav-link">
            Create Group
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
        <a href="student_contribution.php" class="nav-link active">
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
            <h1>Contribution Analysis</h1>

            <p>
                Track each member's participation using a weighted
                algorithm. Contribution scores are based on task
                completion, file updates and team discussion activity.
            </p>
        </div>

        <button type="button" class="export-btn">
            Export Report (PDF)
        </button>

    </section>

    <!-- Summary Cards -->

    <section class="summary-grid">

        <div class="summary-card">
            <h2>Total Team Score</h2>
            <p class="summary-number">340</p>
            <p class="summary-description">Combined contribution points</p>
        </div>

        <div class="summary-card">
            <h2>Average Score</h2>
            <p class="summary-number">85</p>
            <p class="summary-description">Average per member</p>
        </div>

        <div class="summary-card">
            <h2>Top Contributor</h2>
            <p class="summary-number">105</p>
            <p class="summary-description">Student A (Leader)</p>
        </div>

        <div class="summary-card">
            <h2>Team Balance</h2>
            <p class="summary-number">Good</p>
            <p class="summary-description">Distribution is fairly balanced</p>
        </div>

    </section>

    <!-- Pie Chart -->

    <section class="content-box">

        <h2>Contribution Distribution</h2>

        <div class="chart-wrapper">

            <!-- SVG Donut Chart -->
            <svg class="pie-chart" viewBox="0 0 42 42">
                <!-- Background circle -->
                <circle
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    stroke="#eeeeee"
                    stroke-width="8"
                ></circle>

                <!-- Student A: 31% -->
                <circle
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    stroke="#000000"
                    stroke-width="8"
                    stroke-dasharray="31 69"
                    stroke-dashoffset="25"
                ></circle>

                <!-- Student B: 26% -->
                <circle
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    stroke="#555555"
                    stroke-width="8"
                    stroke-dasharray="26 74"
                    stroke-dashoffset="-6"
                ></circle>

                <!-- Student C: 24% -->
                <circle
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    stroke="#999999"
                    stroke-width="8"
                    stroke-dasharray="24 76"
                    stroke-dashoffset="-32"
                ></circle>

                <!-- Student D: 19% -->
                <circle
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="transparent"
                    stroke="#cccccc"
                    stroke-width="8"
                    stroke-dasharray="19 81"
                    stroke-dashoffset="-56"
                ></circle>

                <!-- Center text -->
                <text
                    x="21"
                    y="20"
                    text-anchor="middle"
                    font-size="4"
                    font-weight="bold"
                    fill="#222"
                >
                    340
                </text>
                <text
                    x="21"
                    y="24.5"
                    text-anchor="middle"
                    font-size="2.2"
                    fill="#777"
                >
                    Total Points
                </text>
            </svg>

            <!-- Legend -->
            <div class="chart-legend">

                <div class="legend-item">
                    <div class="legend-color color-a"></div>

                    <div class="legend-info">
                        <h3>Student A (Leader)</h3>
                        <p>105 points</p>
                    </div>

                    <div class="legend-percent">31%</div>
                </div>

                <div class="legend-item">
                    <div class="legend-color color-b"></div>

                    <div class="legend-info">
                        <h3>Student B</h3>
                        <p>90 points</p>
                    </div>

                    <div class="legend-percent">26%</div>
                </div>

                <div class="legend-item">
                    <div class="legend-color color-c"></div>

                    <div class="legend-info">
                        <h3>Student C</h3>
                        <p>80 points</p>
                    </div>

                    <div class="legend-percent">24%</div>
                </div>

                <div class="legend-item">
                    <div class="legend-color color-d"></div>

                    <div class="legend-info">
                        <h3>Student D</h3>
                        <p>65 points</p>
                    </div>

                    <div class="legend-percent">19%</div>
                </div>

            </div>

        </div>

    </section>

    <!-- Member Contribution Bars -->

    <section class="content-box">

        <h2>Member Contribution Details</h2>

        <!-- Student A -->

        <div class="member-contrib">

            <div class="member-header">

                <div class="member-left">
                    <div class="member-avatar">SA</div>

                    <div class="member-info">
                        <h3>Student A (Leader)</h3>
                        <p>Student ID: 250000001</p>
                    </div>
                </div>

                <div class="member-score">105 pts</div>

            </div>

            <div class="contrib-bar">
                <div class="contrib-value" style="width: 100%;"></div>
            </div>

            <div class="contrib-details">
                <span>Tasks: 40 pts</span>
                <span>Files: 35 pts</span>
                <span>Discussion: 30 pts</span>
            </div>

        </div>

        <!-- Student B -->

        <div class="member-contrib">

            <div class="member-header">

                <div class="member-left">
                    <div class="member-avatar">SB</div>

                    <div class="member-info">
                        <h3>Student B</h3>
                        <p>Student ID: 250000002</p>
                    </div>
                </div>

                <div class="member-score">90 pts</div>

            </div>

            <div class="contrib-bar">
                <div class="contrib-value" style="width: 85%;"></div>
            </div>

            <div class="contrib-details">
                <span>Tasks: 35 pts</span>
                <span>Files: 30 pts</span>
                <span>Discussion: 25 pts</span>
            </div>

        </div>

        <!-- Student C -->

        <div class="member-contrib">

            <div class="member-header">

                <div class="member-left">
                    <div class="member-avatar">SC</div>

                    <div class="member-info">
                        <h3>Student C</h3>
                        <p>Student ID: 250000003</p>
                    </div>
                </div>

                <div class="member-score">80 pts</div>

            </div>

            <div class="contrib-bar">
                <div class="contrib-value" style="width: 76%;"></div>
            </div>

            <div class="contrib-details">
                <span>Tasks: 30 pts</span>
                <span>Files: 25 pts</span>
                <span>Discussion: 25 pts</span>
            </div>

        </div>

        <!-- Student D -->

        <div class="member-contrib">

            <div class="member-header">

                <div class="member-left">
                    <div class="member-avatar">SD</div>

                    <div class="member-info">
                        <h3>Student D</h3>
                        <p>Student ID: 250000004</p>
                    </div>
                </div>

                <div class="member-score">65 pts</div>

            </div>

            <div class="contrib-bar">
                <div class="contrib-value" style="width: 62%;"></div>
            </div>

            <div class="contrib-details">
                <span>Tasks: 25 pts</span>
                <span>Files: 20 pts</span>
                <span>Discussion: 20 pts</span>
            </div>

        </div>

    </section>

    <!-- Weight Breakdown -->

    <section class="content-box">

        <h2>Contribution Weights</h2>

        <div class="weight-grid">

            <div class="weight-card">
                <p class="weight-percent">40%</p>
                <p class="weight-title">Task Completion</p>
                <p class="weight-desc">
                    Weighted by number of tasks completed,
                    difficulty and timeliness before deadline.
                </p>
            </div>

            <div class="weight-card">
                <p class="weight-percent">35%</p>
                <p class="weight-title">File / Document Updates</p>
                <p class="weight-desc">
                    Frequency of file uploads, document edits
                    and version contributions in the shared workspace.
                </p>
            </div>

            <div class="weight-card">
                <p class="weight-percent">25%</p>
                <p class="weight-title">Discussion &amp; Confirmations</p>
                <p class="weight-desc">
                    Participation in group chat, comments,
                    task confirmations and team voting.
                </p>
            </div>

        </div>

    </section>

    <!-- Recent Contribution Activity -->

    <section class="content-box">

        <h2>Recent Contribution Activity</h2>

        <div class="activity-item">
            <div class="activity-dot"></div>

            <div class="activity-content">
                <h3>Student A completed a task</h3>
                <p>Develop Student Dashboard — marked as completed on time.</p>
            </div>

            <div class="activity-points">+10 pts</div>
        </div>

        <div class="activity-item">
            <div class="activity-dot"></div>

            <div class="activity-content">
                <h3>Student B uploaded a file</h3>
                <p>Project_Proposal.pdf — Version 3 uploaded to shared workspace.</p>
            </div>

            <div class="activity-points">+8 pts</div>
        </div>

        <div class="activity-item">
            <div class="activity-dot"></div>

            <div class="activity-content">
                <h3>Student C posted a message</h3>
                <p>Posted a meeting reminder in the group chat.</p>
            </div>

            <div class="activity-points">+3 pts</div>
        </div>

        <div class="activity-item">
            <div class="activity-dot"></div>

            <div class="activity-content">
                <h3>Student D confirmed a task</h3>
                <p>Marked "Prepare System Testing Document" as started.</p>
            </div>

            <div class="activity-points">+2 pts</div>
        </div>

        <div class="activity-item">
            <div class="activity-dot"></div>

            <div class="activity-content">
                <h3>Student A edited a shared document</h3>
                <p>Updated the Requirement Analysis document with new content.</p>
            </div>

            <div class="activity-points">+6 pts</div>
        </div>

    </section>

    <!-- Info Note -->

    <div class="info-note">
        The contribution score is calculated automatically by the
        system using a weighted algorithm. It records task completion,
        file updates and discussion activity to support fair evaluation
        by instructors. Scores are visible to the instructor by default.
    </div>

</main>

</body>
</html>