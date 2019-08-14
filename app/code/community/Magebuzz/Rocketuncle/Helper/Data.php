<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Rocketuncle_Helper_Data extends Mage_Core_Helper_Abstract {
	const ACCESS_TOKEN_URL = 'https://go.rocketuncle.com/oauth/Token';
	const CREATE_ORDER_URL = 'http://api.rocketuncle.com/api/v2/Deliveries/Create';
	
  public function getDbAccessToken() {
		$storeId = Mage::app()->getStore()->getId(); 
		return (string) Mage::getStoreConfig('rocketuncle/general/access_token', $storeId);
	}
	
	public function getClientId() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (string) Mage::getStoreConfig('rocketuncle/general/client_id', $storeId);
  }

  public function getClientSecret() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (string) Mage::getStoreConfig('rocketuncle/general/client_secret', $storeId);
  }

  public function enableRocketUncle() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (bool) Mage::getStoreConfig('rocketuncle/general/active', $storeId);
  }

  public function getScope() {
		return 'order';
    // $storeId = Mage::app()->getStore()->getId(); 
    // return (string) Mage::getStoreConfig('rocketuncle/general/scope', $storeId);
  }

  public function getService() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (string) Mage::getStoreConfig('rocketuncle/general/service', $storeId);
  }

  public function getPickupTime() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (string) Mage::getStoreConfig('rocketuncle/general/pickup_time', $storeId);
  }

  public function getWeightUnit() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (string) Mage::getStoreConfig('rocketuncle/general/weight_unit', $storeId);
  }
	
	public function isTestMode() {
    $storeId = Mage::app()->getStore()->getId(); 
    return (string) Mage::getStoreConfig('rocketuncle/general/test_mode', $storeId);
  }
	

  public function getSender() {

    $localtion = array(
    "countryCode"=>Mage::getStoreConfig('rocketuncle/sender/country_code'),
    "address"=>Mage::getStoreConfig('rocketuncle/sender/address_sender'),
    "address2"=>Mage::getStoreConfig('rocketuncle/sender/address_sender2'),
    "postalCode"=>Mage::getStoreConfig('rocketuncle/sender/postal_code'),
    );

    $senderData =  array(
    "companyName"=>Mage::getStoreConfig('rocketuncle/sender/company_name'),
    "contactName"=>Mage::getStoreConfig('rocketuncle/sender/contact_name'),
    "contactNumber"=>Mage::getStoreConfig('rocketuncle/sender/contact_number'),
    "location"=> $localtion,  
    );

    return $senderData;

  }


  public function call($method, $url, $data = null ,$accessTocken) {    
    $ch = curl_init($url);  
    $scope = Mage::helper('rocketuncle')->getScope();      
    $header = array(
	    'Content-Type: application/json',
	    'Authorization : Bearer '.$accessTocken,
	    'scope: '.$scope,
	    'grant_type: client_credentials'
    );      
    curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,   
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER =>  $header,    
    ));                                       
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);        
    return json_decode($res, true);  
  }


  function getAccessToken($method, $curl_data) {
    $options = array(
	    CURLOPT_RETURNTRANSFER => true,         
	    CURLOPT_HEADER         => false,       
	    CURLOPT_FOLLOWLOCATION => true,         
	    CURLOPT_ENCODING       => "",           
	    CURLOPT_USERAGENT      => "spider",    
	    CURLOPT_AUTOREFERER    => true,         
	    CURLOPT_CONNECTTIMEOUT => 120,          
	    CURLOPT_TIMEOUT        => 120,          
	    CURLOPT_MAXREDIRS      => 10,           
	    CURLOPT_POST            => 1,           
	    CURLOPT_POSTFIELDS     => $curl_data,   
	    CURLOPT_SSL_VERIFYHOST => 0,            
	    CURLOPT_SSL_VERIFYPEER => false,       
	    CURLOPT_VERBOSE        => 1 ,
	    CURLOPT_CUSTOMREQUEST => strtoupper($method),             
    );
		
		$accessTokenUrl = 'https://go.rocketuncle.com/oauth/Token';
		if ($this->isTestMode()) {
			$accessTokenUrl = 'https://go.rocketuncle.net/oauth/Token';
		}
		
    $ch      = curl_init($accessTokenUrl);
    curl_setopt_array($ch,$options);
    $content = curl_exec($ch);
    $err     = curl_errno($ch);
    $errmsg  = curl_error($ch) ;
    $header  = curl_getinfo($ch);    
    curl_close($ch);
    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;   
    return $header;
  }

  public function postRocketShipmentToAPI($order) {   
		Mage::log('start creating shipment', null, 'rocketuncle.log');
    $shippingAddress = $order->getShippingAddress()->getData();
    $shipName = $shippingAddress['firstname']. ' '. $shippingAddress['lastname'];
    $companyName = $shippingAddress['company'];
    if ($companyName =='') {
      $companyName = 'N/A';
    }
    $countryId = $shippingAddress['country_id'];
    $postcode = $shippingAddress['postcode'];
    $address = $shippingAddress['street']. ',' .$shippingAddress['city'].',' .$shippingAddress['region'];
    $phoneNumber = $shippingAddress['telephone'];
    $location = array (
			'countryCode' => $countryId,
			'address' => $address,
			'address2' => '',
			'postalCode' => $postcode
		);

    // receiver
    $receiver = array (
			'companyName' => $companyName,
			'contactName' => $shipName,
			'contactNumber' => $phoneNumber,
			'location' => $location
		);  
    // sender
    $sender = $this->getSender();
		
    // services
    //$service = $this->getService() ;   
		$service = $this->getService();
		
    $time =  '10:00' ;    
    $weightUnit = $this->getWeightUnit() ;    
    // comment 
    $comments = '';  
    //pickupTime  
		$date = new DateTime();
		$pickupTime = $date->format('Y-m-d') . 'T' . $this->getPickupTime() . ':00+08:00';
    // get deliverydate_date
		
		// get delivery date -- integrated with Amasty deliverydate extension
		if ($this->isModuleInstalled('Amasty_Deliverydate')) {
			$orderDate = Mage::getModel('amdeliverydate/deliverydate');
			$orderDate->load($order->getId(), 'order_id');
			if ($orderDate->getDate()) {
				$deliveryDate = $orderDate->getDate();
				$deliveryTime = $orderDate->getTime();   
				if ($deliveryTime == '14 00 - 19 00') {
					$service = 'AFTERNOON';
				}
				else {
					$service = 'MORNING';
				}
				$pickupTime = $deliveryDate . 'T' . $pickupTime . ':00+08:00';
				$comments = $orderDate->getComment();
			}
		}		

    // get weight total of order
    $weight = 0;
    $items = $order->getAllItems();
    foreach ($items as $item) {
      $weight += ($item->getWeight() * $item->getQtyOrdered());    
    }

    $dataPost = array(
			'sender' => $sender,
			'receiver' => $receiver,
			'service' => $service,
			'pickupTime' => $pickupTime,
			'parcels' => array (
				array(
					'description' => 'parcel1',
					'dimension' => array('unit' => 'cm', 'width' => 0, 'height' => 0, 'length' => 0),
					'weight'=>array("unit" => $weightUnit, "value" => $weight)
				),    
			),
			'comments' => $comments
    );
		
		if ($order->getPayment()->getMethod() == 'cashondelivery') {
			$dataPost['cod'] = array(
				'symbol' => $order->getOrderCurrencyCode(), 
				'value'	=> $order->getGrandTotal()
			);  
		}

    //$url = 'https://gofuture.rocketuncle.com/oauth/Token';
    //$url = 'https://go.rocketuncle.com/oauth/Token';              
    
		$accessToken = $this->getDbAccessToken();
		if (!$accessToken) {
			$clientId = $this->getClientId();
			$secretKey = $this->getClientSecret();
			$scope = $this->getScope(); 
			$method = "POST";
			$data = array(
				'grant_type' => 'client_credentials',
				'scope' => $scope,
				'client_id' => $clientId,
				'client_secret' => $secretKey
			);
			$responseToken = $this->getAccessToken($method, $data);
			if (isset($responseToken['http_code']) && $responseToken['http_code'] == 200) {
				$contentResponce  = $responseToken['content'];
				$decodeData = json_decode($contentResponce);     
				$accessToken = $decodeData->access_token;
			}
		}
		
    if ($accessToken) {
      $jsonData = json_encode($dataPost);  
      // send request Create Order on Rocket 
      //$urlCreateOrder = 'http://apifuture.rocketuncle.com/api/v2/Deliveries/Create';        
      $urlCreateOrder = 'http://api.rocketuncle.com/api/v2/Deliveries/Create';
			if ($this->isTestMode()) {
				$urlCreateOrder = 'http://api.rocketuncle.net/api/v2/Deliveries/Create';
			}
      $sendRequestCreateOrder = $this->call("POST", $urlCreateOrder, $jsonData, $accessToken);
			
      if (isset($sendRequestCreateOrder['status']) && $sendRequestCreateOrder['status'] == 'Success') {
        //--------------------------------
        $order->setRocketuncleStatus(1);
        $order->setRocketuncleInformation(json_encode($sendRequestCreateOrder['delivery']));         
      } else {
        $order->setRocketuncleStatus(2); 
        if (isset($sendRequestCreateOrder['error'])) {
          $error = $sendRequestCreateOrder['error'];
          $message= '';
          foreach ($error as $er) {
            $message .=$er['errorMessage'].',';
          }
          $errorMessage = array('status'=>'error', 'message'=>$message) ;
        }
        $order->setRocketuncleInformation(json_encode($errorMessage)); 
      }        
    } else {      
      $order->setRocketuncleStatus(2); 
      $errorMessage = array('status' => 'error','message' => "Cannot get access token.") ;
      $order->setRocketuncleInformation(json_encode($errorMessage));
    }

    $order->save();   
    return $this;
  }		
	
	public function getAvailableServices() {
		$url = 'http://api.rocketuncle.net/api/v2/Services';	
		$data = array();
		$data['senderLocation'] = array(
			'countryCode' => 'SG', 
			'postalCode' => '469025'
		);
		$data['receiverLocation'] = array(
			'countryCode' => 'SG', 
			'postalCode' => '610118'
		);
		
		$data['parcels'] = array(
			array(
				'description' => 'Parcel 1', 
				'weight' => array(
					'unit' => 'kg', 
					'value' => '0.14'
				)
			)			
		);	
		
		// 'parcels' => array (
				// array(
					// 'description' => 'parcel1',
					// 'dimension' => array('unit' => 'cm', 'width' => 0, 'height' => 0, 'length' => 0),
					// 'weight'=>array("unit" => $weightUnit, "value" => $weight)
				// ),    
			// )

		$jsonData = json_encode($data);
		$accessToken = $this->getDbAccessToken();

	//	echo $accessToken; die('fff');
		//$scope = $this->getScope(); 
		//$method = "POST";
		$response = $this->call("POST", $url, $jsonData, $accessToken);	
		Zend_Debug::dump($response);
		die('aaaa');
	}
	
	public function isModuleInstalled($moduleName) {
		$isActive = Mage::getConfig()->getNode('modules/' . $moduleName . '/active');
		if ($isActive && in_array((string)$isActive, array('true', '1'))) {
			return true;
		}
		return false;
	}

}