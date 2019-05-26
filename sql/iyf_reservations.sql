SELECT `reservations`.*, `guests`.* FROM `reservations`
                                    INNER JOIN `guests` ON `guests`.`guest_uid` = `reservations`.`guest_id`
                                    WHERE ((`reservations`.`checkin` = '%_date%') OR (`reservations`.`status` = 1) OR (`reservations`.`checkout` = '%_date%')) AND `reservations`.`status` != 2
