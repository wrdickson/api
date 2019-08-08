<?php
Class Sales_Item {
  public $id;
  public $sales_group;
  public $sales_group_order;
  public $sales_item_title;
  public $is_fixed_price;
  public $price;
  public $tax_type;

  public function __construct($id){
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("SELECT * FROM sales_items WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->sales_group = $obj->sales_group;
      $this->sales_group_order = $obj->sales_group_order;
      $this->sales_item_title = $obj->sales_item_title;
      $this->is_fixed_price = $obj->is_fixed_price;
      $this->price = $obj->price;
      $this->tax_type = $obj->tax_type;
    };  
  }

  public static function add_sales_item( $group, $group_order, $code, $title, $is_fixed_price, $price, $tax_type){
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("INSERT INTO sales_items (sales_group, group_order, sales_item_code, sales_item_title, is_fixed_price, price, tax_type) VALUES (:gr, :gro, :sic, :ti, :ifp, :pr, :tt)");
    $stmt->bindParam(':gr', $group);
    $stmt->bindParam(':gro', $group_order);
    $stmt->bindParam(':sic', $code);
    $stmt->bindParam(':ti', $title);
    $stmt->bindParam(':ifp', $is_fixed_price);
    $stmt->bindParam(':pr', $price);
    $stmt->bindParam(':tt', $tax_type);
    $execute = $stmt->execute();
    $error_info = $stmt->errorInfo();
    $newId = $pdo->lastInsertId();
    return $execute; 
  }

  public static function get_sales_items(){
    $pdo = DataConnector::getConnection();
    //now iterate through and get the sales items
    $stmt =$pdo->prepare("SELECT sales_items.id, sales_items.sales_group, sales_item_groups.group_title, sales_items.group_order, sales_items.sales_item_code, sales_items.sales_item_title, sales_items.is_fixed_price, sales_items.price, sales_items.tax_type, tax_types.tax_title, tax_types.tax_rate FROM (( sales_items INNER JOIN sales_item_groups ON sales_items.sales_group = sales_item_groups.id ) INNER JOIN tax_types ON sales_items.tax_type = tax_types.id )");
    $stmt->execute();
    $itemsArr = array();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $itArr = array();
      $itArr['id'] = $obj->id;
      $itArr['sales_group'] = $obj->sales_group;
      $itArr['group_title'] = $obj->group_title;
      $itArr['group_order'] = $obj->group_order;
      $itArr['sales_item_code'] = $obj->sales_item_code;
      $itArr['sales_item_title'] = $obj->sales_item_title;
      $itArr['is_fixed_price'] = $obj->is_fixed_price;
      $itArr['price'] = $obj->price;
      $itArr['tax_type'] = $obj->tax_type;
      $itArr['tax_title'] = $obj->tax_title;
      $itArr['tax_rate'] = $obj->tax_rate;
      array_push($itemsArr, $itArr);
    };
    return $itemsArr;
  }

  public static function update_from_params( $id, $sales_group, $group_order, $sales_item_code, $sales_item_title, $is_fixed_price, $price, $tax_type ){
    $pdo = DataConnector::getConnection();
    $stmt = $pdo->prepare("UPDATE sales_items SET sales_group = :sg, group_order = :sgo, sales_item_code = :sic, sales_item_title = :sit, is_fixed_price = :ifp, price = :pr, tax_type = :tt WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":sg", $sales_group);
    $stmt->bindParam(":sgo", $group_order);
    $stmt->bindParam(":sic", $sales_item_code);
    $stmt->bindParam(":sit", $sales_item_title);
    $stmt->bindParam(":ifp", $is_fixed_price);
    $stmt->bindParam(":pr", $price);
    $stmt->bindParam(":tt", $tax_type);
    $execute = $stmt->execute();
    $error = $stmt->errorInfo();
    return $execute;
  }

  public function update_to_db(){
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("UPDATE `sales_items` SET `sales_group` = :gr, `sales_group_order` = :gro, `title` = :ti, `is_fixed_price` = :ifp, `price` = :pr, `tax_type` = :tt WHERE `id` = :id");
    $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
    $stmt->bindParam(':gr', $this->sales_group, PDO::PARAM_INT);
    $stmt->bindParam(':gro', $this->sales_group_order, PDO::PARAM_INT);
    $stmt->bindParam(':ti', $this->title, PDO::PARAM_STR);
    $stmt->bindParam(':ifp', $this->is_fixed_price, PDO::PARAM_INT);
    $stmt->bindParam(':pr', $this->price, PDO::PARAM_STR);
    $stmt->bindParam(':tt', $this->tax_type, PDO::PARAM_INT);
    $execute = $stmt->execute();
    $error_info = $stmt->errorInfo();
    return $execute;
  }


}