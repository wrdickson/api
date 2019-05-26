SELECT `uid`, `space_id` FROM `reservations` WHERE NOT (`reservations`.`checkin` > '%_date%' or `reservations`.`checkout` < '%_date%')
