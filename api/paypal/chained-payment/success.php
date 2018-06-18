<?php
require_once('../PPBootStrap.php');
session_start();
/*
 *  # PaymentDetails API
  Use the PaymentDetails API operation to obtain information about a payment. You can identify the payment by your tracking ID, the PayPal transaction ID in an IPN message, or the pay key associated with the payment.
  This sample code uses AdaptivePayments PHP SDK to make API call
 */
/*
 * 
  PaymentDetailsRequest which takes,
  `Request Envelope` - Information common to each API operation, such
  as the language in which an error message is returned.
 */
$requestEnvelope = new RequestEnvelope("en_US");
/*
 * 		 PaymentDetailsRequest which takes,
  `Request Envelope` - Information common to each API operation, such
  as the language in which an error message is returned.
 */
$paymentDetailsReq = new PaymentDetailsRequest($requestEnvelope);
/*
 * 		 You must specify either,

 * `Pay Key` - The pay key that identifies the payment for which you want to retrieve details. This is the pay key returned in the PayResponse message.
 * `Transaction ID` - The PayPal transaction ID associated with the payment. The IPN message associated with the payment contains the transaction ID.
  `paymentDetailsRequest.setTransactionId(transactionId)`
 * `Tracking ID` - The tracking ID that was specified for this payment in the PayRequest message.
  `paymentDetailsRequest.setTrackingId(trackingId)`
 */
if ($_SESSION['pay_key'] != "") {
    $paymentDetailsReq->payKey = $_SESSION['pay_key'];
}
if(file_exists('../../../wp-config.php')){
    require_once('../../../wp-config.php');
    global $wpdb;
}
/*
 * 	 ## Creating service wrapper object
  Creating service wrapper object to make API call and loading
  Configuration::getAcctAndConfig() returns array that contains credential and config parameters
 */
$service = new AdaptivePaymentsService(Configuration::getAcctAndConfig());
try {
    /* wrap API method calls on the service object with a try catch */
    $response = $service->PaymentDetails($paymentDetailsReq);
    if ($response) {
        $product_id = $_REQUEST['id'];
        $product_price = $_REQUEST['price'];
        $productname = $_REQUEST['name'];
        $user_id = $_REQUEST['user_id'];
        $product_quantity = $_REQUEST['quantity'];
        $seller_id = $_REQUEST['sellerid'];
        if ($response->status == 'COMPLETED') {
            $admin_transid = $response->paymentInfoList->paymentInfo[1]->transactionId;
            $seller_transid = $response->paymentInfoList->paymentInfo[0]->transactionId;
            $admin_share = $response->paymentInfoList->paymentInfo[1]->receiver->amount;
            $seller_share = $response->paymentInfoList->paymentInfo[0]->receiver->amount;
            $admin_email = $response->paymentInfoList->paymentInfo[1]->receiver->email;
            $seller_email = $response->paymentInfoList->paymentInfo[0]->receiver->email;
            $payment_status = $response->status;
            $created = date('Y-m-d H:i:s');
            $proqty = $results = $wpdb->get_row("SELECT * FROM wp_bp_activity WHERE id = '" . $product_id . "'", ARRAY_A);
            $totalqty = $proqty['productquantity'];
            
                //update Product main Qty
                $qtyrem = $totalqty - $product_quantity;
                $updateqty = "UPDATE `wp_bp_activity` SET `productquantity`= '" . $qtyrem . "' WHERE id = '" . $product_id . "'";
                $wpdb->query($updateqty);
                // Save Order after Payment
                $sql1 = "INSERT INTO `wp_orders`(`admin_transid`, `seller_transid`, `user_id`, `product_id`, `seller_id`, `price`, `quantity`, `totalprice`, `admin_share`, `seller_share`, `admin_email`, `seller_email`, `payment_status`, `created`) VALUES ('" . $admin_transid . "','" . $seller_transid . "','" . $user_id . "','" . $product_id . "','" . $seller_id . "','" . $product_price . "','" . $product_quantity . "','" . $product_price . "','" . $admin_share . "','" . $seller_share . "','" . $admin_email . "','" . $seller_email . "','" . $payment_status . "','" . $created . "')";
                $wpdb->query($sql1);
                
                $res['status'] = true;
                $res['data'] = $response;
                echo json_encode($res);
                exit;
         
        }
    }
} catch (Exception $ex) {
    require_once '../Common/Error.php';
    exit;
}
        

