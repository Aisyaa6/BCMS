-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 05:14 PM
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
-- Database: `coursework_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `CID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `TID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`CID`, `name`, `TID`) VALUES
(1, 'Mawar', 2),
(2, 'Melati', 2),
(3, 'Melur', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `SID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `CID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`SID`, `name`, `email`, `password`, `CID`) VALUES
(1, 'Saem', 'saem@gg.com', '32250170a0dca92d53ec9624f336ca24', 1),
(2, 'rin', 'rinrin@gg.com', 'da6523a488577c99148969e5841908ae', 1),
(10, 'ahmed', 'aamd@gg.com', '803462bd4e7efba58f3674091699720f', 3),
(17, 'caelus', 'tb@gg.com', '72b8c1ecdb0c1aa5a6e7cd8770cbeffa', 2),
(18, 'mani', 'mani@gg.com', '6086113edb6ef1feae03a5662dccd6fc', 3);

-- --------------------------------------------------------

--
-- Table structure for table `student_tk`
--

CREATE TABLE `student_tk` (
  `TKID` int(11) NOT NULL,
  `SID` int(11) NOT NULL,
  `is_checked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_tk`
--

INSERT INTO `student_tk` (`TKID`, `SID`, `is_checked`) VALUES
(1, 10, 1),
(2, 1, 1),
(2, 2, 1),
(2, 17, 1),
(3, 1, 0),
(3, 2, 1),
(3, 17, 0);

-- --------------------------------------------------------

--
-- Table structure for table `submit`
--

CREATE TABLE `submit` (
  `SUBID` int(11) NOT NULL,
  `draft_file` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `SID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submit`
--

INSERT INTO `submit` (`SUBID`, `draft_file`, `comment`, `status`, `SID`) VALUES
(1, 'assignment1.docx', 'Good', 'Viewed', 1),
(2, 'PNG_print_1.docx', NULL, 'Pending', 10),
(3, 'SCHEME OF WORK (20252csc264).docx', 'Good', 'Viewed', 10),
(4, 'Proposal Template.docx', NULL, 'Pending', 17),
(5, 'CSC186-ProjectExample.docx', NULL, 'Pending', 2);

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `TKID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `TK_desc` text DEFAULT NULL,
  `TID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`TKID`, `name`, `TK_desc`, `TID`) VALUES
(1, 'Assignment 1', 'Research and write a report on marketing strategies.', 1),
(2, 'Business informations', 'Come up with business name and logo and put them in docx along with your student informations.', 2),
(3, 'Business background', 'Fill the table in the given docx template', 2);

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `TID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`TID`, `name`, `email`, `password`) VALUES
(1, 'Ali', 'ali@gg.com', '984d8144fa08bfc637d2825463e184fa'),
(2, 'Afzan', 'afz@gg.com', 'ef004bde5174ffe0a1c186c764c8e5a3');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`CID`),
  ADD KEY `TID` (`TID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`SID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `CID` (`CID`);

--
-- Indexes for table `student_tk`
--
ALTER TABLE `student_tk`
  ADD PRIMARY KEY (`TKID`,`SID`),
  ADD KEY `SID` (`SID`);

--
-- Indexes for table `submit`
--
ALTER TABLE `submit`
  ADD PRIMARY KEY (`SUBID`),
  ADD KEY `SID` (`SID`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`TKID`),
  ADD KEY `TID` (`TID`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`TID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `CID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `SID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `submit`
--
ALTER TABLE `submit`
  MODIFY `SUBID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task`
--
ALTER TABLE `task`
  MODIFY `TKID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `TID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`TID`) REFERENCES `teacher` (`TID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`CID`) REFERENCES `class` (`CID`);

--
-- Constraints for table `student_tk`
--
ALTER TABLE `student_tk`
  ADD CONSTRAINT `student_tk_ibfk_1` FOREIGN KEY (`TKID`) REFERENCES `task` (`TKID`),
  ADD CONSTRAINT `student_tk_ibfk_2` FOREIGN KEY (`SID`) REFERENCES `student` (`SID`);

--
-- Constraints for table `submit`
--
ALTER TABLE `submit`
  ADD CONSTRAINT `submit_ibfk_1` FOREIGN KEY (`SID`) REFERENCES `student` (`SID`);

--
-- Constraints for table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`TID`) REFERENCES `teacher` (`TID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
