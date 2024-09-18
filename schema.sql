CREATE TABLE `cddastorybrowser`.`categories` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;

CREATE TABLE `cddastorybrowser`.`stories` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `category` INT UNSIGNED NOT NULL , `story` TEXT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;

ALTER TABLE `stories` ADD CONSTRAINT `stories_to_categories` FOREIGN KEY (`category`) REFERENCES `categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `categories` ADD UNIQUE(`name`);

ALTER TABLE `stories` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `categories` ADD `descriptor` TINYINT(1) NOT NULL DEFAULT '0' AFTER `name`, ADD INDEX `categoriesDescriptorIdx` (`descriptor`);

CREATE TABLE `cddastorybrowser`.`styles` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;

INSERT INTO `styles` (`id`, `name`) VALUES ('1', 'graffiti'), ('2', 'news'), ('3', 'postit'), ('4', 'note');

CREATE TABLE `cddastorybrowser`.`categoriesStyles` ( `categoriesId` INT UNSIGNED NOT NULL , `stylesId` INT UNSIGNED NOT NULL ) ENGINE = InnoDB;

ALTER TABLE `categoriesStyles` ADD CONSTRAINT `categoriesStyles_to_categories` FOREIGN KEY (`categoriesId`) REFERENCES `categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE; ALTER TABLE `categoriesStyles` ADD CONSTRAINT `categoriesStyles_to_styles` FOREIGN KEY (`stylesId`) REFERENCES `styles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `cddastorybrowser`.`categoriesStyles` ADD UNIQUE `categoriesStylesUniqueIdx` (`categoriesId`, `stylesId`);