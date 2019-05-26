SELECT `shift_log`.*, `users`.`name` AS `user_name` FROM `shift_log`
                                     INNER JOIN `users` ON `users`.`uid` = `shift_log`.`opened_by`
                                     WHERE `shift_log`.`uid` = '%_id%'
