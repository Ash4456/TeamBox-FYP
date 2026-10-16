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

    <title>Shared Files | TeamBox</title>

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
           Summary Cards
        ========================= */

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
            font-size: 34px;
            font-weight: bold;
        }

        .summary-description {
            margin-top: 7px;
            color: #777;
            font-size: 13px;
        }

        /* =========================
           Upload Section
        ========================= */

        .upload-section {
            padding: 28px;
            margin-bottom: 25px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            padding-bottom: 15px;
            margin-bottom: 20px;

            border-bottom: 2px solid #eeeeee;
            font-size: 23px;
        }

        .upload-area {
            min-height: 170px;
            padding: 25px;

            background: #fafafa;
            border: 2px dashed #999999;
            border-radius: 12px;

            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;

            text-align: center;
        }

        .upload-icon {
            width: 55px;
            height: 55px;
            margin-bottom: 15px;

            background: black;
            color: white;
            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 27px;
            font-weight: bold;
        }

        .upload-area h3 {
            margin-bottom: 7px;
            font-size: 19px;
        }

        .upload-area p {
            margin-bottom: 16px;
            color: #777;
            font-size: 14px;
        }

        .file-input {
            display: none;
        }

        .upload-btn {
            min-width: 160px;
            height: 45px;
            padding: 0 18px;

            background: white;
            color: black;
            border: 2px solid black;
            border-radius: 8px;

            font-size: 15px;
            cursor: pointer;

            display: flex;
            justify-content: center;
            align-items: center;

            transition: 0.3s;
        }

        .upload-btn:hover {
            background: black;
            color: white;
        }

        /* =========================
           File List Section
        ========================= */

        .file-section {
            padding: 28px;

            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .file-section-header {
            padding-bottom: 18px;
            margin-bottom: 22px;
            border-bottom: 2px solid #eeeeee;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .file-section-header h2 {
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
            min-width: 150px;
            cursor: pointer;
        }

        .search-input:focus,
        .filter-select:focus {
            border-color: black;
        }

        .folder-navigation {
            padding: 14px 16px;
            margin-bottom: 20px;

            background: #f5f5f5;
            border-radius: 8px;

            color: #555;
            font-size: 14px;
        }

        .folder-navigation strong {
            color: black;
        }

        /* =========================
           File Items
        ========================= */

        .file-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .file-item {
            padding: 18px;

            background: #fafafa;
            border: 1px solid #dddddd;
            border-radius: 11px;

            display: grid;
            grid-template-columns: 55px 2fr 1fr 1fr auto;
            align-items: center;
            gap: 18px;

            transition:
                    transform 0.3s,
                    box-shadow 0.3s;
        }

        .file-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .file-icon {
            width: 50px;
            height: 50px;

            background: black;
            color: white;
            border-radius: 9px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 12px;
            font-weight: bold;
        }

        .folder-icon {
            background: #555555;
        }

        .file-information h3 {
            margin-bottom: 6px;
            font-size: 16px;
            word-break: break-word;
        }

        .file-information p {
            color: #777;
            font-size: 13px;
        }

        .file-detail h4 {
            margin-bottom: 6px;
            color: #777;
            font-size: 12px;
        }

        .file-detail p {
            color: #222;
            font-size: 13px;
            font-weight: bold;
        }

        .version-label {
            display: inline-block;
            padding: 5px 9px;

            background: #eeeeee;
            color: #555;
            border-radius: 15px;

            font-size: 12px;
            font-weight: bold;
        }

        /* File actions as text links */

        .file-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .text-link {
            color: black;
            font-size: 14px;
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
           Storage Progress
        ========================= */

        .storage-area {
            margin-top: 25px;
            padding: 20px;

            background: #f7f7f7;
            border-radius: 10px;
        }

        .storage-heading {
            margin-bottom: 11px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .storage-heading strong {
            font-size: 15px;
        }

        .storage-heading span {
            color: #666;
            font-size: 13px;
        }

        .storage-bar {
            width: 100%;
            height: 13px;

            background: #dddddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .storage-value {
            width: 35%;
            height: 100%;

            background: black;
            border-radius: 10px;
        }

        .storage-note {
            margin-top: 9px;
            color: #777;
            font-size: 13px;
        }

        /* =========================
           Information Notice
        ========================= */

        .file-note {
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
           Responsive Design
        ========================= */

        @media (max-width: 1050px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .file-section-header {
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

            .file-item {
                grid-template-columns: 55px 2fr 1fr auto;
            }

            .file-item .version-detail {
                display: none;
            }
        }

        @media (max-width: 700px) {
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

            .file-item {
                grid-template-columns: 50px 1fr;
            }

            .file-detail,
            .file-actions {
                grid-column: 1 / -1;
            }

            .file-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
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

        <a href="student_files.php" class="nav-link active">
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

    <section class="page-heading">

        <h1>Shared Files</h1>

        <p>
            Upload, download and manage your team project files.
            View file versions, upload information and recent changes.
        </p>

    </section>

    <!-- File Summary -->

    <section class="summary-grid">

        <div class="summary-card">
            <h2>Total Files</h2>
            <p class="summary-number">12</p>
            <p class="summary-description">Files stored in the project workspace</p>
        </div>

        <div class="summary-card">
            <h2>Folders</h2>
            <p class="summary-number">4</p>
            <p class="summary-description">Project file categories</p>
        </div>

        <div class="summary-card">
            <h2>Recent Uploads</h2>
            <p class="summary-number">3</p>
            <p class="summary-description">Files uploaded this week</p>
        </div>

        <div class="summary-card">
            <h2>Storage Used</h2>
            <p class="summary-number">35%</p>
            <p class="summary-description">350 MB of 1 GB used</p>
        </div>

    </section>

    <!-- Upload File Section -->

    <section class="upload-section">

        <h2 class="section-title">Upload New File</h2>

        <div class="upload-area">

            <div class="upload-icon">+</div>

            <h3>Select a project file</h3>

            <p>
                Supported examples: PDF, DOCX, PPTX, XLSX, PNG and JPG
            </p>

            <label for="fileUpload" class="upload-btn">
                Choose File
            </label>

            <input
                type="file"
                id="fileUpload"
                name="fileUpload"
                class="file-input"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.png,.jpg,.jpeg"
            >

        </div>

    </section>

    <!-- Shared File List -->

    <section class="file-section">

        <div class="file-section-header">

            <h2>Project Files</h2>

            <div class="toolbar">

                <input
                    type="search"
                    class="search-input"
                    placeholder="Search files"
                    aria-label="Search files"
                >

                <select
                    class="filter-select"
                    aria-label="Filter file type"
                >
                    <option value="all">All File Types</option>
                    <option value="folder">Folders</option>
                    <option value="document">Documents</option>
                    <option value="presentation">Presentations</option>
                    <option value="spreadsheet">Spreadsheets</option>
                    <option value="image">Images</option>
                </select>

            </div>

        </div>

        <div class="folder-navigation">
            <strong>Location:</strong>
            TeamBox Project / Shared Workspace
        </div>

        <div class="file-list">

            <!-- Folder 1 -->

            <article class="file-item">

                <div class="file-icon folder-icon">DIR</div>

                <div class="file-information">
                    <h3>Project Documents</h3>
                    <p>Folder containing reports and proposals</p>
                </div>

                <div class="file-detail">
                    <h4>Last Updated</h4>
                    <p>23 September 2026</p>
                </div>

                <div class="file-detail version-detail">
                    <h4>Items</h4>
                    <p>5 files</p>
                </div>

                <div class="file-actions">
                    <a href="#" class="text-link">Open</a>
                    <a href="#" class="text-link">⋯</a>
                </div>

            </article>

            <!-- Folder 2 -->

            <article class="file-item">

                <div class="file-icon folder-icon">DIR</div>

                <div class="file-information">
                    <h3>System Design</h3>
                    <p>Folder containing diagrams and interface designs</p>
                </div>

                <div class="file-detail">
                    <h4>Last Updated</h4>
                    <p>22 September 2026</p>
                </div>

                <div class="file-detail version-detail">
                    <h4>Items</h4>
                    <p>4 files</p>
                </div>

                <div class="file-actions">
                    <a href="#" class="text-link">Open</a>
                    <a href="#" class="text-link">⋯</a>
                </div>

            </article>

            <!-- File 1 -->

            <article class="file-item">

                <div class="file-icon">PDF</div>

                <div class="file-information">
                    <h3>Project_Proposal.pdf</h3>
                    <p>Uploaded by Student A · 2.4 MB</p>
                </div>

                <div class="file-detail">
                    <h4>Uploaded Date</h4>
                    <p>20 September 2026</p>
                </div>

                <div class="file-detail version-detail">
                    <h4>Version</h4>
                    <span class="version-label">Version 3</span>
                </div>

                <div class="file-actions">
                    <a href="#" class="text-link">Download</a>
                    <a href="#" class="text-link">⋯</a>
                </div>

            </article>

            <!-- File 2 -->

            <article class="file-item">

                <div class="file-icon">DOCX</div>

                <div class="file-information">
                    <h3>Requirement_Analysis.docx</h3>
                    <p>Uploaded by Student B · 1.8 MB</p>
                </div>

                <div class="file-detail">
                    <h4>Uploaded Date</h4>
                    <p>21 September 2026</p>
                </div>

                <div class="file-detail version-detail">
                    <h4>Version</h4>
                    <span class="version-label">Version 2</span>
                </div>

                <div class="file-actions">
                    <a href="#" class="text-link">Download</a>
                    <a href="#" class="text-link">⋯</a>
                </div>

            </article>

            <!-- File 3 -->

            <article class="file-item">

                <div class="file-icon">PPTX</div>

                <div class="file-information">
                    <h3>Project_Presentation.pptx</h3>
                    <p>Uploaded by Student C · 6.2 MB</p>
                </div>

                <div class="file-detail">
                    <h4>Uploaded Date</h4>
                    <p>22 September 2026</p>
                </div>

                <div class="file-detail version-detail">
                    <h4>Version</h4>
                    <span class="version-label">Version 1</span>
                </div>

                <div class="file-actions">
                    <a href="#" class="text-link">Download</a>
                    <a href="#" class="text-link">⋯</a>
                </div>

            </article>

            <!-- File 4 -->

            <article class="file-item">

                <div class="file-icon">XLSX</div>

                <div class="file-information">
                    <h3>Project_Task_List.xlsx</h3>
                    <p>Uploaded by Student D · 950 KB</p>
                </div>

                <div class="file-detail">
                    <h4>Uploaded Date</h4>
                    <p>23 September 2026</p>
                </div>

                <div class="file-detail version-detail">
                    <h4>Version</h4>
                    <span class="version-label">Version 4</span>
                </div>

                <div class="file-actions">
                    <a href="#" class="text-link">Download</a>
                    <a href="#" class="text-link">⋯</a>
                </div>

            </article>

        </div>

        <!-- Storage Usage -->

        <div class="storage-area">

            <div class="storage-heading">
                <strong>Project Storage</strong>
                <span>350 MB / 1 GB</span>
            </div>

            <div class="storage-bar">
                <div class="storage-value"></div>
            </div>

            <p class="storage-note">
                35% of the available project storage has been used.
            </p>

        </div>

        <!-- Information Note -->

        <div class="file-note">
            The upload, search, filter, download and version functions
            currently demonstrate the user interface only. PHP and database
            functions will be required to store and manage actual files.
        </div>

    </section>

</main>

</body>
</html>