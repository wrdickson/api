<?php 
Class Sale {
  private $id;
  private $sale_date;
  private $tax_type;
  //from table tax_types
  private $tax_title;
  //from table tax_types
  private $tax_rate;
  private $sales_item;
  //from table sales_items
  private $sales_item_title;
  private $net;
  private $tax;
  private $total;
  private $by;
  private $folio;
  private $shift;

  public static function loadSalesByFolioId( $folioId ){
    $response = array();
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("SELECT sales.id, sales.sale_date, sales.tax_type, tax_types.tax_title, tax_types.tax_rate, sales.sales_item, sales_items.sales_item_title,  sales.net, sales.tax, sales.total, sales.by, sales.shift FROM (( sales INNER JOIN tax_types ON sales.tax_type = tax_types.id) INNER JOIN sales_items ON sales.sales_item = sales_items.id) WHERE sales.folio = :folio_id ORDER BY sales.sale_date ASC");
    $stmt->bindParam(':folio_id', $folioId, PDO::PARAM_INT);
    $response['execute'] = $stmt->execute();
    $salesArray = array();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $iArr = array();
      $iArr['id'] = $obj->id;
      $iArr['sale_date'] = $obj->sale_date;
      $iArr['tax_type'] = $obj->tax_type;
      $iArr['tax_title'] = $obj->tax_title;
      $iArr['tax_rate'] = $obj->tax_rate;
      $iArr['sales_item'] = $obj->sales_item;
      $iArr['sales_item_title'] = $obj->sales_item_title;
      $iArr['net'] = $obj->net;
      $iArr['tax'] = $obj->tax;
      $iArr['total'] = $obj->total;
      $iArr['by'] = $obj->by;
      $iArr['shift'] = $obj->shift;
      array_push($salesArray, $iArr);
    }
    $response['sales'] = $salesArray;
    //return $response;
    return $salesArray;
  }

  public function __construct( $id ){
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("SELECT * FROM sales WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->sale_date = $obj->sale_date;
      $this->tax_type = $obj->tax_type;
      $this->tax_rate = $obj->tax_rate;
      $this->sales_item = $obj->sales_item;
      $this->net = $obj->net;
      $this->tax = $obj->tax;
      $this->total = $obj->total;
      $this->by = $obj->by;
      $this->folio = $obj->folio;
      $this->shift = $obj->shift;
    };  
  }
}