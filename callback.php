<?php
ob_start();
session_start();
include "connect_db.php";
include "xfunct.php";

$curl = curl_init();
$reference = protect(isset($_GET['tx_ref']) ? $_GET['tx_ref'] : '');
$status = protect(isset($_GET['status']) ? $_GET['status'] : '');
$transaction_id = protect(isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '');
$date = date("l jS \of F Y h:i:s A");
if(!$reference){
  die('No reference supplied');
}

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/". rawurlencode($transaction_id)."/verify",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => [
    "accept: application/json",
    "authorization: Bearer FOLLOWEDTHEBEARERKEY",//Bearer KEYMUST BE HERE-X",
    "cache-control: no-cache"
  ],
));

$response = curl_exec($curl);
$err = curl_error($curl);
//print_r($response);

if($err){
    // there was an error contacting the flutter API
  die('Curl returned error: ' . $err);
}
$tranx = json_decode($response);
try {
        if(!$tranx->status){
        // there was an error from the API
        die('Payment GW returned error: ' . $tranx->message);
        }
        
		if($tranx!=null && 'success' == $tranx->status)
			{
			    	foreach($tranx as $key => $value)
					{
						$data = $value['data'];
						foreach($data as $datavalue){
						    $status = $datavalue['status']; 
						    $amount = $datavalue['amount'];
						    $currency = $datavalue['currency'];
						    $tx_ref = $datavalue['tx_ref'];
						    $payment_type = $datavalue['payment_type'];
						}
					}
        $stmt = $db->prepare("SELECT * FROM payments WHERE tranx_ref = '$tx_ref'");
	    $stmt->execute();
		if($stmt->rowCount() > 0 ){
		    $row = $stmt->fetch(PDO::FETCH_ASSOC);
		    if($row['amount']==$amount AND $row['currency']==$currency){
		        
		        $updstmt = $db->prepare("UPDATE payments SET status = '$status', transaction_id = '$transaction_id' WHERE tranx_id = '$tx_ref'");
		        $updstmt->execute();
		        if($stmt->rowCount() > 0 ){
		            $paymentSuccess = '<img src="images/paid.jpg" style="margin-left:50%;">';
		        }
		    
		}
		else{
		     $referenceFailure = "<h2>Error! Payment refrence not registered. Detail of failure has been sent your mail. Contact the admin for any complaints.</h2>";
		}
		}
	}
	else{
	    $paymentFailure = '<img src="images/failure.jpg" style="margin-left:40%;"><br><h3 style="color:red;"><center>Payment failed, detail of failure has been sent your mail</center></h3>'; //failure.jpg
	    $paymentFailureclass = "hide";
			 
	}
}
	catch (Exception $e) {
		    file_put_contents('ipn_data.log', "==========================\r\nTransaction Error\r\nTransaction Query :$response \r\nDate: $date\r\n" . $e->getMessage(), LOCK_EX | FILE_APPEND);
			exit('Not OK');
		}
    
  // transaction was successful...


  // I still need to check whether value already exist for this ref

  // I still need to check whether if email matches the customer who owns the product etc

  // Assign value and store in db
  

?>
<!DOCTYPE html>
<html>
<head>
    <title> PAYMENT RECEIPT</title>
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
 <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Charm" rel="stylesheet"> 
<style type="text/css">
    .text-danger strong {
            color: #9f181c;
        }
        .receipt-main {
            background: #ffffff none repeat scroll 0 0;
            border-bottom: 12px solid #333333;
            border-top: 12px solid #9f181c;
            margin-top: 50px;
            margin-bottom: 50px;
            padding: 40px 30px !important;
            position: relative;
            box-shadow: 0 1px 21px #acacac;
            color: #333333;
            font-family: open sans;
            width: 800px;
        }
        .receipt-main p {
            color: #333333;
            font-family: open sans;
            line-height: 1.42857;
        }
        .receipt-footer h1 {
            font-size: 15px;
            font-weight: 400 !important;
            margin: 0 !important;
        }
        .receipt-main::after {
            background: #414143 none repeat scroll 0 0;
            content: "";
            height: 5px;
            left: 0;
            position: absolute;
            right: 0;
            top: -13px;
        }
        .receipt-main thead {
            background: #414143 none repeat scroll 0 0;
        }
        .receipt-main thead th {
            color:#fff;
        }
        .receipt-right h5 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 7px 0;
        }
        .receipt-right p {
            font-size: 12px;
            margin: 0px;
        }
        .receipt-right p i {
            text-align: center;
            width: 18px;
        }
        .receipt-main td {
            padding: 9px 20px !important;
        }
        .receipt-main th {
            padding: 13px 20px !important;
        }
        .receipt-main td {
            font-size: 13px;
            font-weight: initial !important;
        }
        .receipt-main td p:last-child {
            margin: 0;
            padding: 0;
        }   
        .receipt-main td h2 {
            font-size: 20px;LWSECK_TEST-SANDBOXDEMOKEY
            font-weight: 900;
            margin: 0;
            text-transform: uppercase;
        }
        .receipt-header-mid .receipt-left h1 {
            font-weight: 100;
            margin: 34px 0 0;
            text-align: right;
            text-transform: capitalize;
        }
        .receipt-header-mid {
            margin: 24px 0;
            overflow: hidden;
        }
        .hide{
            display:none;
        }
        
        #container {
            background-color: #dcdcdc;
        }
</style>
</head>
<body>
<div class="container">
    <div class="row">
        
        <div class="receipt-main col-xs-10 col-sm-10 col-md-6 col-xs-offset-1 col-sm-offset-1 col-md-offset-2">
            <div class="row"><div class="pull-right no-print"><a href="index"><i class="fa fa-home" title="home"></i></a>  <i class='fa fa-print' onclick="window.print()" title="print"></i></div>
            <center> SUBSCRIPTION.</center>
            <br>
                <div class="receipt-header">
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="receipt-left">
                            <img class="img-responsive" alt="" src="images/logo.png" style="width: 71px;"><!-- border-radius: 43px; -->
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6 text-right">
                        <div class="receipt-right">
                            <h5>PAYMENT RECEIPT</h5>
                            <p>Generated on:<?php echo $date;?></p>
                            <p>Transaction ID: <?php echo $transaction_id; ?> </p>
                            <p>Description: MEMEBERSHIP SUBSCRIPTION</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php 
                            if(isset($paymentFailure)){ echo $paymentFailure;}
                            if(isset($paymentSuccess)){ echo $paymentSuccess;}
                            
                ?>
            </div>
            <div class="row <?php if(isset($paymentFailureclass)){ echo $paymentFailureclass;}?>">
                <div class="receipt-header receipt-header-mid">
                    <div class="col-xs-8 col-sm-8 col-md-8 text-left">
                        <div class="receipt-right">
                           
                            
                            <h5>Trx: <?php echo $transaction_id; ?> <small> | Order ID:<?php echo $reference;?></small></h5>
                            <p>Membership No/Reg No:<?php //echo $matricno; ?></p>
                            <p>Email : <?php // echo $Studentemail; ?></p>
                            <p>Mobile No: <?php //echo $PhoneNo ?></p>
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="receipt-left">
                            <h4><?php echo $status; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="<?php if(isset($paymentFailureclass)){ echo $paymentFailureclass;}?>">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="col-md-9">Being Payment for <?php //echo $Description; ?></td>
                            <td class="col-md-3">??? <?php if(isset($amount)) {echo $amount."00";} ?></td>
                        </tr>
                        
                        <tr>
                            <td class="text-right">
                            Payment Scheme : <?php if(isset($payment_type)) {echo $payment_type;} ?>
                            </td>
                            <td>
                            
                            </td>
                        </tr>
                        <tr>
                           
                            <td class="text-right"><h2><strong>Total: </strong></h2></td>
                            <td class="text-left text-danger"><h2><strong>???  <?php if(isset($amount)) {echo $amount."00";}; ?></strong></h2></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="row <?php if(isset($paymentFailureclass)){ echo $paymentFailureclass;}?>">
                <div class="receipt-header receipt-header-mid receipt-footer">
                    <div class="col-xs-6 col-sm-6 col-md-6 text-left">
                        <div class="receipt-right">
                            <h1> Member's Signature</h1><br>
                            <p><b>Date :</b> <?php echo $date;?></p>
                            <h5 style="color: rgb(140, 140, 140);"><a href="profile">View Payment history.</a></h5>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="receipt-left">
                        <h1>Admin: .............</h1><br>
                           
                            
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                <img src="images/flutterwave.png" width="100%">
                </div>
            </div>
            
        </div>    
    </div>
</div>
</body>
</html>
