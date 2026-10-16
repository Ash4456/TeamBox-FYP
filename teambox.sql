-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-10-13 11:35:43
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `teambox`
--

-- --------------------------------------------------------

--
-- 表的结构 `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `actor_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `activities`
--

INSERT INTO `activities` (`id`, `project_id`, `group_id`, `actor_id`, `action`, `detail`, `created_at`) VALUES
(1, 1, 1, 6, 'uploaded', 'uploaded Proposal_v2.docx', '2026-10-07 11:11:59'),
(2, 1, 1, 8, 'completed', 'marked Requirement Analysis complete', '2026-10-07 11:11:59'),
(3, 1, 1, 9, 'commented', 'commented on UI_Wireframe', '2026-10-07 11:11:59'),
(4, 1, 1, 6, 'elected', 'was elected as team leader', '2026-10-07 11:11:59'),
(5, 1, 1, 1, 'locked', 'locked the team roster', '2026-10-07 11:11:59'),
(6, 4, 4, 13, 'uploaded', 'uploaded SRS_v1.docx', '2026-10-07 11:11:59'),
(7, 4, 4, 14, 'uploaded', 'uploaded SeatMap_Mockup.png', '2026-10-07 11:11:59'),
(8, 4, 4, 15, 'uploaded', 'uploaded API_Design.pdf', '2026-10-07 11:11:59'),
(9, 4, 4, 2, 'reviewed', 'accepted SRS_v1.docx', '2026-10-07 11:11:59'),
(10, 5, 5, 14, 'uploaded', 'uploaded Menu_Schema.sql', '2026-10-07 11:11:59'),
(11, 5, 5, 17, 'uploaded', 'uploaded Payment_Flow.pdf', '2026-10-07 11:11:59'),
(12, 6, 6, 18, 'uploaded', 'uploaded Dataset_Description.pdf', '2026-10-07 11:11:59'),
(13, 6, 6, 20, 'uploaded', 'uploaded Baseline_Model.ipynb', '2026-10-07 11:11:59'),
(14, 6, 6, 21, 'uploaded', 'uploaded Prompt_Templates.txt', '2026-10-07 11:11:59'),
(15, 8, 9, 25, 'uploaded', 'uploaded Company_List.xlsx', '2026-10-07 11:11:59'),
(16, 8, 9, 26, 'uploaded', 'uploaded Matching_Algo.pdf', '2026-10-07 11:11:59'),
(17, 8, 9, 27, 'uploaded', 'uploaded Dashboard_Wireframe.fig', '2026-10-07 11:11:59'),
(18, 1, 1, 6, 'created', 'created Task \"Create System Interface\"', '2026-10-07 11:11:59'),
(19, 5, 5, 2, 'reviewed', 'rejected API_Design.pdf', '2026-10-07 11:11:59'),
(20, 4, 13, 6, 'submitted', 'submitted group \"ABC\" to supervisor Dr. Alan Ho', '2026-10-09 11:09:13'),
(21, 4, 13, 6, 'elected', 'was elected as team leader', '2026-10-12 16:25:28');

-- --------------------------------------------------------

--
-- 表的结构 `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `target_group_id` int(11) DEFAULT NULL,
  `target_project_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `announcements`
--

INSERT INTO `announcements` (`id`, `teacher_id`, `title`, `message`, `target_group_id`, `target_project_id`, `created_at`) VALUES
(1, 1, 'Progress Report Deadline', 'Please submit your progress report by 15 Oct.', NULL, 1, '2026-10-07 11:11:59'),
(2, 1, 'Welcome to FYP', 'Welcome everyone to the Final Year Project.', NULL, NULL, '2026-10-07 11:11:59'),
(3, 2, 'Mid-term Demo Schedule', 'Mid-term demo will be held on 20 Nov 2026.', NULL, 4, '2026-10-07 11:11:59'),
(4, 2, 'Campus Food Order Update', 'Please finalise the payment flow by end Oct.', NULL, 5, '2026-10-07 11:11:59'),
(5, 3, 'AI Study Buddy Reminder', 'Please submit the baseline model results.', NULL, 6, '2026-10-07 11:11:59'),
(6, 3, 'Green Campus Kickoff', 'Kickoff meeting on 15 Oct 2026.', NULL, 7, '2026-10-07 11:11:59'),
(7, 4, 'Career Path Finder', 'Dashboard design review next Monday.', NULL, 8, '2026-10-07 11:11:59'),
(8, 4, 'Virtual Lab Update', 'VR prototype draft due end of month.', NULL, 9, '2026-10-07 11:11:59'),
(9, 5, 'Campus Events Update', 'UI design must be done by mid Nov.', NULL, 10, '2026-10-07 11:11:59'),
(10, 5, 'Study Group Match', 'Voting for leader opens next week.', NULL, 11, '2026-10-07 11:11:59');

-- --------------------------------------------------------

--
-- 表的结构 `announcement_targets`
--

CREATE TABLE `announcement_targets` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `target_type` enum('course','group','student') NOT NULL,
  `target_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `teacher_id`, `semester`, `created_at`) VALUES
(1, 'ITE4116M', 'Final Year Project', 1, '2026/27 S1', '2026-10-07 11:11:58'),
(2, 'ITE3118', 'Web Application Dev', 1, '2026/27 S1', '2026-10-07 11:11:58'),
(3, 'ITE3120', 'Mobile Application Dev', 2, '2026/27 S1', '2026-10-07 11:11:58'),
(4, 'ITE3220', 'Database Systems', 3, '2026/27 S1', '2026-10-07 11:11:58'),
(5, 'ITE3330', 'Software Engineering', 4, '2026/27 S1', '2026-10-07 11:11:58'),
(6, 'ITE3440', 'AI & Machine Learning', 5, '2026/27 S1', '2026-10-07 11:11:58');

-- --------------------------------------------------------

--
-- 表的结构 `course_enrollments`
--

CREATE TABLE `course_enrollments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `course_enrollments`
--

INSERT INTO `course_enrollments` (`id`, `course_id`, `student_id`, `enrolled_at`) VALUES
(1, 1, 1, '2026-10-07 11:11:58'),
(2, 1, 2, '2026-10-07 11:11:58'),
(3, 1, 3, '2026-10-07 11:11:58'),
(4, 1, 4, '2026-10-07 11:11:58'),
(5, 1, 5, '2026-10-07 11:11:58'),
(6, 1, 6, '2026-10-07 11:11:58'),
(7, 1, 7, '2026-10-07 11:11:58'),
(8, 1, 8, '2026-10-07 11:11:58'),
(9, 1, 9, '2026-10-07 11:11:58'),
(10, 1, 10, '2026-10-07 11:11:58'),
(11, 1, 11, '2026-10-07 11:11:58'),
(12, 1, 12, '2026-10-07 11:11:58'),
(13, 1, 13, '2026-10-07 11:11:58'),
(14, 1, 14, '2026-10-07 11:11:58'),
(15, 1, 15, '2026-10-07 11:11:58'),
(16, 1, 16, '2026-10-07 11:11:58'),
(17, 1, 17, '2026-10-07 11:11:58'),
(18, 1, 18, '2026-10-07 11:11:58'),
(19, 1, 19, '2026-10-07 11:11:58'),
(20, 1, 20, '2026-10-07 11:11:58'),
(21, 1, 21, '2026-10-07 11:11:58'),
(22, 1, 22, '2026-10-07 11:11:58'),
(23, 1, 23, '2026-10-07 11:11:58'),
(24, 1, 24, '2026-10-07 11:11:58'),
(25, 1, 25, '2026-10-07 11:11:58');

-- --------------------------------------------------------

--
-- 表的结构 `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(30) DEFAULT NULL,
  `file_type` varchar(30) DEFAULT NULL,
  `status` enum('Unviewed','Accepted','Rejected') DEFAULT 'Unviewed',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `files`
--

INSERT INTO `files` (`id`, `project_id`, `group_id`, `uploader_id`, `file_name`, `file_size`, `file_type`, `status`, `uploaded_at`, `reviewed_by`, `reviewed_at`, `review_note`) VALUES
(1, 1, 1, 1, 'Proposal_v2.docx', '2.4 MB', 'DOCX', 'Unviewed', '2026-10-04 02:42:00', NULL, NULL, NULL),
(2, 1, 1, 3, 'Requirement_Analysis.pdf', '1.8 MB', 'PDF', 'Accepted', '2026-10-03 07:20:00', 1, '2026-10-04 09:00:00', 'Looks good.'),
(3, 1, 1, 4, 'UI_Wireframe.fig', '5.1 MB', 'FIG', 'Unviewed', '2026-10-02 01:15:00', NULL, NULL, NULL),
(4, 1, 1, 1, 'Sprint_Plan_Oct.xlsx', '180 KB', 'XLSX', 'Accepted', '2026-09-25 09:45:00', 1, '2026-09-26 09:00:00', 'OK.'),
(5, 1, 1, 1, 'Kickoff_Notes.docx', '120 KB', 'DOCX', 'Accepted', '2026-09-10 01:00:00', 1, '2026-09-10 12:00:00', 'OK.'),
(6, 1, 1, 2, 'Brainstorm.png', '780 KB', 'PNG', 'Accepted', '2026-09-12 06:00:00', 1, '2026-09-12 16:00:00', 'OK.'),
(7, 1, 2, 3, 'Database_Schema.sql', '120 KB', 'SQL', 'Accepted', '2026-10-01 03:00:00', 1, '2026-10-01 15:00:00', 'Approved.'),
(8, 1, 2, 2, 'API_Spec.pdf', '890 KB', 'PDF', 'Rejected', '2026-09-30 08:30:00', 1, '2026-10-01 10:00:00', 'Missing authentication section.'),
(9, 1, 2, 3, 'ER_Diagram.png', '1.1 MB', 'PNG', 'Accepted', '2026-09-15 02:00:00', 1, '2026-09-15 14:00:00', 'Nice.'),
(10, 2, 3, 6, 'Meeting_Minutes_2026-09-28.docx', '240 KB', 'DOCX', 'Accepted', '2026-09-28 06:00:00', 1, '2026-09-28 16:00:00', 'OK.'),
(11, 2, 3, 7, 'Budget_Plan.xlsx', '310 KB', 'XLSX', 'Rejected', '2026-09-26 02:00:00', 1, '2026-09-27 09:00:00', 'Numbers do not match the proposal.'),
(12, 2, 3, 8, 'Research_Notes.pdf', '1.1 MB', 'PDF', 'Unviewed', '2026-09-22 05:10:00', NULL, NULL, NULL),
(13, 4, 4, 8, 'SRS_v1.docx', '3.1 MB', 'DOCX', 'Accepted', '2026-10-05 01:30:00', 2, '2026-10-05 14:00:00', 'Looks good.'),
(14, 4, 4, 9, 'SeatMap_Mockup.png', '1.2 MB', 'PNG', 'Unviewed', '2026-10-06 03:15:00', NULL, NULL, NULL),
(15, 4, 4, 10, 'API_Design.pdf', '880 KB', 'PDF', 'Rejected', '2026-10-06 07:00:00', 2, '2026-10-07 09:00:00', 'Please add error handling.'),
(16, 5, 5, 9, 'Menu_Schema.sql', '95 KB', 'SQL', 'Accepted', '2026-10-02 02:00:00', 2, '2026-10-02 16:00:00', 'Approved.'),
(17, 5, 5, 12, 'Payment_Flow.pdf', '1.4 MB', 'PDF', 'Unviewed', '2026-10-05 00:45:00', NULL, NULL, NULL),
(18, 5, 5, 13, 'Order_Tracking.fig', '2.0 MB', 'FIG', 'Unviewed', '2026-10-06 01:20:00', NULL, NULL, NULL),
(19, 6, 6, 13, 'Dataset_Description.pdf', '1.5 MB', 'PDF', 'Accepted', '2026-09-30 06:00:00', 3, '2026-10-01 09:00:00', 'Good.'),
(20, 6, 6, 15, 'Baseline_Model.ipynb', '2.8 MB', 'IPYNB', 'Unviewed', '2026-10-06 08:40:00', NULL, NULL, NULL),
(21, 6, 6, 16, 'Prompt_Templates.txt', '45 KB', 'TXT', 'Unviewed', '2026-10-07 02:00:00', NULL, NULL, NULL),
(22, 8, 9, 20, 'Company_List.xlsx', '420 KB', 'XLSX', 'Accepted', '2026-10-05 03:00:00', 4, '2026-10-05 15:30:00', 'Verified.'),
(23, 8, 9, 21, 'Matching_Algo.pdf', '950 KB', 'PDF', 'Unviewed', '2026-10-07 05:00:00', NULL, NULL, NULL),
(24, 8, 9, 22, 'Dashboard_Wireframe.fig', '3.5 MB', 'FIG', 'Unviewed', '2026-10-07 06:30:00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `max_members` int(11) DEFAULT 4,
  `status` enum('Recruiting','Waiting_Send','Voting','Executing','Completed') DEFAULT 'Recruiting',
  `formed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `groups`
--

INSERT INTO `groups` (`id`, `project_id`, `course_id`, `name`, `leader_id`, `creator_id`, `max_members`, `status`, `formed_at`, `created_at`) VALUES
(1, 1, 1, 'Team 01', 1, 4, 4, 'Executing', '2026-09-15 10:00:00', '2026-10-07 11:11:58'),
(2, 1, 1, 'Team 02', 3, 3, 4, 'Voting', NULL, '2026-10-07 11:11:58'),
(3, 2, 1, 'Team 03', 5, 7, 4, 'Recruiting', NULL, '2026-10-07 11:11:58'),
(4, 4, 1, 'Team 04', 8, 11, 4, 'Executing', '2026-09-14 10:00:00', '2026-10-07 11:11:58'),
(5, 5, 1, 'Team 05', 9, 14, 4, 'Executing', '2026-09-15 11:30:00', '2026-10-07 11:11:58'),
(6, 6, 1, 'Team 06', 13, 17, 4, 'Executing', '2026-09-16 14:20:00', '2026-10-07 11:11:58'),
(7, 6, 1, 'Team 07', 17, 17, 4, 'Voting', NULL, '2026-10-07 11:11:58'),
(8, 7, 1, 'Team 08', 18, 18, 4, 'Recruiting', NULL, '2026-10-07 11:11:58'),
(9, 8, 1, 'Team 09', 20, 23, 4, 'Executing', '2026-09-17 09:00:00', '2026-10-07 11:11:58'),
(10, 9, 1, 'Team 10', 22, 22, 4, 'Recruiting', NULL, '2026-10-07 11:11:58'),
(11, 11, 1, 'Team A', 1, 1, 4, 'Voting', NULL, '2026-10-07 11:12:46'),
(12, 4, 1, 'dff', 3, 1, 3, 'Executing', '2026-10-07 23:37:11', '2026-10-07 12:34:06'),
(13, 4, 1, 'ABC', 1, 1, 4, 'Executing', '2026-10-09 19:09:13', '2026-10-09 11:06:49'),
(14, 9, 1, 'Teram b', NULL, 1, 4, 'Recruiting', NULL, '2026-10-09 11:58:44');

-- --------------------------------------------------------

--
-- 表的结构 `group_members`
--

CREATE TABLE `group_members` (
  `group_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `role` enum('leader','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `group_members`
--

INSERT INTO `group_members` (`group_id`, `student_id`, `role`, `joined_at`) VALUES
(1, 1, 'leader', '2026-10-07 11:11:58'),
(1, 2, 'member', '2026-10-07 11:11:58'),
(1, 3, 'member', '2026-10-07 11:11:58'),
(1, 4, 'member', '2026-10-07 11:11:58'),
(2, 3, 'leader', '2026-10-07 11:11:58'),
(2, 6, 'member', '2026-10-07 11:11:58'),
(3, 5, 'leader', '2026-10-07 11:11:58'),
(3, 6, 'member', '2026-10-07 11:11:58'),
(3, 7, 'member', '2026-10-07 11:11:58'),
(4, 8, 'leader', '2026-10-07 11:11:58'),
(4, 9, 'member', '2026-10-07 11:11:58'),
(4, 10, 'member', '2026-10-07 11:11:58'),
(4, 11, 'member', '2026-10-07 11:11:58'),
(5, 9, 'leader', '2026-10-07 11:11:58'),
(5, 12, 'member', '2026-10-07 11:11:58'),
(5, 13, 'member', '2026-10-07 11:11:58'),
(5, 14, 'member', '2026-10-07 11:11:58'),
(6, 13, 'leader', '2026-10-07 11:11:58'),
(6, 15, 'member', '2026-10-07 11:11:58'),
(6, 16, 'member', '2026-10-07 11:11:58'),
(6, 17, 'member', '2026-10-07 11:11:58'),
(7, 17, 'leader', '2026-10-07 11:11:58'),
(7, 18, 'member', '2026-10-07 11:11:58'),
(8, 18, 'leader', '2026-10-07 11:11:58'),
(8, 19, 'member', '2026-10-07 11:11:58'),
(9, 20, 'leader', '2026-10-07 11:11:58'),
(9, 21, 'member', '2026-10-07 11:11:58'),
(9, 22, 'member', '2026-10-07 11:11:58'),
(9, 23, 'member', '2026-10-07 11:11:58'),
(10, 22, 'leader', '2026-10-07 11:11:58'),
(10, 23, 'member', '2026-10-07 11:11:58'),
(11, 1, 'leader', '2026-10-07 11:12:46'),
(11, 2, 'member', '2026-10-09 10:37:08'),
(11, 3, 'member', '2026-10-09 10:38:04'),
(11, 4, 'member', '2026-10-09 10:55:01'),
(12, 1, 'member', '2026-10-07 12:34:06'),
(12, 3, 'leader', '2026-10-07 15:35:11'),
(12, 4, 'member', '2026-10-07 15:36:55'),
(13, 1, 'leader', '2026-10-09 11:06:49'),
(13, 2, 'member', '2026-10-09 11:08:42'),
(13, 3, 'member', '2026-10-09 11:08:23'),
(13, 4, 'member', '2026-10-09 11:08:01'),
(14, 1, 'member', '2026-10-09 11:58:44');

-- --------------------------------------------------------

--
-- 表的结构 `group_requests`
--

CREATE TABLE `group_requests` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `project_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `review_note` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `group_requests`
--

INSERT INTO `group_requests` (`id`, `group_name`, `project_id`, `submitted_by`, `status`, `review_note`, `submitted_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 'ABC', 4, 1, 'Accepted', NULL, '2026-10-09 11:09:13', NULL, NULL),
(2, 'Team New', 1, 1, 'Pending', NULL, '2026-10-13 08:46:58', NULL, NULL),
(3, 'Team Y', 1, 1, 'Pending', NULL, '2026-10-13 08:57:59', NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `invitations`
--

CREATE TABLE `invitations` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `inviter_id` int(11) NOT NULL,
  `invitee_id` int(11) NOT NULL,
  `status` enum('Pending','Accepted','Rejected','Cancelled','Leave') DEFAULT 'Pending',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `invitations`
--

INSERT INTO `invitations` (`id`, `group_id`, `inviter_id`, `invitee_id`, `status`, `sent_at`, `responded_at`) VALUES
(1, 12, 1, 2, 'Cancelled', '2026-10-07 15:10:47', '2026-10-07 23:35:28'),
(2, 12, 1, 3, 'Accepted', '2026-10-07 15:34:21', '2026-10-07 23:35:11'),
(3, 12, 1, 4, 'Accepted', '2026-10-07 15:36:41', '2026-10-07 23:36:55'),
(4, 11, 1, 2, 'Accepted', '2026-10-09 10:36:32', '2026-10-09 18:37:08'),
(5, 11, 2, 3, 'Accepted', '2026-10-09 10:37:22', '2026-10-09 18:38:04'),
(6, 11, 1, 4, 'Accepted', '2026-10-09 10:54:48', '2026-10-09 18:55:01'),
(7, 13, 1, 2, 'Accepted', '2026-10-09 11:07:06', '2026-10-09 19:08:42'),
(8, 13, 1, 3, 'Accepted', '2026-10-09 11:07:27', '2026-10-09 19:08:23'),
(9, 13, 1, 4, 'Accepted', '2026-10-09 11:07:38', '2026-10-09 19:08:01'),
(10, 14, 1, 2, 'Pending', '2026-10-09 11:59:02', NULL),
(11, 14, 1, 3, 'Leave', '2026-10-09 14:14:43', '2026-10-13 00:48:22'),
(12, 14, 1, 4, 'Accepted', '2026-10-09 14:14:55', '2026-10-13 00:25:45');

-- --------------------------------------------------------

--
-- 表的结构 `leader_votes`
--

CREATE TABLE `leader_votes` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `leader_votes`
--

INSERT INTO `leader_votes` (`id`, `group_id`, `voter_id`, `candidate_id`, `created_at`) VALUES
(1, 12, 1, 3, '2026-10-07 15:37:17'),
(2, 12, 3, 3, '2026-10-07 15:37:41'),
(3, 12, 4, 3, '2026-10-07 15:38:02'),
(4, 13, 1, 1, '2026-10-09 14:57:03');

-- --------------------------------------------------------

--
-- 表的结构 `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `attachment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `messages`
--

INSERT INTO `messages` (`id`, `group_id`, `sender_id`, `content`, `attachment_id`, `created_at`) VALUES
(1, 1, 1, 'Please update your assigned task progress before the team meeting.', NULL, '2026-10-07 11:11:59'),
(2, 1, 3, 'I have completed the student login page and I am currently developing the student dashboard.', NULL, '2026-10-07 11:11:59'),
(3, 1, 2, 'I have uploaded the latest project proposal to the shared files page.', NULL, '2026-10-07 11:11:59'),
(4, 1, 4, 'What time will the next team meeting begin?', NULL, '2026-10-07 11:11:59'),
(5, 1, 1, 'The meeting will begin at 3:30 PM today. Please prepare your current progress and questions.', NULL, '2026-10-07 11:11:59'),
(6, 4, 8, 'Hi team, let us start the SRS document today.', NULL, '2026-10-07 11:11:59'),
(7, 4, 9, 'OK. I will draft the introduction section.', NULL, '2026-10-07 11:11:59'),
(8, 4, 10, 'I will work on the functional requirements.', NULL, '2026-10-07 11:11:59'),
(9, 5, 9, 'Payment integration is tricky. Any ideas?', NULL, '2026-10-07 11:11:59'),
(10, 5, 12, 'Let us use Stripe test mode first.', NULL, '2026-10-07 11:11:59'),
(11, 5, 13, 'Agreed. I will set up a sandbox account.', NULL, '2026-10-07 11:11:59'),
(12, 6, 13, 'Baseline model accuracy is 78%.', NULL, '2026-10-07 11:11:59'),
(13, 6, 15, 'Can we try a bigger transformer model?', NULL, '2026-10-07 11:11:59'),
(14, 6, 16, 'Yes, I will test it this week.', NULL, '2026-10-07 11:11:59'),
(15, 9, 20, 'Company list is ready. Please review.', NULL, '2026-10-07 11:11:59'),
(16, 9, 21, 'Matching algorithm draft done. Testing now.', NULL, '2026-10-07 11:11:59'),
(17, 9, 22, 'Dashboard wireframe uploaded.', NULL, '2026-10-07 11:11:59');

-- --------------------------------------------------------

--
-- 表的结构 `milestones`
--

CREATE TABLE `milestones` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `progress` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `milestones`
--

INSERT INTO `milestones` (`id`, `project_id`, `name`, `start_date`, `end_date`, `status`, `progress`) VALUES
(1, 1, 'Requirement Analysis', '2026-09-01', '2026-10-05', 'Completed', 100),
(2, 1, 'System Design', '2026-10-06', '2026-10-20', 'Completed', 100),
(3, 1, 'Core Development', '2026-10-21', '2027-01-15', 'In Progress', 55),
(4, 1, 'Testing & Documentation', '2027-01-16', '2027-03-20', 'Pending', 0),
(5, 1, 'Final Presentation', '2027-03-21', '2027-04-30', 'Pending', 0),
(6, 2, 'Requirement Analysis', '2026-09-01', '2026-10-10', 'Completed', 100),
(7, 2, 'System Design', '2026-10-11', '2026-10-31', 'In Progress', 60),
(8, 2, 'Development', '2026-11-01', '2027-02-15', 'Pending', 0),
(9, 2, 'Testing', '2027-02-16', '2027-03-31', 'Pending', 0),
(10, 2, 'Presentation', '2027-04-01', '2027-04-30', 'Pending', 0),
(11, 3, 'Requirement Analysis', '2026-09-01', '2026-10-20', 'Pending', 0),
(12, 3, 'AR Prototype', '2026-10-21', '2027-02-28', 'Pending', 0),
(13, 3, 'User Testing', '2027-03-01', '2027-04-10', 'Pending', 0),
(14, 4, 'Requirement Analysis', '2026-09-01', '2026-10-10', 'Completed', 100),
(15, 4, 'System Design', '2026-10-11', '2026-10-31', 'In Progress', 60),
(16, 4, 'Development', '2026-11-01', '2027-02-15', 'Pending', 0),
(17, 4, 'Testing', '2027-02-16', '2027-03-31', 'Pending', 0),
(18, 4, 'Presentation', '2027-04-01', '2027-04-30', 'Pending', 0),
(19, 5, 'Requirement Analysis', '2026-09-01', '2026-10-05', 'Completed', 100),
(20, 5, 'UI Design', '2026-10-06', '2026-10-25', 'In Progress', 40),
(21, 5, 'Development', '2026-10-26', '2027-02-28', 'Pending', 0),
(22, 5, 'Testing', '2027-03-01', '2027-04-10', 'Pending', 0),
(23, 6, 'Research', '2026-09-01', '2026-09-30', 'Completed', 100),
(24, 6, 'Model Training', '2026-10-01', '2027-01-10', 'In Progress', 55),
(25, 6, 'Evaluation', '2027-01-11', '2027-03-15', 'Pending', 0),
(26, 6, 'Thesis', '2027-03-16', '2027-04-30', 'Pending', 0),
(27, 7, 'Requirement Analysis', '2026-09-01', '2026-10-15', 'Pending', 0),
(28, 7, 'Hardware Setup', '2026-10-16', '2027-01-31', 'Pending', 0),
(29, 7, 'Data Collection', '2027-02-01', '2027-03-15', 'Pending', 0),
(30, 7, 'Final Presentation', '2027-03-16', '2027-04-30', 'Pending', 0),
(31, 8, 'Requirement Analysis', '2026-09-01', '2026-10-10', 'Completed', 100),
(32, 8, 'Database Design', '2026-10-11', '2026-11-10', 'In Progress', 50),
(33, 8, 'Frontend Dev', '2026-11-11', '2027-02-10', 'Pending', 0),
(34, 8, 'Backend Dev', '2026-11-11', '2027-02-10', 'Pending', 0),
(35, 8, 'Final Presentation', '2027-03-01', '2027-04-30', 'Pending', 0),
(36, 9, 'Research', '2026-09-01', '2026-10-20', 'Pending', 0),
(37, 9, 'VR Prototype', '2026-10-21', '2027-02-28', 'Pending', 0),
(38, 9, 'User Testing', '2027-03-01', '2027-04-10', 'Pending', 0),
(39, 10, 'Requirement Analysis', '2026-09-01', '2026-10-05', 'Completed', 100),
(40, 10, 'UI Design', '2026-10-06', '2026-11-15', 'In Progress', 65),
(41, 10, 'Development', '2026-11-16', '2027-02-28', 'Pending', 0),
(42, 10, 'Testing', '2027-03-01', '2027-04-10', 'Pending', 0),
(43, 11, 'Requirement Analysis', '2026-09-01', '2026-10-20', 'In Progress', 30),
(44, 11, 'System Design', '2026-10-21', '2026-11-30', 'Pending', 0),
(45, 11, 'Development', '2026-12-01', '2027-03-15', 'Pending', 0),
(46, 11, 'Testing', '2027-03-16', '2027-04-20', 'Pending', 0);

-- --------------------------------------------------------

--
-- 表的结构 `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('task','file','team','project','system') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 6, 'task', 'New Task Assigned', 'You have been assigned \"Develop Student Dashboard\".', 'student_task.php', 1, '2026-10-07 11:11:59'),
(2, 6, 'file', 'New File Uploaded', 'Student B uploaded \"Proposal_v2.docx\".', 'student_files.php', 1, '2026-10-07 11:11:59'),
(3, 6, 'team', 'New Group Message', 'A new meeting reminder was posted.', 'student_chat.php', 1, '2026-10-07 11:11:59'),
(4, 13, 'task', 'New Task Assigned', 'You have been assigned \"Design Seat Map UI\".', 'student_task.php', 0, '2026-10-07 11:11:59'),
(5, 14, 'file', 'File Approved', 'Your \"Menu_Schema.sql\" was approved.', 'student_files.php', 0, '2026-10-07 11:11:59'),
(6, 15, 'file', 'File Rejected', 'Your \"API_Design.pdf\" was rejected.', 'student_files.php', 0, '2026-10-07 11:11:59'),
(7, 18, 'task', 'Task In Progress', 'Your task \"Train Baseline Model\" is ongoing.', 'student_task.php', 0, '2026-10-07 11:11:59'),
(8, 20, 'team', 'New Group Message', 'A new message was posted in Team 06.', 'student_chat.php', 0, '2026-10-07 11:11:59'),
(9, 25, 'file', 'File Approved', 'Your \"Company_List.xlsx\" was approved.', 'student_files.php', 0, '2026-10-07 11:11:59'),
(10, 26, 'task', 'New Task Assigned', 'You have been assigned \"Matching Algorithm\".', 'student_task.php', 0, '2026-10-07 11:11:59'),
(11, 27, 'team', 'New Group Message', 'A new message was posted in Team 09.', 'student_chat.php', 0, '2026-10-07 11:11:59'),
(12, 8, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"dff\".', 'accept_invite.php?id=2', 1, '2026-10-07 15:34:21'),
(13, 9, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"dff\".', 'accept_invite.php?id=3', 1, '2026-10-07 15:36:41'),
(14, 7, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Team A\".', 'accept_invite.php?id=4', 1, '2026-10-09 10:36:32'),
(15, 8, 'team', 'Group Invitation', 'CHUI Ka Chun invited you to join group \"Team A\".', 'accept_invite.php?id=5', 1, '2026-10-09 10:37:22'),
(16, 9, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Team A\".', 'accept_invite.php?id=6', 1, '2026-10-09 10:54:48'),
(17, 6, 'team', 'Team Full', 'Your group \"Team A\" is now full. Waiting for the group creator to send it to the supervisor.', 'student_create_group.php?group_id=11', 0, '2026-10-09 10:55:01'),
(18, 7, 'team', 'Team Full', 'Your group \"Team A\" is now full. Waiting for the group creator to send it to the supervisor.', 'student_create_group.php?group_id=11', 0, '2026-10-09 10:55:01'),
(19, 8, 'team', 'Team Full', 'Your group \"Team A\" is now full. Waiting for the group creator to send it to the supervisor.', 'student_create_group.php?group_id=11', 0, '2026-10-09 10:55:01'),
(20, 9, 'team', 'Team Full', 'Your group \"Team A\" is now full. Waiting for the group creator to send it to the supervisor.', 'student_create_group.php?group_id=11', 0, '2026-10-09 10:55:01'),
(21, 7, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"ABC\".', 'accept_invite.php?id=7', 1, '2026-10-09 11:07:06'),
(22, 8, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"ABC\".', 'accept_invite.php?id=8', 1, '2026-10-09 11:07:27'),
(23, 9, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"ABC\".', 'accept_invite.php?id=9', 1, '2026-10-09 11:07:38'),
(24, 6, 'team', 'Team Full', 'Your group \"ABC\" is now full. Waiting for the group creator to send it to the supervisor before voting starts.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:08:42'),
(25, 7, 'team', 'Team Full', 'Your group \"ABC\" is now full. Waiting for the group creator to send it to the supervisor before voting starts.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:08:42'),
(26, 8, 'team', 'Team Full', 'Your group \"ABC\" is now full. Waiting for the group creator to send it to the supervisor before voting starts.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:08:42'),
(27, 9, 'team', 'Team Full', 'Your group \"ABC\" is now full. Waiting for the group creator to send it to the supervisor before voting starts.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:08:42'),
(28, 6, 'team', 'Voting Started', 'Group \"ABC\" has been sent to Dr. Alan Ho. Please vote for the group leader.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:09:13'),
(29, 7, 'team', 'Voting Started', 'Group \"ABC\" has been sent to Dr. Alan Ho. Please vote for the group leader.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:09:13'),
(30, 8, 'team', 'Voting Started', 'Group \"ABC\" has been sent to Dr. Alan Ho. Please vote for the group leader.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:09:13'),
(31, 9, 'team', 'Voting Started', 'Group \"ABC\" has been sent to Dr. Alan Ho. Please vote for the group leader.', 'student_create_group.php?group_id=13', 0, '2026-10-09 11:09:13'),
(32, 2, 'project', 'New Group Submission', 'Group \"ABC\" has submitted for project \"Smart Library\".', 'teacher_teams.php', 0, '2026-10-09 11:09:13'),
(33, 7, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Teram b\".', 'accept_invite.php?id=10', 0, '2026-10-09 11:59:02'),
(34, 8, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Teram b\".', 'accept_invite.php?id=11', 1, '2026-10-09 11:59:15'),
(35, 9, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Teram b\".', 'accept_invite.php?id=12', 1, '2026-10-09 11:59:23'),
(36, 8, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Teram b\".', 'accept_invite.php?id=11', 1, '2026-10-09 14:14:43'),
(37, 9, 'team', 'Group Invitation', 'CHAN Ho Yin invited you to join group \"Teram b\".', 'accept_invite.php?id=12', 1, '2026-10-09 14:14:55'),
(38, 6, 'team', 'Group Leader Elected', 'CHAN Ho Yin has been elected as the group leader after the 48-hour voting window ended.', 'student_create_group.php?group_id=13', 0, '2026-10-12 16:25:28'),
(39, 7, 'team', 'Group Leader Elected', 'CHAN Ho Yin has been elected as the group leader after the 48-hour voting window ended.', 'student_create_group.php?group_id=13', 0, '2026-10-12 16:25:28'),
(40, 8, 'team', 'Group Leader Elected', 'CHAN Ho Yin has been elected as the group leader after the 48-hour voting window ended.', 'student_create_group.php?group_id=13', 0, '2026-10-12 16:25:28'),
(41, 9, 'team', 'Group Leader Elected', 'CHAN Ho Yin has been elected as the group leader after the 48-hour voting window ended.', 'student_create_group.php?group_id=13', 0, '2026-10-12 16:25:28'),
(42, 6, 'team', 'Member Left Group', 'MIAO Tung has left your group \"Teram b\".', 'student_create_group.php?group_id=14', 0, '2026-10-12 16:25:54'),
(43, 6, 'team', 'Member Left Group', 'LAI Ka-Yu Savio has left your group \"Teram b\".', 'student_create_group.php?group_id=14', 0, '2026-10-12 16:48:22');

-- --------------------------------------------------------

--
-- 表的结构 `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `supervisor_id` int(11) NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `status` enum('Recruiting','Waiting_Send','Voting','Executing','Completed','Archived') DEFAULT 'Recruiting',
  `progress` int(11) DEFAULT 0,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `projects`
--

INSERT INTO `projects` (`id`, `name`, `category`, `course_id`, `supervisor_id`, `period_start`, `period_end`, `status`, `progress`, `description`) VALUES
(1, 'TeamBox', 'Software Engineering', 1, 1, '2026-09-01', '2027-04-30', 'Executing', 60, 'Cross-platform collaboration and management system for student teams.'),
(2, 'Smart Attendance', 'Mobile App', 1, 1, '2026-09-01', '2027-04-30', 'Executing', 35, 'Face recognition based attendance system.'),
(3, 'Campus Map AR', 'AR / VR', 1, 1, '2026-09-01', '2027-04-30', 'Recruiting', 10, 'AR navigation for campus buildings.'),
(4, 'Smart Library', 'Web App', 1, 2, '2026-09-01', '2027-04-30', 'Executing', 40, 'Library seat booking and recommendation system.'),
(5, 'Campus Food Order', 'Mobile App', 1, 2, '2026-09-01', '2027-04-30', 'Executing', 25, 'Mobile app to order food from campus canteens.'),
(6, 'AI Study Buddy', 'Machine Learning', 1, 3, '2026-09-01', '2027-04-30', 'Executing', 55, 'AI assistant for personalised study plans.'),
(7, 'Green Campus', 'IoT', 1, 3, '2026-09-01', '2027-04-30', 'Recruiting', 10, 'IoT energy monitoring for campus buildings.'),
(8, 'Career Path Finder', 'Web App', 1, 4, '2026-09-01', '2027-04-30', 'Executing', 30, 'Web platform to match students with internships.'),
(9, 'Virtual Lab', 'VR', 1, 4, '2026-09-01', '2027-04-30', 'Recruiting', 5, 'VR-based virtual laboratory for science students.'),
(10, 'Campus Events', 'Mobile App', 1, 5, '2026-09-01', '2027-04-30', 'Executing', 45, 'Mobile app to discover and manage campus events.'),
(11, 'Study Group Match', 'Web App', 1, 5, '2026-09-01', '2027-04-30', 'Voting', 15, 'Platform to match students into study groups.');

-- --------------------------------------------------------

--
-- 表的结构 `request_members`
--

CREATE TABLE `request_members` (
  `request_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `is_leader` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `request_members`
--

INSERT INTO `request_members` (`request_id`, `student_id`, `is_leader`) VALUES
(2, 8, 1),
(2, 9, 0),
(2, 10, 0),
(2, 11, 0),
(3, 1, 1),
(3, 5, 0),
(3, 6, 0),
(3, 7, 0);

-- --------------------------------------------------------

--
-- 表的结构 `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `major` varchar(100) DEFAULT NULL,
  `grade_level` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_number`, `major`, `grade_level`) VALUES
(1, 6, 'S48291537', 'Software Engineering', 14),
(2, 7, 'S15942736', 'Software Engineering', 14),
(3, 8, 'S73658120', 'Software Engineering', 14),
(4, 9, 'S20473819', 'Software Engineering', 14),
(5, 10, 'S91826504', 'Software Engineering', 14),
(6, 11, 'S35718492', 'Software Engineering', 14),
(7, 12, 'S62048571', 'Software Engineering', 14),
(8, 13, 'S17395028', 'Software Engineering', 14),
(9, 14, 'S84926315', 'Software Engineering', 14),
(10, 15, 'S50274186', 'Software Engineering', 14),
(11, 16, 'S31486972', 'Information Technology', 14),
(12, 17, 'S76592043', 'Software Engineering', 14),
(13, 18, 'S09851426', 'Cloud Computing', 14),
(14, 19, 'S47203581', 'Software Engineering', 14),
(15, 20, 'S61573924', 'Information Technology', 14),
(16, 21, 'S28639174', 'Software Engineering', 14),
(17, 22, 'S93051768', 'Cloud Computing', 14),
(18, 23, 'S34718059', 'Software Engineering', 14),
(19, 24, 'S80245391', 'Information Technology', 14),
(20, 25, 'S15972806', 'Software Engineering', 14),
(21, 26, 'S62048913', 'Software Engineering', 14),
(22, 27, 'S47123895', 'Cloud Computing', 14),
(23, 28, 'S90471625', 'Software Engineering', 14),
(24, 29, 'S23850147', 'Information Technology', 14),
(25, 30, 'S76093184', 'Software Engineering', 14);

-- --------------------------------------------------------

--
-- 表的结构 `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `group_id`, `title`, `description`, `assigned_to`, `created_by`, `status`, `priority`, `due_date`, `completed_at`, `created_at`) VALUES
(1, 1, 1, 'Complete Project Proposal', 'Draft and submit the FYP proposal.', 1, 6, 'Completed', 'High', '2026-09-30', NULL, '2026-10-07 11:11:59'),
(2, 1, 1, 'Requirement Analysis', 'Analyse requirements and produce document.', 3, 6, 'Completed', 'Medium', '2026-10-05', NULL, '2026-10-07 11:11:59'),
(3, 1, 1, 'Create System Interface', 'Design the main UI screens.', 4, 6, 'In Progress', 'High', '2026-10-10', NULL, '2026-10-07 11:11:59'),
(4, 1, 1, 'Database Schema Design', 'Design the MySQL schema for TeamBox.', 3, 6, 'Pending', 'Medium', '2026-10-15', NULL, '2026-10-07 11:11:59'),
(5, 1, 2, 'Android Prototype', 'Build a first Android prototype.', 2, 8, 'Pending', 'High', '2026-10-25', NULL, '2026-10-07 11:11:59'),
(6, 4, 4, 'Write SRS Document', 'Software requirement spec.', 8, 13, 'Completed', 'High', '2026-10-05', NULL, '2026-10-07 11:11:59'),
(7, 4, 4, 'Design Seat Map UI', 'Interactive seat map design.', 9, 13, 'In Progress', 'Medium', '2026-10-15', NULL, '2026-10-07 11:11:59'),
(8, 4, 4, 'Booking API', 'Implement booking API.', 10, 13, 'Pending', 'High', '2026-10-25', NULL, '2026-10-07 11:11:59'),
(9, 5, 5, 'Menu Database', 'Create menu database schema.', 9, 14, 'Completed', 'Medium', '2026-10-01', NULL, '2026-10-07 11:11:59'),
(10, 5, 5, 'Payment Integration', 'Integrate payment gateway.', 12, 14, 'In Progress', 'High', '2026-10-20', NULL, '2026-10-07 11:11:59'),
(11, 5, 5, 'Order Tracking UI', 'Design order tracking screen.', 13, 14, 'Pending', 'Low', '2026-10-30', NULL, '2026-10-07 11:11:59'),
(12, 6, 6, 'Data Collection', 'Collect study material dataset.', 13, 18, 'Completed', 'High', '2026-09-30', NULL, '2026-10-07 11:11:59'),
(13, 6, 6, 'Train Baseline Model', 'Train first ML baseline.', 15, 18, 'In Progress', 'High', '2026-10-25', NULL, '2026-10-07 11:11:59'),
(14, 6, 6, 'Prompt Engineering', 'Design prompt templates.', 16, 18, 'Pending', 'Medium', '2026-11-10', NULL, '2026-10-07 11:11:59'),
(15, 8, 9, 'Company Database', 'Collect company data.', 20, 22, 'Completed', 'Medium', '2026-10-05', NULL, '2026-10-07 11:11:59'),
(16, 8, 9, 'Matching Algorithm', 'Build matching algorithm.', 21, 22, 'In Progress', 'High', '2026-10-28', NULL, '2026-10-07 11:11:59'),
(17, 8, 9, 'Frontend Dashboard', 'Build student dashboard.', 22, 22, 'Pending', 'Medium', '2026-11-15', NULL, '2026-10-07 11:11:59');

-- --------------------------------------------------------

--
-- 表的结构 `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teacher_code` varchar(6) NOT NULL,
  `hire_date` date DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `teacher_code`, `hire_date`, `department`, `title`) VALUES
(1, 1, 'T48291', '2022-09-01', 'Information Technology', 'Senior Lecturer'),
(2, 2, 'T15942', '2020-09-01', 'Information Technology', 'Lecturer'),
(3, 3, 'T73658', '2017-09-01', 'Computer Science', 'Associate Professor'),
(4, 4, 'T20473', '2021-09-01', 'Information Technology', 'Lecturer'),
(5, 5, 'T91826', '2018-09-01', 'Software Engineering', 'Senior Lecturer');

-- --------------------------------------------------------

--
-- 表的结构 `teacher_student`
--

CREATE TABLE `teacher_student` (
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `teacher_student`
--

INSERT INTO `teacher_student` (`teacher_id`, `student_id`, `assigned_at`) VALUES
(1, 1, '2026-10-07 11:11:58'),
(1, 2, '2026-10-07 11:11:58'),
(1, 3, '2026-10-07 11:11:58'),
(1, 4, '2026-10-07 11:11:58'),
(1, 5, '2026-10-07 11:11:58'),
(1, 6, '2026-10-07 11:11:58'),
(1, 7, '2026-10-07 11:11:58'),
(1, 8, '2026-10-07 11:11:58'),
(1, 9, '2026-10-07 11:11:58'),
(1, 10, '2026-10-07 11:11:58'),
(1, 11, '2026-10-07 11:11:58'),
(1, 12, '2026-10-07 11:11:58'),
(2, 13, '2026-10-07 11:11:58'),
(2, 14, '2026-10-07 11:11:58'),
(2, 15, '2026-10-07 11:11:58'),
(2, 16, '2026-10-07 11:11:58'),
(2, 17, '2026-10-07 11:11:58'),
(3, 18, '2026-10-07 11:11:58'),
(3, 19, '2026-10-07 11:11:58'),
(3, 20, '2026-10-07 11:11:58'),
(3, 21, '2026-10-07 11:11:58'),
(4, 22, '2026-10-07 11:11:58'),
(4, 23, '2026-10-07 11:11:58'),
(4, 24, '2026-10-07 11:11:58'),
(5, 25, '2026-10-07 11:11:58');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('teacher','student','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Esther Yuen', 'wyyuen@vtc.edu.hk', '12345', 'teacher', '2026-10-07 11:11:58'),
(2, 'Dr. Alan Ho', 'alanho@vtc.edu.hk', '12345', 'teacher', '2026-10-07 11:11:58'),
(3, 'Prof. Rebecca Lam', 'rebeccalam@vtc.edu.hk', '12345', 'teacher', '2026-10-07 11:11:58'),
(4, 'Dr. Vincent Ng', 'vincentng@vtc.edu.hk', '12345', 'teacher', '2026-10-07 11:11:58'),
(5, 'Ms. Karen Tsang', 'karentsang@vtc.edu.hk', '12345', 'teacher', '2026-10-07 11:11:58'),
(6, 'CHAN Ho Yin', '260222111@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(7, 'CHUI Ka Chun', '260222112@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(8, 'LAI Ka-Yu Savio', '260222113@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(9, 'MIAO Tung', '260222114@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(10, 'WONG Mei Ling', '260222115@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(11, 'LEE Chun Kit', '260222116@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(12, 'NG Wing Yan', '260222117@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(13, 'TAM Chi Hang', '260222201@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(14, 'HO Wing Sze', '260222202@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(15, 'LAM Ka Ho', '260222203@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(16, 'YIP Man Yee', '260222204@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(17, 'SO Chun Ming', '260222205@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(18, 'CHEUNG Ka Yan', '260222206@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(19, 'CHU Ho Yin', '260222207@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(20, 'FUNG Wing Chi', '260222208@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(21, 'KWOK Chi Wai', '260222209@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(22, 'LEUNG Mei Mei', '260222210@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(23, 'MAK Ho Cheung', '260222211@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(24, 'NGAI Ka Wai', '260222212@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(25, 'POON Yuen Ting', '260222213@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(26, 'SIU Chun Hei', '260222214@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(27, 'TSE Wing Lam', '260222215@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(28, 'WU Ka Man', '260222216@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(29, 'YIU Ho Kwan', '260222217@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58'),
(30, 'ZHAO Wei', '260222218@stu.vtc.edu.hk', '12345', 'student', '2026-10-07 11:11:58');

--
-- 转储表的索引
--

--
-- 表的索引 `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `actor_id` (`actor_id`);

--
-- 表的索引 `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `target_group_id` (`target_group_id`),
  ADD KEY `target_project_id` (`target_project_id`);

--
-- 表的索引 `announcement_targets`
--
ALTER TABLE `announcement_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann` (`announcement_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- 表的索引 `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- 表的索引 `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enroll` (`course_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- 表的索引 `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `uploader_id` (`uploader_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- 表的索引 `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `leader_id` (`leader_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- 表的索引 `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`group_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- 表的索引 `group_requests`
--
ALTER TABLE `group_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- 表的索引 `invitations`
--
ALTER TABLE `invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_invite` (`group_id`,`invitee_id`),
  ADD KEY `inviter_id` (`inviter_id`),
  ADD KEY `invitee_id` (`invitee_id`);

--
-- 表的索引 `leader_votes`
--
ALTER TABLE `leader_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`group_id`,`voter_id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- 表的索引 `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `attachment_id` (`attachment_id`);

--
-- 表的索引 `milestones`
--
ALTER TABLE `milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- 表的索引 `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `course_id` (`course_id`);

--
-- 表的索引 `request_members`
--
ALTER TABLE `request_members`
  ADD PRIMARY KEY (`request_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- 表的索引 `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- 表的索引 `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- 表的索引 `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `teacher_code` (`teacher_code`);

--
-- 表的索引 `teacher_student`
--
ALTER TABLE `teacher_student`
  ADD PRIMARY KEY (`teacher_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- 使用表AUTO_INCREMENT `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `announcement_targets`
--
ALTER TABLE `announcement_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `course_enrollments`
--
ALTER TABLE `course_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- 使用表AUTO_INCREMENT `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- 使用表AUTO_INCREMENT `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `group_requests`
--
ALTER TABLE `group_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `invitations`
--
ALTER TABLE `invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `leader_votes`
--
ALTER TABLE `leader_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- 使用表AUTO_INCREMENT `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- 使用表AUTO_INCREMENT `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- 使用表AUTO_INCREMENT `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- 限制导出的表
--

--
-- 限制表 `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activities_ibfk_3` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`target_group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `announcements_ibfk_3` FOREIGN KEY (`target_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- 限制表 `announcement_targets`
--
ALTER TABLE `announcement_targets`
  ADD CONSTRAINT `announcement_targets_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- 限制表 `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- 限制表 `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- 限制表 `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_3` FOREIGN KEY (`uploader_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- 限制表 `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_3` FOREIGN KEY (`leader_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_creator` FOREIGN KEY (`creator_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

--
-- 限制表 `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- 限制表 `group_requests`
--
ALTER TABLE `group_requests`
  ADD CONSTRAINT `group_requests_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_requests_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- 限制表 `invitations`
--
ALTER TABLE `invitations`
  ADD CONSTRAINT `invitations_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invitations_ibfk_2` FOREIGN KEY (`inviter_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invitations_ibfk_3` FOREIGN KEY (`invitee_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- 限制表 `leader_votes`
--
ALTER TABLE `leader_votes`
  ADD CONSTRAINT `leader_votes_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leader_votes_ibfk_2` FOREIGN KEY (`voter_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leader_votes_ibfk_3` FOREIGN KEY (`candidate_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- 限制表 `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`attachment_id`) REFERENCES `files` (`id`) ON DELETE SET NULL;

--
-- 限制表 `milestones`
--
ALTER TABLE `milestones`
  ADD CONSTRAINT `milestones_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- 限制表 `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`supervisor_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- 限制表 `request_members`
--
ALTER TABLE `request_members`
  ADD CONSTRAINT `request_members_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `group_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_members_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- 限制表 `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- 限制表 `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `teacher_student`
--
ALTER TABLE `teacher_student`
  ADD CONSTRAINT `teacher_student_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_student_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
