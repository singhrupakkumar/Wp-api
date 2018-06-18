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

if($getit=="addgroups"){
    $user_id = $_REQUEST['user_id'];
    $groupid = $_REQUEST['groupid'];
    $joinstatus = $_REQUEST['joinstatus'];
    $dattime = date('Y-m-d H:i:s');

    $table_name = $wpdb->prefix . 'bp_groups_members';
    if($joinstatus=="Leave"){
        $delete = $wpdb->query($wpdb->prepare( "DELETE FROM {$table_name} WHERE group_id = %d AND user_id = %d", $groupid, $user_id ));
            if($delete){
                $response['code']=0;
                $response['msg']="Leave group successfully";
                echo json_encode($response);
                    exit();
            } else {
                $response['code']=1;
                $response['msg']="Something went wrong";
                echo json_encode($response);
                    exit();
            }
            
    } else {
        $joined = $wpdb->insert('wp_bp_groups_members', array(
            'user_id' => $user_id,
            'group_id' => $groupid,
            'inviter_id' => 0, 
            'is_admin' => 0,
            'is_mod' => 0,
            'user_title' =>'',
            'comments' =>'',
            'is_confirmed' =>1,
            'is_banned'=>0,
            'invite_sent'=>0,
            'date_modified' => $dattime,// ... and so on
        ));
        
        if($joined){
            $response['code']=0;
            $response['msg']="Join group successfully";
            echo json_encode($response);
                exit();
        } else {
            $response['code']=1;
            $response['msg']="Something went wrong";
            echo json_encode($response);
                exit();
        }   
    }
        exit();
}

if($getit=="getjoinedgroups"){
    $user_id = $_REQUEST['user_id'];
    $groups = BP_Groups_Member::get_group_ids( $user_id, $limit, $page )[groups];
   
                $table_nm = $wpdb->prefix . 'bp_groups_members';
                $group=[];
            foreach($groups as $key=>$value){
                $id = $value;
                $dir = "../wp-content/uploads/group-avatars/".$id;
                $table_name = $wpdb->prefix . 'bp_groups';
                    $count_query = "select * from $table_name WHERE id=$id ORDER BY id DESC";
                    array_push($group,$wpdb->get_results($count_query)[0]);
    // Open a directory, and read its contents
                    if (is_dir($dir)){
                        if ($dh = opendir($dir)){
                            if (($file = scandir($dir,1)) !== false){
                                $group[$key]->filename = site_url()."/wp-content/uploads/group-avatars/".$id."/".$file[1];
                            }
                                closedir($dh);
                        }
                    } else {
                        $group[$key]->filename = 'https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm';
                    }
                $query = "select * from $table_nm WHERE group_id=$id AND user_id=$user_id";
                $groupjoined = $wpdb->get_results($query);


                    if(!empty($groupjoined)){
                        $group[$key]->groupjoined="Leave";
                    } else {
                        $group[$key]->groupjoined="Join";
                    }
                
                $totalmembercount = groups_get_groupmeta( $id, 'total_member_count');
                $group[$key]->totalmembercount = $totalmembercount;
                $lastactivity = groups_get_groupmeta( $id, 'last_activity');
                $group[$key]->lastactivity = $lastactivity;
                $group[$key]->recorded_date = time_elapsed_string($lastactivity);
            }
    if($groups){
        $response['code']=0;
        $response['data']=$group;
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

if($getit=="getgroups"){
    $user_id = $_REQUEST['user_id'];
    $table_name = $wpdb->prefix . 'bp_groups';
        $count_query = "select * from $table_name ORDER BY id DESC";
        $groups = $wpdb->get_results($count_query);
                $table_nm = $wpdb->prefix . 'bp_groups_members';
                
            foreach($groups as $key=>$value){
                $id = $value->id;
                $dir = "../wp-content/uploads/group-avatars/".$id;
                
        // Open a directory, and read its contents
                    if (is_dir($dir)){
                        if ($dh = opendir($dir)){
                            if (($file = scandir($dir,1)) !== false){
                                $groups[$key]->filename = site_url()."/wp-content/uploads/group-avatars/".$id."/".$file[1];
                            }
                                closedir($dh);
                        }
                    } else {
                        $groups[$key]->filename = 'https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm';
                    }
                $query = "select * from $table_nm WHERE group_id=$id AND user_id=$user_id";
                $groupjoined = $wpdb->get_results($query);

                    if(!empty($groupjoined)){
                        $groups[$key]->groupjoined="Leave";
                    } else {
                        $groups[$key]->groupjoined="Join";
                    }
                
                $totalmembercount = groups_get_groupmeta( $id, 'total_member_count');
                $groups[$key]->totalmembercount = $totalmembercount;
                $lastactivity = groups_get_groupmeta( $id, 'last_activity');
                $groups[$key]->recorded_date = time_elapsed_string($lastactivity);
            }
    if($groups){
        $response['code']=0;
        $response['data']=$groups;
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

if($getit=="demo"){
	echo "hello";
		$res = bp_get_activity_secondary_item_id(2360);
		print_r($res);
		exit();
}

if($getit == "getgroupinfo"){
	$group_id = $_REQUEST['group_id'];
    $login_user = $_REQUEST['user_id'];
    	$table_name = $wpdb->prefix . 'bp_groups';
        $count_query = "select * from $table_name WHERE id=$group_id ORDER BY id DESC";
        $group = $wpdb->get_results($count_query);

                $dir = "../wp-content/uploads/group-avatars/".$group_id;
                
        // Open a directory, and read its contents
                    if (is_dir($dir)){
                        if ($dh = opendir($dir)){
                            if (($file = scandir($dir,1)) !== false){
                                $group[0]->filename = site_url()."/wp-content/uploads/group-avatars/".$group_id."/".$file[1];
                            }
                                closedir($dh);
                        }
                    } else {
                        $group[0]->filename = 'https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm';
                    }
                //$query = "select * from $table_nm WHERE group_id=$id AND user_id=$user_id";
                //$groupjoined = $wpdb->get_results($query);
					 
                    $groupjoined = groups_is_user_member($login_user,$group_id);

                    if(!empty($groupjoined)){
                        $group[0]->groupjoined="Leave";
                    } else {
                        $group[0]->groupjoined="Join";
                    }

        //code to get the admin of the group            
                    $table_admin = $wpdb->prefix . 'bp_groups_members';
            $getadmin_query = "select * from $table_admin WHERE group_id=$group_id AND user_title='Group Admin' ORDER BY id DESC";
        	$getadmin_res = $wpdb->get_results($getadmin_query); 
        	$adminusrinfo=[];
        	foreach ($getadmin_res as $key => $value) {
        	   		$userdata = get_user_by('ID',$value->user_id)->data;
        	   		// code to get admin Image
        	   			$avatarid = get_user_meta($value->user_id, $key="wp_user_avatar");
				            if(!empty($avatarid)){
				                $id=$avatarid[0];
				                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
				                $image = $media[0]->guid;
				                $user_image = '<img src="'.$image.'" >';
				            } else {
				                $avatar = get_avatar($value->user_id);
				                $checkgr = explode("//", $avatar);
					                if (strpos($checkgr[1], 'www.gravatar') !== false) {
					                    $avatarimg = get_avatar_url($value->user_id);
					                    $user_image = '<img src="' . $avatarimg . '">';;
					                }else{
					                    $user_image = $avatar;
					                }
				            }
				            $userdata->userimage=$user_image;
				            $adminusrinfo=$userdata;
        	   		// end of code to get admin image
        	   }         
        //end of code to get admin user

        	// code to get posts of group members
        	   $sql = "SELECT * FROM {$wpdb->prefix}bp_activity WHERE component = 'groups' AND (type = 'rtmedia_update' OR type='activity_update') AND item_id = '" . $group_id . "' ORDER BY id DESC";
    			$posts = $wpdb->get_results($sql, ARRAY_A);
    			foreach ($posts as $key => $value) {
    					$id = $value['id'];

    				# code to get comment post user image and like count
    				$data = bp_activity_get_meta($id, "liked_count");
    				
                        $table_name = $wpdb->prefix . 'bp_activity';
                        $count_query = "select count(*) from $table_name where item_id=$id";
                        $posts[$key]['comment'] = $wpdb->get_var($count_query);
                        
                        $posts[$key]['content'] = stripslashes($value['content']);
                        $posts[$key]['recorded_date'] = time_elapsed_string($value['date_recorded']);
                            $args = array(
                                'field' => 'Your Nickname',
                                'user_id' => $value['user_id']
                            );
                            $usrnickname = get_userdata($value['user_id'])->data->display_name;
                            $posts[$key]['nickname'] = $usrnickname;
                            
                            if (array_key_exists($login_user, $data)){
                                $posts[$key]['userlike'] = 1;
                            }else{
                                $posts[$key]['userlike'] = 0;
                            }

                            if(!empty($data)) {
                                $posts[$key]['like'] = count($data);
                            } else {
                                $posts[$key]['like'] = count($activiti_data);
                            }
                            $avatarid = get_user_meta($value['user_id'], "wp_user_avatar");

                            if(!empty($avatarid)){
                                $id=$avatarid[0];
                                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                                $image = $media[0]->guid;
                                $posts[$key]['userimage'] = '<img src="'.$image.'" >';
                            } else {
                                $avatar = get_avatar($value['user_id']);
                                $checkgr = explode("//", $avatar);
                                    if (strpos($checkgr[1], 'www.gravatar') !== false) {
                                        $avatarimg = get_avatar_url($value['user_id']);
                                        $posts[$key]['userimage'] = '<img src="' . $avatarimg . '">';;
                                    }else{
                                        $posts[$key]['userimage'] = $avatar;
                                    }
                            }
    			}

        	// end of code to get posts of group members   
                $totalmembercount = groups_get_groupmeta( $group_id, 'total_member_count');
                $group[0]->totalmembercount = $totalmembercount;
                $lastactivity = groups_get_groupmeta( $group_id, 'last_activity');
                $group[0]->recorded_date = time_elapsed_string($lastactivity);
           
    if($group){
        $response['code']=0;
        $response['data']=$group[0];
        $response['adminuser']=$adminusrinfo;
        $response['activities']=$posts;
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

if ($getit == "getgroupmembers") {
    $group_id = $_REQUEST['group_id'];
    $login_user = $_REQUEST['user_id'];
    $table_name = $wpdb->prefix . 'bp_groups_members';
    $table_name1 = $wpdb->prefix . 'users';
    $query = "SELECT  * FROM {$wpdb->prefix}bp_groups_members LEFT JOIN {$wpdb->prefix}users ON {$wpdb->prefix}users.ID = {$wpdb->prefix}bp_groups_members.user_id WHERE {$wpdb->prefix}bp_groups_members.group_id = '" . $group_id . "'";
    $groups_members = $wpdb->get_results($query);
    $friends = array();

    foreach ($groups_members as $groups_member) {
        if ($groups_member->ID != '') {
            $groups_member->isfollower = bp_follow_is_following(array('leader_id' => $groups_member->user_id, 'follower_id' => $login_user)) ? "follow" : "unfollow";
            $groups_member->isfriend = friends_check_friendship_status($groups_member->user_id, $login_user);
            $groups_member->recorded_date = time_elapsed_string($groups_member->user_registered);
            //$groups_member->userimage = get_avatar($groups_member->user_id);
            $avatarid = get_user_meta($groups_member->user_id, "wp_user_avatar");
            if (!empty($avatarid)) {
                $id = $avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                $groups_member->userimage = '<img src="' . $image . '">';
            } else {
                $avatar = get_avatar($groups_member->user_id);
                $checkgr = explode("//", $avatar);
                if (strpos($checkgr[1], 'www.gravatar') !== false) {
                    $avatarimg = get_avatar_url($groups_member->user_id);
                    $groups_member->userimage = '<img src="' . $avatarimg . '">';
                }else{
                    $groups_member->userimage = $avatar;
                }
               // echo $checkgr[1];
            }
            $friends[] = $groups_member;
        }

    }
    
    if (!empty($groups_members)) {
        $response['code'] = 0;
        $response['data'] = $friends;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = "No data available";
        echo json_encode($response);
        exit();
    }
     exit();
}

//get group posts by group id
if ($getit == "getgroupmembersposts") {
    $group_id = $_REQUEST['group_id'];

    $sql = "SELECT * FROM {$wpdb->prefix}bp_activity WHERE component = 'groups' AND type = 'rtmedia_update' AND item_id = '" . $group_id . "'";
    //echo $sql;
    $activities = $wpdb->get_results($sql, ARRAY_A);
    //echo '<pre>'; print_r($activities); echo '</pre>'; die;
    if (!empty($activities)) {
        $response['code'] = 0;
        $response['data'] = $activities;
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = "No data available";
        echo json_encode($response);
        exit();
    }
    exit();
}

if($getit=="addpostfromgroup"){
    $user_id = $_REQUEST['user_id'];
    
    $mediaid  = $_REQUEST['mediaid'];
    $media_ids = explode(",", $mediaid);
    $group_id = $_REQUEST['groupid'];
    $content = $_REQUEST['content'];
    $mediacontent = explode(",",$_REQUEST['mediacontent']);
    $countmedia = count($mediacontent);
    $type = $_REQUEST['type'];
    $groupname=$_REQUEST['name'];
    $groupslug=$_REQUEST['slug'];
    $recorded_time = date('Y-m-d H:i:s');//$_REQUEST['recorded_time'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> posted an update in the group <a href='.esc_url("https://aawesomeme.com/blog/").$groupslug.'>'.esc_html($groupname).'</a>';
    $primarylink = "https://aawesomeme.com/members/".esc_html($user_nicename)."/";
    $html = '<div class="rtmedia-activity-container"><div class="rtmedia-activity-text"><span>';
        if($content!=undefined){
            $html .= esc_html($content);
        } 
        // else { 
        //     $html .= "&nbsp";
        // }
        if($location_media!=undefined){
            $location_src =  $_REQUEST['location_media'];
            $html .= '<img class="buddystream_map_image" src="' . $location_src .'" />';
        }
    $html .= '</span></div>';
    $html .="<ul class='rtmedia-list rtm-activity-media-list rtmedia-activity-media-length-";
    $html .= $countmedia;
    $html .= "'>";

    foreach($mediacontent as $key=>$value){
    if(strpos($value,"vmvideo")>0){
        $html .= '<li class="rtmedia-list-item media-type-video">
                <div class="rtmedia-item-thumbnail">
                <video src="';
                $html .= $value;
                $html .= '" width="320" height="240" class="wp-video-shortcode" id="rt_media_video_418" controls="controls" controlsList="nodownload" preload="none">
                </video>';
                    $strlen = strlen($value)-4;
                    $strpos = strpos($value,"vmvideo");
                    $substr = substr($value,$strpos,$strlen);
                    
                $html .= '</div>
                <div class="rtmedia-item-title">';
                $html .= $substr;
                $html .= '</div></a></li>';
        }
    else {
        $html .= '<li class="rtmedia-list-item media-type-photo">
            <a href="';
            $html .= $primarylink;
            $html .='">
                <div class="rtmedia-item-thumbnail">
                    <img alt="';
                    $strlen = strlen($value)-4;
                    $strpos = strpos($value,"vmmedia");
                    $substr = substr($value,$strpos,$strlen);
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
                'action'=>$action,
                'content'=>$html,
                'component'=>'groups',
                'type'=>$type,
                'primary_link'=>$primarylink,
                'user_id'=>$user_id,
                'item_id'=>$group_id,
                'recorded_time'=>$recorded_time,
            );
    
    $res = bp_activity_add($args);
    if($res){
        
        $activity_data                          = array();
		$activity_data['akismet_comment_nonce'] = 'inactive';
		$activity_data['comment_author']        = $output->data->display_name;
		$activity_data['comment_author_email']  = $output->data->user_email;
		$activity_data['comment_author_url']    = bp_core_get_userlink( $user_id, false, true);
		$activity_data['comment_content']       = " ";
		$activity_data['comment_type']          = "activity_update";
		$activity_data['permalink']             = bp_activity_get_permalink( $res );
		$activity_data['user_ID']               = $user_id;
        $activity_data['user_role']             = akismet_get_user_roles( $user_id );
        $data = serialize($activity_data);
        $event = array(
			'event'   => "check-ham",
			'message' => "Akismet cleared this item",
			'time'    => akismet_microtime(),
			'user'    => bp_loggedin_user_id(),
		);

		// Save the history data
		bp_activity_update_meta($res, '_bp_akismet_history', $event);
        bp_activity_update_meta($res,'rtmedia_privacy',0);
        bp_activity_update_meta($res,'bp_old_activity_content',$html);
        bp_activity_update_meta($res,'bp_activity_text',$content);
        bp_activity_update_meta($res,'_bp_akismet_result',false);
        bp_activity_update_meta($res,'_bp_akismet_submission',$activity_data );
        $sendreq = $wpdb->insert('wp_rt_rtm_activity', array(
            'activity_id' => $res,
            'user_id' => $user_id,
            'privacy' => 0, 
            'blog_id' => 1
        ));
        
        //Update rt Media
        if (!empty($media_ids)) {
            foreach($media_ids as $media_id){
                $table_name = $wpdb->prefix . 'rt_rtm_media';
                $update_rt_media = $wpdb->update($table_name, array('activity_id' => $res), array('media_id' => $media_id));
            }
        }
        $response['code']=0;
        $response['msg']="Media update now";
        echo json_encode($response);
            exit();
    } else {
        $response['code']=1; 
        $response['msg']="Post unsuccessful";      
        echo json_encode($response);
            exit();
    }
}

/// api to post an post from app under group /////
if($getit=="postupdategroup"){
    $user_id = $_REQUEST['user_id'];
    $content = $_REQUEST['content'];
    $type = $_REQUEST['type'];
    $groupname=$_REQUEST['name'];
    $groupslug=$_REQUEST['slug'];
    $group_id = $_REQUEST['groupid'];
    $recorded_time = date('Y-m-d H:i:s'); //$_REQUEST['recorded_time'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $primarylink = "https://aawesomeme.com/members/".$user_nicename."/";
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> posted an update in the group <a href='.esc_url("https://aawesomeme.com/blog/").$groupslug.'>'.esc_html($groupname).'</a>';
    $args = array(
                'action'=>$action,
                'content'=>$content,
                'component'=>'groups',
                'type'=>$type,
                'primary_link'=>$primarylink,
                'user_id'=>$user_id,
                'item_id'=>$group_id,
                'recorded_time'=>$recorded_time,
            );
    
    $res = bp_activity_add($args);
    if($res){
        $sendreq = $wpdb->insert('wp_rt_rtm_activity', array(
            'activity_id' => $res,
            'user_id' => $user_id,
            'privacy' => 0, 
            'blog_id' => 1
        ));
        bp_activity_update_meta($res,'rtmedia_privacy',0);
        $response['code']=0;
        $response['msg']="Post successful";
        echo json_encode($response);
            exit();
    } else {
        $response['code']=1; 
        $response['msg']="Post unsuccessful";      
        echo json_encode($response);
            exit();
    }
}


/*
 * //Purchase ticket
 */
exit();
/* * ******** search_event api end ************ */
?>