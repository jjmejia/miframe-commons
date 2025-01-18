CREATE TABLE `person` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(200) NOT NULL,
	`short_name` VARCHAR(20) NOT NULL,
	`gender_id` INT NOT NULL DEFAULT 0,
	`nationality_id` INT NOT NULL DEFAULT 0,
	`birthdate` DATE DEFAULT NULL,
	`resume` TEXT DEFAULT NULL,
	`url` VARCHAR(100) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `short_name_unique` (`short_name`),
	INDEX `person_name` (`name`)
)
COLLATE='utf8_spanish_ci'
;

CREATE TABLE `gender` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(50) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `gender_unique` (`name`)
)
COLLATE='utf8_spanish_ci'
;

CREATE TABLE `nationality` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `nationality_unique` (`name`)
)
COLLATE='utf8_spanish_ci'
;

CREATE TABLE `tags` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `tags_unique` (`name`)
)
COLLATE='utf8_spanish_ci'
;

CREATE TABLE `person_tags` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`person_id` INT NOT NULL DEFAULT "0",
	`tags_id` INT NOT NULL DEFAULT "0",
	PRIMARY KEY (`id`),
	UNIQUE INDEX `person_tags_unique` (`person_id`,`tags_id`)
)
COLLATE='utf8_spanish_ci'
;
