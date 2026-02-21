-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2026 at 11:13 AM
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
-- Database: `call_reporting`
--

-- --------------------------------------------------------

--
-- Table structure for table `integration_settings`
--

CREATE TABLE `integration_settings` (
  `id` int(11) NOT NULL,
  `last_fetch_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `integration_settings`
--

INSERT INTO `integration_settings` (`id`, `last_fetch_time`) VALUES
(1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ringba_calls`
--

CREATE TABLE `ringba_calls` (
  `id` int(11) NOT NULL,
  `call_id` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `campaign_id` varchar(50) DEFAULT NULL,
  `campaign_name` varchar(100) DEFAULT NULL,
  `payout` decimal(10,2) DEFAULT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `has_recording` tinyint(1) DEFAULT NULL,
  `sentiment` varchar(20) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `transcript_url` text DEFAULT NULL,
  `raw_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `integration_settings`
--
ALTER TABLE `integration_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ringba_calls`
--
ALTER TABLE `ringba_calls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `call_id` (`call_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `integration_settings`
--
ALTER TABLE `integration_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ringba_calls`
--
ALTER TABLE `ringba_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
