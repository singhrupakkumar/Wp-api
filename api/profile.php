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

/* * **** Get the setting info for privacy status ******** * */
if($getit == "getsettinginfo"){
    $user_id = $_REQUEST['user_id'];
    $userdata = get_user_by('ID',$user_id)->data;
        if(!empty($userdata)){
            $response['code']=0;
            $response['data']=$userdata;
        } else {
            $response['code']=1;
            $response['msg']="No data available";
        }
            echo json_encode($response);
            exit();
}

/* * **** Give permissions to other users to limit the accesibility for posts ********** * */
if($getit == "change_privacystatus"){
    $user_id = $_REQUEST['user_id'];
    $privacystatus = $_REQUEST['privacystatus'];

    $updated = $wpdb->update($wpdb->users, array('privacystatus' => $privacystatus), array('ID' => $user_id));
    
        if($updated){
            $response['code']=0;
            $response['msg']="Privacy status changed";
        } else {
            $response['code']=1;
            $response['msg']="Please try again later";
        }
            echo json_encode($response);
            exit();
}

/* * **** user Post Info api ********* */
if($getit == "userprofileinfo"){
    $user_id = $_REQUEST['user_id'];
    $app_user_id = $_REQUEST['app_user_id'];
        $response=array();
        $output = get_userdata($user_id);
        $meta = get_user_meta($user_id);
        $args=array(
            'user_id'=>$user_id
        );
        $following = [];
            $following['isfollower'] = bp_follow_is_following(array('leader_id' => $app_user_id, 'follower_id' => $user_id))? "follow": "unfollow";
            $following['isfriend'] = friends_check_friendship_status($user_id, $app_user_id);

        $table_name = $wpdb->prefix . 'posts';
        $articles = $wpdb->get_var("select count(*) from $table_name where post_type='post' and post_author=$user_id and post_status='publish'");

        //$user_image = get_avatar($user_id);
        $avatarid = get_user_meta($user_id, $key="wp_user_avatar");
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                $user_image = '<img src="'.$image.'" >';
            } else {
                $avatar = get_avatar($user_id);
                $checkgr = explode("//", $avatar);
                if (strpos($checkgr[1], 'www.gravatar') !== false) {
                    $avatarimg = get_avatar_url($user_id);
                    $user_image = '<img src="' . $avatarimg . '">';;
                }else{
                    $user_image = $avatar;
                }
                //$user_image = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
            }
        
            $args = array(
                    'field'   => 'Gender',
                    'user_id' => $user_id
                );
            $gender = bp_get_profile_field_data( $args );
        
            $args = array(
                    'field' => 'Birth Date',
                    'user_id' => $user_id
            );
            $dob = bp_get_profile_field_data( $args );

            // $args = array(
            //         'field' => 'Your Nickname',
            //         'user_id' => $user_id
            // );
            $name = $output->data->display_name; //bp_get_profile_field_data( $args );
            $pos=stripos($name, " ");
                if($pos){
                    $nickname=substr(trim($name),0,$pos);
                } else {
                    $nickname=trim($name);
                }
            $args = array(
                'field' => "What\'s your hometown?",
                'user_id' => $user_id
            );
            $hometown = bp_get_profile_field_data( $args );
            

            $count_query = friends_get_friend_user_ids($user_id);
            $friends = count($count_query);

        $groups = BP_Groups_Member::get_group_ids( $user_id, $limit, $page )[total];

        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "SELECT * FROM $table_name WHERE user_id=$user_id AND (component='activity' OR (component='blogs' AND (type='new_blog_post' AND item_id='1')) OR component='groups') AND (type!='activity_comment') ORDER BY id DESC";
        $posts = $wpdb->get_results($count_query);

        //media = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE (post_author=$user_id AND post_type='attachment' AND post_parent!='0')");
        $media = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}rt_rtm_media WHERE media_author = '".$user_id."' AND context = 'profile'");
        $activiti_data = array();   
        
            foreach ($posts as $key => $value) {
                    $id = $value->id;
                    $data = bp_activity_get_meta($id, "liked_count");
                    if($value->component=="blogs" && $value->type=="new_blog_post"&& $value->item_id==1){
                            $postcontent=get_post($value->secondary_item_id);
                            $posts[$key]->posttitle=$postcontent->post_title;
                            $posts[$key]->postcontent=$postcontent->post_content;
                        }
                    else if($value->component=="groups") {
                            $group_id=$value->item_id;
                            $table_name = $wpdb->prefix . 'bp_groups';
                            $count_query = "select * from $table_name WHERE id=$group_id ORDER BY id DESC";
                            $group = $wpdb->get_results($count_query);
                            $posts[$key]->groupInf=$group[0];
                        }
                        $table_name = $wpdb->prefix . 'bp_activity';
                        $count_query = "select count(*) from $table_name where item_id=$id";
                        $posts[$key]->comment = $wpdb->get_var($count_query);
                        $posts[$key]->content = stripslashes($value->content);
                        $posts[$key]->recorded_date = time_elapsed_string($value->date_recorded);
                                if (array_key_exists($value->user_id, $data)){
                                    $posts[$key]->userlike = 1;
                                } else {
                                    $posts[$key]->userlike = 0;
                                }
                            if(!empty($data)) {
                                $posts[$key]->like = count($data);
                            } else {
                                $posts[$key]->like = count($activiti_data);
                            }
                }

         if(!empty($output)){
            $response['code']=0;
            $response['gender']=$gender;
            $response['dob']=$dob;
            $response['hometown']=$hometown;
            $response['userinfo']=$output;
            $response['meta']=$meta;
            $response['following']=$following;
            $response['media']=count($media);
            $response['friends']=$friends;
            $response['groups']=$groups;
            $response['posts']=$posts;
            $response['nickname']=$nickname;
            $response['articles']=$articles;
            $response['user_image']=$user_image;
         }
            
        echo json_encode($response);
        exit();
}
exit();

?>