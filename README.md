GuifiBaix qMp Mesh Map
----------------------

This service is a simple map to show, in a visual manner, how is connected a qMp mesh network.

Each qMp node reports info to this api, using a simple REST protocol, with the "libremap" and BMX protocol info, and
saves it to a database.

The fronted website, simply reads the database and prints on the map that information.



On each qMp node, a script must be created, in order to send info to the API service, and then insert into
a cron schedule, to execute it once in an hour.

This is the script actualy using:

#-------------------------------------------------------------------------------------------------

#!/bin/ash

JSON=`libremap-agent | sed '$d'`
head="POST http://[YOUR URL]/api/rest/GuifiBaix/getData/ HTTP/1.1\r\nHost: map.guifibaix.coop\r\nConnection: close\r\nPragma: no-cache\r\nCache-Control: no-cache\r\nAccept: application/json, text/javascript, */*; q=0.01\r\nOrigin: null\r\nUser-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36\r\nContent-Type: application/x-www-form-urlencoded; charset=UTF-8\r\nAccept-Encoding: gzip, deflate\r\nAccept-Language: es-ES,es;q=0.8,ca;q=0.6,en;q=0.4"

let longJSON=`echo $JSON | wc -m`
total=`expr $longJSON - 1`
post="$head\r\nContent-Length: $total\r\n\r\n$JSON"

echo -ne $post | nc -v map.guifibaix.coop 80



head="POST http://[YOUR URL]/api/rest/GuifiBaix/getLinks/ HTTP/1.1\r\nHost: map.guifibaix.coop\r\nConnection: close\r\nPragma: no-cache\r\nCache-Control: no-cache\r\nAccept: application/json, text/javascript, */*; q=0.01\r\nOrigin: null\r\nUser-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36\r\nContent-Type: application/x-www-form-urlencoded; charset=UTF-8\r\nAccept-Encoding: gzip, deflate\r\nAccept-Language: es-ES,es;q=0.8,ca;q=0.6,en;q=0.4"
JSONLinks=`bmx6 -c --jshow status --jshow links --jshow interfaces`

let longJSONLinks=`echo $JSONLinks | wc -m`
total=`expr $longJSONLinks - 1`
post="$head\r\nContent-Length: $total\r\n\r\n$JSONLinks"

echo -ne $post | nc -v map.guifibaix.coop 80


#-------------------------------------------------------------------------------------------------




The Database is also really simple, only one table is needed, with an "on_update" timestamp change:

#-------------------------------------------------------------------------------------------------
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
  
#-------------------------------------------------------------------------------------------------




  
  











