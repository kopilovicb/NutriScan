-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 02:01 PM
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
-- Database: `nutriscan`
--

-- --------------------------------------------------------

--
-- Table structure for table `dnevnik_ishrane`
--

CREATE TABLE `dnevnik_ishrane` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) NOT NULL,
  `proizvod_id` int(11) NOT NULL,
  `kolicina_grama` int(11) NOT NULL,
  `datum_unosa` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dnevnik_ishrane`
--

INSERT INTO `dnevnik_ishrane` (`id`, `korisnik_id`, `proizvod_id`, `kolicina_grama`, `datum_unosa`) VALUES
(1, 1, 1, 180, '2025-01-10'),
(3, 2, 4, 200, '2025-01-11'),
(4, 3, 3, 40, '2025-01-12');

-- --------------------------------------------------------

--
-- Table structure for table `komentari`
--

CREATE TABLE `komentari` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) NOT NULL,
  `proizvod_id` int(11) NOT NULL,
  `tekst` text NOT NULL,
  `datum` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `komentari`
--

INSERT INTO `komentari` (`id`, `korisnik_id`, `proizvod_id`, `tekst`, `datum`) VALUES
(2, 2, 4, 'Odličan jogurt, preporuka!', '2025-12-03 13:55:26'),
(3, 3, 3, 'Malo suv, ali ok.', '2025-12-03 13:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `korisnici`
--

CREATE TABLE `korisnici` (
  `id` int(11) NOT NULL,
  `ime` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `lozinka_hash` varchar(255) NOT NULL,
  `datum_registracije` datetime DEFAULT current_timestamp(),
  `je_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `korisnici`
--

INSERT INTO `korisnici` (`id`, `ime`, `email`, `lozinka_hash`, `datum_registracije`, `je_admin`) VALUES
(1, 'Marko Marković', 'marko.new@example.com', 'hash1', '2025-12-03 13:55:26', 0),
(2, 'Jelena Petrović', 'jelena@example.com', 'hash2', '2025-12-03 13:55:26', 0),
(3, 'Ivan Ivić', 'ivan@example.com', 'hash3', '2025-12-03 13:55:26', 0),
(4, 'Admin', 'admin@gmail.com', 'admin_hash', '2025-12-03 13:58:08', 1);

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivnosti`
--

CREATE TABLE `log_aktivnosti` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) DEFAULT NULL,
  `akcija` varchar(200) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivnosti`
--

INSERT INTO `log_aktivnosti` (`id`, `korisnik_id`, `akcija`, `timestamp`) VALUES
(1, 1, 'Korisnik uneo novi dnevnik ishrane', '2025-12-03 13:55:26'),
(2, 2, 'Korisnik ocenio proizvod', '2025-12-03 13:55:26'),
(3, 3, 'Korisnik dodao komentar', '2025-12-03 13:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `ocene`
--

CREATE TABLE `ocene` (
  `id` int(11) NOT NULL,
  `korisnik_id` int(11) NOT NULL,
  `proizvod_id` int(11) NOT NULL,
  `ocena` int(11) DEFAULT NULL CHECK (`ocena` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ocene`
--

INSERT INTO `ocene` (`id`, `korisnik_id`, `proizvod_id`, `ocena`) VALUES
(1, 1, 1, 5),
(2, 1, 2, 4),
(4, 3, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `proizvodi`
--

CREATE TABLE `proizvodi` (
  `id` int(11) NOT NULL,
  `naziv` varchar(150) NOT NULL,
  `kalorije` int(11) DEFAULT NULL,
  `proteini` decimal(5,2) DEFAULT NULL,
  `masti` decimal(5,2) DEFAULT NULL,
  `ugljeni_hidrati` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proizvodi`
--

INSERT INTO `proizvodi` (`id`, `naziv`, `kalorije`, `proteini`, `masti`, `ugljeni_hidrati`) VALUES
(1, 'Jabuka', 52, 0.30, 0.20, 14.00),
(2, 'Piletina (100g)', 170, 31.00, 3.60, 0.00),
(3, 'Integralni hleb (1 kriška)', 69, 3.60, 1.10, 12.00),
(4, 'Jogurt 2.8% (200ml)', 120, 6.00, 5.00, 12.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dnevnik_ishrane`
--
ALTER TABLE `dnevnik_ishrane`
  ADD PRIMARY KEY (`id`),
  ADD KEY `korisnik_id` (`korisnik_id`),
  ADD KEY `proizvod_id` (`proizvod_id`);

--
-- Indexes for table `komentari`
--
ALTER TABLE `komentari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `korisnik_id` (`korisnik_id`),
  ADD KEY `proizvod_id` (`proizvod_id`);

--
-- Indexes for table `korisnici`
--
ALTER TABLE `korisnici`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `log_aktivnosti`
--
ALTER TABLE `log_aktivnosti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `korisnik_id` (`korisnik_id`);

--
-- Indexes for table `ocene`
--
ALTER TABLE `ocene`
  ADD PRIMARY KEY (`id`),
  ADD KEY `korisnik_id` (`korisnik_id`),
  ADD KEY `proizvod_id` (`proizvod_id`);

--
-- Indexes for table `proizvodi`
--
ALTER TABLE `proizvodi`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dnevnik_ishrane`
--
ALTER TABLE `dnevnik_ishrane`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `komentari`
--
ALTER TABLE `komentari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `korisnici`
--
ALTER TABLE `korisnici`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `log_aktivnosti`
--
ALTER TABLE `log_aktivnosti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ocene`
--
ALTER TABLE `ocene`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `proizvodi`
--
ALTER TABLE `proizvodi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dnevnik_ishrane`
--
ALTER TABLE `dnevnik_ishrane`
  ADD CONSTRAINT `dnevnik_ishrane_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dnevnik_ishrane_ibfk_2` FOREIGN KEY (`proizvod_id`) REFERENCES `proizvodi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `komentari`
--
ALTER TABLE `komentari`
  ADD CONSTRAINT `komentari_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `komentari_ibfk_2` FOREIGN KEY (`proizvod_id`) REFERENCES `proizvodi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `log_aktivnosti`
--
ALTER TABLE `log_aktivnosti`
  ADD CONSTRAINT `log_aktivnosti_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ocene`
--
ALTER TABLE `ocene`
  ADD CONSTRAINT `ocene_ibfk_1` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ocene_ibfk_2` FOREIGN KEY (`proizvod_id`) REFERENCES `proizvodi` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
