<?php 
/*
The use cases for this example is 
	a situation where the shopper can decide if he wants to spend some points he has in an account.  Use function process() in the code below

	He selects the nr of points on the merchant site. This is then passed on to the HPP via orderData, together
	with some customfields ('paypoints' and 'amount').
	This code then does some checks and substracts the entered points from the orderAmount. 
	The new amount is what the shopper has to pay.

OR 
	a situation where he has to agree to terms & conditions. Use function processTC() in the code below
	A checkbox is displayed (called 'tc') which has the value 'yes'. If it is not received the response will be [invalid] along
 with some error message. If it is checked, the value received will be 'yes' and the response will be [accepted].

sample input from Adyen :
	 [request_customFields_0_name] => amount
		[request_customFields_0_value] => 70
		[request_customFields_1_name] => paypoints
		[request_merchantAccount] => SomeMerchant
		[request_merchantReference] => 2013-08-23 14:58:45
		[request_sessionFields_0_name] => skinCode
		[request_sessionFields_0_value] => gbpWBmxT
		[request_sessionFields_1_name] => countryCode
		[request_sessionFields_2_name] => paymentAmount
		[request_sessionFields_2_value] => 7000
		[request_sessionFields_3_name] => currencyCode
		[request_sessionFields_3_value] => EUR
		[request_sessionFields_4_name] => shopperEmail
		[request_sessionFields_4_value] => 83152@adyen.com
		[request_sessionFields_5_name] => shopperReference
		[request_sessionFields_5_value] => c43050993041b411f5a0f6152e0b3778

sample input from Adyen in a 'accept Terms and Conditions' scenario :
	[request_customFields_0_name] => tc
	[request_customFields_0_value] => yes
..
..

or like this :
- php://input---------
request.customFields.0.name=amount&request.customFields.0.value=70&request.customFields.1.name=paypoints&request.merchantAccount=N-Vision&request.merchantReference=2013-08-23+14%3A58%3A45&request.sessionFields.0.name=skinCode&request.sessionFields.0.value=gbpWBmxT&request.sessionFields.1.name=countryCode&request.sessionFields.2.name=paymentAmount&request.sessionFields.2.value=7000&request.sessionFields.3.name=currencyCode&request.sessionFields.3.value=EUR&request.sessionFields.4.name=shopperEmail&request.sessionFields.4.value=83152%40adyen.com&request.sessionFields.5.name=shopperReference&request.sessionFields.5.value=c43050993041b411f5a0f6152e0b3778
 
Example error response:

response.customFields.0.name=name&
response.customFields.0.value=Please+supply+a+name&
response.response=[invalid]

Example accepted response, modify amount:

response.sessionFields.0.name=paymentAmount&
response.sessionFields.0.value=2000&
response.sessionFields.1.name=currencyCode&
response.sessionFields.1.value=EUR&
response.response=[accepted]
*/


writePost("PHP");

$aFields = getFields($_REQUEST);

// select the one to use
$out = process($aFields) ;

//$out = processTC($aFields) ;


// dump input & generated fields & output 2 file 
	$fp = fopen('fields.txt', 'w');
	$date = date("H:i:s");
	ob_start();
	print_r($_REQUEST);
	print "\nfields---------\n";
	print_r($aFields);
	print "\noutput---------\n";
	print $out;
	$output = ob_get_clean();
	fprintf($fp, '%s', "DUMPFIELDS - $date\n". $output . "\nEND\n");
	
print $out; //response 2 server

exit;

/* 
execute some specific logic according to the fields and their value, modify session fields accordingly

should produce output like this :
response.sessionFields.0.name=paymentAmount&
response.sessionFields.0.value=2000&
response.sessionFields.1.name=currencyCode&
response.sessionFields.1.value=EUR&
response.response=[accepted]
*/
function process( $aFields ) { 
	if (!is_array($aFields)) exit;  //should log error

	$usepoints = false;

	if( $aFields["paypoints"] == "ja") { 
			 $usepoints = true;
	}		 
	
	elseif (empty($aFields["paypoints"])) {
		//Throw error
		$out= "response.customFields.0.name=paypoints&";
		$out.= "response.customFields.0.value=Was not able to parse paypoint value!&";
		$out.= "response.response=[invalid]";
		return $out; 
		}
	
	if (!$usepoints) {
		$out= "response.customFields.0.name=paypoints&";
		$out.= "response.customFields.0.value=NOT OK to use points&";
		$out.= "response.response=[invalid]";
		return $out; 
	}	

	/* Validation Error Example */
	if($aFields['amount'] < 1) {
		$out= "response.customFields.0.name=amount&";
		$out.= "response.customFields.0.value=You have to pay at least 1.00 {$aFields['currencyCode']}!&";
		$out.= "response.response=[invalid]";
		return $out; 
	}
		$newamount = $aFields['paymentAmount'] - ($aFields['amount'] *100) ; /* 100 points = 1 EUR */
		$out = "response.sessionFields.0.name=paymentAmount&";
		$out.= "response.sessionFields.0.value={$newamount}&";
		$out.= "response.sessionFields.1.name=currencyCode&";
		$out.= "response.sessionFields.1.value={$aFields['currencyCode']}&";
		$out.= "response.response=[accepted]";
		return $out; 
} 

function processTC( $aFields ) { 
	if (!is_array($aFields)) exit;  //should log error

	if( $aFields["tc"] == "yes") { 
		return "response.response=[accepted]";
	}     
	
	else {
		//Throw error
		$out= "response.customFields.0.name=tc&";
		$out.= "response.customFields.0.value=Please accept our terms and conditions!&";
		$out.= "response.response=[invalid]";
		return $out; 
		}
}

// helper functions

/* puts fields in array like this :
 [merchantAccount] => SomeMerchant
		[merchantReference] => 2013-08-23 22:58:44
		[amount] => 70
		[paypoints] => ja
		[skinCode] => gbpWBmxT
		[countryCode] => 
		[paymentAmount] => 7000
		[currencyCode] => EUR
		[shopperEmail] => 85306@adyen.com
		[shopperReference] => c43050993041b411f5a0f6152e0b3778
*/
function getFields($p) {
	$aFields["merchantAccount"] = $p["request_merchantAccount"];
	$aFields["merchantReference"] = $p["request_merchantReference"];

	$nrcust = 0; $nrsess = 0;
	foreach($p as $k => $v) {
	 if (stristr($k,"customField") ) $nrcust++;
	 if (stristr($k,"sessionField") ) $nrsess++;
	}

	for ($i=0;$i<$nrcust;$i++) {
		$name = $p["request_customFields_{$i}_name"];  $val = $p["request_customFields_{$i}_value"];  
		$aFields[$name] = $val;
	}

	for ($i=0;$i<$nrsess;$i++) {
		$name = $p["request_sessionFields_{$i}_name"];  $val = $p["request_sessionFields_{$i}_value"];  
		$aFields[$name] = $val;
	}
	return $aFields;

}

/* get input in several ways - use the one that works ;-) */
function writePost($prefix) {
	$raw = file_get_contents('php://input');
	$raw2 = $HTTP_RAW_POST_DATA;

	$date = date('Ymd-His');
	if (strlen($prefix) < 1)
		$fp = fopen("log/".$_SERVER['REMOTE_ADDR']."-$date.txt","w") ;
	else
		$fp = fopen("log/".$_SERVER['REMOTE_ADDR']."-$prefix-$date.txt","w") ;
	if (!$fp) exit;

	ob_start();
	print "called by ".$_SERVER['REMOTE_ADDR']."\n\n";
	print_r($_REQUEST);
	print "- php://input---------\n$raw";
	print "\n-HTTP_RAW_POST_DATA---------\n$raw2";
	$r = ob_get_clean();
	fputs($fp,$r,strlen($r));
	fclose($fp);
} 
?> 
