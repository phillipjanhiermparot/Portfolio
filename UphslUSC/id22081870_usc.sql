-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 12, 2024 at 06:12 PM
-- Server version: 10.5.20-MariaDB
-- PHP Version: 7.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `id22081870_usc`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `ID` int(11) NOT NULL,
  `DateTime` datetime(6) DEFAULT current_timestamp(6),
  `department` varchar(255) NOT NULL,
  `product` varchar(255) NOT NULL,
  `size` varchar(255) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `subtotal` int(255) NOT NULL,
  `orderstatus` varchar(255) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`ID`, `DateTime`, `department`, `product`, `size`, `quantity`, `price`, `subtotal`, `orderstatus`) VALUES
(119, '2024-05-08 17:10:09.617820', 'CAS050', 'C_Blouse', 'M', '1', '500', 500, 'Paid'),
(120, '2024-05-08 17:18:09.015068', 'CAS329', 'C_Polo Barong', 'M', '5', '625', 3125, 'Paid'),
(121, '2024-05-08 17:34:49.753441', 'CAS370', 'C_Blouse', 'L', '1', '500', 500, 'Paid'),
(122, '2024-05-08 18:25:17.650022', 'SHS897', 'SHS_Blazer', 'M', '1', '1600', 1600, 'Cancelled'),
(123, '2024-05-09 08:17:21.070722', 'CCS565', 'NKP_Jumper', 'C-SW31', '2', '375', 750, 'Paid'),
(124, '2024-05-09 08:17:34.740047', 'CCS565', 'CIHM_T-Uniform Male', 'E-SHW12', '4', '1500', 6000, 'Paid'),
(125, '2024-05-09 08:17:38.534815', 'CCS565', 'C_PE Tshirt', 'E-SHW12', '4', '480', 1920, 'Paid'),
(126, '2024-05-09 08:23:02.304579', 'CCS143', 'C_PE Tshirt', '----', '1', '480', 480, 'Cancelled'),
(128, '2024-05-09 10:57:42.852091', 'CAS734', 'G1-3_Blouse', '3XL', '1', '325', 325, 'Cancelled'),
(129, '2024-05-09 15:39:46.939829', 'CAS970', 'C_Polo Barong', 'C-SW31', '1', '625', 625, 'Cancelled'),
(130, '2024-05-09 15:40:00.411896', 'CBA935', 'C_Blouse', 'L', '1', '500', 500, 'Cancelled'),
(131, '2024-05-09 22:39:28.933991', 'CAS924', 'C_PE Pants', 'M', '1', '570', 570, 'Cancelled'),
(132, '2024-05-11 06:54:47.295912', 'CAS722', 'C_Blouse', 'E-PJ16', '1', '500', 500, 'Cancelled'),
(133, '2024-05-11 06:54:51.969808', 'CAS722', 'C_Blouse', 'E-PJ16', '1', '500', 500, 'Cancelled'),
(134, '2024-05-11 06:55:11.575527', 'CCS154222', 'SHS_Blazer', '3XL', '1', '1800', 1800, 'Cancelled'),
(135, '2024-05-11 06:55:11.778710', 'CCS154222', 'SHS_Blazer', '3XL', '1', '1800', 1800, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `signup`
--

CREATE TABLE `signup` (
  `username` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `signup`
--

INSERT INTO `signup` (`username`, `firstname`, `lastname`, `email`, `password`) VALUES
('admin', 'admin', 'admin', 'admin@gmail.com', 'admin1');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `signup`
--
ALTER TABLE `signup`
  ADD PRIMARY KEY (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
