<?php
/*
* @copyright   Copyright (c) 2014 www.magebuzz.com
*/

class Magebuzz_Rocketuncle_Block_Adminhtml_Order_Grid_Renderer_Shipment_Rocketstatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
  public function render(Varien_Object $row) {
    $order = Mage::getModel('sales/order')->load($row->getOrderIncrementId(), 'increment_id');
    $rocketStatus = $order->getRocketuncleStatus() ;     
    $message = $order->getRocketuncleInformation() ; 
    $information = json_decode($message);
    if ($rocketStatus == 1) {
      $name = "<p>Success</p>";
    } else if($rocketStatus== 2) {    
      $name = "<p>Fail : ".$information->message." </p>";
    } else {
      $name = "Pending";
    }   

    return $name;
  }
}