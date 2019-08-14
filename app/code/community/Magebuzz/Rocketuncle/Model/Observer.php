<?php                                 
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Rocketuncle_Model_Observer {

  public function sendOrderJson(Varien_Event_Observer $observer) {

    $_helper = Mage::helper('rocketuncle');

    if (!$_helper->enableRocketUncle()) {
      return $this;
    }
    $rocketStatus = Mage::getSingleton('adminhtml/session')->getCreateDelivery();        
    $event = $observer->getEvent();
    $shipment = $event->getShipment();    
    $order = $shipment->getOrder();
    if($rocketStatus){
      $_helper->postRocketShipmentToAPI($order);
    }
    
		Mage::getSingleton('adminhtml/session')->setCreateDelivery(false);      
  }
  
  // add field Rocket information in Order Grid
  public function addFieldRocketForOrderGrid($observer){
    $block = $observer->getEvent()->getBlock();   
    $allowPendingCustomer  = Mage::helper('rocketuncle')->enableRocketUncle();
    if($allowPendingCustomer ==1){                                                                                       
      //if($block->getType() == 'adminhtml/sales_order_view_tab_shipments'){        
//        $block->addColumnAfter('rocketuncle_status', array(
//        'header'    => 'Rocket Uncle Shipment Status',
//        'type'      => 'text',
//        'width'      => '50px',
//        'index'     => 'rocketuncle_status',
//        'renderer'  => 'rocketuncle/adminhtml_order_grid_renderer_rocketstatus',               
//        'filter'    => false,
//        'sort'      => false
//        ),'grand_total');
//      }   


      if($block->getType() == 'adminhtml/sales_shipment_grid'){        

        $block->addColumnAfter('rocketuncle_status', array(
        'header'    => 'Rocket Uncle Shipment Status',
        'type'      => 'text',
        'width'      => '50px',
        'index'     => 'rocketuncle_status',
        'renderer'  => 'rocketuncle/adminhtml_order_grid_renderer_shipment_rocketstatus',               
        'filter'    => false,
        'sort'      => false
        ),'increment_id');
      }  
    }   
  }

  public function controllerPredispatchAction($observer){      
    $fullActionName = $observer->getEvent()->getControllerAction()->getFullActionName();        
    if($observer->getEvent()->getControllerAction()->getFullActionName() == 'adminhtml_sales_order_shipment_save') {
      $event = $observer->getEvent();
      $postAction = $event->getControllerAction();
      $params =  $postAction->getRequest()->getParams();
      if(isset($params['shipment']['create_delivery_rocket']) && $params['shipment']['create_delivery_rocket']==1){
        Mage::getSingleton('adminhtml/session')->setCreateDelivery(true);
      }else{
        Mage::getSingleton('adminhtml/session')->setCreateDelivery(false);
      }
    }    
  }

  public function predispatchInvoiceSaveAction($observer){      
    $fullActionName = $observer->getEvent()->getControllerAction()->getFullActionName();            
    if($observer->getEvent()->getControllerAction()->getFullActionName() == 'adminhtml_sales_order_invoice_save') {
      $event = $observer->getEvent();
      $postAction = $event->getControllerAction();
      $params =  $postAction->getRequest()->getParams();      
      if(isset($params['invoice']['do_shipment']) && $params['invoice']['do_shipment']==1){
        if(isset($params['invoice']['rocket_shipment']) && $params['invoice']['rocket_shipment']==1){
          Mage::getSingleton('adminhtml/session')->setCreateDelivery(true);
          return;
        }
      }
    } 
    Mage::getSingleton('adminhtml/session')->setCreateDelivery(false);     
  }

  public function setTemplateInvoiceTracking($observer){
    if ($observer->getBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_Create_Tracking) {
      $observer->getBlock()->setTemplate('rocketuncle/sales/order/invoice/create/tracking.phtml');
    }
  }
  public function addMassActionPostShipment($observer){
    $block = $observer->getBlock();
    if ($block instanceof Mage_Adminhtml_Block_Sales_Shipment_Grid) {

      $block->getMassactionBlock()->addItem('post_to_rocketuncle', array(
      'label'=> Mage::helper('sales')->__('Post job to Rocket Uncle'),
      'url'  => Mage::getUrl('rocketuncle/adminhtml_rocketuncle/post'),
      ));
    }
  }                                      
}
