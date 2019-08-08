<?php
Class Sales_Group{
  private $id;
  private $group_title;
  private $group_order;

  public static function get_sales_groups(){
    $response = array();
    $pdo = dataConnector::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM sales_item_groups ORDER BY group_title ASC");
    $stmt->execute();
    $sales_groups = array();
    while( $obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $i = array();
      $i['id'] = $obj->id;
      $i['group_title'] = $obj->group_title;
      $i['group_order'] = $obj->group_order;
      array_push( $sales_groups, $i );
    }
    return $sales_groups;
  }

}