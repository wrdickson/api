<?php
Class Payment_Type{


  public static function get_payment_types(){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM payment_types WHERE is_active = 1 ORDER BY payment_title");
    $stmt->execute();
    $payment_types = array();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ) ){
      $i = array();
      $i['id'] = $obj->id;
      $i['payment_title'] = $obj->payment_title;
      array_push( $payment_types, $i);
    }
    return $payment_types;
  }

}