-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2026 at 10:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fortune_heights`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, '2025/2026', '2025-09-01', '2026-07-31', 1, '2026-06-23 09:29:26');

-- --------------------------------------------------------

--
-- Table structure for table `academic_terms`
--

CREATE TABLE `academic_terms` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_terms`
--

INSERT INTO `academic_terms` (`id`, `session_id`, `term_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, 1, 'Third Term', '2026-06-04', '2026-09-04', 1, '2026-06-23 12:46:46');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('parent','teacher','admin') NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_type`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'admin', 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 09:36:42'),
(2, 2, 'admin', 'create_teacher', 'Created teacher: Bilal Bello', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:44:55'),
(3, 2, 'admin', 'create_parent', 'Created parent: Steven Brown', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:51:51'),
(4, 2, 'admin', 'create_student', 'Created student: Ismail Steve', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:53:04'),
(5, 2, 'admin', 'update_parent', 'Updated parent: Steven Brown', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:54:46'),
(6, 2, 'admin', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:54:52'),
(7, 1, 'teacher', 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:55:15'),
(8, 1, 'teacher', 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:55:49'),
(9, 1, 'parent', 'login', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 10:56:16'),
(10, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 11:24:31'),
(11, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 11:33:55'),
(12, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:06:38'),
(13, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:14:30'),
(14, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:21:43'),
(15, 1, 'parent', 'send_message', 'Sent message to teacher #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:23:36'),
(16, 1, 'parent', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:24:21'),
(17, 2, 'admin', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:26:26'),
(18, 2, 'admin', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:28:10'),
(19, 1, 'teacher', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:28:32'),
(20, 1, 'teacher', 'send_message', 'Sent message to parent #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:29:04'),
(21, 1, 'teacher', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:41:39'),
(22, 1, 'teacher', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:43:11'),
(23, 2, 'admin', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:45:41'),
(24, 2, 'admin', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:47:43'),
(25, 1, 'teacher', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:48:14'),
(26, 1, 'teacher', 'mark_attendance', 'Marked attendance for class 9 on 2026-06-23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:48:37'),
(27, 1, 'teacher', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:49:07'),
(28, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 12:50:01'),
(29, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 13:03:46'),
(30, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 13:07:54'),
(31, 1, 'parent', 'send_message', 'Sent message to teacher #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 13:08:37'),
(32, 1, 'parent', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 13:09:54'),
(33, 2, 'admin', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 13:14:12'),
(34, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 14:11:48'),
(35, 1, 'parent', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 14:12:06'),
(36, 1, 'teacher', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 14:12:25'),
(37, 1, 'teacher', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 14:17:32'),
(38, 1, 'teacher', 'send_message', 'Sent message to parent #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 14:27:27'),
(39, 2, 'admin', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:12:27'),
(40, 2, 'admin', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:14:23'),
(41, 1, 'teacher', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:14:42'),
(42, 1, 'teacher', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:16:21'),
(43, 1, 'parent', 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:17:32'),
(44, 1, 'parent', 'send_message', 'Sent message to teacher #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:18:36'),
(45, 1, 'parent', 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 20:20:40');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `staff_id`, `full_name`, `email`, `phone`, `password`, `avatar`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'ADM001', 'System Administrator', 'admin@fortuneheights.edu.ng', '08012345678', '$2y$12$HKcEUgscJTM/S0.glOYbkuLnhuzRqtOtR5qjb40ePM5MuOpeXGbcC', 'default.png', 1, NULL, '2026-06-23 09:29:26'),
(2, 'ADM002', 'Salisu Shago', 'shago@fortuneheights.edu.ng', '08012345678', '$2y$12$/mY2q0UIvR3bN0Us7M.6Juw.JgtJWhWeGUSO5NN4Us5e6BgbXbOm.', 'default.png', 1, '2026-06-23 13:12:27', '2026-06-23 09:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','parents','teachers') DEFAULT 'all',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('draft','published','scheduled') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `target_audience`, `priority`, `status`, `scheduled_at`, `published_at`, `created_by`, `created_at`) VALUES
(1, 'New Term Alert', 'New Term is starting and we expect every parent to pay fees as expected respectfully', 'all', 'high', 'published', NULL, '2026-06-23 13:27:53', 2, '2026-06-23 12:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `marked_by` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `class_id`, `date`, `status`, `remark`, `marked_by`, `term_id`, `created_at`) VALUES
(1, 1, 9, '2026-06-23', 'Present', NULL, 1, 1, '2026-06-23 12:48:37');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `level` enum('Creche','Nursery','Primary') NOT NULL,
  `capacity` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `class_code`, `level`, `capacity`, `created_at`) VALUES
(1, 'Creche', 'CR01', 'Creche', 15, '2026-06-23 09:29:26'),
(2, 'Nursery 1', 'NY01', 'Nursery', 25, '2026-06-23 09:29:26'),
(3, 'Nursery 2', 'NY02', 'Nursery', 25, '2026-06-23 09:29:26'),
(4, 'Primary 1', 'PR01', 'Primary', 30, '2026-06-23 09:29:26'),
(5, 'Primary 2', 'PR02', 'Primary', 30, '2026-06-23 09:29:26'),
(6, 'Primary 3', 'PR03', 'Primary', 30, '2026-06-23 09:29:26'),
(7, 'Primary 4', 'PR04', 'Primary', 30, '2026-06-23 09:29:26'),
(8, 'Primary 5', 'PR05', 'Primary', 30, '2026-06-23 09:29:26'),
(9, 'Primary 6', 'PR06', 'Primary', 30, '2026-06-23 09:29:26');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `status` enum('pending','responded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('parent','teacher','admin') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_type` enum('parent','teacher','admin') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`, `subject`, `message`, `is_read`, `parent_id`, `created_at`) VALUES
(1, 1, 'parent', 1, 'teacher', 'Question about Homework', 'About my child i wish to focus on homework', 1, NULL, '2026-06-23 12:23:35'),
(2, 1, 'teacher', 1, 'parent', 'Re: Conversation', 'okay noted on it', 1, NULL, '2026-06-23 12:29:04'),
(3, 1, 'parent', 1, 'teacher', 'ffffff', 'frgjksf', 1, NULL, '2026-06-23 13:08:37'),
(4, 1, 'teacher', 1, 'parent', 'Re: Conversation', 'kk', 1, NULL, '2026-06-23 14:27:27'),
(5, 1, 'parent', 1, 'teacher', 'yoo', 'u', 0, NULL, '2026-06-23 20:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('parent','teacher','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('message','attendance','result','announcement','feedback','system') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_type`, `title`, `message`, `type`, `reference_id`, `is_read`, `created_at`) VALUES
(1, 1, 'teacher', 'New Message from Steven Brown', 'About my child i wish to focus on homework...', 'message', 1, 0, '2026-06-23 12:23:35'),
(2, 1, 'parent', 'New Message from Bilal Bello', 'okay noted on it...', 'message', 2, 0, '2026-06-23 12:29:04'),
(3, 1, 'teacher', 'New Message from Steven Brown', 'frgjksf...', 'message', 3, 0, '2026-06-23 13:08:37'),
(4, 1, 'parent', 'New Message from Bilal Bello', 'kk', 'message', 4, 0, '2026-06-23 14:27:27'),
(5, 1, 'teacher', 'New Message from Steven Brown', 'u', 'message', 5, 0, '2026-06-23 20:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `parent_id` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `parent_id`, `full_name`, `email`, `phone`, `gender`, `occupation`, `address`, `password`, `avatar`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'PAR5680', 'Steven Brown', 'steve@fortuneheights.edu.ng', '08135874172', 'Male', 'Lawyer', 'Ikorodu', '$2y$12$GQya/JjpSH.0Q1HzjCsjlOzlDKbLMwi.e.gUdYEwLfVQW1CqmK03a', 'default.png', 1, '2026-06-23 13:17:32', '2026-06-23 10:51:51');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `ca_score` decimal(5,2) DEFAULT 0.00,
  `exam_score` decimal(5,2) DEFAULT 0.00,
  `total_score` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `remark` varchar(100) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `class_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `address` text DEFAULT NULL,
  `medical_info` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `admission_no`, `full_name`, `gender`, `date_of_birth`, `class_id`, `parent_id`, `admission_date`, `address`, `medical_info`, `avatar`, `is_active`, `created_at`) VALUES
(1, 'STD7378', 'Ismail Steve', 'Male', '2019-04-01', 9, 1, '2026-06-23', 'Ikorodu', 'Healthy', 'default.png', 1, '2026-06-23 10:53:04');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `class_id`, `teacher_id`, `created_at`) VALUES
(1, 'English Language', 'ENG1', 1, NULL, '2026-06-23 13:17:33'),
(2, 'Mathematics', 'MTH1', 1, NULL, '2026-06-23 13:17:50'),
(3, 'Phonics', 'PHN1', 1, NULL, '2026-06-23 13:18:59'),
(4, 'Handwriting', 'HND1', 1, NULL, '2026-06-23 13:19:25'),
(5, 'Songs &amp; Rhymes', 'SRM1', 1, NULL, '2026-06-23 13:20:17'),
(6, 'English Language', 'ENG2', 2, NULL, '2026-06-23 13:20:47'),
(7, 'Mathematics', 'MTH2', 2, NULL, '2026-06-23 13:21:03'),
(8, 'Phonics', 'PHN2', 2, NULL, '2026-06-23 13:21:20'),
(9, 'Handwriting', 'HND2', 2, NULL, '2026-06-23 13:21:39'),
(10, 'Songs &amp; Rhymes', 'SRM2', 2, NULL, '2026-06-23 13:21:57'),
(11, 'Health Habits', 'HEH2', 2, NULL, '2026-06-23 13:22:26'),
(12, 'Social Habits', 'SOC2', 2, NULL, '2026-06-23 13:22:53'),
(13, 'English Language', 'ENG3', 3, NULL, '2026-06-23 13:23:19'),
(14, 'Mathematics', 'MTH3', 3, NULL, '2026-06-23 13:23:33'),
(15, 'Phonics', 'PHN3', 3, NULL, '2026-06-23 13:23:52'),
(16, 'Health Habits', 'HEH3', 3, NULL, '2026-06-23 13:24:12'),
(17, 'Social Habits', 'SOC3', 3, NULL, '2026-06-23 13:24:32'),
(18, 'English Language', 'ENG4', 4, NULL, '2026-06-23 13:25:24'),
(19, 'Mathematics', 'MTH4', 4, NULL, '2026-06-23 13:25:38'),
(20, 'Physical And Health Education', 'PHE1', 4, NULL, '2026-06-23 13:26:16'),
(21, 'Social Studies', 'SOC4', 4, NULL, '2026-06-23 13:26:43'),
(22, 'Basic Science', 'BSC1', 4, NULL, '2026-06-23 13:27:10'),
(23, 'Civic Education', 'CIV1', 4, NULL, '2026-06-23 13:27:33'),
(24, 'Verbal Reasoning', 'VER1', 4, NULL, '2026-06-23 13:27:57'),
(25, 'Quantitative Reasoning', 'QUA1', 4, NULL, '2026-06-23 13:28:18'),
(26, 'English Language', 'ENG5', 5, NULL, '2026-06-23 13:28:43'),
(27, 'Mathematics', 'MTH5', 5, NULL, '2026-06-23 13:28:57'),
(28, 'Social Studies', 'SOC5', 5, NULL, '2026-06-23 13:29:25'),
(29, 'Physical And Health Education', 'PHE2', 5, NULL, '2026-06-23 13:29:49'),
(30, 'Quantitative Reasoning', 'QUA2', 5, NULL, '2026-06-23 13:30:08'),
(31, 'Verbal Reasoning', 'VER2', 5, NULL, '2026-06-23 13:30:26'),
(32, 'Civic Education', 'CIV2', 5, NULL, '2026-06-23 13:30:43'),
(33, 'Basic Science', 'BSC2', 5, NULL, '2026-06-23 13:31:56'),
(34, 'English Language', 'ENG6', 6, NULL, '2026-06-23 13:32:35'),
(35, 'Mathematics', 'MTH6', 6, NULL, '2026-06-23 13:32:56'),
(36, 'Physical And Health Education', 'PHE3', 6, NULL, '2026-06-23 13:33:24'),
(37, 'Basic Science', 'BSC3', 6, NULL, '2026-06-23 13:33:42'),
(38, 'Quantitative Reasoning', 'QUA3', 6, NULL, '2026-06-23 13:34:01'),
(39, 'Verbal Reasoning', 'VER3', 6, NULL, '2026-06-23 13:34:18'),
(40, 'Civic Education', 'CIV3', 6, NULL, '2026-06-23 13:34:42'),
(41, 'Social Studies', 'SOC6', 6, NULL, '2026-06-23 13:35:19'),
(42, 'English Language', 'ENG7', 7, NULL, '2026-06-23 13:36:17'),
(43, 'Mathematics', 'MTH7', 7, NULL, '2026-06-23 13:36:30'),
(44, 'Physical And Health Education', 'PHE4', 7, NULL, '2026-06-23 13:36:53'),
(45, 'Basic Science', 'BSC4', 7, NULL, '2026-06-23 13:37:18'),
(46, 'Civic Education', 'CIV4', 7, NULL, '2026-06-23 13:37:37'),
(47, 'Quantitative Reasoning', 'QUA4', 7, NULL, '2026-06-23 13:38:45'),
(48, 'Verbal Reasoning', 'VER4', 7, NULL, '2026-06-23 13:39:00'),
(49, 'Computer Studies', 'COM1', 7, NULL, '2026-06-23 13:39:37'),
(51, 'English Language', 'ENG8', 8, NULL, '2026-06-23 13:40:40'),
(52, 'Mathematics', 'MTH8', 8, NULL, '2026-06-23 13:41:04'),
(53, 'Physical And Health Education', 'PHE5', 8, NULL, '2026-06-23 13:41:25'),
(54, 'Basic Science', 'BSC5', 8, NULL, '2026-06-23 13:41:40'),
(55, 'Civic Education', 'CIV5', 8, NULL, '2026-06-23 13:41:59'),
(56, 'Verbal Reasoning', 'VER5', 8, NULL, '2026-06-23 13:42:13'),
(57, 'Quantitative Reasoning', 'QUA5', 8, NULL, '2026-06-23 13:42:29'),
(58, 'Computer Studies', 'COM2', 8, NULL, '2026-06-23 13:43:18'),
(59, 'English Language', 'ENG9', 9, 1, '2026-06-23 13:43:34'),
(60, 'Mathematics', 'MTH9', 9, 1, '2026-06-23 13:43:49'),
(61, 'Physical And Health Education', 'PHE6', 9, 1, '2026-06-23 13:44:09'),
(62, 'Basic Science', 'BSC6', 9, 1, '2026-06-23 13:48:17'),
(63, 'Civic Education', 'CIV6', 9, 1, '2026-06-23 13:48:33'),
(64, 'Verbal Reasoning', 'VER6', 9, 1, '2026-06-23 13:48:47'),
(65, 'Quantitative Reasoning', 'QUA6', 9, 1, '2026-06-23 13:49:05'),
(66, 'Computer Studies', 'COM3', 9, 1, '2026-06-23 13:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `class_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `staff_id`, `full_name`, `email`, `phone`, `gender`, `qualification`, `password`, `avatar`, `class_id`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'TCH5592', 'Bilal Bello', 'bmb@fortuneheights.edu.ng', '0812345678', 'Male', 'HND', '$2y$12$cmh1smWl.lLzz6BlZjJl0.vAq0M.D6zOQkxUVFIM5popTxDCSpbTa', 'default.png', 9, 1, '2026-06-23 13:14:42', '2026-06-23 10:44:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_log` (`user_id`,`user_type`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receiver` (`receiver_id`,`receiver_type`),
  ADD KEY `idx_sender` (`sender_id`,`sender_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`,`user_type`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parent_id` (`parent_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`subject_id`,`term_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`),
  ADD KEY `idx_class` (`class_id`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `class_id` (`class_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `academic_terms`
--
ALTER TABLE `academic_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD CONSTRAINT `academic_terms_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_4` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_4` FOREIGN KEY (`uploaded_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
