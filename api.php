<?php

require "live_db.php";
date_default_timezone_set("America/Denver");
//error_reporting(0);

$params = $_GET;

$pricing_summer = array(
  "dorm" => array(
    "max_size" => 1,
    array(
      12.0
    )
  ),
  "private room" => array(
    "max_size" => 5,
    array(
      32.0,
      36.0,
      42.0,
      48.0,
      54.0
    )
  ),
  "cabin" => array(
    "max_size" => 6,
    array(
      37.0,
      42.0,
      46.0,
      50.0,
      54.0,
      58.0
    )
  )
);

$pricing = $pricing_summer;
$tax_rates = array(
  "room charge" => 0.1095,
  "shower" => 0,
  "towel" => 0,
  "soap" => 0,
  "laundry" => 0,
  "shirts" => 0.1095,
  "stickers" => 0.1095,
  "camping" => 0.1095,
  "cash payment" => 0,
  "card payment" => 0,
  "deposit" => 0,
  "refund" => 0
);

function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 ||
    (substr($haystack, -$length) === $needle);
}

function query($sql) {
  global $conn;

  $result = $conn->query($sql);
  if(mysqli_error($conn)) {
    return mysqli_error($conn);
  }
  $data = array();

  if(is_bool($result)) {
    return "";
  }

  if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
      $data[] = $row;
    }
  }

  return $data;
}

function load_sql($sql_name, $args) {
  $filename = "sql/" . $sql_name . ".sql";

  $handle = fopen($filename, "r");
  $data = fread($handle, filesize($filename));
  fclose($handle);

  foreach($args as $k => $v) {
    $data = str_replace("%$k%", $v, $data);
  }

  return $data;
}

function query_sql($sql_name, $args = array()) {
  return query(load_sql($sql_name, $args));
}

function rooms_from_string($params) {
  $res_id = $params["res_id"];
  $res_info = query_sql("res_from_id", array("_id" => $res_id));

  $ids = explode(",", $res_info[0]["space_id"]);
  $data = array();
  $rooms = array();
  $seen = array();

  foreach($ids as $id) {
    $room = array("room" => "", "beds" => array());
    $room_info = query_sql("room_from_id", array("_room_id" => $id));
    $data[] = $room_info;
    if(!in_array($room_info[0]["room"], $seen)) {
      $room["room"] = $room_info[0]["room"];
      $seen[] = $room_info[0]["room"];

      $rooms[] = $room;
    }
  }

  foreach($rooms as $key => $value) {
    foreach($data as $room_data) {
      if($room_data[0]["room"] == $value["room"]) {
        $rooms[$key]["beds"][] = $room_data[0]["bed"];
      }
    }
  }

  $rooms_string = "";
  foreach($rooms as $room) {
    $rooms_string = $rooms_string . ", " . $room["room"];
  }
  $rooms["rooms_string"] = ltrim($rooms_string, ", ");

  return $rooms;
}

function room_type_beds($beds) {
  foreach(explode(",", $beds) as $bed) {
    $results = query_sql("room_type_beds", array("_bed" => $bed));

    return $results[0]["space_type"];
  }
}

function post_room_charge($beds, $people = 1, $reservation_id, $shift_id, $checkin, $checkout) {
  global $pricing;

  $checkin = new DateTime($checkin);
  $checkout = new DateTime($checkout);

  $total_stay = $checkin->diff($checkout)->format("%a");

  $room_type = room_type_beds($beds);
  $max_size = $pricing[$room_type]["max_size"];

  $max_remainder = $people - $max_size;
  if($max_remainder > 0) {
    $room_charge = $pricing[$room_type][0][$max_size - 1];
    $additional = $max_remainder * 5.0;
    $room_subtotal = $room_charge + $additional;
  } else {
    $room_charge = $pricing[$room_type][0][$people - 1];
    $room_subtotal = $room_charge;
  }

  $params = array(
    "id" => $reservation_id,
    "item_desc" => "room charge",
    "amount" => $room_subtotal * $total_stay,
    "shift_id" => $shift_id
  );

  post_res_folio($params);
}

function arrivals() {
  $res_results = query_sql("arrivals", array("_reservation_date" => date("Y-m-d")));

  return $res_results;
}

function departures() {
  $res_results = query_sql("departures", array("_reservation_date" => date("Y-m-d")));

  return $res_results;
}

function inhouse() {
  $res_results = query_sql("inhouse");

  return $res_results;
}

function available_rooms() {
  $space_ids = query_sql("available_rooms_res", array("_date" => date("Y-m-d")));
  $room_ids = query_sql("available_rooms_rooms");
  $available_room_ids = array();

  foreach($room_ids as $id) {
    $available_room_ids[] = $id["uid"];
  }

  foreach($space_ids as $space_id) {
    $split = explode(",", $space_id["space_id"]);
    foreach($split as $uid) {
      if(in_array($uid, $available_room_ids)) {
        $available_room_ids = array_diff($available_room_ids, array($uid));
      }
    }
  }

  $data = array("rooms" => array(), "groups" => array());
  $seen = array();

  foreach($available_room_ids as $room_id) {
    foreach($room_ids as $room) {
      if($room["uid"] == $room_id) {
        if(!in_array($room["room"], $seen)) {
          $data["groups"][$room["space_type"]][] = $room["room"];
          $data["rooms"][] = $room["room"];
          $seen[] = $room["room"];
        }
      }
    }
  }

  return $data;

}

function available_beds($params) {
  if(isset($params["room"])) { $rooms = $params["room"]; }

  $data = array();

  $space_ids = query_sql("available_rooms_res", array("_date" => date("Y-m-d")));
  $available_bed_ids = array();
  $rooms_data = array();

  foreach(explode(",", $rooms) as $room) {
    $room_ids = query_sql("available_beds", array("_room" => $room));

    foreach($room_ids as $id) {
      $available_bed_ids[] = $id["uid"];
      $rooms_data[] = $id;
    }
  }

  foreach($space_ids as $space_id) {
    $split = explode(",", $space_id["space_id"]);
    foreach($split as $uid) {
      if(in_array($uid, $available_bed_ids)) {
        $available_bed_ids = array_diff($available_bed_ids, array($uid));
      }
    }
  }

  $data = array();

  foreach($available_bed_ids as $bed_id) {
    foreach($rooms_data as $room) {
      if($room["uid"] == $bed_id) {
        $data[] = $room;
      }
    }
  }

  return $data;
}

function room_list($params) {
  if(isset($params["type"])) { $type = $params["type"]; }

  $rooms = available_rooms($params);
  $room_ids = implode(",", $rooms["groups"][$type]);

  $params["room"] = $room_ids;
  $beds = available_beds($params);

  return array("rooms" => $rooms["groups"][$type], "beds" => $beds);
}

function room_list_html($params) {
  $room_list = room_list($params);
  if(isset($params["check"])) { $check = $params["check"]; } else { $check = "yes"; }
  $html = array("rooms" => "", "beds" => "");

  $room_html = array("<tr><th></th><th>Room</th><th>Type</th></tr>");
  foreach($room_list["rooms"] as $room) {
    if($check == "yes") { $check_html = "<input type=\"checkbox\" name=\"room\" value=\"" . $room . "\">"; } else { $check_html = ""; }
    $item = "<tr><td>" . $check_html . "</td><td>" . $room . "</td><td>" . ucfirst($params["type"]) . "</td></tr>";
    $room_html[] = $item;
  }

  $bed_html = array("<tr><th style=\"text-align:center;\"></th><th>Bed</th><th>Type</th></tr>");
  foreach($room_list["beds"] as $bed) {
    if($check == "yes") { $check_html = "<input id=\"bed\" type=\"checkbox\" name=\"bed[]\" value=\"" . $bed["uid"] . "\" data-room=\"" . $bed["room"] . "\">"; } else { $check_html = ""; }
    $item = "<tr><td>" . $check_html . "</td><td>" . $bed["name"] . "</td><td>" . ucfirst($bed["bed_type"]) . "</td></tr>";
    $bed_html[] = $item;
  }

  $html["rooms"] = implode("\n", $room_html);
  $html["beds"] = implode("\n", $bed_html);

  return $html;
}

function clean_list() {
  return query_sql("clean_list");
}

function login($params) {
  if(isset($params["username"]) && isset($params["password"])) { $username = $params["username"]; $password = $params["password"]; }
  $login_sql = load_sql("login", array("_username" => $username, "_password" => $password));
  $login_results = query($login_sql);
  $data = array();

  foreach($login_results as $result) {
    $data[] = $result;
  }

  return $data;
}

function user_info($params) {
  if(isset($params["uid"])) { $uid = $params["uid"]; }
  $user_sql = load_sql("user_info", array("_uid" => $uid));
  $user_results = query($user_sql);

  return $user_results[0];
}

function create_reservation($params) {
  $first_name = $params["first_name"];
  $last_name = $params["last_name"];
  $check_in = date("Y-m-d", strtotime($params["check_in"]));
  $check_out = date("Y-m-d", strtotime($params["check_out"]));
  $space_id = $params["space_id"];
  $people = $params["people"];
  $beds = $params["beds"];
  $phone = $params["phone"];
  $license = $params["license"];
  $notes = $params["notes"];
  $guest_id = (isset($params["guest_id"])) ? $params["guest_id"] : "";
  $shift_id = (isset($params["shift_id"])) ? $params["shift_id"] : "";
  $cn = substr(md5(uniqid(rand(), true)), -12, 12); // 16 characters long

  if($guest_id == 0) {
    $create_guest = query_sql("create_guest", array(
      "_first_name" => $first_name,
      "_last_name" => $last_name,
      "_phone_number" => $phone,
      "_license" => $license,
    ));

    $guest_id = query("SELECT LAST_INSERT_ID()")[0]["LAST_INSERT_ID()"];
  }

  $create_sql = load_sql("create_reservation", array(
    "_check_in" => $check_in,
    "_check_out" => $check_out,
    "_space_id" => $space_id,
    "_people" => $people,
    "_beds" => $beds,
    "_cn" => $cn,
    "_guest_id" => $guest_id,
    "_notes" => $notes
  ));
  $create_response = query($create_sql);
  $response = array();

  $last_id = query("SELECT LAST_INSERT_ID()");

  if($create_response != "") {
    $response[0] = "error";
    $response[1] = $create_response;
  } else {
    $response[0] = "success";
    $response[1] = $last_id[0]["LAST_INSERT_ID()"];

    post_room_charge($space_id, $people, $last_id[0]["LAST_INSERT_ID()"], $shift_id, $check_in, $check_out);
  }

  return $response;

}

function update_reservation($params) {
  $resuid = $params["id"];
  $guest_id = $params["guest_id"];
  $reservation = reservation_from_id($params);

  $first_name = ($reservation["first_name"] != $params["first_name"]) ? $params["first_name"] : $reservation["first_name"];
  $last_name = ($reservation["last_name"] != $params["last_name"]) ? $params["last_name"] : $reservation["last_name"];
  $check_in = ($reservation["status"] != 1) ? date("Y-m-d", strtotime($params["check_in"])) : $reservation["checkin"];
  $check_out = date("Y-m-d", strtotime($params["check_out"]));
  $space_id = ($reservation["space_id"] != $params["space_id"] && $params["space_id"] != "") ? $params["space_id"] : $reservation["space_id"];
  $people = ($reservation["people"] != $params["people"]) ? $params["people"] : $reservation["people"];
  $beds = ($reservation["beds"] != $params["beds"]) ? $params["beds"] : $reservation["beds"];
  $phone = ($reservation["phone"] != $params["phone"]) ? $params["phone"] : $reservation["phone"];
  $license = ($reservation["license"] != $params["license"]) ? $params["license"] : $reservation["license"];
  $notes = ($reservation["notes"] != $params["notes"]) ? $params["notes"] : $reservation["notes"];

  $update_guest = query_sql("update_guest", array(
    "_first_name" => $first_name,
    "_last_name" => $last_name,
    "_phone" => $phone,
    "_license" => $license,
    "_guest_id" => $guest_id
  ));

  $update_sql = load_sql("update_reservation", array(
    "_check_in" => $check_in,
    "_check_out" => $check_out,
    "_space_id" => $space_id,
    "_people" => $people,
    "_beds" => $beds,
    "_notes" => $notes,
    "_resuid" => $resuid,
    "_guest_id" => $guest_id
  ));
  $update_response = query($update_sql);
  $response = array();

  if($update_response != "") {
    $response[0] = "error";
    $response[1] = $update_response;
  } else {
    $response[0] = "success";
    $response[1] = "";
  }

  return $response;
}

function delete_reservation($params) {
  $data = array();
  $data[] = query_sql("delete_reservation", array("_id" => $params["id"]));
  $data[] = query_sql("delete_folio_by_res_id", array("_id" => $params["id"]));

  return $data;
}

function reservation_from_id($params) {
  $id = $params["id"];

  return query_sql("res_from_id", array("_id" => $id))[0];
}

function checkin($params) {
  $id = $params["id"];

  $reservation = reservation_from_id($params);

  foreach(explode(",", $reservation["space_id"]) as $space_id) {
    update_room_status(array("id" => $space_id, "status" => 3));
  }

  return query_sql("checkin", array("_id" => $id));
}

function checkout($params) {
  $id = $params["id"];

  $reservation = reservation_from_id($params);

  foreach(explode(",", $reservation["space_id"]) as $space_id) {
    update_room_status(array("id" => $space_id, "status" => 0));
  }

  return query_sql("checkout", array("_id" => $id));
}

function is_checked_in($params) {
  return reservation_from_id($params)["status"] == 1;
}

function quick_stats($params) {
  $stats = array(
    "arrivals" => sizeof(arrivals()),
    "departures" => sizeof(departures()),
    "clean" => sizeof(array_filter(clean_list(), function($v) { return $v["clean"] == 1; })),
    "available" => sizeof(available_rooms()["rooms"]),
  );

  return $stats;
}

function update_room_status($params) {
  $room_id = $params["id"];
  $status = $params["status"];

  foreach(explode(",", $room_id) as $id) {
    $result = query_sql("update_room_status", array("_id" => $id, "_status" => $status));
  }

  return $result;
}

function res_folio($params) {
  $res_id = $params["id"];

  $results = query_sql("res_folio", array("_id" => $res_id));
  $data = array("items" => array(), "total" => 0, "tax_total" => 0);

  foreach($results as $result) {
    $data["items"][] = $result;
    $data["total"] += $result["amount"];
    $data["tax_total"] += $result["tax"];
  }

  $data["total"] += $data["tax_total"];

  return $data;
}

function house_folio($params) {
  $shift_id = $params["shift_id"];

  $results = query_sql("house_folio", array("_id" => $shift_id));
  $data = array("items" => $results);
  $groups = array();

  foreach($results as $item) {
    if(in_array($item["item_desc"], $groups)) { $groups[$item["item_desc"]] += abs($item["amount"]); }
    else { $groups[$item["item_desc"]] = abs($item["amount"]); }
  }

  $data["groups"] = $groups;

  return $data;
}

function post_res_folio($params) {
  global $tax_rates;

  $res_id = $params["id"];
  $folio_item = strtolower($params["item_desc"]);
  $amount = (!endsWith($folio_item, "payment") && !endsWith($folio_item, "refund") && !endsWith($folio_item, "deposit")) ? $params["amount"] : -abs($params["amount"]);
  if($params["shift_id"] == "") { return array("error" => "You must have an open shift to post to a folio."); }
  $shift_id = $params["shift_id"];
  $tax = $amount * $tax_rates[$folio_item];

  $results = query_sql("post_res_folio", array(
    "_res_id" => $res_id,
    "_item_desc" => $folio_item,
    "_amount" => $amount,
    "_tax" => $tax,
    "_desc" => "",
    "_shift_id" => $shift_id
  ));

  return array("success" => $results);
}

function post_house_folio($params) {
  global $tax_rates;

  $res_id = "master";
  $folio_item = strtolower($params["item_desc"]);
  $amount = (!endsWith($folio_item, "payment") && !endsWith($folio_item, "refund") && !endsWith($folio_item, "deposit")) ? $params["amount"] : -abs($params["amount"]);
  if($params["shift_id"] == "") { return array("error" => "You must have an open shift to post to a folio."); }
  $shift_id = $params["shift_id"];
  $tax = $amount * $tax_rates[$folio_item];

  $results = query_sql("post_res_folio", array(
    "_res_id" => $res_id,
    "_item_desc" => $folio_item,
    "_amount" => $amount,
    "_tax" => $tax,
    "_desc" => "master",
    "_shift_id" => $shift_id
  ));

  return array("success" => $results);
}

function accounting($params) {
  $date = $params["date"];
  $data = array("items" => array(), "room" => array("cabin" => 0, "dorm" => 0, "la casa" => 0, "private room" => 0), "cash" => 0, "card" => 0, "refunds" => 0);

  $folio_items = query_sql("folio_items", array("_date" => $date));
  $data["items"] = $folio_items;

  foreach($folio_items as $item) {
    if($item["item_desc"] == "room charge") {
      if(!array_key_exists($item["description"], $data["room"])) { $data["room"][$item["description"]] = 0; }
      $data["room"][$item["description"]] += $item["amount"];
    } elseif($item["item_desc"] == "cash payment") {
      $data["cash"] += abs($item["amount"]);
    } elseif($item["item_desc"] == "card payment") {
      $data["card"] += abs($item["amount"]);
    }
  }

  return $data;
}

function current_shift($params) {
  $last_shift = query_sql("last_shift");

  if($last_shift[0]["end_time"] <= 0) {
    return array($last_shift[0]["uid"]);
  } else {
    return "";
  }
}

function open_shift($params) {
  $open_response = query_sql("start_shift", array("_start_amount" => $params["start_amount"], "_user_id" => $params["user_id"]));
  return array(query("SELECT LAST_INSERT_ID()")[0]["LAST_INSERT_ID()"]);
}

function close_shift($params) {
  $close_response = query_sql("end_shift", array("_id" => $params["id"], "_end_amount" => $params["end_amount"]));
  return $close_response;
}

function shift_summary($params) {
  $shift_id = $params["id"];

  $shift_items = query_sql("shift_items", array("_id" => $shift_id));
  $shift_info = query_sql("shift_info", array("_id" => $shift_id));
  $groups = array();

  foreach($shift_items as $item) {
    if(in_array($item["item_desc"], $groups)) { $groups[$item["item_desc"]] += abs($item["amount"]); }
    else { $groups[$item["item_desc"]] = abs($item["amount"]); }
  }

  return array("groups" => $groups, "items" => $shift_items, "info" => $shift_info);
}


function _log($params) {
  $user_id = $params["user_id"];
  $message = $params["message"];

  return query_sql("log", array("_user_id" => $user_id, "_message" => $message));
}

function future_availability($params) {
  $from = $params["from"];
  $to = $params["to"];

  $room_list = query_sql("room_list");
  $reservations = query_sql("future_reservations", array("_from" => $from, "_to" => $to));

  $data = array();

  foreach($room_list as $room) {
    if(!in_array($room["uid"], $data)) { $data[$room["uid"]] = array("info" => $room, "reservations" => array()); }
  }

  foreach($reservations as $reservation) {
    $spaces = explode(",", $reservation["space_id"]);
    foreach($spaces as $space_id) {
      foreach($room_list as $room) {
        if($space_id == $room["uid"]) {
          $data[$room["uid"]]["reservations"][] = $reservation;
        }
      }
    }
  }

  return $data;
}

function iyf_data($params) {
  $date = $params["date"];

  $room_list = query_sql("room_list");
  $reservations = query_sql("iyf_reservations", array("_date" => $date));

  $raw_data = array();
  $data = array();
  $seen = array();

  foreach($room_list as $room) {
    if(!in_array($room["room"], $seen)) {
      $raw_data[$room["room"]] = array("room" => $room["room"], "reservations" => array());
      $seen[] = $room["room"];
    }
  }

  foreach($reservations as $reservation) {
    $spaces = explode(",", $reservation["space_id"]);
    foreach($spaces as $space_id) {
      foreach($room_list as $room) {
        if($space_id == $room["uid"]) {
          $raw_data[$room["room"]]["reservations"][] = $reservation;
        }
      }
    }
  }

  foreach($raw_data as $item) {
    $item["reservations"] = array_unique($item["reservations"], SORT_REGULAR);
    $data[] = $item;
  }

  return $data;
}

//---------------------------------------------------------------------------------------------------------------------------------

function json_results($results) {
  header('Content-Type: application/json');
  echo json_encode($results);
}

switch($params["action"]) {
  case "quick_stats":
    json_results(quick_stats($params));
    break;
  case "create_reservation":
    json_results(create_reservation($params));
    break;
  case "update_reservation":
    json_results(update_reservation($params));
    break;
  case "delete_reservation":
    json_results(delete_reservation($params));
    break;
  case "reservation_from_id":
    json_results(reservation_from_id($params));
    break;
  case "checkin":
    json_results(checkin($params));
    break;
  case "checkout":
    json_results(checkout($params));
    break;
  case "arrivals":
    json_results(arrivals());
    break;
  case "departures":
    json_results(departures());
    break;
  case "inhouse":
    json_results(inhouse());
    break;
  case "available_rooms":
    json_results(available_rooms());
    break;
  case "available_beds":
    json_results(available_beds($params));
    break;
  case "room_list":
    json_results(room_list($params));
    break;
  case "room_list_html":
    json_results(room_list_html($params));
    break;
  case "iyf_data":
    json_results(iyf_data($params));
    break;
  case "future_availability":
    json_results(future_availability($params));
    break;
  case "rooms_from_id":
    json_results(rooms_from_string($params));
    break;
  case "room_type_beds":
    json_results(room_type_beds($params["beds"]));
    break;
  case "post_room_charge":
    json_results(post_room_charge($params["res_id"], $params["shift_id"]));
    break;
  case "clean_list":
    json_results(clean_list());
    break;
  case "update_room_status":
    json_results(update_room_status($params));
    break;
  case "res_folio":
    json_results(res_folio($params));
    break;
  case "post_res_folio":
    json_results(post_res_folio($params));
    break;
  case "post_house_folio":
    json_results(post_house_folio($params));
    break;
  case "house_folio":
    json_results(house_folio($params));
    break;
  case "accounting":
    json_results(accounting($params));
    break;
  case "current_shift":
    json_results(current_shift($params));
    break;
  case "open_shift":
    json_results(open_shift($params));
    break;
  case "end_shift":
    json_results(close_shift($params));
    break;
  case "shift_summary":
    json_results(shift_summary($params));
    break;
  case "login":
    json_results(login($params));
    break;
  case "user_info":
    json_results(user_info($params));
    break;
  case "log":
    json_results(_log($params));
    break;
}

$conn->close();

?>
