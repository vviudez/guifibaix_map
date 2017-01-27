--
-- Database: `map_guifibaix`
--
CREATE DATABASE IF NOT EXISTS `map_guifibaix` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `map_guifibaix`;

-- --------------------------------------------------------

--
-- Estructura de la taula `nodos`
--

DROP TABLE IF EXISTS `nodos`;
CREATE TABLE IF NOT EXISTS `nodos` (
  `Id` int(11) NOT NULL,
  `Nodo` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `MAC` varchar(18) COLLATE utf8_unicode_ci NOT NULL,
  `IPv4` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IPv6_LL` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `JSON` text COLLATE utf8_unicode_ci NOT NULL,
  `JSONLinks` text COLLATE utf8_unicode_ci,
  `IsGateway` tinyint(4) DEFAULT '0',
  `Actualizado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `nodos`
--
ALTER TABLE `nodos`
  ADD PRIMARY KEY (`Id`), ADD KEY `Nodo` (`Nodo`,`MAC`), ADD KEY `Timestamp` (`Actualizado`);

