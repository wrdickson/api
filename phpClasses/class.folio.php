<?php
Class Folio{
  private $id;
  private $customer;
  
  public function __construct($id){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM folios WHERE id = :id");
    $stmt->bindParam(":id",$id,PDO::PARAM_INT);
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->customer = $obj->customer;
    }
  }
  
  public function to_array(){
    $arr = array();
    $arr['id'] = $this->id;
    $arr['customer'] = $this->customer;
    return $arr;
  }
}