<?php

require('../wp-config.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//require('../wp-content/plugins/woocommerce-gateway-stripe/includes/class-wc-gateway-stripe-addons.php');
session_start();
$getit = $_REQUEST['getit'];
global $wpdb, $current_site;


/// api to post an product as an post from app /////
if ($getit == "productpostupdate") {

    $productname = $_REQUEST['productname'];
    $productprice = $_REQUEST['productprice'];
    $sellproduct = 1;
    $mediaid = $_REQUEST['mediaid'];
    $media_ids = explode(",", $mediaid);
    $user_id = $_REQUEST['user_id'];
    $content = $_REQUEST['content'];

    if (isset($_REQUEST['productquantity'])) {
        $productquantity = $_REQUEST['productquantity'];
    } else {
        $productquantity = 0;
    }

    $type = $_REQUEST['type'];
    $recorded_time = date('Y-m-d H:i:s'); //$_REQUEST['recorded_time'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $primarylink = "https://aawesomeme.com/members/" . $user_nicename . "/";
    $action = '<a href="' . esc_url('https://aawesomeme.com/members/' . $user_nicename) . '">' . esc_html($user_login) . '</a> posted an update';
    $args = array(
        'action' => $action,
        'content' => $content,
        'component' => 'activity',
        'type' => $type,
        'primary_link' => $primarylink,
        'user_id' => $user_id,
        'recorded_time' => $recorded_time,
    );

    // 'productname'=>$productname,
    //             'productprice'=>$productprice,
    //             'sellproduct'=>$sellproduct,

    $res = bp_activity_add($args);
    if ($res) {
        $argsq = array(
            'productname' => $productname,
            'productprice' => $productprice,
            'sellproduct' => $sellproduct,
            'productquantity' => $productquantity,
            'is_spam' => 1
        );

        $wpdb->update('wp_bp_activity', $argsq, array('id' => $res));
        //$sql ="UPDATE wp_bp_activity  SET `productname`= '".$productname ."' ,`productprice`= '".$productprice ."', `sellproduct`= '".$sellproduct ."' WHERE  `id` = '".$res."'";
        $sendreq = $wpdb->insert('wp_rt_rtm_activity', array(
            'activity_id' => $res,
            'user_id' => $user_id,
            'privacy' => 0,
            'blog_id' => 1
        ));
        bp_activity_update_meta($res, 'rtmedia_privacy', 0);
        //Update rt Media
        if (!empty($media_ids)) {
            foreach ($media_ids as $media_id) {
                $table_name = $wpdb->prefix . 'rt_rtm_media';
                $update_rt_media = $wpdb->update($table_name, array('activity_id' => $res), array('media_id' => $media_id));
            }
        }
        $response['code'] = 0;
        $response['msg'] = "Product post successful" . $res;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = "Product post unsuccessful";
        echo json_encode($response);
        exit();
    }
}




//// api to post an product with image or video update in media ////
if ($getit == "productpostupdatertmedia") {
    $user_id = $_REQUEST['user_id'];
    $content = $_REQUEST['content'];
    $productname = $_REQUEST['productname'];
    $productprice = $_REQUEST['productprice'];
    $sellproduct = 1;
    if (isset($_REQUEST['productquantity'])) {
        $productquantity = $_REQUEST['productquantity'];
    } else {
        $productquantity = 0;
    }
    $mediacontent = explode(",", $_REQUEST['mediacontent']);
    $countmedia = count($mediacontent);
    $type = $_REQUEST['type'];
    $recorded_time = date('Y-m-d H:i:s'); //$_REQUEST['recorded_time'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $action = '<a href="' . esc_url('https://aawesomeme.com/members/' . $user_nicename) . '">' . esc_html($user_login) . '</a> posted an update';
    $primarylink = "https://aawesomeme.com/members/" . esc_html($user_nicename) . "/";
    $html = '<div class="rtmedia-activity-container"><div class="rtmedia-activity-text"><span>';
    if ($content != undefined) {
        $html .= esc_html($content);
    }
    // else { 
    //     $html .= "&nbsp";
    // }
    $html .= '</span></div>';
    $html .="<ul class='rtmedia-list rtm-activity-media-list rtmedia-activity-media-length-";
    $html .= $countmedia;
    $html .= "'>";

    foreach ($mediacontent as $key => $value) {
        if (strpos($value, "vmvideo") > 0) {
            $html .= '<li class="rtmedia-list-item media-type-video">
                <div class="rtmedia-item-thumbnail">
                <video src="';
            $html .= $value;
            $html .= '" width="320" height="240" class="wp-video-shortcode" id="rt_media_video_418" controls="controls" controlsList="nodownload" preload="none">
                </video>';
            $strlen = strlen($value) - 4;
            $strpos = strpos($value, "vmvideo");
            $substr = substr($value, $strpos, $strlen);

            $html .= '</div>
                <div class="rtmedia-item-title">';
            $html .= $substr;
            $html .= '</div></a></li>';
        } else {
            $html .= '<li class="rtmedia-list-item media-type-photo">
            <a href="';
            $html .= $primarylink;
            $html .='">
                <div class="rtmedia-item-thumbnail">
                    <img alt="';
            $strlen = strlen($value) - 4;
            $strpos = strpos($value, "vmmedia");
            $substr = substr($value, $strpos, $strlen);
            $html .= $substr;
            $html .= '" src="';
            $html .= $value;
            $html .= '" />
                </div>
                <div class="rtmedia-item-title">';
            $html .= $substr;
            $html .= '</div></a></li>';
        }
    }

    $html .= '</ul></div>';

    $args = array(
        'action' => $action,
        'content' => $html,
        'component' => 'activity',
        'type' => $type,
        'primary_link' => $primarylink,
        'user_id' => $user_id,
        'recorded_time' => $recorded_time,
    );

    $res = bp_activity_add($args);
    if ($res) {

        $argsq = array(
            'productname' => $productname,
            'productprice' => $productprice,
            'productquantity' => $productquantity,
            'sellproduct' => $sellproduct,
            'is_spam' => 1
        );

        $wpdb->update('wp_bp_activity', $argsq, array('id' => $res));

        $activity_data = array();
        $activity_data['akismet_comment_nonce'] = 'inactive';
        $activity_data['comment_author'] = $output->data->display_name;
        $activity_data['comment_author_email'] = $output->data->user_email;
        $activity_data['comment_author_url'] = bp_core_get_userlink($user_id, false, true);
        $activity_data['comment_content'] = " ";
        $activity_data['comment_type'] = "activity_update";
        $activity_data['permalink'] = bp_activity_get_permalink($res);
        $activity_data['user_ID'] = $user_id;
        $activity_data['user_role'] = akismet_get_user_roles($user_id);
        $data = serialize($activity_data);
        $event = array(
            'event' => "check-ham",
            'message' => "Akismet cleared this item",
            'time' => akismet_microtime(),
            'user' => bp_loggedin_user_id(),
        );

        // Save the history data
        bp_activity_update_meta($res, '_bp_akismet_history', $event);
        bp_activity_update_meta($res, 'rtmedia_privacy', 0);
        bp_activity_update_meta($res, 'bp_old_activity_content', $html);
        bp_activity_update_meta($res, 'bp_activity_text', $content);
        bp_activity_update_meta($res, '_bp_akismet_result', false);
        bp_activity_update_meta($res, '_bp_akismet_submission', $activity_data);
        $sendreq = $wpdb->insert('wp_rt_rtm_activity', array(
            'activity_id' => $res,
            'user_id' => $user_id,
            'privacy' => 0,
            'blog_id' => 1
        ));
        $response['code'] = 0;
        $response['msg'] = "Media update now";
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = "Post unsuccessful";
        echo json_encode($response);
        exit();
    }
}

//if($getit=="updateqtyafterproductsell"){
//    $product_id = $_REQUEST['product_id'];
//    $getqty = "SELECT * FROM $wpdb->bp_activity WHERE id = '".$product_id."'";
//    $gerres = $wpdb->get_row($getqty, ARRAY_A);
//    $qty = $gerres[productquantity];
//    $sellqty = $_REQUEST['qty'];
//    $newqty = $qty - $sellqty;
//    $sql ="UPDATE $wpdb->bp_activity  SET `productquantity`= '".$newqty."'	WHERE  `id` = '".$product_id."' AND `sellproduct` = 1";
//    $updateactivity = $wpdb->query($sql);
//    
//}

if ($getit == "checkqtyavailabelitypluse") {
    $product_id = $_REQUEST['product_id'];
    $product_price = $_REQUEST['price'];
    $qtyval = $_REQUEST['qtyval'];
    $getqty = "SELECT * FROM " . $wpdb->prefix . "bp_activity WHERE id = '" . $product_id . "'";
    $gerres = $wpdb->get_row($getqty, ARRAY_A);
    //echo '<pre>'; print_r($gerres); echo '</pre>'; 
    $qty = $gerres['productquantity'];
    if ($qty >= $qtyval) {
        $newprice = $product_price * $qtyval;

        $response['code'] = 0;
        $response['productprice'] = $newprice;
        $response['msg'] = "Quantity updated";
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['productprice'] = $product_price;
        $response['msg'] = "Quantity Not availabel";
        echo json_encode($response);
        exit();
    }
}

if ($getit == "oredrcreated") {
    $product_id = $_REQUEST['product_id'];
    $user_id = $_REQUEST['user_id'];
    $seller_id = $_REQUEST['seller_id'];
    $transc_id = $_REQUEST['transc_id'];
    $quantity = $_REQUEST['quantity'];
    $total = $_REQUEST['totalprice'];
    $created = date('Y-m-d H:i:s');
    $orderdata = array(
        'transc_id' => $transc_id,
        'user_id' => $user_id,
        'product_id' => $product_id,
        'seller_id' => $seller_id,
        'quantity' => $quantity,
        'totalprice' => $total,
        'created' => $created
    );
    $wpdb->insert("{$wpdb->prefix}orders", $orderdata);
    $order_id = $wpdb->insert_id;
    if ($order_id != '') {

        $getqty = "SELECT * FROM " . $wpdb->prefix . "bp_activity WHERE id = '" . $product_id . "'";
        $gerres = $wpdb->get_row($getqty, ARRAY_A);
        $qty = $gerres['productquantity'];
        $newqty = $qty - $quantity;
        $sql = "UPDATE " . $wpdb->prefix . "bp_activity  SET `productquantity`= '" . $newqty . "'	WHERE  `id` = '" . $product_id . "' AND `sellproduct` = 1";
        $updateactivity = $wpdb->query($sql);

        $response['code'] = 0;
        $response['order_id'] = $order_id;
        $response['msg'] = "Order created Successfully";
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['order_id'] = '';
        $response['msg'] = "No Order created";
        exit();
    }
}

//Code to save paypal email for payments sent to seller
if ($getit == "savepaypalemail") {
    $user_id = $_REQUEST['userid'];
    $paypalemail = $_REQUEST['paypalemail'];
    $update = $wpdb->update('wp_users', array('paypal_email' => $paypalemail), array('ID' => $user_id)
    );
    if ($update) {
        //$userdata = get_userdata($user_id);
        $userdata = $wpdb->get_results("SELECT * FROM $wpdb->users WHERE (ID=$user_id)");

        $avatarid = get_user_meta($user_id, "wp_user_avatar");
        if (!empty($avatarid)) {
            $id = $avatarid[0];
            $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
            $image = $media[0]->guid;
            $userdata[0]->image = '<img src="' . $image . '">';
        } else {
//                        $avatar = get_avatar_url($value->data->ID);
//                        $data[$key]->data->userimage = '<img src="'.$avatar.'" >';
            $avatar = get_avatar($user_id);
            $checkgr = explode("//", $avatar);
            if (strpos($checkgr[1], 'www.gravatar') !== false) {
                $avatarimg = get_avatar_url($user_id);
                $userdata[0]->image = '<img src= "https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';
            } else {
                $userdata[0]->image = $avatar;
            }
        }
        $response['code'] = 0;
        $response['data'] = $userdata[0];
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        echo json_encode($response);
        exit();
    }
}


/**************Post Id By slug ruapk********************/


if ($getit == "postslug") {
    global $wpdb;
    $postid = $_REQUEST['postid'];
    
 
    if ($postid) {

           $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where id=".$postid;
            $posts = $wpdb->get_results($count_query);

         if($posts[0]->component == 'blogs') {
         
           $url = 'https://aawesomeme.com/?p='.$posts[0]->secondary_item_id;

         }else{
        $url = bp_activity_get_permalink($postid);
         } 
        
       
      // $url = get_post_permalink($postid);
        $response['status'] = true;
        $response['url'] = $url;
     $response['post'] = $posts;
        echo json_encode($response);
        exit();
    } else {
        $response['msg'] = 'post id requred';
        $response['status'] = false;

        echo json_encode($response);
        exit();
    }
}
/********************************************/


/*
 * //Purchase ticket
 */
if ($getit == "allcustomerorders") {
    $user_id = $_REQUEST['user_id'];
    //$customer_orders = $wpdb->get_results("SELECT *,  FROM ". $wpdb->prefix ."orders WHERE user_id = '".$user_id."'", ARRAY_A);
    $table1 = $wpdb->prefix . "orders";
    $table2 = $wpdb->prefix . "users";
    $table3 = $wpdb->prefix . "bp_activity";
    $getqry = "SELECT " . $table1 . ".*," . $table2 . ".display_name as customer," . $table3 . ".productname, (SELECT display_name FROM " . $table2 . " WHERE " . $table2 . ".ID = " . $table1 . ".seller_id) as seller_name FROM " . $table1 . " LEFT JOIN " . $table2 . " ON " . $table2 . ".ID = " . $table1 . ".user_id LEFT JOIN " . $table3 . " ON " . $table3 . ".id = " . $table1 . ".product_id WHERE " . $table1 . ".user_id = '" . $user_id . "' ";
    $allorders = $wpdb->get_results($getqry);
    if (!empty($allorders)) {
        $response['code'] = 0;
        $response['data'] = $allorders;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        echo json_encode($response);
        exit();
    }
}
if ($getit == "allsellerorders") {
    $seller_id = $_REQUEST['user_id'];
    //$customer_orders = $wpdb->get_results("SELECT *,  FROM ". $wpdb->prefix ."orders WHERE user_id = '".$user_id."'", ARRAY_A);
    $table1 = $wpdb->prefix . "orders";
    $table2 = $wpdb->prefix . "users";
    $table3 = $wpdb->prefix . "bp_activity";
    $getqry = "SELECT " . $table1 . ".*," . $table2 . ".display_name as customer," . $table3 . ".productname, (SELECT display_name FROM " . $table2 . " WHERE " . $table2 . ".ID = " . $table1 . ".seller_id) as seller_name FROM " . $table1 . " LEFT JOIN " . $table2 . " ON " . $table2 . ".ID = " . $table1 . ".user_id LEFT JOIN " . $table3 . " ON " . $table3 . ".id = " . $table1 . ".product_id WHERE " . $table1 . ".seller_id = '" . $seller_id . "' ";
    $allorders = $wpdb->get_results($getqry);
    if (!empty($allorders)) {
        $response['code'] = 0;
        $response['data'] = $allorders;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        echo json_encode($response);
        exit();
    }
}

//if ($getit = "myproducts") {
//    $seller_id = $_REQUEST['user_id'];
//    $table2 = $wpdb->prefix . "users";
//    $table1 = $wpdb->prefix . "bp_activity";
//    $getqry = "SELECT *,$table2.display_name as customer FROM $table1 LEFT JOIN " . $table2 . " ON " . $table2 . ".ID = " . $table1 . ".user_id  WHERE $table1.sellproduct = 1 AND $table1.user_id = '" . $seller_id . "'";
//
//    $results = $wpdb->get_results($getqry, ARRAY_A);
//    if (!empty($results)) {
//        $response['code'] = 0;
//        $response['data'] = $results;
//        echo json_encode($response);
//        exit();
//    } else {
//        $response['code'] = 1;
//        echo json_encode($response);
//        exit();
//    }
//}

exit();
/* * ******** search_event api end ************ */
?>
