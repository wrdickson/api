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
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE folio = :id");
    $stmt->bindParam(":id",$id,PDO::PARAM_INT);
    $sales = array();
    $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $sale = array();
      $sale['id'] = $obj->id;
      $sale['sale_date'] = $obj->sale_date;
      $sale['tax_type'] = $obj->tax_type;
      $sale['tax_type_title'] = $obj->tax_type_title;
      $sale['tax_rate'] = $obj->tax_rate;
      $sale['sales_item'] = $obj->sales_item;
      $sale['sales_item_title'] = $obj->sales_item_title;
      $sale['net'] = $obj->net;
      $sale['tax'] = $obj->tax;
      $sale['total'] = $obj->total;
      $sale['by'] = $obj->by;
      $sale['folio'] = $obj->folio;
      $sale['shift'] = $obj->shift;
      array_push($sales, $sale);
    }   
    $this->sales = $sales;
  }

  public function get_id(){
    return $this->id;
  }
  
  public function to_array(){
    $arr = array();
    $arr['id'] = $this->id;
    $arr['customer'] = $this->customer;
    $arr['reservatio'] = $this->reservation;
    return $arr;
  }
}