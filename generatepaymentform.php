<?php

	/*
	 * This class provides a JSON response to an AJAX HTTP call requesting the payment form given the order 
	 */

	require '../common/security/HMAC.php'; 	# PEAR Crypt_HMAC
	//Personal Data
	
	
	//Product Data	
	$amount 			= strip_tags($_GET['amount']); //convert to cents
	$currency 			= strip_tags($_GET['currency']);
	$shopperLocale 		= strip_tags($_GET['shopperLocaleInput']);
	
	$shipBeforeDate 	= date("Y-m-d"		, mktime(date("H"), date("i"), date("s"), date("m"), date("j"), date("Y")+1)); //one year (Not related to specific products)
	$sessionValidity 	= date(DATE_ATOM	, mktime(date("H"), date("i"), date("s"), date("m"), date("j"), date("Y")+1)); //one year (Not related to specific products)
	//$sessionValidity 	= date(DATE_ATOM	, mktime(date("H")+1, date("i"), date("s"), date("m"), date("j"), date("Y"))); //only one hour (Not related to specific products)
	
	$skinCode 			= strip_tags($_GET['skinCodeInput']);
	$merchantAccount 	= strip_tags($_GET['merchant']);
		
	//$orderData 			=  base64_encode("Various goods and services"); //wrong
	$orderData			= ""; //no order data at this moment.
		
	
	
	//Get personal data
	$name			  = strip_tags($_GET['name']);
	$company		= strip_tags($_GET['company']);
	$country		= strip_tags($_GET['country']);
	$phone			= strip_tags($_GET['phone']);
	$email			= strip_tags($_GET['email']);
	$ip_address	= $_SERVER['REMOTE_ADDR'];
	
	//Get settings
	$display		= 'pop';
	$ajax			= 'ajax';
	
	//Get products
	$products[0]		= strip_tags($_GET['qty_0']);
	$products[1]		= strip_tags($_GET['qty_1']);
	$products[2]		= strip_tags($_GET['qty_2']);
	
	$order_id  = 0;
	if(false){
		//Post to the database
		include("../functions/dbvar.php");
	 
		//insert company?
		$db->query("insert INTO company (company_name, country) VALUES ('$company', '$country')");
		$company_id = $db->insert_id();
		
		//insert contact 
		//check if contact is known
		if(!($contact_id = $_GET['contact_id'])){
			$db->query("insert INTO contact (name, email, ip_address, phone,company_id) VALUES ('$name', '$email', '$ip_address', '$phone', '$company_id')");
			$contact_id = $db->insert_id();
		}
		
		//insert order
		$db->query("insert INTO orders (currency, contact_id) VALUES ('$currency', '$contact_id')");
		$order_id = $db->insert_id();
		
		//insert settings
		$db->query("insert INTO settings (display, ajax, order_id) VALUES ('$display', '$ajax', '$order_id')");
		$settings_id = $db->insert_id();
	
		
		//insert products
		for ($i = 0; $i < 3; $i++){
			if($products[$i] != 0){
				$subamount = $products[$i] * $price[$i];
				$db->query("insert INTO products (order_id, amount, quantity, product) VALUES ('$order_id', '$subamount', '$products[$i]', '$productNames[$i]')");
			}
		}
	}
	
	if($order_id == 0){
		$order_id = rand(0, 1000000);
	}
	
	if($skinCode == "ZKDPV1OT"){
		$order_id = "CallCenter: ".$order_id; //callcenter
	}
	
	$merchantref 		= $order_id;
	
	//Generate HMAC encrypted merchant signature
	//Instantiate a HMAC object and provide private key
	$Crypt_HMAC = new Crypt_HMAC("A Hard Day's Night", 'sha1');
	
	//the data that needs to be signed is a concatenated string of the form data (except the order data)
	$sign = $amount . $currency . $shipBeforeDate .  $merchantref . $skinCode .  $merchantAccount . $orderData . $sessionValidity;
	
	//base64 encoding is necessary because the string needs to be send over the internet and 
	//the hexadecimal result of the HMAC encryption could include escape characters
	//first get the hex string from the HMAC encryption -> convert back to binary data (and pack / zip) -> base64 encode
	$merchantsig 		=  base64_encode(pack('H*',$Crypt_HMAC->hash($sign)));
	
	//echo JSON string
	if (function_exists('json_encode')) {
  	$obj = new StdClass();
    $obj->merchantref = $merchantref;
    $obj->currency = $currency;
    $obj->amount = $amount;
    $obj->shopperLocale = $shopperLocale;
    $obj->shipBeforeDate= $shipBeforeDate;
    $obj->skinCode = $skinCode;
    $obj->merchantAccount = $merchantAccount;
    $obj->orderData = $orderData;
    $obj->sessionValidity = $sessionValidity;
    $obj->merchantsig = $merchantsig;
  
    $array[] = $obj;

    echo json_encode($array);
	
	} else {
	  // Note: We want to upgrade PHP, to enabled json_encode.
  	echo "[{ 'merchantref':'"		. addslashes(htmlentities($merchantref)) 		. "', " .
	 		 "'currency':'"			. addslashes(htmlentities($currency))	. "', " .
			 "'amount':'"			. addslashes(htmlentities(($amount)) . "', " .
			 "'shopperLocale':'"	. addslashes(htmlentities($shopperLocale)) 	. "', " .
			 "'shipBeforeDate':'"	. addslashes(htmlentities($shipBeforeDate))	. "', " .
			 "'skinCode':'"			. addslashes(htmlentities($skinCode)) 		. "', " .
			 "'merchantAccount':'"	. addslashes(htmlentities($merchantAccount)) 	. "', " .
			 "'orderData':'"		. addslashes(htmlentities($orderData))	 	. "', " .
			 "'sessionValidity':'"	. addslashes(htmlentities($sessionValidity)) 	. "', " .
			 "'merchantsig':'"		. addslashes(htmlentities($merchantsig))	 	. "' } ]";
  }
?>
