<?php
error_reporting(E_ALL);
require('../wp-config.php');
require_once(ABSPATH.'wp-admin/includes/user.php' );
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//require('../wp-content/plugins/woocommerce-gateway-stripe/includes/class-wc-gateway-stripe-addons.php');
session_start();
$getit = $_REQUEST['getit'];
global $wpdb, $current_site;

// https://www.youtube.com/watch?v=uXWVEeP-aOo
/* * **** Delete user and Info api ********* */

if($getit == "userdeleteinfo"){
    $user_id = $_REQUEST['user_id'];
        $response=array();
        $deletion = wp_delete_user($user_id);
        $args = array(
            'user_id' => $user_id
        );
        $bool = bp_activity_delete( $args );

                if($deletion){
                    $response['code']=0;
                    $response['msg']="Account deletion done";
                } else {
                    $response['code']=1;
                    $response['msg']="Please try again.....";
                }
        echo json_encode($response);
        exit();
}
exit();

?>