-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2025 at 02:51 PM
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
-- Database: `clinic_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` varchar(100) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','declined','rescheduled','confirmed') DEFAULT 'pending',
  `email` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `doctor_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT 'Dr. Sarah Johnson',
  `faculty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_visits`
--

CREATE TABLE `clinic_visits` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `visit_reason` varchar(500) DEFAULT NULL,
  `visit_type` enum('appointment','prescription','walk_in','emergency') DEFAULT 'appointment',
  `staff_member` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_visits`
--

INSERT INTO `clinic_visits` (`id`, `patient_id`, `patient_name`, `visit_date`, `visit_time`, `visit_reason`, `visit_type`, `staff_member`, `notes`, `created_at`, `updated_at`) VALUES
(9, 21, 'Test Student', '2025-08-18', NULL, 'Fever and headache', 'prescription', 'Staff', NULL, '2025-08-18 20:13:07', '2025-08-18 20:13:07'),
(10, 21, 'Abella, Joseph B.', '2025-08-18', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-18 20:13:41', '2025-08-18 20:13:41'),
(11, 21, 'Abella, Joseph B.', '2025-08-18', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-18 20:14:00', '2025-08-18 20:14:00'),
(12, 21, 'Abella, Joseph B.', '2025-08-18', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-18 20:14:16', '2025-08-18 20:14:16'),
(13, 21, 'Abella, Joseph B.', '2025-08-18', NULL, 'fevcer', 'prescription', 'Staff', NULL, '2025-08-18 20:33:09', '2025-08-18 20:33:09'),
(14, 21, 'Abella, Joseph B.', '2025-08-18', NULL, 'fever', 'prescription', 'Staff', NULL, '2025-08-18 20:49:06', '2025-08-18 20:49:06'),
(15, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Sakits ulo', 'prescription', 'Staff', NULL, '2025-08-19 07:35:33', '2025-08-19 07:35:33'),
(16, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Qui occaecat magna v', 'prescription', 'Staff', NULL, '2025-08-19 07:37:15', '2025-08-19 07:37:15'),
(17, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Dolore consequuntur ', 'prescription', 'Staff', NULL, '2025-08-19 07:37:31', '2025-08-19 07:37:31'),
(18, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Doloribus praesentiu', 'prescription', 'Staff', NULL, '2025-08-19 07:37:49', '2025-08-19 07:37:49'),
(19, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Recusandae Rerum te', 'prescription', 'Staff', NULL, '2025-08-19 07:38:02', '2025-08-19 07:38:02'),
(20, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Enim qui eum asperio', 'prescription', 'Staff', NULL, '2025-08-19 07:38:16', '2025-08-19 07:38:16'),
(21, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'fever', 'prescription', 'Staff', NULL, '2025-08-19 07:40:10', '2025-08-19 07:40:10'),
(22, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Assumenda a ipsa as', 'prescription', 'Staff', NULL, '2025-08-19 08:11:22', '2025-08-19 08:11:22'),
(23, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Repudiandae unde lau', 'prescription', 'Staff', NULL, '2025-08-19 08:13:01', '2025-08-19 08:13:01'),
(24, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Ab nihil alias accus', 'prescription', 'Staff', NULL, '2025-08-19 08:41:54', '2025-08-19 08:41:54'),
(25, 22, 'Abellana, Vincent Anthony Q.', '2025-08-19', NULL, 'Adipisci soluta est', 'prescription', 'Staff', NULL, '2025-08-19 08:42:17', '2025-08-19 08:42:17'),
(26, 21, 'Abella, Joseph B.', '2025-08-19', NULL, 'Neque provident sae', 'prescription', 'Staff', NULL, '2025-08-19 08:42:29', '2025-08-19 08:42:29'),
(27, 22, 'Abellana, Vincent Anthony Q.', '2025-08-19', NULL, 'Laboriosam culpa si', 'prescription', 'Staff', NULL, '2025-08-19 08:42:53', '2025-08-19 08:42:53'),
(28, 23, 'Abendan, Christian James A.', '2025-08-19', NULL, 'Corrupti debitis qu', 'prescription', 'Staff', NULL, '2025-08-19 08:58:05', '2025-08-19 08:58:05'),
(29, 23, 'Abendan, Christian James A.', '2025-08-19', NULL, 'Sit assumenda quod ', 'prescription', 'Staff', NULL, '2025-08-19 08:58:17', '2025-08-19 08:58:17'),
(30, 23, 'Abendan, Christian James A.', '2025-08-19', NULL, 'Maiores ex sint sed ', 'prescription', 'Staff', NULL, '2025-08-19 08:58:28', '2025-08-19 08:58:28'),
(31, 31, 'Alicaya, Ralph Lorync D.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 10:58:25', '2025-08-26 10:58:25'),
(32, 31, 'Alicaya, Ralph Lorync D.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 10:58:41', '2025-08-26 10:58:41'),
(33, 31, 'Alicaya, Ralph Lorync D.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 10:58:57', '2025-08-26 10:58:57'),
(34, 31, 'Alicaya, Ralph Lorync D.', '2025-08-26', NULL, 'fever', 'prescription', 'Staff', NULL, '2025-08-26 14:51:47', '2025-08-26 14:51:47'),
(35, 31, 'Alicaya, Ralph Lorync D.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 14:59:20', '2025-08-26 14:59:20'),
(36, 31, 'Alicaya, Ralph Lorync D.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 15:02:22', '2025-08-26 15:02:22'),
(37, 32, 'Baraclan, Genesis S.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 15:02:58', '2025-08-26 15:02:58'),
(38, 32, 'Baraclan, Genesis S.', '2025-08-26', NULL, 'Molestiae ut fugiat', 'prescription', 'Staff', NULL, '2025-08-26 15:03:19', '2025-08-26 15:03:19'),
(39, 32, 'Baraclan, Genesis S.', '2025-08-26', NULL, 'asd', 'prescription', 'Staff', NULL, '2025-08-26 15:03:33', '2025-08-26 15:03:33'),
(40, 31, 'Alicaya, Ralph Lorync D.', '2025-08-28', NULL, 'fever', 'prescription', 'Staff', NULL, '2025-08-28 16:44:03', '2025-08-28 16:44:03'),
(42, 40, 'Arcamo Jr., Emmanuel P.', '2025-10-02', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-02 18:07:52', '2025-10-02 18:07:52'),
(43, 21, 'Abella, Joseph B.', '2025-10-02', NULL, 'Eum anim iste assume', 'prescription', 'Staff', NULL, '2025-10-02 20:33:43', '2025-10-02 20:33:43'),
(44, 21, 'Abella, Joseph B.', '2025-10-02', NULL, 'Mollit consequatur ', 'prescription', 'Staff', NULL, '2025-10-02 20:33:54', '2025-10-02 20:33:54'),
(45, 21, 'Abella, Joseph B.', '2025-10-02', NULL, 'Ut provident natus ', 'prescription', 'Staff', NULL, '2025-10-02 20:34:03', '2025-10-02 20:34:03'),
(46, 25, 'Abellana, Ariel L', '2025-10-02', NULL, 'Ut eligendi eu earum', 'prescription', 'Staff', NULL, '2025-10-02 20:34:16', '2025-10-02 20:34:16'),
(47, 25, 'Abellana, Ariel L', '2025-10-02', NULL, 'Voluptatem sequi ut', 'prescription', 'Staff', NULL, '2025-10-02 20:34:24', '2025-10-02 20:34:24'),
(48, 25, 'Abellana, Ariel L', '2025-10-02', NULL, 'Optio consequat Qu', 'prescription', 'Staff', NULL, '2025-10-02 20:34:32', '2025-10-02 20:34:32'),
(49, 22, 'Abellana, Vincent Anthony Q.', '2025-10-02', NULL, 'Voluptas debitis ips', 'prescription', 'Staff', NULL, '2025-10-02 20:34:42', '2025-10-02 20:34:42'),
(50, 22, 'Abellana, Vincent Anthony Q.', '2025-10-02', NULL, 'Ut sed omnis ipsum a', 'prescription', 'Staff', NULL, '2025-10-02 20:34:50', '2025-10-02 20:34:50'),
(51, 22, 'Abellana, Vincent Anthony Q.', '2025-10-02', NULL, 'Quos sit nisi deser', 'prescription', 'Staff', NULL, '2025-10-02 20:34:57', '2025-10-02 20:34:57'),
(52, 23, 'Abendan, Christian James A.', '2025-10-02', NULL, 'Qui quidem ab vel ad', 'prescription', 'Staff', NULL, '2025-10-02 20:35:06', '2025-10-02 20:35:06'),
(53, 23, 'Abendan, Christian James A.', '2025-10-02', NULL, 'Et et et omnis sit e', 'prescription', 'Staff', NULL, '2025-10-02 20:35:14', '2025-10-02 20:35:14'),
(54, 23, 'Abendan, Christian James A.', '2025-10-02', NULL, 'Tempor dolore conseq', 'prescription', 'Staff', NULL, '2025-10-02 20:35:23', '2025-10-02 20:35:23'),
(55, 24, 'Abendan, Nino Rashean T.', '2025-10-02', NULL, 'Aperiam commodi eum ', 'prescription', 'Staff', NULL, '2025-10-02 20:35:40', '2025-10-02 20:35:40'),
(56, 24, 'Abendan, Nino Rashean T.', '2025-10-02', NULL, 'Itaque rerum nihil d', 'prescription', 'Staff', NULL, '2025-10-02 20:35:48', '2025-10-02 20:35:48'),
(57, 24, 'Abendan, Nino Rashean T.', '2025-10-02', NULL, 'Animi quidem quo vo', 'prescription', 'Staff', NULL, '2025-10-02 20:35:57', '2025-10-02 20:35:57'),
(58, 26, 'Acidillo, Baby John V.', '2025-10-02', NULL, 'Eligendi aliquid err', 'prescription', 'Staff', NULL, '2025-10-02 20:36:16', '2025-10-02 20:36:16'),
(59, 26, 'Acidillo, Baby John V.', '2025-10-02', NULL, 'Asperiores mollit ac', 'prescription', 'Staff', NULL, '2025-10-02 20:36:24', '2025-10-02 20:36:24'),
(60, 26, 'Acidillo, Baby John V.', '2025-10-02', NULL, 'Veniam aute illum ', 'prescription', 'Staff', NULL, '2025-10-02 20:36:33', '2025-10-02 20:36:33'),
(61, 35, 'Adlawan, Ealla Marie', '2025-10-02', NULL, 'Cupiditate facilis a', 'prescription', 'Staff', NULL, '2025-10-02 20:36:54', '2025-10-02 20:36:54'),
(62, 35, 'Adlawan, Ealla Marie', '2025-10-02', NULL, 'Eaque architecto ut ', 'prescription', 'Staff', NULL, '2025-10-02 20:37:05', '2025-10-02 20:37:05'),
(63, 35, 'Adlawan, Ealla Marie', '2025-10-02', NULL, 'Et sapiente minim pr', 'prescription', 'Staff', NULL, '2025-10-02 20:37:16', '2025-10-02 20:37:16'),
(64, 27, 'Adona, Carl Macel C.', '2025-10-02', NULL, 'Commodi voluptates i', 'prescription', 'Staff', NULL, '2025-10-02 20:37:54', '2025-10-02 20:37:54'),
(65, 27, 'Adona, Carl Macel C.', '2025-10-02', NULL, 'Repudiandae nisi eum', 'prescription', 'Staff', NULL, '2025-10-02 20:38:09', '2025-10-02 20:38:09'),
(66, 27, 'Adona, Carl Macel C.', '2025-10-02', NULL, 'Voluptas fugiat vol', 'prescription', 'Staff', NULL, '2025-10-02 20:38:30', '2025-10-02 20:38:30'),
(67, 30, 'Aguilar, Jaymar C', '2025-10-02', NULL, 'Voluptas corrupti r', 'prescription', 'Staff', NULL, '2025-10-02 20:38:50', '2025-10-02 20:38:50'),
(68, 30, 'Aguilar, Jaymar C', '2025-10-02', NULL, 'Eos aliqua Alias d', 'prescription', 'Staff', NULL, '2025-10-02 20:39:03', '2025-10-02 20:39:03'),
(69, 30, 'Aguilar, Jaymar C', '2025-10-02', NULL, 'Nisi sunt dolorum e', 'prescription', 'Staff', NULL, '2025-10-02 20:39:14', '2025-10-02 20:39:14'),
(70, 28, 'Albiso, Creshell Mary M.', '2025-10-02', NULL, 'Omnis odit molestiae', 'prescription', 'Staff', NULL, '2025-10-02 20:40:24', '2025-10-02 20:40:24'),
(71, 28, 'Albiso, Creshell Mary M.', '2025-10-02', NULL, 'Et libero irure labo', 'prescription', 'Staff', NULL, '2025-10-02 20:40:34', '2025-10-02 20:40:34'),
(72, 28, 'Albiso, Creshell Mary M.', '2025-10-02', NULL, 'Proident ex sunt op', 'prescription', 'Staff', NULL, '2025-10-02 20:40:41', '2025-10-02 20:40:41'),
(73, 29, 'Alegado, John Raymon B.', '2025-10-02', NULL, 'Deserunt explicabo ', 'prescription', 'Staff', NULL, '2025-10-02 20:41:33', '2025-10-02 20:41:33'),
(74, 29, 'Alegado, John Raymon B.', '2025-10-02', NULL, 'Dolores dolores accu', 'prescription', 'Staff', NULL, '2025-10-02 20:41:46', '2025-10-02 20:41:46'),
(75, 29, 'Alegado, John Raymon B.', '2025-10-02', NULL, 'Sunt exercitation co', 'prescription', 'Staff', NULL, '2025-10-02 20:42:10', '2025-10-02 20:42:10'),
(76, 21, 'Abella, Joseph B.', '2025-10-02', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-02 21:14:24', '2025-10-02 21:14:24'),
(77, 21, 'Abella, Joseph B.', '2025-10-02', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-02 21:14:55', '2025-10-02 21:14:55'),
(78, 25, 'Abellana, Ariel L', '2025-10-02', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-02 21:15:59', '2025-10-02 21:15:59'),
(79, 22, 'Abellana, Vincent Anthony Q.', '2025-10-02', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-02 21:21:57', '2025-10-02 21:21:57'),
(80, 21, 'Abella, Joseph B.', '2025-10-08', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-08 19:07:47', '2025-10-08 19:07:47'),
(81, 21, 'Abella, Joseph B.', '2025-10-08', NULL, 'test', 'prescription', 'Staff', NULL, '2025-10-08 19:14:43', '2025-10-08 19:14:43');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `schedule_date` date NOT NULL,
  `schedule_time` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `profession` varchar(100) DEFAULT 'Physician'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_name`, `schedule_date`, `schedule_time`, `created_at`, `profession`) VALUES
(103, 'jade', '2025-10-02', '21:44-22:44', '2025-09-26 21:44:12', 'Physician'),
(116, 'Cedric Getuaban', '2025-10-02', '07:52-19:52', '2025-10-02 19:52:49', 'Ophthalmologist'),
(117, 'Rhona', '2025-10-02', '07:56-19:56', '2025-10-02 19:56:33', 'Physician'),
(118, 'GWAPO', '2025-10-02', '07:58-19:58', '2025-10-02 19:58:19', 'Ophthalmologist'),
(147, 'Cedric Getuaban', '2025-10-05', '10:39-22:39', '2025-10-04 22:39:19', 'Dentist'),
(148, 'jade', '2025-10-04', '10:49-22:49', '2025-10-04 22:49:05', 'Physician'),
(149, 'Cedric Getuaban', '2025-10-10', '10:38-22:38', '2025-10-10 10:38:51', 'Dentist');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `emergency_contact` varchar(20) NOT NULL,
  `age` int(11) DEFAULT NULL CHECK (`age` > 0),
  `department` enum('JHS','SHS','College') NOT NULL,
  `college_course` enum('BSIT','BSBA','BEED','BSED','BSHTM','BSCRIM','BSN') DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced') NOT NULL,
  `citizenship` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Profile image file path/URL for faculty admin users',
  `username` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `full_name`, `address`, `contact`, `emergency_contact`, `age`, `department`, `college_course`, `gender`, `email`, `password`, `civil_status`, `citizenship`, `profile_image`, `username`, `phone`) VALUES
(1, 'Maria Santos', '123 Mabini St., Cebu City', '09171234567', '09187654321', 35, 'College', 'BSIT', 'Female', 'maria.santos@scci.edu.ph', '', 'Single', 'Filipino', NULL, NULL, NULL),
(2, 'Perry Nelson', 'Adipisicing sunt eos', 'Enim cillum mollitia', 'Nisi a pariatur Vol', 92, 'SHS', '', 'Male', 'zuqyxyrax@mailinator.com', '', 'Divorced', 'Sed aliquip dolores', NULL, NULL, NULL),
(3, 'Whoopi Harrell', 'Delectus omnis inci', 'Dolorem quibusdam qu', 'Fugiat nihil distinc', 24, 'JHS', '', 'Male', 'qacenagyne@mailinator.com', '', 'Single', 'Ut quos nemo nisi qu', NULL, NULL, NULL),
(4, 'Ayanna Pearson', 'Consequatur excepte', 'Est culpa sint lau', 'Non ut et voluptas p', 32, 'SHS', '', 'Female', 'syxy@mailinator.com', '', 'Single', 'Nostrum ipsa magni', NULL, NULL, NULL),
(5, 'Basia Robbins', 'Ut earum et doloremq', 'Voluptate sunt reru', 'Cum rem quis ea nesc', 63, 'College', 'BSBA', 'Other', 'tykyp@mailinator.com', '', 'Divorced', 'Est earum eveniet u', NULL, NULL, NULL),
(6, 'Kitra Hardy', 'Cum laudantium id', 'In aspernatur ad vit', 'Optio sed consequat', 74, 'College', 'BEED', 'Male', 'pylezoke@mailinator.com', '', 'Widowed', 'Dolorum labore nemo', NULL, NULL, NULL),
(8, 'Holmes Leon', 'Autem maxime nostrum', 'Duis dolore culpa n', 'Occaecat dolore veni', 67, 'JHS', '', 'Male', 'kuhadyril@mailinator.com', '$2y$10$KGq5qUcVqh11Mir5ions5emyaIiKBryz9RMyLbfDbbyu7ZELKA9FS', 'Single', 'Voluptate et aut est', NULL, NULL, NULL),
(9, 'Cedric Pinili monggoloid', 'Acoy Vito', 'Voluptate ut autem a', 'Nihil est excepturi', 33, 'College', 'BSBA', 'Male', 'cedricjade13@gmail.com', '$2y$10$bZqTeY7ipqkzk6LUTD75Zu/Q2M7tWW8npWTC7QsDBN74lfbfGHIwq', '', 'Reprehenderit elit', 'uploads/profiles/faculty_9_1759766537.jpg', NULL, NULL),
(10, 'Rylee Whitley', 'Quo doloribus iure i', 'Hic illum duis omni', 'Elit asperiores id', 93, 'JHS', '', 'Other', 'test@gmail.com', '$2y$10$Txt16FBnV6bl.B22q09EJu0QzeLWLG1GE.xeSCN9Fs4.Z7oNmL.oC', 'Married', 'Id voluptatem modi', 'uploads/profiles/faculty_10_1757671765.png', NULL, NULL),
(11, 'Castor Goodman', 'Necessitatibus susci', 'Voluptas beatae sit', 'Officiis ea optio r', 2, 'JHS', '', 'Male', 'gefivotec@mailinator.com', '$2y$10$thZNUQwFVxq3hDHWcoUSGevlq40KAbUNJh4mTMkWJ4hm7brTYtTWW', 'Married', 'Nisi est quia ad ess', NULL, NULL, NULL),
(12, 'Emmanuel Levy', 'Odio ea amet maiore', 'Sit labore possimus', 'Nihil obcaecati dolo', 53, 'College', 'BSIT', 'Other', 'kinemanu@mailinator.com', '$2y$10$.U0v3usUk6vuZeaZyhM9lOUkVt9/VgPeCEmHJpuwjll0sNRAH/oAq', 'Widowed', 'Voluptatem commodi d', NULL, NULL, NULL),
(13, 'Elvis Long', 'Veniam qui quia ver', 'Quia dolores magna a', 'Anim commodi consequ', 2, 'SHS', '', 'Other', 'kunupuwyjy@mailinator.com', '$2y$10$.Gs1hIwUtwzd2LRqKNbrBOv3gqJKbkXnxUJ5M1jZ8sjT/A91M/.62', 'Divorced', 'Consequatur Aliqua', NULL, NULL, NULL),
(14, 'Demetria Sanford', 'Esse esse quas cons', 'Cillum quam est sed', 'Necessitatibus magna', 89, 'JHS', '', 'Other', 'muniru@mailinator.com', '$2y$10$lKROl2YgIfd3AWrpKb9DSOKSvuUogNSDqYCBH1AgvIX/ASkMv2/V.', 'Divorced', 'Nihil et labore maio', NULL, NULL, NULL),
(15, 'chean lisondra', 'jay@gmail.com', '09123456789', '09876543212', 33, 'College', 'BSIT', 'Female', 'chean@gmail.com', '$2y$10$QIxJDGsm2MDRzU8Gmgw8OuWUpJWdG2Y.qbFgDNrsQ.Ze4YophxUBi', 'Married', 'filipino', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `imported_patients`
--

CREATE TABLE `imported_patients` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT NULL,
  `course_program` varchar(255) DEFAULT NULL,
  `civil_status` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `year_level` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `upload_year` varchar(9) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Profile image file path/URL for student admin users'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `imported_patients`
--

INSERT INTO `imported_patients` (`id`, `student_id`, `name`, `dob`, `gender`, `address`, `email`, `parent_email`, `parent_phone`, `contact_number`, `religion`, `citizenship`, `course_program`, `civil_status`, `password`, `year_level`, `guardian_name`, `guardian_contact`, `emergency_contact_name`, `emergency_contact_number`, `upload_year`, `profile_image`) VALUES
(21, 'SCC-22-00015336', 'Abella, Joseph B.', '2000-03-19', 'Male', 'Camarin Vito Minglanilla Cebu', 'joseph.abella@gmail.com', 'abella.maria@gmail.com', '09170000001', '09172345678', 'Roman Catholic', 'Filipino', 'BSHM', 'Single', '$2y$10$zLuwaOC3mH1cjhXdjLVPHuK75i0N17ZVEhtpB52NSllEZwlYg7esG', '1st Year', 'Maria Abella', '09181234567', 'Juan Abella', '09987654321', NULL, NULL),
(22, 'SCC-22-00017358', 'Abellana, Vincent Anthony Q.', '2002-07-08', 'Male', 'Pakigne Minglanilla Cebu', 'vincentanthony.abellana@gmail.com', 'abellana.juan@gmail.com', '09170000002', '09354567890', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$uiCOjiBIc89IA/GvHPc8ZOJ70L.ysj0fWXniSkanE4lL/tW7Ldz3G', '1st Year', 'Roberto Abellana', '09213456789', 'Grace Abellana', '09123456789', NULL, NULL),
(23, 'SCC-20-00010846', 'Abendan, Christian James A.', '2004-04-27', 'Male', 'Pob. Ward 2 Minglanilla, Cebu', 'christianjames.abendan@gmail.com', 'abendan.rosario@gmail.com', '09170000003', '09475678901', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$7C8fflR.Nc6TImIRHkcOA.PxRhE5LjRbYuThGwPwbPG72FbtdyH76', '1st Year', 'Lourdes Abendan', '09354567890', 'Michael Abendan', '09221234567', NULL, NULL),
(24, 'SCC-14-0001275', 'Abendan, Nino Rashean T.', '2002-02-12', 'Male', 'Ward 2 pob., Minglanilla, Cebu ', 'ninorashean.abendan@gmail.com', 'abendan.carlo@gmail.com', '09170000004', '09566789012', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$GJEDpHlRcKzQwCTmzQfUueffjF0jIytRulJKXHv2XHPexTMeex5aq', '1st Year', 'Antonio Abendan', '09475678901', 'Fatima Abendan', '09331234567', NULL, NULL),
(25, 'SCC-21-00012754', 'Abellana, Ariel L', '2002-10-01', 'Male', 'Basak, Sibonga, Cebu', 'ariel.abellana@gmail.com', 'abellana.luz@gmail.com', '09170000005', '09213456789', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$C8RdzZaGS02XLI5z/MSOZuGfHl0Zef7emZq9HBwijCUYgV0tHOZ.a', '2nd Year', 'Elena Abellana', '09566789012', 'Mario Abellana', '09441234567', NULL, NULL),
(26, 'SCC-21-00012377', 'Acidillo, Baby John V.', '2000-07-21', 'Male', 'Bairan City of Naga', 'babyjohn.acidillo@gmail.com', 'acidillo.jose@gmail.com', '09170000006', '09657890123', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$PioqTa4h6bSTXYAyKqwYVu9JuILw3APsUjtp0FyPxa0/tSjmIeyCi', '2nd Year', 'Victor Acidillo', '09657890123', 'Ana Acidillo', '09551234567', NULL, NULL),
(27, 'SCC-21-00014490', 'Adona, Carl Macel C.', '2002-03-29', 'Male', 'Pob. Ward IV Minglanilla Cebu', 'carlmacel.adona@gmail.com', 'adona.elena@gmail.com', '09170000007', '09919012345', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$E0jBLTDFD6tcAFpqqH6ZIOS.j//HPq1m2PQ.iWvhF9069gsUjUt5W', '2nd Year', 'Rosalinda Adona', '09778901234', 'Carlos Adona', '09661234567', NULL, NULL),
(28, 'SCC-19-0009149', 'Albiso, Creshell Mary M.', '2003-06-18', 'Female', 'Bairan, City of Naga, Cebu', 'creshellmary.albiso@gmail.com', 'albiso.raul@gmail.com', '09170000008', '09234567891', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$383jG1yyAQYAp18ZpHVGceNiW2UJk32VhQn213UL/XimQA/VbiOoa', '2nd Year', 'Carmelita Albiso', '09919012345', 'Pedro Albiso', '09771234567', NULL, NULL),
(29, 'SCC-21-00014673', 'Alegado, John Raymon B.', '2002-01-09', 'Male', 'Tagjaguimit City of Naga Cebu', 'johnraymon.alegado@gmail.com', 'alegado.sofia@gmail.com', '09170000009', '09365678902', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$feXjN/akd/GnwW13nlrnxuRRmOm3Pd/Cynqla23aZDX4hyLoc3Mc2', '2nd Year', 'Benjamin Alegado', '09123456780', 'Marissa Alegado', '09881234567', NULL, NULL),
(30, 'SCC-18-0007848', 'Aguilar, Jaymar C', '2000-02-22', 'Male', 'North Poblacion, San Fernando, Cebu', 'jaymar.aguilar@gmail.com', 'aguilar.pedro@gmail.com', '09170000010', '09123456780', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$DV7K6IKb08ikbQOoDOMyt.2KXzpHZl8MVAxnSwRwJiuoDaLIp7r8W', '3rd Year', 'Cynthia Aguilar', '09234567891', 'Joseph Aguilar', '09991234567', NULL, NULL),
(31, 'SCC-18-0006048', 'Alicaya, Ralph Lorync D.', '2000-01-17', 'Male', 'Lower Pakigne, Minglanilla Cebu', 'ralphlorync.alicaya@gmail.com', 'alicaya.lorena@gmail.com', '09170000011', '09577890124', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$hnlKNK.IzrdzVzyOMXyvHeYVtItbi/mP7qLHDFuxyVPgt5derxlzy', '3rd Year', 'Daniel Alicaya', '09365678902', 'Sophia Alicaya', '09102345678', NULL, NULL),
(32, 'SCC-20-00011552', 'Baraclan, Genesis S.', '1999-11-12', 'Male', 'Bacay Tulay Minglanilla Cebu', 'genesis.baraclan@gmail.com', 'baraclan.david@gmail.com', '09170000012', '09242345678', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$BLXoXrjZNfgQmPZqkFGVRO1FTzHZenb7M5w4.N6TvmZplXXiMXMdi', '3rd Year', 'Gloria Baraclan', '09486789013', 'Mark Baraclan', '09213456780', NULL, NULL),
(33, 'SCC-18-0007440', 'Base, Jev Adrian', '2001-11-08', 'Male', 'Sambag, Tuyan, City of Naga, Cebu', 'jev.adrian.base@gmail.com', NULL, NULL, '09373456789', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$0yJ3FLTxKRFNHmadZyE2kuJpq80ZWZmNTimpLLf/mi3XoesNoBu1G', '3rd Year', 'Francisco Base', '09577890124', 'Jenny Base', '09334567890', NULL, NULL),
(34, 'SCC-19-00010521', 'Booc, Aloysius A.', '1996-06-06', 'Male', 'Babag Lapulapu City', 'aloysious.booc@gmail.com', 'booc.andres@gmail.com', '09170000014', '09494567890', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$821pO7HCC52trO8rFjTNou35NIFJSf6tEgT8deQCKoI1h3G1kG4dq', '3rd Year', 'Teresa Booc', '09668901235', 'Arthur Booc', '09445678901', NULL, NULL),
(35, 'SCC-18-0007793', 'Adlawan, Ealla Marie', '1999-11-07', 'Female', 'Spring Village Pakigne, Minglanilla', 'ealla.adlawan@gmail.com', 'adlawan.rina@gmail.com', '09170000015', '09778901234', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$qfgIHTIHVWQzWCU04yKUrOtvUUew72AqMc/6s9jwUf4gXah7DhAjG', '4th Year', 'Rogelio Adlawan', '09789012346', 'Clara Adlawan', '09556789012', NULL, NULL),
(36, 'SCC-19-00010625', 'Alferez Jr., Bernardino S.', '1999-08-12', 'Male', 'Cantao-an Naga Cebu', 'bernardino.alferezjr@gmail.com', 'alferez.ernesto@gmail.com', '09170000016', '09486789013', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$LhAXid2R.1q5UGhfKU0Tq.Jme6xtqAYFghu2vlZQKf3jXNbI1C6uO', '4th Year', 'Marites Alferez', '09920123457', 'Diego Alferez', '09667890123', NULL, NULL),
(37, 'SCC-19-0009987', 'Almendras, Alistair A', '2000-04-21', 'Male', 'Purok Mahogany, Sambag Kolo, Tuyan City of Naga, Cebu', 'alistair.almendras@gmail.com', 'almendras.gloria@gmail.com', '09170000017', '09668901235', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$enCVWjTPr9XoYO4/IFwKZuj.aYciXxI0Us8B1AHN9Ka4mrjqetJz6', '4th Year', 'Edgar Almendras', '09131234567', 'Liza Almendras', '09778901234', NULL, NULL),
(38, 'SCC-17-0005276', 'Alvarado, Dexter Q.', '1999-07-12', 'Male', 'Babayongan Dalaguete Cebu', 'dexter.alvarado@gmail.com', 'alvarado.manuel@gmail.com', '09170000018', '09789012346', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$bg9VQ1Lk3rVaTntnW7tgbO7WH.4rtql5r5R/x2h6RN1WT2IFq6fKS', '4th Year', 'Angela Alvarado', '09242345678', 'Mario Alvarado', '09889012345', NULL, NULL),
(39, 'SCC-19-00010487', 'Amarille, Kim Ryan M', '1997-10-31', 'Male', 'Tungkop Minglanilla Cebu', 'kimryan.amarille@gmail.com', 'amarille.susan@gmail.com', '09170000019', '09920123457', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$DCK1M2ri7Z7ytWswOYiykedq7/xb0tMow.Rzra1JZJErzppGOrEK.', '4th Year', 'Ricardo Amarille', '09373456789', 'Helen Amarille', '09990123456', NULL, NULL),
(40, 'SCC-18-0008724', 'Arcamo Jr., Emmanuel', '1997-10-01', 'Male', 'Acoy Vito', 'emmanuel.arcamojr@gmail.com', 'arcamo.roberto@gmail.com', '09170000020', '09131234567', 'Roman Catholic', 'Filipino', 'BSIT', 'Single', '$2y$10$FxHgGDMXiTsrgNqvNXdUaeZM.8Lr/Ut.ZTzSFwNEKdWS0.W8PFRhi', '2nd Year', 'Josephine Arcamo', '09494567890', 'Paul Arcamo', '09111234567', NULL, 'uploads/profiles/student_40_1759561384.jfif'),
(81, 'SCC-16-0002907', 'Cedric Jade Getuaban', '2003-08-13', 'Male', 'Acoy Vito', 'cedricjade13@gmail.com', '', '', '09452586033', 'Catholic', 'Filipino', 'BSIT', 'Married', '$2y$10$FadZ4JSGOcRGW5P5qoX75e2dRo4AnbovVfkDpL5MQfbQoVM45I1DS', '1st Year', '09123456789', '', '', '', NULL, NULL),
(86, 'SCC-22-12345678', 'Kameko Daniels', '1997-01-05', 'Female', 'Non occaecat ipsa l', 'calurenir@mailinator.com', 'nyridyr@mailinator.com', '+1 (162) 215-6136', '+1 (799) 573-5561', 'Necessitatibus volup', 'Tenetur rem odio ut ', 'Dolor expedita et qu', 'Divorced', '$2y$10$8KMLox2/84urgNW1poeTVOpOmwSNOE5/gIeOqIsx/yXrsU6ffBkOO', '3rd Year', 'Geoffrey Haley', '+1 (391) 448-9806', 'Kay Cervantes', '+1 (788) 779-2513', NULL, NULL),
(87, 'SCC-11-1111111', 'Marvin Sims', '1998-03-12', 'Female', 'Ea voluptatem Et ma', 'qapejoco@mailinator.com', 'nyxecat@mailinator.com', '+1 (998) 327-6949', '+1 (771) 986-9981', 'Molestias soluta lab', 'Nobis inventore labo', 'Deserunt qui soluta ', 'Married', '$2y$10$Wxnyiuo3T9FpAVZaQZMEzuPTagPQgB02UTFXi3RFReABFGp/7e8Ci', '2nd Year', 'Lillith Spencer', '+1 (365) 356-9788', 'Linus Fry', '+1 (633) 736-1688', NULL, NULL),
(88, 'SCC-16-0002906', 'Declan Koch', '1996-07-23', 'Male', 'Itaque libero verita', 'dizizyw@mailinator.com', 'kewajuseh@mailinator.com', '+1 (425) 391-3152', '+1 (679) 654-1242', 'Consequat Cumque si', 'Commodo voluptatibus', 'Non et voluptatem r', 'Divorced', '$2y$10$gcBPHjn1P2EdRqkL9ey5O.N9cw4NDnvgdYjU3Pte.MjbJVdFspvFa', '2nd Year', 'Hamilton Hess', '+1 (391) 865-6291', 'Shana Olson', '+1 (495) 608-5143', NULL, NULL),
(89, 'SCC-11-11111111', 'Bell Hubbard', '1999-11-02', 'Female', 'Dolor do rem est in', 'misumipaze@mailinator.com', 'purity@mailinator.com', '+1 (318) 992-5044', '+1 (795) 404-5179', 'Adipisicing ea culpa', 'Quis quis ullamco sa', 'Non repellendus Aut', 'Married', '$2y$10$hatmv3/Sw1e91xKrO7jkl.WpgzUJQQ4gmuSHXMnWqhUsTCpwGfitu', '5th Year', 'Benedict Bartlett', '+1 (272) 822-2771', 'Roth Marshall', '+1 (364) 972-2143', NULL, NULL),
(90, 'SCC-16-00029078', 'Todd Clay', '1995-09-14', 'Male', 'Quod nostrud nihil l', 'pupota@mailinator.com', 'xace@mailinator.com', '+1 (752) 348-7563', '+1 (242) 428-4809', 'Expedita quis velit ', 'Facilis dolorem ut q', 'Facilis in duis enim', 'Single', '$2y$10$c2srYjQkB/OINLpKDHLG4.lGHNzQ00UeCnrTYYT1LHIVCRVi0QbPu', '5th Year', 'Colette Wilcox', '+1 (473) 901-1879', 'Tobias Lara', '+1 (863) 451-3249', NULL, NULL),
(91, 'SCC-11-11111112', 'Dorian Galloway', '1997-05-30', 'Male', 'Id est quam cupidata', 'vohuvuc@mailinator.com', 'gexinu@mailinator.com', '+1 (645) 866-6544', '+1 (514) 507-4241', 'Aliquam molestias do', 'Aut qui ullamco est', 'Repudiandae ut ipsum', 'Married', '$2y$10$OMSxoJLcxFpS6hWDGzSi/ewLIdM/5AMIj8pUA38OGtYHC0/yg/xlC', '5th Year', 'Ezekiel Lowery', '+1 (782) 157-3306', 'Russell Buchanan', '+1 (274) 602-1058', NULL, NULL),
(92, 'SCC-11-2222222', 'Alfonso Richards', '1998-12-08', 'Female', 'Velit doloremque vel', 'dogidax@mailinator.com', 'kilur@mailinator.com', '+1 (988) 866-2402', '+1 (659) 346-4785', 'Omnis quibusdam inve', 'Nisi voluptates cons', 'Soluta voluptas nesc', 'Divorced', '$2y$10$FnFzShayeCCBEih/HZgVC.dYV1nzwUZ5LjUlEu/zkTQWnR0teYhmm', '2nd Year', 'Rashad Kirk', '+1 (478) 302-5151', 'Hamilton Bartlett', '+1 (251) 976-8512', NULL, NULL),
(93, 'SCC-16-0001234', 'Mariele Gabutero', '2004-02-17', 'Female', 'Acoy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Single', '$2y$10$oJuUboULGifCrcOYU0968.XH6cBc6PKaZlxS00ptAB6k1mZn7Axui', '4th Year', NULL, NULL, NULL, NULL, NULL, NULL),
(94, 'SCC-11-22222222', 'Lawrence Kelley', '1999-06-27', 'Female', 'Aliquam ipsa tempor', 'wakusahi@mailinator.com', 'cuxeniwyt@mailinator.com', '+1 (901) 566-3296', '+1 (528) 924-5017', 'Amet optio nesciun', 'Sint est quis aliqua', 'Aut ut ipsam fugiat ', 'Married', '$2y$10$Bm4O65m7iMTk3j1pDDM84uaQKCSEus9gTKYDg5iSINcTaw2I74EdW', '2nd Year', 'Evan Boyer', '+1 (951) 777-8005', 'Lillith Lloyd', '+1 (821) 253-2747', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `level` varchar(10) DEFAULT 'INFO',
  `message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medication_referrals`
--

CREATE TABLE `medication_referrals` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `visitor_name` varchar(255) DEFAULT NULL,
  `subjective` text DEFAULT NULL,
  `objective` text DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `plan` text DEFAULT NULL,
  `intervention` text DEFAULT NULL,
  `evaluation` text DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `referral_to` varchar(255) DEFAULT NULL,
  `faculty_id` varchar(255) DEFAULT NULL,
  `faculty_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dosage` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expiry` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `manufacturer` varchar(255) DEFAULT 'PharmaCorp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `dosage`, `quantity`, `expiry`, `created_at`, `manufacturer`) VALUES
(32, 'Diatabs', '500mg', 1, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(33, 'Bioflu', '500mg', 2, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(34, 'Biogesic', '500mg', 0, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(35, 'Mefinamic', '500mg', 0, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(36, 'Paracetamol', '500mg', 1, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(37, 'Ibuprofen', '500mg', 1, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(38, 'Aspirin', '500mg', 0, '2026-09-02', '2025-09-15 20:36:24', 'PharmaCorp'),
(39, 'Alaxan', '500mg', 933, '2026-09-02', '2025-09-25 14:21:02', 'PharmaCorp'),
(40, 'Robitussin', '50mg', 1, '2026-09-02', '2025-09-25 14:42:55', 'PharmaCorp'),
(44, 'test', '1', 0, '2026-09-02', '2025-10-02 13:23:52', 'PharmaCorp'),
(45, 'Test1', '1', 0, '2026-09-02', '2025-10-02 13:52:34', 'PharmaCorp'),
(47, 'Test3', '500mg', 1, '2024-09-02', '2025-10-02 16:46:15', 'PharmaCorp'),
(49, 'bioflu', '500mg', 198, '2025-10-31', '2025-10-08 17:53:02', 'PharmaCorp'),
(50, 'test2', '500mg', 864, '2025-10-01', '2025-10-08 18:51:10', 'PharmaCorp');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_role` varchar(50) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `sender_name`, `sender_role`, `recipient_id`, `recipient_name`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1528, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'BLOOD LETTING', 'The Bloodletting Program is a community-centered initiative designed to promote voluntary blood donation and raise awareness about the importance of maintaining an adequate and safe blood supply. This activity aims to support hospitals, health centers, and emergency services by ensuring a steady flow of life-saving blood for patients in need, such as accident victims, surgical patients, and individuals with life-threatening conditions.\r\n\r\nThe program will start with a registration and screening process where participants will provide their basic information and undergo a health assessment to ensure their eligibility to donate blood. A short orientation will follow to educate donors about the procedure, its benefits, and post-donation care. Licensed medical professionals and trained staff will oversee the entire blood collection process to ensure safety, comfort, and efficiency.\r\n\r\nAside from the donation itself, the event also seeks to foster a spirit of volunteerism and compassion within the community. Informational booths and presentations will be available to explain the significance of regular blood donation, its positive impact on recipients, and the health benefits for donors. Light snacks and refreshments will be provided after donation to aid recovery and show appreciation to participants.\r\n\r\nThe Bloodletting Program will conclude with a brief recognition and appreciation of all donors and partners who contributed to the success of the activity. Certificates or tokens may be distributed as a form of gratitude. This agenda highlights the program’s commitment not only to collecting blood but also to educating and inspiring individuals to become regular, responsible donors, thereby strengthening the culture of giving and saving lives.', 1, '2025-09-30 20:09:00'),
(1529, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'BLOOD LETTING', 'The Bloodletting Program is a community-centered initiative designed to promote voluntary blood donation and raise awareness about the importance of maintaining an adequate and safe blood supply. This activity aims to support hospitals, health centers, and emergency services by ensuring a steady flow of life-saving blood for patients in need, such as accident victims, surgical patients, and individuals with life-threatening conditions.\r\n\r\nThe program will start with a registration and screening process where participants will provide their basic information and undergo a health assessment to ensure their eligibility to donate blood. A short orientation will follow to educate donors about the procedure, its benefits, and post-donation care. Licensed medical professionals and trained staff will oversee the entire blood collection process to ensure safety, comfort, and efficiency.\r\n\r\nAside from the donation itself, the event also seeks to foster a spirit of volunteerism and compassion within the community. Informational booths and presentations will be available to explain the significance of regular blood donation, its positive impact on recipients, and the health benefits for donors. Light snacks and refreshments will be provided after donation to aid recovery and show appreciation to participants.\r\n\r\nThe Bloodletting Program will conclude with a brief recognition and appreciation of all donors and partners who contributed to the success of the activity. Certificates or tokens may be distributed as a form of gratitude. This agenda highlights the program’s commitment not only to collecting blood but also to educating and inspiring individuals to become regular, responsible donors, thereby strengthening the culture of giving and saving lives.', 1, '2025-09-30 20:09:00'),
(1530, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'BLOOD LETTING', 'The Bloodletting Program is a community-centered initiative designed to promote voluntary blood donation and raise awareness about the importance of maintaining an adequate and safe blood supply. This activity aims to support hospitals, health centers, and emergency services by ensuring a steady flow of life-saving blood for patients in need, such as accident victims, surgical patients, and individuals with life-threatening conditions.\r\n\r\nThe program will start with a registration and screening process where participants will provide their basic information and undergo a health assessment to ensure their eligibility to donate blood. A short orientation will follow to educate donors about the procedure, its benefits, and post-donation care. Licensed medical professionals and trained staff will oversee the entire blood collection process to ensure safety, comfort, and efficiency.\r\n\r\nAside from the donation itself, the event also seeks to foster a spirit of volunteerism and compassion within the community. Informational booths and presentations will be available to explain the significance of regular blood donation, its positive impact on recipients, and the health benefits for donors. Light snacks and refreshments will be provided after donation to aid recovery and show appreciation to participants.\r\n\r\nThe Bloodletting Program will conclude with a brief recognition and appreciation of all donors and partners who contributed to the success of the activity. Certificates or tokens may be distributed as a form of gratitude. This agenda highlights the program’s commitment not only to collecting blood but also to educating and inspiring individuals to become regular, responsible donors, thereby strengthening the culture of giving and saving lives.', 1, '2025-09-30 20:09:00'),
(1531, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'BLOOD LETTING', 'The Bloodletting Program is a community-centered initiative designed to promote voluntary blood donation and raise awareness about the importance of maintaining an adequate and safe blood supply. This activity aims to support hospitals, health centers, and emergency services by ensuring a steady flow of life-saving blood for patients in need, such as accident victims, surgical patients, and individuals with life-threatening conditions.\r\n\r\nThe program will start with a registration and screening process where participants will provide their basic information and undergo a health assessment to ensure their eligibility to donate blood. A short orientation will follow to educate donors about the procedure, its benefits, and post-donation care. Licensed medical professionals and trained staff will oversee the entire blood collection process to ensure safety, comfort, and efficiency.\r\n\r\nAside from the donation itself, the event also seeks to foster a spirit of volunteerism and compassion within the community. Informational booths and presentations will be available to explain the significance of regular blood donation, its positive impact on recipients, and the health benefits for donors. Light snacks and refreshments will be provided after donation to aid recovery and show appreciation to participants.\r\n\r\nThe Bloodletting Program will conclude with a brief recognition and appreciation of all donors and partners who contributed to the success of the activity. Certificates or tokens may be distributed as a form of gratitude. This agenda highlights the program’s commitment not only to collecting blood but also to educating and inspiring individuals to become regular, responsible donors, thereby strengthening the culture of giving and saving lives.', 1, '2025-09-30 20:09:00'),
(1534, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'BLOOD LETTING', 'The Bloodletting Program is a community-centered initiative designed to promote voluntary blood donation and raise awareness about the importance of maintaining an adequate and safe blood supply. This activity aims to support hospitals, health centers, and emergency services by ensuring a steady flow of life-saving blood for patients in need, such as accident victims, surgical patients, and individuals with life-threatening conditions.\r\n\r\nThe program will start with a registration and screening process where participants will provide their basic information and undergo a health assessment to ensure their eligibility to donate blood. A short orientation will follow to educate donors about the procedure, its benefits, and post-donation care. Licensed medical professionals and trained staff will oversee the entire blood collection process to ensure safety, comfort, and efficiency.\r\n\r\nAside from the donation itself, the event also seeks to foster a spirit of volunteerism and compassion within the community. Informational booths and presentations will be available to explain the significance of regular blood donation, its positive impact on recipients, and the health benefits for donors. Light snacks and refreshments will be provided after donation to aid recovery and show appreciation to participants.\r\n\r\nThe Bloodletting Program will conclude with a brief recognition and appreciation of all donors and partners who contributed to the success of the activity. Certificates or tokens may be distributed as a form of gratitude. This agenda highlights the program’s commitment not only to collecting blood but also to educating and inspiring individuals to become regular, responsible donors, thereby strengthening the culture of giving and saving lives.', 1, '2025-09-30 20:09:00'),
(1536, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1537, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1538, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1539, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1540, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1541, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1542, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1543, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1544, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1545, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1546, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1547, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1548, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1549, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1550, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1551, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1552, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'AGENDA', 'test', 1, '2025-10-02 13:29:00'),
(1553, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1554, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1555, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1556, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1557, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1558, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1559, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1560, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1561, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1562, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1563, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1564, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1565, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'AGENDA', 'test', 1, '2025-10-02 13:29:00'),
(1566, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1567, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1568, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1569, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1570, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1571, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'AGENDA', 'test', 0, '2025-10-02 13:29:00'),
(1572, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'TEST ni BAI', 'ayg kuyawkuyaw', 1, '2025-10-04 16:32:44'),
(1573, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1574, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1575, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1576, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1577, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1578, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1579, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1580, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1581, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1582, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1583, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1584, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1585, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1586, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1587, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1588, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1589, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1590, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'Iloveyouuuu', 'baby', 1, '2025-10-04 16:34:28'),
(1591, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1592, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1593, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1594, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1595, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1596, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1597, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1598, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1599, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1600, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1601, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1602, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1603, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1604, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1605, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'Iloveyouuuu', 'baby', 1, '2025-10-04 16:34:28'),
(1606, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1607, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1608, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1609, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1610, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1611, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1612, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1613, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1614, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'Iloveyouuuu', 'baby', 0, '2025-10-04 16:34:28'),
(1615, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1616, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1617, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1618, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1619, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1620, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1621, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1622, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1623, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1624, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1625, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1626, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1627, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1628, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1629, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1630, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1631, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1632, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'Minatay', 'jaynu', 1, '2025-10-04 16:36:16'),
(1633, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1634, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1635, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1636, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1637, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1638, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1639, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1640, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1641, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1642, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1643, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1644, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1645, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1646, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1647, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'Minatay', 'jaynu', 1, '2025-10-04 16:36:16'),
(1648, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1649, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1650, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1651, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1652, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1653, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1654, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1655, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1656, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'Minatay', 'jaynu', 0, '2025-10-04 16:36:16'),
(1657, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1658, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1659, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1660, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1661, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1662, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1663, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1664, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1665, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1666, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1667, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1668, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1669, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1670, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1671, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1672, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1673, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1674, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'haha', 'hahahaha', 1, '2025-10-04 16:38:35'),
(1675, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1676, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1677, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1678, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1679, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1680, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1681, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1682, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1683, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1684, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1685, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1686, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1687, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1688, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1689, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'haha', 'hahahaha', 1, '2025-10-04 16:38:35'),
(1690, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1691, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1692, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1693, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1694, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1695, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1696, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1697, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1698, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'haha', 'hahahaha', 0, '2025-10-04 16:38:35'),
(1699, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1700, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1701, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1702, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1703, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1704, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1705, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1706, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1707, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1708, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1709, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1710, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1711, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1712, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1713, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1714, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1715, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1716, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'balik', 'balik', 1, '2025-10-04 16:47:03'),
(1717, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1718, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1719, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1720, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1721, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1722, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1723, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1724, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1725, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1726, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1727, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1728, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1729, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1730, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1731, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'balik', 'balik', 1, '2025-10-04 16:47:03'),
(1732, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1733, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1734, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1735, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1736, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1737, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1738, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1739, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1740, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'balik', 'balik', 0, '2025-10-04 16:47:03'),
(1741, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1742, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1743, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1744, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1745, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1746, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1747, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1748, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1749, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1750, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1751, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1752, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1753, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1754, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1755, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1756, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1757, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1758, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'try', 'try', 1, '2025-10-04 16:49:15'),
(1759, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1760, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1761, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1762, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1763, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1764, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1765, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1766, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1767, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1768, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1769, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1770, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1771, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1772, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1773, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'try', 'try', 1, '2025-10-04 16:49:15'),
(1774, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1775, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1776, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1777, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1778, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1779, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1780, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1781, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1782, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'try', 'try', 0, '2025-10-04 16:49:15'),
(1783, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1784, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1785, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1786, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1787, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1788, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1789, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1790, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1791, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1792, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1793, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1794, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1795, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1796, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1797, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1798, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1799, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1800, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'try nasad', 'try nasad', 1, '2025-10-04 16:51:44'),
(1801, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1802, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1803, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1804, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1805, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1806, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1807, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1808, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1809, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1810, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1811, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1812, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1813, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1814, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1815, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'try nasad', 'try nasad', 1, '2025-10-04 16:51:44'),
(1816, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1817, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1818, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1819, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1820, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1821, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1822, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1823, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1824, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'try nasad', 'try nasad', 0, '2025-10-04 16:51:44'),
(1825, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1826, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1827, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1828, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1829, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1830, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1831, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1832, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1833, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1834, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1835, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1836, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1837, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1838, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1839, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1840, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1841, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1842, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'try nasad ta ani', 'try nasad ta ani', 1, '2025-10-04 16:53:55'),
(1843, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1844, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1845, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1846, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1847, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1848, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1849, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1850, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1851, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1852, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1853, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1854, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1855, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1856, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1857, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'try nasad ta ani', 'try nasad ta ani', 1, '2025-10-04 16:53:55'),
(1858, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1859, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1860, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1861, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1862, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1863, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1864, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1865, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1866, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'try nasad ta ani', 'try nasad ta ani', 0, '2025-10-04 16:53:55'),
(1867, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1868, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1869, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1870, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1871, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1872, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1873, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1874, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1875, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1876, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1877, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1878, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1879, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1880, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1881, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1882, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1883, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1884, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'real time najud ka?', 'real time najud ka?', 1, '2025-10-04 16:54:14'),
(1885, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1886, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1887, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1888, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1889, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1890, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1891, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1892, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1893, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1894, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1895, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1896, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1897, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1898, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14');
INSERT INTO `messages` (`id`, `sender_id`, `sender_name`, `sender_role`, `recipient_id`, `recipient_name`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1899, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'real time najud ka?', 'real time najud ka?', 1, '2025-10-04 16:54:14'),
(1900, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1901, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1902, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1903, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1904, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1905, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1906, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1907, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1908, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'real time najud ka?', 'real time najud ka?', 0, '2025-10-04 16:54:14'),
(1909, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1910, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1911, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1912, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1913, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1914, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1915, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1916, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1917, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1918, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1919, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1920, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1921, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1922, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1923, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1924, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1925, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1926, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'hey', 'hey', 1, '2025-10-04 16:56:47'),
(1927, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1928, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1929, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1930, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1931, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1932, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1933, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1934, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1935, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1936, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1937, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1938, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1939, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1940, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1941, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hey', 'hey', 1, '2025-10-04 16:56:47'),
(1942, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1943, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1944, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1945, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1946, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1947, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1948, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1949, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1950, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'hey', 'hey', 0, '2025-10-04 16:56:47'),
(1951, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1952, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1953, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1954, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1955, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1956, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1957, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1958, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1959, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1960, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1961, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1962, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1963, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1964, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1965, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1966, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1967, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1968, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'hey', 'hey', 1, '2025-10-04 16:57:09'),
(1969, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1970, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1971, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1972, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1973, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1974, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1975, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1976, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1977, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1978, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1979, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1980, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1981, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1982, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1983, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hey', 'hey', 1, '2025-10-04 16:57:09'),
(1984, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1985, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1986, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1987, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1988, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1989, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1990, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1991, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1992, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'hey', 'hey', 0, '2025-10-04 16:57:09'),
(1993, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(1994, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(1995, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(1996, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(1997, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(1998, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(1999, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2000, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2001, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2002, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2003, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2004, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2005, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2006, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2007, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2008, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2009, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2010, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'heyhey', 'heyhey', 1, '2025-10-04 16:59:56'),
(2011, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2012, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2013, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2014, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2015, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2016, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2017, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2018, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2019, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2020, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2021, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2022, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2023, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2024, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2025, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'heyhey', 'heyhey', 1, '2025-10-04 16:59:56'),
(2026, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2027, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2028, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2029, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2030, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2031, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2032, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2033, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2034, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'heyhey', 'heyhey', 0, '2025-10-04 16:59:56'),
(2035, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2036, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2037, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2038, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2039, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2040, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2041, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2042, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2043, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2044, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2045, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2046, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2047, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2048, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2049, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2050, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2051, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2052, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'helo', 'helo', 1, '2025-10-04 17:00:13'),
(2053, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2054, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2055, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2056, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2057, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2058, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2059, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2060, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2061, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2062, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2063, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2064, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2065, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2066, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2067, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'helo', 'helo', 1, '2025-10-04 17:00:13'),
(2068, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2069, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2070, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2071, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2072, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2073, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2074, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2075, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2076, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'helo', 'helo', 0, '2025-10-04 17:00:13'),
(2077, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2078, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2079, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2080, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2081, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2082, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2083, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2084, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2085, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2086, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2087, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2088, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2089, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2090, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2091, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2092, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2093, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2094, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'yawaa ato oy', 'yawaa ato oy', 1, '2025-10-04 17:27:47'),
(2095, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2096, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2097, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2098, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2099, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2100, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2101, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2102, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2103, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2104, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2105, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2106, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2107, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2108, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2109, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yawaa ato oy', 'yawaa ato oy', 1, '2025-10-04 17:27:47'),
(2110, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2111, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2112, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2113, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2114, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2115, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2116, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2117, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2118, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'yawaa ato oy', 'yawaa ato oy', 0, '2025-10-04 17:27:47'),
(2119, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2120, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2121, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2122, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2123, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2124, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2125, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2126, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2127, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2128, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2129, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2130, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2131, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2132, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2133, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2134, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2135, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2136, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'yehey', 'yehey', 1, '2025-10-04 17:28:50'),
(2137, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2138, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2139, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2140, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2141, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2142, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2143, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2144, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2145, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2146, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2147, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2148, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2149, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2150, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2151, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yehey', 'yehey', 1, '2025-10-04 17:28:50'),
(2152, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2153, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2154, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2155, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2156, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2157, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2158, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2159, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2160, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'yehey', 'yehey', 0, '2025-10-04 17:28:50'),
(2161, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2162, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2163, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2164, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2165, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2166, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2167, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2168, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2169, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2170, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2171, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2172, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2173, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2174, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2175, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2176, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2177, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2178, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'Minatay', 'ka', 1, '2025-10-04 17:31:06'),
(2179, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2180, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2181, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2182, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2183, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2184, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2185, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2186, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2187, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2188, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2189, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2190, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2191, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2192, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2193, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'Minatay', 'ka', 1, '2025-10-04 17:31:06'),
(2194, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2195, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2196, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2197, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2198, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2199, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2200, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2201, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2202, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'Minatay', 'ka', 0, '2025-10-04 17:31:06'),
(2203, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2204, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2205, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2206, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2207, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2208, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2209, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2210, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2211, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2212, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2213, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2214, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2215, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2216, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2217, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2218, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2219, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2220, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'hey', 'hey', 1, '2025-10-04 17:32:34'),
(2221, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2222, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2223, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2224, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2225, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2226, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2227, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2228, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2229, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2230, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2231, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2232, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2233, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2234, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2235, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hey', 'hey', 1, '2025-10-04 17:32:34'),
(2236, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2237, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2238, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2239, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2240, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2241, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2242, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2243, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2244, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'hey', 'hey', 0, '2025-10-04 17:32:34'),
(2245, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2246, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2247, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2248, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2249, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2250, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2251, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2252, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2253, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2254, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2255, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2256, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2257, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2258, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2259, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2260, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2261, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2262, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'Minatay', 'ya', 1, '2025-10-04 17:33:23'),
(2263, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2264, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2265, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2266, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2267, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2268, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2269, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2270, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2271, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2272, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2273, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2274, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2275, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2276, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2277, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'Minatay', 'ya', 1, '2025-10-04 17:33:23'),
(2278, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2279, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2280, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2281, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2282, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2283, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2284, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2285, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2286, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'Minatay', 'ya', 0, '2025-10-04 17:33:23'),
(2287, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2288, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2289, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2290, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2291, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2292, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2293, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2294, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2295, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2296, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2297, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2298, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2299, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2300, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2301, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2302, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2303, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2304, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'yawa', 'hahays', 1, '2025-10-04 17:34:14'),
(2305, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2306, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2307, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2308, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2309, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2310, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2311, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2312, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2313, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2314, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2315, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2316, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2317, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2318, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2319, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yawa', 'hahays', 1, '2025-10-04 17:34:14'),
(2320, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2321, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2322, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2323, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2324, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2325, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2326, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2327, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2328, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'yawa', 'hahays', 0, '2025-10-04 17:34:14'),
(2329, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2330, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2331, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2332, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2333, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2334, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2335, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2336, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2337, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2338, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2339, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2340, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2341, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:39'),
(2342, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2343, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2344, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2345, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2346, 11, 'Staff', 'doctor/nurse', 40, 'Arcamo Jr., Emmanuel P.', 'hey', 'heyheyhey', 1, '2025-10-04 17:34:40'),
(2347, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2348, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2349, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2350, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2351, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2352, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2353, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2354, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2355, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2356, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2357, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2358, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2359, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2360, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2361, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hey', 'heyheyhey', 1, '2025-10-04 17:34:40'),
(2362, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2363, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2364, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2365, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2366, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2367, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2368, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2369, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2370, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'hey', 'heyheyhey', 0, '2025-10-04 17:34:40'),
(2371, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2372, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2373, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58');
INSERT INTO `messages` (`id`, `sender_id`, `sender_name`, `sender_role`, `recipient_id`, `recipient_name`, `subject`, `message`, `is_read`, `created_at`) VALUES
(2374, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2375, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2376, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2377, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2378, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2379, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2380, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2381, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2382, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2383, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2384, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2385, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2386, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2387, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2389, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2390, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2391, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2392, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2393, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2394, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2395, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2396, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2397, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2398, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2399, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2400, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2401, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2402, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2403, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hey', 'heyhey', 1, '2025-10-04 17:37:58'),
(2404, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2405, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2406, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2407, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2408, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2409, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2410, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2411, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2412, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'hey', 'heyhey', 0, '2025-10-04 17:37:58'),
(2413, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2414, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2415, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2416, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2417, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2418, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2419, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2420, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2421, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2422, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2423, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2424, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2425, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2426, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2427, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2428, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2429, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2431, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2432, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2433, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2434, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2435, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2436, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2437, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2438, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2439, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2440, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2441, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2442, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2443, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2444, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2445, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hehe', 'hehehehe', 1, '2025-10-04 17:39:24'),
(2446, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2447, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2448, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2449, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2450, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2451, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2452, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2453, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2454, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'hehe', 'hehehehe', 0, '2025-10-04 17:39:24'),
(2455, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2456, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2457, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2458, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2459, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2460, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2461, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2462, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2463, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2464, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2465, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2466, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2467, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2468, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2469, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2470, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2471, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2473, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2474, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2475, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2476, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2477, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2478, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2479, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2480, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2481, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2482, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2483, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2484, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2485, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2486, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2487, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'lets', 'lsetsds', 1, '2025-10-04 17:39:35'),
(2488, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2489, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2490, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2491, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2492, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2493, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2494, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2495, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2496, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'lets', 'lsetsds', 0, '2025-10-04 17:39:35'),
(2497, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2498, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2499, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2500, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2501, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2502, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2503, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2504, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2505, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2506, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2507, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2508, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2509, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2510, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2511, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2512, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2513, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2515, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2516, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2517, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2518, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2519, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2520, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2521, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2522, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2523, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2524, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2525, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2526, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2527, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2528, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2529, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yati ka', 'hays', 1, '2025-10-04 17:44:37'),
(2530, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2531, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2532, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2533, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2534, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2535, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2536, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2537, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2538, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'yati ka', 'hays', 0, '2025-10-04 17:44:37'),
(2539, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2540, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2541, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2542, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2543, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2544, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2545, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2546, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2547, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2548, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2549, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2550, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2551, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2552, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2553, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2554, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2555, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2557, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2558, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2559, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2560, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2561, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2562, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2563, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2564, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2565, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2566, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2567, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2568, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2569, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2570, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2571, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'litse', 'ka', 1, '2025-10-04 17:47:03'),
(2572, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2573, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2574, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2575, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2576, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2577, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2578, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2579, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2580, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'litse', 'ka', 0, '2025-10-04 17:47:03'),
(2581, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2582, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2583, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2584, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2585, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2586, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2587, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2588, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2589, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2590, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2591, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2592, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2593, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2594, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2595, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2596, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2597, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2599, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2600, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2601, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2602, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2603, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2604, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2605, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2606, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2607, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2608, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2609, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2610, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2611, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2612, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2613, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yati', 'ka', 1, '2025-10-04 17:54:19'),
(2614, 11, 'Staff', 'doctor/nurse', 15, 'chean lisondra', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2615, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2616, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2617, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2618, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2619, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2620, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2621, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2622, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2623, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'yati', 'ka', 0, '2025-10-04 17:54:19'),
(2624, 11, 'Staff', 'doctor/nurse', 21, 'Abella, Joseph B.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2625, 11, 'Staff', 'doctor/nurse', 25, 'Abellana, Ariel L', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2626, 11, 'Staff', 'doctor/nurse', 22, 'Abellana, Vincent Anthony Q.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2627, 11, 'Staff', 'doctor/nurse', 23, 'Abendan, Christian James A.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2628, 11, 'Staff', 'doctor/nurse', 24, 'Abendan, Nino Rashean T.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2629, 11, 'Staff', 'doctor/nurse', 26, 'Acidillo, Baby John V.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2630, 11, 'Staff', 'doctor/nurse', 35, 'Adlawan, Ealla Marie', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2631, 11, 'Staff', 'doctor/nurse', 27, 'Adona, Carl Macel C.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2632, 11, 'Staff', 'doctor/nurse', 30, 'Aguilar, Jaymar C', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2633, 11, 'Staff', 'doctor/nurse', 28, 'Albiso, Creshell Mary M.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2634, 11, 'Staff', 'doctor/nurse', 29, 'Alegado, John Raymon B.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2635, 11, 'Staff', 'doctor/nurse', 36, 'Alferez Jr., Bernardino S.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2636, 11, 'Staff', 'doctor/nurse', 92, 'Alfonso Richards', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2637, 11, 'Staff', 'doctor/nurse', 31, 'Alicaya, Ralph Lorync D.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2638, 11, 'Staff', 'doctor/nurse', 37, 'Almendras, Alistair A', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2639, 11, 'Staff', 'doctor/nurse', 38, 'Alvarado, Dexter Q.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2640, 11, 'Staff', 'doctor/nurse', 39, 'Amarille, Kim Ryan M', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2642, 11, 'Staff', 'doctor/nurse', 32, 'Baraclan, Genesis S.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2643, 11, 'Staff', 'doctor/nurse', 33, 'Base, Jev Adrian', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2644, 11, 'Staff', 'doctor/nurse', 89, 'Bell Hubbard', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2645, 11, 'Staff', 'doctor/nurse', 34, 'Booc, Aloysius A.', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2646, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2647, 11, 'Staff', 'doctor/nurse', 88, 'Declan Koch', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2648, 11, 'Staff', 'doctor/nurse', 91, 'Dorian Galloway', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2649, 11, 'Staff', 'doctor/nurse', 86, 'Kameko Daniels', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2650, 11, 'Staff', 'doctor/nurse', 85, 'Mariele G. Gabutero', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2651, 11, 'Staff', 'doctor/nurse', 87, 'Marvin Sims', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2652, 11, 'Staff', 'doctor/nurse', 90, 'Todd Clay', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2653, 11, 'Staff', 'doctor/nurse', 4, 'Ayanna Pearson', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2654, 11, 'Staff', 'doctor/nurse', 5, 'Basia Robbins', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2655, 11, 'Staff', 'doctor/nurse', 11, 'Castor Goodman', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2656, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'litse', 'jaynu', 1, '2025-10-04 17:54:39'),
(2657, 11, 'Staff', 'doctor/nurse', 15, 'chean lisondra', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2658, 11, 'Staff', 'doctor/nurse', 14, 'Demetria Sanford', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2659, 11, 'Staff', 'doctor/nurse', 13, 'Elvis Long', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2660, 11, 'Staff', 'doctor/nurse', 12, 'Emmanuel Levy', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2661, 11, 'Staff', 'doctor/nurse', 8, 'Holmes Leon', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2662, 11, 'Staff', 'doctor/nurse', 6, 'Kitra Hardy', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2663, 11, 'Staff', 'doctor/nurse', 1, 'Maria Santos', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2664, 11, 'Staff', 'doctor/nurse', 2, 'Perry Nelson', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2665, 11, 'Staff', 'doctor/nurse', 10, 'Rylee Whitley', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2666, 11, 'Staff', 'doctor/nurse', 3, 'Whoopi Harrell', 'litse', 'jaynu', 0, '2025-10-04 17:54:39'),
(2667, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'hello', 'Cedric', 1, '2025-10-04 21:02:01'),
(2668, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'Hello', 'Ced', 0, '2025-10-04 21:02:25'),
(2669, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'Hello', 'Ced', 1, '2025-10-04 21:02:42'),
(2670, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'Hey', 'Yow', 1, '2025-10-04 21:02:58'),
(2671, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yawa erica', 'yawa erica', 1, '2025-10-04 21:25:14'),
(2672, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yawa jud', 'yawa', 1, '2025-10-04 21:30:07'),
(2673, 11, 'Staff', 'doctor/nurse', 81, 'Cedric Jade Getuaban', 'hays', 'hahahaays', 0, '2025-10-04 21:31:39'),
(2675, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'sala', 'jud ni nimo jaynu', 1, '2025-10-04 21:38:18'),
(2676, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yatia', 'yatia', 1, '2025-10-04 21:46:25'),
(2677, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yatia', 'yatia', 1, '2025-10-04 21:48:54'),
(2678, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yatia', 'yatia', 1, '2025-10-04 21:48:57'),
(2679, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'asd', 'asdadas', 1, '2025-10-04 21:49:07'),
(2680, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'asd', 'asdadas', 1, '2025-10-04 21:53:57'),
(2681, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'litse', 'rhona', 1, '2025-10-04 21:59:00'),
(2682, 40, 'Staff', 'faculty', 9, 'Cedric Pinili Getuaban', 'yawa ka jaynu', 'uyab sila erica', 1, '2025-10-04 23:21:55'),
(2683, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili Getuaban', 'yawa ka jaynu', 'uyab mo erica', 1, '2025-10-04 23:22:53'),
(2685, 11, 'Staff', 'doctor/nurse', 9, 'Cedric Pinili monggoloid', 'matay paka', 'jaynu', 1, '2025-10-07 02:00:23'),
(2686, 40, 'Staff', 'student', 9, 'Cedric Pinili monggoloid', 'hays', 'hgasd', 0, '2025-10-07 02:02:30'),
(2687, 40, 'Staff', 'student', 9, 'Cedric Pinili monggoloid', 'hey', 'asd', 0, '2025-10-07 02:02:43');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `faculty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_alerts`
--

CREATE TABLE `parent_alerts` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `parent_email` varchar(255) NOT NULL,
  `visit_count` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `alert_sent_at` datetime DEFAULT current_timestamp(),
  `alert_status` enum('pending','sent','failed') DEFAULT 'pending',
  `email_content` text DEFAULT NULL,
  `sent_by` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_alerts`
--

INSERT INTO `parent_alerts` (`id`, `patient_id`, `patient_name`, `parent_email`, `visit_count`, `week_start_date`, `week_end_date`, `alert_sent_at`, `alert_status`, `email_content`, `sent_by`, `created_at`) VALUES
(1, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-10-02', '2025-10-02', '2025-08-19 07:51:38', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 07:51:38'),
(2, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-10-02', '2025-10-02', '2025-08-19 07:51:45', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 07:51:45'),
(3, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-10-02', '2025-10-02', '2025-08-19 07:53:38', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 07:53:38'),
(4, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-10-02', '2025-10-02', '2025-08-19 07:58:55', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 07:58:55'),
(5, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-10-02', '2025-10-02', '2025-08-19 07:59:03', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 07:59:03'),
(6, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-08-18', '2025-08-24', '2025-08-19 08:02:44', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:02:44'),
(7, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-08-18', '2025-08-24', '2025-08-19 08:02:54', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:02:54');
INSERT INTO `parent_alerts` (`id`, `patient_id`, `patient_name`, `parent_email`, `visit_count`, `week_start_date`, `week_end_date`, `alert_sent_at`, `alert_status`, `email_content`, `sent_by`, `created_at`) VALUES
(8, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-08-18', '2025-08-24', '2025-08-19 08:10:48', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:10:48'),
(9, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 15, '2025-08-18', '2025-08-24', '2025-08-19 08:10:57', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>15 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:10:57'),
(11, 21, 'Abella, Joseph B.', 'sicecyre@mailinator.com', 16, '2025-08-18', '2025-08-24', '2025-08-19 08:11:31', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>16 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 16:</strong> Aug 19, 2025 at 8:11 AM<br>Reason: Assumenda a ipsa as<br>Medication: [{&quot;medicine&quot;:&quot;Mefinamic&quot;,&quot;dosage&quot;:&quot;Quo pariatur Labori&quot;,&quot;quantity&quot;:&quot;30&quot;,&quot;frequency&quot;:&quot;Delectus enim cumqu&quot;,&quot;instructions&quot;:&quot;Dolor illum quis au&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:11:31'),
(12, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 17, '2025-08-18', '2025-08-24', '2025-08-19 08:13:07', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>17 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 16:</strong> Aug 19, 2025 at 8:11 AM<br>Reason: Assumenda a ipsa as<br>Medication: [{&quot;medicine&quot;:&quot;Mefinamic&quot;,&quot;dosage&quot;:&quot;Quo pariatur Labori&quot;,&quot;quantity&quot;:&quot;30&quot;,&quot;frequency&quot;:&quot;Delectus enim cumqu&quot;,&quot;instructions&quot;:&quot;Dolor illum quis au&quot;}]<br><br><strong>Visit 17:</strong> Aug 19, 2025 at 8:13 AM<br>Reason: Repudiandae unde lau<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Voluptas error Nam m&quot;,&quot;quantity&quot;:&quot;84&quot;,&quot;frequency&quot;:&quot;Inventore modi aliqu&quot;,&quot;instructions&quot;:&quot;Neque et rerum dolor&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:13:07'),
(13, 21, 'Abella, Joseph B.', 'jaynujangad03@gmail.com', 17, '2025-08-18', '2025-08-24', '2025-08-19 08:27:57', 'sent', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>17 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 1:45 AM<br>Reason: Fever<br>Medication: [{&quot;medicine&quot;:&quot;Neozep&quot;,&quot;dosage&quot;:&quot;Quo odit ipsa in ea&quot;,&quot;quantity&quot;:&quot;38&quot;,&quot;frequency&quot;:&quot;Cum numquam in ea ex&quot;,&quot;instructions&quot;:&quot;Nesciunt labore dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 18, 2025 at 7:17 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 3:</strong> Aug 18, 2025 at 7:34 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 4:</strong> Aug 18, 2025 at 8:13 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 5:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 6:</strong> Aug 18, 2025 at 8:14 PM<br>Reason: asd<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;asd&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 7:</strong> Aug 18, 2025 at 8:33 PM<br>Reason: fevcer<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 8:</strong> Aug 18, 2025 at 8:49 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;asd&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 9:</strong> Aug 19, 2025 at 7:35 AM<br>Reason: Sakits ulo<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Mollitia qui facilis&quot;,&quot;quantity&quot;:&quot;11&quot;,&quot;frequency&quot;:&quot;Iste explicabo Rem &quot;,&quot;instructions&quot;:&quot;Natus quos harum sae&quot;}]<br><br><strong>Visit 10:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Qui occaecat magna v<br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Omnis velit dolor m&quot;,&quot;quantity&quot;:&quot;71&quot;,&quot;frequency&quot;:&quot;Consequat Itaque in&quot;,&quot;instructions&quot;:&quot;Quia rerum eveniet &quot;}]<br><br><strong>Visit 11:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Dolore consequuntur <br>Medication: [{&quot;medicine&quot;:&quot;Diatabs&quot;,&quot;dosage&quot;:&quot;Ut rerum quis cupida&quot;,&quot;quantity&quot;:&quot;75&quot;,&quot;frequency&quot;:&quot;Autem obcaecati ut c&quot;,&quot;instructions&quot;:&quot;Fugiat enim a dolor &quot;}]<br><br><strong>Visit 12:</strong> Aug 19, 2025 at 7:37 AM<br>Reason: Doloribus praesentiu<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Non laborum Digniss&quot;,&quot;quantity&quot;:&quot;29&quot;,&quot;frequency&quot;:&quot;Commodo rerum id co&quot;,&quot;instructions&quot;:&quot;Voluptatem Velit of&quot;}]<br><br><strong>Visit 13:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Recusandae Rerum te<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Quis porro ipsum co&quot;,&quot;quantity&quot;:&quot;42&quot;,&quot;frequency&quot;:&quot;Dolor aliquip nisi q&quot;,&quot;instructions&quot;:&quot;Sint atque ab incid&quot;}]<br><br><strong>Visit 14:</strong> Aug 19, 2025 at 7:38 AM<br>Reason: Enim qui eum asperio<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Iusto optio anim do&quot;,&quot;quantity&quot;:&quot;16&quot;,&quot;frequency&quot;:&quot;Qui qui deserunt ips&quot;,&quot;instructions&quot;:&quot;Voluptas officiis po&quot;}]<br><br><strong>Visit 15:</strong> Aug 19, 2025 at 7:40 AM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;1&quot;,&quot;frequency&quot;:&quot;asd&quot;,&quot;instructions&quot;:&quot;asd&quot;}]<br><br><strong>Visit 16:</strong> Aug 19, 2025 at 8:11 AM<br>Reason: Assumenda a ipsa as<br>Medication: [{&quot;medicine&quot;:&quot;Mefinamic&quot;,&quot;dosage&quot;:&quot;Quo pariatur Labori&quot;,&quot;quantity&quot;:&quot;30&quot;,&quot;frequency&quot;:&quot;Delectus enim cumqu&quot;,&quot;instructions&quot;:&quot;Dolor illum quis au&quot;}]<br><br><strong>Visit 17:</strong> Aug 19, 2025 at 8:13 AM<br>Reason: Repudiandae unde lau<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Voluptas error Nam m&quot;,&quot;quantity&quot;:&quot;84&quot;,&quot;frequency&quot;:&quot;Inventore modi aliqu&quot;,&quot;instructions&quot;:&quot;Neque et rerum dolor&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:27:57'),
(16, 22, 'Abellana, Vincent Anthony Q.', 'jaynujangad03@gmail.com', 3, '2025-08-18', '2025-08-24', '2025-08-19 08:43:03', 'sent', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Abellana, Vincent Anthony Q.</strong>, has received medication from the clinic <strong>3 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'><strong>Visit 1:</strong> Aug 18, 2025 at 7:15 PM<br>Reason: fever<br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;500mg&quot;,&quot;quantity&quot;:&quot;2&quot;,&quot;frequency&quot;:&quot;3x a day&quot;,&quot;instructions&quot;:&quot;after meals&quot;}]<br><br><strong>Visit 2:</strong> Aug 19, 2025 at 8:42 AM<br>Reason: Adipisci soluta est<br>Medication: [{&quot;medicine&quot;:&quot;Biogesic&quot;,&quot;dosage&quot;:&quot;Dolorum dolore nisi &quot;,&quot;quantity&quot;:&quot;59&quot;,&quot;frequency&quot;:&quot;Et vel adipisci quia&quot;,&quot;instructions&quot;:&quot;Placeat et consequa&quot;}]<br><br><strong>Visit 3:</strong> Aug 19, 2025 at 8:42 AM<br>Reason: Laboriosam culpa si<br>Medication: [{&quot;medicine&quot;:&quot;Rexidol&quot;,&quot;dosage&quot;:&quot;Consequuntur eos cu&quot;,&quot;quantity&quot;:&quot;72&quot;,&quot;frequency&quot;:&quot;Laboriosam consecte&quot;,&quot;instructions&quot;:&quot;Rerum aliquid non se&quot;}]<br><br></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:43:03'),
(17, 23, 'Abendan, Christian James A.', 'jaynujangad03@gmail.com', 3, '2025-08-18', '2025-08-24', '2025-08-19 08:58:35', 'sent', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #ffffff;\'>\r\n                <div style=\'background-color: #2563eb; color: white; padding: 20px; text-align: center;\'>\r\n                    <h1 style=\'margin: 0; font-size: 24px;\'>School Health Clinic</h1>\r\n                    <p style=\'margin: 5px 0 0 0; font-size: 16px;\'>Student Health Notification</p>\r\n                </div>\r\n                \r\n                <div style=\'padding: 30px 20px;\'>\r\n                    <p style=\'font-size: 16px; color: #333333; margin-bottom: 20px;\'>Dear Parent/Guardian,</p>\r\n                    \r\n                    <p style=\'font-size: 16px; color: #333333; line-height: 1.6;\'>\r\n                        We are writing to inform you that your child, <strong style=\'color: #2563eb;\'>Abendan, Christian James A.</strong>, \r\n                        has visited the school clinic for medication <strong>3 times</strong> during this week \r\n                        (Monday through Sunday).\r\n                    </p>\r\n                    \r\n                    <div style=\'background-color: #f8f9fa; padding: 20px; border-left: 4px solid #2563eb; margin: 25px 0;\'>\r\n                        <h3 style=\'margin-top: 0; color: #374151; font-size: 18px;\'>This Week\'s Clinic Visits:</h3>\r\n                        <div style=\'font-size: 14px; color: #555555;\'>\r\n                            <strong>Visit 1:</strong> Aug 19, 2025 at 8:58 AM<br>Reason: Corrupti debitis qu<br>Medication: [{&quot;medicine&quot;:&quot;Rexidol&quot;,&quot;dosage&quot;:&quot;Voluptas est quis mo&quot;,&quot;quantity&quot;:&quot;55&quot;,&quot;frequency&quot;:&quot;Incididunt laudantiu&quot;,&quot;instructions&quot;:&quot;Qui sint eum in dic&quot;}]<br><br><strong>Visit 2:</strong> Aug 19, 2025 at 8:58 AM<br>Reason: Sit assumenda quod <br>Medication: [{&quot;medicine&quot;:&quot;Mefinamic&quot;,&quot;dosage&quot;:&quot;Ex debitis architect&quot;,&quot;quantity&quot;:&quot;47&quot;,&quot;frequency&quot;:&quot;Quasi veritatis cupi&quot;,&quot;instructions&quot;:&quot;Harum laborum Repel&quot;}]<br><br><strong>Visit 3:</strong> Aug 19, 2025 at 8:58 AM<br>Reason: Maiores ex sint sed <br>Medication: [{&quot;medicine&quot;:&quot;Bioflu&quot;,&quot;dosage&quot;:&quot;Perferendis autem de&quot;,&quot;quantity&quot;:&quot;80&quot;,&quot;frequency&quot;:&quot;Porro dolor voluptas&quot;,&quot;instructions&quot;:&quot;Voluptates natus duc&quot;}]<br><br>\r\n                        </div>\r\n                    </div>\r\n                    \r\n                    <div style=\'background-color: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0;\'>\r\n                        <h4 style=\'margin-top: 0; color: #856404;\'>???? Recommended Actions:</h4>\r\n                        <ul style=\'margin-bottom: 0; color: #856404;\'>\r\n                            <li>Monitor your child\'s health and wellbeing at home</li>\r\n                            <li>Contact our clinic if you have concerns about frequent visits</li>\r\n                            <li>Consider scheduling a consultation with your family doctor</li>\r\n                            <li>Review any patterns that might be causing recurring symptoms</li>\r\n                        </ul>\r\n                    </div>\r\n                    \r\n                    <p style=\'font-size: 16px; color: #333333; line-height: 1.6;\'>\r\n                        Multiple clinic visits in one week may indicate a health concern that requires attention. \r\n                        We encourage you to follow up with your child\'s healthcare provider if you have any concerns.\r\n                    </p>\r\n                    \r\n                    <p style=\'font-size: 16px; color: #333333; line-height: 1.6;\'>\r\n                        If you have any questions about your child\'s clinic visits, please don\'t hesitate to contact us.\r\n                    </p>\r\n                </div>\r\n                \r\n                <div style=\'background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;\'>\r\n                    <p style=\'margin: 0; font-size: 14px; color: #666666;\'>\r\n                        <strong>School Health Clinic</strong><br>\r\n                        This is an automated notification for your child\'s health and safety.<br>\r\n                        Please do not reply to this email. Contact the clinic directly for inquiries.\r\n                    </p>\r\n                </div>\r\n            </div>\r\n        ', 'Staff', '2025-08-19 08:58:35'),
(19, 31, 'Alicaya, Ralph Lorync D.', 'phennybert@gmail.com', 3, '2025-08-25', '2025-08-31', '2025-08-26 12:36:35', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>3 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-26 12:36:35'),
(20, 31, 'Alicaya, Ralph Lorync D.', 'phennybert@gmail.com', 3, '2025-08-25', '2025-08-31', '2025-08-26 12:38:12', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>3 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-26 12:38:12'),
(21, 31, 'Alicaya, Ralph Lorync D.', 'phennybert@gmail.com', 3, '2025-08-25', '2025-08-31', '2025-08-26 12:38:21', 'failed', '\r\n            <div style=\'font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\'>\r\n                <h2 style=\'color: #2563eb;\'>Clinic Medication Visit Alert</h2>\r\n                <p>Dear Parent/Guardian,</p>\r\n                <p>We are writing to inform you that your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>3 times</strong> this week (Monday to Sunday).</p>\r\n                <div style=\'background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #374151;\'>Medication Visit Details This Week:</h3>\r\n                    <p style=\'margin-bottom: 0;\'></p>\r\n                </div>\r\n                <p>We recommend that you:</p>\r\n                <ul>\r\n                    <li>Check up on your child\'s health and wellbeing</li>\r\n                    <li>Contact the clinic if you have any concerns about the frequent medication needs</li>\r\n                    <li>Consider scheduling a consultation to discuss any ongoing health issues</li>\r\n                    <li>Review if there are any patterns or triggers that might be causing frequent visits</li>\r\n                </ul>\r\n                <p>Multiple medication visits in a week may indicate an underlying health concern that should be addressed.</p>\r\n                <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>\r\n                <p style=\'margin-top: 30px;\'>\r\n                    Best regards,<br>\r\n                    <strong>Clinic Management Team</strong>\r\n                </p>\r\n            </div>\r\n        ', 'Staff', '2025-08-26 12:38:21'),
(22, 31, 'Alicaya, Ralph Lorync D.', 'jaynujangad03@gmail.com', 4, '2025-08-25', '2025-08-31', '2025-08-26 14:57:10', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>4 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-08-26 14:57:10'),
(23, 31, 'Alicaya, Ralph Lorync D.', 'jaynujangad03@gmail.com', 5, '2025-08-25', '2025-08-31', '2025-08-26 15:01:42', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>5 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-08-26 15:01:42'),
(24, 31, 'Alicaya, Ralph Lorync D.', 'jaynujangad03@gmail.com', 6, '2025-08-25', '2025-08-31', '2025-08-26 15:09:34', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>6 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-08-26 15:09:34'),
(25, 31, 'Alicaya, Ralph Lorync D.', 'cedricjade13@gmail.com', 7, '2025-08-25', '2025-08-31', '2025-08-28 16:44:21', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Alicaya, Ralph Lorync D.</strong>, has received medication from the clinic <strong>7 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-08-28 16:44:21');
INSERT INTO `parent_alerts` (`id`, `patient_id`, `patient_name`, `parent_email`, `visit_count`, `week_start_date`, `week_end_date`, `alert_sent_at`, `alert_status`, `email_content`, `sent_by`, `created_at`) VALUES
(26, 21, 'Abella, Joseph B.', 'cedricjade13@gmail.com', 5, '2025-09-29', '2025-10-05', '2025-10-02 21:15:04', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Abella, Joseph B.</strong>, has received medication from the clinic <strong>5 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-10-02 21:15:04'),
(27, 25, 'Abellana, Ariel L', 'cedricjade13@gmail.com', 4, '2025-09-29', '2025-10-05', '2025-10-02 21:20:42', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Abellana, Ariel L</strong>, has received medication from the clinic <strong>4 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-10-02 21:20:42'),
(28, 22, 'Abellana, Vincent Anthony Q.', 'cedricjade13@gmail.com', 4, '2025-09-29', '2025-10-05', '2025-10-02 21:22:17', 'sent', '<strong>Dear Parent/Guardian,</strong><br><br>Your child, <strong>Abellana, Vincent Anthony Q.</strong>, has received medication from the clinic <strong>4 times</strong> this week.<br><br>Please check up on your child\'s health and contact the clinic if you have any concerns.<br><br>Best regards,<br>Clinic Management Team', 'staff', '2025-10-02 21:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `token`, `expires_at`, `used`) VALUES
(1, 8, 'jaynujangad03@gmail.com', 'b68dce0b2b01552f8d5187dd693efb3a145e93621c0ac763bf48d0863496251d', '2025-05-18 20:33:51', 0),
(2, 8, 'jaynujangad03@gmail.com', '45887e86db9de66e72a555fa387df767f0b5b7a90a8acc3e87276076f2bc4898', '2025-05-18 20:33:53', 0),
(3, 8, 'jaynujangad03@gmail.com', '4e88e49df878cc71abcebb6b7aa3f03228e3a3bfe969ad7131f9d362548417b7', '2025-05-18 20:33:54', 0),
(4, 8, 'jaynujangad03@gmail.com', '4af804095667fb6e1eb0827e082edefc10698484bdd5539dfc93294f6e3b7412', '2025-05-18 20:33:54', 0),
(5, 8, 'jaynujangad03@gmail.com', 'dc39528256939894c46893eb8e44f985eaa012781a4c4534a376df2d56187d7a', '2025-05-18 20:33:54', 0),
(6, 8, 'jaynujangad03@gmail.com', '05952cb144b4fdd1ed7a0a6bbd1f5b4e1af67daf18a8c1a27ccfeee8f7a26a86', '2025-05-18 20:33:54', 0),
(7, 8, 'jaynujangad03@gmail.com', '5eae727002fb6bb8429eaa9d6cd55c790b7b75bc6c17e379582a63e6c357b558', '2025-05-18 20:33:54', 0),
(8, 8, 'jaynujangad03@gmail.com', 'cf044cab8177407d6765388d40c22aa865d2de4613ba3b1cf5a64e22056c745e', '2025-05-18 20:33:55', 0),
(9, 8, 'jaynujangad03@gmail.com', 'a2eae7ec7604a517d8d5e2e103d6eae7a8ef17baf48d65a0cd4642481ec563b3', '2025-05-18 20:33:56', 0),
(10, 8, 'jaynujangad03@gmail.com', 'c375c7e1764e65b2cab3e1d6e5301a9c6883e7e80cc232f17e7b67b4824ba8e2', '2025-05-18 20:33:56', 0),
(11, 8, 'jaynujangad03@gmail.com', '5dee716808517b38b8fa2ffe88db23e050eff925f912b99e3a9f17a40b3b80fe', '2025-05-18 20:33:56', 0),
(12, 8, 'jaynujangad03@gmail.com', '08633010b17969b02618ea772554c96760958eb82c7c170659009fbdda6f2091', '2025-05-18 20:33:56', 0),
(13, 8, 'jaynujangad03@gmail.com', '490f5e1dd8616537c36b4465971bd0f60af469d8666634d9a200ed233a2bfb5b', '2025-05-18 20:33:56', 0),
(14, 8, 'jaynujangad03@gmail.com', '106255bd32c7b28fe1993d182b8a825d9b10fc3a3f45a8dca965e685fcdcf9e0', '2025-05-18 20:33:57', 0),
(15, 8, 'jaynujangad03@gmail.com', 'ed7b646ff5e601ebe0fe1728b6feb7450239a294074b726868bb09f879131183', '2025-05-18 20:33:58', 0),
(16, 8, 'jaynujangad03@gmail.com', '81cac3332e6f7a06c763e7026f2685eff79912d605ce3fbfb27ec573f1873d0d', '2025-05-18 20:33:58', 0),
(17, 8, 'jaynujangad03@gmail.com', '29d933f866f94df8d952f86900ea464dda3dec91be517507a01cf6693e59cd61', '2025-05-18 20:33:58', 0),
(18, 8, 'jaynujangad03@gmail.com', '26a203871c437098a9d6701c7a67d0bfe13eb37cd489d9a35ba1558e804ef56b', '2025-05-18 20:33:58', 0),
(19, 8, 'jaynujangad03@gmail.com', '0301f7b4ee987dde4a4a5de07eb386f5922af8ca1b00c0c50c192c2e00e08001', '2025-05-18 20:33:59', 0),
(20, 8, 'jaynujangad03@gmail.com', 'b504489bc664b34a7606daf939467e2cc4f7a68327b2b121ba58c0e7892489e8', '2025-05-18 20:34:46', 0),
(21, 8, 'jaynujangad03@gmail.com', '97178842f202ea4c96e6dc32b389fc739d52d7052f0f8e07ef3d896977f075c0', '2025-05-18 20:34:46', 0),
(22, 8, 'jaynujangad03@gmail.com', '84c0484b921a18be611bd55eb3bb971a92bed88ffd746c73e0473f8cbda83a02', '2025-05-18 20:36:36', 0),
(23, 8, 'jaynujangad03@gmail.com', 'f80ea4ab88f4463e03b219e912db188ad16c159fc05bb6ea985e6c2620da660a', '2025-05-18 20:36:37', 0),
(24, 8, 'jaynujangad03@gmail.com', '6f53b1025e81ee1a4ee10009dfb9290869e94ca42dce5911febb44d2d511a46e', '2025-05-18 20:36:37', 0),
(25, 8, 'jaynujangad03@gmail.com', '9298fc1b7b26fd6bf68377aaebe883d0dd445b79aa72b57c08dc093c79c2d33b', '2025-05-18 20:36:38', 0),
(26, 8, 'jaynujangad03@gmail.com', 'ea17a1ad03fbfb73722abd4dd5d25a01b966d4c588814e2f3b6b72f7145c69b9', '2025-05-18 20:36:38', 0),
(27, 8, 'jaynujangad03@gmail.com', '44fb0f34e3f5f7232bafcf641689cc4fdaf4ac27ba0627ace0c5d4e584cb578e', '2025-05-18 20:36:38', 0),
(28, 8, 'jaynujangad03@gmail.com', 'd1b1dd51e321192988476605c0443aa3d16c6be977bc164ce9b8435b9aa27ed3', '2025-05-18 20:36:38', 0),
(29, 8, 'jaynujangad03@gmail.com', '384245c5759c2611ce52bd1c19d9cf4054693f11771685f6d0eb846c44d95190', '2025-05-18 20:36:39', 0),
(30, 8, 'jaynujangad03@gmail.com', 'f69b9d4a60ee99479ef2af76f4d4ae67f72cdee52d4f1339034f9175467f2bde', '2025-05-18 20:36:39', 0),
(31, 8, 'jaynujangad03@gmail.com', 'ac4231712a3d0935a0eb6e4c81640e557e19fa1b22f5c0758b13d688ce7f0a7a', '2025-05-18 20:36:40', 0),
(32, 8, 'jaynujangad03@gmail.com', 'bb773c22bca0eaa2b25d7bbdf98d836d703d30e429df44a1d918733d3f7aba33', '2025-05-18 20:36:40', 0),
(33, 8, 'jaynujangad03@gmail.com', 'a03302d4dc6bf4756f48cb0952a26256852c1d330106c973363e6136e0f7cebe', '2025-05-18 20:36:40', 0),
(34, 8, 'jaynujangad03@gmail.com', 'b79c2f51eb93e01ecdf68004e890c649ae590e3dd726b658454a2fd945faf364', '2025-05-18 20:36:40', 0),
(35, 8, 'jaynujangad03@gmail.com', '68f3e61925e466399b618500942e36ec27300ae2fe8e4746424a1d791ce5f15d', '2025-05-18 20:36:41', 0),
(36, 8, 'jaynujangad03@gmail.com', '741f99d02c8223acfa9193e0e3320842ea37cc84a8a19aac29af6d421feef3ee', '2025-05-18 20:36:42', 0),
(37, 8, 'jaynujangad03@gmail.com', 'db179a54d81e13e314906690975958e80c5a0a3e53f2d90275e66763cc7fb3aa', '2025-05-18 20:36:42', 0),
(38, 8, 'jaynujangad03@gmail.com', 'c0ef3255a4dd2b56c75c395288b2029b34bb279be468a2bf7902b20c24d49da0', '2025-05-18 20:36:43', 0),
(39, 8, 'jaynujangad03@gmail.com', '2f47970775239a27ecca77b652363f8e601d0c0061a1222b89f6a8bbf162e2f4', '2025-05-18 20:36:44', 0),
(40, 8, 'jaynujangad03@gmail.com', '6775695e771563b513087429822eb6eca0548b80b3a2bfb194648baa3b16deaa', '2025-05-18 20:36:44', 0),
(41, 8, 'jaynujangad03@gmail.com', 'bac02050d152150941b680e8a862997870439aece8d0220a5153967153bb7c95', '2025-05-18 20:42:12', 0),
(42, 8, 'jaynujangad03@gmail.com', '5cda39fea5bf7ce29facb0a433bef38950e46833739a6fbe5c706716e23883e5', '2025-05-18 20:42:12', 0),
(43, 8, 'jaynujangad03@gmail.com', '583ed3393517af62de33d6eba073a4db62dbfb0bf974864f4d47352aa2417272', '2025-05-18 20:42:13', 0),
(44, 8, 'jaynujangad03@gmail.com', '72f2e93c41554653a6e1d4553220090982c79ffc3d44c65478dba3b7d4ea52b6', '2025-05-18 20:42:33', 0),
(45, 8, 'jaynujangad03@gmail.com', '9390fb8afdedf7cbb965cbbf336a137a13340f7e733f5c06289470cd090bf4eb', '2025-05-18 20:42:33', 0),
(46, 8, 'jaynujangad03@gmail.com', '0cda9b00e26780589b217c29117e981be1428324750e49cdd9f9495e3a271698', '2025-05-18 20:45:14', 0),
(47, 8, 'jaynujangad03@gmail.com', '4169bd450777973927c7701e62088b970c07c8881c365f36cd0332a5c3d92687', '2025-05-18 20:45:15', 0),
(48, 8, 'jaynujangad03@gmail.com', 'b0654cdb60a2637158f064c85504b3a1f9992d4d819450e01ee49a6537d9c17c', '2025-05-18 20:45:15', 0),
(49, 8, 'jaynujangad03@gmail.com', '3f74101b6a5fa5412d67d1f1b30998646b5680ffa13dbcfff5bbf3cf2672651a', '2025-05-18 20:45:16', 0),
(50, 8, 'jaynujangad03@gmail.com', 'b46f5e70b9f28c68f469f274cfbd4b7a167721775a8c8ffcdffd417d769db123', '2025-05-18 20:45:16', 0),
(51, 8, 'jaynujangad03@gmail.com', '9a32ba5a3e840a7a52e9d8861f473546fe04b52a680c934bcb07eb41573e2d40', '2025-05-18 20:45:16', 0),
(52, 8, 'jaynujangad03@gmail.com', '50977b8450f14725ad17feee19bfe96399ea59dc247ba92d98503fa41ac895cd', '2025-05-18 20:45:16', 0),
(53, 8, 'jaynujangad03@gmail.com', '49a6cd982c6b616f725259dd9e65fe19a0bdc7fd7137f975d4ed5a2a24134ea5', '2025-05-18 20:45:17', 0),
(54, 8, 'jaynujangad03@gmail.com', '30aa01428ba10a0a04a494c04c2a6c2232a5127a9492d02f1d968fa488b9e826', '2025-05-18 20:45:17', 0),
(55, 8, 'jaynujangad03@gmail.com', '36ef9a6f2bb4f4595dd65603e6b6760bf879a6ecd4c79134fbfb94d2403863bf', '2025-05-18 20:45:18', 0),
(56, 8, 'jaynujangad03@gmail.com', '1c2993880eb782882f801634d3f703070da08bb7c3c27460c417fd817d132eb1', '2025-05-18 20:45:18', 0),
(57, 8, 'jaynujangad03@gmail.com', 'b5c2415e629601e0f08396983aac39d13d49af66a5c71b0ce89f987132966592', '2025-05-18 20:45:18', 0),
(58, 8, 'jaynujangad03@gmail.com', '45c8e53e66250b408d546b227d564b953bd94a57c9c3ee894d5c71acb30160b6', '2025-05-18 20:45:18', 0),
(59, 8, 'jaynujangad03@gmail.com', '115f659e255b5a2512720ae41415da1b9abb377cd6e15c1aff941b5ac2dcd79b', '2025-05-18 20:45:19', 0),
(60, 8, 'jaynujangad03@gmail.com', 'd51fd70d6df396c2b2b8e731b8a359eda505dcac29eb697fd7c506c23ede8b8f', '2025-05-18 20:45:19', 0),
(61, 8, 'jaynujangad03@gmail.com', 'c9624fa6a43ef34877a45909f53e0e4a5864c34dd2361f77aea00ba7d5864226', '2025-05-18 20:45:47', 0),
(62, 8, 'jaynujangad03@gmail.com', 'd4e17119c64fb55a45c73d81d8cf54038943684a67d3f81d67ca6366ec6549c0', '2025-05-18 20:45:48', 0),
(63, 8, 'jaynujangad03@gmail.com', 'b211be78e41221a65b3f7fa385ff8739778aa1ea3017a2cedb6c0faba1689c04', '2025-05-18 20:45:49', 0),
(64, 8, 'jaynujangad03@gmail.com', '748817edce26be3502113f908246cbd20e94af8b67a036aa94ae99c2bc10ebb6', '2025-05-18 20:45:49', 0),
(65, 8, 'jaynujangad03@gmail.com', '24f07ff3df4392aa6f25c7723b9cd4063f50af3d9dfcfc88507ef4b85ecb12d9', '2025-05-18 20:45:49', 0),
(66, 8, 'jaynujangad03@gmail.com', '1d89580eb1321cb202b6e489ad1b3e737ec3d6736cf85b3a910f240172c6f0f9', '2025-05-18 20:45:54', 0),
(67, 8, 'jaynujangad03@gmail.com', 'c1bfc73479245237c81bb5b1e9ff122c7d67698ab1de24cb575bb128cb0c070c', '2025-05-18 20:45:55', 0),
(68, 8, 'jaynujangad03@gmail.com', 'c6d049ae24d086d499336a69fcb712b0894e7ffea272f0ff19054982d68b6d93', '2025-05-18 20:45:56', 0),
(69, 8, 'jaynujangad03@gmail.com', '595f32076bf5cf2d8dd33718e2f4704ccc8f3f78fe4391509e4d230fd1c6cc1f', '2025-05-18 20:45:57', 0),
(70, 8, 'jaynujangad03@gmail.com', '471a85c5cbb47b696833e3b1a410d4ca4d18a7a0c2a8296004c64722b5b45a29', '2025-05-18 20:45:57', 0),
(71, 8, 'jaynujangad03@gmail.com', 'dc0e2be3b066c44a6b83691807136fc23416d25ca75e4094c20e8ac5d925254d', '2025-05-18 20:48:40', 0),
(72, 8, 'jaynujangad03@gmail.com', '2ee561447f12f28c00cb2739a946798bf453c25ef9f968dc0e36bcb8e066d37f', '2025-05-18 20:48:41', 0),
(73, 8, 'jaynujangad03@gmail.com', '51c64e652c3a540946a51c7e703f482b978651040ec44cb0f9bc6529b46315cc', '2025-05-18 20:48:41', 0),
(74, 8, 'jaynujangad03@gmail.com', '5708a17c6c5010034b488cb376a286350c1fb66a8f9c68f1781e056601683e91', '2025-05-18 20:48:41', 0),
(75, 8, 'jaynujangad03@gmail.com', 'b68ac50b6d560d4cce2b8aafd932e54975ded50d5950c41a548abcc771418d37', '2025-05-18 20:48:41', 0),
(76, 8, 'jaynujangad03@gmail.com', '8a4f10c89d0b3993ec23a1e1c5c8287101900b4758b843461c401ed7eab0d60c', '2025-05-18 20:48:42', 0),
(77, 8, 'jaynujangad03@gmail.com', 'dbb59b6fa2ee080c66e0c30db28096ff264ba61858fd124a0f0660c904f24719', '2025-05-18 20:48:42', 0),
(78, 8, 'jaynujangad03@gmail.com', 'd2a07f735802d1aa06b7400ed13aeea6996a61389ae15f3da1a76eb15d713f50', '2025-05-18 20:48:43', 0),
(79, 8, 'jaynujangad03@gmail.com', '3d47f282e1a9fd1b2bbefd498b244d0beb190c9ed4a874c4aad7b621cf36a07b', '2025-05-18 20:48:43', 0),
(80, 8, 'jaynujangad03@gmail.com', '1cd4da83dc36b6c5f64aa45f9a5fd91bbcddf9259572da42a73eec54a4b44aa2', '2025-05-18 20:48:43', 0),
(81, 8, 'jaynujangad03@gmail.com', '1fd240b24e582c7d326b36726ea857be98aeb268ecec889a99f78b1ec56cda4c', '2025-05-18 20:48:43', 0),
(82, 8, 'jaynujangad03@gmail.com', 'ebfc5351a0416f67eea943fba7a65cce2eb5aaed65c2783b32b2f1b2ee582b49', '2025-05-18 20:48:44', 0),
(83, 8, 'jaynujangad03@gmail.com', '6e06895eeade7d9cb150794ef0fcdbbbb61fd6a70df995f46f233383ccd8cd68', '2025-05-18 20:48:44', 0),
(84, 8, 'jaynujangad03@gmail.com', 'dd7069dd33478e1f07fb20f9bba57b3dfa7d6099275e3b7513a42b362febad62', '2025-05-18 22:15:56', 0),
(85, 8, 'jaynujangad03@gmail.com', '1651f55a223dc98728dd3dc32558f697e12176c6347ba9502788e23b089cb0af', '2025-05-18 22:15:56', 0),
(86, 8, 'jaynujangad03@gmail.com', 'd303d9f5c4440d943de8acd2f65aa891f08b8614b0642e805cc40223744119d3', '2025-05-18 22:15:57', 0),
(87, 8, 'jaynujangad03@gmail.com', '080c4d936e8040aa2e4f44b356ec0ffdcb0bdfc8d53c140bc9988e6dbdedaf5a', '2025-05-18 22:15:57', 0),
(88, 8, 'jaynujangad03@gmail.com', '9007a009051c54de791162316cfe445dd31679f98b8ca3bb47a4fe387bc0a334', '2025-05-18 22:15:58', 0),
(89, 8, 'jaynujangad03@gmail.com', 'da01be76bfe467698ed4ecae31c670e023eb506aace5102e887a1d9e55dee049', '2025-05-18 22:15:58', 0),
(90, 8, 'jaynujangad03@gmail.com', '3fed7c4b24321ecf15bf9a27b1a1894251eb8a18a06920950933ae9544cea971', '2025-05-18 22:15:58', 0),
(91, 8, 'jaynujangad03@gmail.com', '00ef796f08454f3fab612a600edc7493b9af11e41e0fe145be1053166730291b', '2025-05-18 22:15:58', 0),
(92, 8, 'jaynujangad03@gmail.com', '449944897493388a97f83d8ee4247c7ef8c6ff621e5e68a3be139c6f5a58b010', '2025-05-18 22:15:59', 0),
(93, 8, 'jaynujangad03@gmail.com', '21254ace4c9f0fb737cb9d28e09d9354e01451d08f7ca6e312f15a770c4f74b1', '2025-05-20 01:20:32', 0),
(94, 8, 'jaynujangad03@gmail.com', '8bc55aa0340c42e15ccfffe8cd880267d06d7e8732af43b44bed9960e6e7d8e3', '2025-05-20 01:20:35', 0),
(95, 8, 'jaynujangad03@gmail.com', '4f4f8e1e00a0381d2a31dbaa1592df46628cc0cc0fd232196869fdc7eaa64422', '2025-08-26 12:47:50', 0),
(96, 8, 'jaynujangad03@gmail.com', 'ddcba124a0b407b45db5a24627d5d1f49c4216590b222c925a501dc9909ab289', '2025-08-26 12:47:55', 0),
(97, 8, 'jaynujangad03@gmail.com', '25ba2535ce7896c94bfac7164331668e491f20c1e320bd342d3441b325444dfa', '2025-08-26 12:49:58', 0),
(98, 8, 'jaynujangad03@gmail.com', '6f0f807f66e418892e70298589e42bcfbe517298eb1d863d226825920b3f74c6', '2025-08-26 12:50:26', 0),
(99, 8, 'jaynujangad03@gmail.com', '5bdf07b9ce2db77e17cf25ca96b00008ec0a74ba115b3db5279d3f3b060b1356', '2025-08-26 12:52:03', 0),
(100, 8, 'jaynujangad03@gmail.com', 'e4ca5ad0bcb3967721c1f9a5fe29c28cfe79689815beb4e68c344e6d589e97d5', '2025-08-26 12:52:14', 0),
(101, 8, 'jaynujangad03@gmail.com', 'a202de18236bf21cb54d0ea137c73cdca7a51f69af0b2fcbd19119e18e0be1be', '2025-08-26 12:52:50', 0),
(102, 8, 'jaynujangad03@gmail.com', 'e73b24fafd6824317d91c84cfd036c0623a60711292d70b8c51387b961e416ae', '2025-08-26 12:54:44', 0),
(103, 11, 'jaynujangad03@gmail.com', '6a3faeb492614a2af376cb6db6b6ddbf9b9b0a889df9d2e51f3eaa0b2093555c', '2025-08-26 12:57:05', 0),
(104, 11, 'jaynujangad03@gmail.com', '5c8d675cfd9e82cf6d1e0a2a69a87ecbe0d4b964c7951f01a87570a196af361c', '2025-08-26 13:01:04', 0),
(105, 11, 'jaynujangad03@gmail.com', '2b031659a53cef22bd8d15d0af6dbc7ccbf89e0c69c5bd5ceb2c3f04a6d1d4f2', '2025-08-26 13:06:04', 0),
(106, 11, 'jaynujangad03@gmail.com', '7e466c20445f0b73a305b565b1f8b9351dca9fba231234ab9e8f0ff1d1f0a595', '2025-08-26 13:06:19', 0),
(107, 11, 'jaynujangad03@gmail.com', 'a159bd5c5a31573c3326685ccf0289099dc8177cb4ec3fd7616912ba19f27d42', '2025-08-26 13:07:43', 0),
(108, 11, 'jaynujangad03@gmail.com', 'd693cc0ae0fc834eefc30f1f81ddfd604627d9cfc7b74ec9b764893933260ec2', '2125-08-26 12:09:43', 0),
(109, 11, 'jaynujangad03@gmail.com', 'c6868a42c1c22b1b12bb0e9f4725e28c5965fcd27b69f9ec86d728341fbb9e53', '2125-08-26 12:13:37', 0),
(110, 11, 'jaynujangad03@gmail.com', '3b60ca40626d1bbc82d27e501e956e18ff2b6321001dff683640a0ac2fd56c51', '2125-08-26 12:17:27', 0),
(111, 11, 'jaynujangad03@gmail.com', 'aa504285358c029c39508e852d2a4fcd95a67946337c76ce11aae80a5c964fde', '2125-08-26 12:19:09', 0),
(112, 11, 'jaynujangad03@gmail.com', '52b61f442fd8f080244deb453448936cd72151a188ba91dfe5bdefb8bfab665e', '2125-08-26 12:26:18', 0),
(113, 11, 'jaynujangad03@gmail.com', 'b3f8db4a41eef24ea81a371bd97077f4c4c3d71dfc139e918281905bec3534da', '2125-08-26 12:34:07', 0),
(114, 11, 'jaynujangad03@gmail.com', 'a94e58bf3b5eabac424fcfa0804750b731810e38dcd5d95be40182d8597017a2', '2125-08-26 12:35:10', 0),
(115, 11, 'jaynujangad03@gmail.com', 'e06918e5b5834daf3c9729c0fe53ad9043c13f7944a91f552e5d4fef18af7527', '2125-08-26 12:38:06', 0),
(116, 11, 'jaynujangad03@gmail.com', 'f30127a3064416c3430338a57f71867f93d1f78cf4f11e0e75ef7d7f5878e3dc', '2125-08-26 12:43:33', 0),
(117, 11, 'jaynujangad03@gmail.com', '2c5fb2f85a4b3d83b368023239e1b70f55dd6ff5d72e929a61535e98522c7c3f', '2125-08-26 12:46:03', 1),
(118, 11, 'jaynujangad03@gmail.com', 'a9fc0335885ea8371d9c55eec2dd542afb15b06a831a043b6203ea233ed652a9', '2125-08-26 12:47:23', 1),
(119, 11, 'jaynujangad03@gmail.com', '6322e7ff002dfff415a72083b414f7c56cc065d331c33ff106b4cd11d0eb9a73', '2125-08-26 12:50:28', 1),
(120, 11, 'jaynujangad03@gmail.com', 'c1b171e4cb59a361164a20278284de234534f5a2c3935c2752ca01efe36d591f', '2125-08-26 12:52:24', 1),
(121, 11, 'jaynujangad03@gmail.com', '2d86ee25d532b713911deace5fb1e1f3af789b136d2e69d9ce21b496bb8d09f4', '2125-08-26 12:55:39', 1),
(122, 11, 'jaynujangad03@gmail.com', '347c34269ee9f01d46da50ebcbb699fb971e130da6e45b52f59460c8a9c93eff', '2125-08-26 12:56:53', 0),
(123, 11, 'jaynujangad03@gmail.com', 'c71d2b83bfb3862b49ec01364d4924aa19c438a82040a010e6e92e08815e38f0', '2125-08-26 12:58:31', 0),
(124, 11, 'jaynujangad03@gmail.com', '3843fdc65818e8d29c2c390e84d46a1b129b92ae3dbb797dc31f34d85765c4c4', '2125-08-26 13:02:19', 0),
(125, 11, 'jaynujangad03@gmail.com', 'ffba7b3ac2afc6c4d999d54629f72420a6a23f167efffd97067b39928cd49964', '2125-08-26 13:04:18', 0),
(126, 11, 'jaynujangad03@gmail.com', '7140ff3780ccaa2d9fcefff80bf556908b4c8c413a0e0ae8794ae7e0f58dbc8a', '2125-08-26 13:06:04', 0),
(127, 11, 'jaynujangad03@gmail.com', '51a0fda70eb87173550bbe1c1472d6e15079d28fdfd0da02100ef21368b424d1', '2125-08-26 13:07:51', 0),
(128, 11, 'jaynujangad03@gmail.com', '7496b34ebf00a1bb8b54438e7d9e9952b9f6a79bad05f50203141ba03bda7f09', '2125-08-26 13:09:37', 0),
(129, 11, 'jaynujangad03@gmail.com', 'b8301291725f5d251dd319b9fbdc33d680591939e502f8e7e90f50a5c3ef519e', '2125-08-26 13:11:32', 1),
(130, 11, 'jaynujangad03@gmail.com', '44e3c6c26fec7b2e5e38e58c34e49d9ca90105080a4ff18b4a6806c7bc7ef659', '2125-08-26 13:12:06', 0),
(131, 11, 'jaynujangad03@gmail.com', '4075c48fdb089e67316e5fc02d08e0fa50ae0eba45d1a0a523e9b0127c624264', '2125-08-26 13:14:34', 0),
(132, 11, 'jaynujangad03@gmail.com', 'cff7d823ff58b2808439341b2df47ca63aecd86ad922c0aaa86e7623a11f379e', '2125-08-26 13:15:43', 0),
(133, 11, 'jaynujangad03@gmail.com', 'babc59cc695ef2df6f61d7f3d578e1d30f00cfe2218dc7dc309b6f572c2be895', '2125-08-26 13:20:54', 0),
(134, 11, 'jaynujangad03@gmail.com', 'bfb68454c1464f2708b0390141292db13a3ed0cd8bc45809af6d003a181488d2', '2125-08-26 13:24:16', 0),
(135, 11, 'jaynujangad03@gmail.com', '79b1fae3383ad4be11b652468425982c98a2e0abfc0e823ef3fede08d0c5cae3', '2125-08-26 13:36:39', 1),
(136, 11, 'jaynujangad03@gmail.com', '6810dde0194ed8a3813624962b08e445842220a5a8879e3c527b40d14b704f0d', '2125-08-26 13:42:05', 1),
(137, 11, 'jaynujangad03@gmail.com', '8f6c2b5dafed8b9a5cbcdf35360b578a450cb3ebb6ca1d0fb8d933f70660ed08', '2125-08-26 13:44:13', 1),
(138, 11, 'cedricjade13@gmail.com', '8b8fa2edd2516ca18b725dcb8215cd3a6eaa12d8e29afce14acb1e015d4157b5', '2125-08-28 06:55:16', 1),
(139, 11, 'cedricjade13@gmail.com', 'b51f31eb258fcc0c18e03e523b538d900022330f702c46236ae943bedc06fc8f', '2125-08-31 16:19:58', 1),
(140, 11, 'cedricjade13@gmail.com', '47bf715bdea8a5643905f8b82cda98c63d312e0dcd49f7980ec4834420949214', '2125-08-31 16:21:10', 1),
(141, 11, 'cedricjade13@gmail.com', 'f0d35d97caf1d1cea5f6d6f1ae48389c2db36fdf585f5c5e6e34d19f0635239b', '2125-09-15 16:55:40', 0),
(142, 10, 'cedricjade13@gmail.com', 'bc4faba5a4eba94af2c798e12671555158454d309096d64ee5f0e4218ab89e5b', '2125-10-02 09:06:17', 1),
(143, 10, 'cedricjade13@gmail.com', '59a294c0a84706af0f975e3efd5c2ea9f56496c8f9a0ff3f983d8363ecd6a66f', '2125-10-02 09:06:19', 0),
(144, 11, 'jaynujangad03@gmail.com', '2710ec7c7764b4181d1eaf0be826cf6d667c36775f69a86da9ed461e36daf031', '2125-10-02 09:08:51', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pending_prescriptions`
--

CREATE TABLE `pending_prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `patient_email` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `prescribed_by` varchar(255) DEFAULT NULL,
  `prescription_date` datetime DEFAULT current_timestamp(),
  `medicines` text DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `patient_email` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `parent_phone` varchar(32) DEFAULT NULL,
  `prescribed_by` varchar(255) DEFAULT NULL,
  `prescription_date` datetime DEFAULT current_timestamp(),
  `medicines` text DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `summary` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `date`, `type`, `summary`, `created_at`) VALUES
(1, '2025-08-22', 'Visits', 'Total: 126 visits', '2025-08-22 12:44:40'),
(2, '2025-08-21', 'Medications', 'Diatabs low stock', '2025-08-22 12:44:40'),
(3, '2025-08-21', 'Medications', 'No soon-to-expire', '2025-08-22 12:44:40'),
(4, '2025-08-20', 'Appointments', '4 pending appointments', '2025-08-22 12:44:40'),
(5, '2025-08-19', 'Inventory', 'Monthly stock review completed', '2025-08-22 12:44:40'),
(6, '2025-08-18', 'Visits', 'Weekly patient summary', '2025-08-22 12:44:40'),
(7, '2025-08-17', 'Medications', 'Prescription analysis report', '2025-08-22 12:44:40'),
(8, '2025-08-16', 'Appointments', 'Daily appointment summary', '2025-08-22 12:44:40'),
(9, '2025-08-15', 'Inventory', 'Low stock alert report', '2025-08-22 12:44:40'),
(10, '2025-08-14', 'Visits', 'Patient demographics analysis', '2025-08-22 12:44:40'),
(11, '2025-08-13', 'Medications', 'Drug interaction review', '2025-08-22 12:44:40'),
(12, '2025-08-12', 'Appointments', 'Missed appointments report', '2025-08-22 12:44:40'),
(13, '2025-08-11', 'Inventory', 'Expiry date monitoring', '2025-08-22 12:44:40'),
(14, '2025-08-10', 'Visits', 'Treatment outcome analysis', '2025-08-22 12:44:40'),
(15, '2025-08-09', 'Medications', 'Prescription volume report', '2025-08-22 12:44:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(225) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Profile image file path/URL for admin users',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `role`, `status`, `password`, `profile_image`, `phone`, `address`, `department`) VALUES
(10, 'Cedric Jade Pinili Getuaban', 'admin', 'cedricjade13@gmail.com', 'admin', 'Active', '$2y$10$jMIhuCjuVt9MQIOAjfMxpuZ92HV84Xga7Cu.gzwfWMz3vrxZu7dfu', 'uploads/profiles/profile_68c7e94d7f7960.02581633.jpg', NULL, NULL, NULL),
(11, 'Janey Jangad', 'staff', 'jaynujangad03@gmail.com', 'doctor/nurse', 'Active', '$2y$10$LgTfdrlUqfD3nEDjsHNCs.QQRM4rCq3tNhMo3tgk0SeESsIEj1Y1W', 'uploads/profiles/staff_11_1757950909.jpg', '', '', ''),
(26, 'Grant Mcintyre', 'vepif', 'syxoren@mailinator.com', 'doctor/nurse', 'Active', '$2y$10$IfQXB.Cxv7726OR9MrnoNe1O3uQXsxQmIpdTADICD282lFd/RY1sK', NULL, NULL, NULL, NULL),
(27, 'Helen Pratt', 'vexuwezyho', 'vegonyjem@mailinator.com', 'doctor/nurse', 'Active', '', NULL, NULL, NULL, NULL),
(28, 'Rafael Hays', 'nekejaba', 'mozifuxedu@mailinator.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(29, 'Risa Parrish', 'bazopeli', 'letafu@mailinator.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(30, 'Alec Houston', 'hijihynol', 'copabinuxy@mailinator.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(31, 'atay', 'asdas', 'd@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(32, 'yati', 'ka', 'ka@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(33, 'jaynu', '213123', '123@gmaio.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(34, 'test', 'test', 'test@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(36, 'jaynu', 'hehe', 'test@gmail.com', 'doctor/nurse', 'Active', '', NULL, NULL, NULL, NULL),
(37, 'hahay', 'hahay', 'hahay@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(38, '213', '123', 'ced@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(39, 'jay', 'jay', 'jay@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(40, 'ye', 'ye', 'ye@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(41, 'y', 'y', 'y@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(42, 'eyq', 'ey', 'ey@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(43, 'sh', 'sh', 'shh@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(44, 'jade', 'jade', 'jade@gmail.com', 'admin', 'Active', '', NULL, NULL, NULL, NULL),
(45, 'les', 'les', 'les@gmail.com', 'doctor/nurse', 'Active', '', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--

CREATE TABLE `visitor` (
  `visitor_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL CHECK (`age` >= 0),
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor`
--

INSERT INTO `visitor` (`visitor_id`, `full_name`, `age`, `gender`, `address`, `contact`, `emergency_contact`) VALUES
(1, 'John Michael Cruz', 28, 'Male', '123 Mabini St., Cebu City', '09171234567', '09187654321'),
(2, 'Maria Teresa Dela Cruz', 34, 'Female', '45 Bonifacio Ave., Manila', '09283456789', '09334567890'),
(3, 'Alex Reyes', 19, 'Female', '7 Lopez Jaena St., Iloilo', '09998887766', '09192345678'),
(4, 'Francis Javier Santos', 42, 'Male', '89 Rizal Blvd., Davao City', '09182345678', '09451239876'),
(5, 'Angela Mae Villanueva', 25, 'Female', 'Block 5 Lot 7 Greenfield Subd., Laguna', '09091231234', '09562348765'),
(6, 'Jade', 13, 'Male', 'Vito', '09123456789', '09213456789'),
(7, 'Brooke Pitts', 69, 'Male', 'Laudantium et vel q', 'Quia aut dolore illu', '09381234567'),
(8, 'Lionel Mcclure', 69, 'Male', 'Vero saepe id quaera', 'Et autem qui est non', '09471234567'),
(9, 'Signe House', 76, 'Female', 'Et et vitae laudanti', 'Odio quae ut ad cumq', '09581234567'),
(10, 'Nora William', 10, 'Female', 'Nostrud ex autem ess', 'Ut temporibus nulla', '09691234567'),
(11, 'Tara Christensen', 18, 'Female', 'Quidem cillum quos s', 'Aut recusandae Nequ', '09712345678'),
(12, 'Howard William', 98, 'Female', 'Dicta unde sed ipsum', 'Repellendus Ut inci', NULL),
(13, 'Amy Thompson', 82, 'Female', 'Minus non id sapient', 'Est esse omnis aute', NULL),
(14, 'Ivory Gross', 47, 'Female', 'Ut ipsa ut maiores', 'Natus Nam nulla null', 'Dicta dolor sequi ip'),
(15, 'Regina Sosa', 82, 'Male', 'Sunt officia quis ex', 'Sunt odio molestias', 'Adipisicing ut sunt'),
(16, 'Quon Guthrie', 16, 'Male', 'Itaque velit est ut', 'Deserunt vitae irure', 'Nam occaecat delenit'),
(17, 'Brody Munoz', 5, 'Male', 'Magna reiciendis und', 'Vel deleniti et aut', 'Et ut magni possimus'),
(18, 'Gannon Greene', 69, 'Male', 'Inventore enim eos', 'Nostrum in est dicta', 'Impedit sunt eligen'),
(19, 'Tanek Mckenzie', 53, 'Male', 'Laboriosam aute ut', 'Id impedit aut ver', 'Ullam sed quae magni'),
(20, 'Rebecca Sosa', 52, 'Female', 'Earum voluptas illo', 'Expedita voluptas ea', 'Ex anim et labore ma');

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

CREATE TABLE `vital_signs` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `faculty_name` varchar(100) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `visitor_name` varchar(255) DEFAULT NULL,
  `vital_date` date DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `body_temp` decimal(4,2) DEFAULT NULL,
  `resp_rate` int(11) DEFAULT NULL,
  `pulse` int(11) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `oxygen_sat` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_visit_summary`
--

CREATE TABLE `weekly_visit_summary` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `total_visits` int(11) DEFAULT 0,
  `visit_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visit_types`)),
  `last_visit_date` date DEFAULT NULL,
  `needs_alert` tinyint(1) DEFAULT 0,
  `alert_sent` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_date` (`patient_id`,`visit_date`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_patient_id` (`patient_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`doctor_name`,`schedule_date`,`schedule_time`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `imported_patients`
--
ALTER TABLE `imported_patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_level` (`level`),
  ADD KEY `idx_logs_timestamp` (`timestamp`),
  ADD KEY `idx_logs_user_email` (`user_email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medication_referrals`
--
ALTER TABLE `medication_referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_med_ref_visitor_id` (`visitor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient` (`recipient_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `parent_alerts`
--
ALTER TABLE `parent_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_week` (`patient_id`,`week_start_date`),
  ADD KEY `idx_alert_date` (`alert_sent_at`),
  ADD KEY `idx_status` (`alert_status`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_prescriptions`
--
ALTER TABLE `pending_prescriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`visitor_id`);

--
-- Indexes for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_patient_date` (`patient_id`,`vital_date`),
  ADD KEY `idx_vital_signs_visitor_id` (`visitor_id`),
  ADD KEY `idx_vital_signs_faculty_id` (`faculty_id`);

--
-- Indexes for table `weekly_visit_summary`
--
ALTER TABLE `weekly_visit_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_patient_week` (`patient_name`,`week_start_date`),
  ADD KEY `idx_needs_alert` (`needs_alert`),
  ADD KEY `idx_week_dates` (`week_start_date`,`week_end_date`),
  ADD KEY `idx_patient_name` (`patient_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `imported_patients`
--
ALTER TABLE `imported_patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=711;

--
-- AUTO_INCREMENT for table `medication_referrals`
--
ALTER TABLE `medication_referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2688;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1777;

--
-- AUTO_INCREMENT for table `parent_alerts`
--
ALTER TABLE `parent_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `pending_prescriptions`
--
ALTER TABLE `pending_prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `visitor`
--
ALTER TABLE `visitor`
  MODIFY `visitor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `vital_signs`
--
ALTER TABLE `vital_signs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `weekly_visit_summary`
--
ALTER TABLE `weekly_visit_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `imported_patients` (`id`);

--
-- Constraints for table `clinic_visits`
--
ALTER TABLE `clinic_visits`
  ADD CONSTRAINT `clinic_visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `imported_patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `medication_referrals`
--
ALTER TABLE `medication_referrals`
  ADD CONSTRAINT `medication_referrals_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `imported_patients` (`id`),
  ADD CONSTRAINT `medication_referrals_ibfk_2` FOREIGN KEY (`visitor_id`) REFERENCES `visitor` (`visitor_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `imported_patients` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);

--
-- Constraints for table `parent_alerts`
--
ALTER TABLE `parent_alerts`
  ADD CONSTRAINT `parent_alerts_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `imported_patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `imported_patients` (`id`);

--
-- Constraints for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD CONSTRAINT `vital_signs_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`),
  ADD CONSTRAINT `vital_signs_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `imported_patients` (`id`),
  ADD CONSTRAINT `vital_signs_ibfk_3` FOREIGN KEY (`visitor_id`) REFERENCES `visitor` (`visitor_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
