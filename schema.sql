CREATE TABLE `cddastorybrowser`.`categories` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;

CREATE TABLE `cddastorybrowser`.`stories` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `category` INT UNSIGNED NOT NULL , `story` TEXT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;

ALTER TABLE `stories` ADD CONSTRAINT `stories_to_categories` FOREIGN KEY (`category`) REFERENCES `categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `categories` ADD UNIQUE(`name`);

ALTER TABLE `stories` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;