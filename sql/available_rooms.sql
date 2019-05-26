SELECT `space`.`uid`, `space`.`room`, `space`.`space_type` FROM `space`
              INNER JOIN `reservations`
              WHERE FIND_IN_SET(`space`.`uid`, `reservations`.`space_id`) = 0
              AND `space`.`clean` = 1
