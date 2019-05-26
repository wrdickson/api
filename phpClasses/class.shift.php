<?php

Class Shift{
  
  private $id;
  private $user;
  private $start_date;
  private $end_date;
  
  public function __construct( $id ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = :id");
    $stmt->bindParam(":id",$id,PDO::PARAM_INT);
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->user = $obj->user;
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
    $arr['start_date'] = $this->start_date;
    $arr['end_date'] = $this->end_date;
    
  }
  
  /*
  * static methods
  */
  public static function close_shift( $shiftId, $datetime ){
    
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
      $iArr['start_date'] = $obj->start_date;
      $iArr['end_date'] = $obj->end_date;
      array_push($sArr, $iArr);
    };
    return $sArr;
  }
  
  public static function open_shift( $userId, $datetime ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("INSERT INTO shifts ( user, start_date, end_date) VALUES ( :userId, :datetime, '0000-00-00 00:00:00')");
    $stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":datetime", $datetime, PDO::PARAM_STR);
    $stmt->execute();
    return $pdo->lastInsertId();
  }

}