SELECT `res`.*, `guests`.* FROM `reservations` as `res`
               INNER JOIN `guests` ON `guests`.`guest_uid` = `res`.`guest_id`
               WHERE `res`.`checkout` = '%_reservation_date%' AND `res`.`status` = 1
