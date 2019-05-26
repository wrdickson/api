<?php

require "db.php";

function query($sql) {
  global $conn;

  $result = $conn->query($sql);
  if(mysqli_error($conn)) {
    return mysqli_error($conn);
  }
}

$rooms = array(
  "4,dorm,4,0",
  "2,pr,2,1",
  "3,pr,0,1",
  "5,pr,2,1",
  "6,pr,0,1",
  "7,pr,2,1",
  "8,pr,0,1",
  "9,pr,0,1",
  "10,cabin,1,2",
  "11,cabin,2,2",
  "12,cabin,0,1",
  "13,cabin,4,0",
  "14,cabin,0,2",
  "15,cabin,2,2",
  "16,cabin,0,2",
  "17,cabin,0,1",
  "18,lecasa,2,1",
  "19,lecasa,2,1",
  "20,lecasa,4,0",
  "21,lecasa,0,1",
  "22,lecasa,1,1",
  "23,lecasa,6,0",
  "24,special,5,0"
);

foreach($rooms as $room) {
  $items = explode(",", $room);
  $room = $items[0];
  $type = $items[1];
  $singles = $items[2];
  $doubles = $items[3];

  if($type == "pr") { $type = "private room"; }
  elseif($type == "lecasa") { $type = "le casa"; }

  $bed_counter = 1;

  if($singles > 0) {
    for($i = 1; $i <= $singles; $i++) {
      $name = ucfirst($type) . " " . $room . ", Bed " . $bed_counter;
      $query = "INSERT INTO `space` (`name`, `room`, `bed`, `bed_type`, `space_type`) VALUES ('$name', '$room', '$bed_counter', 'single', '$type')";
      query($query);

      $bed_counter++;
    }
  }

  if($doubles > 0) {
    for($i = 1; $i <= $doubles; $i++) {
      $name = ucfirst($type) . " " . $room . ", Bed " . $bed_counter;
      $query = "INSERT INTO `space` (`name`, `room`, `bed`, `bed_type`, `space_type`) VALUES ('$name', '$room', '$bed_counter', 'double', '$type')";
      query($query);

      $bed_counter++;
    }
  }
}

?>
