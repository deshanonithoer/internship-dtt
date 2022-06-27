-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Gegenereerd op: 28 jun 2022 om 00:23
-- Serverversie: 10.4.20-MariaDB
-- PHP-versie: 8.0.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dtt_api`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `facility`
--

CREATE TABLE `facility` (
  `id` int(11) NOT NULL,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `location_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `facility`
--

INSERT INTO `facility` (`id`, `name`, `created_at`, `location_id`) VALUES
(66, 'Test Facility 1', '2022-06-19 23:06:48', 15),
(67, 'Test Facility 2', '2022-06-26 17:42:50', 16),
(68, 'Test Facility 3', '2022-06-26 18:29:34', 15),
(69, 'Test facility #4', '2022-06-27 18:50:30', 16),
(70, 'Test facility 6', '2022-06-27 23:13:57', 15),
(71, 'Test facility 5', '2022-06-27 23:14:23', 15),
(72, 'Test facility 7', '2022-06-27 23:15:25', 15),
(73, 'Test facility 8', '2022-06-27 23:15:48', 15);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `facilitytag`
--

CREATE TABLE `facilitytag` (
  `id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `facilitytag`
--

INSERT INTO `facilitytag` (`id`, `facility_id`, `tag_id`) VALUES
(13, 69, 14),
(14, 71, 14),
(17, 72, 14),
(20, 73, 14),
(15, 71, 15),
(18, 72, 15),
(21, 73, 15),
(16, 71, 16),
(19, 72, 16),
(22, 73, 16),
(23, 73, 17);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `location`
--

CREATE TABLE `location` (
  `id` int(11) NOT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `zip_code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `country` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `phone_number` varchar(11) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `location`
--

INSERT INTO `location` (`id`, `city`, `address`, `zip_code`, `country`, `phone_number`) VALUES
(15, 'Amsterdam', 'Rijtzstraat 10', '1429 AJ', 'Netherlands', '0648751687'),
(16, 'Utrecht', 'Koningin Wilhelminalaan', '1248 KE', 'Netherlands', '0648751687');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `tag`
--

CREATE TABLE `tag` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `tag`
--

INSERT INTO `tag` (`id`, `name`) VALUES
(14, 'Test tag 1'),
(15, 'Test tag 2'),
(16, 'Test tag 3'),
(17, 'Test tag 4');

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facility is at location` (`location_id`);

--
-- Indexen voor tabel `facilitytag`
--
ALTER TABLE `facilitytag`
  ADD PRIMARY KEY (`id`,`facility_id`),
  ADD KEY `tags belong to facility` (`facility_id`),
  ADD KEY `facitlity has tags` (`tag_id`);

--
-- Indexen voor tabel `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `facility`
--
ALTER TABLE `facility`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT voor een tabel `facilitytag`
--
ALTER TABLE `facilitytag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT voor een tabel `location`
--
ALTER TABLE `location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT voor een tabel `tag`
--
ALTER TABLE `tag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `facility`
--
ALTER TABLE `facility`
  ADD CONSTRAINT `facility is at location` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `facilitytag`
--
ALTER TABLE `facilitytag`
  ADD CONSTRAINT `facitlity has tags` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tags belong to facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
