-- phpMyAdmin SQL Dump
-- version 4.3.11
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2016 at 11:10 AM
-- Server version: 5.6.24
-- PHP Version: 5.5.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `schooltv_content`
--

-- --------------------------------------------------------

--
-- Table structure for table `deck`
--

CREATE TABLE IF NOT EXISTS `deck` (
  `id` bigint(19) unsigned NOT NULL,
  `name` tinytext NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL,
  `created_by` varchar(45) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(45) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(45) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `deck`
--

INSERT INTO `deck` (`id`, `name`, `description`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES
(1, 'Deck 1', NULL, '0000-00-00 00:00:00', '', '2015-12-28 03:45:49', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deck_video_inuse`
--

CREATE TABLE IF NOT EXISTS `deck_video_inuse` (
  `deck_id` bigint(19) unsigned NOT NULL,
  `video_id` bigint(19) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE IF NOT EXISTS `question` (
  `id` bigint(19) unsigned NOT NULL,
  `type` varchar(16) NOT NULL COMMENT 'Question type',
  `deck_id` bigint(19) unsigned NOT NULL,
  `data` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(45) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(45) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(45) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`id`, `type`, `deck_id`, `data`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES
(1, 'single', 1, '{"correct_return_second":12,"wrong_return_second":1,"time_limit":30,"commentary":"","question":"\\u3053\\u306e\\u8c46\\u8150\\u306e\\u3088\\u3046\\u306b\\u9577\\u65b9\\u5f62\\u3068\\u6b63\\u65b9\\u5f62\\u3067\\u56f2\\u307e\\u308c\\u305f\\u7acb\\u4f53\\u306f\\u4f55\\u3068\\u3044\\u3046?","question_image":[],"question_images":[{"url":"sample_tofu.jpg","caption":"","width":"","height":"","order":1}],"answers":[{"text":"\\u76f4\\u65b9\\u4f53","image_url":null,"correct":false,"order":null},{"text":"\\u7acb\\u65b9\\u4f53","image_url":null,"correct":true,"order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(2, 'single', 1, '{"correct_return_second":16,"wrong_return_second":12,"time_limit":30,"commentary":"","question":"\\u306a\\u305c\\u30b9\\u30a4\\u30df\\u30fc\\u3068\\u3044\\u3046\\u30cb\\u30c3\\u30af\\u30cd\\u30fc\\u30e0\\u304c\\u3064\\u3044\\u305f\\u306e\\u304b","answers":[{"text":"\\u3060\\u308c\\u3088\\u308a\\u3082\\u901f\\u304f\\u6cf3\\u3052\\u305f\\u304b\\u3089","image_url":null,"correct":true,"order":null},{"text":"\\u30b9\\u30a4\\u30e0\\u3068\\u3044\\u3046\\u306e\\u304c\\u3001\\u82f1\\u8a9e\\u3067\\u6cf3\\u3050\\u3060\\u304b\\u3089","image_url":null,"correct":false,"order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(3, 'multiple', 1, '{"correct_return_second":16.111,"wrong_return_second":12.121,"time_limit":30,"commentary":"","question":"\\u3069\\u3093\\u306a\\u3069\\u3046\\u3076\\u3064\\u304c\\u3044\\u307e\\u3059\\u304b\\uff1f\\u3059\\u3079\\u3066\\u3048\\u3089\\u3073\\u307e\\u3057\\u3087\\u3046","answers":[{"text":"\\u305e\\u3046","image_url":null,"correct":true,"order":null},{"text":"\\u306d\\u3053","image_url":null,"correct":true,"order":null},{"text":"\\u3044\\u306c","image_url":null,"correct":true,"order":null},{"text":"\\u3055\\u308b","image_url":null,"correct":false,"order":null},{"text":"\\u3046\\u3055\\u304e","image_url":null,"correct":false,"order":null},{"text":"\\u3068\\u308a","image_url":null,"correct":false,"order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(4, 'multiple', 1, '{"correct_return_second":16.111,"wrong_return_second":12.121,"time_limit":30,"commentary":"","question":"\\u3069\\u3093\\u306a\\u3069\\u3046\\u3076\\u3064\\u304c\\u3044\\u307e\\u3059\\u304b\\uff1f\\u3059\\u3079\\u3066\\u3048\\u3089\\u3073\\u307e\\u3057\\u3087\\u3046","answers":[{"text":"\\u305e\\u3046","image_url":"sample_elephant.jpg","correct":true,"order":null},{"text":"\\u306d\\u3053","image_url":"sample_neko.jpg","correct":true,"order":null},{"text":"\\u3044\\u306c","image_url":"sample_dog.jpg","correct":true,"order":null},{"text":"\\u3055\\u308b","image_url":"sample_saru.jpg","correct":false,"order":null},{"text":"\\u3046\\u3055\\u304e","image_url":"sample_usagi.jpg","correct":false,"order":null},{"text":"\\u3068\\u308a","image_url":"sample_tori.jpg","correct":false,"order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(5, 'multi_field', 1, '{"correct_return_second":16.111,"wrong_return_second":12.121,"time_limit":30,"commentary":"","question":"\\u65b9\\u4f4d\\u306e\\u554f\\u984c\\u3060\\u3088\\n\\uff11\\uff0c\\uff12\\uff0c\\uff13\\uff0c\\uff14\\u306b\\u306f\\u4f55\\u3068\\u3044\\u3046\\u5b57\\u304c\\u306f\\u3044\\u308b\\u304b\\u306a?","question_image":[],"question_images":[{"url":"sample_hogaku.jpg","caption":"","width":"","height":"","order":1}],"answers":[{"text":"\\u5317\\u897f","image_url":null,"correct":true,"order":null},{"text":"\\u5317\\u6771","image_url":null,"correct":true,"order":null},{"text":"\\u5357\\u6771","image_url":null,"correct":true,"order":null},{"text":"\\u6771\\u5357","image_url":null,"correct":true,"order":null},{"text":"\\u9593\\u9055\\u3063\\u305f\\u7b54\\u3048","image_url":null,"correct":false,"order":null},{"text":"\\u4ed6\\u306b","image_url":null,"correct":false,"order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(6, 'text', 1, '{"correct_return_second":17.022,"wrong_return_second":10.053,"time_limit":30,"commentary":"","question":"7x8\\u306f\\u3044\\u304f\\u3064\\u3067\\u3057\\u3087\\u3046\\u304b\\uff1f","answers":[{"text":"7x8={input}","image_url":null,"correct":"56","order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(7, 'text', 1, '{"correct_return_second":17.022,"wrong_return_second":10.053,"time_limit":30,"commentary":"","question":"\\u6b21\\u306e\\u7acb\\u65b9\\u4f53\\u306e\\u4f53\\u7a4d\\u3092\\u3001\\u516c\\u5f0f\\u3092\\u4f7f\\u3063\\u3066\\u8a08\\u7b97\\u3057\\u3066\\u307f\\u3088\\u3046\\u3002","question_image":[],"question_images":[{"url":"sample_rettai.jpg","caption":"","width":"","height":"","order":1}],"answers":[{"text":"{input}cm3","image_url":null,"correct":"48","order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(8, 'text', 1, '{"correct_return_second":17.022,"wrong_return_second":10.053,"time_limit":30,"commentary":"","question":"\\u30af\\u30ed\\u306f\\u3001\\u53f3\\u304b\\u3089\\u4f55\\u756a\\u76ee\\u306b\\u3044\\u308b\\u304b\\u306a\\uff1f","answers":[{"text":"{input}\\u756a\\u76ee","image_url":null,"correct":"4","order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(9, 'sort', 1, '{"correct_return_second":0,"wrong_return_second":10.053,"time_limit":30,"commentary":"","question":"Sign language is Used in the world.\\u3092\\u7591\\u554f\\u6587\\u306b\\u3059\\u308b\\u3068\\u3001\\u3069\\u3046\\u306a\\u308a\\u307e\\u3059\\u304b?","answers":[{"text":"is","image_url":null,"correct":1,"order":null},{"text":"sign","image_url":null,"correct":2,"order":null},{"text":"language","image_url":null,"correct":3,"order":null},{"text":"used","image_url":null,"correct":4,"order":null},{"text":"in","image_url":null,"correct":5,"order":null},{"text":"the","image_url":null,"correct":6,"order":null},{"text":"world","image_url":null,"correct":7,"order":null},{"text":"?","image_url":null,"correct":8,"order":null},{"text":"something","image_url":null,"correct":9,"order":null},{"text":"waste","image_url":null,"correct":10,"order":null},{"text":"question","image_url":null,"correct":11,"order":null}]}', '2015-12-29 04:28:39', 'system', '2016-01-08 09:08:35', 'system', NULL, NULL),
(10, 'group', 1, '{"correct_return_second":0,"wrong_return_second":10.053,"time_limit":30,"commentary":"","question":"\\u3075\\u305f\\u3064\\u306e\\u4ef2\\u9593\\u306b\\u5206\\u3051\\u307e\\u3057\\u3087\\u3046","question_groups":["\\u3044\\u306c","\\u306d\\u3053"],"answers":[{"text":"\\u305e\\u3046","image_url":"sample_elephant.jpg","correct":"\\u3044\\u306c","order":null},{"text":"\\u306d\\u3053","image_url":"sample_neko.jpg","correct":"\\u3044\\u306c","order":null},{"text":"\\u3044\\u306c","image_url":"sample_dog.jpg","correct":"\\u3044\\u306c","order":null},{"text":"\\u3055\\u308b","image_url":"sample_saru.jpg","correct":"\\u306d\\u3053","order":null},{"text":"\\u3046\\u3055\\u304e","image_url":"sample_usagi.jpg","correct":"\\u306d\\u3053","order":null},{"text":"\\u3068\\u308a","image_url":"sample_tori.jpg","correct":"\\u306d\\u3053","order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(11, 'group', 1, '{"correct_return_second":0,"wrong_return_second":10.053,"time_limit":30,"commentary":"","question":"\\u3075\\u305f\\u3064\\u306e\\u4ef2\\u9593\\u306b\\u5206\\u3051\\u307e\\u3057\\u3087\\u3046","question_groups":["\\u3044\\u306c","\\u306d\\u3053"],"answers":[{"text":"\\u30cf\\u30b9\\u30ad\\u30fc","image_url":null,"correct":"\\u3044\\u306c","order":null},{"text":"\\u30c0\\u30c3\\u30af\\u30b9\\u30d5\\u30f3\\u30c9","image_url":null,"correct":"\\u3044\\u306c","order":null},{"text":"\\u30e8\\u30fc\\u30af\\u30b7\\u30e3\\u30f3\\u30c7\\u30ea\\u30a2","image_url":null,"correct":"\\u306d\\u3053","order":null},{"text":"\\u305f\\u307e","image_url":null,"correct":"\\u306d\\u3053","order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL),
(12, 'multi_text', 1, '{"correct_return_second":16.111,"wrong_return_second":12.121,"time_limit":30,"commentary":"","question":"\\u4e5d\\u4e5d\\u306e\\u8868\\u306e\\u4e00\\u90e8\\u306a\\u3093\\u3060\\u3051\\u3069\\u3001\\u30a2\\u3001\\u30a4\\u3001\\u30a6\\u306e\\u6570\\u304c\\u4f55\\u304b\\u308f\\u304b\\u308b\\u304b\\u306a\\uff1f\\u3068\\u304d\\u65b9\\u3082\\u3044\\u3048\\u308b\\u304b\\u306a\\uff1f","question_image":[],"question_images":[{"url":"sample_hogaku.jpg","caption":"","width":"","height":"","order":1}],"answers":[{"text":"","image_url":null,"correct":"42","order":null},{"text":"","image_url":null,"correct":"34","order":null},{"text":"","image_url":null,"correct":"53","order":null},{"text":"","image_url":null,"correct":"22","order":null}]}', '2015-12-29 04:28:39', 'system', '2015-12-28 14:28:39', 'system', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `video`
--

CREATE TABLE IF NOT EXISTS `video` (
  `id` bigint(19) unsigned NOT NULL,
  `type` enum('textbook','active_learning') NOT NULL DEFAULT 'textbook',
  `name` tinytext,
  `description` text,
  `brightcove_id` varchar(192) NOT NULL,
  `brightcove_thumbnail_url` varchar(192) DEFAULT NULL COMMENT 'Thumbnail url of Brightcove',
  `image_key` varchar(192) DEFAULT NULL COMMENT 'Thumbnail key',
  `created_at` datetime NOT NULL,
  `created_by` varchar(45) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(45) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(45) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `video`
--

INSERT INTO `video` (`id`, `type`, `name`, `description`, `brightcove_id`, `brightcove_thumbnail_url`, `image_key`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES
(1, 'textbook', 'xxx', 'Description Demo', 'slh58j4F3O1HrLdYeuUokKQMPEwYc2bIgBJWnWttVXa7cGQX23yenKZaNwk1AgNT', NULL, NULL, '2015-12-29 04:28:39', 'system', '2015-12-28 21:28:39', 'system', NULL, NULL),
(2, 'textbook', 'xxxb', 'Description Demo', 'O8mH8FIl7otpj1vxdhceFINMbyL71a4haUEBRnndYSuVqDecKQ9fEOqrsfS93W2G', NULL, NULL, '2015-12-29 04:28:39', 'system', '2015-12-28 21:28:39', 'system', NULL, NULL),
(3, 'textbook', 'xxxc', 'Description Demo', 'TYBUKASWjsLmieV1vO9cVFjka45t86RrnQhovcU2FNyzdTfPEbMCwu0XJBlgtHm9', NULL, NULL, '2015-12-29 04:28:39', 'system', '2015-12-28 21:28:39', 'system', NULL, NULL),
(4, 'textbook', 'xxxd', 'Description Demo', 'C2gPLEfbUrFtmdzS6SJDnejIROMhoo7BZzY8Idn74Fp16lRt13aKiNJfVvePB5Tp', NULL, NULL, '2015-12-29 04:28:39', 'system', '2015-12-28 21:28:39', 'system', NULL, NULL),
(5, 'textbook', 'xxxe', 'Description Demo', 'BE7CnfF7SAfUhtyuxugIpkerRis9MXU5y3m4TSBONGZ0oQEPYs9a2HKTHL4eM0qF', NULL, NULL, '2015-12-29 04:28:39', 'system', '2015-12-28 21:28:39', 'system', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `video_progress`
--

CREATE TABLE IF NOT EXISTS `video_progress` (
  `video_id` bigint(19) unsigned NOT NULL,
  `user_id` bigint(19) unsigned DEFAULT NULL,
  `cookie_id` varchar(128) DEFAULT NULL COMMENT 'First party cookie to store',
  `session_id` varchar(16) DEFAULT NULL,
  `second` float NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `created_by` varchar(45) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `video_question_timeline`
--

CREATE TABLE IF NOT EXISTS `video_question_timeline` (
  `video_id` bigint(19) unsigned NOT NULL,
  `question_id` bigint(19) unsigned NOT NULL,
  `second` float unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `video_question_timeline`
--

INSERT INTO `video_question_timeline` (`video_id`, `question_id`, `second`) VALUES
(1, 1, 12),
(1, 2, 16),
(2, 3, 12),
(2, 4, 16),
(2, 5, 18),
(3, 6, 12),
(3, 7, 16),
(3, 8, 18),
(4, 9, 12),
(5, 10, 12),
(5, 11, 16),
(2, 12, 12);

-- --------------------------------------------------------

--
-- Table structure for table `video_view_count`
--

CREATE TABLE IF NOT EXISTS `video_view_count` (
  `video_id` bigint(19) unsigned NOT NULL,
  `count` int(11) NOT NULL COMMENT 'Use count in master_textbook_inuse'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deck`
--
ALTER TABLE `deck`
  ADD PRIMARY KEY (`id`), ADD FULLTEXT KEY `TEXTINDEX` (`name`,`description`);

--
-- Indexes for table `deck_video_inuse`
--
ALTER TABLE `deck_video_inuse`
  ADD KEY `fk_deck_video_deck_idx` (`deck_id`), ADD KEY `fk_deck_video_video1_idx` (`video_id`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`), ADD KEY `fk_question_deck1_idx` (`deck_id`);

--
-- Indexes for table `video`
--
ALTER TABLE `video`
  ADD PRIMARY KEY (`id`), ADD FULLTEXT KEY `TEXTINDEX` (`name`,`description`);

--
-- Indexes for table `video_progress`
--
ALTER TABLE `video_progress`
  ADD PRIMARY KEY (`video_id`), ADD UNIQUE KEY `UNIQUE` (`video_id`,`session_id`), ADD KEY `USER_UPDATE` (`video_id`,`user_id`,`updated_at`);

--
-- Indexes for table `video_question_timeline`
--
ALTER TABLE `video_question_timeline`
  ADD PRIMARY KEY (`video_id`,`question_id`,`second`), ADD KEY `fk_video_question_timeline_question1_idx` (`question_id`);

--
-- Indexes for table `video_view_count`
--
ALTER TABLE `video_view_count`
  ADD PRIMARY KEY (`video_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deck`
--
ALTER TABLE `deck`
  MODIFY `id` bigint(19) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `id` bigint(19) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `video`
--
ALTER TABLE `video`
  MODIFY `id` bigint(19) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `deck_video_inuse`
--
ALTER TABLE `deck_video_inuse`
ADD CONSTRAINT `fk_deck_video_deck` FOREIGN KEY (`deck_id`) REFERENCES `deck` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_deck_video_video1` FOREIGN KEY (`video_id`) REFERENCES `video` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
ADD CONSTRAINT `fk_question_deck1` FOREIGN KEY (`deck_id`) REFERENCES `deck` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `video_progress`
--
ALTER TABLE `video_progress`
ADD CONSTRAINT `fk_video_progress_video1` FOREIGN KEY (`video_id`) REFERENCES `video` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `video_question_timeline`
--
ALTER TABLE `video_question_timeline`
ADD CONSTRAINT `fk_video_question_timeline_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_video_question_timeline_video1` FOREIGN KEY (`video_id`) REFERENCES `video` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `video_view_count`
--
ALTER TABLE `video_view_count`
ADD CONSTRAINT `fk_video _id` FOREIGN KEY (`video_id`) REFERENCES `video` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
