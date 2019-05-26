SELECT `reservations`.*, `guests`.* FROM `reservations`
                                    INNER JOIN `guests` ON `guests`.`guest_uid` = `reservations`.`guest_id`
                                    WHERE `reservations`.`checkin` = '%_reservation_date%' AND `reservations`.`status` = 0
