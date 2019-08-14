<?php
/*
* @copyright   Copyright (c) 2014 www.magebuzz.com
*/

class Magebuzz_Rocketuncle_Block_Adminhtml_Order_Grid_Renderer_Rocketstatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
  public function render(Varien_Object $row)
  {      

    $order = Mage::getModel('sales/order')->load($row->getId());
    $rocketStatus = $order->getRocketuncleStatus() ; 
    Zend_Debug::dump($rocketStatus)  ;die();
    $message = $order->getRocketuncleInformation() ; 
    $information = json_decode($message);
    if($rocketStatus == 1){
      $name = "<p>Success</p>";
    }else if($rocketStatus== 2){
        $name = "<p>Fail : ".$information->errorMessage." </p>";
      }else{
        $name = "Pending";
    }   

    return $name;
  }
}