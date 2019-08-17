<?php

Class Shift{
  
  private $id;
  private $user;
  private $is_open;
  private $start_date;
  private $end_date;
  
  public function __construct( $id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->user = $obj->user;
      $this->is_open = $obj->is_open;
      $this->start_date = $obj->start_date;
      $this->end_date = $obj->end_date;
    };
  }
  
  /*
  *  Object methods:
  */
  
  
  public function to_array(){
    $arr = array();
    $arr['id'] = $this->id;
    $arr['user'] = $this->user;
    $arr['is_open'] = $this->is_open;
    $arr['start_date'] = $this->start_date;
    $arr['end_date'] = $this->end_date;
    return $arr;
  }

  public function get_payments(){
    return Payment::get_payments_by_shift_id( $this->id );
  }

  public function get_sales(){
    return Sale::get_sales_by_shift_id( $this->id );
  }
  
  /*
  * static methods
  */
  public static function check_open_shifts_by_user( $user_id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT id FROM shifts WHERE user = :u AND is_open = true");
    $stmt->bindParam(":u", $user_id);
    $execute = $stmt->execute();
    //  random array
    $results = array();
    //  each result hits the array . . . there had better only be 0 or 1 hits
    while( $obj = $stmt->fetch( PDO::FETCH_OBJ ) ){
      $shift = array();
      $shift['id'] = $obj->id;
      array_push( $results, $shift );
    };
    // if the array has an element, there is an open shift
    // it better not have more than one open elment . . . RULE: can't have 2 open shifts
    $openShiftQuantity = sizeof( $results );
    return $openShiftQuantity;
  }

  public function close_shift(){
    //use 0/1 not true/false
    $this->is_open = 0;
    $this->end_date = date('Y-m-d h:i:s');
    return $this->update_to_db();
  }

  public static function get_closed_shifts_by_user_id( $user_id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE user = :ui AND is_open = false");
    $stmt->bindParam( ":ui", $user_id );
    $stmt->execute();
    $closed_shifts = array();
    while( $obj = $stmt->fetch( PDO::FETCH_OBJ )){
      $iShift = new Shift( $obj->id );
      array_push( $closed_shifts, $iShift->to_array() );
    }
    return $closed_shifts;
    //return $stmt->errorInfo();
  }
  
  public static function get_shifts_by_user( $userId ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE user = :userId");
    $stmt->bindParam(":userId",$userId,PDO::PARAM_INT);
    $stmt->execute();
    $sArr = array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $iArr = array();
      $iArr['id'] = $obj->id;
      $iArr['user'] = $obj->user;
      $iArr['is_open'] = $obj->is_open;
      $iArr['start_date'] = $obj->start_date;
      $iArr['end_date'] = $obj->end_date;
      array_push($sArr, $iArr);
    };
    return $sArr;
  }
  
  public static function open_shift( $userId ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("INSERT INTO shifts ( user, is_open, start_date, end_date) VALUES ( :userId, true, NOW(), '0000-00-00 00:00:00')");
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $pdo->lastInsertId();
  }

  public static function getUserOpenShift( $userId ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE user = :userId AND is_open = true");
    $stmt->bindParam(":userId",$userId,PDO::PARAM_INT);
    $stmt->execute();
    $rArr = array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $iArr = array();
      $iArr['id'] = $obj->id;
      $iArr['user'] = $obj->user;
      $iArr['is_open'] = $obj->is_open;
      $iArr['start_date'] = $obj->start_date;
      $iArr['end_date'] = $obj->end_date;
      array_push($rArr, $iArr);
    };
    return $rArr;
  }

  public function reopen_shift(){
    $this->is_open = true;
    $this->end_date = "0000-00-00 00:00:00";
    return $this->update_to_db();
  }

  private function update_to_db(){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("UPDATE shifts SET is_open = :io, start_date = :sd, end_date = :ed WHERE id = :id");
    $stmt->bindParam(":io", $this->is_open);
    $stmt->bindParam(":sd", $this->start_date);
    $stmt->bindParam(":ed", $this->end_date);
    $stmt->bindParam(":id", $this->id);
    $execute = $stmt->execute();
    $error = $stmt->errorInfo();
    return $execute;
  }

}