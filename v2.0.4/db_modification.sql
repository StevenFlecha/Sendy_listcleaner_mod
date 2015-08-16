CREATE TABLE IF NOT EXISTS `flecha_activity_log` (
  id INT(11) NOT NULL AUTO_INCREMENT,
  subid INT(11) NOT NULL,
  ownerid INT(11) NOT NULL,
  last_clicked datetime default NULL,
  last_opened datetime default NULL,
  PRIMARY KEY (`id`),
  KEY `subid` (`subid`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;		

CREATE TABLE IF NOT EXISTS flecha_mods (
  id INT(11) NOT NULL AUTO_INCREMENT,
  modname VARCHAR(40) NOT NULL,
  install_date datetime default NOW(),
  PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO flecha_mods (modname) VALUES('flecha_list_cleaner');