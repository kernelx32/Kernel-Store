-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2025 at 07:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kernel`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('available','reserved','sold') DEFAULT 'available',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `featured` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `reviews` int(11) DEFAULT 0,
  `rank` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `game_id`, `title`, `description`, `price`, `email`, `password`, `details`, `status`, `created_at`, `updated_at`, `discount`, `featured`, `rating`, `reviews`, `rank`) VALUES
(2, 5, 'rank', NULL, 100.00, '', '', NULL, 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(3, 1, 'gold', NULL, 200.00, '', '', NULL, 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(4, 5, 'dimond', NULL, 600.00, '', '', NULL, 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(5, 1, 'test', NULL, 20.00, '', '', NULL, 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(6, 4, 'test2', NULL, 30.00, '', '', NULL, 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(7, 1, 'sadsad', NULL, 99999999.99, '', '', 'sdafsadfasdfaf\r\nFasdfasdf\r\nfdsafasdf\r\n\r\nFasdF\r\nADS\r\nfads\r\nF\r\nF\r\naSDf\r\nSds', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(8, 1, 'sadsad', NULL, 99999999.99, '', '', 'sdafsadfasdfaf\r\nFasdfasdf\r\nfdsafasdf\r\n\r\nFasdF\r\nADS\r\nfads\r\nF\r\nF\r\naSDf\r\nSds', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(9, 2, 'rank', NULL, 1000.00, '', '', 'sadasd\r\nDasdas\r\ndsadasd\r\ndsadasd\r\ndasdasd\r\ndsadasd\r\ndasdasd\r\nsaddasd\r\nasddasd', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(10, 1, 'rank', NULL, 1000.00, '', '', 'dfsafadsfadsfadsfadsf\r\ndfsafasdfasdfasdfadsfadsfadsf\r\ndfssafasdfasdfad\r\nafsdf\r\nadsfadsF\r\nadsf\r\nADS\r\nF\r\nADSf\r\nADS', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(11, 3, 'rank', NULL, 1000.00, '', '', 'سشيسبليسبلس\r\nبيلسلسيبليبسليبسلسيبل\r\nبلييسليسبليسبليبسليسبليبسليبس\r\nليبسليبسلأ\r\nيبل\r\n][س\r\nلأيس\r\nبل\r\nيبسليبسلسيبلسيبل\r\nبيلأس\r\n][لأ\r\n\r\n[]ٍ', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(12, 2, 'rank', NULL, 10000.00, '', '', 'يشسيشسيشسيشسيشسيشس', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(13, 2, 'rank', NULL, 10000.00, '', '', 'fdsgfsdzfdgs', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(14, 1, 'hamda', NULL, 150.00, '', '', 'ranked ready bulk', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(15, 2, 'rank', NULL, 10000.00, '', '', 'addsds', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(16, 2, 'rank', NULL, 9999.00, '', '', 'dsad', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL),
(17, 2, 'kkkk', NULL, 9999999.00, '', '', 'sdadasd', 'available', '0000-00-00 00:00:00', NULL, 0.00, 0, 0.00, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `account_images`
--

CREATE TABLE `account_images` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_images`
--

INSERT INTO `account_images` (`id`, `account_id`, `image`, `created_at`) VALUES
(1, 4, 'callofduty.jpg', '2025-03-18 03:37:43'),
(2, 4, 'knife.jpg', '2025-03-18 03:37:43'),
(3, 4, 'valorant.jpg', '2025-03-18 03:37:43'),
(4, 5, 'marvelrivals.jpg', '2025-03-18 04:23:43'),
(5, 6, 'overwatch.jpg', '2025-03-18 04:24:04'),
(6, 3, 'marvelrivals.jpg', '2025-03-18 04:24:36'),
(7, 7, 'valorant.jpg', '2025-03-18 04:50:18'),
(8, 8, 'valorant.jpg', '2025-03-18 04:51:01'),
(9, 9, 'callofduty.jpg', '2025-03-18 04:52:11'),
(10, 10, 'marvelrivals.jpg', '2025-03-18 04:56:29'),
(11, 11, 'fragpunk.jpg', '2025-03-18 04:59:54'),
(12, 12, 'callofduty.jpg', '2025-03-18 06:15:43'),
(13, 13, 'callofduty.jpg', '2025-03-18 06:16:38'),
(14, 14, 'marvelrivals.jpg', '2025-03-18 06:19:00'),
(15, 15, 'callofduty.jpg', '2025-03-18 06:19:56'),
(16, 16, 'callofduty.jpg', '2025-03-18 06:21:23'),
(17, 17, 'callofduty.jpg', '2025-03-18 06:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `boosting_services`
--

CREATE TABLE `boosting_services` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_bans`
--

CREATE TABLE `chat_bans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `banned_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `chat_requests`
--

CREATE TABLE `chat_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `support_id` int(11) DEFAULT NULL,
  `status` enum('pending','claimed','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

--
-- Dumping data for table `chat_requests`
--

INSERT INTO `chat_requests` (`id`, `user_id`, `order_id`, `support_id`, `status`, `created_at`) VALUES
(1, 3, 1, NULL, 'pending', '2025-03-17 22:29:23'),
(2, 3, 1, NULL, 'pending', '2025-03-17 22:29:24'),
(3, 3, 1, NULL, 'pending', '2025-03-17 22:29:25'),
(4, 3, 1, NULL, 'pending', '2025-03-17 22:29:25'),
(5, 3, 1, NULL, 'pending', '2025-03-17 22:29:26'),
(6, 3, 1, NULL, 'pending', '2025-03-17 22:29:26'),
(7, 3, 1, NULL, 'pending', '2025-03-17 22:29:28'),
(8, 3, 1, NULL, 'pending', '2025-03-17 22:29:28'),
(9, 3, 1, NULL, 'pending', '2025-03-17 22:29:28'),
(10, 3, 1, NULL, 'pending', '2025-03-17 22:29:29'),
(11, 3, 1, NULL, 'pending', '2025-03-17 22:29:29'),
(12, 3, 1, NULL, 'pending', '2025-03-17 22:29:29'),
(13, 3, 1, NULL, 'pending', '2025-03-17 22:29:29'),
(14, 3, 1, NULL, 'pending', '2025-03-17 22:29:29'),
(15, 3, 1, NULL, 'pending', '2025-03-17 22:29:30'),
(16, 3, 1, NULL, 'pending', '2025-03-17 22:29:30'),
(17, 3, 1, NULL, 'pending', '2025-03-17 22:29:30'),
(18, 3, 1, NULL, 'pending', '2025-03-17 22:29:30'),
(19, 3, 1, NULL, 'pending', '2025-03-17 22:29:30'),
(20, 3, 1, NULL, 'pending', '2025-03-17 22:29:42'),
(21, 3, 1, NULL, 'pending', '2025-03-17 22:29:42'),
(22, 3, 1, NULL, 'pending', '2025-03-17 22:29:43'),
(23, 3, 1, NULL, 'pending', '2025-03-17 22:29:43'),
(24, 3, 1, NULL, 'pending', '2025-03-17 22:29:43'),
(25, 3, 1, NULL, 'pending', '2025-03-17 22:29:43'),
(26, 3, 1, NULL, 'pending', '2025-03-17 22:29:43'),
(27, 3, 1, NULL, 'pending', '2025-03-17 22:29:46'),
(28, 3, 1, NULL, 'pending', '2025-03-17 22:29:46'),
(29, 3, 1, NULL, 'pending', '2025-03-17 22:29:46'),
(30, 3, 1, NULL, 'pending', '2025-03-17 22:29:46'),
(31, 3, 1, NULL, 'pending', '2025-03-17 22:29:46'),
(32, 3, 1, NULL, 'pending', '2025-03-17 22:29:47'),
(33, 3, 1, NULL, 'pending', '2025-03-17 22:29:47'),
(34, 3, 1, NULL, 'pending', '2025-03-17 22:29:47'),
(35, 3, 1, NULL, 'pending', '2025-03-17 22:29:47'),
(36, 3, 1, NULL, 'pending', '2025-03-17 22:34:17'),
(37, 3, 1, NULL, 'pending', '2025-03-17 22:34:18'),
(38, 3, 1, NULL, 'pending', '2025-03-17 22:34:46'),
(39, 3, 1, NULL, 'pending', '2025-03-17 22:34:47'),
(40, 3, 1, NULL, 'pending', '2025-03-17 22:34:48'),
(41, 3, 1, NULL, 'pending', '2025-03-17 22:34:48'),
(42, 3, 1, NULL, 'pending', '2025-03-17 22:34:49'),
(43, 3, 1, NULL, 'pending', '2025-03-17 22:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','waiting') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `user_id`, `status`, `created_at`) VALUES
(5, 4, 'waiting', '2025-03-17 22:26:09'),
(6, 4, 'waiting', '2025-03-17 22:27:08'),
(7, 3, 'active', '2025-03-17 22:29:23'),
(8, 4, 'waiting', '2025-03-17 22:29:23'),
(9, 3, 'active', '2025-03-17 22:29:24'),
(10, 4, 'waiting', '2025-03-17 22:29:24'),
(11, 3, 'active', '2025-03-17 22:29:25'),
(12, 4, 'waiting', '2025-03-17 22:29:25'),
(13, 3, 'active', '2025-03-17 22:29:25'),
(14, 4, 'waiting', '2025-03-17 22:29:25'),
(15, 3, 'active', '2025-03-17 22:29:26'),
(16, 4, 'waiting', '2025-03-17 22:29:26'),
(17, 3, 'active', '2025-03-17 22:29:26'),
(18, 4, 'waiting', '2025-03-17 22:29:27'),
(19, 3, 'active', '2025-03-17 22:29:28'),
(20, 4, 'waiting', '2025-03-17 22:29:28'),
(21, 3, 'active', '2025-03-17 22:29:28'),
(22, 4, 'waiting', '2025-03-17 22:29:28'),
(23, 3, 'active', '2025-03-17 22:29:28'),
(24, 4, 'waiting', '2025-03-17 22:29:28'),
(25, 3, 'active', '2025-03-17 22:29:29'),
(26, 4, 'waiting', '2025-03-17 22:29:29'),
(27, 3, 'active', '2025-03-17 22:29:29'),
(28, 4, 'waiting', '2025-03-17 22:29:29'),
(29, 3, 'active', '2025-03-17 22:29:29'),
(30, 4, 'waiting', '2025-03-17 22:29:29'),
(31, 3, 'active', '2025-03-17 22:29:29'),
(32, 4, 'waiting', '2025-03-17 22:29:29'),
(33, 3, 'active', '2025-03-17 22:29:29'),
(34, 4, 'waiting', '2025-03-17 22:29:30'),
(35, 3, 'active', '2025-03-17 22:29:30'),
(36, 4, 'waiting', '2025-03-17 22:29:30'),
(37, 3, 'active', '2025-03-17 22:29:30'),
(38, 4, 'waiting', '2025-03-17 22:29:30'),
(39, 3, 'active', '2025-03-17 22:29:30'),
(40, 4, 'waiting', '2025-03-17 22:29:30'),
(41, 3, 'active', '2025-03-17 22:29:30'),
(42, 4, 'waiting', '2025-03-17 22:29:30'),
(43, 3, 'active', '2025-03-17 22:29:30'),
(44, 4, 'waiting', '2025-03-17 22:29:30'),
(45, 3, 'active', '2025-03-17 22:29:42'),
(46, 4, 'waiting', '2025-03-17 22:29:42'),
(47, 3, 'active', '2025-03-17 22:29:42'),
(48, 4, 'waiting', '2025-03-17 22:29:42'),
(49, 3, 'active', '2025-03-17 22:29:43'),
(50, 4, 'waiting', '2025-03-17 22:29:43'),
(51, 3, 'active', '2025-03-17 22:29:43'),
(52, 4, 'waiting', '2025-03-17 22:29:43'),
(53, 3, 'active', '2025-03-17 22:29:43'),
(54, 4, 'waiting', '2025-03-17 22:29:43'),
(55, 3, 'active', '2025-03-17 22:29:43'),
(56, 4, 'waiting', '2025-03-17 22:29:43'),
(57, 3, 'active', '2025-03-17 22:29:43'),
(58, 4, 'waiting', '2025-03-17 22:29:44'),
(59, 3, 'active', '2025-03-17 22:29:46'),
(60, 4, 'waiting', '2025-03-17 22:29:46'),
(61, 3, 'active', '2025-03-17 22:29:46'),
(62, 4, 'waiting', '2025-03-17 22:29:46'),
(63, 3, 'active', '2025-03-17 22:29:46'),
(64, 4, 'waiting', '2025-03-17 22:29:46'),
(65, 3, 'active', '2025-03-17 22:29:46'),
(66, 4, 'waiting', '2025-03-17 22:29:46'),
(67, 3, 'active', '2025-03-17 22:29:46'),
(68, 4, 'waiting', '2025-03-17 22:29:47'),
(69, 3, 'active', '2025-03-17 22:29:47'),
(70, 4, 'waiting', '2025-03-17 22:29:47'),
(71, 3, 'active', '2025-03-17 22:29:47'),
(72, 4, 'waiting', '2025-03-17 22:29:47'),
(73, 3, 'active', '2025-03-17 22:29:47'),
(74, 4, 'waiting', '2025-03-17 22:29:47'),
(75, 3, 'active', '2025-03-17 22:29:47'),
(76, 4, 'waiting', '2025-03-17 22:29:47'),
(77, 3, 'active', '2025-03-17 22:34:17'),
(78, 4, 'waiting', '2025-03-17 22:34:17'),
(79, 3, 'active', '2025-03-17 22:34:18'),
(80, 4, 'waiting', '2025-03-17 22:34:18'),
(81, 3, 'active', '2025-03-17 22:34:46'),
(82, 4, 'waiting', '2025-03-17 22:34:46'),
(83, 3, 'active', '2025-03-17 22:34:47'),
(84, 4, 'waiting', '2025-03-17 22:34:47'),
(85, 3, 'active', '2025-03-17 22:34:48'),
(86, 4, 'waiting', '2025-03-17 22:34:48'),
(87, 3, 'active', '2025-03-17 22:34:48'),
(88, 4, 'waiting', '2025-03-17 22:34:49'),
(89, 3, 'active', '2025-03-17 22:34:49'),
(90, 4, 'waiting', '2025-03-17 22:34:49'),
(91, 3, 'active', '2025-03-17 22:34:49'),
(92, 4, 'waiting', '2025-03-17 22:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `account_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `name`, `slug`, `description`, `image`, `featured`, `created_at`, `updated_at`, `account_count`) VALUES
(1, 'Marvel Rivals', 'marvel-rivals', 'Marvel Rivals is a team-based hero shooter featuring iconic Marvel characters.', 'marvelrivals.jpg', 1, '2025-03-17 00:55:25', NULL, 0),
(2, 'Call of Duty', 'call-of-duty', 'Call of Duty is a first-person shooter game series published by Activision.', 'callofduty.jpg', 1, '2025-03-17 00:55:25', NULL, 0),
(3, 'Fragpunk', 'fragpunk', 'Fragpunk is a futuristic first-person shooter with cyberpunk elements.', 'fragpunk.jpg', 1, '2025-03-17 00:55:25', NULL, 0),
(4, 'Overwatch', 'overwatch', 'Overwatch is a team-based multiplayer first-person shooter developed by Blizzard Entertainment.', 'overwatch.jpg', 1, '2025-03-17 00:55:25', NULL, 0),
(5, 'Valorant', 'valorant', '', 'valorant.jpg', 1, '0000-00-00 00:00:00', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `boosting_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `account_id`, `boosting_id`, `price`, `payment_method`, `transaction_id`, `status`, `created_at`, `updated_at`, `total_amount`) VALUES
(6, 5, 2, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 100.00),
(7, 5, 2, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 100.00),
(8, 5, 4, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 600.00),
(9, 5, 4, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 600.00),
(10, 5, 4, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 600.00),
(11, 5, 4, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 600.00),
(12, 5, 4, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 600.00),
(13, 5, 11, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 1000.00),
(14, 5, 10, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 1000.00),
(15, 5, 10, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 1000.00),
(16, 5, 10, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 1000.00),
(17, 5, 9, NULL, 0.00, NULL, NULL, 'pending', '0000-00-00 00:00:00', NULL, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'KernelStore', '2025-03-17 00:55:25'),
(2, 'site_description', 'Premium Gaming Accounts Marketplace', '2025-03-17 00:55:25'),
(3, 'contact_email', 'support@kernelstore.com', '2025-03-17 00:55:25'),
(4, 'currency', 'USD', '2025-03-17 00:55:25'),
(5, 'maintenance_mode', '0', '2025-03-17 00:55:25');

-- --------------------------------------------------------

--
-- Table structure for table `support_ratings`
--

CREATE TABLE `support_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `support_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

--
-- Dumping data for table `support_ratings`
--

INSERT INTO `support_ratings` (`id`, `user_id`, `support_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 1, 4, '???', '2025-03-17 21:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','banned') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `is_admin`, `balance`, `created_at`, `last_login`, `status`) VALUES
(2, 'attayeb', 'rattayeb@gmail.com', '$2y$10$pxWOZaj3WMy5IqUgvAbdiO/Wajgr9aNMeNybdns8E1FnUPcMIw9ia', NULL, NULL, 0, 0.00, '0000-00-00 00:00:00', NULL, 'active'),
(4, 'Rdad', 'rattayeb0@gmail.com', '$2y$10$/2mPqPA8Do3w45QQKx.90ugcdalhBeTdONNQZ5b77uWxgXpCFt5Ma', NULL, NULL, 0, 0.00, '0000-00-00 00:00:00', NULL, 'active'),
(5, 'admin', 'admin@kernelstore.com', '$2y$10$9n4gpZye/wjgQzbCJwH26.47lebQipqylOq1UZG83xD/EAscPFwrW', NULL, NULL, 1, 0.00, '2025-03-17 12:04:48', NULL, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `account_images`
--
ALTER TABLE `account_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `boosting_services`
--
ALTER TABLE `boosting_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `chat_bans`
--
ALTER TABLE `chat_bans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `banned_by` (`banned_by`);

--
-- Indexes for table `chat_requests`
--
ALTER TABLE `chat_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `support_id` (`support_id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `boosting_id` (`boosting_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `support_ratings`
--
ALTER TABLE `support_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `support_id` (`support_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `account_images`
--
ALTER TABLE `account_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `boosting_services`
--
ALTER TABLE `boosting_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_bans`
--
ALTER TABLE `chat_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chat_requests`
--
ALTER TABLE `chat_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

--
-- Constraints for table `account_images`
--
ALTER TABLE `account_images`
  ADD CONSTRAINT `account_images_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `boosting_services`
--
ALTER TABLE `boosting_services`
  ADD CONSTRAINT `boosting_services_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`boosting_id`) REFERENCES `boosting_services` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`),
  ADD CONSTRAINT `ticket_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
