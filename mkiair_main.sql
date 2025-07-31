-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 31, 2025 at 01:48 PM
-- Server version: 8.0.42-cll-lve
-- PHP Version: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mkiair_main`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'سیاسی', 'politics', 'اخبار سیاسی کشور و جهان', '2025-07-31 06:32:46', '2025-07-31 06:32:46'),
(2, 'ورزشی', 'sports', 'اخبار ورزشی', '2025-07-31 06:32:46', '2025-07-31 06:32:46'),
(3, 'اقتصادی', 'economy', 'اخبار اقتصادی', '2025-07-31 06:32:46', '2025-07-31 06:32:46'),
(4, 'فناوری', 'technology', 'اخبار فناوری و تکنولوژی', '2025-07-31 06:32:46', '2025-07-31 06:32:46'),
(5, 'فرهنگی', 'culture', 'اخبار فرهنگی و هنری', '2025-07-31 06:32:46', '2025-07-31 06:32:46');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `news_id`, `user_id`, `content`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'slams', 'approved', '2025-07-31 07:06:59', '2025-07-31 07:13:32'),
(2, 1, 5, 'testi', 'approved', '2025-07-31 12:46:58', '2025-07-31 12:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` text,
  `content` longtext NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `author_id` int NOT NULL,
  `category_id` int NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `slug`, `summary`, `content`, `image_url`, `author_id`, `category_id`, `status`, `views`, `created_at`, `updated_at`) VALUES
(1, 'عنوان خبر سیاسی اول', 'political-news-1', 'خلاصه خبر سیاسی', 'متن کامل خبر سیاسی...', 'https://placehold.co/400x250', 2, 1, 'approved', 5, '2025-07-31 06:32:46', '2025-07-31 13:34:48'),
(2, 'عنوان خبر ورزشی اول', 'sports-news-1', 'خلاصه خبر ورزشی', 'متن کامل خبر ورزشی...', 'https://placehold.co/400x250', 2, 2, 'approved', 1, '2025-07-31 06:32:46', '2025-07-31 07:26:09'),
(3, 'عنوان خبر اقتصادی', 'anvan-khbr-aghtsady', 'خلاصه خبر اقتصادی', 'متن کامل خبر اقتصادی...\r\n\r\nمثلا تغییر', 'https://placehold.co/400x250', 2, 3, 'approved', 17, '2025-07-31 06:32:46', '2025-07-31 13:46:46'),
(4, 'خبر سیاسی جدید', 'khbr-syasy-jdyd', 'خلاصه خبر تستی', 'اینجا باید متن کامل خبر باشه که نیست، این صرفا یه تست از نویسنده ای جز ادمین اصلی هست', 'https://placehold.co/400x250', 5, 1, 'approved', 1, '2025-07-31 13:32:33', '2025-07-31 13:35:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','writer','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@news.com', '$2y$10$TWg7dN4zYAWMU7JYCOVhQOjf5exlB1nWX3V00TBwbeZFKvoU1ZTCS', 'مدیر سیستم', 'admin', 'active', '2025-07-31 06:32:46', '2025-07-31 13:34:16'),
(2, 'writer1', 'writer1@news.com', '$2y$10$BYHS8SwCKJqzQpNN.kSPaeywdbzotiLJrAh62BlA.4eSXAQt0a3o6', 'نویسنده اول', 'writer', 'active', '2025-07-31 06:32:46', '2025-07-31 13:34:27'),
(3, 'test1', 'testi@test.ir', '$2y$10$lLO4FmdZsuTwuQYbXa2P9eiDKqN6AdvXYpLKAWNlbGV/k7cJO2acu', 'testi', 'user', 'active', '2025-07-31 06:48:33', '2025-07-31 13:37:04'),
(4, 'awe', 'qwe@QWE.qwe', '$2y$10$9iAuplB.L//NCBhTZA2JGu/hVbR/NpTt4j5qQ2QReXCXksTD8Njza', 'qwe', 'writer', 'active', '2025-07-31 12:45:35', '2025-07-31 12:45:35'),
(5, 'qweqwe', 'qwe@qweqwe.qwe', '$2y$10$PQwurYp6gA3xhKdw7qRJUeORnZnnDf8Wwd.WT1bwIK81Y8MvjAD8W', 'qwe', 'writer', 'active', '2025-07-31 12:46:11', '2025-07-31 12:46:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_id` (`news_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category_id` (`category_id`);

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
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `news_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
