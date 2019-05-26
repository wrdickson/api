<?php
Class Reservation{


public static function getReservation($id){
      $pdo = DataConnector::getConnection();
      $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id");
      $stmt->bindParam(":id",$id,PDO::PARAM_INT);
      $stmt->execute();
      while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
        $r = array();
        $r['id'] = $obj->id;
        $r['space_type'] = $obj->space_type;
        $r['space_code'] = $obj->space_code;
        $r['space_id'] = $obj->space_id;
        $r['checkin'] = $obj->checkin;
        $r['checkout'] = $obj->checkout;
        $r['people'] = $obj->people;
        $r['beds'] = $obj->beds;
        $r['folio'] = $obj->folio;
        $r['status'] = $obj->status;
        $r['history'] = json_decode($obj->history, true);
        $r['notes'] = json_decode($obj->notes, true);
        $iCustomer = new Customer($obj->customer);
        $r['customer'] = $iCustomer->dumpArray();
      }
      return $r;
}
}