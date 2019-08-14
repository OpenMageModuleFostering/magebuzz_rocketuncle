<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Rocketuncle_Adminhtml_RocketuncleController extends Mage_Adminhtml_Controller_Action {
  public function postAction() {
    $post = $this->getRequest()->getPost();
    $_helper = Mage::helper('rocketuncle');
    if (!$_helper->enableRocketUncle()) {      
      return $this;
    }

    if(isset($post['shipment_ids']) && count($post['shipment_ids'])>0){
      
      $shipmentIds = $post['shipment_ids'];
      foreach($shipmentIds as $shipmentId){
        $shipment = Mage::getModel('sales/order_shipment');
        $shipment->load($shipmentId);
        $order = $shipment->getOrder();
        $_helper->postRocketShipmentToAPI($order);
      }
    }
      
    $this->_redirect('adminhtml/sales_shipment/index')  ;
  }
	
	public function get_access_tokenAction() {	
		$scope = Mage::helper('rocketuncle')->getScope();
		$clientId = Mage::helper('rocketuncle')->getClientId();
		$secretKey = Mage::helper('rocketuncle')->getClientSecret();
		$method = "POST";
		$data = array(
			'grant_type' => 'client_credentials',
			'scope' => $scope,
			'client_id' => $clientId,
			'client_secret' => $secretKey
		);
		$responseToken = Mage::helper('rocketuncle')->getAccessToken($method, $data); 
		if (isset($responseToken['http_code']) && $responseToken['http_code'] == 200) {
			$content  = $responseToken['content'];
			$decodeData = json_decode($content);     
      $accessToken = $decodeData->access_token;
			$result = array(
				'success' => true,
				'access_token' => $accessToken
			);
		}
		else {
			$result = array(
				'success' => false
			);
		}
    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}
}