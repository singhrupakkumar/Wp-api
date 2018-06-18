<?php
require('../wp-config.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//require('../wp-content/plugins/woocommerce-gateway-stripe/includes/class-wc-gateway-stripe-addons.php');
$getit = $_REQUEST['getit'];
global $wpdb, $current_site;

if( $getit == "getreplies"){
    
    $topicid = $_REQUEST['postid'];
    $topicpostid = $_REQUEST['topicpostid'];

        $postd = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE (comment_status='closed' AND id=$topicid AND post_status='publish' AND post_type='topic') ORDER BY post_date DESC");
        $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE (comment_status='closed' AND post_parent=$topicid AND post_status='publish' AND post_type='reply') ORDER BY post_date DESC");
        
        foreach($postd as $key=>$value){
            $user_id = $value->post_author;
            $postd[$key]->content = stripslashes($value->post_content);
            $avatarid = get_user_meta($user_id, "wp_user_avatar");
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                $postd[$key]->userimage = '<img src="'.$image.'">';
            } else {
                $postd[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
            }
            $postd[$key]->userinfo = get_userdata($value->post_author);
            $postd[$key]->username = get_userdata($value->post_author)->data->display_name;
            $postd[$key]->recorded_date = time_elapsed_string($value->post_date);
        }

        foreach($posts as $key=>$value){
            $user_id = $value->post_author;
            $posts[$key]->content = stripslashes($value->post_content);
            $avatarid = get_user_meta($user_id, "wp_user_avatar");
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                $posts[$key]->userimage = '<img src="'.$image.'">';
            } else {
                $posts[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
            }
            $posts[$key]->userinfo = get_userdata($value->post_author);
            $posts[$key]->username = get_userdata($value->post_author)->data->display_name;
            $posts[$key]->recorded_date = time_elapsed_string($value->post_date);
        }
            if(!empty($posts) || !empty($postd)){
                if(!empty($posts) && !empty($postd)){
                    foreach($posts as $k=>$v){
                        array_push($postd,$v);
                    }
                } elseif(!empty($posts)){
                    $postd=$posts;
                }
                    $response['code']=0;
                    $response['data']=$postd;
            } else {
                $response['code']=1;
                $response['msg']="Comments unavailable for this post";
            }
            echo json_encode($response);
            exit();
}

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

/* * **** Get comments of Logged In user posts ********** */
if($getit == "getforums"){
          $forums = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE (comment_status='closed' AND post_parent='0' AND ping_status='open' AND post_status='publish' AND post_type='forum') ORDER BY menu_order ASC, post_title ASC");
    
    if(!empty($forums)){
            foreach($forums as $key=>$val)
                {
                    $id = $val->ID;
                    $countopics=0;
                    $title="";
                    $forums[$key]->replycount = get_post_meta( $id, '_bbp_total_reply_count' )[0] + get_post_meta( $id, '_bbp_total_topic_count' )[0];
                    $forums[$key]->countopics = get_post_meta( $id, '_bbp_total_topic_count' )[0];
                    $forums[$key]->countforum = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE (post_parent=$id AND post_status='publish' AND post_type='forum')");
                    $forums[$key]->topics = $wpdb->get_results("SELECT ID,post_parent,post_title FROM $wpdb->posts WHERE (comment_status='closed' AND post_parent=$id AND ping_status='open' AND post_status='publish' AND post_type='forum')");
                    foreach($forums[$key]->topics as $k=>$v){
                        $idd=$forums[$key]->topics[$k]->ID;
                        $total_post = '';
                        $total_post = get_post_meta( $idd, '_bbp_total_reply_count' )[0]; //+ get_post_meta( $idd, '_bbp_total_topic_count' )[0];
                        $forums[$key]->topics[$k]->total_post=$total_post;
                        //$countopics = $wpdb->get_results("SELECT count(*) FROM $wpdb->posts WHERE (post_parent=$idd AND post_status='publish' AND post_type='topic')");
                        $forums[$key]->topics[$k]->counreply = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE (post_parent=$idd AND post_status='publish' AND post_type='reply')");
                        
                        $countopics = get_post_meta( $idd, '_bbp_total_topic_count' )[0]; //$countopics+$wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE (post_parent=$idd AND post_status='publish' AND post_type='topic')");
                        $title=$forums[$key]->topics[$k]->post_title.'('.$countopics.','.$total_post.')'.', '.$title;
                    }
                    $lastactiveid = get_post_meta( $id,'_bbp_last_active_id' )[0];
                    $postdata = get_post($lastactiveid);
                   
                    $user_id = $postdata->post_author;
            
                    $avatarid = get_user_meta($user_id, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $forums[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                        $forums[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
                    }
                    $forums[$key]->recorded_date = time_elapsed_string($postdata->post_date);
            // $posts[$key]->userinfo = get_userdata($value->post_author);_bbp_total_topic_count
                    $forums[$key]->username = get_userdata($user_id)->data->display_name;
                    $forums[$key]->topiccount = get_post_meta( $id, '_bbp_total_topic_count' )[0];
                    $forums[$key]->topic = $title;
                }
        $response['code'] = 0;
        $response['data'] = $forums;
    } else {
        $response['code'] = 1;
        $response['msg'] = "No data available";
    }
    echo json_encode($response);
    exit();
}

/* * **** Get comments of Logged In user posts ********** */
if($getit == "getsubforums"){
        $postid=$_REQUEST['postid'];
    $forums = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE (comment_status='closed' AND post_status='publish' AND post_parent=$postid AND post_type='forum') ORDER BY menu_order ASC, post_title ASC");

    if(!empty($forums)){
            foreach($forums as $key=>$val)
                {
                    $id = $val->ID;
                    $countopics=0;
                    $title="";
                    $lastactiveid = get_post_meta( $id,'_bbp_last_active_id' )[0];
                    $postdata = get_post($lastactiveid);
                   
                    $user_id = $postdata->post_author;
            
                    $avatarid = get_user_meta($user_id, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $idd=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$idd AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $forums[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                        $forums[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
                    }
                    $forums[$key]->recorded_date = time_elapsed_string($postdata->post_date);
            // $posts[$key]->userinfo = get_userdata($value->post_author);_bbp_total_topic_count
                    $forums[$key]->username = get_userdata($user_id)->data->display_name;
                    $forums[$key]->replycount = get_post_meta( $id, '_bbp_total_reply_count' )[0]+get_post_meta( $id, '_bbp_total_topic_count' )[0];
                    $forums[$key]->countforum = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE (post_parent=$id AND post_status='publish' AND post_type='topic')");
                    $forums[$key]->topics = $wpdb->get_results("SELECT ID,post_parent,post_title FROM $wpdb->posts WHERE (post_parent=$id AND post_status='publish' AND post_type='topic')");
                }
        $response['code'] = 0;
        $response['data'] = $forums;
    } else {
        $response['code'] = 1;
        $response['msg'] = "No data available";
    }
    echo json_encode($response);
    exit();
}

if($getit=="inserttopic"){
    $title = $_REQUEST['title'];
    $content = $_REQUEST['content'];
    $postid = $_REQUEST['postid'];
    $user_id = $_REQUEST['user_id'];
    $forumid = $_REQUEST['forumid'];
    $recorded_time = date('Y-m-d H:i:s');
    $postname = str_replace(" ","-",$title);
    $datime = date('Y-m-d H:i:s');
    $event = site_url()."/forums/topic/".$postname."/";
    $output = get_userdata($user_id);
    $displayname = $output->data->display_name;
    $useremail = $output->data->user_email;
    $user_login = $output->data->user_login;
    $author_url = $output->data->user_url;
    $user_nicename = $output->data->user_nicename; 
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> posted a new activity comment';
    $args = array(
        'user_id' => $user_id,
        'component' => 'activity',
        'type' => 'activity_comment',
        'content' => $title,
        'action' => $action,
        'primary_link' => $primarylink,
        'item_id' => $postid,
        'secondary_item_id' => $postid,
        'date_recorded' => $postdate);
        
    $insert = bp_activity_add($args);
    
    $posts = array(
        'post_author' => $user_id,
        'post_content' => $content,
        'post_date' => $datime,
        'post_date_gmt' => $datime,
        'post_modified' => $datime,
        'post_modified_gmt' => $datime,
        'post_content_filtered' => '',
        'post_title' => $title,
        'post_name' => $postname,
        'post_excerpt' => '',
        'post_status' => 'publish',
        'post_type' => 'topic',
        'post_mime_type' => '',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_parent' => $postid,
        'menu_order' => 0,
        'guid' => $event,
    );
    $post_id = wp_insert_post($posts);
    $totalrepl=get_post_meta( $forumid, '_bbp_total_topic_count' )[0];
        update_post_meta( $forumid, '_bbp_total_topic_count', $totalrepl+1 );
    $action2 = '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '" rel="nofollow">'. esc_html($user_login).'</a> started the topic';
        update_post_meta($post_id,'_is_video','');
        update_post_meta($post_id,'_bbp_akismet_result','false');
        update_post_meta($post_id,'_bbp_forum_id',$postid);
        update_post_meta($post_id,'_bbp_topic_id',$post_id);
        update_post_meta($post_id,'_bbp_last_reply_id',0);
        update_post_meta($post_id,'_bbp_last_active_id',$post_id);
        update_post_meta($post_id,'_bbp_last_active_time',$datime);
        update_post_meta($post_id,'_bbp_reply_count',0);
        update_post_meta($post_id,'_bbp_topic_count',1);
        update_post_meta($post_id,'_bbp_reply_count_hidden',0);
        update_post_meta($post_id,'_bbp_voice_count',1);
        update_post_meta($post_id,'_bbp_total_topic_count',1);
        update_post_meta($post_id,'_bbp_activity_id',$activity_id);
        $topicount=get_post_meta( $postid, '_bbp_total_topic_count' )[0];
        update_post_meta($postid, '_bbp_total_topic_count',$topicount+1);
        $postdate = date('Y-m-d H:i:s');

        $args2 = array(
            'user_id' => $user_id,
            'component' => 'bbpress',
            'type' => 'bbp_topic_create',
            'content' => $content,
            'action' => $action2,
            'primary_link' => $primarylink,
            'item_id' => $postid,
            'secondary_item_id' => $post_id,
            'date_recorded' => $postdate);
            $insert2 = bp_activity_add($args2);
            $activity_data                          = array();
            $activity_data['akismet_comment_nonce'] = 'inactive';
            $activity_data['comment_author']        = $output->data->display_name;
            $activity_data['comment_author_email']  = $output->data->user_email;
            $activity_data['comment_author_url']    = bp_core_get_userlink( $user_id, false, true);
            $activity_data['comment_content']       = $content;
            $activity_data['comment_type']          = "activity_comment";
            $activity_data['permalink']             = bp_activity_get_permalink( $insert );
            $activity_data['user_ID']               = $user_id;
            $activity_data['user_role']             = akismet_get_user_roles( $user_id );
            $data = serialize($activity_data);
            bp_activity_update_meta($insert,'_bp_akismet_submission',$activity_data );
            if($insert2){
                $response['code'] = 0;
                $response['msg'] = "Topic added successfully";
            } else {
                $response['code'] = 1;
                $response['msg'] = "Something went wrong";
            }
                echo json_encode($response);
                exit();
}

if($getit=="inserttopicreply"){
    $reply = $_REQUEST['reply'];
    $forumid = $_REQUEST['forumid'];
    $newforumid = $_REQUEST['newforumid'];
    $parentid = $_REQUEST['parentid'];
    $user_id = $_REQUEST['user_id'];
    $topictitle = str_replace(" ","-",$_REQUEST['topictitle']);
    $recorded_time = date('Y-m-d H:i:s');
    $postname = str_replace(" ","-",$title);
    $datime = date('Y-m-d H:i:s');
   
    $output = get_userdata($user_id);
    $displayname = $output->data->display_name;
    $useremail = $output->data->user_email;
    $user_login = $output->data->user_login;
    $author_url = $output->data->user_url;
    $user_nicename = $output->data->user_nicename; 
    $action = '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '" rel="nofollow">'. esc_html($user_login).'</a> replied to the topic';
    $action .= '<a href="' . esc_url('https://aawesomeme.com/forums/topic/'. $topictitle).'" > '.esc_html($topictitle).'</a> in the forum';
    $posts = array(
        'post_author' => $user_id,
        'post_content' => $reply,
        'post_date' => $datime,
        'post_date_gmt' => $datime,
        'post_modified' => $datime,
        'post_modified_gmt' => $datime,
        'post_content_filtered' => '',
        'post_title' => '',
        'post_name' =>'',
        'post_excerpt' => '',
        'post_status' => 'publish',
        'post_type' => 'reply',
        'post_mime_type' => '',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_parent' => $parentid,
        'menu_order' => 1,
        'guid' => '',
    );
    $post_id = wp_insert_post($posts);
    $event = esc_url(site_url()).esc_html("/forums/reply/".$post_id."/");
    $primarylink = esc_url(site_url()).esc_html("/forums/topic/".$topictitle."/#post-".$post_id);
    $my_post = array(
        'ID'          => $post_id,
        'post_name'   => $post_id,
        'guid' => $event,
    );
  
    $totalrepl=get_post_meta( $forumid, '_bbp_total_reply_count' )[0];
        update_post_meta( $forumid, '_bbp_total_reply_count', $totalrepl+1 );
        update_post_meta( $forumid, '_bbp_last_active_id', $post_id);
        update_post_meta( $post_id, '_bbp_forum_id', $newforumid);
        update_post_meta( $post_id, '_bbp_topic_id', $parentid);
        // Update the post into the database 
        wp_update_post( $my_post );
        update_post_meta($post_id,'_bbp_akismet_as_submitted','');
        $postdate = date('Y-m-d H:i:s');
        $replycount = get_post_meta( $parentid, '_bbp_reply_count' )[0];
        $lastactiveid = update_post_meta( $parentid,'_bbp_last_active_id',$post_id );
        update_post_meta($parentid, '_bbp_reply_count', $replycount+1);
        $args = array(
            'user_id' => $user_id,
            'component' => 'bbpress',
            'type' => 'bbp_reply_create',
            'content' => $reply,
            'action' => $action,
            'primary_link' => $primarylink,
            'item_id' => $post_id,
            'secondary_item_id' => $parentid,
            'date_recorded' => $postdate);
            
        $insert = bp_activity_add($args);
            if($insert){
                update_post_meta( $post_id, '_bbp_activity_id', $insert);
                
                $response['code'] = 0;
                $response['msg'] = "Topic reply successfully";
            } else {
                $response['code'] = 1;
                $response['msg'] = "Something went wrong";
            }
                echo json_encode($response);
                exit();
}

if($getit == "gettopics"){
    $postid=$_REQUEST['postid'];
    $topics = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE (post_status='publish' AND post_parent=$postid AND post_type='topic') ORDER BY ID DESC");

    if(!empty($topics)){
        foreach($topics as $key=>$val){
            $id=$topics[$key]->ID;
            $user_id = $val->post_author;
            $topics[$key]->username = get_userdata($user_id)->data->display_name;
            $topics[$key]->replycount = get_post_meta( $id, '_bbp_reply_count' )[0]+1;
            $lastactiveid = get_post_meta( $id,'_bbp_last_active_id' )[0];
                    $postdata = get_post($lastactiveid);
                   
                    $user_idd = $postdata->post_author;
            
                    $avatarid = get_user_meta($user_idd, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $idd=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$idd AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $topics[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                        $topics[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
                    }
                    $topics[$key]->recorded_date = time_elapsed_string($postdata->post_date);
                     // $posts[$key]->userinfo = get_userdata($value->post_author);_bbp_total_topic_count
                    $topics[$key]->usernamer = get_userdata($user_idd)->data->display_name;
        }
        $response['code'] = 0;
        $response['data'] = $topics;
    } else {
        $response['code'] = 1;
        $response['msg'] = "No data available";
    }
    echo json_encode($response);
    exit();
}
//if($getit == "listforum"){
//     $args = array(
//		'post_parent'         => 0,
//		'post_type'           => 'forum',
//		'post_status'         => 'any',
//		'posts_per_page'      => 10,
//		'orderby'             => 'menu_order title',
//		'order'               => 'ASC',
//		'ignore_sticky_posts' => true,
//		'no_found_rows'       => true
//	);
//	$get_posts = new WP_Query($args);
//
//	// No forum passed
//	//$sub_forums = !empty( $r['post_parent'] ) ? $get_posts->query( $r ) : array();
//        
//        echo '<pre>'; print_r($get_posts->posts); echo '</pre>'; die;
//}

/*
 * //Purchase ticket
 */
exit();
/* * ******** search_event api end ************ */
?>