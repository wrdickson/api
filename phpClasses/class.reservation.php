<?php
Class Reservation{
  public $id;
  public $space_id;
  public $space_code;
  public $checkin;
  public $checkout;
  public $people;
  public $beds;
  public $folio;
  public $status;
  public $history;
  public $notes;
  public $customer;
  public $customer_obj;

  public function __construct($id){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id");
    $stmt->bindParam(":id",$id,PDO::PARAM_INT);
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->space_id = $obj->space_id;
      $this->space_code = $obj->space_code;
      $this->checkin = $obj->checkin;
      $this->checkout = $obj->checkout;
      $this->people = $obj->people;
      $this->beds = $obj->beds;
      $this->folio = $obj->folio;
      $this->status = $obj->status;
      $this->history = json_decode($obj->history, true);
      $this->notes = json_decode($obj->notes, true);
      $this->customer = $obj->customer;
      $iCustomer = new Customer($obj->customer);
      $this->customer_obj = $iCustomer->dumpArray();
    }
  }

  public function update( $space_id, $space_code, $checkin, $checkout, $people, $beds, $folio, $status, $history, $notes, $customer){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("UPDATE reservations SET space_id = :si, space_code = :si, checkin = :ci, checkout = :co, people = :pe, beds = :be, folio = :fo, status=:st, history = :hi, notes = :nt, customer = :cu WHERE id = :id");
    $stmt->bindParam(":si", $space_id, PDO::PARAM_INT);
    $stmt->bindParam(":sc", $space_code, PDO::PARAM_STR);
    $stmt->bindParam(":ci", $checkin, PDO::PARAM_STR);
    $stmt->bindParam(":co", $checkout, PDO::PARAM_STR);
    $stmt->bindParam(":pe", $people, PDO::PARAM_INT);
    $stmt->bindParam(":be", $beds, PDO::PARAM_INT);
    $stmt->bindParam(":fo", $folio, PDO::PARAM_INT);
    $stmt->bindParam(":st", $status, PDO::PARAM_INT);
    $stmt->bindParam(":hi", $history, PDO::PARAM_STR);
    $stmt->bindParam(":nt", $notes, PDO::PARAM_STR);
    $stmt->bindParam(":cu", $customer, PDO::PARAM_STR);
    $stmt->bindParam(":id", $this->id);
    $execute = $stmt->execute();
    $error = $stmt->errorInfo(); 
    return $execute;
  }


  public static function createReservation(){

  }

  public static function getReservation($id){
        $pdo = DataConnector::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id");
        $stmt->bindParam(":id",$id,PDO::PARAM_INT);
        $stmt->execute();
        while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
          $r = array();
          $r['id'] = $obj->id;
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
          $r['customer'] = $obj->customer;
          $iCustomer = new Customer($obj->customer);
          $r['customer_obj'] = $iCustomer->dumpArray();
        }
        return $r;
  }
}