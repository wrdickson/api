<?php
Class Reservations {

public static function checkAvailabilityByDates($start, $end){
  $response = array();
  $pdo = DataConnector::getConnection();
  //first, get all reservations that conflict with those dates
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE checkin < :end AND checkout > :start");
  $stmt->bindParam(":start", $start, PDO::PARAM_STR);
  $stmt->bindParam(":end", $end, PDO::PARAM_STR);
  $stmt->execute();
  //second, get all space_id's that are booked for those dates ($rArr)
  $rArr = array();
  while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
    $tArr = explode(",", $obj->space_code);
    foreach( $tArr AS $iterate){
      array_push( $rArr, $iterate );
    }
  }
  //third, get an array of all space_id's
  $allSpaceIds = Spaces::get_all_space_ids();
  //fourth, get only those from all space_ids that are
  //NOT in the array of booked id's
  $availableSpaceIds = array_diff($allSpaceIds, $rArr);
  $response['availableSpaceIds'] = $availableSpaceIds;
  return $response;
}

public static function checkConflictsByIdDate($start, $end, $spaceId ){
    $pdo = DataConnector::getConnection();
    //works, note the comparators are "<" and ">", not "<=" and ">=" because
    //we do allow overlap in sense that one person can checkout on the same
    //day someone checks in
    //  https://stackoverflow.com/questions/325933/determine-whether-two-date-ranges-overlap
    $stmt = $pdo->prepare("SELECT * FROM `reservations` WHERE FIND_IN_SET( :spaceId, space_code ) > 0 AND ( :start < `checkout` AND :end > `checkin`  )");
    $stmt->bindParam(":start", $start, PDO::PARAM_STR);
    $stmt->bindParam(":end", $end, PDO::PARAM_STR);
    $stmt->bindParam(":spaceId", $spaceId, PDO::PARAM_INT);
    $success = $stmt->execute();
    $pdoError = $pdo->errorInfo();
    $response['success'] = $success;
    $rArr = array();
    //todo? handle the case where the  space_id doesn't exist
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $iArr = array();
        $iArr['id'] = $obj->id;
        $iArr['space_id'] = $obj->space_id;
        array_push($rArr, $iArr);
    };
    $response['hits'] = $rArr;
    //return $rArr;
    if(sizeOf($response['hits']) > 0){
        return false;
    } else {
        return true;
    };
}

}
