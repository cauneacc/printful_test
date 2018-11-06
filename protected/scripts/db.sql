CREATE TABLE `printful_test`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`));
CREATE TABLE `printful_test`.`tests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  PRIMARY KEY (`id`));
CREATE TABLE `printful_test`.`questions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `text` TEXT NULL,
  PRIMARY KEY (`id`));
CREATE TABLE `printful_test`.`available_answers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `text` TEXT NULL,
  PRIMARY KEY (`id`));
  CREATE TABLE `printful_test`.`test_question_lookup` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `test_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_tests_idx` (`test_id` ASC),
  INDEX `fk_questions_idx` (`question_id` ASC),
  CONSTRAINT `fk_tests`
    FOREIGN KEY (`test_id`)
    REFERENCES `printful_test`.`tests` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_questions`
    FOREIGN KEY (`question_id`)
    REFERENCES `printful_test`.`questions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);
CREATE TABLE `printful_test`.`question_answer_lookup` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `answer_id` INT NOT NULL,
  `correct_answer` BIT(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`),
  INDEX `fk_question_idx` (`question_id` ASC),
  INDEX `fk_answer_idx` (`answer_id` ASC),
  CONSTRAINT `fk_question`
    FOREIGN KEY (`question_id`)
    REFERENCES `printful_test`.`questions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_answer`
    FOREIGN KEY (`answer_id`)
    REFERENCES `printful_test`.`available_answers` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);
CREATE TABLE `printful_test`.`user_answer_lookup` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `answer_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `test_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_user_answer_lookup_user_idx` (`user_id` ASC),
  INDEX `fk_user_answer_lookup_answer_idx` (`answer_id` ASC),
  INDEX `fk_user_answer_lookup_question_idx` (`question_id` ASC),
  INDEX `fk_user_answer_lookup_test_idx` (`test_id` ASC),
  CONSTRAINT `fk_user_answer_lookup_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `printful_test`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_answer_lookup_answer`
    FOREIGN KEY (`answer_id`)
    REFERENCES `printful_test`.`available_answers` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_answer_lookup_question`
    FOREIGN KEY (`question_id`)
    REFERENCES `printful_test`.`questions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_user_answer_lookup_test`
    FOREIGN KEY (`test_id`)
    REFERENCES `printful_test`.`tests` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);
