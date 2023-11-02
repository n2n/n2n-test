DROP TABLE IF EXISTS `n2n_test_dummy_object`;
CREATE TABLE `n2n_test_dummy_object` (
                                       `id` INT NOT NULL AUTO_INCREMENT,
                                       `dummy_string` VARCHAR(255) NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ;

DROP TABLE IF EXISTS `n2n_test_tbl`;
CREATE TABLE `n2n_test_tbl` (
                                         `id` INT NOT NULL AUTO_INCREMENT,
                                         `value_name` VARCHAR(255) NULL DEFAULT NULL,
                                         `value_status` VARCHAR(255) NULL DEFAULT 'initial',
                                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ;