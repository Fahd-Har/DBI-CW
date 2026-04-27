-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Apr 26, 2026 at 02:08 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `COMP1044_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE `assessment` (
  `AssessmentID` int NOT NULL,
  `InternshipID` int NOT NULL,
  `LecturerID` int DEFAULT NULL,
  `SupervisorID` int DEFAULT NULL,
  `UndertakingTaskOrProject` int DEFAULT NULL,
  `HealthSafetyWorkplace` int DEFAULT NULL,
  `ConnectivityTheoreticalKnowledge` int DEFAULT NULL,
  `PresentationWrittenDocument` int DEFAULT NULL,
  `ClarityLanguageIllustration` int DEFAULT NULL,
  `LifelongLearningActivities` int DEFAULT NULL,
  `ProjectManagement` int DEFAULT NULL,
  `TimeManagement` int DEFAULT NULL,
  `TotalMarks` int DEFAULT NULL,
  `Comments` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `assessment`
--

INSERT INTO `assessment` (`AssessmentID`, `InternshipID`, `LecturerID`, `SupervisorID`, `UndertakingTaskOrProject`, `HealthSafetyWorkplace`, `ConnectivityTheoreticalKnowledge`, `PresentationWrittenDocument`, `ClarityLanguageIllustration`, `LifelongLearningActivities`, `ProjectManagement`, `TimeManagement`, `TotalMarks`, `Comments`) VALUES
(9, 1, 30002, NULL, 6, 8, 9, 10, 5, 9, 9, 10, 66, 'Good Skills!'),
(10, 1, NULL, 40001, 9, 9, 9, 9, 9, 9, 9, 9, 72, 'Impressive.');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `CompanyID` int NOT NULL,
  `CompanyName` varchar(150) NOT NULL,
  `Location` varchar(150) DEFAULT NULL,
  `Sector` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`CompanyID`, `CompanyName`, `Location`, `Sector`) VALUES
(40000, 'Autotek Sdn Bhd', 'Petaling Jaya', 'Data Analytics'),
(40001, 'Maybank', 'Kuala Lumpur', 'Banking'),
(40002, 'Boustead Holdings Petronas', '', 'Oil and Gas');

-- --------------------------------------------------------

--
-- Table structure for table `grade_classification`
--

CREATE TABLE `grade_classification` (
  `GradeID` int NOT NULL,
  `AssessmentID` int NOT NULL,
  `GradeClassification` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grade_classification`
--

INSERT INTO `grade_classification` (`GradeID`, `AssessmentID`, `GradeClassification`) VALUES
(7, 9, 'C'),
(8, 10, 'C');

-- --------------------------------------------------------

--
-- Table structure for table `internship`
--

CREATE TABLE `internship` (
  `InternshipID` int NOT NULL,
  `StudentID` int NOT NULL,
  `LecturerID` int DEFAULT NULL,
  `SupervisorID` int DEFAULT NULL,
  `CompanyID` int NOT NULL,
  `Duration` int DEFAULT NULL,
  `Start_Date` date NOT NULL,
  `End_Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `internship`
--

INSERT INTO `internship` (`InternshipID`, `StudentID`, `LecturerID`, `SupervisorID`, `CompanyID`, `Duration`, `Start_Date`, `End_Date`) VALUES
(1, 20011, 30002, 40001, 40000, 1, '2026-04-01', '2026-04-30'),
(2, 20010, 30001, 40002, 40001, 6, '2026-01-01', '2026-06-30');

-- --------------------------------------------------------

--
-- Table structure for table `lecturer`
--

CREATE TABLE `lecturer` (
  `LecturerID` int NOT NULL,
  `UserID` int NOT NULL,
  `Name` varchar(150) DEFAULT NULL,
  `Department` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lecturer`
--

INSERT INTO `lecturer` (`LecturerID`, `UserID`, `Name`, `Department`) VALUES
(30000, 37, 'Dr Tan Chye Cheah', 'School of Computer and Mathematical Sciences'),
(30001, 38, 'Dr Liew Kian Wah', 'School of Computer and Mathematical Sciences'),
(30002, 39, 'Dr Siu Yee New', 'School of Psychology');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int NOT NULL,
  `Name` varchar(150) NOT NULL,
  `Programme` varchar(150) DEFAULT NULL,
  `UserID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `Name`, `Programme`, `UserID`) VALUES
(20009, 'Mohamed Fahd', 'Mathematics and Data Science', 34),
(20010, 'Adrian Naufal Mazaya', 'Computer Science', 35),
(20011, 'Khaalid Nana', 'Engineering', 36);

-- --------------------------------------------------------

--
-- Table structure for table `supervisor`
--

CREATE TABLE `supervisor` (
  `SupervisorID` int NOT NULL,
  `UserID` int NOT NULL,
  `Name` varchar(150) DEFAULT NULL,
  `CompanyID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supervisor`
--

INSERT INTO `supervisor` (`SupervisorID`, `UserID`, `Name`, `CompanyID`) VALUES
(40001, 41, 'George Roll', 40000),
(40002, 42, 'Adibah Noor', 40001),
(40003, 43, 'Farah Faridah', 40002);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `Role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Password`, `Role`) VALUES
(1, 'admin', 'admin123', 'admin'),
(34, 'fahd', 'fahd123', 'student'),
(35, 'adrianmazaya', 'adrian123', 'student'),
(36, 'khaalid', 'nana123', 'student'),
(37, 'drtan', 'drtan123', 'lecturer'),
(38, 'drLiew', 'drLiew@234', 'lecturer'),
(39, 'drNew', 'drNew999', 'lecturer'),
(41, 'autoRoll', 'roll111', 'supervisor'),
(42, 'adibahMaybank', 'mayBank123', 'supervisor'),
(43, 'farahBHP', 'bhp123', 'supervisor');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`AssessmentID`),
  ADD KEY `InternshipID` (`InternshipID`),
  ADD KEY `LecturerID` (`LecturerID`),
  ADD KEY `SupervisorID` (`SupervisorID`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`CompanyID`),
  ADD UNIQUE KEY `uk_company_name` (`CompanyName`);

--
-- Indexes for table `grade_classification`
--
ALTER TABLE `grade_classification`
  ADD PRIMARY KEY (`GradeID`),
  ADD KEY `AssessmentID` (`AssessmentID`);

--
-- Indexes for table `internship`
--
ALTER TABLE `internship`
  ADD PRIMARY KEY (`InternshipID`),
  ADD KEY `CompanyID` (`CompanyID`),
  ADD KEY `LecturerID` (`LecturerID`),
  ADD KEY `supervisorID` (`SupervisorID`),
  ADD KEY `fk_internship_student` (`StudentID`);

--
-- Indexes for table `lecturer`
--
ALTER TABLE `lecturer`
  ADD PRIMARY KEY (`LecturerID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD PRIMARY KEY (`SupervisorID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `fk_supervisor_company` (`CompanyID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `AssessmentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `CompanyID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40004;

--
-- AUTO_INCREMENT for table `grade_classification`
--
ALTER TABLE `grade_classification`
  MODIFY `GradeID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `internship`
--
ALTER TABLE `internship`
  MODIFY `InternshipID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lecturer`
--
ALTER TABLE `lecturer`
  MODIFY `LecturerID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30003;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20012;

--
-- AUTO_INCREMENT for table `supervisor`
--
ALTER TABLE `supervisor`
  MODIFY `SupervisorID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40004;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessment`
--
ALTER TABLE `assessment`
  ADD CONSTRAINT `assessment_ibfk_1` FOREIGN KEY (`InternshipID`) REFERENCES `internship` (`InternshipID`),
  ADD CONSTRAINT `assessment_ibfk_2` FOREIGN KEY (`LecturerID`) REFERENCES `lecturer` (`LecturerID`),
  ADD CONSTRAINT `assessment_ibfk_3` FOREIGN KEY (`SupervisorID`) REFERENCES `supervisor` (`SupervisorID`);

--
-- Constraints for table `grade_classification`
--
ALTER TABLE `grade_classification`
  ADD CONSTRAINT `grade_classification_ibfk_1` FOREIGN KEY (`AssessmentID`) REFERENCES `assessment` (`AssessmentID`);

--
-- Constraints for table `internship`
--
ALTER TABLE `internship`
  ADD CONSTRAINT `fk_internship_student` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `internship_ibfk_3` FOREIGN KEY (`CompanyID`) REFERENCES `company` (`CompanyID`),
  ADD CONSTRAINT `internship_ibfk_4` FOREIGN KEY (`LecturerID`) REFERENCES `lecturer` (`LecturerID`),
  ADD CONSTRAINT `internship_ibfk_5` FOREIGN KEY (`SupervisorID`) REFERENCES `supervisor` (`SupervisorID`);

--
-- Constraints for table `lecturer`
--
ALTER TABLE `lecturer`
  ADD CONSTRAINT `lecturer_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD CONSTRAINT `fk_supervisor_company` FOREIGN KEY (`CompanyID`) REFERENCES `company` (`CompanyID`) ON DELETE CASCADE,
  ADD CONSTRAINT `supervisor_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
