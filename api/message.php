<?php
require('../wp-config.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//require('../wp-content/plugins/woocommerce-gateway-stripe/includes/class-wc-gateway-stripe-addons.php');
session_start();
$getit = $_REQUEST['getit'];
global $wpdb, $current_site;

//// function to get time in ago time period//////
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

if($getit=="getmessages"){
    
    $user_id = $_REQUEST['user_id'];

    $table_name = $wpdb->prefix . 'bp_messages_messages';
        $count_query = "select * from $table_name WHERE sender_id=$user_id ORDER BY id DESC";
        $groups = $wpdb->get_results($count_query);

            foreach($groups as $key=>$value){
                $groups[$key]->recorded_date = time_elapsed_string($value->date_sent);
            }
            
        $table_name2 = $wpdb->prefix . 'bp_notifications';
        $count_query2 = "select * from $table_name2 WHERE user_id=$user_id ORDER BY id ASC";
        $notifications = $wpdb->get_results($count_query2);

            foreach($notifications as $k=>$v){
                        if($v->date_notified=="0000-00-00 00:00:00"){
                            $notifications[$k]->recorded_date = "Date not found";
                        } else {
                            $notifications[$k]->recorded_date = time_elapsed_string($v->date_notified);
                        }
                
                    switch($v->component_name){
                        case "social_articles":
                            $user_data = get_user_by('ID', $v->secondary_item_id);
                            $user_nicename = $user_data->display_name;
                            $avatarid = get_user_meta($v->secondary_item_id, "wp_user_avatar");
                                    if(!empty($avatarid)){
                                        $id=$avatarid[0];
                                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                                        $image = $media[0]->guid;
                                        $notifications[$k]->userimage = '<img src="'.$image.'">';
                                    } else {
                                        $notifications[$k]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
                                    }
                                $notifications[$k]->user_nicename=$user_nicename;
                                $notifications[$k]->notification="There is a new article by ".$user_nicename.", check it out!";
                            break;
                        case "friends":
                            $user_data = get_user_by('ID', $v->item_id);
                            $user_nicename = $user_data->display_name;
                            $avatarid = get_user_meta($v->item_id, "wp_user_avatar");
                                    if(!empty($avatarid)){
                                        $id=$avatarid[0];
                                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                                        $image = $media[0]->guid;
                                        $notifications[$k]->userimage = '<img src="'.$image.'">';
                                    } else {
                                        $notifications[$k]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
                                    }
                                $notifications[$k]->user_nicename=$user_nicename;
                                $notifications[$k]->notification="You have a friendship request from ".$user_nicename;
                            break;
                        case "follow":
                            $user_data = get_user_by('ID', $v->item_id);
                            $user_nicename = $user_data->display_name;
                            $avatarid = get_user_meta($v->item_id, "wp_user_avatar");
                                    if(!empty($avatarid)){
                                        $id=$avatarid[0];
                                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                                        $image = $media[0]->guid;
                                        $notifications[$k]->userimage = '<img src="'.$image.'">';
                                    } else {
                                        $notifications[$k]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
                                    }
                                $notifications[$k]->user_nicename=$user_nicename;
                                $notifications[$k]->notification=$user_nicename." is now following you";
                            break;
                        default:

                    }
            }
    if($notifications){
        $response['code']=0;
        $response['data']=$groups;
        $response['notifications']=$notifications;
            echo json_encode($response);
                exit();
    } else {
        $response['code']=1;
        $response['msg']="No data available";
            echo json_encode($response);
                exit();
    }
        exit();
}

if ($getit == "sendmessages") {
    // $getlast = "SELECT * FROM {$wpdb->prefix}bp_messages_messages ORDER BY id DESC LIMIT 1";
    // $last_thread_id = $wpdb->get_row($getlast, ARRAY_A);
    // $last_thread = $last_thread_id['thread_id'];
    $sender_id = $_REQUEST['sender']; //user id who send msg
    $reciver_id = $_REQUEST['reciver']; //user id who recive msg
    $subject = $_REQUEST['subject'];
    $message = $_REQUEST['message'];
    $date_sent = date('Y-m-d H:i:s');
    // $data = array(
    //         'thread_id' => $last_thread + 1,
    //         'sender_id' => $sender_id,
    //         'subject' => $subject,
    //         'message' => $message,
    //         'date_sent' => $date_sent
    //     );
    // Array of arguments. 
	$args = array( 
	    'sender_id' => $sender_id, 
	    'thread_id' => false, 
	    'recipients' => $reciver_id, 
	    'subject' => esc_textarea($subject), 
	    'content' => esc_textarea($message), 
	    'date_sent' => $date_sent
	); 
  
// NOTICE! Understand what this does before running. 
		$sendmsg = messages_new_message($args);
        //$sendmsg = $wpdb->insert('wp_bp_messages_messages', $data);

        if ($sendmsg) {
            // $lastid = $wpdb->insert_id;
            // $notifydata = array(
            //     'user_id' => $reciver_id,
            //     'item_id' => $lastid,
            //     'secondary_item_id' => $sender_id,
            //     'component_name' => 'message',
            //     'component_action' => 'new_message',
            //     'date_notified' => $date_sent,
            //     'is_new' => 1    
            // );
            // $insert_notif = $wpdb->insert('wp_bp_notifications', $notifydata);
            $response['code'] = 0;
            $response['thread_id'] = $sendmsg;
            $response['msg'] = 'Message Sent Successfully';
            echo json_encode($response);
            exit();
        } else {
            $response['code'] = 1;
            $response['msg'] = 'Message Not Sent';
            echo json_encode($response);
            exit();
        }
    exit();
}
if ($getit == "readunreadmsg") {
    $sender_id = $_REQUEST['sender']; // user id who send msg
    $reciver_id = $_REQUEST['reciver']; // user id who recive msg
    $thread_id = $_REQUEST['thread_id']; //id from last api

    if ($thread_id != '') {
        $data = array(
            'user_id' => $sender_id,
            'thread_id' => $thread_id,
            'unread_count' => 0,
            'sender_only' => 1,
            'is_deleted' => 0
        );
        $sendmsgread = $wpdb->insert('wp_bp_messages_recipients', $data);
        $data1 = array(
            'user_id' => $reciver_id,
            'thread_id' => $thread_id,
            'unread_count' => 1,
            'sender_only' => 0,
            'is_deleted' => 0
        );
        $sendmsgreadun = $wpdb->insert('wp_bp_messages_recipients', $data1);
        $response['code'] = 0;
        $response['msg'] = 'Message Sent Successfully';
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'Message Not Sent';
        echo json_encode($response);
        exit();
    }
    exit();
}

if ($getit == "update_readunread") {
    $thread_id = $_REQUEST['thread_id'];
    $user_id = $_REQUEST['user_id']; // user id who read msg
    $table_name = "wp_bp_messages_recipients";
    $msgread = $wpdb->update('wp_bp_messages_recipients', array('unread_count'=>1),
                array('user_id'=> $user_id,'thread_id' => $thread_id));

    if ($msgread) {
        $response['code'] = 0;
        $response['msg'] = 'Message Read Successfully';
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'Message Not Read';
        echo json_encode($response);
        exit();
    }
    exit();
}

// api to delete messages 
if ($getit == "deletemsg") {
    $thread_id = $_REQUEST['thread_id'];
    $user_id = $_REQUEST['user_id']; // user id who read msg
    //$table_name = "wp_bp_messages_recipients";
    $result = messages_delete_thread($thread_id, $user_id); 

    if ($result) {
        $response['code'] = 0;
        $response['msg'] = 'Message delete Successfully';
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'Message not deleted';
        echo json_encode($response);
        exit();
    }
    exit();
}

//get Message getmsgreplies
if($getit == "getmsgreplies"){
    $user_id = $_REQUEST['user_id'];
    $thread_id = $_REQUEST['thread_id'];
    $sql = "SELECT wp_bp_messages_messages.*,wp_bp_messages_recipients.user_id,wp_users.display_name as sendername,
	(SELECT wp_users.display_name FROM wp_users WHERE wp_users.ID= wp_bp_messages_recipients.user_id ) as reciver_name,

	(SELECT unread_count FROM `wp_bp_messages_recipients` where wp_bp_messages_recipients.thread_id = wp_bp_messages_messages.thread_id and wp_bp_messages_recipients.user_id=wp_bp_messages_messages.sender_id) as sender_unreadcount,

	(SELECT unread_count FROM `wp_bp_messages_recipients` t1 where t1.thread_id = wp_bp_messages_messages.thread_id and t1.user_id=wp_bp_messages_recipients.user_id) as reciver_unreadcount 

	FROM `wp_bp_messages_messages` 
	join 
	wp_bp_messages_recipients 
	on wp_bp_messages_recipients.thread_id = wp_bp_messages_messages.thread_id

	join 
	wp_users on wp_users.ID = wp_bp_messages_messages.sender_id 

where (wp_bp_messages_messages.sender_id = '".$user_id."' OR wp_bp_messages_recipients.user_id ='".$user_id."') AND wp_bp_messages_recipients.thread_id ='".$thread_id."' AND wp_bp_messages_recipients.is_deleted=0 GROUP BY wp_bp_messages_messages.id ORDER BY wp_bp_messages_messages.date_sent DESC";
    $mesages = $wpdb->get_results($sql, ARRAY_A);
    $allreplymsg=[];
    if (!empty($mesages)) {
    	foreach ($mesages as $key => $value) {
    			if(strpos(trim($value['subject']," "),"Re:")!==0)
	    			{
	    				unset($mesages[$key]);
	    				continue;
	    			}
    			$avatarid = get_user_meta($value['sender_id'], "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $value['userimage'] = '<img src="'.$image.'">';
                    } else {
                        $avatar = get_avatar($value['sender_id']);
                        $checkgr = explode("//", $avatar);
                        if (strpos($checkgr[1], 'www.gravatar') !== false) {
                            $avatarimg = get_avatar_url($value['sender_id']);
                            $value['userimage'] = '<img src= "https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';
                        }else{
                            $value['userimage'] = $avatar;
                        }
                    } 
    			$value['recorded_date'] = time_elapsed_string($value['date_sent']);
    			$allreplymsg[]=$value;
    			unset($mesages[$key]);
    	}
	        $response['code'] = 0;
	        $response['msg'] = 'Fetch Message Successfully';
    		$response['allreplymsg'] = $allreplymsg;
            $response['mesages'] = $mesages;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'There is problem in Fetch Message';
        echo json_encode($response);
        exit();
    }
    exit();
}

//get Message getmsgreplies
if($getit == "getmsg"){
    $user_id = $_REQUEST['user_id'];
    $sql = "SELECT wp_bp_messages_messages.*,wp_bp_messages_recipients.user_id,wp_users.display_name as sendername,(SELECT wp_users.display_name FROM wp_users WHERE wp_users.ID= wp_bp_messages_recipients.user_id ) as reciver_name,
(SELECT unread_count FROM `wp_bp_messages_recipients` where wp_bp_messages_recipients.thread_id = wp_bp_messages_messages.thread_id and wp_bp_messages_recipients.user_id=wp_bp_messages_messages.sender_id) as sender_unreadcount,

(SELECT unread_count FROM `wp_bp_messages_recipients` t1 where t1.thread_id = wp_bp_messages_messages.thread_id and t1.user_id=wp_bp_messages_recipients.user_id) as reciver_unreadcount 

FROM `wp_bp_messages_messages` join wp_bp_messages_recipients on wp_bp_messages_recipients.thread_id = wp_bp_messages_messages.thread_id and wp_bp_messages_recipients.user_id!= wp_bp_messages_messages.sender_id 

join wp_users on wp_users.ID = wp_bp_messages_messages.sender_id 

where wp_bp_messages_messages.sender_id = '".$user_id."' OR wp_bp_messages_recipients.user_id ='".$user_id."' AND wp_bp_messages_recipients.is_deleted=0 ORDER BY wp_bp_messages_messages.date_sent DESC";
    $mesages = $wpdb->get_results($sql, ARRAY_A);
    $inbox=[];
    $sent=[];
    if (!empty($mesages)) {
    	foreach ($mesages as $key => $value) {
    		if($value['user_id']==$user_id){
    			if(strpos(trim($value['subject']," "),"Re:")===0)
	    			{
	    				unset($mesages[$key]);
	    				continue;
	    			}
    			$avatarid = get_user_meta($value['sender_id'], "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $value['userimage'] = '<img src="'.$image.'">';
                    } else {
                        $avatar = get_avatar($value['sender_id']);
                        $checkgr = explode("//", $avatar);
                        if (strpos($checkgr[1], 'www.gravatar') !== false) {
                            $avatarimg = get_avatar_url($value['sender_id']);
                            $value['userimage'] = '<img src= "https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';
                        }else{
                            $value['userimage'] = $avatar;
                        }
                    } 
    			$value['recorded_date'] = time_elapsed_string($value['date_sent']);
    			$inbox[]=$value;
    			unset($mesages[$key]);
    		} else {
    			if(strpos(trim($value['subject']," "),"Re:")===0)
	    			{
	    				unset($mesages[$key]);
	    				continue;
	    			}
    			$avatarid = get_user_meta($value['sender_id'], "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $value['userimage'] = '<img src="'.$image.'">';
                    } else {
                        $avatar = get_avatar($value['user_id']);
                        $checkgr = explode("//", $avatar);
                        if (strpos($checkgr[1], 'www.gravatar') !== false) {
                            $avatarimg = get_avatar_url($value['user_id']);
                            $value['userimage'] = '<img src= "https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';
                        }else{
                            $value['userimage'] = $avatar;
                        }
                    } 
    			$value['recorded_date'] = time_elapsed_string($value['date_sent']);
    			$sent[]=$value;
    			unset($mesages[$key]);
    		}
    	}
	        $response['code'] = 0;
	        $response['msg'] = 'Fetch Message Successfully';
    		$response['inbox'] = $inbox;
            $response['sent'] = $sent;
            $response['mesages'] = $mesages;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'There is problem in Fetch Message';
        echo json_encode($response);
        exit();
    }
    exit();
}

if ($getit == "replymsg") {
    $thread_id = $_REQUEST['thread_id'];
    $sender_id = $_REQUEST['sender']; //who reply on msg
    $reciver_id = $_REQUEST['reciver']; //who now recive reply on msg
    $subject = $_REQUEST['subject'];
    $message = $_REQUEST['message'];
    $date_sent = date('Y-m-d H:i:s');

// Array of arguments. 
	$args = array( 
	    'sender_id' => $sender_id, 
	    'thread_id' => $thread_id, 
	    //'recipients' => null, 
	    'subject' => 'Re: '.$subject, 
	    'content' => esc_textarea($message), 
	    'date_sent' => $date_sent
	); 
  
// NOTICE! Understand what this does before running. 
		$sendmsg = messages_new_message($args);
    if ($sendmsg) {
        $response['code'] = 0;
        $response['msg'] = 'Message Reply Sent Successfully';
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'Message Reply Not Sent';
        echo json_encode($response);
        exit();
    }
    exit();
}

//api to send new reply for message in app 
// if ($getit == "replymsg") {
//     $thread_id = $_REQUEST['thread_id'];
//     $sender_id = $_REQUEST['sender']; //who reply on msg
//     $reciver_id = $_REQUEST['reciver']; //who now recive reply on msg
//     $subject = $_REQUEST['subject'];
//     $message = $_REQUEST['message'];
//     $date_sent = date('Y-m-d H:i:s');
//     $data = array(
//         'thread_id' => $thread_id,
//         'sender_id' => $sender_id,
//         'subject' => 'Re: '.$subject,
//         'message' => $message,
//         'date_sent' => $date_sent
//     );

  
//     // $sendmsg = $wpdb->insert('wp_bp_messages_messages', $data);
// 	$sendmsg = $wpdb->insert("{$wpdb->prefix}bp_messages_messages", $data);
//     if ($sendmsg) {
//         $msgread = $wpdb->update('wp_bp_messages_recipients', array('sender_only'=>0, 'unread_count'=>1),
//                 array('user_id'=> $reciver_id,'thread_id' => $thread_id));
        
//          $msgreciver = $wpdb->update('wp_bp_messages_recipients', array('sender_only'=>0, 'unread_count'=>0),
//                 array('user_id'=> $sender_id,'thread_id' => $thread_id));
//         $response['code'] = 0;
//         $response['msg'] = 'Message Reply Sent Successfully';
//         echo json_encode($response);
//         exit();
//     } else {
//         $response['code'] = 1;
//         $response['msg'] = 'Message Reply Not Sent';
//         echo json_encode($response);
//         exit();
//     }
//     exit();
// }

if($getit = 'getnotification'){
    $user_id = $_REQUEST['user_id']; 
     $getlast = "SELECT * FROM {$wpdb->prefix}bp_notifications WHERE user_id = '".$user_id."'";
    $allnotification = $wpdb->get_results($getlast, ARRAY_A);
    $noti = count($allnotification);
    if ($noti) {
        $response['code'] = 0;
        $response['msg'] = 'Fetch Notification Successfully';
        echo json_encode($noti);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = 'There is problem in Fetch Notification';
        echo json_encode($noti);
        exit();
    }
    exit();
}

/*
 * //Purchase ticket
 */


exit();
/* * ******** search_event api end ************ */
?>