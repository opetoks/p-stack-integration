<?php
ob_start();
session_start();
include "connect_db.php";
include "classes/xfunct.php";
if(!isset($_SESSION['user_id'])){
    header('Location: error-page');
}
$uid = $_SESSION['user_id'];


if(isset($_POST['pay_btn'])){
    $amount = protect($_POST['amount']);
    $email = protect($_POST['email']);
    $firstname = protect($_POST['firstname']);
    $lastname = protect($_POST['lastname']);
    $name = $firstname." ".$lastname;
    $ip = $_SERVER['REMOTE_ADDR'];
    $phone = $_POST['phone'];
    
    if($amount < '5000'){
        $errormsg = 'error';
    }
    //print_r($_POST);
    //echo $name;
    else{
        try {
        $curl = curl_init();
        $email = $email;
        $name = $name;
        $amount = $amount;
        $currency = 'NGN';
        $option = 'card';
        $rand = mt_rand();
        $tx_ref = 'oaal-usr-0'.$uid.'-'.$rand;
        $redirect_url = 'callback.php';
        $date = date("l jS \of F Y h:i:s A");
        //print_r($tx_ref);
        //https://api.paystack.co/transaction/initialize
        $stmt = $db->prepare("INSERT INTO payments SET tranx_ref = '$tx_ref', user_id = '$uid', amount = '$amount',currency = '$currency', name ='$name', date ='$date'");
		$stmt->execute();
		if($stmt->rowCount() > 0 ){
		    
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.flutterwave.com/v3/payments",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'tx_ref'=>$tx_ref,
            'amount'=>$amount,
            'currency'=>$currency,
            'redirect_url' => $redirePaystackct_url,
            'payment_options' => $option,
            'meta'=>['consumer_id'=>$uid,
            'consumer_mac'=>$ip],
            'customer'=> ['email'=>$email,'phonenumber'=>$phone,'name'=> $name],
            'customizations'=>['title'=>'PUT HEADER HERE',
            'description'=>'Subscription Fee Title',
            'logo'=>'https:/linktoyourlogo/images/logo.png']
            ]),
          CURLOPT_HTTPHEADER => [
            "authorization: Bearer FOLLOWEDBYTHEBEARERKEY",
            "content-type: application/json",
            "cache-control: no-cache"
          ]));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        if($err){
          // there was an error contacting the flutter API
          die('Curl returned error: ' . $err);
        }

        $tranx = json_decode($response, true);

        if(!$tranx['status']){
          // there was an error from the API
          print_r('API returned error: ' . $tranx['message']);
        }
        //print_r($tranx);
        header('Location: ' . $tranx['data']['link']);
		}else{ 
		    $_SESSION['error'] = 'Refrence cannot be recorded! Contact site admin';
		    header('Location: payment');}

        } catch (Exception $e) {
        file_put_contents('ipn_data.log', "==========================\r\nTransaction Error\r\nTransaction Query :$tranx \r\nDate: $date\r\n" . $e->getMessage(), LOCK_EX | FILE_APPEND);
            exit('Not OK');
    }
}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title> PAYMENT </title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Sriracha&display=swap" rel="stylesheet">
    <!--Sweetalert Plugin --->
    <script src="bower_components/sweetalert/sweetalert.js"></script>
       <!-- jQuery 3 -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
</head>
<body>

 <p style="padding-top:30px;">
 	<center><h1 style="font-family: 'Sriracha', cursive;">PAYMENT</h1></center>
 
 </p>

 <main class="my-form">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php
                    
                    if(isset($_SESSION['error'])){
                      echo "
                        <div class='alert alert-danger alert-dismissible'>
                          <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                          <h4><i class='icon fa fa-warning'></i> Error!</h4>
                          ".$_SESSION['error']."
                        </div>
                      ";
                      unset($_SESSION['error']);
                    }
                
                ?>
                    <div class="card" style="background-color: rgba(4, 12, 37, 1); color:#fff;">
                      <div class="card-header" style="text-align: center;"> Subscription payment</div>
                        <div class="card-body">
                            <form name="payment-form" id="paymentForm" method="POST" action="">
                                <div class="form-group row">
                                    <label for="full_name" class="col-md-4 col-form-label text-md-right">First Name</label>
                                    <div class="col-md-6">
                                        <input type="text" id="first-name" class="form-control" name="firstname" value="<?php if(isset($_SESSION['user_id'])) {echo Info($uid, 'firstname');} ?>">
                                    </div>
                                </div>
                                <input type="hidden" id="uid" class="form-control" name="user_id" value="<?php if(isset($_SESSION['user_id'])) {echo Info($uid, 'user_id');} ?>">
                                
                                <input type="hidden" id="phone" class="form-control" name="phone" value="<?php if(isset($_SESSION['user_id'])) {echo Info($uid, 'phone');} ?>">


                                <div class="form-group row">
                                    <label for="full_name" class="col-md-4 col-form-label text-md-right">Last Name</label>
                                    <div class="col-md-6">
                                        <input type="text" id="last-name" class="form-control" name="lastname" value="<?php if(isset($_SESSION['user_id'])) {echo Info($uid, 'lastname');} ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="email_address" class="col-md-4 col-form-label text-md-right">E-Mail Address</label>
                                    <div class="col-md-6">
                                        <input type="text" id="email-address" class="form-control" name="email" value="<?php if(isset($_SESSION['user_id'])) {echo Info($uid, 'email');} ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="amount" class="col-md-4 col-form-label text-md-right">Amount</label>
                                    <div class="col-md-6">
                                        <input type="tel" id="amount" class="form-control" name="amount">
                                    </div>
                                </div>
                                <small id="message">  </small>

                                    <div class="col-md-6 offset-md-4">
                                        <button type="submit" class="btn btn-success" name="pay_btn"> Pay membership due </button>
                                    </div>
                                </div>
                            </form>
                            <?php 
                                if(empty($errormsg)){
                                    }else{
                                      echo'<script type="text/javascript">
                                          jQuery(function validation(){
                                          swal("PAYMENT TOO LOW", "You can only make payment above ???5000.00!", "error", {
                                          button: "Continue",
                                            });
                                          });
                                      </script>';
                                    }
                            ?>  
                        </div>
                    </div>
            </div>
198
</html>
        </div>
    </div>

</main>
</body>
</html>
