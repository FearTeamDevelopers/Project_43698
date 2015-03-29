DROP TABLE IF EXISTS `tb_admessage`;
DROP TABLE IF EXISTS `tb_adimage`;
DROP TABLE IF EXISTS `tb_advertisement`;
DROP TABLE IF EXISTS `tb_adsection`;
DROP TABLE IF EXISTS `tb_action`;
DROP TABLE IF EXISTS `tb_report`;
DROP TABLE IF EXISTS `tb_partner`;
DROP TABLE IF EXISTS `tb_pagecontent`;
DROP TABLE IF EXISTS `tb_news`;
DROP TABLE IF EXISTS `tb_photo`;
DROP TABLE IF EXISTS `tb_gallery`;
DROP TABLE IF EXISTS `tb_adminlog`;
DROP TABLE IF EXISTS `tb_authtoken`;
DROP TABLE IF EXISTS `tb_user`;
DROP TABLE IF EXISTS `tb_redirect`;
DROP TABLE IF EXISTS `tb_searchindex`;
DROP TABLE IF EXISTS `tb_searchindexlog`;
DROP TABLE IF EXISTS `tb_feedback`;
DROP TABLE IF EXISTS `tb_config`;

CREATE TABLE `tb_pagecontent` (
`id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
`active` tinyint(4) NOT NULL DEFAULT 1,
`title` varchar(150) NOT NULL,
`urlKey` varchar(200) NOT NULL DEFAULT '',
`body` MEDIUMTEXT,
`bodyEn` MEDIUMTEXT,
`keywords` varchar(350) NOT NULL DEFAULT '',
`metaTitle` varchar(150) NOT NULL DEFAULT '',
`metaDescription` TEXT,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`urlKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_partner` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`active` tinyint(4) NOT NULL DEFAULT 1,
`title` varchar(150) NOT NULL DEFAULT '',
`web` varchar(300) NOT NULL DEFAULT '',
`logo` varchar(350) NOT NULL DEFAULT '',
`section` varchar(30) NOT NULL DEFAULT '',
`rank` tinyint(4) DEFAULT 1,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_user` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`email` varchar(60) NOT NULL DEFAULT '',
`password` varchar(200) NOT NULL DEFAULT '',
`active` tinyint(4) NOT NULL DEFAULT 1,
`salt` varchar(40) NOT NULL DEFAULT '',
`role` varchar(25) NOT NULL DEFAULT '',
`lastLogin` int(10) NOT NULL DEFAULT '0',
`totalLoginAttempts` int(2) NOT NULL DEFAULT '0',
`lastLoginAttempt` int(10) NOT NULL DEFAULT '0',
`firstLoginAttempt` int(10) NOT NULL DEFAULT '0',
`firstname` varchar(40) NOT NULL DEFAULT '',
`lastname` varchar(40) NOT NULL DEFAULT '',
`phoneNumber` varchar(15) NOT NULL DEFAULT '',
`emailActivationToken` varchar(50) DEFAULT NULL,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`salt`),
UNIQUE KEY (`emailActivationToken`),
UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_gallery` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` INT UNSIGNED,
`avatarPhotoId` INT UNSIGNED NOT NULL DEFAULT 0,
`active` tinyint(4) NOT NULL DEFAULT 1,
`urlKey` varchar(200) NOT NULL DEFAULT '',
`userAlias` varchar(80) NOT NULL DEFAULT '',
`title` varchar(150) NOT NULL DEFAULT '',
`description` TEXT,
`isPublic` tinyint(4) NOT NULL DEFAULT 1,
`rank` tinyint(4) DEFAULT 1,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`urlKey`),
KEY `ix_gallery_active` (`active`),
FOREIGN KEY `fk_gallery_user` (`userId`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_photo` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`galleryId` INT UNSIGNED NOT NULL,
`active` tinyint(4) NOT NULL DEFAULT 1,
`photoName` varchar(60) NOT NULL DEFAULT '',
`imgThumb` varchar(350) NOT NULL DEFAULT '',
`imgMain` varchar(350) NOT NULL DEFAULT '',
`description` TEXT,
`rank` tinyint(4) DEFAULT 1,
`mime` varchar(32) NOT NULL DEFAULT '',
`format` varchar(10) NOT NULL DEFAULT '',
`size` INT NOT NULL DEFAULT 0,
`width` INT NOT NULL DEFAULT 0,
`height` INT NOT NULL DEFAULT 0,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `ix_photo_active` (`active`),
FOREIGN KEY `fk_photo_gallery` (`galleryId`) REFERENCES `tb_gallery` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_action` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` INT UNSIGNED,
`active` tinyint(4) NOT NULL DEFAULT 1,
`approved` tinyint(4) NOT NULL DEFAULT 0,
`archive` tinyint(4) NOT NULL DEFAULT 0,
`urlKey` varchar(200) NOT NULL DEFAULT '',
`userAlias` varchar(80) NOT NULL DEFAULT '',
`title` varchar(150) NOT NULL DEFAULT '',
`shortBody` TEXT,
`body` MEDIUMTEXT,
`rank` tinyint(4) NOT NULL DEFAULT 1,
`startDate` varchar(22) DEFAULT NULL,
`endDate` varchar(22) DEFAULT NULL,
`startTime` varchar(22) DEFAULT NULL,
`endTime` varchar(22) DEFAULT NULL,
`keywords` varchar(350) NOT NULL DEFAULT '',
`metaTitle` varchar(150) NOT NULL DEFAULT '',
`metaDescription` TEXT,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`urlKey`),
KEY `ix_action_archive` (`active`, `approved`, `archive`),
FOREIGN KEY `fk_action_user` (`userId`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_report` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` INT UNSIGNED,
`active` tinyint(4) NOT NULL DEFAULT 1,
`approved` tinyint(4) NOT NULL DEFAULT 0,
`archive` tinyint(4) NOT NULL DEFAULT 0,
`urlKey` varchar(200) NOT NULL DEFAULT '',
`userAlias` varchar(80) NOT NULL DEFAULT '',
`title` varchar(150) NOT NULL DEFAULT '',
`shortBody` TEXT,
`body` MEDIUMTEXT,
`rank` tinyint(4) NOT NULL DEFAULT 1,
`photoName` varchar(60) NOT NULL DEFAULT '',
`imgThumb` varchar(350) NOT NULL DEFAULT '',
`imgMain` varchar(350) NOT NULL DEFAULT '',
`keywords` varchar(350) NOT NULL DEFAULT '',
`metaTitle` varchar(150) NOT NULL DEFAULT '',
`metaDescription` TEXT,
`metaImage` varchar(350) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`urlKey`),
KEY `ix_report_archive` (`active`, `approved`, `archive`),
FOREIGN KEY `fk_report_user` (`userId`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_news` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` INT UNSIGNED,
`active` tinyint(4) NOT NULL DEFAULT 1,
`approved` tinyint(4) NOT NULL DEFAULT 0,
`archive` tinyint(4) NOT NULL DEFAULT 0,
`urlKey` varchar(200) NOT NULL DEFAULT '',
`userAlias` varchar(80) NOT NULL DEFAULT '',
`title` varchar(150) NOT NULL DEFAULT '',
`shortBody` TEXT,
`body` MEDIUMTEXT,
`rank` tinyint(4) NOT NULL DEFAULT 1,
`keywords` varchar(350) NOT NULL DEFAULT '',
`metaTitle` varchar(150) NOT NULL DEFAULT '',
`metaDescription` TEXT,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`urlKey`),
KEY `ix_news_archive` (`active`, `approved`, `archive`),
FOREIGN KEY `fk_news_user` (`userId`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_adsection` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`active` tinyint(4) NOT NULL DEFAULT 1,
`urlKey` varchar(200) NOT NULL DEFAULT '',
`title` varchar(150) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`urlKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_advertisement` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` INT UNSIGNED,
`sectionId` INT UNSIGNED,
`active` tinyint(4) NOT NULL DEFAULT 1,
`uniqueKey` varchar(50) NOT NULL DEFAULT '',
`adType` ENUM('tender', 'demand') DEFAULT 'tender',
`userAlias` varchar(80) NOT NULL DEFAULT '',
`title` varchar(150) NOT NULL DEFAULT '',
`content` TEXT,
`price` FLOAT NOT NULL DEFAULT 0,
`expirationDate` varchar(22) DEFAULT NULL,
`keywords` varchar(350) NOT NULL DEFAULT '',
`hasAvailabilityRequest` tinyint(4) NOT NULL DEFAULT 0,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`uniqueKey`),
KEY `ix_advertisement_active` (`active`),
FOREIGN KEY `fk_advertisement_user` (`userId`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL,
FOREIGN KEY `fk_advertisement_section` (`sectionId`) REFERENCES `tb_adsection` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_adimage` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`adId` INT UNSIGNED NOT NULL,
`userId` INT UNSIGNED,
`photoName` varchar(60) NOT NULL DEFAULT '',
`imgThumb` varchar(350) NOT NULL DEFAULT '',
`imgMain` varchar(350) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY `fk_adimage_advertisement` (`adId`) REFERENCES `tb_advertisement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_admessage` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`adId` INT UNSIGNED NOT NULL,
`msAuthor` varchar(80) NOT NULL DEFAULT '',
`msEmail` varchar(60) NOT NULL DEFAULT '',
`message` TEXT,
`sendEmailCopy` tinyint(4) NOT NULL DEFAULT 0,
`messageSent` tinyint(4) NOT NULL DEFAULT 0,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
FOREIGN KEY `fk_admessage_advertisement` (`adId`) REFERENCES `tb_advertisement` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_adminlog` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` varchar(80) NOT NULL DEFAULT '',
`module` varchar(50) NOT NULL DEFAULT '',
`controller` varchar(50) NOT NULL DEFAULT '',
`action` varchar(50) NOT NULL DEFAULT '',
`result` varchar(15) NOT NULL DEFAULT '',
`params` varchar(350) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_redirect` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`fromPath` varchar(250) NOT NULL DEFAULT '',
`toPath` varchar(250) NOT NULL DEFAULT '',
`module` varchar(30) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`fromPath`, `toPath`, `module`),
KEY `ix_redirect` (`fromPath`, `module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_searchindex` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`sourceModel` varchar(100) NOT NULL DEFAULT '',
`sword` varchar(100) NOT NULL DEFAULT '',
`pathToSource` varchar(350) NOT NULL DEFAULT '',
`sourceTitle` varchar(150) NOT NULL DEFAULT '',
`sourceMetaDescription` TEXT,
`sourceCreated` varchar(22) DEFAULT NULL,
`occurence` INT NOT NULL DEFAULT 0,
`weight` INT NOT NULL DEFAULT 0,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `ix_search` (`sword`, `occurence`, `weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_searchindexlog` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`sourceModel` varchar(100) NOT NULL DEFAULT '',
`idxTableAlias` varchar(100) NOT NULL DEFAULT '',
`runBy` varchar(100) NOT NULL DEFAULT '',
`isManualIndex` tinyint(4) NOT NULL DEFAULT 0,
`wordsCount` INT NOT NULL DEFAULT 0,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `ix_manual` (`isManualIndex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_config` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`title` varchar(200) NOT NULL DEFAULT '',
`xkey` varchar(200) NOT NULL DEFAULT '',
`value` varchar(500) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_authtoken` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userId` INT UNSIGNED,
`token` varchar(130) NOT NULL DEFAULT '',
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tb_feedback` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`userAlias` varchar(80) NOT NULL DEFAULT '',
`message` TEXT,
`created` varchar(22) DEFAULT NULL,
`modified` varchar(22) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tb_config` VALUES (default, 'Application status', 'appstatus', 1, now(), default),
(default, 'Meta Keywords', 'meta_keywords', 'potápění', now(), default),
(default, 'Meta Description', 'meta_description', 'Potápěčské centrum Hastrman', now(), default),
(default, 'Meta Robots', 'meta_robots', 'NOINDEX,NOFOLLOW', now(), default),
(default, 'Meta Title', 'meta_title', 'Hastrman', now(), default),
(default, 'Meta OG URL', 'meta_og_url', 'http://www.hastrman.cz', now(), default),
(default, 'Meta OG Type', 'meta_og_type', 'website', now(), default),
(default, 'Meta OG Site name', 'meta_og_site_name', 'Hastrman', now(), default),
(default, 'Meta OG Image', 'meta_og_image', 'http://www.hastrman.cz/public/images/logo_hastrman_divers.jpg', now(), default),
(default, 'Photo thumb height', 'thumb_height', 200, now(), default),
(default, 'Photo thumb widht', 'thumb_width', 200, now(), default),
(default, 'Photo thumb resize by', 'thumb_resizeby', 'height', now(), default),
(default, 'Photo max height', 'photo_maxheight', 1080, now(), default),
(default, 'Photo max width', 'photo_maxwidth', 1920, now(), default),
(default, 'Životnost inzerátu (dny)', 'bazar_ad_ttl', 90, now(), default),
(default, 'Počet akcí na stránku', 'actions_per_page', 10, now(), default),
(default, 'Počet novinek na stránku', 'news_per_page', 10, now(), default),
(default, 'Počet reportáží na stránku', 'reports_per_page', 12, now(), default),
(default, 'Počet výsledků vyhledávání', 'search_results_per_page', 10, now(), default),
(default, 'Počet výsledků vyhledávání v bazaru', 'bazaar_search_results_per_page', 10, now(), default),
(default, 'Aktivovat uživ.účet pomocí ověřovacího emailu', 'registration_verif_email', 1, now(), default),
(default, 'Automaticky publikovat akce', 'action_autopublish', 0, now(), default),
(default, 'Automaticky publikovat reportáže', 'report_autopublish', 0, now(), default),
(default, 'Automaticky publikovat novinky', 'news_autopublish', 0, now(), default);

INSERT INTO `tb_redirect` VALUES
(default, '/akce', '/app/action/index/', 'app', now(), default),
(default, '/archivakci', '/app/action/archive/', 'app', now(), default),
(default, '/novinky', '/app/news/index/', 'app', now(), default),
(default, '/reportaze', '/app/report/index/', 'app', now(), default),
(default, '/galerie', '/app/gallery/index/', 'app', now(), default),
(default, '/nenalezeno', '/app/index/notFound/', 'app', now(), default),
(default, '/bazar', '/app/advertisement/index/', 'app', now(), default),
(default, '/bazar/pridat', '/app/advertisement/add/', 'app', now(), default),
(default, '/bazar/moje-inzeraty', '/app/advertisement/listByUser/', 'app', now(), default),
(default, '/bazar/nenalezeno', '/app/index/notFound/', 'app', now(), default),
(default, '/prihlasit', '/app/user/login/', 'app', now(), default),
(default, '/odhlasit', '/app/user/logout/', 'app', now(), default),
(default, '/muj-profil', '/app/user/profile/', 'app', now(), default),
(default, '/registrace', '/app/user/registration/', 'app', now(), default),
(default, '/feedback', '/app/system/feedback/', 'app', now(), default),
(default, '/o-nas', '/page/o-nas', 'app', now(), default),
(default, '/klub-hastrman', '/page/klub-hastrman', 'app', now(), default),
(default, '/apnea-diving-international', '/page/apnea-diving-international', 'app', now(), default),
(default, '/apnea-tym', '/page/apnea-tym', 'app', now(), default),
(default, '/odkazy', '/page/odkazy', 'app', now(), default),
(default, '/sluzby/plneni-lahvi', '/page/plneni-lahvi', 'app', now(), default),
(default, '/sluzby/prodej-nove-vystroje', '/page/prodej-nove-vystroje', 'app', now(), default),
(default, '/sluzby/prace-pod-vodou', '/page/prace-pod-vodou', 'app', now(), default),
(default, '/sluzby/pujcovna-potapecske-vystroje', '/page/pujcovna-potapecske-vystroje', 'app', now(), default),
(default, '/sluzby/servis-potapecske-techniky', '/page/servis-potapecske-techniky', 'app', now(), default),
(default, '/bazen', '/page/bazen', 'app', now(), default),
(default, '/technika', '/page/technika', 'app', now(), default),
(default, '/pojisteni', '/page/pojisteni', 'app', now(), default),
(default, '/kurzy', '/page/kurzy', 'app', now(), default),
(default, '/kurzy/cmas', '/page/kurzy-cmas', 'app', now(), default),
(default, '/kurzy/tdi', '/page/kurzy-tdi', 'app', now(), default),
(default, '/kurzy/sdi', '/page/kurzy-sdi', 'app', now(), default),
(default, '/kurzy/udi', '/page/kurzy-udi', 'app', now(), default);

INSERT INTO `tb_user` VALUES(default, 'hodan.tomas@gmail.com', 'cdc15216f6fabc9863a99e3a7dbc3037fd99d0df0842fbe11671b466baf9b5a3a4c0c42dec12a96e1b5ac10aa687ca846be7440ba7ccded13ac9820d789f2c37', 
    default, '3ee5042db8e109bc3b868adce63ea92231d96b65', 'role_superadmin', default, default, 
    default, default, 'Tomas', 'Hodan', default, default, now(), default);

INSERT INTO `tb_user` VALUES(default, 'kuhn@hastrman.cz', '5c5cb8e70d55d394100393a659f605571861e8a447823f0ab62c4c536163cf76ac5657c860130306519b3b14f82b04217723695dd162c99e727ab3e2b7126aef', 
    default, '283e13a940fa530d3b7fed732d1891d457b65c29', 'role_admin', default, default, 
    default, default, 'Bohumir', 'Kuhn', default, default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Odkazy', 'odkazy', 
'<h1>Zajímavé www stránky</h1><br/>
<p>
<a title="Strany potápěčské" href="http://www.stranypotapecske.cz/" target="_blank">Strany potápěčské</a> 
<br /><a title="www.adrex.cz" href="http://www.adrex.cz" target="_blank">www.adrex.cz</a> 
<br /><a title="www.freediving.cz" href="http://www.freediving.cz" target="_blank">www.freediving.cz</a> 
<br /><a title="www.pinguindiving.cz" href="http://www.pinguindiving.cz" target="_blank">www.pinguindiving.cz</a> 
<br /><a title="www.centrum-nurkowe.pl" href="http://www.centrum-nurkowe.pl" target="_blank">www.centrum-nurkowe.pl</a> 
<br /><a title="Potápěčský klub PRAGOAQUANAUT" href="http://www.pragoaquanaut.wz.cz/" target="_blank">Potápěčský klub PRAGOAQUANAUT</a>
</p>
<h3>Zajímavé lokality:</h3>
<p>Attersee, Rakousko - <a title="Attersee, Rakousko" href="http://www.arge-tauchen.at/attersee.htm" target="_blank">www.arge-tauchen.at</a> 
<br />Borek - <a title="Borek" href="http://www.stranypotapecske.cz/index2.htm?soubor=lokality/lokaldet.asp&amp;Nazev=Borek" target="_blank">www.stranypotapecske.cz</a> 
<br />Gosausee, Rakousko - <a title="Gosausee, Rakousko" href="http://www.arge-tauchen.at/gosausee.htm" target="_blank">www.arge-tauchen.at</a> 
<br />Horka, Německo - <a title="Horka, Německo" href="http://www.aquapur.de/" target="_blank">www.aquapur.de</a> , <a title="Horka, Německo" href="http://www.200bar.de/sichtweiten/tauchen_spotinfo.php?id=70" target="_blank">www.200bar.de</a> 
<br />Jesenný - <a title="Jesenný" href="http://www.stranypotapecske.cz/index2.htm?soubor=lokality/lokaldet.asp&amp;Nazev=Jesenn%FD" target="_blank">www.stranypotapecske.cz</a> 
<br />Kamenz, Německo - <a title="Kamenz, Německo" href="http://www.techtauchen-sparmann.de/" target="_blank">www.techtauchen-sparmann.de</a> 
<br />Lěštinka - <a title="Leštinka" href="http://www.cevoxdive.cz/cs/kontakty/respo---lom-lestinka/" target="_blank">www.cevoxdive.cz</a> 
<br />Lěštinka lom Zvěřinov- <a title="Leštinka lom Zvěřinov" href="http://www.stranypotapecske.cz/index2.htm?soubor=lokality/lokaldet.asp&amp;Nazev=Le%9Atinka+-+lom+Zv%EC%F8inov" target="_blank">www.stranypotapecske.cz</a> 
<br />Lěštinka - Kaňon - <a title="Leštinka Kaňon" href="http://www.stranypotapecske.cz/index2.htm?soubor=lokality/lokaldet.asp&amp;Nazev=Le%9Atinka+-+Ka%F2on" target="_blank">www.stranypotapecske.cz</a> 
<br />Rumchalpa - <a title="Rumchalpa" href="http://www.rumchalpa.cz/" target="_blank">www.rumchalpa.cz</a> 
<br />Tauchtreff Kubschütz - <a title="Tauchtreff Kubschütz" href="http://www.drdiving.de/" target="_blank">www.drdiving.de</a> 
<br />Trhová Kamenice - <a title="Trhová Kamenice" href="http://www.stranypotapecske.cz/index2.htm?soubor=lokality/lokaldet.asp&amp;Nazev=Trhov%E1+Kamenice" target="_blank">www.stranypotapecske.cz</a>
</p>
<h3>Počasí a moře</h3>
<p>odkazy vybral a poskytl Jirka Hovorka - <a title="Barakuda potápěčský klub - kurzy potápění, potápěčské zájezdy, potápění eshop" href="http://www.barakuda-diving.cz/" target="_blank">www.barakuda-diving.cz</a> 
<br />
<br /><a title="teplota moře" href="http://www.wunderground.com/MAR/" target="_blank">www.wunderground.com</a> - barevně teplota moře na zeměkouli + detaily
<br /><a title="Počasí" href="http://www.wunderground.com/global/stations/80001.html" target="_blank">www.wunderground.com</a> - počasí ve zvoleném státě a pak dále v prezentovaných městech a předpověď i historie, důležité měsíční průměry tepolt a srážek v rubrice Historie a ročenka – Sezónní průměry, dále je důležité: pod Plánovač výletů je sekce View the weather history for this location.
<br /><a title="Předpověď vln" href="http://www.wunderground.com/MAR/mmm_wave_anim.html" target="_blank">www.wunderground.com</a> - animovaná předpověď vln na zeměkouili
<br /><a title="Teplota moře" href="http://www.wunderground.com/MAR/eum.html" target="_blank">www.wunderground.com</a> - barevně teplota moře kolem Evropy a ve východním Atlantiku
<br /><a title="Předpověď pro Rudé moře" href="http://www.wunderground.com/MAR/HS/020.html">www.wunderground.com</a> - text. předpověď pro Rudé moře
<br /><a title="Teplotní mapa Evropy" href="http://www.wunderground.com/global/Region/EU/Temperature.html" target="_blank">www.wunderground.com</a> - teplotní mapa Evbropy + det. text. situace ve městech zvoleného státu
<br /><a title="18 moří" href="http://www.unep.ch/seas/" target="_blank">www.unep.ch</a> - velmi všeobecný web 18ti regionálních moří od UNEPu (United Nations Environment Programm)
<br /><a title="us meteorologický web" href="http://www1.accuweather.com/adcbin/index.asp/www1.accuweather.com?partner=accuweather" target="_blank">www.accuweather.com</a> - nějaký americký meteorolocký web
</p>
<h3>Chorvatsko</h3>
<p><a title="Počasí chorvatsko" href="http://meteo.hr/index.php" target="_blank">www.meteo.hr</a> - Državni Hidrometeoroločki Závod - kompletní počásí s pododdíly
<br /><a title="Počasí" href="http://vrijeme.hr/aktpod.html" target="_blank">www.vrijeme.hr</a> - Aktualni podaci - současná situace počasí s pododdílem: Temperature mora – teplota moře na Jadranu - je overeno, ze je to velice objektivni, casto je zachyceno i stoupnuti teploty behem dne. "Vrijeme u Hrvatskoj" zachycuje soucasne meteorologicke parametry na sousi u 42 mest ve vnitrozemi i na pobrezi.
<br /><a title="Předpovědi počasí" href="http://prognoza.hr/prognoze.html" target="_blank">www.prognoza.hr</a> - předpovedi počasí "Hrvatska danas" a "Hrvatska sutra" s teplotami, oblacnosti a srazkami, „Sedmodnevna prognoza“ je 7 denní předpověď počasí pro světová města včetně chorvatských, „pomorci“ – aktuální data pro jachtaře, „satelitske slike“ - zachycuje oblacnost a jeji animovanou historii behem poslednich 3 hodin nad Evropou
<br /><a title="Teplota moře a vzduchu" href="http://www.brela.hr/climate.htm" target="_blank">www.brela.hr</a> - Klima v Brele (mezi Omišem a Splitem) - teploty moře a vzduchu
<br /><a title="Teplota, sluneční svit" href="http://www.tz-novi-vinodolski.hr/klima.html" target="_blank">www.tz-novi-vinodolski.hr</a> - Klima v Novi Vinodolski - teploty moře a vzduchu a sluneční svit
<br /><a title="Počasí v HR" href="http://vreme.yubc.net/prognoza3dana.php?kv=1&amp;idg=231&amp;idz=4&amp;idf=1299" target="_blank">www.vreme.yubc.net</a> - počasí v HR – města předpověď
<br /><a title="Srbský meteoweb" href="http://vreme.yubc.net/prognoza.php?kv=1" target="_blank">www.vreme.yubc.net</a> - Srbský meteoweb – předovědi v Evropě
</p>
<h3>Předpověď počasí, výsky vln a směry větru pro Středozemní moře:</h3>
<p><a href="http://www.datameteo.com/marine/marine14.htm" target="_blank">www.datameteo.com</a> - (Fleet Numerical) - z WW3 Europe, zahrnuje pobřeží celé Evropy i přilehlý Atlantik, vlny ve feetech nyní, 12, 24, 36, 48, 60 a 72 hodin dopředu
<br /><a href="http://www.datameteo.com/marine/marine9.htm" target="_blank">www.datameteo.com</a> - (Naval Oceanographic Office) – jen Středozemí, vlny ve feetech nyní, 12, 24, 36 a 48 hodin dopredu, přehledné, zjednodušené
<br /><a href="http://www.datameteo.com/marine/marine12.htm" target="_blank">www.datameteo.com</a> - Insular Coastal Dynamics - jen Středozemí, vlny v metrech nyní, 12, 24, 36, 48, 60 a 72 hodin dopředu
<br /><a href="http://www.datameteo.com/marine/marine13.htm" target="_blank">www.datameteo.com</a> - Athen University - jen Středozemí, vlny v metrech nyní, 6, 12, 24, 30, 36, 42 a 48 hodin dopředu OK – funguje!!!
<br /><a href="http://www.datameteo.com/portal/modules.php?name=Content&amp;pa=showpage&amp;pid=54" target="_blank">www.datameteo.com</a> - Středozemí, vlny
<br /><a href="http://www.marine/marine3datameteo.com/.htm" target="_blank">www.marine.com</a> - (Naval Oceanographic Office) – jen Jadran, vlny ve feetech nyní, 12, 24, 36, 48, 60 a 72 hodin dopředu, přehledné, nefunfuje
<br /><a href="https://www.navo.navy.mil/cgi-bin/animate.pl/metoc/52/21/0-0-1/17" target="_blank">www.navo.pl</a> - METOC-WAM Mar Adriatico - – jen Jadran, vlny ve feetech nyní, 12, 24, 36, 48, 60 a 72 hodin dopředu, přehledné
<br /><a href="http://www.datameteo.com/marine/marine11.htm" target="_blank">www.datameteo.com</a> - (Naval Oceanographic Office) – jen oblast Španělska, vlny ve feetech nyní, 12, 24, 36 a 48 hodin dopředu
<br /><a href="http://128.160.23.54/products/OFA/mswcofa.gif" target="_blank">Naval Oceanographic Office</a> - mapa teploty moře, západní Středomoří
<br /><a href="http://www.meteoliguria.it/mare/meteomare.html" target="_blank">www.meteoliguria.it</a>- Centro Meteo Idrologico della Regione Liguria – podrobné meteorologické mapy Ligurského moře: tlak, směr větru, výška vln současná a na příští den
<br /><a href="http://www.meteoliguria.it/oss/aries/ship1.jpg" target="_blank">www.meteoliguria.it</a> - Centro Meteo Idrologico della Regione Liguria – podrobné meteorologické mapy Ligurského moře: dohlednost, výška a perioda vln
<br /><a href="http://www.arpal.org/balne/balneaz/arpal_it/2002/Genova/Genova.htm" target="_blank">www.arpal.org</a> - ARPAL - mapa znečištění Ligurského moře
<br /><a href="http://www.datameteo.com/marine/marine5.htm" target="_blank">www.datameteo.com</a> - Centro Meteo Idrologico della Regione Liguria – mapa Itálie a Ligurského moře: směr a síla větru nyní a předpověď 12, 24, 36, 48, 60 a 72 hodin dopředu
<br /><a href="http://www1.sar.sardegna.it/newmeteo/mainmeteo.html?marezone.html" target="_blank">www.sar.sardegna.it</a> - podrobná numerická předpověď výšky vln kolem Sardinie na 3 dny dopředu
<br /><a href="http://www.sar.sardegna.it/newmeteo/ventozonems.html" target="_blank">www.sar.sardegna.it</a> - podrobná numerická předpověď rychlosti větru na Sardinii na 3 dny dopředu
<br /><a href="http://www.sar.sardegna.it/newmeteo/mainmeteo.html?b6z_main.html" target="_blank">www.sar.sardegna.it</a> - názvy větrů na Sardinii a Baufortova a Douglasova stupnice
<br /><a href="http://www.sar.sardegna.it/newmeteo/mainmeteo.html?b6z_main.html" target="_blank">www.sar.sardegna.it</a> - mapa teplot na Sardinii
<br /><a href="http://www.sar.sardegna.it/newmeteo/mainmeteo.html?b6z_main.html" target="_blank">www.sar.sardegna.it</a> - tabulka předpovědi teplot ve 35 místech Sardinie, 5 dní dopředu
<br /><a href="http://www.sar.sardegna.it/newmeteo/mainmeteo.html?b6z_main.html" target="_blank">www.sar.sardegna.it</a> - rozmístění výše ovedených meteorologických stanic na Sardinii
<br /><a href="http://www.sar.sardegna.it/newmeteo/mainmeteo.html?b6z_main.html" target="_blank">www.sar.sardegna.it</a> - mapa větrů na Sardinii a předpověď na 2 dny odpředu
<br /><a href="http://www.icod.org.mt/modeling/forecasts/env_med.htm" target="_blank">www.icog.org.mt</a> - Euro-Mediteranean Centre on Insular Coastal Dynamics – Daily Forecast – Ocean - surface current/temperature – Time 00, 12, 24, 36, 48, 60 a 72 hod. – Select Map : 2 mapy Středozemního moře – mapa směru a rychlosti mořských proudů a mapa teploty moře na zvolenou dobu dopředu. Tlačítko Waves je ekvivalentní s bodem 3. Ještě mapy tepelných toků.
<br /><a href="http://www.icod.org.mt/modeling/extreme/etna.htm" target="_blank">www.icog.org.mt</a> - Euro-Mediteranean Centre on Insular Coastal Dynamics – erupce na Etně
<br /><a href="http://www.wetteronline.de/cgi-bin/windframe?11&amp;CONT=afri&amp;WIND=g040&amp;KUST=00163&amp;LANG=de" target="_blank">www.wetteronline.de</a> - předpovědi a mapy pro Evropu a zbytek světa, počasí, vítr, teplota vody Středozemního moře
<br /><a href="https://www.fnmoc.navy.mil/CGI/ww3_loop.cgi?color=b&amp;area=europe&amp;prod=sig_wav_ht" target="_blank">www.fnmoc.navy.mil</a> - předpovědi a mapy pro Evropu, vlny
<br /><a href="http://www.izor.hr/eng/online/" target="_blank">www.izor.hr</a> - Institute of Oceanography and Fisheries, Split, Dubrovník, předpovědi počasí a moře pro Jadran ve Splitu, pod robné grafy teplot i moře
<br /><a href="http://www.dalmatianet.com/cmms/index.htm" target="_blank">www.dalmatianet.com</a> - CROATIAN MARINE METEOROLOGICAL SERVICE – pro jachtaře
<br /><a href="http://www.toolworks.com/bilofsky/tidetool.htm" target="_blank">www.toolworks.com</a> - SW o přílivu pro Palm
<br /><a href="http://www.co-ops.nos.noaa.gov/tp4days.html" target="_blank">www.oc-ops.nos.noaa.gov</a> - Water Level Tidal Predictions pro USA
<br /><a href="http://scilib.ucsd.edu/sio/tide/" target="_blank">www.scilib.ucsd.edu</a> - linky na předpověi přílivu
<br /><a href="http://www.bbc.co.uk/weather/marine/tides/editorial/faq.shtml" target="_blank">www.bbc.co.uk</a> - FAQ o přílivech v angličtině
<br /><a href="http://www.bbc.co.uk/weather/marine/tides/index.shtml" target="_blank">www.bbc.co.uk</a> - přílivové grafy pro GB
<br /><a href="http://www.adlu78.manufree.net/tides/uktidesuk.htm" target="_blank">www.adlu78.manufree.net</a> - přílivové tabulky GB
<br /><a href="http://www.co-ops.nos.noaa.gov/" target="_blank">co-ops.nos.noaa.gov/</a> - NOAA
<br /><a href="http://www.meto.gov.uk/weather/europe/uk/nwscotland.html" target="_blank">www.meto.gov.uk</a> - počasí UK a Orkneje
<br /><a href="http://red-sea.com/general_info/climate_weather/" target="_blank">www.red-sea.com</a> - měsíční průměry teploty vody, vzduchu, srážky a vítr v Rudém moři
<br /><a href="http://www.ameinfo.com/weather/" target="_blank">www.ameinfo.com</a> - předpověď pro Blízký Východ a Rudé moře, animace 6 hod. satelitní obrázky mraků, teplota a oblačnost každých 15 minut
<br /><a href="http://www.wetteronline.de/cgi-bin/windframe?11&amp;CONT=afri&amp;WIND=g040&amp;KUST=00163&amp;LANG=de" target="_blank">www.wetteronline.de</a> - aktuální počasí a předpověď pro Dahab a Rudé moře, převody rychlostí: Der Wind in: , vlny v RM: tlačítko Wellenhöhen , okamžité počasí z letiště: Stationsmeldungen Vorhersage
<br /><a href="http://gazetteer.hometownlocator.com/AirportWeather2.cfm?CC=eg" target="_blank">www.gazetteer.hometownlocator.com</a> – počasí letiště Egypt
<br /><a href="http://www.sunshinediving.com/main_pages/weather.htm" target="_blank">www.sunshinediving.com</a> - průměrné měsíční teploty vzduchu a vody v EG, vítr atd. a 3 denní předpověď Hurghada
<br /><a href="http://eptours.com/zzzzzz05.htm" target="_blank">www.eptours.com</a> - průměrné měsíční teploty v EG
<br /><a href="http://www.touregypt.net/climate.htm" target="_blank">www.touregypt.net</a> - průměrné měsíční teploty v EG
<br /><a href="http://weather.yahoo.com/forecast/EGXX0008_c.html?force_units=1" target="_blank">weather.yahoo.com</a> - Hurghada, Safaga, Sharm el Sheik, Quesir, Marsa Alam – předpověď počasí Rudé moře
<br /><a href="http://www.weather.com/outlook/travel/businesstraveler/map/EGXX0008?from=36hour_map" target="_blank">www.weather.com</a> – satelitní mapa počasí v RM, pohyb v čase 
<br /><a href="http://www.elbadiving.it/indice.html" target="_blank">www.elbadiving.it</a> - tlačítko METEO - počasí na Elbě aktuální, max. min., vítr, italsky
<br /><a href="http://www.eurometeo.com/english/forecast/city_LIRX/weather-forecast_Elba-Monte%20Calamita" target="_blank">www.eurometeo.com</a> - počasí na Elbě – Elba – Monte Calamita
<br /><a href="http://www.sailorschoice.com/Terms/scterms.htm" target="_blank">www.sailorschoice.com</a> - výkladový nautický slovník v angličtině
<br /><a href="http://www.bbc.co.uk/weather/ukweather/wind.shtml" target="_blank">www.bbc.co.uk</a> - animovaný vítr pro UK
<br /><a href="http://scilib.ucsd.edu/sio/tide/" target="_blank">www.scilib.ucsd.edu</a> - přehled webů, programů, teorie s přílivovou tématikou, angl., obsáhlé
<br /><a href="http://www.shom.fr/ann_marees/cgi-bin/predit_ext/choixp?opt=&amp;zone=1&amp;port=0&amp;portsel=map" target="_blank">www.shom.fr</a> - předpověď přílivu v celé Evropě, frnacouzsky, zada se datum a hodina, pro města jako Terst, Bakar, Split, Dubrovník, Bar, taky Grónsko
<br /><a href="http://www.co-ops.nos.noaa.gov/about2.html#ABOUT" target="_blank">www.co-ops.nos.noaa.gov</a> - názorné i podrobné vysvětlení přílivů
<br /><a href="http://www-ocean.tamu.edu/education/common/notes/PDF_files/book_pdf_files.html" target="_blank">www-ocean.tamu.edu</a> - přílivy a odlivy, uz je tam i revidovana verze z roku 2002, akurat se to musi stahovat kdyz jeste/uz v Emerice nepracuji
<br /><a href="http://www.murorum.demon.co.uk/sailing/" target="_blank">www.murorum.demon.co.uk</a> - v rámci jachtění povídání o slapech a navigaci, GB
<br /><a href="http://www.meteoconsult.com/" target="_blank">www.meteoconsult.com</a> - předpovědi počasí pro některá evropská města 5 dní dopředu (CZ: jen Praha a Mimoň, HR: Dubrovník, Rijeka, Zagreb)
<br /><a href="http://www.meteoconsult.fr/an/mar/maree/Maree.html" target="_blank">www.meteoconsult.fr</a> 
<br /><a href="http://www.westwind.ch/w_1nat.php" target="_blank">www.westwind.ch</a> - hydrometeorologické ústavy a předpovědi počasí všech evropských zemí
<br /><a href="http://meteo.webpark.cz/" target="_blank">www.meteo.webpark.cz</a> - meteorologie na Internetu, česky, linky na naše i zahraniční instituce
<br /><a href="http://www.chmi.cz/meteo/om/aktinf.html" target="_blank">www.chmi.cz</a> - Český hydrometeorologický ústav - Aktuální meteorologické informace
<br /><a href="http://www.astro.cz/projekty/ukazy/index.htm" target="_blank">www.astro.cz</a> - optické úkazy v atmosféře, velice názorné vysvětlení na obrázcích, fotografie, odkazy
<br /><a href="http://www.pvl.cz/aplikace/pvl.nsf/" target="_blank">www.pvl.cz</a> - informace z Povodí Vltavy
<br /><a href="http://www.pvl.cz/aplikace/pvl.nsf/0/BBBD1B8785307EF0C1256BAA002FBAB7?OpenDocument" target="_blank">www.pvl.cz</a> - teplota vody a průhlednost na našich nádržích v Povodí Vltavy
<br /><a href="http://web.telecom.cz/kralova/index.html" target="_blank">web.telecom.cz</a> - vše o počasí
<br /><a href="http://web.telecom.cz/kralova/optika.html" target="_blank">web.telecom.cz</a> - zajímavé optické úkazy v atmosféře (zelený záblesk)
<br /><a href="http://www.ped.muni.cz/wphy/STRANKA/OPTIKA/duha.htm" target="_blank">www.ped.muni.cz</a> - obrázek - vznik duhy
<br /><a href="http://astro.sci.muni.cz/%7Ehollan/a_papers/vyuka/komentar/4/000127.htm" target="_blank">astro.sci.muni.cz</a> - halové jevy
<br /><a href="http://astro.sci.muni.cz/pub/hollan/lighting/texty_html/kraj_neb.html" target="_blank">astro.sci.muni.cz</a> - halové jevy
<br /><a href="http://www.pef.zcu.cz/pef/kof/diplomky/diplomka/html/obsah.htm" target="_blank">www.pef.zcu.cz</a> - optické jevy v atmosféře
<br /><a href="http://nix.nasa.gov/nix.cgi" target="_blank">nix.nasa.gov</a> - satelitní foto zeměkoule a okolí (ale není tam všechno :-)
<br /><a href="http://eol.jsc.nasa.gov/sseop/clickmap/" target="_blank">eol.jsc.nasa.gov</a> - satelitní foto zeměkoule a okolí (ale není tam všechno :-)
<br /><a href="http://www.visibleearth.nasa.gov/" target="_blank">www.visibleearth.nasa.gov</a> - satelitní foto zeměkoule a okolí, písečné bouře vnitřní vlny např. v Rudém moři
</p>',
default, 'zajimave odkazy', 'Zajímavé odkazy', 'Odkazy na zajímavé stránky o potápění', now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Plnění láhví', 'plneni-lahvi', 
'<h1><strong>PLNÍME POUZE PRO POTŘEBY POTÁPĚČŮ</strong></h1>
<br/>
<h3>Plnění lahví</h3>
<p><ul>
    <li>Plníme: Vzduch, Nitrox, Trimix, Heliox, Heliair</li>
    <li>Ceny plnění vzduchem:<br /> členové ksp HASTRMAN - 6 Kč l/20 MPa<br /> ostatní - 8 Kč l/20 MPa </li>
    <li>Ceny ostatních plynů:<br /> <strong>Kyslík</strong> 0,07 Kč (za jeden litr atmosferického objemu)
    <br /> <strong>Helium</strong> 0,50 Kč (za jeden litr atmosferického objemu)
    <br /> <strong>Argon</strong> 0,30 Kč (za jeden litr atmosferického objemu)
    <br /> <br /> * Veškeré plyny dodáváme i v 50 l lahvích</li>
</ul>
</p>
<h3>UPOZORNĚNÍ</h3>
<p>PROVOZNÍ ŘÁD plnírny vychází z odpovídajících norem a všeobecně uznávaných zásad, zejména:
<ul>
    <li>plníme výhradně lahve v dobrém technickém stavu a s platnou tlakovou zkouškou </li>
    <li>plníme jen lahve se zbytkovým tlakem, který umožňuje analýzu původní směsi </li>
    <li>parciálně plníme jen lahve, které jsou prokazatelně kyslíkově čisté </li>
    <li>vyhrazujeme si právo nepřijmout k plnění lahve, které neodpovídají podmínkám stanoveným provozním řádem </li>
    <li>k naplnění je třeba předložit odpovídající potápěčskou kvalifikaci </li>
</ul></p>', 
default, 'plneni lahvi', 'Plnění láhví', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Prodej nové výstroje', 'prodej-nove-vystroje', 
'<h1>Prodej nové výstroje</h1><br/>
<h3>POTÁPĚČSKÁ TECHNIKA</h3>
<p>Vedle používání zdravého rozumu, dostatečné kvalifikace i praxe je kvalitní a spolehlivá potápěčská technika 
jednou z podmínek dosažení nejvyšší míry bezpečnosti vašich ponorů. Kupujte jen takovou výstroj, která tato kriteria splňuje! 
Pamatujte, že cena je až druhotným ukazatelem... nebo váš život stojí za pár ušetřených stokorun?<br/><br/>
Potápěčské centrum <strong>Hastrman</strong> Mladá Boleslav vám pomůže zorientovat se v obrovském sortimentu 
výstroje pro rekreační i technické potápění. Co je vhodné pro jeden druh potápění, nemusí vyhovovat podmínkám druhého. 
Váháte-li s výběrem konkrétního produktu, kontaktujte a využijte možnost odborné konzultace, poradenství i zácviku.</p>
<p><strong>Prosíme o strpení … na dalších informacích se pracuje… </strong></p>', 
default, 'prodej nove vystroje', 'Prodej nové výstroje', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Práce pod vodou', 'prace-pod-vodou', 
'<h1>Práce pod vodou</h1><br/>
<p><ul>
    <li>video a fotodokumentace</li>
    <li>práce strojního a stavebního charakteru </li>
    <li>průzkumy a posudky </li>
    <li>odsávání sedimentů </li>
    <li>těsnění jímek a hrází </li>
    <li>řezání ocele a betonu </li>
    <li>veškeré montáže a demontáže</li>
</ul>​</p>', 
default, 'prace pod vodou', 'Práce pod vodou', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Půjčovna potápěčské výstroje', 'pujcovna-potapecske-vystroje', 
'<h1>Půjčovna potápěčské výstroje</h1><br/>
<p><table cellpadding="0" border="1">
    <tbody>
        <tr>
            <td>
                <p><strong> Položka výstroje</strong></p>
            </td>
            <td width="70">
                <p align="center"><strong>Cena / den</strong></p>
            </td>
            <td width="70">
                <p align="center"><strong>Kauce</strong><sup>*</sup></p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Maska</p>
            </td>
            <td>
                <p align="center">15</p>
            </td>
            <td>
                <p align="center">500</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Šnorchl</p>
            </td>
            <td>
                <p align="center">5</p>
            </td>
            <td>
                <p align="center">100</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Ploutve</p>
            </td>
            <td>
                <p align="center">20</p>
            </td>
            <td>
                <p align="center">1000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Lahev (1 l vod.obsahu)</p>
            </td>
            <td>
                <p align="center">5</p>
            </td>
            <td>
                <p align="center">500</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Automatika s octopusem + manometr</p>
            </td>
            <td>
                <p align="center">120</p>
            </td>
            <td>
                <p align="center">7000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Jacket</p>
            </td>
            <td>
                <p align="center">100</p>
            </td>
            <td>
                <p align="center">6000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Neoprenový oblek</p>
            </td>
            <td>
                <p align="center">100</p>
            </td>
            <td>
                <p align="center">6000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Botičky</p>
            </td>
            <td>
                <p align="center">20</p>
            </td>
            <td>
                <p align="center">500</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Rukavice</p>
            </td>
            <td>
                <p align="center">25</p>
            </td>
            <td>
                <p align="center">500</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Nůž</p>
            </td>
            <td>
                <p align="center">15</p>
            </td>
            <td>
                <p align="center">500</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Hloubkoměr</p>
            </td>
            <td>
                <p align="center">25</p>
            </td>
            <td>
                <p align="center">1000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Kompas</p>
            </td>
            <td>
                <p align="center">25</p>
            </td>
            <td>
                <p align="center">1000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Uwatec gauge</p>
            </td>
            <td>
                <p align="center">35</p>
            </td>
            <td>
                <p align="center">3000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Dekompresní počítač</p>
            </td>
            <td>
                <p align="center">120</p>
            </td>
            <td>
                <p align="center">6000</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Opasek + olova</p>
            </td>
            <td>
                <p align="center">15</p>
            </td>
            <td>
                <p align="center">500</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Baterka (bez baterií)</p>
            </td>
            <td>
                <p align="center">50</p>
            </td>
            <td>
                <p align="center">5000</p>
            </td>
        </tr>
    </tbody>
</table>
</p>
<p><strong>Členové ksp HASTRMAN kauci neplatí!<br /> Půjčovné v Kč včetně DPH. </strong><br/>
<strong>Minimální doba zápůjčky je 1 den. V případě nepoužití zapůjčeného materiálu se hradí pouze sazba za jeden den.</strong>
</p>
<p><table cellpadding="0" border="1"">
    <tbody>
        <tr>
            <td colspan="2">
                <p><strong>Slevy</strong></p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Členové ksp HASTRMAN</p>
            </td>
            <td>
                <p align="center">-10%</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Zápůjčka na 3-5 dnů</p>
            </td>
            <td>
                <p align="center">- 5%</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Zápůjčka na 5 -10 dnů</p>
            </td>
            <td>
                <p align="center">-10%</p>
            </td>
        </tr>
        <tr>
            <td>
                <p>Zápůjčka nad 10 dnů</p>
            </td>
            <td>
                <p align="center">-15%</p>
            </td>
        </tr>
    </tbody>
</table>
</p>
<p><strong>Další informace o podmínkách výpůjčky na tel. 603 161 417. </strong></p>', 
default, 'pujcovna potapecske vystroje', 'Půjčovna potápěčské výstroje', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Servis potápěčské techniky', 'servis-potapecske-techniky', 
'<h1>Servis potápěčské techniky</h1><br/>
<p><ul>
    <li>Servis automatik<br /> - Poseidon<br /> - Mares<br /> - Apeks<br /> - Cressi<br /> - Ocean reef<br /> - Scubapro </li>
    <li>Opravy žaketů </li>
    <li>Drobné opravy neoprenových obleků </li>
    <li>Výměna všech manžet, zipů a bot u suchých obleků </li>
    <li>Testy lahví </li>
</ul>​</p>', 
default, 'servis potapecske techniky', 'Servis potápěčské techniky', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Bazén', 'bazen', 
'<h1>Bazény pro hastrmany</h1><br/>
<p>Přijatelné podmínky pro plnohodnotný trénink v prostředí krytého bazénu bohužel v 
Mladé Boleslavi nejsou. Jezdíme tedy na Mělník, výjimečně do Liberce a některé akce směrujeme 
do výcvikové jámy Olson Aquaparku v Čestlicích. Rozpis platí v průběhu celého roku s výjimkou 
prázdninových měsíců, července a srpna. Přesné termíny odstávky jednotlivých bazénů, 
jakož i informaci o vánočním provozu zde najdete v dostatečném předstihu…</p>
<h3>Bazén na Mělníce:</h3>
<p>Úterý &nbsp; &nbsp; &nbsp; &nbsp;20:00 - 22:00<br /> 
Středa&nbsp; &nbsp; &nbsp; &nbsp;20:00 - 22:00<br /> 
Neděle&nbsp; &nbsp; &nbsp; 10:00 - 12:00<br/>
Bližší informace o přístupu, otvíracích hodinách a cenách najdete na adrese:
<br /> <a target="_blank" href="http://bazen-melnik.wz.cz/">http://bazen-melnik.wz.cz/</a><br/>
* Termíny pro trénink technického potápění po dohodě</p>
<h3>Bazén v Liberci:</h3>
<p>Středa&nbsp; &nbsp; &nbsp; 20:00 - 21:00<br/>
Bližší informace o přístupu, otvíracích hodinách a cenách najdete na adrese:
<br /> <a target="_blank" href="http://www.bazen-info.cz/">http://www.bazen-info.cz/</a>​</p>', 
default, 'bazen melnik liberec', 'Bazén', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Technika', 'technika', 
'<h1>Potápěčská technika</h1><br/>
<p>Vedle používání zdravého rozumu, dostatečné kvalifikace i praxe je kvalitní a spolehlivá potápěčská technika 
jednou z podmínek dosažení nejvyšší míry bezpečnosti vašich ponorů. Kupujte jen takovou výstroj, 
která tato kriteria splňuje! Pamatujte, že cena je až druhotným ukazatelem ... nebo váš život stojí za pár ušetřených stokorun?<br/><br/>
Potápěčské centrum <strong>Hastrman</strong> Mladá Boleslav vám pomůže zorientovat se v 
obrovském sortimentu výstroje pro rekreační i technické potápění. Co je vhodné pro jeden druh potápění, 
nemusí vyhovovat podmínkám druhého. Váháte-li s výběrem konkrétního produktu, kontaktujte a využijte možnost odborné konzultace, poradenství i zácviku.<br/><br/>
Výkoné potápěčské skútry firmy Suex s.r.l.<br /><a target="_blank" href="/public/uploads/files/suex_katalog_2014.pdf">Suex katalog 2014</a></p>', 
default, 'technika', 'Technika', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Pojištění', 'pojisteni', 
'<h1>Pojištění - DAN Europe Česko</h1><br/>
<p>Před začínající potápěčskou sezonou rozbíhá svoji činnost národní pobočka DAN Europe Česko. 
Důvodem založení národní kaceláře je rostoucí počet členů- potápěčů z České republiky po našem 
vstupu do EU bezkonkureční servis poskytovaný celosvětově členům DAN. 
Zkratka DAN znamená z angličtiny Diving Alert Network-potápěčská záchranná síť.</p>
<p><strong>DAN Europe je mezinárodní nezisková nadace, která svým členům poskytuje služby sloužící větší bezpečnosti potápění.</strong>
Mimo jiné i speciální na míru pro potápěče šité pojištění.Součástí členství je i roční běžné cestovní pojištění - Travel Assist.<br/>
Své aktivity DAN Europe dále soustřeďuje na výzkum zaměřený na zvýšení bezpečnosti potápění, pořádání vzdělávacích 
kurzů pro potápěče jako např. kurzy poskytovatelů kyslíku, kurzy poskytování první pomoci při nehodách ve vodním prostředí, 
kurzy první pomoci při poranění mořskými živočichy, konzultační činnost v oblasti potápěčské medicíny a jiné. 
Každý člen obdrží během svého členství, 4 čísla vědeckého časopisu Diving Alert s potápěčům blízkou tématikou.<br/>
Národní kancelář si klade za cíl přenést tento servis i našim potápěčům v našich podmínkách. 
Pro případ potápěčské nehody v České republice je zřízena národní emergency hot line (608 111 799), která je 
připravena pomoci v případě nehody na našem území.Při nehodě v zahraničí člen DAN Europe kontaktuje 
mezinárodní emergency hot line se sídlem v Italii (+39 039 60 57858).</p>
<p>A virtuální platba členského poplatku bankovní kartou. Česká mutace webových stránek bude 
zpřístupněna během letošní potápěčské sezony. Pro ty, kteří mají problémy s virtuální 
platbou ( pozn. což je bohužel relativně častý případ ) je k dispozici možnost platby na 
účet DAN Europe Česko nejlépe složením peněz v hotovosti v CZK nebo v € či bankovním převodem v 
CZK ( při platbě vždy uveďte jméno, příjmení a typ zvoleného členství). Dále,prosím zašlete veškerých 
potřebné údaje o sobě (jméno,příjmení,národnost, datum a místo narození, e- mail adresa, adresa včetně PSČ, 
telefonní kontakty, zvolený typ členství ) e-mailem na adresu <a href="mailto:cekia@daneurope.org.">cekia@daneurope.org</a>. 
Po potvrzení přijetí platby na účet veškerou agendu vyřídí národní kacelář a do několika dnů dostane 
zájemce o členství e-mailem potvrzené členství z centrály v italském Rosetu( od té doby platí pojištění) a 
poštou většinou do týdne členskou kartu mini CD s návodem jak postupovat při nehodě 
(zatím ve světových jazycích) a ostatní náležitosti.V pracovní dny jsem schopni v naléhavých případech sjednat 
členství během jednoho pracovního dne.Pro ty, kteří mají zájem o urychlení doporučujeme zaslat faxem potvrzení 
o platbě s identifikačními údaji faxem na tel 495 264 641.</p>
<p>Věříme, že otevření národní pobočky DAN Europe Česko pomůže našim potápěčům zvýšit bezpečnost 
potápění a umožní rozšířit servis nadace i našim potápěčům.<br/>
Bližší informace obdrží zájemci v pracovní dny dopoledne ( 8-12:30 hodin) na telefonu DAN 
Europe Česko: 495 516 147 nebo na tel. 608 111 799, 776 146 314, nebo e- mailem na adrese 
<a href="mailto:cekia@daneurope.org">cekia@daneurope.org</a><br/>
Informační brožury v češtině jsou k dispozici na sekretariátech SPČR a v některých prodejnách potápěčské techniky a klubech.</p>
<p><strong>MUDr. Pavel Macura<br /> MUDr. Jiří Reitinger</strong>
<br /> Národní kancelář DAN Europe Česko<br /> K břízkám 4/7<br /> 500 09 Hradec Králové 
<br /><a target="_blank" href="http://www.daneurope.org/">http://www.daneurope.org/</a>
<br /><a href="mailto:cekia@daneurope.org">cekia@daneurope.org</a>​</p>', 
default, 'pojisteni dan', 'Pojištění DAN', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Kurzy', 'kurzy', 
'<h1>Kurzy</h1><br/>
<p>Potápěčské centrum <strong>HASTRMAN Mladá Boleslav</strong> má pro nové adepty přístrojového potápění, 
pokročilé, velmi zkušené či technické potápěče připraven ucelený systém výcviku, 
který umožní stálý růst jejich znalostí, schopností a dovedností.</p>
<h3>Proč začít právě s námi?</h3>
<p><ul>
    <li>Kurzy provádíme výhradně v celosvětově uznávaných systémech <a href="/kurzy/cmas">CMAS</a>, <a href="/kurzy/tdi">TDI</a> a <a href="/kurzy/sdi">SDI</a></li>
    <li>Tradice a zkušenosti z výuky potápěčů od roku 1983</li>
    <li>Kurzy jsou zakončeny udělením mezinárodní certifikace</li>
    <li>Absolutní prioritou všech kurzů jsou profesionalita, kvalita a bezpečnost</li>
    <li>Individuální přístup</li>
    <li>Nadstandardní obsah a rozsah kurzů</li>
    <li>Stáváte se členy potápěčského klubu HASTRMAN se všemi z toho plynoucími výhodami</li>
    <li>Dostanete se do party skvělých lidí, kde jistě najdete dobrého parťáka pro výlety pod vodní hladinu</li>
    <li>Kurzy můžete absolvovat v českém, anglickém nebo německém jazyce</li>
</ul></p>
<hr/>Žádosti o přihlášky do kurzu posílejte na kuhn@hastrman.cz<hr/>
<br/>
<h3>Přehled certifikačních systémů</h3><br />
<p><a href="http://www.cmas.org/" target="_blank">CMAS</a> - <strong>Confédération Mondiale des Activités Subaquatiques</strong><br/>
Celosvětová organizace sdružující potápěčské svazy, založená vynálezcem aqualungu a známým oceánografem J.Y.Costeauem. 
Je nejrozšířenějším systémem v ČR zastoupeným Svazem Potápěčů. V 250 klubech sdružuje více než 11.000 potápěčů. 
V jejich řadách najdete legendy českého potápění a mnoho velmi dobrých instruktorů poskytujících kvalitní výcvik. 
Již základní stupeň přístrojového potápění <a href="/kurzy/cmas#p*">Potápěč P*</a> vám poskytne více znalostí a dovedností, 
než většina komerčních systémů. V rámci kurzu P* tak získáte kvalifikaci srovnatelnou s pokročilým kurzem v některých 
komerčních systémech…<br/><strong><span style="color: #ff0000;"><a href="/kurzy/cmas">Kurzy CMAS</a></span></strong></p>
<p><a href="http://www.tdisdi.com/" target="_blank">SDI</a> – <strong>Scuba Diving International </strong>
<br />Dynamická agentura nabízí obdobně kvalitní výcvik jako TDI i v oblasti rekreačního potápění. 
Předností je přímočarý systém výcviku oproštěný od zbytečností. Studijní materiály jsou zjednodušeny tak, 
aby poskytovaly skutečně potřebné informace. Důraz je kladen na praktický výcvik. Výcvikový program SDI 
pružně reaguje na současné trendy a vývoj techniky. Jako první zavedla SDI do výcviku povinné 
používání osobních potápěčských počítačů jejichž správné používání preferuje před dekompresními tabulkami.
<br /> <strong><span style="color: #ff0000;"><a href="/kurzy/sdi">Kurzy SDI</a></span></strong></p>
<p><a href="http://www.udi.cz">UDI</a> - <strong>United Diving Instrutors</strong><br/>
UDI je instituce s celosvětovou působností. Jejím cílem je na vysoké úrovni zajišťovat výchovu, 
školení a výcvik instruktorů potápění, kteří budou na profesionální úrovni s dodržováním 
nejpřísnějších bezpečnostních opatření a využitím nejmodernějších výukových metod provozovat 
výcvik potápěčů všech kvalifikačních stupňů i mnoha specializačních kurzů. Organizace byla 
založena v roce 1983 v Kalifornii. Díky atraktivitě pořádaných kurzů se výcvikový systém těší 
stále větší oblibě a popularitě. Systém UDI se jako první stal držitelem Certifikace EN S 4260, 
tedy normy pro základní výcvik potápění „Open Water Diver“ platné v Evropské unii.<br/>
<strong><span style="color: #ff0000;"><a href="/kurzy/udi">Kurzy UDI</a></span></strong></p>',
default, 'kurzy', 'Kurzy', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Kurzy CMAS', 'kurzy-cmas', 
'<h1>CMAS - Přehled kurzů</h1><br/>
<p><strong>Úvodní "seznamovací" kurz potápění</strong><br />Chcete "ochutnat" potápění? Tento kurz vám poskytne 
základní informace o přístrojoém potápění. Součástí je teoretická instruktáž a ponor v 
doprovodu zkušeného instruktora. Pokud se poté přihlásíte do kurzu P*, odečteme vám tento ponor z ceny kurzovného.<br/>
Cena: 490,- Kč</p>
<p><strong>Snorkel diver</strong><br />Výcvik v potápění na nádech, tj. bez dýchacího přístroje, 
pouze s pomocí ABC (maskou, dýchací trubicí a ploutvemi). Osvojíte si správné techniky tak, 
aby vaše výlety pod vodní hladinu byly zajímavé a hlavně zcela bezpečné, rozšířeno o základy volného potápění – freedivingu.<br/>
Cena: 1600,- Kč</p>
<p><strong>Potápěč Junior SPČR</strong><br />Základní kvalifikační stupeň potápění s dýchacím 
přístrojem určený pro mladistvé adepty, kteří dosáhli 12 let věku. 
Opravňuje k potápění do malých hloubek. Po dosažení 14 let možnost upgrade na stupeň CMAS P* za zvýhodněných podmínek.<br/>
Cena: 3.900,- Kč</p>
<p style="background-color: #fcfbe3;"><strong>Potápěč s jednou hvězdou CMAS P*</strong><br />Základní kvalifikační stupeň potápění s dýchacím 
přístrojem vás naučí samostatému pobytu pod vodní hladinou. Po jeho absolvování se v doprovodu zkušeného 
potápěče můžete vydat až do hloubky 30 metrů.<br/>
Cena: 6.850,- Kč (možnost platby na splátky: 3 200,-Kč akontace + 3x 1000,-Kč/měs.)<br/>
<a href=""><strong>... a jak výcvik probíhá?</strong></a></p>
<p><strong>Potápěč se dvěma hvězdami CMAS P**</strong><br /> Kvalifikace pro pokročilé potápění se vzduchovým dýchacím 
přístrojem do hloubky max. 40 metrů opravňující mj. k potápění v noci, za snížené viditelnosti apod. 
Naučíte se navigaci pod vodou, účinné záchraně a dopomoci partnerům, důkladně se seznámíte s dekompresní teorií a jejím použitím v praxi.<br/>
Cena: 3.950.- Kč</p>
<p><strong>Potápěč se třemi hvězdami CMAS P***</strong><br /> Nejvyšší potápěčská kvalifikace pro zkušené potápěče se vzduchovým přístrojem. 
Předstupeň pro zájemce o instruktorskou činnost. Zahrnuje široké spektrum teoretických vědomostí i praktických 
dovedností od plnění přístrojů vysokotlakým kompresorem, přes ovládání malého plavidla až po organizování 
potápěčských akcí a vedení potápěčských skupin pod vodou.<br/>
Cena: 7.950,- Kč</p>
<p><strong>Instruktor sportovního potápění CMAS I* až I***</strong><br /> Přípravu na instruktorské kvalifikační stupně provádíme formou 
přednášek, konzultací, společných tréninků. Bližší podrobnosti najdete ve výcvikových směrnicích SPČR.​</p>',
default, 'kurzy cmas', 'Kurzy CMAS', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Kurzy TDI', 'kurzy-tdi', 
'<h1>TDI - Přehled kurzů</h1><br/>
<p><strong><a href="/page/tdi-nitrox">Nitroxový potápěč (Nitrox diver)</a></strong><br /> 
Toto je  úvodní kurz pro rekreační potápěče, kteří chtějí při potápění používat 
obohacený  vzduch (EAN - Enriched Air Nitrox) jako dýchací plyn. Cílem tohoto 
kurzu je  seznámit potápěče s výhodami, nebezpečími a správnými postupy používání nitroxu  s obsahem 22% až 40% kyslíku.</p>
<p><strong>Pokročilý nitroxový potápěč (Advanced Nitrox diver)</strong><br /> Tento kurz se  zabývá používáním EAN s obsahem 21% až 100% 
kyslíku pro dosažení optimální směsi  pro hloubky do 45 metrů. Cílem tohoto kurzu je seznámit potápěče s výhodami,  
nebezpečími a správnými postupy používání EAN22 až po 100% kyslík při ponorech,  u kterých je nutná stupňovitá dekomprese.</p>
<p><strong>Zkušený vrakový potápěč</strong><br /> Tento kurz zajišťuje výcvik a přípravu k  provádění komplikovaných sestupů na vracích. 
Vyučují se techniky a způsoby  pronikání do vraku.</p>
<p><strong>Dekompresní postupy</strong><br /> Kurz se zabývá teorií, metodami a postupy při  potápění s 
plánovanou stupňovitou dekompresí. Cílem kurzu je naučit potápěče jak  plánovat a provádět standardní 
sestupy se stupňovitou dekompresí, bez překročení  mezní hloubky 50 m, pokud neprobíhá výuka v kombinaci s 
kurzy zkušený nitroxový  potápěč, hloubkové potápění, zkušený vrakový potápěč. Jsou probírány nejčastější  
požadavky na vybavení, konfigurace výstroje a používání dekompresních směsí  (včetně EAN a O2).</p>
<p><strong>Hloubkové potápění</strong><br /> V tomto kurzu se vyučují metody a znalosti  potřebné k zodpovědnému 
používání vzduchu k potápění do hloubek do 55 m, s  prováděním stupňovité dekomprese, 
s použitím směsí nitroxu a čistého kyslíku při  dekompresi.</p>
<p><strong>Základní trimix (Basic Trimix Diver)</strong><br /> V tomto kurzu se učí  zodpovědné a bezpečné používání 
dýchacích směsí s obsahem helia při sestupech,  které vyžadují stupňovitou 
dekompresi s použitím nitroxu a/nebo čistého kyslíku.  Maximální hloubka je 60 m.</p>
<p><strong>Pokročilý trimixový potápěč (Advanced Trimix Diver)</strong><br /> V tomto kurzu  se učí zodpovědné a 
bezpečné používání dýchacích směsí s obsahem helia při  sestupech, které vyžadují dekompresi s 
použitím nitroxu a/nebo čistého kyslíku.  Maximální hloubka je 100 m.</p>
<p><strong>Uzavřený okruh (Rebreather)</strong><br /> Toto je základní výcvikový stupeň pro  zájemce o 
používání nitroxového přístroje s polouzavřeným okruhem (důkladně se  probírají výhody, nebezpečí a správné postupy).</p>
<p><strong>Míchač nitroxu (Nitrox Blender)</strong><br /> Cílem tohoto kurzu je naučit  účastníky správnému 
používání technického vybavení pro přípravu nitroxu a  upozornit na nebezpečí při míchání nitroxových plynů pro rekreační potápění.</p>
<p><strong>Zkušený míchač plynů (Gas Mixture Blender)</strong><br /> Cílem tohoto kurzu je  naučit účastníky správné 
postupy, které jsou potřebné pro míchání vysoce  kvalitních směsí plynů.</p>
<p><strong>Servisní technik</strong><br /> V tomto kurzu se naučí úspěšní účastníci  připravovat potápěčskou 
výstroj pro plyny používané při technickém potápění.</p>
<p><strong>Uzavřené prostory, kaverny</strong><br /> V tomto kurzu získají účastníci základní  znalosti pro 
potápění v prostorách bez volné hladiny a v kavernách v rámci  dosahu denního světla. Současně se seznámí s nebezpečími, 
která jsou specifická  pro jeskynní potápění. Kurz potápění v kavernách není v žádném případě kurzem  pro potápění v jeskyních.</p>
<p><strong>Úvod do jeskynního potápění</strong><br /> Tento kurz je úvod do základních  principů jeskynního potápění s 
používáním jedné lahve. Úvod do jeskynního  potápění je druhý stupeň při učení bezpečných technik jeskynního potápění, 
který  vychází ze znalostí získaných v kurzu potápění v kavernách.</p>
<p><strong>Jeskyně</strong><br /> Tento kurz je třetí stupeň v řadě kurzů TDI na cestě k  získání kvalifikace jeskynního potápěče. 
Jeho součásti je náročné plánování  jeskynních sestupů a jejich praktické provádění v různých typech jeskynních  systémů a 
nácvik různých situací, se kterými se může potápěč setkat pod  vodou.</p>',
default, 'kurzy tdi', 'Kurzy TDI', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Kurzy SDI', 'kurzy-sdi', 
'<h1>SDI - Přehled kurzů</h1><br/>
<p><strong>Potápění se základní výstrojí (Skin Diver)</strong><br /> Kurz poskytne studentům znalosti 
potřebné pro potápění se základní výstrojí (maska, dýchací trubice, ploutve). 
Mohou se bezpečně potápět v obdobných podmínkách, za jakých probíhal výcvik. 
Absolventi se mohou přihlásit do kurzu SDI potápění s přístrojem.</p>
<p><strong>Program pro budoucí potápěče (Future Buddies)</strong><br /> Program pro budoucí potápěče je 
zcela nový projekt, který poskytne dětem ve věku 8 a 9 let úvod do přístrojového potápění v 
bezpečném vodním prostředí pod přímým dohledem instruktora. Děti si mohou vyzkoušet 
dýchání z přístroje v chráněném prostředí (např. bazénu). V teorii se probírají 
témata jako je historie potápění, potápěčská výstroj a vodní prostředí.</p>
<p><strong>Kurz potápění s přístrojem (Open Water Diver – OWD)</strong><br /> Kurz potápění s 
přístrojem (OWD) je základní výcvikový stupeň potápění s dýchacím přístrojem. 
Kurz poskytne všechny potřebné znalosti týkající se vodního prostředí, působení 
fyzikálních zákonů, fyziologie potápěče, používané výstroje i zásad bezpečného potápění a plánování ponorů. 
V průběhu výcviku v chráněném vodní prostředí a ponorů ve volné vodě si studenti v praxi 
osvojí potápěčské dovednosti nutné k samostatnému potápění do hloubek 18 m.<br/>
Cena: 6 200,- Kč</p>
<p><strong>Program rozvoje pokročilého potápěče (AOWD)</strong><br /> SDI program rozvoje 
pokročilého potápěče byl navržen tak, aby jeho výsledkem byl opravdu pokročilý potápěč. 
Podle starého způsobu výcviku mohl student nastoupit do kurzu pro pokročilé potápěče ihned po 
dokončení základního výcviku a absolvovat tak celkem 9 sestupů pod dohledem instruktora. 
Nový program pro pokročilé potápěče nyní vyžaduje získat čtyři SDI specializační 
kvalifikace nebo jejich ekvivalent a uskutečnit celkem 25 ponorů.</p>
<p><strong>Sólo potápěč (Solo Diver)</strong><br /> Cílem tohoto kurzu ke vycvičit potápěče k správnému 
sólo (osamocenému) potápění a seznámit jej i nebezpečími a správnými postupy při sólovém potápění.</p>
<p><strong>Kurz potápěče záchranáře (Rescue Diver)</strong><br /> Záchranářský kurz je určen 
k získání potřebných znalostí a dovedností k efektivnímu provádění záchranných 
akcí a poskytování potřebné první pomoci. Studenti se učí nejen metody záchrany a správné 
poskytování první pomoci při postižení dekompresní nemocí, ale i zvládnutí potápěče v panice.</p>
<p><strong>Kurz divemastera (Divemaster)</strong><br /> Kurz pro divemastra poskytne potřebné znalosti pro 
vedení skupiny certifikovaných potápěčů ve vodním prostředí. Absolventi mohou asistovat instruktorům při 
výcviku: Dále mohou řídit a dohlížet na ponory certifikovaných potápěčů. V tomto kurzu se cvičí 
studenti v získávání znalostí a dovedností potřebných pro vedení skupin potápěčů.</p>
<p><strong>Kurz asistenta instruktora (Assistant Instructor)</strong><br /> V tomto kurz získají účastníci 
praktické zkušenosti s vedením výcviku pod dohledem aktivního instruktora. Studenti budou pomáhat 
instruktorovi při výuce v kurzech potápění s přístrojem, pokročilý potápěč, potápěč záchranář. 
Po skončení tohoto kurzu mohou absolventi učit a certifikovat potápěče se základní výstrojí, 
mohou vést program pro neaktivní potápěče. Dále mohou provádět testové zkoušky v kurzech potápění s přístrojem.</p>
<p><strong>Specializační kurzy SDI</strong>
<ul>
  <li>Potápěč ve větších nadmořských výškách </li>
  <li>Potápěč ze člunu </li>
  <li>Potápěč s počítačem </li>
  <li>Nitroxový potápěč s počítačem </li>
  <li>SDI hloubkový potápěč </li>
  <li>Potápěč s skútrem </li>
  <li>SDI driftový potápěč </li>
  <li>Specialista na vybavení </li>
  <li>Potápěč pod ledem </li>
  <li>Ochranář vodního prostředí </li>
  <li>Potápěč v noci nebo za snížené viditelnosti </li>
  <li>Potápěč výzkumník </li>
  <li>Potápěč pátrač vyhledávač </li>
  <li>Podvodní navigátor </li>
  <li>Podvodní fotograf </li>
  <li>Podvodní video kameraman </li>
  <li>Vrakový potápěč </li>
  <li>Poskytovatel CPROX </li>
  <li>TDI Nitroxový potápěč </li>
</ul></p>',
default, 'kurzy sdi', 'Kurzy SDI', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Kurzy UDI', 'kurzy-udi', 
'Na stránce se pracuje',
default, 'kurzy udi', 'Kurzy UDI', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'TDI Nitrox', 'tdi-nitrox', 
'<h1>Potápění s nitroxem</h2><br/>
<p>Mnozí z Vás se již setkali s používáním <strong>Nitroxu - dýchacích směsí obohacených o kyslík</strong>. 
Víme, že kyslík nutně potřebujeme k zachování života a že nám dusík při potápění vadí. 
Způsobuje hloubkové opojení a může vyvolat dekompresní nemoc. Proč tedy nesnížit 
obsah dusíku a nenahradit jej kyslíkem? Nitrox je ideální směsí pro ponory do 40m. Tím že naše tělo přijímá méně 
dusíku a více kyslíku se prodlužuje se nulový čas, zkracuje dekompresní čas při případném překročení nulového času, 
redukuje se tvorba mikrobublin, minimalizuje hloubkové opojení, snižuje se pravděpodobnost vzniku dekompresní nemoci, 
zkracuje se povrchový interval a snižuje tělesná únava po ponoru. Doba strávená v hloubce bez potřeby 
dekompresních zastávek se může prodloužit až na dvojnásobek! ….. To přece není málo!</p>
<p>Pokud se zajímáte o možnost získání kvalifikace opravňující k potápění s NITROXEM, pak právě Vám nabízíme kurz k získání kvalifikace:<br/>
<strong>NITROX DIVER</strong><br/>
Kurz probíhá a podle standardů TDI, která je s více než 10.000 instruktory největší agenturou specializovanou na technické potápění na světě.<br/>
<strong>Cíl kurzu:</strong> přístupnou cestou seznámit potápěče s výhodami, nebezpečími a správnými postupy používání 
Nitroxu s obsahem 22% až 40% kyslíku.<br /> 
<strong>Podmínky:</strong> kvalifikace CMAS P* nebo OWD v jiném systému<br /> 
<strong>Časový rozsah:</strong> cca 6 - 8 hodin<br /> 
<strong>Cena:</strong> 3 500 Kč + certifikace ( cca 25 USD)</p>
<p>Cena obsahuje učebnice TDI v českém jazyce, teoretickou přípravu, přezkoušení, nitroxové lahve a analyzátory k nácviku analýzy směsi. 
Pro vlastní certifikaci nejsou povinné ponory, přesto je vhodné udělat 1 – 2 ponory s Nitroxem. Cena ponoru 400 Kč.</p>',
default, 'tdi nitrox', 'TDI Nitrox', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'O nás', 'o-nas', 
'<h1>Potápěčské centrum Hastrman</h1><br/>
<h1>&hellip; váš partner v hlubinách</h1>
<br />
<p>Vznik potápěčského centra <strong>Hastrman</strong> Mladá Boleslav je spojen s tradicí a 
rozvojem sportovního potápění na mladoboleslavsku. Změny ekonomických podmínek po 
&bdquo;sametové revoluci&ldquo; vyústily v obrovský příliv nových potápěčských nadšenců. 
Potápěčské centrum vzniklo z potřeby potápěčských služeb pro členy klubu Subaqua, 
nově založeného klubu Hastrman i ostatní potápěče v regionu. Prioritou Potápěčského 
centra <strong>Hastrman</strong> je, vedle všestranného potápěčského servisu, provádění 
kvalitního výcviku potápěčů. A jak se to daří? O tom jednoznačně svědčí výsledky - 
stovky dobře vycvičených potápěčů i jejich bezproblémové a bezpečné potápěčské akce po celém světě.</p>
<br />
<p><strong>Potápěčské centrum</strong> <strong>Hastrman Mladá Boleslav</strong> 
je výcvikovým centrem <a href="/kurzy/cmas"><strong>CMAS</strong></a> a vlastní 
licenci <a href="/kurzy/sdi"><strong>SDI</strong></a> a <a href="/kurzy/tdi"><strong>TDI</strong></a> Training facility # 1001642.<br />
Nově poskytuje výcvik i v systému <strong>UDI.</strong><br />
Naší vizí je <strong>potápění all inclusive &ndash; potápění pro všechny</strong>.</p>
<hr /> <strong>Náš tým</strong><br />
<p><span style="text-decoration: underline;"><strong>Jaromír Dítě</strong></span><br/>
<img alt="jaromir_web" src="/public/uploads/images/articles/o-nas/jaromir_web.jpg" style="float: right; width: 160px; height: 201px;" />
Potápí se od roku 1999 a má na svém kontě bezmála 800 registrovaných ponorů. 
Jako člen týmu <strong>Hastrman</strong> se podílí na organizaci i realizaci služeb, 
je &bdquo;šedou eminencí&ldquo; většiny akcí pořádaných Potápěčským centrem. Ty nesou jeho osobitý punc, což potvrdí každý ze zasvěcených.</p>
<br class="clear-all"/>
<p><span style="text-decoration: underline;"><strong>Ing. Bohumír Kuhn</strong></span><br />
Potápí se od roku 1968. První stupeň instruktora sportovního potápění mu byl udělen legendárním Jindrou Maťákem v roce 1983. Od roku 1990 věnuje výcviku profesionálně.<br />
<img alt="boda_web" src="/public/uploads/images/articles/o-nas/boda_web.jpg" style="float: right; width: 160px; height: 186px;" />
<ul>
 <li>CMAS I** instruktor No.099</li>
 <li>SDI OWD instruktor No.7939</li>
 <li>TDI Nitrox instruktor No.7939</li>
 <li>TDI Advanced Trimix diver</li>
 <li>TDI Gas blender</li>
 <li>GUE Tech-1 diver</li>
 <li>DAN Oxygen provider</li>
 <li>CMAS Instruktor poskytovatele kyslíku</li>
 <li>CMAS SDI instruktor</li>
</ul></p>
<br class="clear-all"/>
<p><span style="text-decoration: underline;"><strong>Miroslav Hodaň</strong></span><br />
Potápěčské zkušenosti získal během 28 let potápěčské praxe a téměř dvoutisícovky 
registrovaných ponorů. Od roku 1982 vychoval více než stovku nových potápěčů. Jednou z jeho specializací je potápění dětí.<br />
<img alt="mira_web" src="/public/uploads/images/articles/o-nas/mira_web.jpg" style="float: right; width: 160px; height: 214px;" />
<ul>
 <li>CMAS I** instruktor No.100</li>
 <li>CMAS Instruktor potápění dětí</li>
 <li>IANTD Nitrox diver</li>
 <li>DAN Oxygen provider</li>
 <li>CMAS SDI instruktor</li>
</ul></p>
<br class="clear-all"/>
<p><span style="text-decoration: underline;"><strong>Jiří Císař</strong></span><br />
Patří k průkopníkům potápění v českých zemích. První potápěčskou kvalifikaci získal v roce 1958. 
Instruktorské činnosti se věnuje od roku 1970. Jako instruktor CMAS I*** vyškolil 
téměř tisícovku nových potápěčů. Od roku 2007 funguje aktivně jako Staff Instruktor *** ve 
výcvikovém systému United Diving Instructors (UDI) Czech Republic.<br />
<br />
<img alt="jirka_web" src="/public/uploads/images/articles/o-nas/jirka_web.jpg" style="float: right; width: 160px; height: 207px;" />
<ul>
 <li>UDI I*** Instructor</li>
</ul></p><br class="clear-all"/>',
default, 'potapecske centrum hastrman', 'O nás', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Klub Hastrman', 'klub-hastrman', 
'<h1>Klub sportovních potápěčů</h1><br/>
<p>Je jedním z 250 klubů sdružených pod hlavičkou <a href="http://www.cmas.cz/" target="_blank"><strong>Svazu potápěčů České republiky</strong></a>, 
součásti celosvětové potápěčské federace <a href="http://www.cmas.org/" target="_blank"><strong>CMAS</strong></a>. 
Navazuje na padesátiletou tradici potápění v mladoboleslavském regionu. Klub <strong>HASTRMAN</strong> je 
ideálním místem k výměně zkušeností, plánování a přípravě aktivit pod i nad vodou, zdrojem informací o 
zajímavých lokalitách, tuzemských i zahraničních potápěčských bázích, možnostech nákupu cenově přijatelné výstroje a řadě dalších výhod.</p>
<hr />
<p>Žádosti o přihlášky do ksp HASTRMAN posílejte na kuhn@hastrman.cz</p>
<hr />
<h3>HASTRMAN - pohodový vstup do „světa ticha“.</h3>
<p>Získání razítka v potápěčském průkazu a plastové kvalifikační karty nesoucí vaše 
jméno je jen začátkem dobrodružné cesty za poznáním podvodního světa. Logickou 
otázkou každého potápěčského novice je "s kým se vlastně mohu potápět? Kde najdu 
dobrého parťáka - buddyho? Nebyla by partička stejných nadšenců, jako jsem já? 
Kde seženu vhodnou výstroj, atd… Prostě co, kde s kým a jak dál? <br />
Rada je prostá. Členství v potápěčském klubu je pro začátečníka i profesionála 
ideální základnou pro uskutečnění individuálních potápěčských snů a plánů, k 
dosažení sportovních či technických met, je prostorem k vlastní seberealizaci 
umocněné možnostmi většího kolektivu. Mladoboleslavský potápěčský klub 
<strong>HASTRMAN</strong> v tomto směru není žádnou výjimkou. Octnete v okruhu 
lidí vedených stejným zájmem a cíli a otevřou se vám netušené možnosti, jež byste 
samostatně nebyli schopni získat a využívat. Není nic snazšího, než seznámit se 
třeba s přehledem připravovaných akcí podvodních výprav a expedic, zapojit se do 
jejich příprav i realizace, získávat odborné informace, zajímavé novinky, 
dozvědět se o lokalitách, kontaktech dalších možnostech zvyšování vlastní potápěčské kvalifikace, atd, atd, atd...
</p>
<h3>Prioritou klubu je bezpečné potápění.</h3>
<p>Paleta potápěčských aktivit klubu <strong>HASTRMAN</strong> je mimořádně pestrá. 
Začíná hned za humny potápěním v nejbližším rybníce, řece, přehradě či zatopeném 
lomu, pokračuje ponory v křišťálově čistých alpských jezerech či v relativně dostupných 
lokalitách Jaderského moře, graduje skvělými potápěčskými zážitky v teplých vodách 
Rudého moře a kulminuje expedicemi do exotických míst naší planety. Záleží jen a 
jen na vás, jakému typu potápění dáte přednost. A jak se zapojit? Je to snadné. 
Většinu potřebných informací získáte při pondělních schůzkách v naší klubovně. 
Nemáte čas? Nevadí! Ucelený seznam všech akcí máte k dispozici 24 hodin denně na 
Internetu. Pokud vám totiž termíny pravidelných schůzek nevyhovují, jste příliš 
vytížení nebo jste z daleka, máte jednoduchou možnost shlédnout připravované 
akce na adrese www.hastrman.cz. Tam můžete snadno vypsat vlastní akci a přizvat 
tak kolegy k potápění tam, kde a kdy sami chcete. Jak sami poznáte, počet akcí 
narůstá geometrickou řadou, a tak je možné každý měsíc vybírat hned z několika 
plánovaných potápěčských výprav. Nevěříte? Podívejte se do sekce <a href="/akce"><strong>AKCE</strong></a> 
- tedy do nabídky toho CO SE CHYSTÁ a do <a href="/reportaze"><strong>REPORTÁŽÍ</strong></a> 
- tedy do (byť neúplného) přehledu toho, CO A JAK BYLO. Reportáže navíc doplňuje 
pestrá paleta fotografií, jež vám přiblíží mnohé z našich podvodních výprav.
</p>
<h3>Členství v klubu přináší mnoho výhod</h3>
<p>Jak již bylo zdůrazněno, samotné členství v klubu přináší výhody zejména v možnosti 
snadného předávání a získávání informací. V současné době má klub kolem stovky 
členů a tento počet garantuje přísun cenných informací a know-how, které byste 
jinde pracně získávali. Po vstupu mezi nás vám budou plně k dispozici. V klubové 
půjčovně si např. zdarma půjčíte svazky věnované navigaci pod vodou, potápění 
v noci a za snížené viditelnosti či potápění pod ledem nebo do vraků atd. 
Najdete tam i průvodce specializované na potápění v Rakousku, Španělsku, 
Chorvatsku či v Egyptě. Máte-li v oblibě vzdálenější destinace, prolistujete 
potápěčského průvodce shrnujícího více než 4000 potápěčských lokalit po celém světě. 
Z první ruky se dozvíte se o dění v potápěčském světě, neuniknou vám zajímavosti 
ani novinky v potápěčské technice, bezprostředně můžete využívat aktuální informace 
o atraktivních lokalitách, získáte snadný přístup k mapám vhodných potápěčských terénů atd., atd, atd.
</p>
<h3>Nepotápěč, potápěč nebo profesionál?</h3>
<p>Klub <strong>HASTRMAN</strong> nekastuje a je přístupný všem zájemcům bez rozdílu 
dosažené potápěčské kvalifikace. V klubu <strong>HASTRMAN</strong> rádi přivítáme 
nepotápěče, kteří se o potápění teprve zajímají, stejně tak i potápěče, kteří již 
absolvovali základní výcvik kdekoliv jinde a rádi by se seznámili s dalšími kolegy. 
Klub je otevřen i zkušeným potápěčům, kteří nejsou nikde registrováni nebo se 
rozhodnou přestoupit z jiných klubů, využít výhod ksp <strong>HASTRMAN</strong> 
přispět svými zkušenostmi. Pro členy je připraven program dalšího růstu a rozšiřování 
potápěčské kvalifikace. Za podpory Potápěčského centra <strong>HASTRMAN</strong> 
Mladá Boleslav jsou poskytovány různé výhody - jak po stránce výcvikové, tak i 
materiální (slevy při plnění lahví, nákupu nebo zapůjčení potápěčského vybavení...). 
Na rozdíl od některých jiných organizací a potápěčských klubů není <strong>HASTRMAN</strong> 
komerčním subjektem, nezískává postředky vybíráním horentních klubových příspěvků, 
tedy na úkor vlastních členů. Klubové příspěvky ksp <strong>HASTRMAN</strong> 
jsou spíše symbolické. Prioritou není komerční zájem, ale snaha usnadnit kontakt 
mezi vámi, snaha zjednodušit přístup k informacím, vzdělávání, výstroji a 
hlavně všem bez rozdílu umožnit věnovat se potápění skutečně naplno.
</p>
<p><strong>Máte-li rádi vodu neváhejte a přijďte mezi nás! Nebudete litovat!</strong>​</p>',
default, 'klub hastrman', 'Klub Hastrman', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Apnea Diving International', 'apnea-diving-international', 
'<h1>Apnea Diving International</h1><br/>
<p>Co je to <strong>apnea</strong> a <strong>freediving</strong>? Existují různá pojetí či rozdělení. 
Výcvikové agentury vesměs nabízí výcvik "freedivingu" směřující k výkonům na 
závodním poli v několika specifických soutěžních disciplínách. Apnea je veškeré 
potápění na zadržený dech, tedy i freediving. V tomto kontextu je však chápána 
spíše jako <strong>rekreační freediving</strong>.</p>
<p>Člověk nemusí mít závodnické ambice, stačí chuť poznat a naučit se vše, co potápění 
na jediný nádech nabízí. <strong>Apnea Diving International</strong> učí potápění 
na zadržený dech v několika úrovních. Přestože cílem kurzů není dosahování maximálních 
výkonů, posunete - jakoby mimochodem - své osobní limity daleko za hranice, 
které vám dosud připadaly zcela nemožné. Náš program vám pomůže osvojit si správné 
techniky nádechového potápění s důrazem na vysokou bezpečnost. Po úspěšném 
absolvování kurzu obdrží studenti certifikační kartu. Mohou se zapojit do 
aktivit jednotlivých klubů, zabývajících se nádechovým potápěním a pokračovat na 
vyšším stupni výcviku...</p>
<h3>APNEA Discovery</h3>
<p>Poznávat svět pod vodní hladinou lze s minimem nákladů a potápěčské výstroje. 
Pod dohledem instruktora nahlédnete do světa nádechového potápění a poznáte 
volnost pohybu ve stavu bez tíže. Seznámíme vás s principy nádechového potápění, 
naučíte se efektivnímu pohybu ve vodě a dozvíte se co dělat, aby vaše potápění 
bylo bezpečné. &nbsp;Pokud vás tento sport zaujme, máte otevřené dveře pro 
vstup do kvalifikačních kurzů Apnea Diver* a dalších...</p>
<p><img src="/public/uploads/images/articles/apnea/adi_pic_1.jpg" alt="Apnea Diving International" title="Apnea Diving International" /><br/>
<strong>Jednodenní kurz</strong></p>
<h3>APNEA DIVER *</h3>
<p>Apnea diver* je potápěč, který je schopen bezpečně a správně používat základní 
potápěčské vybavení v podmínkách bazénu i v chráněné vodní ploše. V uvolněném, zábavném a 
především bezpečném prostředí se studenti naučíte potápět na nádech do maximální 
hloubky 10 metrů. Dozvíte se, jak zůstat pod hladinou déle a potopit se hlouběji 
při zachování maximální bezpečnosti. Vedle účinné techniky potápění na nádech si 
osvojíte správné bezpečnostní návyky, které jsou nezbytné pro potápění v týmu. 
Potápěč je připraven absolvovat další výcvik pro potápění na otevřené vodě a v 
moři v doprovodu zkušeného potápěče.</p>
<p><img src="/public/uploads/images/articles/apnea/adi_pic_2.jpg" alt="Apnea Diving International" title="Apnea Diving International" /><br/>
<strong>Víkendový kurz</strong></p>
<h3>APNEA DIVER **</h3>
<p>Potápěč, který získal částečné zkušenosti pro potápění na volné vodě a na otevřeém 
moři. Absolvováním kurzu je připraven účastnit se ponorů na otevřené vodě a v moři 
v doprovodu zkušeného potápěče. Naučí se pokročilé techniky přípravy na ponor, 
správné dýchání a vyrovnávací techniky. V rámci týmové spolupráce si upevní správné 
bezpečnostní návyky nutné k efektivní sebezáchraně, k účinné dopomoci či záchraně partnera. 
Potápěč s dvouhvězdičkovou kvalifikací se považuje za dostatečně vyškoleného pro 
ponory do hloubky 20 metrů.</p>
<p><img src="/public/uploads/images/articles/apnea/adi_pic_3.jpg" alt="Apnea Diving International" title="Apnea Diving International" />
<img src="/public/uploads/images/articles/apnea/adi_pic_4.jpg" alt="Apnea Diving International" title="Apnea Diving International" /><br/>
<strong>2 - 3 denní kurz</strong></p>
<h3>APNEA DIVER ***</h3>
<p>Plně vyškolený potápěč, který získal značné zkušenosti pro potápění na otevřeném 
moři při různých podmínkách. Dokáže plně využít svůj akvatický potenciál a 
používá vyspělé techniky umožňující ponory pod hranici reziduálního objemu plic. 
Má dostatečné teoretické i praktické znalosti k zajištění maximální bezpečnosti 
při ponorech pod hranici středních hloubek. Potápěč s tříhvězdičkovou kvalifikací 
se považuje za dostatečně vyškoleného pro ponory do hloubky 30 metrů a více.</p>
<p><img src="/public/uploads/images/articles/apnea/adi_pic_5.jpg" alt="Apnea Diving International" title="Apnea Diving International" />
<img src="/public/uploads/images/articles/apnea/adi_pic_6.jpg" alt="Apnea Diving International" title="Apnea Diving International" /><br/>
<strong>Čtyřdenní kurz</strong></p>
<p><strong>Apnea Camp</strong><br/><br/>
<strong>Přihlášky do kurzu na adrese:</strong><a href="mailto:info@hastrman.cz">info@hastrman.cz</a></p>',
default, 'apnea diving international', 'Apnea Diving International', default, now(), default);

INSERT INTO `tb_pagecontent` VALUES(default, default, 'Apnea Tým', 'apnea-tym', 
'<p><strong>Jan Tichý - ALIEN<img alt="" src="/public/uploads/images/articles/apnea/alien.jpg" style="width: 120px; height: 208px; float: right;" /></strong><br />
Kvalifikace: <strong>Apnea Academy level 2</strong></p>

<p>Bydliště: Mladá Boleslav<br />
Tel.: 721 863 728<br />
E-mail: alien5@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>4:34 min</strong><br />
Dynamika s ploutvemi: <strong>75 m</strong><br />
Hloubka s ploutvemi: <strong>35 m</strong><br />
Free immersion: <strong>40.5 m </strong></p>

<hr />
<p><strong>Bohumír Kuhn - BOĎA<img alt="" src="/public/uploads/images/articles/apnea/boda.jpg" style="width: 120px; height: 241px; float: right;" /></strong><br />
Kvalifikace: <strong>FIT level 2, Apnea Academy level 2</strong></p>

<p>Bydliště: Mladá Boleslav<br />
Tel.: 603 161 417<br />
E-mail: kuhn@hastrman.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>5:12 min</strong><br />
Dynamika s ploutvemi: <strong>90 m</strong><br />
Hloubka s ploutvemi: <strong>38 m</strong><br />
Free immersion: <strong>32 m</strong><br />
Scooter: <strong>47 m</strong></p>

<hr />
<p><strong>Petra Smutná - Štěně<img alt="" src="/public/uploads/images/articles/apnea/stene.jpg" style="width: 76px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>zatím bez</strong></p>

<p>Bydliště: Mladá Boleslav<br />
Tel.:<br />
E-mail: peta.smutna@centrum.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>2:55 min</strong><br />
Dynamika s ploutvemi: <strong>25 m</strong><br />
Hloubka s ploutvemi: <strong>10 m</strong><br />
Free immersion: <strong>10 m</strong></p>

<hr />
<p><strong>Michal Příšovský - MIŠÁK<img alt="" src="/public/uploads/images/articles/apnea/misak.jpg" style="width: 162px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>Apnea Academy level 2</strong></p>

<p>Bydliště: Neratovice<br />
Tel.:<br />
E-mail: michalprisov@centrum.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>5:05 min</strong><br />
Dynamika s ploutvemi: <strong>100 m</strong><br />
Hloubka s ploutvemi: <strong>38 m</strong><br />
Free immersion: <strong>35 m</strong></p>

<hr />
<p><strong>Zdenka Mládková - ČENDA<img alt="" src="/public/uploads/images/articles/apnea/cenda.jpg" style="width: 192px; height: 218px; float: right;" /></strong><br />
Kvalifikace: <strong>CMAS P**</strong></p>

<p>Bydliště: Mladá Boleslav<br />
Tel.:<br />
E-mail: cendamladkova@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>5:25 min</strong><br />
Dynamika s ploutvemi: <strong>55 m</strong><br />
Hloubka s ploutvemi: <strong>18 m</strong><br />
Free immersion: <strong>31 m</strong></p>

<hr />
<p><strong>Arno Kryl - ARNY<img alt="" src="/public/uploads/images/articles/apnea/arny.jpg" style="width: 197px; height: 221px; float: right;" /></strong><br />
Kvalifikace: <strong>Hastrman snorkel diver *</strong></p>

<p>Bydliště:<br />
Tel.:<br />
E-mail: kryl.a@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>3:30 min</strong><br />
Dynamika s ploutvemi: <strong>50 m</strong><br />
Hloubka s ploutvemi: <strong>20 m</strong><br />
Free immersion: <strong>20 m</strong></p>

<hr />
<p><strong>Ivan Houžvička - IVAN<img alt="" src="/public/uploads/images/articles/apnea/ivan.jpg" style="width: 244px; height: 208px; float: right;" /></strong><br />
Kvalifikace: <strong>Hastrman snorkel diver *</strong></p>

<p>Bydliště: Praha<br />
Tel.:<br />
E-mail: ivanhou1@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>2:48 min</strong><br />
Dynamika s ploutvemi: <strong>50 m</strong><br />
Hloubka s ploutvemi: <strong>14 m</strong><br />
Free immersion:</p>

<hr />
<p><strong>Jiří Zakouřil - JIRKA<img alt="" src="/public/uploads/images/articles/apnea/jirka.jpg" style="width: 88px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>Hastrman snorkel diver *</strong></p>

<p>Bydliště: Turnov<br />
Tel.: 602 183 731<br />
E-mail: jirik.z@tiscali.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>4:16 min</strong><br />
Dynamika s ploutvemi: <strong>50 m</strong><br />
Hloubka s ploutvemi: <strong>31,5 m</strong><br />
Free immersion:</p>

<hr />
<p><strong>Vladimír Zavůrka<img alt="" src="/public/uploads/images/articles/apnea/vladimir.jpg" style="width: 180px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>Hastrman snorkel diver *</strong></p>

<p>Bydliště: Praha<br />
Tel.:<br />
E-mail: vzavurka@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>4:04 min</strong><br />
Dynamika s ploutvemi: <strong>25 m</strong><br />
Hloubka s ploutvemi: <strong>12,6 m</strong><br />
Free immersion:</p>

<hr />
<p><strong>Hana Zavůrková<img alt="" src="/public/uploads/images/articles/apnea/hana.jpg" style="width: 196px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>Hastrman snorkel diver *</strong></p>

<p>Bydliště: Praha<br />
Tel.:<br />
E-mail: hanka.channah@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>2.32 min</strong><br />
Dynamika s ploutvemi: <strong>25 m</strong><br />
Hloubka s ploutvemi: <strong>14 m</strong><br />
Free immersion:</p>

<hr />
<p><strong>Karel Veselý - Kája V<img alt="" src="/public/uploads/images/articles/apnea/karel.jpg" style="width: 176px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>Apnea Academy level 2</strong></p>

<p>Bydliště: Praha<br />
Tel.:<br />
E-mail: lu123@seznam.cz</p>

<p><strong>Výkony</strong><br />
Statika: <strong>5:06 min</strong><br />
Dynamika s ploutvemi: <strong>50 m</strong><br />
Hloubka s ploutvemi: <strong>40 m</strong><br />
Free immersion:</p>

<hr />
<p><strong>Dan Janoušek<img alt="" src="/public/uploads/images/articles/apnea/dan.jpg" style="width: 81px; height: 220px; float: right;" /></strong><br />
Kvalifikace: <strong>Hastrman snorkel diver *</strong></p>

<p>Bydliště:<br />
Tel.:<br />
E-mail:</p>

<p><strong>Výkony</strong><br />
Statika:<br />
Dynamika s ploutvemi: <strong>25 m</strong><br />
Hloubka s ploutvemi: <strong>16 m</strong><br />
Free immersion:</p>',
default, 'apnea tym hastrman', 'Apnea Tým', default, now(), default);

INSERT INTO `tb_adsection` VALUES
(default, default, 'automatiky', 'Automatiky', now(), default),
(default, default, 'dekompresni-pocitace', 'Dekompresní počítače', now(), default),
(default, default, 'foto-a-prislusenstvi', 'Foto a příslušenství', now(), default),
(default, default, 'kompresory', 'Kompresory', now(), default),
(default, default, 'kridla-a-jackety', 'Křídla a jackety', now(), default),
(default, default, 'lahve-a-prislusenstvi', 'Lahve a příslušenství', now(), default),
(default, default, 'merici-instrumenty', 'Měřicí instrumenty', now(), default),
(default, default, 'obleky', 'Obleky', now(), default),
(default, default, 'ostatni', 'Ostatní', now(), default),
(default, default, 'potapecske-doplnky', 'Potápěčské doplňky', now(), default),
(default, default, 'svetla', 'Světla', now(), default),
(default, default, 'zakladni-vystroj-abc', 'Základní výstroj ABC', now(), default);

INSERT INTO `tb_partner` VALUES(default, default, 'Vaclav Kreplik', 'http://www.vaclavkrpelik.com/', '/public/uploads/images/partners/1419274855_logo_vaclavkrpelik.jpg', 'partners', default, '2014-12-23 12:00', default),
(default, default, 'Lom Jeseny', 'http://lomjesenny.cz/', '/public/uploads/images/partners/1419274904_logo_diving_lom_jesenny.jpg', 'partners', default, '2014-12-23 12:01', default),
(default, default, 'Tauchsee Horka', 'http://tauchsee-horka.de/', '/public/uploads/images/partners/1419274943_logo_horka_animiert.gif', 'partners', default, '2014-12-23 12:02', default),
(default, default, 'Pinguin diving', 'http://www.pinguindiving.cz/', '/public/uploads/images/partners/1419274992_logo_pinguindiving.jpg', 'partners', default, '2014-12-23 12:03', default),
(default, default, 'Koktejl Ocean', 'http://www.czech-press.cz/index.php?option=com_content&view=article&id=6021&Itemid=7', '/public/uploads/images/partners/1419275111_logo_Ocean.jpg', 'partners', default, '2014-12-23 12:04', default),
(default, default, 'Suex', 'http://www.suex.it/', '/public/uploads/images/partners/1419275128_logo_suex.jpg', 'partners', default, '2014-12-23 12:05', default),
(default, default, 'Rumchalpa', 'http://www.rumchalpa.cz/', '/public/uploads/images/partners/1419275151_logo_rumchalpa.jpg', 'partners', default, '2014-12-23 12:06', default),
(default, default, 'Buddymag', 'http://www.buddymag.cz/', '/public/uploads/images/partners/1419275177_logo_buddymag.jpg', 'partners', default, '2014-12-23 12:07', default);

INSERT INTO `tb_action` (`id`, `userId`, `active`, `approved`, `archive`, `urlKey`, `userAlias`, `title`, `shortBody`, `body`, `rank`, `startDate`, `endDate`, `startTime`, `endTime`, `keywords`, `metaTitle`, `metaDescription`, `created`, `modified`) VALUES
(1, 2, 1, 1, 0, 'jama-liberec-s-bublinkami', 'Bohumír Kuhn', 'Jáma Liberec s bublinkami', '<p><strong>Sraz v plnírně v 9:15, nebo 10:15 u vrátek k nouzovému vjezdu. </strong></p>\r\n\r\n<p>Kdo bude chtít zůstat v areálu na plavání, saunu atd. - dejte prosím co nejdřív vědět!!!</p>\r\n', '<p><strong>Sraz v plnírně v 9:15, nebo 10:15 u vrátek k nouzovému vjezdu.</strong></p>\r\n\r\n<p>Kdo bude chtít zůstat v areálu na plavání, saunu atd. - dejte prosím co nejdřív vědět!!!</p>\r\n\r\n<p><strong>Účast:</strong> Boďa, Jirka + 2, Pepa K.?, ...</p>\r\n\r\n<p><img alt="" src="http://hastrman.dev/public/uploads/images/articles/akce/jama_05102013_8_5c252fa039e33b88719b657334ef5832.jpg" style="width: 500px; height: 375px;" /></p>\r\n', 1, '2015-01-18', '2015-01-18', '10:15', '12:00', 'jama liberec', 'Jáma Liberec s bublinkami', 'Liberec: Jáma pro bublinkáře', '2015-01-17 11:42:48', '2015-01-17 11:56:35'),
(2, 2, 1, 1, 0, 'salzkammer', 'Bohumír Kuhn', 'Salzkammer', '<p>Jezera solné komory...<br />\r\nmají v zimě skvělou viditelnost... pojeďme to zkontrolovat :-)</p>\r\n\r\n<p><strong>Místo:</strong> Utterach am Attersee</p>\r\n', '<p>Jezera solné komory...<br />\r\nmají v zimě skvělou viditelnost... pojeďme to zkontrolovat :-)</p>\r\n\r\n<p><strong>Místo:</strong> Utterach am Attersee<br />\r\n<strong>Datum:</strong> víkend 24.- 25.01.2015<br />\r\n<strong>Ubytování:</strong> v apartmánu<br />\r\n<strong>Potápění:</strong> pokud možno nasucho...<br />\r\n<strong>Logistika:</strong> domluvíme během svátků podle účasti.<br />\r\n<strong>Účast:</strong> Boďa, Jarda V.,David K., Patrik H., Honza P., ...</p>\r\n', 1, '2015-01-24', '2015-01-25', '', '', 'salzkammer', 'Salzkammer', 'Jezera solné komory...\r\nmají v zimě skvělou viditelnost... pojeďme to zkontrolovat :-)', '2015-01-17 11:45:33', '2015-01-17 11:46:11'),
(3, 2, 1, 1, 0, 'the-best-of-thajsko', 'Bohumír Kuhn', ' The best of... Thajsko', '<p><strong>Surinské a Similanské souostroví + ostrov Koh Yao ... to nejlepší z Thajska!</strong></p>\r\n\r\n<p>Tentokrát Hastrmánek předkládá úžasnou kombinaci toho nejlepšího co tato úžasná země nabízí. Kombinace týdeního safárka Similany a další relaxační týden v resortíku na uchvatném ostrově Koh Yao.</p>\r\n', '<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/akce/thajsko_1_5c252fa039e33b88719b657334ef5832.jpg" style="width: 455px; height: 321px;" /></p>\r\n\r\n<p style="text-align: center;"><span style="font-size:22px;"><span style="color:#0000FF;"><strong>Surinské a Similanské souostroví + ostrov Koh Yao</strong></span></span></p>\r\n\r\n<p style="text-align: center;"><span style="font-size:22px;"><span style="color:#0000FF;"><strong>... to nejlepší z Thajska!</strong></span></span></p>\r\n\r\n<p>tentokrát Hastrmánek předkládá úžasnou kombinaci toho nejlepšího co tato úžasná země nabízí . Kombinace<strong>&nbsp;týdeního safárka Similany</strong>&nbsp;a další&nbsp;<strong>relaxační týden</strong>&nbsp;v resortíku na uchvatném ostrově Koh Yao.</p>\r\n\r\n<p style="text-align: center;"><span style="font-size:22px;"><span style="color:#FF0000;"><strong>Termín:</strong></span></span></p>\r\n\r\n<p style="text-align: center;"><span style="font-size:22px;"><span style="color:#FF0000;"><strong>2.2.2015 - 16.2.2015</strong></span></span></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p><span style="background-color: rgb(136, 136, 136);"><strong><span style="background-color: rgb(255, 255, 0);">Cesta</span></strong></span>: abychom co nejvíce předešli cestovním útrapám, ztrátám &nbsp;energie, času a nálady, zvolíme nejlepší variantu cestování a to&nbsp;<strong>přímo do Thajského Phuketu</strong>&nbsp;.</p>\r\n\r\n<p><span style="background-color: rgb(0, 255, 255);"><strong>Odlet Praha</strong></span>&nbsp;:&nbsp;&nbsp;<span style="background-color: rgb(255, 255, 0);"><strong>&nbsp;2.2.2015 v 07,00 hodin</strong></span></p>\r\n\r\n<p><span style="background-color: rgb(0, 255, 255);"><strong>Přílet Phuket</strong>&nbsp;</span>:&nbsp;<span style="background-color: rgb(255, 255, 0);"><strong>3.2.2015 v 11,35 hodin</strong></span></p>\r\n\r\n<p>Po příletu na Phuket krátký transfer do přístavu Koh Lamu Pier (cca. 70km)... a safárko může začít. (20,00 hodin)</p>\r\n\r\n<p><span style="background-color: rgb(255, 255, 0);"><strong>Safárko</strong></span>&nbsp;: jedná se o týdenní pobyt na lodi se kterou navštívíme&nbsp;<strong>Surinské a Similanské souostroví</strong>, které se nachází ve střední části Thajska cca. 80km od pevniny v Andamanském moři. Cestou navštívíme proslulé reefy např.<strong>&nbsp;Koh Tachai Pinnacle, Richelieeu Rock a Ko Bon</strong>, kde není nouze o manty , různé druhy žraloků např. leopardí , bělocípí , černocípí , liščí a největší z nich žralok velrybí. Obrovská hejna různých rybek , rejnoci a makro život. Vodička 28 - 30&deg;C teplá s viditelností 25 m je standart.&nbsp; Plná penze, nealko nápoje, zápůjčka zátěže a hahve 12L. budou v ceně safárka. Celé safárko po týdnu ukončíme na překrásném&nbsp;<strong>ostrově Koh Yao</strong>.</p>\r\n\r\n<p><span style="background-color: rgb(255, 255, 0);"><strong>Koh Yao</strong></span>&nbsp;: týdenní&nbsp; relaxační pobyt na resortíku, kde budeme mít zaplaceny&nbsp;<strong>čtyři ponory v oblasti Phi Phi .&nbsp;</strong>Navštívíme proslulé překrásné pláže (např. pláž ,,Maya&quot; z amerického oscarového filmu ,,PLÁŽ&quot; s Leonardem di Capriem v hlavní roli) a plážičky s úžasným šnorchlováním, budeme obdivovat skalní útvary (homole) a koupat se v jejich těsné blízkosti = prostě&nbsp;<strong>pohoda a relax</strong>.&nbsp;</p>\r\n\r\n<p><span style="background-color: rgb(0, 255, 255);"><strong>Odlet Phuket</strong></span>&nbsp;:&nbsp;<span style="background-color: rgb(255, 255, 0);"><strong>15.2.2015 ve 12,30 hodin</strong></span></p>\r\n\r\n<p style="margin-top: 0px; margin-bottom: 5px; font-family: Helvetica, Arial, sans-serif; font-size: 12px; line-height: 15.6000003814697px;"><span style="background-color: rgb(0, 255, 255);"><strong>Přílet Praha</strong></span>&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;<span style="background-color: rgb(255, 255, 0);"><strong>16.2.2015 v 10,55 hodi</strong></span></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Poslední aktualizace : 5.1.2015</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p><strong>Účast</strong><strong>Jaromír, Boďa + Jitka, Lenka D., Jarda + Hanka N.,&nbsp; David V. + Markéta V. , David D. + Pavla D. ,&nbsp; Pružný J., David + Sylva K., Břéťa Z. Ivan L. , Markéta a Petr K., Vláďa B.</strong></p>\r\n\r\n<p><strong><span style="background-color: rgb(255, 255, 0);"><span style="color: rgb(255, 0, 0);">UZAVŘENO !</span></span></strong></p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/akce/thajsko_2_5c252fa039e33b88719b657334ef5832.jpg" style="width: 479px; height: 169px;" /></p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/akce/thajsko_3_5c252fa039e33b88719b657334ef5832.jpg" style="width: 353px; height: 500px;" /></p>\r\n', 1, '2015-02-02', '2015-02-16', '', '', 'surinske similanske souostrovi thajsko', ' The best of... Thajsko', 'Surinské a Similanské souostroví + ostrov Koh Yao... to nejlepší z Thajska!\r\nTentokrát Hastrmánek předkládá úžasnou kombinaci toho nejlepšího co tato úžasná země nabízí. Kombinace týdeního safárka Similany a další relaxační týden v resortíku na uchvatném ostrově Koh Yao.', '2015-01-17 11:56:04', '2015-01-17 11:56:45'),
(4, 2, 1, 1, 0, 'marina', 'Bohumír Kuhn', 'Marína', '<p>Po dlouhém zimním období bude potřeba řádně zasolit automatiky a veškerý materiál v relativně teplé vodičce na Jadranu.</p>\r\n', '<p>Ubytování : bude upřesněno</p>\r\n\r\n<p>Po dlouhém zimním období bude potřeba řádně zasolit automatiky a veškerý materiál v relativně teplé vodičce na Jadranu.</p>\r\n\r\n<p>Účast : Bodimír, Jaromír ......... ???</p>\r\n', 1, '2015-04-30', '2015-05-03', '', '', 'marina jadran', 'Marína', '', '2015-01-25 11:18:24', '2015-01-25 11:20:59'),
(5, 2, 1, 1, 0, 'dahabek-jak-vymalovany', 'Bohumír Kuhn', 'Dahábek jak vymalovaný', '<p>Již tradiční jarní výprava do Akabského zálivu Rudého moře. Teplá a křišťálová vodička, parádní ubytování na Planetě, suprové služby potápěčské báze Planetdiver a hlavně plno nádherných zážitků s partou úžasných lidí.</p>\r\n', '<p>Již tradiční jarní výprava do Akabského zálivu Rudého moře. Teplá a křišťálová vodička, parádní ubytování na Planetě, suprové služby potápěčské báze Planetdiver a hlavně plno nádherných zážitků s partou úžasných lidí.</p>\r\n\r\n<p>Tak u toho nemůžu chybět !!!</p>\r\n\r\n<p><strong>Cena:</strong> bude brzo upřesněna</p>\r\n\r\n<p><strong>Zálohy:</strong> 8.500,-Kč zašlete na účet: 2605214123/0800, jako vs. uvádějte číslo Vašeho pasu, platného min. do konce listopadu 2015. Doplatky musí být (a to je novinka) zaplaceny dva měsíce před odletem (tz. do 13. března 2015) !</p>\r\n\r\n<p><strong>Cena obsahuje:</strong> letenka tam a zpět, veškeré transfery, týden ubytování v hotelu Planetoasis se snídaní, zapůjčení volova a pot. láhve.</p>\r\n\r\n<p><strong>Cena neobsahuje:</strong> pojištění potápěče i nepotápěče (nutnost !), potápění (balíček 10. ponorů 180,-eur), stravování, případné výlety (Petra, St. Kateřina, Ras Abu Galum či Gabr el Bind atd.), zapůjčení výstroje atd.</p>\r\n\r\n<p><strong>Účast:</strong> Bodimír, Jaromír, Jirka + Alena C, Rudánek + Jana, Zdena + Hanka Z.,.,.......kdo další ???</p>\r\n', 1, '2015-05-13', '2015-05-20', '', '', 'dahab', 'Dahábek jak vymalovaný', '', '2015-01-25 11:20:48', '2015-01-25 11:21:50');

INSERT INTO `tb_news` (`id`, `userId`, `active`, `approved`, `archive`, `urlKey`, `userAlias`, `title`, `shortBody`, `body`, `rank`, `keywords`, `metaTitle`, `metaDescription`, `created`, `modified`) VALUES
(1, 2, 1, 1, 0, 'pf-2015', 'Bohumír Kuhn', 'PF 2015', '<p>Klidné Vánoce, hodně zdraví, šťastný nový rok a nevšední zážitky v novém roce všem Hastrmanům přejí...</p>\r\n', '<p><img alt="" src="http://hastrman.dev/public/uploads/images/articles/novinky/pf_2015.jpg" style="width: 900px; height: 675px;" /></p>\r\n', 1, 'pf', 'PF 2015', 'Klidné Vánoce, hodně zdraví, šťastný nový rok a nevšední zážitky v novém roce všem Hastrmanům přejí...', '2014-12-24 09:57:26', '2014-12-24 09:57:44');

INSERT INTO `tb_report` (`id`, `userId`, `active`, `approved`, `archive`, `urlKey`, `userAlias`, `title`, `shortBody`, `body`, `rank`, `photoName`, `imgThumb`, `imgMain`, `keywords`, `metaTitle`, `metaDescription`, `metaImage`, `created`, `modified`) VALUES
(1, 2, 1, 1, 0, 'poprve-s-barborou-diky-opalujici-se-nahe-blondynky', 'Bohumír Kuhn', 'Poprvé s Barborou - díky, opalující se nahé blondýnky', '<p>Ten starej blázen mi snad fakt uplave!!! Zabírám do ploutví ze všech sil, přesto mi Bóďa mizí v kalné vodě přede mnou, jako bych stál na místě. Lom Barbora, sobota 26. 7. 2014, hloubka 46 metrů, teplota vody cca 5&deg;C. Podívám se na vodící lano těsně pod sebou a &ndash; stojím na místě! Já opravdu stojím! Jak je to jen možný?</p>\r\n', '<p><em>Ten starej blázen mi snad fakt uplave!!! Zabírám do ploutví ze všech sil, přesto mi Bóďa mizí v kalné vodě přede mnou, jako bych stál na místě. Lom Barbora, sobota 26. 7. 2014, hloubka 46 metrů, teplota vody cca 5&deg;C. Podívám se na vodící lano těsně pod sebou a &ndash; stojím na místě! Já opravdu stojím! Jak je to jen možný? </em></p>\r\n\r\n<p>Tuhle akci na lom Barbora plánujeme již od pondělka. Na moje SOS &ndash; v Hradišti je o víkendu pouť, tak potřebuju zmizet! &ndash; reaguje Bóďa přiměřeně: ihned ruší část rodinné oslavy a posílá plánek lomu s neuvěřitelně plným polygonem atrakcí od 5 do 54 metrů. Je rozhodnuto. Ještě se osobně snažím přesvědčit dvě krásné pražské potápěčky Radku a Markétu ke spoluúčasti, ale moje kouzlo venkovského chlapce prostých poměrů a mysli na to zjevně nestačí. V sobotu ráno vyrážíme s vteřinovou přesností směr Teplice. Zapomenutá baterka nás nijak nezaskočí, bleskově otáčíme zpět na MH a o půl hodiny později již opět překračujeme hranice MB a míříme na Teplice. Cesta na radiálních pneumatikách rychle utíká a v deset vykládáme naše nádobíčko na luxusní plovoucí plošině lomu Barbora. Jsme tady první, je krásně. Brífink nad mapkami polygonu, jejich zatavenou verzi strkáme do kapes sucháčů a hurá do vody!</p>\r\n\r\n<p>Krátký signál ok a padáme podél lana do hloubky: plošina 5m - keson 10m - hrazda 23m - stan 30m. Dál už lano chybí, padáme dál a doufáme, že se nám podaří najít člun a sud v 54 metrech. Luxujeme dno, viditelnost se hodně mění, chvílemi je vidět na metr. Přesto nacházíme křížení lan a vydáváme se k dalším atrakcím.</p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/polygon%20barbora.jpg" style="width: 600px; height: 439px;" /></p>\r\n\r\n<p>Je mi jasný, že mám problém! Snažím se rukou nahmatat kde a cítím, že mě zezadu něco táhne za cívku dekošky. Baterkou signalizuju problém, Bóďa se otáčí, signalizuje ok a ukazuje směr vpřed. Moje zoufalé mávání rukama zjevně považuje za osobitý projev radosti z okolní vody. No přece tady nezůstanu! Uvědomuju si mraky bublin kolem sebe - začínám ventilovat. Hlavně klid a myslet na něco hodně příjemného. Představuju si tři nahatý, krásný a prsatý blondýnky, jak se opalují na molu. To zabírá, jako vždycky. Uklidňuju se a ze všech sil zabírám dopředu. Chytám konec Bóďovi ploutve a trhem si ho přemísťuju k sobě. Tak kamaráde, nikam! Jára Pružina má problém! Teď už Bóďa chápe, že se něco děje a snaží se mě vyprostit. Já čekám, ke třem blondýnám přidávám čtvrtou a pátou. Pohoda, jsem vyproštěn. Pomalu pokračujeme v ponoru a kromě průjmu, zaseklého ventilu inflátoru a tří křečí do pravé nohy nás už nic neruší&hellip;</p>\r\n\r\n<p>Balíme věci. Ještě krátce navštívíme základnu a školicí středisko CMASu a pak vyrážíme na Boleslav. Zajímavá zkušenost. Viditelnost nic moc, příhoda s uvíznutím, průjem, křeče, zaseklý ventil - mě se to přesto líbilo. Určitě se sem ještě vrátím. Uvědomuju si, jaké mám štěstí na parťáka, skvělého potápěče i kamaráda. V duchu si říkám, že mu za to chci nějak poděkovat. Takže teď: díky Bóďo, za tvoje nadšení pro potápění a za tvou trpělivost se mnou i s ostatními!!!</p>\r\n\r\n<p>Jarda Vaněk Pružina</p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/barbora_260714_3.jpg" style="width: 440px; height: 330px;" /><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/barbora_260714_2.jpg" style="width: 248px; height: 330px;" /></p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/barbora_260714_5.jpg" style="width: 440px; height: 330px;" /><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/barbora_260714_4.jpg" style="width: 440px; height: 330px;" /></p>\r\n', 1, 'poprve-s-barborou-diky-opalujici-se-nahe-blondynky', '/public/uploads/images/report/1421487222_poprve-s-barborou-diky-opalujici-se-nahe-blondynky_thumb.png', '/public/uploads/images/report/1421487222_poprve-s-barborou-diky-opalujici-se-nahe-blondynky.png', 'lom barbora', 'Poprvé s Barborou - díky, opalující se nahé blondýnky', 'Lom Barbora', '/public/uploads/images/report/1421487222_poprve-s-barborou-diky-opalujici-se-nahe-blondynky.png', '2014-07-28 10:33:42', '2014-07-28 11:58:09'),
(2, 2, 1, 1, 0, 'borena-hora-po-30-letech', 'Bohumír Kuhn', 'Bořená Hora po 30 letech...', '<p>Slunce na bezmračném nebi, v autě panuje pohodová atmosféra a navigace nás neomylně vede k areálu na kopci. Moje vzpomínky z poslední návštěvy lomu asi před 30 lety jsou víc než mlhavé a na chvíli zapochybuji, zda jsme skutečně na správném místě. Žádný plot ani pevná vrata tehdy vstupu nebránily. No nic - doba je jiná...</p>\r\n', '<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_0.jpg" style="width: 510px; height: 383px;" /></p>\r\n\r\n<p><em>Slunce na bezmračném nebi, v autě panuje pohodová atmosféra a navigace nás neomylně vede k areálu na kopci. Moje vzpomínky z poslední návštěvy lomu asi před 30 lety jsou víc než mlhavé a na chvíli zapochybuji, zda jsme skutečně na správném místě. Žádný plot ani pevná vrata tehdy vstupu nebránily. No nic - doba je jiná... </em></p>\r\n\r\n<p>Zaparkujeme hned za vstupními vraty a natěšeni na blížící se ponory bez váhání míříme rovnou k vodě. &quot;Halt!&quot; Zastavuje nás hlasité halekání jakési slečny: &quot;Musíte se nejdřív nahlásit a zaplatit vstup. Teprve pak se můžete podívat vodě!&quot; Vracíme se tedy a dostáváme vysvětlení, proč ta přísnost - dokud se nezaregistrujeme, jsme považováni za potenciální zloděje potápěčských cajků! Navíc jsme nepozdravili šéfa základny, který svou funkci bohužel nemá napsanou na čele, a to se zde zřejmě netrpí. Podepisujeme prohlášení, že jsme &quot;Tauchtauglich&quot;, tj. kvalifikačně i zdravotně způsobilí potápění. O naše kvalifikační karty nikdo nejeví zájem, ten se soustředí výhradně na inkaso 150 Kč za osobu a podpis stvrzující, že za všechno co se stane můžeme jen a pouze my. Hotovo! Konečně si můžeme prohlédnout, za co jsme vlastně zaplatili. Základna nabízí dobré zázemí: stoly ke kompletaci přístrojů, lavičky k pohodlnému převlékání i k odpočinku, nechybí věšáky na sušení výstroje. K dispozici je několik dobře upravených vstupů do vody, nehrozí tedy tlačenice jak je známe z německé Horky. Sociální zařízení je po ruce, možnost teplého i studeného občerstvení, krytý přístřešek pro hosty. Nabízí se i ubytování v chatkách a s tím související služby. Až na počáteční extempore vcelku příjemné zjištění...</p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_2.jpg" style="width: 510px; height: 383px;" /></p>\r\n\r\n<p>Soukáme se do sucháčů a noříme se do chladivé vody hledat po dně rozmístěné atrakce. Pohybujeme se v hloubce kolem 10 m, když nacházíme první z místních zajímavostí. - protiletadlovou střelu. O kousek dál točíme koly automobilu Mercedes Benz spočívajícího na střeše a v zápětí se vynoří obrys hlavní atrakce lomu - vrtulníku Chassi Mi-8. Proplaveme kabinou a potřeseme pravicí sedícímu kostlivci. Během tří koleček v různých hloubkách mineme kanoi, vojenské pontony a na druhé straně lomu obdivujeme nádherný, fotogenický náklaďák značky Praga. V zadní části lomu potkáváme kapry, kteří postrádají přirozenou plachost, malé jesetery, tolstolobiky a další rybí drobotinu... Na hladině má voda koupací teplotu, zato v hloubce již standardních 8 &deg;C. V pohodě zvládneme dva ponory 70 a 45 minut, max. hloubka 22 metrů při viditelnosti od 1 do 6 metrů. Pro českého hastrmana důvod k maximální spokojenosti. Shrnuto a podtrženo: parádní den!</p>\r\n\r\n<p>Boďa, Jarda a David</p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_3.jpg" style="width: 400px; height: 274px;" />&nbsp;<img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_4.jpg" style="width: 400px; height: 300px;" /></p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_1.jpg" style="width: 400px; height: 300px;" />&nbsp;<img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_5.jpg" style="width: 400px; height: 300px;" /></p>\r\n\r\n<p style="text-align: center;"><img alt="" src="http://hastrman.dev/public/uploads/images/articles/reportaze/boenka_030814_8.jpg" style="width: 367px; height: 510px;" /></p>\r\n', 1, 'borena-hora-po-30-letech', '/public/uploads/images/report/1421609036_borena-hora-po-30-letech_thumb.png', '/public/uploads/images/report/1421609036_borena-hora-po-30-letech.png', 'borena hora', 'Bořená Hora po 30 letech...', 'Bořená Hora po 30 letech...', '/public/uploads/images/report/1421609036_borena-hora-po-30-letech.png', '2014-08-05 20:23:57', '2014-05-08 20:25:31'),
(3, 2, 1, 1, 0, 'dahabek-malovany-2014', 'Bohumír Kuhn', 'Dahábek ,,malovaný" 2014', '<p><strong>Jak se řeklo, tak se stalo!?</strong></p>\r\n\r\n<p>Je ráno 7 hodin a naše početná výprava, vedená Kulichem dorazila do Dahabu. Všichni jen ,,proletí&quot; recepcí uchopí již připravené klíčky od pokojů a za 3 minuty jsme všichni ubytovaní. Každý z nás je po probdělé noci tak trošku ,,jetý&quot;, někteří jdou dospat, jiní okukujou Planetu a okolí jen Kulich se okamžitě zanořuje do křišťálové vodičky a ani nevadí, že má ,,jen&quot; 26&deg;C, je to paráda a odpoledne jdeme na to.</p>\r\n', '<p>Jak se řeklo, tak se stalo!?</p>\r\n\r\n<p>Je ráno 7 hodin a naše početná výprava, vedená Kulichem dorazila do Dahabu. Všichni jen ,,proletí&quot; recepcí uchopí již připravené klíčky od pokojů a za 3 minuty jsme všichni ubytovaní. Každý z nás je po probdělé noci tak trošku ,,jetý&quot;, někteří jdou dospat, jiní okukujou Planetu a okolí jen Kulich se okamžitě zanořuje do křišťálové vodičky a ani nevadí, že má ,,jen&quot; 26&deg;C, je to paráda a odpoledne jdeme na to.</p>\r\n\r\n<p>První ,,vyvažovák&quot; na Lighthousu, příjemné vykoupání, rybičky, oživení potápěčské dovednosti a večer hurá k ,,Šárkovi&quot; na stejka ! Únava se podepisuje a postýlka čeká.</p>\r\n\r\n<p>Ráno jak malované, větřík jen lehký, sluníčko příjemně hřeje a ranní koupel začíná být pro většinu standart a nutnost. Vše může začít a tak postupně střídáme lokality na jihu a severu . The Caves, Golden Block, Morey Garden, Southern Oasis, Canyon, Ricks Reef, Tiger Canyon, Blue Holle, Bels a velbloudí karavana na Ras Abu Gallum je parádním zážitkem. Nemůžu vynechat také chrabrost některých z nás při ,,nočáku&quot; na Lighthousu, všem se moc líbil.</p>\r\n\r\n<p>Naši divemástři Pavel, Mahmud a Nabil jsou bezchybní a velmi ochotní.</p>\r\n\r\n<p>Já a Jarda, můj pružný buddy, proháníme skůtry až se potí baterie. Užíváme si trošku jinou dimenzi potápění a obdivujeme jak jsou ti potápěči pomalí!? Také nechávám do našeho světa ,,skůtrystů&quot; nahlédnou Patrička Sokolíčka, kterému se v Dahabu mimořádně líbí (jen sám ví proč?!).</p>\r\n\r\n<p>Dny letí jak voda, plno legrace, nikdo ani nehlásí žádné střevní problémy a tak jen Rudánek stále prudí a Kulich si z něj dělá legraci. No právě proto jsme tady a určitě se sem ještě příští rok vrátíme.</p>\r\n\r\n<p>Je jedna hodina v noci, vstávat a mazat na letiště, v 9 hodin přistáváme v Praze a pohádky je konec. Jak říká můj skůtrovací buddy: ,,zajíci se počítaj až po honu&quot; a bylo jich tentokrát opravdu dost.</p>\r\n\r\n<p>Bylo mi s Vámi móóóóóc dobře a všem děkuju Jaromír a Kulich</p>\r\n\r\n<p>Foto dodáme jakmile dorazí!</p>\r\n', 1, 'dahabek-malovany-2014', '/public/uploads/images/report/1421485488_dahabek-malovany-2014_thumb.png', '/public/uploads/images/report/1421485488_dahabek-malovany-2014.png', 'dahab 2014', 'Dahábek ,,malovaný" 2014', 'Jak se řeklo, tak se stalo!?\r\n\r\nJe ráno 7 hodin a naše početná výprava, vedená Kulichem dorazila do Dahabu. Všichni jen ,,proletí" recepcí uchopí již připravené klíčky od pokojů a za 3 minuty jsme všichni ubytovaní. Každý z nás je po probdělé noci tak trošku ,,jetý", někteří jdou dospat, jiní okukujou Planetu a okolí jen Kulich se okamžitě zanořuje do křišťálové vodičky a ani nevadí, že má ,,jen" 26°C, je to paráda a odpoledne jdeme na to.', '/public/uploads/images/report/1421485488_dahabek-malovany-2014.png', '2014-10-23 10:04:48', '2014-10-23 11:58:48');


-- testing data

INSERT INTO `tb_user` VALUES(default, 'participant@test.cz', '4293ad26542583fa5b636526ab69d81e9c8039c7140314c37a5b2492a6b0e0f10f202d8d5a58838309c99bc87010b47a956db6b88ba344e9b2147335e9e02583', 
    default, '56e0c6accc2a6fc362842634f37a84993a7a103b', 'role_participant', default, default, 
    default, default, 'Test', 'Participant', default, default, now(), default);

INSERT INTO `tb_user` VALUES(default, 'member@test.cz', 'ec9865f1dab9dda39049f655f51b00bd96ca3013befcc8a4ce3d72e312f04dbdfc7d4f94981c24e34b25e2f4ff3bc4c0b11d4506475f21e72bcda11d0ca6a501', 
    default, 'ea08bb414ef8addedd090cb22a9a58c582e9fb6a', 'role_member', default, default, 
    default, default, 'Test', 'Member', default, default, now(), default);

INSERT INTO `tb_advertisement` VALUES 
    (default, 1, 1, default, 'dafdfasfe9a4sdf89', default, 'Admin', 'Prodam automatiku', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 3, default, '1546454s8sadfef89', 'demand', 'Admin', 'Koupim neco 3', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 3, default, '1546dfss8sadfef89', default, 'Admin', 'Prodam neco 3', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 3, default, '155asdfs8sadfef89', default, 'Admin', 'Prodam neco 3', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 4, default, '15464abhhgjdfh289', default, 'Admin', 'Prodam neco 4', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 5, default, '15df4fs5d15sdf11f', 'demand', 'Admin', 'Koupim neco 5', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 5, default, '15sd145df15sdf11f', default, 'Admin', 'Prodam neco 5', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 6, default, '154654f61fdf315df', 'demand', 'Admin', 'Koupim neco 6', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 6, default, 'asdfsadf1fdf315df', 'demand', 'Admin', 'Koupim neco 6', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 1, default, '1546454s89a4sdf89', default, 'Admin', 'Prodam automatiku', 'bla bla lba bla', 15.6, '2015-12-12', 'automatika', default, now(), default),
    (default, 1, 2, default, 'sd4f6s4df65a464sa', default, 'Participant', 'Prodam něco', 'bla bla lba bla', 125.0, '2015-12-12', 'neco', default, now(), default),
    (default, 2, 1, default, '65a4sdf64asd65f46', 'demand', 'Kuhn', 'Koupim automatiku', 'bla bla lba bla', 355, '2015-12-12', 'automatika', 1, now(), default);

INSERT INTO `tb_admessage` VALUES 
(default, 1, 'Pepa z Depa', 'bla@bla.com', 'Bla bla chci', default, default, now(), default),
(default, 1, 'Bla von bla', 'von@asdf.com', 'Bla bla', default, default, now(), default),
(default, 2, 'Bla von bla', 'von@asdf.com', 'Bla neco nechci', default, default, now(), default);

INSERT INTO `tb_gallery` VALUES (default, 1, default, default, 'bla', 'Admin', 'Fotky z Bla', 'Už mě to nebaví psát', default, default, now(), default);
INSERT INTO `tb_gallery` VALUES (default, 2, default, default, 'super-bla', 'User', 'Blahamy 2014', 'Nestručný popis', default, default, now(), default);