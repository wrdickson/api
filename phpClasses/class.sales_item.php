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

  public static function add_sales_item( $group, $group_order, $title, $is_fixed_price, $price, $tax_type){
    $pdo = DataConnector::getConnection();
    $stmt =$pdo->prepare("INSERT INTO sales_items (`sales_group`, `sales_group_order`, `title`, `is_fixed_price`, `price`, `tax_type`) VALUES (:gr, :gro, :ti, :ifp, :pr, :tt)");
    $stmt->bindParam(':gr', $group, PDO::PARAM_INT);
    $stmt->bindParam(':gro', $group_order, PDO::PARAM_INT);
    $stmt->bindParam(':ti', $title, PDO::PARAM_STR);
    $stmt->bindParam(':ifp', $is_fixed_price, PDO::PARAM_INT);
    $stmt->bindParam(':pr', $price, PDO::PARAM_STR);
    $stmt->bindParam(':tt', $tax_type, PDO::PARAM_INT);
    $execute = $stmt->execute();
    $error_info = $stmt->errorInfo();
    $newId = $pdo->lastInsertId();
    return $error_info;  
  }
}