<?php 
Class Sale {
  private $id;
  private $sale_date;
  private $tax_type;
  private $tax_type_title;
  private $tax_rate;
  private $sales_item;
  private $sales_item_title;
  private $net;
  private $tax;
  private $total;
  private $by;
  private $folio;
  private $shift;

  public function __construct( $id ){
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("SELECT * FROM sales WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->sale_date = $obj->sale_date;
      $this->tax_type = $obj->tax_type;
      $this->tax_type_title = $obj->tax_type_title;
      $this->tax_rate = $obj->tax_rate;
      $this->sales_item = $obj->sales_item;
      $this->sales_item_title = $obj->sales_item_title;
      $this->net = $obj->net;
      $this->tax = $obj->tax;
      $this->total = $obj->total;
      $this->by = $obj->by;
      $this->folio = $obj->folio;
      $this->shift = $obj->shift;
    };  
  }
}