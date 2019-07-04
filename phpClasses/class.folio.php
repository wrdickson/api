<?php
Class Folio{
  //$id, $customer, and $reservaton are from the folio table
  private $id;
  private $customer;
  private $reservation;
  //$sales is generated from the sales table
  private $sales;
  //$payments is generated from the payments table
  private $payments;
  
  public function __construct($id){
    $pdo = DataConnector::getConnection();
    //first get the basics: id, customer, reservation
    $stmt = $pdo->prepare("SELECT * FROM folios WHERE id = :id");
    $stmt->bindParam(":id",$id,PDO::PARAM_INT);
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->customer = $obj->customer;
      $this->reservation = $obj->reservation;
      
    }
    //second, get the sales (charges)
    
  }
  
  public function to_array(){
    $arr = array();
    $arr['id'] = $this->id;
    $arr['customer'] = $this->customer;
    return $arr;
  }
}