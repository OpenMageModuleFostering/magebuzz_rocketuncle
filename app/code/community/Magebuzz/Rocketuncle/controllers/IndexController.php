<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Rocketuncle_IndexController extends Mage_Core_Controller_Front_Action {  
  public function testAction() {
		$order = Mage::getModel('sales/order')->load(2876);				
		echo $order->getPayment()->getMethod();
		//Mage::helper('rocketuncle')->postRocketShipmentToAPI($order);
		die('1111');
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
    $receiver = array(
			'companyName' => $companyName,
			'contactName' => $shipName,
			'contactNumber' => $phoneNumber,
			'location' => $location
		);  
    // sender
    $sender = Mage::helper('rocketuncle')->getSender();
		
		die('xxx'); 
		
    // services
    //$service = $this->getService() ;   
		$service = 'OFFICE_HOURS_6PM';		 //hardcode for expatfoodhall.
		
    $time =  '10:00' ;    
    $weightUnit = Mage::helper('rocketuncle')->getWeightUnit() ;    
    // comment 
    $comments = '';  
    //pickupTime  
    $pickupTime = '';      
    // get deliverydate_date
    $row_saleorderid = Mage::getModel('onestepcheckout/onestepcheckout')->getCollection()
			->addFieldToFilter('sales_order_id',$order->getId());
		
    if ($row_saleorderid->getSize()) {  
      $orderonestep = $row_saleorderid->getFirstItem();              
      $deliveryDate = $orderonestep->getMwDeliverydateDate();              
      $deliveryDate = ereg_replace('/','-',$deliveryDate);
      $timestampDelivery = strtotime($deliveryDate);  
      $deliveryDate = date('Y-m-d',$timestampDelivery);           
      $deliveryTime = $orderonestep->getMwDeliverydateTime();   
			
      if ($deliveryTime !="") {
				$deliveryTime = explode('-', $deliveryTime) ;
				$time = $deliveryTime[0];
				$end_time = $deliveryTime[1];
      }
			
			if ($time == 'All' || $end_time == '18:00') {
				$service = 'OFFICE_HOURS_6PM';
			}
			else {
				$service = 'OFFICE_HOURS_2PM';
			}
			
			if ($time == 'All') {
				$time = '10:00';
			}						
			
			$time = '10:00';
      $dateTime = $deliveryDate;
      $date = new DateTime($dateTime);
      //$pickupTime = $date->format('Y-m-d  H:i:sA');         
      $pickupTime = $date->format('Y-m-d');         
			$pickupTime .= 'T10:00:00+08:00';
      $comments = $orderonestep->getMwCustomercommentInfo();    
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
		$jsonData = json_encode($dataPost);						
		
		$clientId = Mage::helper('rocketuncle')->getClientId();
    $secretKey = Mage::helper('rocketuncle')->getClientSecret();
    $scope = Mage::helper('rocketuncle')->getScope();
    $method = "POST";
    $data = array('grant_type'=>'client_credentials','scope'=>$scope,'client_id'=>$clientId,'client_secret'=>$secretKey);
    $responceToken = $this->getAccessToken($method, $url, $data);       
    if (isset($responceToken['http_code']) && $responceToken['http_code'] == 200) {
      $contentResponce  = $responceToken['content'];
      $decodeData = json_decode($contentResponce);     
      $accessToken = $decodeData->access_token;
      $jsonData = json_encode($dataPost);     

			
      // send request Create Order on Rocket 
      $urlCreateOrder = 'http://apifuture.rocketuncle.com/api/v2/Deliveries/Create';        
      $sendRequestCreateOrder = $this->call("POST",$urlCreateOrder,$jsonData,$accessToken) ;

      //if(isset($sendRequestCreateOrder['status']) && $sendRequestCreateOrder['status'] == 'Success'){
        //--------------------------------
        //$order->setRocketuncleStatus(1);
        //$order->setRocketuncleInformation(json_encode($sendRequestCreateOrder['delivery']));         
     // }else{
        $order->setRocketuncleStatus(2); 
        if(isset($sendRequestCreateOrder['error'])){
          $error = $sendRequestCreateOrder['error'];
          $message= '';
          foreach($error as $er){
            $message .=$er['errorMessage'].',';
          }
          $errorMessage = array('status'=>'error','message'=>$message) ;
        }
        $order->setRocketuncleInformation(json_encode($errorMessage)); 
     // }        
    }else{      
     // $order->setRocketuncleStatus(2); 
     // $errorMessage = array('status'=>'error','message'=>"Not connect to server") ;
     // $order->setRocketuncleInformation(json_encode($errorMessage));
    }
	}

  public function call($method, $url, $data = null ,$accessTocken) {
    //echo $accessTocken;die();
    $ch = curl_init($url);    
    $header = array(
    'Content-Type: application/json',
    // 'Accept: application/json',
    //  'Connection: close',
    'Authorization : Bearer '.$accessTocken,
    'scope: order',
    'grant_type: client_credentials'
    );      
    curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    //CURLOPT_CUSTOMREQUEST => strtoupper($method),
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER =>  $header,
    // CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl
    // CURLOPT_SSL_VERIFYPEER => false,    
    ));                                       
    $res = curl_exec($ch);

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // $content = curl_exec($ch);
    // $err     = curl_errno($ch);
    // $errmsg  = curl_error($ch) ;
    // $header  = curl_getinfo($ch);    
    // curl_close($ch);    
    // $header['errno']   = $err;
    // $header['errmsg']  = $errmsg;
    // $header['content'] = $res;
    Zend_Debug::dump($res);die();



    if ($status == 400) {   
      //throw new SiteBizApiClientException($status, json_decode($res, true));
    } elseif ($status > 400) {
      //  throw new SiteBizApiClientException($status);
    }  
    return json_decode($res, true);  
  }


  function getAccessToken($method,$url,$curl_data )
  {
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

    $ch      = curl_init($url);
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


  public function testconnectAction(){
    $url = 'https://gofuture.rocketuncle.com/oauth/Token';
    $clientId = Mage::helper('rocketuncle')->getClientId();
    $secretKey = Mage::helper('rocketuncle')->getClientSecret();
    $method = "POST";
    $data = array('grant_type'=>'client_credentials','scope'=>'order','client_id'=>$clientId,'client_secret'=>$secretKey);
    $responceToken = $this->getAccessToken($method, $url, $data);
    $accessToken  = $responceToken['content'];
    $jsonDecode = json_decode($accessToken);    
    //echo $jsonDecode->access_token; die('bbb');
    $content = $this->test($jsonDecode->access_token) ;    
  }
	
	public function servicesAction() {
		Mage::helper('rocketuncle')->getServices();
		die('ssson');
	}
}