<?php
require('../wp-config.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//require('../wp-content/plugins/woocommerce-gateway-stripe/includes/class-wc-gateway-stripe-addons.php');
session_start();
$getit = $_REQUEST['getit'];
global $wpdb, $current_site;

//api to delete post article
if($getit=="deletearticle"){
    $postid=$_REQUEST['postid'];
    $delete = wp_delete_post( $postid, $force_delete = false );
    if($delete){
        $response['code']=0;
        $response['msg']="Article deleted successfully";
            echo json_encode($response);
                exit();
    } else {
        $response['code']=1;
        $response['msg']="Deletion failed";
            echo json_encode($response);
                exit();
    }
        exit();
}

//api to post comment in for published posts
if($getit == "publishedcomment"){
    $user_id = $_REQUEST['user_id'];
    $postid = $_REQUEST['postid'];
    $comment = $_REQUEST['comment'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $primarylink = "https://aawesomeme.com/members/".$user_nicename."/";
    $action= '<a href="https://aawesomeme.com/members/"'.$user_nicename.'"/">'.$user_login.'</a> posted an update';
    $postdate = $_REQUEST['postdate']; //"2018-01-29 08:14:56";
    
    if(!empty($output)){
            $args = array(
                'user_id' => $user_id,
                'component' => 'activity',
                'type' => 'activity_comment',
                'content' => $comment,
                'action' => $action,
                'primary_link' => $primarylink,
                'item_id' => $postid,
                'secondary_item_id' => $postid,
                'date_recorded' => $postdate);
            $insert = bp_activity_add($args);
            if($insert){
                $lastid = $wpdb->insert_id;
                $value['id']=$lastid;
                $value['content']=$comment;
                    update_user_meta($user_id, "bp_latest_update", $value);
                    $response['code']=0;
                    $response['msg']="Comment successfull";
                    echo json_encode($response);
                    exit();
            } else {
                    $response['code']=1;
                    $response['msg']="Comment unsuccessfull";
                    echo json_encode($response);
                    exit();
            }
    }
    exit();
}

////api to get particular article information 
if($getit == "termcategorybyid"){
    $user_id=$_REQUEST['user_id'];
    $postid=$_REQUEST['postid'];
    $postype=$_REQUEST['posttype'];
    $terms=array();
    $category=array();
    $terms = get_terms( array(
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
    ));
    $category = get_terms( array(
        'taxonomy' => 'category',
        'hide_empty' => false,
    ));
//    $args = array('post_type'=>'post',
//                  'author'=>$user_id,
//                  'post_status'=>$postype,
//                  'numberposts'=>1,
//                  'ID'=>$postid);
//    echo '<pre>'; print_r($args);
    //$posts = get_posts($args);
    $posts[] = get_post($postid, $output  = OBJECT);
   //echo '<pre>'; print_r($posts); die;
        foreach($posts as $k=>$val){
                $arra=array(
                    'component'=>"blogs",
                    'secondary_item_id'=>$val->ID,
                    'type'=>"new_blog_post",
                    'user_id'=>$user_id
                    );
                $valid=$val->ID;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where user_id=$user_id and type='new_blog_post' and component='blogs' and secondary_item_id=$valid ORDER BY id DESC";
        $activity = $wpdb->get_results($count_query);
                $data=$activity[0]->id;
                $data2=$activity[0]->content;
            $posts[$k]->activityid = $data;  
            $posts[$k]->activityname = $data2;
            $posts[$k]->posttag = wp_get_object_terms( $val->ID, 'post_tag' );
            $posts[$k]->category = wp_get_object_terms( $val->ID, 'category' );
            //echo get_post_thumbnail_id( $val->ID) ;
            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $val->ID ), 'single-post-thumbnail' );
//            echo '<pre>'; print_r($image);
            if($image[0] != ''){
                $posts[$k]->image = $image[0];
            }else{
                $posts[$k]->image = site_url() . '/wp-content/uploads/noimg.png';
            }
        }

    if(!empty($terms) || !empty($category)){
        $response['code'] = 0; 
        $response['terms'] = $terms;
        $response['category'] = $category;
        $response['posts'] = $posts; 
        echo json_encode($response);
                    exit();
    } else {
        $response['code'] = 0; 
        $response['terms'] = $terms;
        $response['category'] = $category; 
        echo json_encode($response);
                    exit();
    }
    exit();
}

////api to get drafts and published post with terms and categories 
if($getit == "termcategory"){
    $user_id=$_REQUEST['user_id'];
    $terms=array();
    $category=array();
    $terms = get_terms( array(
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
    ));
    $category = get_terms( array(
        'taxonomy' => 'category',
        'hide_empty' => false,
    ));
    $args = array('post_type'=>'post',
                  'author'=>$user_id,
                  'post_status'=>'draft',
                  'numberposts'=>31);

    $args2 = array('post_type'=>'post',
                   'author'=>$user_id,
                   'post_status'=>'publish',
                   'numberposts'=>31); 
    
    $draft = get_posts($args);
    $posts = get_posts($args2);
//echo '<pre>'; print_r($posts); echo '</pre>'; die();
        foreach($draft as $key=>$value){
            $draft[$key]->posttag = wp_get_object_terms( $value->ID, 'post_tag' );
            $draft[$key]->category = wp_get_object_terms( $value->ID, 'category' );
            $dimage = wp_get_attachment_image_src( get_post_thumbnail_id( $value->ID ), 'single-post-thumbnail' );
            if($dimage[0] != ''){
                $draft[$key]->image = $dimage[0];
            }else{
                $draft[$key]->image = site_url() . '/wp-content/uploads/noimg.png';
            }
        }
        foreach($posts as $k=>$val){
                $arra=array(
                    'component'=>"blogs",
                    'secondary_item_id'=>$val->ID,
                    'type'=>"new_blog_post",
                    'user_id'=>$user_id
                    );
                $valid=$val->ID;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where user_id=$user_id and type='new_blog_post' and component='blogs' and secondary_item_id=$valid ORDER BY id DESC";
        $activity = $wpdb->get_results($count_query);
                $data=$activity[0]->id;
                $data2=$activity[0]->content;
                $table_name = $wpdb->prefix . 'bp_activity';
                    $count_query = "select count(*) from $table_name where item_id=$data";
                    $posts[$k]->comment = $wpdb->get_var($count_query);
            $posts[$k]->activityid = $data;  
            $posts[$k]->activityname = $data2;
            $posts[$k]->posttag = wp_get_object_terms( $val->ID, 'post_tag' );
            $posts[$k]->category = wp_get_object_terms( $val->ID, 'category' );
            //echo "postid=" . $val->ID . "=" . get_post_thumbnail_id( $val->ID );
            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $val->ID ), 'single-post-thumbnail' );
//            echo '<pre>'; print_r($image);
            if($image[0] != ''){
                $posts[$k]->image = $image[0];
            }else{
                $posts[$k]->image = site_url() . '/wp-content/uploads/noimg.png';
            }
        }
//die;
    if(!empty($terms) || !empty($category)){
        $response['code'] = 0; 
        $response['terms'] = $terms;
        $response['category'] = $category;
        $response['draft'] = $draft;
        $response['posts'] = $posts; 
        echo json_encode($response);
                    exit();
    } else {
        $response['code'] = 0; 
        $response['terms'] = $terms;
        $response['category'] = $category; 
        echo json_encode($response);
                    exit();
    }
    exit();
}

///api to upload image or video in app
if ($getit == "uploadimgvdo") {
    
    $img = "";
    $user_id = "";
    $im = "";
    $datime = "";
    $filename = "";

    if ($_REQUEST['imagepath'] != undefined && $_FILES['file']['type'] != "multipart/form-data" && $_REQUEST['imagepath'] != '') {
        $user_id = $_REQUEST['user_id'];
        $img = base64_decode($_REQUEST['imagepath']);
        $im = imagecreatefromstring($img);
        $wp_upload_dir = wp_upload_dir();
        $datime = date('Y-m-d H:i:s'); //$_REQUEST['updatetime'];
        $filename = "vmmedia" . time() . mt_rand() . ".png";
        if (!file_exists($wp_upload_dir['basedir'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m"))) {
            mkdir($wp_upload_dir['basedir'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m"), 0777, true);
        }
        $path_to_file = $wp_upload_dir['basedir'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m") . "/" . $filename;
        $filetype = wp_check_filetype(basename($filename), null);
        //$file_type = explode($filetype['type'], '/');
        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }

        @file_put_contents($path_to_file, $img);
        $attachment = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_date' => $datime,
            'post_date_gmt' => $datime,
            'post_modified' => $datime,
            'post_modified_gmt' => $datime,
            'post_content_filtered' => '',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_name' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_excerpt' => '',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => $filetype['type'],
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 13,
            'menu_order' => 0,
            'guid' => $wp_upload_dir['baseurl'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m") . "/" . $filename,
        );

        $wpdb->insert("{$wpdb->prefix}posts", $attachment);
        $attach_id = $wpdb->insert_id;
        if ($attach_id == false) {
            //echo "false";
            return false;
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $path_to_file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        $update = update_attached_file($attach_id, $path_to_file);
        $media = array(
            'blog_id' => 1,
            'media_id' => $attach_id,
            'media_author' => $user_id,
            'media_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'album_id' => 1,
            'media_type' => 'photo',
            'context' => 'profile',
            'context_id' => $user_id,
            'source' => 'NULL',
            'source_id' => 'NULL',
            'activity_id' => 'NULL',
            'cover_art' => 'NULL',
            'privacy' => 0,
            'views' => 0,
            'downloads' => 0,
            'ratings_total' => 0,
            'ratings_count' => 0,
            'ratings_average' => 0.00,
            'likes' => 0,
            'dislikes' => 0,
            'upload_date' => $datime,
            'file_size' => $_FILES['file']['size']
        );
        $wpdb->insert("{$wpdb->prefix}rt_rtm_media", $media);
        if ($update) {
            $response['code'] = 0;
            $response['data'] = $_REQUEST;
            $response['mediaid'] = $attach_id;
            $response['urlimgvdo'] = $wp_upload_dir['baseurl'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m") . "/" . $filename;
            echo json_encode($response);
            exit();
        }
    } else {
        $filename = $_FILES['file']['name'];
        $user_id = $_REQUEST['user_id'];
        $wp_upload_dir = wp_upload_dir();
        $imageName = $_FILES['file']['name'];
        
        if (!file_exists($wp_upload_dir['basedir'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m"))) {
            mkdir($wp_upload_dir['basedir'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m"), 0777, true);
        }
        $pos = strripos($filename, ".") + 1;
        
        $len = strlen($filename);
        $fileext = substr($filename, $pos, $len);
        $datime = date('Y-m-d H:i:s'); //$_REQUEST['updatetime'];
        $newfilename = "vmvideo".round(microtime(true)) . mt_rand() . '.' . $fileext;
        $path_to_file = $wp_upload_dir['basedir'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m") . '/' . $newfilename;
        
        move_uploaded_file($_FILES['file']['tmp_name'], $path_to_file);

       $filetype = wp_check_filetype(basename($newfilename), null);
       $file_type = explode($filetype['type'], 'video');
       
        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }
        //@file_put_contents($path_to_file, $img);
            
        $attachment = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_date' => $datime,
            'post_date_gmt' => $datime,
            'post_modified' => $datime,
            'post_modified_gmt' => $datime,
            'post_content_filtered' => '',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_name' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_excerpt' => '',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => "video/" . $fileext,
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 13,
            'menu_order' => 0,
            'guid' => $wp_upload_dir['baseurl'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m") . "/" . $newfilename,
        );
        $wpdb->insert("{$wpdb->prefix}posts", $attachment);
        $attach_id = $wpdb->insert_id;
        if ($attach_id == false) {
            //echo "false";
            return false;
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $path_to_file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        $update = update_attached_file($attach_id, $path_to_file);
        
        $media = array(
            'blog_id' => 1,
            'media_id' => $attach_id,
            'media_author' => $user_id,
            'media_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'album_id' => 1,
            'media_type' => $file_type[0],
            'context' => 'profile',
            'context_id' => $user_id,
            'source' => 'NULL',
            'source_id' => 'NULL',
            'activity_id' => 'NULL',
            'cover_art' => 'NULL',
            'privacy' => 0,
            'views' => 0,
            'downloads' => 0,
            'ratings_total' => 0,
            'ratings_count' => 0,
            'ratings_average' => 0.00,
            'likes' => 0,
            'dislikes' => 0,
            'upload_date' => $datime,
            'file_size' => $_FILES['file']['size']
        );
        $minsert = $wpdb->insert("wp_rt_rtm_media", $media);

        if ($update) {
            $response['code'] = 0;
            $response['data'] = $_REQUEST;
            $response['mediaid'] = $attach_id;
            $response['urlimgvdo'] = $wp_upload_dir['baseurl'] . "/rtMedia/users/" . $user_id . "/" . date("Y") . "/" . date("m") . "/" . $newfilename;
            echo json_encode($response);
            exit();
        }
    }
}

//// api to post article as an draft ////
if($getit == "newblogdraft"){
    $user_id= $_REQUEST['user_id'];
    $title = $_REQUEST['title'];
    $content = $_REQUEST['content'];

    $categories = $_REQUEST['categories'];
    $tags = explode(",",$_REQUEST['tags']);
    $output = get_userdata($user_id);
    $recorded_time = date('Y-m-d H:i:s');
    $datime = date('Y-m-d H:i:s');
    $postname = str_replace(" ","-",$title);
    $event = site_url()."/".$postname;
    $user_nicename = $output->data->user_nicename;
    $user_displayname = $output->data->display_name;
    
    if ($_REQUEST['image']) {
        $img = base64_decode($_REQUEST['image']);
        $im = imagecreatefromstring($img);
        $wp_upload_dir = wp_upload_dir();
        $filename = "sa" . time() . ".png";
        $path_to_file = $wp_upload_dir['path'] . "/" . $filename;
        $filetype = wp_check_filetype(basename($filename), null);
        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }
        @file_put_contents($path_to_file, $img);
        $attachment = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_content_filtered' => '',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_name' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_excerpt' => '',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => $filetype['type'],
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'guid' => $wp_upload_dir['url'] . '/' . $filename,
        );
        $wpdb->insert("{$wpdb->prefix}posts", $attachment);
        $attach_id = $wpdb->insert_id;
        if ($attach_id == false) {
            //echo "false";
            return false;
        }
    }
    
    
    $posts = array(
        'post_author' => $user_id,
        'post_content' => $content,
        'post_date' => $datime,
        'post_date_gmt' => "0000-00-00 00:00:00",
        'post_modified' => $datime,
        'post_modified_gmt' => $datime,
        'post_content_filtered' => '',
        'post_title' => $title,
        'post_name' => $postname,
        'post_excerpt' => '',
        'post_status' => 'draft',
        'post_type' => 'post',
        'post_mime_type' => '',
        'comment_status' => 'open',
        'ping_status' => 'open',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'comment_count' => 0,
        'post_parent' => 0,
        'menu_order' => 0,
        'guid' => $event,
    );
    $postid = wp_insert_post($posts);
        if($postid){

                add_post_meta( $postid, "_wp_old_slug", $postname, $unique = false );
                
                wp_set_object_terms( $postid, $tags, "post_tag");
                wp_add_object_terms( $postid, $categories, "category");
                if(isset($attach_id)){
                    add_post_meta( $postid, "_thumbnail_id", $attach_id, $unique = false );
                 $attach_data = wp_generate_attachment_metadata($attach_id, $path_to_file);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    $update = update_attached_file($attach_id, $path_to_file);
                } 
                

        $response['code']=0;
            $response['msg']="Article saved successfully";
            echo json_encode($response);
            exit();
        } else {
            $response['code']=1;
            $response['msg']="Something went wrong";
            echo json_encode($response);
            exit();
        }
}

// api to update publish article //
if($getit=="publisharticleupdate"){ 

	// $myfile1 = fopen("rahulnewfile1.txt", "a+") or die("Unable to open file!");
	// 		fwrite($myfile1, print_r($_REQUEST, true));
	// die;		
    $user_id= $_REQUEST['user_id'];
    $title = $_REQUEST['title'];
    $content = $_REQUEST['content'];
    $categories = $_REQUEST['categories'];
    $activityid = $_REQUEST['activityid'];
    $postid = $_REQUEST['postid'];
   $tags = explode(",",$_REQUEST['tags']);
    $output = get_userdata($user_id);
    $recorded_time = date('Y-m-d H:i:s');
    $datime = date('Y-m-d H:i:s');
    $postname = str_replace(" ","-",$title);
    $user_nicename = $output->data->user_nicename;
    $primarylink = site_url()."/"."members/".esc_html($user_nicename)."/";
    
            $args = array(
            'action'=>$action,
            'content'=>$content,
            'component'=>'blogs',
            'type'=>"new_blog_post",
            'primary_link'=>$primarylink,
            'user_id'=>$user_id,
            'recorded_time'=>$recorded_time,
            );
            $event = site_url()."/".$postname;
                    $res = bp_activity_add($args);
        bp_activity_update_meta($res, 'post_url', $event);
        
        
        if ($_REQUEST['image'] != '') { 
	        $img = base64_decode($_REQUEST['image']);
	        
	        $im = imagecreatefromstring($img);
	        $wp_upload_dir = wp_upload_dir();
	        $filename = "sa" . time() . ".png";
	        $path_to_file = $wp_upload_dir['path'] . "/" . $filename;
	        
	        $filetype = wp_check_filetype(basename($filename), null);
	        if (!function_exists('wp_handle_upload')) {
	            require_once( ABSPATH . 'wp-admin/includes/file.php' );
	            require_once( ABSPATH . 'wp-admin/includes/image.php' );
	        }
	        @file_put_contents($path_to_file, $img);
	        $attchid = get_post_thumbnail_id($postid);
				$sql ="UPDATE wp_posts  SET `guid`= '".$wp_upload_dir['url'] . '/' . $filename."'	WHERE  `ID` = '".$attchid."'";

				$updateattachment = $wpdb->query($sql);
				
	             $attach_data = wp_generate_attachment_metadata($attchid, $path_to_file);
        wp_update_attachment_metadata($attchid, $attach_data);

        $update = update_attached_file($attchid, $path_to_file);         

        }

        $posts = array(
        'ID' => $postid,
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
        'post_type' => 'post',
        'post_mime_type' => '',
        'comment_status' => 'open',
        'ping_status' => 'open',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_parent' => 0,
        'menu_order' => 0,
        'guid' => $event,
    );
    $posti = wp_update_post($posts);

        if($postid){
                add_post_meta( $postid, "_wp_old_slug", $postname, $unique = false );
                wp_set_object_terms( $postid, $tags, "post_tag");
                wp_set_object_terms( $postid, $categories, "category");

            $args = array(
                'user_id' => 307,
                'item_id' => $postid,
                'secondary_item_id' => $user_id,
                'component_name' => 'social_articles',
                'component_action' => "new_article$postid",
                'date_notified' => bp_core_current_time(),
                'is_new' => 1, );
                $argss = array(
                    'user_id' => $user_id,
                    'item_id' => $postid,
                    'secondary_item_id' => -1,
                    'component_name' => 'social_articles',
                    'component_action' => "new_article$postid",
                    'date_notified' => bp_core_current_time(),
                    'is_new' => 1, );
            $notification_id = bp_notifications_add_notification( $args );
            bp_notifications_add_notification( $argss );
            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );
            
            $response['code']=0;
            $response['msg']="Article post successfully";
            echo json_encode($response);
            exit();
        } else {
            $response['code']=1;
            $response['msg']="Something went wrong";
            echo json_encode($response);
            exit();
        }
}

// api to update article activity ////
if($getit == "newpostupdate"){
    $user_id= $_REQUEST['user_id'];
    $title = $_REQUEST['title'];
    $content = $_REQUEST['content'];
    $categories = $_REQUEST['categories'];
    $activityid = $_REQUEST['activityid'];
    $postid = $_REQUEST['postid'];
    $postype = $_REQUEST['posttype'];
    $tags = explode(",",$_REQUEST['tags']);
    $output = get_userdata($user_id);
    $recorded_time = date('Y-m-d H:i:s');
    $datime = date('Y-m-d H:i:s');
    $postname = str_replace(" ","-",$title);
    $user_nicename = $output->data->user_nicename;
    $primarylink = site_url()."/"."members/".esc_html($user_nicename)."/";

    if ($_REQUEST['image'] != '') { 
            $img = base64_decode($_REQUEST['image']);
            
            $im = imagecreatefromstring($img);
            $wp_upload_dir = wp_upload_dir();
            $filename = "sa" . time() . ".png";
            $path_to_file = $wp_upload_dir['path'] . "/" . $filename;
            
            $filetype = wp_check_filetype(basename($filename), null);
            if (!function_exists('wp_handle_upload')) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
            }
            @file_put_contents($path_to_file, $img);
            $attchid = get_post_thumbnail_id($postid);
                $sql ="UPDATE wp_posts  SET `guid`= '".$wp_upload_dir['url'] . '/' . $filename."'   WHERE  `ID` = '".$attchid."'";

                $updateattachment = $wpdb->query($sql);
                
                 $attach_data = wp_generate_attachment_metadata($attchid, $path_to_file);
        wp_update_attachment_metadata($attchid, $attach_data);

        $update = update_attached_file($attchid, $path_to_file);         

        }
    
    if($postype=="publish"){ 
            $args = array(
            'action'=>$action,
            'content'=>$content,
            'component'=>'blogs',
            'type'=>"new_blog_post",
            'primary_link'=>$primarylink,
            'user_id'=>$user_id,
            'recorded_time'=>$recorded_time,
            );
            $event = site_url()."/".$postname;

            $wpdb->update('wp_bp_activity', 
                    $args,
                    array('id' => $activityid));
            bp_activity_update_meta($activityid, 'post_url', $event);
    }   
    $posts = array(
        'ID' => $postid,
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
        'post_status' => $postype,
        'post_type' => 'post',
        'post_mime_type' => '',
        'comment_status' => 'open',
        'ping_status' => 'open',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_parent' => 0,
        'menu_order' => 0,
        'guid' => $event,
    );
    $posti = wp_update_post($posts);

        if($postid){
                add_post_meta( $postid, "_wp_old_slug", $postname, $unique = false );
                wp_set_object_terms( $postid, $tags, "post_tag");
                wp_set_object_terms( $postid, $categories, "category");

                
                
            $args = array(
                'user_id' => 307,
                'item_id' => $postid,
                'secondary_item_id' => $user_id,
                'component_name' => 'social_articles',
                'component_action' => "new_article$postid",
                'date_notified' => bp_core_current_time(),
                'is_new' => 1, );
                $argss = array(
                    'user_id' => $user_id,
                    'item_id' => $postid,
                    'secondary_item_id' => -1,
                    'component_name' => 'social_articles',
                    'component_action' => "new_article$postid",
                    'date_notified' => bp_core_current_time(),
                    'is_new' => 1, );
            $notification_id = bp_notifications_add_notification( $args );
            bp_notifications_add_notification( $argss );
            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );
            
            $response['code']=0;
            $response['msg']="Article post successfully";
            echo json_encode($response);
            exit();
        } else {
            $response['code']=1;
            $response['msg']="Something went wrong";
            echo json_encode($response);
            exit();
        }
}

// api to post new blog activity ////
if($getit == "newblogpost"){
    $user_id = $_REQUEST['user_id'];
    $title = $_REQUEST['title'];
    $content = $_REQUEST['content'];
    $categories = $_REQUEST['categories'];
    $tags =explode(",",$_REQUEST['tags']);
   //$tags = array("Awesome");
    $output = get_userdata($user_id);
    $recorded_time = date('Y-m-d H:i:s');
    $datime = date('Y-m-d H:i:s');
    $postname = str_replace(" ","-",$title);
    $user_nicename = $output->data->user_nicename;
    $user_displayname = $output->data->display_name;
    $event = site_url()."/".$postname;
    
    //upload article image
    if ($_REQUEST['image']) {
        $img = base64_decode($_REQUEST['image']);
        $im = imagecreatefromstring($img);
        $wp_upload_dir = wp_upload_dir();
        $filename = "sa" . time() . ".png";
        $path_to_file = $wp_upload_dir['path'] . "/" . $filename;
        $filepath_url = $wp_upload_dir['url'] . "/" . $filename;
        $filetype = wp_check_filetype(basename($filename), null);
        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }
        @file_put_contents($path_to_file, $img);
        $attachment = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_content_filtered' => '',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_name' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_excerpt' => '',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => $filetype['type'],
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'guid' => $wp_upload_dir['url'] . '/' . $filename,
        );
        $wpdb->insert("{$wpdb->prefix}posts", $attachment);
        $attach_id = $wpdb->insert_id;
        if ($attach_id == false) {
            //echo "false";
            return false;
        }
    }


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
        'post_type' => 'post',
        'post_mime_type' => '',
        'comment_status' => 'open',
        'ping_status' => 'open',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_parent' => 0,
        'menu_order' => 0,
        'guid' => $event,
    );

    $posti = $wpdb->insert("{$wpdb->prefix}posts", $posts);
    
        if($posti){
            $postid=$wpdb->insert_id;
                add_post_meta( $postid, "_wp_old_slug", $postname, $unique = false );
                
                wp_set_object_terms( $postid, $tags, "post_tag");
                wp_set_object_terms( $postid, $categories, "category");
                if(isset($attach_id)) {
                    add_post_meta( $postid, "_thumbnail_id", $attach_id, $unique = false );
                     $attach_data = wp_generate_attachment_metadata($attach_id, $path_to_file);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    $update = update_attached_file($attach_id, $path_to_file);
                }

                
    $primarylink = site_url()."/"."members/".esc_html($user_nicename)."/";
    $action = site_url()."/"."members/".esc_html($user_nicename)."/";
   // $primarylink = site_url()."/"."members/?p=".esc_html($postid);
    //$action = site_url()."/"."members/?p=".esc_html($postid);
    //$action = '<a href="'. bloginfo("url").'"/members/"'.$user_nicename.'"/">'.$user_displayname.'</a> wrote a new post, <a href="'.site_url().'"/?p=8551">"'.$title.'"</a>';

    $content = $content .'<img src="'.$filepath_url .'"/>'; 
    $args = array(
        'action'=>$action,
        'content'=>$content,
        'component'=>'blogs',
        'type'=>"new_blog_post",
        'item_id'=>1,
        'secondary_item_id'=>$postid,
        'primary_link'=>$primarylink,
        'user_id'=>$user_id,
        'recorded_time'=>$recorded_time,
    );
        
        $res = bp_activity_add($args);
        bp_activity_update_meta($res, 'post_url', $event);

        //echo $res;
        
        //Get Post Owner all Friends
        //echo "SELECT * FROM wpdb_bp_friends WHERE initiator_user_id = '".$user_id."'" ;
            $allfriends = $wpdb->get_results( "SELECT * FROM wp_bp_friends WHERE initiator_user_id = '".$user_id."'" );
            
            foreach($allfriends as $allfriend){
                //echo $allfriend->friend_user_id.'<br>';
                $argss = array(
                    'user_id' => $allfriend->friend_user_id,
                    'item_id' => $postid,
                    'secondary_item_id' => -1,
                    'component_name' => 'social_articles',
                    'component_action' => "new_article$postid",
                    'date_notified' => bp_core_current_time(),
                    'is_new' => 1, );
            //$notification_id = bp_notifications_add_notification( $args );
            bp_notifications_add_notification( $argss );
            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );
            } //die;
//                $argss = array(
//                    'user_id' => $user_id,
//                    'item_id' => $postid,
//                    'secondary_item_id' => -1,
//                    'component_name' => 'social_articles',
//                    'component_action' => "new_article$postid",
//                    'date_notified' => bp_core_current_time(),
//                    'is_new' => 1, );
//            //$notification_id = bp_notifications_add_notification( $args );
//            bp_notifications_add_notification( $argss );
//            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );
            
            $response['code']=0;
            $response['msg']="Article post successfully";
            echo json_encode($response);
            exit();
        } else {
            $response['code']=1;
            $response['msg']="Something went wrong";
            echo json_encode($response);
            exit();
        }
}

//// api to post an update in media ////
if($getit=="postupdatertmedia"){
    $user_id = $_REQUEST['user_id'];
    
    $mediaid  = $_REQUEST['mediaid'];
    $media_ids = explode(",", $mediaid);
    
    $content = $_REQUEST['content'];
    $mediacontent = explode(",",$_REQUEST['mediacontent']);
    $countmedia = count($mediacontent);
    $type = $_REQUEST['type'];
    $recorded_time = date('Y-m-d H:i:s');//$_REQUEST['recorded_time'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> posted an update';
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
                'component'=>'activity',
                'type'=>$type,
                'primary_link'=>$primarylink,
                'user_id'=>$user_id,
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

/// api to post an post from app /////
if($getit=="postupdate"){
    $user_id = $_REQUEST['user_id'];
    $content = $_REQUEST['content'];
    $type = $_REQUEST['type'];
    $recorded_time = date('Y-m-d H:i:s'); //$_REQUEST['recorded_time'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $primarylink = "https://aawesomeme.com/members/".$user_nicename."/";
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> posted an update';
    $args = array(
                'action'=>$action,
                'content'=>$content,
                'component'=>'activity',
                'type'=>$type,
                'primary_link'=>$primarylink,
                'user_id'=>$user_id,
                'recorded_time'=>$recorded_time,
            );
    //echo '<pre>'; print_r($args); die;
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


/// api to start following the friend /////
if($getit=="followunfollow"){
    $action = $_REQUEST['action'];
    $leader_id = $_REQUEST['leader_id'];
    $follower_id = $_REQUEST['follower_id'];
    if($action=="follow"){
        $follow = bp_follow_start_following(array('leader_id' => $leader_id, 'follower_id' => $follower_id));
            $args = array(
                'user_id' => $follower_id,
                'item_id' => $leader_id,
                'secondary_item_id' => 0,
                'component_name' => 'follow',
                'component_action' => 'new_follow',
                'date_notified' => bp_core_current_time(),
                'is_new' => 1, );
            $notification_id = bp_notifications_add_notification( $args );
            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );

            $user_data = get_user_by('ID', $follower_id);
            $friend_user_data = get_user_by('ID', $leader_id);
            $user_nicename = $user_data->user_nicename;
            $user_displayname = $user_data->display_name;
            $user_email = $friend_user_data->user_email;
            $friend_nicename = $friend_user_data->user_nicename;
            $friend_displayname = $friend_user_data->display_name;
                
        $home_url = get_home_url();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $message .= '<html xmlns="http://www.w3.org/1999/xhtml"><head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Untitled Document</title>
        <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500" rel="stylesheet"> 
        </head>
        <body style="background: url(';
        $message .= $home_url."/api/img/bgplait.png";
        $message .= ') repeat #dddddd;
                margin:0px auto;
                font-weight:400;
                background-size: 160px;">
            <div class="table-responsive">
        <table width="600" border="0" cellpadding="10" cellspacing="0" style="margin:0px auto; background:#fffefb; text-align:center;">
          <tr style="background:#fff;">
            <td style="text-align:center; padding-top:20px; padding-bottom:20px; border-bottom:2px solid #ff0000;">
                <img src="';
                $message .= $home_url."/api/img/logo.png";
                $message .= '" alt="img" / style="width: 220px;">
            </td>
          </tr>
          <tr>
        <td>
        <h2 style="font-weight:500; margin-bottom:1px;">Hi '; 
        $message .= sprintf(__( '%s'), $friend_displayname) . "\r\n\r\n";
        $message .= '</h2><p>To view profile Click  
        <a style="background:#ff0000; padding:15px 20px; text-transform:uppercase;
                display:inline-block; color:#fff; border-radius: 4px; text-decoration:none;
                font-weight:500;" href="';
                $message .= site_url()."/members/" . sprintf(__( '%s'), $user_nicename) . "/friends/requests/";
                $message .='">';
                $message .= sprintf(__( '%s'), $user_displayname) . "\r\n\r\n".'</a> is now following your activity.. </p>';
                $message .= '<p>To disable these notifications please log in and go to : </p><p>';
                $message .= site_url()."/members/" . sprintf(__( '%s'), $friend_nicename) . "/settings/notifications/";
                $message .= '</p></td>
          </tr>
        </table>
            </div>
        </body>
        </html>';
            if (is_multisite())
                $blogname = $GLOBALS['current_site']->site_name;
            else
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        
            $title = sprintf(__('[%s] '.$user_displayname.' '.' is now following you ' ), $blogname);
            $title = apply_filters('retrieve_password_title', $title);
            //$message = apply_filters('retrieve_password_message', $message, $key, $user_login);
                       if(wp_mail($user_email, $title, $message, $headers)){
                       }
            $notification_id = bp_notifications_add_notification( $args );
            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );

        $response['code']=0;
        $response['follow']=$follow;
        $response['msg']="user now follow";    
        echo json_encode($response);
            exit();
    }
    else if($action=="unfollow"){
        $unfollow = bp_follow_stop_following(array('leader_id' => $leader_id, 'follower_id' => $follower_id));
        $response['code']=0;
        $response['unfollow']=$unfollow;  
        $response['msg']="user now unfollow";      
        echo json_encode($response);
            exit();
    }
    else{
        $response['code']=1;
        $response['msg']="Invalid data";      
        echo json_encode($response);
            exit();
    }
    
}

//// api to cancel friend request //////
if($getit == "cancelfriend"){
    $user_id = $_REQUEST['user_id'];
    $friend_id = $_REQUEST['friend_id'];

    $table_name = $wpdb->prefix . 'bp_friends';
    $delete = $wpdb->query($wpdb->prepare( "DELETE FROM {$table_name} WHERE initiator_user_id = %d AND friend_user_id = %d", $user_id, $friend_id ));
        if($delete){
                $response['code']=0;
                $response['msg']="Friend request cancelled";
                echo json_encode($response);
                exit();
        } else {
            $deleteag = $wpdb->query($wpdb->prepare( "DELETE FROM {$table_name} WHERE initiator_user_id = %d AND friend_user_id = %d", $friend_id, $user_id ));
             if($deleteag) {
                    $response['code']=0;
                    $response['msg']="Friend request cancelled";
                    echo json_encode($response);
                    exit();
             } else {
                    $response['code']=1;
                    $response['msg']="Friend request uncancelled";
                    echo json_encode($response);
                    exit();
             }   
        }
}

//// api to send add friend request //////
if($getit == "sendaddfriend"){
    $user_id = $_REQUEST['user_id'];
    $friend_id = $_REQUEST['friend_id'];
    $dattime  = $_REQUEST['timetocheck'];
    $table_name = $wpdb->prefix . 'bp_friends';
    $sendreq = $wpdb->insert('wp_bp_friends', array(
        'initiator_user_id' => $user_id,
        'friend_user_id' => $friend_id,
        'is_confirmed' => 0, 
        'is_limited' => 0,
        'date_created' => $dattime,// ... and so on
    ));
    
        $args = array(
            'user_id' => $friend_id,
            'item_id' => $user_id,
            'secondary_item_id' => 0,
            'component_name' => 'friends',
            'component_action' => 'friendship_request',
            'date_notified' => bp_core_current_time(),
            'is_new' => 1, );
        if($sendreq){
        $user_data = get_user_by('ID', $user_id);
            $friend_user_data = get_user_by('ID', $friend_id);
            $user_nicename = $user_data->user_nicename;
            $user_displayname = $user_data->display_name;
            $user_email = $friend_user_data->user_email;
            $friend_nicename = $friend_user_data->user_nicename;
            $friend_displayname = $friend_user_data->display_name;
                
        $home_url = get_home_url();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $message .= '<html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>Untitled Document</title>
                <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500" rel="stylesheet"> 
            </head>
        <body style="background: url(';
        $message .= $home_url."/api/img/bgplait.png";
        $message .= ') repeat #dddddd;
                margin:0px auto;
                font-weight:400;
                background-size: 160px;">
            <div class="table-responsive">
        <table width="600" border="0" cellpadding="10" cellspacing="0" style="margin:0px auto; background:#fffefb; text-align:center;">
          <tr style="background:#fff;">
            <td style="text-align:center; padding-top:20px; padding-bottom:20px; border-bottom:2px solid #ff0000;">
                <img src="';
                $message .= $home_url."/api/img/logo.png";
                $message .= '" alt="img" / style="width: 220px;">
            </td>
          </tr>
          <tr>
        <td>
        <h2 style="font-weight:500; margin-bottom:1px;">Hi '; 
        $message .= sprintf(__( '%s'), $friend_displayname) . "\r\n\r\n";
        $message .= '</h2><p>
        <a style="background:#ff0000; padding:15px 20px; text-transform:uppercase;
                display:inline-block; color:#fff; border-radius: 4px; text-decoration:none;
                font-weight:500;" href="';
                $message .= site_url()."/members/" . sprintf(__( '%s'), $user_nicename) . "/friends/requests/";
                $message .='">';
                $message .= sprintf(__( '%s'), $user_displayname) . "\r\n\r\n".'</a> wants to add you as a friend. </p>';
                $message .= '<p>To accept this request and manage all of your pending requests, visit: </p><p>';
                $message .= site_url()."/members/" . sprintf(__( '%s'), $friend_nicename) . "/friends/requests/";
                $message .= '</p></td>
          </tr>
        </table>
            </div>
        </body>
        </html>';
            if (is_multisite())
                $blogname = $GLOBALS['current_site']->site_name;
            else
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        
            $title = sprintf(__('[%s] New friendship request from '.' '.$user_displayname ), $blogname);
            $title = apply_filters('retrieve_password_title', $title);
            //$message = apply_filters('retrieve_password_message', $message, $key, $user_login);
                       if(wp_mail($user_email, $title, $message, $headers)){
                       }
            $notification_id = bp_notifications_add_notification( $args );
            add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );
                $response['code']=0;
                $response['msg']="Friend request sent";
                echo json_encode($response);
                exit();
        }else{
                $response['code']=1;
                $response['msg']="Friend request not sent";
                echo json_encode($response);
                exit();
        }
}

//// api to accept friend request //////
if($getit == "acceptfriend"){
    $user_id = $_REQUEST['user_id'];
    $friend_id = $_REQUEST['friend_id'];

    $table_name = $wpdb->prefix . 'bp_friends';
    $accept = $wpdb->update('wp_bp_friends', 
    array('is_confirmed'=>1),
    array('initiator_user_id'=> $user_id,'friend_user_id' => $friend_id));
        if($accept){
                $response['code']=0;
                $response['msg']="Friend request accepted";
                echo json_encode($response);
                exit();
        }else{
                $response['code']=1;
                $response['msg']="Something went wrong";
                echo json_encode($response);
                exit();
        }
}

//// get friends requests /////////
if($getit == "getfriendreq"){
    $user_id = $_REQUEST['user_id'];
    $frndreq = [];
    $table = $wpdb->prefix . 'bp_friends';
    $data = $wpdb->get_results("SELECT initiator_user_id FROM $table WHERE (friend_user_id=$user_id AND is_confirmed=0)");
        if(!empty($data)){
            foreach($data as $key=>$value){
                $frndreq[] = get_userdata($value->initiator_user_id)->data;
                $avatarid = get_user_meta($value->initiator_user_id, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $frndreq[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                        $frndreq[$key]->userimage = get_avatar($value->initiator_user_id);
                    }
                    $table_name = $wpdb->prefix . 'bp_activity';
                    $count_query = "select date_recorded from $table_name where user_id=$value->initiator_user_id and type='last_activity' ORDER BY id DESC LIMIT 1";
                    $activity = $wpdb->get_results($count_query);
                    $frndreq[$key]->recorded_date = time_elapsed_string($activity[0]->date_recorded);
                    $frndreq[$key]->isfollower = bp_follow_is_following(array('leader_id' => $value->initiator_user_id, 'follower_id' => $user_id))? "follow": "unfollow";
                    $frndreq[$key]->isfriend = friends_check_friendship_status($user_id, $value->initiator_user_id);
            }
                $response['code']=0;
                $response['data']=$frndreq;
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

//// get all members /////
if($getit=="getmemberinfo"){
    $user_id = $_REQUEST['user_id'];
    $args = array('role' => "contributor");
    $data = get_users($args);
    // echo '<pre>'; print_r($data); echo '</pre>'; 
    //         die();
    $memberdata = array();
    foreach($data as $key=>$value){
                $avatarid = get_user_meta($value->data->ID, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $data[$key]->data->userimage = '<img src="'.$image.'">';
                    } else {
//                        $avatar = get_avatar_url($value->data->ID);
//                        $data[$key]->data->userimage = '<img src="'.$avatar.'" >';
                        
                        $avatar = get_avatar($value->data->ID);
                        $checkgr = explode("//", $avatar);
                        if (strpos($checkgr[1], 'www.gravatar') !== false) {
                            $avatarimg = get_avatar_url($value->data->ID);
                            $data[$key]->data->userimage = '<img src= "https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';
                        }else{
                            $data[$key]->data->userimage = $avatar;
                        }
                        //$data[$key]->data->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';//get_avatar($value->initiator_user_id);
                    } 

                    $useridd = $value->data->ID;
                    $table_name = $wpdb->prefix . 'bp_activity';
                    $count_query = "select date_recorded from $table_name where user_id=$useridd and type='last_activity' ORDER BY id DESC LIMIT 1";
                    $activity = $wpdb->get_results($count_query);
                    
                    $data[$key]->data->recorded_date = time_elapsed_string($activity[0]->date_recorded);
                    $data[$key]->data->isfollower = bp_follow_is_following(array('leader_id' => $useridd, 'follower_id' => $user_id))? "follow": "unfollow";
                    $data[$key]->data->isfriend = friends_check_friendship_status($user_id, $useridd);
                    array_push($memberdata, $data[$key]->data);
            }

            if(!empty($data)){
                $response['code']=0;
                $response['data']=$memberdata;
                echo json_encode($response);
                exit();
            } else {
                $response['code']=1;
                $response['msg']="Data unavailable";
                echo json_encode($response);
                exit();
            }
    echo json_encode($data);
    exit();
}

//// get friends information /////
if($getit == "getfriendslist"){
        $user_id = $_REQUEST['user_id'];
        $friends=[];
        $follower=[];
        $followings=[];
        $frndreq=[];
        $table = $wpdb->prefix . 'bp_friends';
    $data = $wpdb->get_results("SELECT initiator_user_id FROM $table WHERE (friend_user_id=$user_id AND is_confirmed=0)");

        if(!empty($data)){
            foreach($data as $key=>$value){
                $frndreq[] = get_userdata($value->initiator_user_id)->data;
                $avatarid = get_user_meta($value->initiator_user_id, "wp_user_avatar");

                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $frndreq[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                        $avatar = get_avatar_url($value->initiator_user_id);
                        $frndreq[$key]->userimage = '<img src="'.$avatar.'" >';
                        //$frndreq[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">';//get_avatar($value->initiator_user_id);
                    }
                    $table_name = $wpdb->prefix . 'bp_activity';
                    $count_query = "select date_recorded from $table_name where user_id=$value->initiator_user_id and type='last_activity' ORDER BY id DESC LIMIT 1";
                    $activity = $wpdb->get_results($count_query);
                    $frndreq[$key]->recorded_date = time_elapsed_string($activity[0]->date_recorded);
                    $frndreq[$key]->isfollower = bp_follow_is_following(array('leader_id' => $value->initiator_user_id, 'follower_id' => $user_id))? "follow": "unfollow";
                    $frndreq[$key]->isfriend = friends_check_friendship_status($user_id, $value->initiator_user_id);

            }
        }
        $data = friends_get_friend_user_ids($user_id);
            if(!empty($data)){
                foreach($data as $key=>$value){
                    $friends[] = get_userdata($value)->data;
                    $avatarid = get_user_meta($value, "wp_user_avatar");
                        if(!empty($avatarid)){
                            $id=$avatarid[0];
                            $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                            $image = $media[0]->guid;
                            $friends[$key]->userimage = '<img src="'.$image.'">';
                        } else {
                            $avatar = get_avatar_url($value);
                            $friends[$key]->userimage = '<img src="'.$avatar.'" >';
                            //$friends[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($value);
                        }
                }
            }
            $args=array(
                'user_id'=>$user_id
            );
        $followers = bp_follow_get_followers($args);
        if(!empty($followers)){
            foreach($followers as $key=>$value){
                $follower[] = get_userdata($value)->data;
                $avatarid = get_user_meta($value, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $follower[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                            $avatar = get_avatar_url($value);
                            $follower[$key]->userimage = '<img src="'.$avatar.'" >';
                        //$follower[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($value);
                    }
        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select date_recorded from $table_name where user_id=$value and type='last_activity' ORDER BY id DESC LIMIT 1";
        $activity = $wpdb->get_results($count_query);
        $follower[$key]->recorded_date = time_elapsed_string($activity[0]->date_recorded);
                    $follower[$key]->isfollower = bp_follow_is_following(array('leader_id' => $value, 'follower_id' => $user_id))? "follow": "unfollow";
                    $follower[$key]->isfriend = friends_check_friendship_status($user_id, $value);
            }
        }
        $following = bp_follow_get_following($args);
        if(!empty($following)){
            foreach($following as $key=>$value){
                
                $followings[] = get_userdata($value)->data;
                $avatarid = get_user_meta($value, "wp_user_avatar");
                    if(!empty($avatarid)){
                        $id=$avatarid[0];
                        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                        $image = $media[0]->guid;
                        $followings[$key]->userimage = '<img src="'.$image.'">';
                    } else {
                            $avatar = get_avatar_url($value);
                            $followings[$key]->userimage = '<img src="'.$avatar.'" >';
                        //$followings[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($value);
                    }
                    $table_name = $wpdb->prefix . 'bp_activity';
                        $count_query = "select date_recorded from $table_name where user_id=$value and type='last_activity' ORDER BY id DESC LIMIT 1";
                        $activity = $wpdb->get_results($count_query);
                        $followings[$key]->recorded_date = time_elapsed_string($activity[0]->date_recorded);
                    $followings[$key]->isfollower = bp_follow_is_following(array('leader_id' => $value, 'follower_id' => $user_id))? "follow": "unfollow";
                    $followings[$key]->isfriend = friends_check_friendship_status($user_id, $value);
            }
        }
            if(!empty($friends) || !empty($followers) || !empty($frndreq) || !empty($following)){
                $response['code']=0;
                $response['data']=$friends;
                $response['followers']=$follower;
                $response['following']=$followings;
                $response['frndrequest']=$frndreq;
                echo json_encode($response);
                exit();
            } else {
                $response['code']=1;
                $response['msg']="Data unavailable";
                echo json_encode($response);
                exit();
            }
        exit();
}

//// api to delete post /////
if ($getit == "deletePost") {
    $postid = $_REQUEST['postid'];
    $args = array(
        'id' => $postid
    );
    $delete = bp_activity_delete($args);
    if ($delete) {
        $table_name = $wpdb->prefix . 'posts';
        $table_name2 = $wpdb->prefix . 'rt_rtm_media';
        $post_type = 'attachment';
        bp_activity_delete_meta($postid);
        
        //Delete data from rt_media table
        $media_model = new RTMediaModel();
        $activity_media = $media_model->get(array('activity_id' => $postid));
        if (!empty($activity_media) && is_array($activity_media)) {
            foreach ($activity_media as $single_media) {
                $delete_attach = $wpdb->query("DELETE FROM {$table_name} WHERE ID = ($single_media->media_id) AND post_type = ($post_type)");
                if ($delete_attach) {
                    $delete = $wpdb->query("DELETE FROM {$table_name2} WHERE media_id = $single_media->media_id");
                }
                //$media_model->update($columns, $where);
            }
        } else {
            //if article is not media only content
            $spec_activity = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "bp_activity WHERE id = '" . $postid . "'"), ARRAY_A);
            $delete_attach = $wpdb->query("DELETE FROM {$table_name} WHERE ID = '" . $spec_activity['secondary_item_id'] . "'");
        }
        $response['code'] = 0;
        $response['msg'] = "Post deleted successfully";
        echo json_encode($response);
        exit();
    } else {
        $response['code'] = 1;
        $response['msg'] = "Post deleted unsuccessfull";
        echo json_encode($response);
        exit();
    }
}

//// api to get media files /////
if($getit == "getmediafiles"){
    $user_id = $_REQUEST['user_id'];
    $media = $wpdb->get_results("SELECT ID,post_mime_type,guid FROM $wpdb->posts WHERE (post_author=$user_id AND post_type='attachment' AND post_parent!='0')");
    if(!empty($media)){
        foreach ($media as $key => $value) {
            $postid = $value->ID;

            $mediacount = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "rt_rtm_media WHERE media_id = '" . $postid . "' AND context='profile' AND media_author = '" . $user_id . "'"), ARRAY_A);
            $fileex = explode('uploads',$media[$key]->guid);
            $checkfile_exists = '/home/aawesome/public_html/wp-content/uploads' . $fileex[1];
            if (!file_exists($checkfile_exists)) {
                unset($media[$key]);
            }
            if (empty($mediacount)) {
                unset($media[$key]);
            }

        }

        $response['code']=0;
        $response['data']=array_values($media);
        echo json_encode($response);
        exit();
    }else{
        $response['code']=1;
        $response['msg']="No media available";
            echo json_encode($response);
            exit();

    }
}

/// api to post reply /////////
if($getit == "postreply"){
    $user_id = $_REQUEST['user_id'];
    $postid = $_REQUEST['postid'];
    $commentid = $_REQUEST['commentid'];
    $comment = $_REQUEST['reply'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $primarylink = "https://aawesomeme.com/members/".$user_nicename."/";
    $action= '<a href="https://aawesomeme.com/members/"'.$user_nicename.'"/">'.$user_login.'</a> posted an update';
    $postdate = $_REQUEST['postdate']; //"2018-01-29 08:14:56";
    
    if(!empty($output)){
        $args = array(
            'user_id' => $user_id,
            'component' => 'activity',
            'type' => 'activity_comment',
            'content' => $comment,
            'action' => $action,
            'primary_link' => $primarylink,
            'item_id' => $postid,
            'secondary_item_id' => $commentid,
            'date_recorded' => $postdate);
        $insert = bp_activity_add($args);
        if($insert){
                $response['code']=0;
                $response['msg']="Reply successfull";
                echo json_encode($response);
                exit();
        }else{
                $response['code']=1;
                $response['msg']="Reply unsuccessfull";
                echo json_encode($response);
                exit();
        }

    }
    exit();
}

//Delete Comment Api
if ($getit == "deletereply") {
    $id = $_REQUEST['activity_id'];
    $args = array(
        'id' => $id
    );
    $getcomment = $wpdb->get_row('SELECT *  FROM ' . $wpdb->prefix . 'commentmeta WHERE meta_value = "' . $id . '" AND meta_key = "bp_activity_comment_id"', ARRAY_A);
    if ($getcomment['comment_id']) {
        $deletecomment = $wpdb->query('DELETE  FROM ' . $wpdb->prefix . 'comments WHERE id = "' . $getcomment['comment_id'] . '"');

        $deletecomment = $wpdb->query('DELETE  FROM ' . $wpdb->prefix . 'bp_activity WHERE id = "' . $id . '" AND type = activity_comment');

        $deletecomment = $wpdb->query('DELETE  FROM ' . $wpdb->prefix . 'commentmeta WHERE comment_id = "' . $getcomment['comment_id'] . '"');
        $response['code'] = 0;
        $response['msg'] = "Comment deleted successfuly";
        echo json_encode($response);
        exit;
    }
    elseif (empty($getcomment)) {
    	$deletecomment = bp_activity_delete($args);
    	if($deletecomment){
	    	$response['code'] = 0;
	        $response['msg'] = "Comment deleted successfuly";
	        echo json_encode($response);
	        exit;
    	} else {
    		$response['code'] = 1;
	        $response['msg'] = "Comment not deleted successfuly";
	        echo json_encode($response);
	        exit;
    	}
    }
    else {
        $response['code'] = 1;
        $response['msg'] = "Comment not deleted successfuly";
        echo json_encode($response);
        exit;
    }
    exit;
}

function newblogpostid($user_id,$postid,$content){
    $postdate = date('Y-m-d H:i:s');
    $output = get_userdata($user_id);
    $displayname = $output->data->display_name;
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename; 
    $primarylink = site_url()."/?p=".esc_html($postid);
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> wrote a new post, ';
    $action.='<a href="' . esc_url('https://aawesomeme.com/?p='. $postid) . '">'. esc_html($user_nicename).' test</a>';
    $args = array(
        'user_id' => $user_id,
        'component' => 'blogs',
        'type' => 'new_blog_post',
        'content' => $content,
        'action' => $action,
        'primary_link' => $primarylink,
        'item_id' => 1,
        'secondary_item_id' => $postid,
        'date_recorded' => $postdate);

    $insert = bp_activity_add($args);
    return $insert;
}

/// api to post comment for article ///////
if($getit == "postarticlecomment"){
    
    $user_id = $_REQUEST['user_id'];
    $postid = $_REQUEST['postid'];
    $comment = $_REQUEST['comment'];
    $content = str_replace(" ","-",$_REQUEST['content']);
    $activityid = $_REQUEST['activityid'];

    $output = get_userdata($user_id);
    $displayname = $output->data->display_name;
    $useremail = $output->data->user_email;
    $user_login = $output->data->user_login;
    $author_url = $output->data->user_url;
    $user_nicename = $output->data->user_nicename; 
    $action= '<a href="' . esc_url('https://aawesomeme.com/members/'. $user_nicename) . '">'. esc_html($user_login).'</a> posted a new activity comment';
    $postdate = $_REQUEST['postdate']; 
    
    if(!empty($output)){

        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select count(*) from $table_name where secondary_item_id=$postid";
        $countcomment = $wpdb->get_var($count_query);
            
            if($countcomment==0){
                $activityid= newblogpostid($user_id,$postid,$content);
            }

        $time = current_time('mysql');

            $data = array(
                'comment_post_ID' => $postid,
                'comment_author' => $displayname,
                'comment_author_email' => $useremail,
                'comment_author_url' => $author_url,
                'comment_content' => $comment,
                'comment_type' => '',
                'comment_parent' => 0,
                'comment_karma' => 0,
                'user_id' => $user_id,
                'comment_author_IP' => '127.0.0.1',
                'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
                'comment_date' => $time,
                'comment_approved' => 1,
            );

                $commentid = wp_insert_comment($data);
                $primarylink = site_url().esc_html($content)."/#comment-".esc_html($commentid);
                $permalink = site_url().esc_html($content);
            $args = array(
                'user_id' => $user_id,
                'component' => 'activity',
                'type' => 'activity_comment',
                'content' => $comment,
                'action' => $action,
                'primary_link' => $primarylink,
                'item_id' => $activityid,
                'secondary_item_id' => $activityid,
                'date_recorded' => $postdate);

            $insert = bp_activity_add($args);
            if($insert){
                $lastid = $wpdb->insert_id;
                $value['id']=$lastid;
                $value['content']=$comment;
                $commentmeta=array('comment_author'=>$displayname,
                                   'comment_author_email'=>$useremail,
                                   'comment_author_url'=>$author_url,
                                   'comment_content'=>$content,
                                   'comment_type'=>'',
                                   'user_ID'=>$user_id,
                                   'user_id'=>$user_id,
                                   'user_ip'=>'127.0.0.1',
                                   'user_agent'=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
                                   'blog'=>site_url(),
                                   'blog_lang'=>'en_US',
                                   'blog_charset'=>'UTF-8',
                                   'permalink'=>$permalink);
                $meta_value=serialize($commentmeta);
                    add_comment_meta( $commentid, "akismet_as_submitted", $meta_value );
                    update_user_meta($user_id, "bp_latest_update", $value);
                    $response['code']=0;
                    $response['activityid']=$activityid;
                    $response['msg']="Comment successfull";
                    echo json_encode($response);
                    exit();
            } else {
                    $response['code']=1;
                    $response['msg']="Comment unsuccessfull";
                    echo json_encode($response);
                    exit();
            }
    }
    exit();
}

/// api to post comment ///////
if($getit == "postcomment"){
    $user_id = $_REQUEST['user_id'];
    $postid = $_REQUEST['postid'];
    $comment = $_REQUEST['comment'];
    $output = get_userdata($user_id);
    $user_login = $output->data->user_login;
    $user_nicename = $output->data->user_nicename;
    $primarylink = "https://aawesomeme.com/members/".$user_nicename."/";
    $action= '<a href="https://aawesomeme.com/members/"'.$user_nicename.'"/">'.$user_login.'</a> posted an update';
    $postdate = $_REQUEST['postdate']; //"2018-01-29 08:14:56";
    
    if(!empty($output)){
            $args = array(
                'user_id' => $user_id,
                'component' => 'activity',
                'type' => 'activity_comment',
                'content' => $comment,
                'action' => $action,
                'primary_link' => $primarylink,
                'item_id' => $postid,
                'secondary_item_id' => $postid,
                'date_recorded' => $postdate);
            $insert = bp_activity_add($args);
            if($insert){
                $lastid = $wpdb->insert_id;
                $value['id']=$lastid;
                $value['content']=$comment;
                    update_user_meta($user_id, "bp_latest_update", $value);
                    $response['code']=0;
                    $response['msg']="Comment successfull";
                    echo json_encode($response);
                    exit();
            } else {
                    $response['code']=1;
                    $response['msg']="Comment unsuccessfull";
                    echo json_encode($response);
                    exit();
            }
    }
    exit();
}

if($getit == "likePost"){
    $activityid = $_REQUEST['postid'];
    $user_id = $_REQUEST['user_id'];
    $data=array();
    $data = bp_activity_get_meta($activityid, "liked_count");

    if(!empty($data)){
    if (array_key_exists($user_id, $data)){
        unset($data[$user_id]);
        $datato = serialize($data);
            $wpdb->update('wp_bp_activity_meta', 
                array('meta_value'=>$datato),
                array('meta_key'=>'liked_count','activity_id' => $activityid));

        $response['code']=0;
        $response['msg']="unlike successfully";
        $response['val']=0;
            echo json_encode($response);
            exit();
    } else {
        $data[$user_id]="user_likes";
        $datato = serialize($data);
        $wpdb->update('wp_bp_activity_meta', 
                array('meta_value'=>$datato),
                array('meta_key'=>'liked_count','activity_id' => $activityid));
        $response['code']=0;
        $response['msg']="liked successfully";
        $response['val']=1;
            echo json_encode($response);
            exit();
       
    } } else{
        if(!is_array($data)){
            $data[$user_id]="user_likes";
                $datato = serialize($data);
                $wpdb->insert('wp_bp_activity_meta', array(
                            'meta_value' => $datato,
                            'meta_key' => 'liked_count',
                            'activity_id' => $activityid, // ... and so on
                ));
                $response['code']=0;
                $response['msg']="liked successfully";
                $response['val']=1;
            echo json_encode($response);
            exit();
        } else{
            $data[$user_id]="user_likes";
            $datato = serialize($data);
            $wpdb->update('wp_bp_activity_meta', 
                    array('meta_value'=>$datato),
                    array('meta_key'=>'liked_count','activity_id' => $activityid));
            $response['code']=0;
            $response['msg']="liked successfully";
            $response['val']=1;
            echo json_encode($response);
            exit();

        }
    }
}

/* * **** Get comments of Logged In user posts ********** */
if($getit == "getcomments"){
    $activityid = $_REQUEST['activityid'];
        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where item_id=$activityid and secondary_item_id=$activityid and type='activity_comment' ORDER BY id DESC";
        $posts = $wpdb->get_results($count_query);
     // print_r($posts); die;
        
        foreach($posts as $key=>$value){
            //print_r($posts); echo "rubal"; print_r($value); die;
            $id = $value->id;
            $user_id = $value->user_id;
            $count_query = "select count(*) from $table_name where secondary_item_id=$id and type='activity_comment' ORDER BY id DESC";
            $countreplies= $wpdb->get_var($count_query);
            $posts[$key]->recorded_date = time_elapsed_string($value->date_recorded);
            
               $posts[$key]->userinfo = get_userdata($value->user_id);
               $posts[$key]->content = stripslashes($value->content);
               $posts[$key]->countreply = $countreplies;
            $avatarid = get_user_meta($user_id, "wp_user_avatar");
            
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                
                $posts[$key]->userimage = '<img src="'.$image.'">';
            } else {
                $avatar = get_avatar_url($user_id);
                $posts[$key]->userimage = '<img src="'.$avatar.'" >';
                //$posts[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
            }
        }

            if(!empty($posts)){
                    $response['code']=0;
                    $response['comments']=$posts;
            } else {
                $response['code']=1;
                $response['msg']="Comments unavailable for this post";
            }
            echo json_encode($response);
            exit();
}

/* * **** Get replies of Logged In user posts ********** */
if($getit == "getreplies"){
    $activityid = $_REQUEST['replyid'];
        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where secondary_item_id=$activityid and type='activity_comment' ORDER BY id DESC";
        $posts = $wpdb->get_results($count_query);
        foreach($posts as $key=>$value){
            $user_id = $value->user_id;
            $posts[$key]->content = stripslashes($value->content);
            $avatarid = get_user_meta($user_id, "wp_user_avatar");
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                $posts[$key]->userimage = '<img src="'.$image.'">';
            } else {
                $avatar = get_avatar_url($user_id);
                $posts[$key]->userimage = '<img src="'.$avatar.'" >';
                //$posts[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
            }
            $posts[$key]->userinfo=get_userdata($value->user_id);
            $posts[$key]->recorded_date = time_elapsed_string($value->date_recorded);
        }
            if(!empty($posts)){
                    $response['code']=0;
                    $response['replies']=$posts;
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

if($getit == "refreshpost"){
    $user_id = $_REQUEST['user_id'];
    $page = $_REQUEST['page']*10;
    $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where (component='activity' OR (component='blogs' AND (type='new_blog_post' AND item_id='1')) OR component='groups') AND (type!='activity_comment') ORDER BY id DESC LIMIT $page,10";

        $posts = $wpdb->get_results($count_query);
        $activiti_data = array();   
        
            foreach ($posts as $key => $value) {
                    $id = $value->id;
                    $privacystatus = "";
                    $privacystatus = get_userdata($value->user_id)->data->privacystatus;
                    if($privacystatus=="1" && $user_id!=$value->user_id)
                    {
                        unset($posts[$key]);
                    } 
                    elseif($user_id==$value->user_id || friends_check_friendship_status($user_id, $value->user_id)=="is_friend" || $privacystatus=="0") {
                        if($value->component=="blogs" && $value->type=="new_blog_post"&& $value->item_id==1){
                            $postcontent=get_post($value->secondary_item_id);
                            $posts[$key]->posttitle=$postcontent->post_title;
                            $posts[$key]->postcontent=$postcontent->post_content;
                        }
                    $data = bp_activity_get_meta($id, "liked_count");
                        $table_name = $wpdb->prefix . 'bp_activity';
                        $count_query = "select count(*) from $table_name where item_id=$id";
                        $posts[$key]->comment = $wpdb->get_var($count_query);
                        $posts[$key]->content = stripslashes($value->content);
                        $posts[$key]->recorded_date = time_elapsed_string($value->date_recorded);
                        $args = array(
                            'field' => 'Your Nickname',
                            'user_id' => $value->user_id
                        );
                            $posts[$key]->nickname = get_userdata($value->user_id)->data->display_name;
                            
                            if (array_key_exists($value->user_id, $data)){
                                $posts[$key]->userlike = 1;
                            }else{
                                $posts[$key]->userlike = 0;
                            }

                            if(!empty($data)) {
                                $posts[$key]->like = count($data);
                            } else {
                                $posts[$key]->like = count($activiti_data);
                            }
                            $avatarid = get_user_meta($value->user_id, "wp_user_avatar");
                            if(!empty($avatarid)){
                                $av_id=$avatarid[0];
                                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$av_id AND post_type='attachment')");
                                $image = $media[0]->guid;
                                $posts[$key]->userimage = '<img src="'.$image.'" >';
                            } else {
                                $avatar = get_avatar_url($user_id);
                                $posts[$key]->userimage = '<img src="'.$avatar.'" >';
                                //$posts[$key]->userimage = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($value->user_id);
                            }
                        } else {
                         unset($posts[$key]);
                    }
                }
    
    if(!empty($posts)){
            $response['code']=0;
            $response['posts']=array_values($posts);
            echo json_encode($response);
            exit();
    } else {
            $response['code']=1;
            $response['posts']=$posts;
            echo json_encode($response);
            exit();
    }
        echo json_encode($response);
        exit();
}

function findpost($incre,$user_id){
			global $wpdb;
		$limitin=$incre*5;

	    $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "select * from $table_name where (component='activity' OR (component='blogs' AND (type='new_blog_post' AND item_id='1')) OR component='groups') AND (type!='activity_comment') ORDER BY id DESC LIMIT $limitin";
        $posts = $wpdb->get_results($count_query);
        
        $activiti_data = array();   
        
            foreach ($posts as $key => $value) {
                    $id = $value->id;
                    $privacystatus = "";
                    $privacystatus = get_userdata($value->user_id)->data->privacystatus;

                    if($privacystatus=="1" && $user_id!=$value->user_id)
                    {
                        unset($posts[$key]);
                    } 
                    elseif($user_id==$value->user_id || friends_check_friendship_status($user_id, $value->user_id)=="is_friend" || $privacystatus=="0") {
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
                        $data = bp_activity_get_meta($id, "liked_count");
                        $table_name = $wpdb->prefix . 'bp_activity';
                        $count_query = "select count(*) from $table_name where item_id=$id";
                        $posts[$key]->comment = $wpdb->get_var($count_query);
                        $posts[$key]->content = stripslashes($value->content);
                        $posts[$key]->recorded_date = time_elapsed_string($value->date_recorded);
                            $args = array(
                                'field' => 'Your Nickname',
                                'user_id' => $value->user_id
                            );
                            $usrnickname = get_userdata($value->user_id)->data->display_name;
                            $posts[$key]->nickname = $usrnickname;
                            
                            if (array_key_exists($value->user_id, $data)){
                                $posts[$key]->userlike = 1;
                            }else{
                                $posts[$key]->userlike = 0;
                            }

                            if(!empty($data)) {
                                $posts[$key]->like = count($data);
                            } else {
                                $posts[$key]->like = count($activiti_data);
                            }
                            $avatarid = get_user_meta($value->user_id, "wp_user_avatar");

                            if(!empty($avatarid)){
                                $id=$avatarid[0];
                                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                                $image = $media[0]->guid;
                                $posts[$key]->userimage = '<img src="'.$image.'" >';
                            } else {
                                $avatar = get_avatar($value->user_id);
                                $checkgr = explode("//", $avatar);
                                    if (strpos($checkgr[1], 'www.gravatar') !== false) {
                                        $avatarimg = get_avatar_url($value->user_id);
                                        $posts[$key]->userimage = '<img src="' . $avatarimg . '">';;
                                    }else{
                                        $posts[$key]->userimage = $avatar;
                                    }
                            }

                    } else {
                         unset($posts[$key]);
                    }
                }
            return $posts;
        }        


/* * **** user Post Info api ********* */
if($getit == "userpostinfo"){
    $user_id = $_REQUEST['user_id'];
    $output = get_userdata($user_id);
        $response=array();
            $args = array(
                'field' => 'Your Nickname',
                'user_id' => $user_id
            );
                $name = $output->data->display_name; //bp_get_profile_field_data( $args );
                $pos=stripos($name, " ");
                if($pos){
                	$nickname=substr(trim($name),0,$pos);
                } else {
                	$nickname=trim($name);
                }
                
                $avatarid = get_user_meta($user_id, $key="wp_user_avatar");
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");

                $image = $media[0]->guid;
                $user_image = '<img src="'.$image.'" >';
            } else {
                $avatar = get_avatar_url($user_id);
                $user_image = '<img src="'.$avatar.'" >';
                //$user_image = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id); 
            }
            $incre=1;
            $posts = findpost($incre,$user_id);
            
            	while(empty($posts) || count($posts)<4) {
				    $incre=$incre+1;
            	$posts = findpost($incre,$user_id);
				} 
                    
    if(!empty($output)){


                //    print_r($posts); 

                // exit;
            $response['code']=0;
            $response['posts']=array_values($posts);
            $response['nickname']=$nickname;
            $response['user_image']=$user_image;
    }
        echo json_encode($response);
        exit();
}


/* * **** userinfo api ********* */
if($getit == "userInfo"){
    $user_id = $_REQUEST['user_id'];

    $response=array();
       $output = get_userdata($user_id);
        $meta = get_user_meta($user_id);
        
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

            // $args = array(
            //     'field' => "What\'s your hometown?",
            //     'user_id' => $user_id
            // );
            // $hometown = bp_get_profile_field_data( $args );


            // $table_namep = $wpdb->prefix . 'bp_xprofile_data';
            // $table_namep1 = $wpdb->prefix . 'bp_xprofile_fields';
            // $prodata = $wpdb->get_results("select * from $table_namep where user_id = '". $user_id ."'", ARRAY_A);
            // for($i = 0; $i < count($prodata); $i++){
            // $ques = $wpdb->get_row("select * from $table_namep1 where id = '". $prodata[$i]['field_id'] ."'", ARRAY_A);
            //   if($prodata[$i]['field_id'] == $ques['id']){
            //     $que = str_replace(' ', '_', $ques['name']);
            //         // $prodata[$i]['que'] = $ques['name'];
            //     $response['meta'][$que] = array($prodata[$i]['value']);
            //    }
            // }
            



            $count_query = friends_get_friend_user_ids($user_id);
            $friends = count($count_query);

        $groups = BP_Groups_Member::get_group_ids( $user_id, $limit, $page )[total];

        $table_name = $wpdb->prefix . 'bp_activity';
        $count_query = "SELECT * FROM $table_name WHERE user_id=$user_id AND (component='activity' OR (component='blogs' AND (type='new_blog_post' AND item_id='1')) OR component='groups') AND (type!='activity_comment') ORDER BY id DESC";

        $posts = $wpdb->get_results($count_query);

        //$media = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE (post_author=$user_id AND post_type='attachment' AND post_parent!='0')");
        $media = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}rt_rtm_media WHERE media_author = '".$user_id."' AND context = 'profile'");
        $activiti_data = array();   
        
            foreach ($posts as $key => $value) {
                    $id = $value->id;
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
                    $data = bp_activity_get_meta($id, "liked_count");

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


/* * **** login api *********** */
if ($getit == "signin") {
    $credentials['user_login'] = $_REQUEST['username'];
    $credentials['user_password'] = $_REQUEST['password'];
    $credentials['remember'] = true;
    $user = wp_signon($credentials, false);

    if (is_wp_error($user)) {
        $response['code'] = 1;
        $response['msg'] = "Incorrect Credentials!";
    } else {
        $user_id = $user->data->ID;
        //$user_image = get_avatar($user->data->ID);
        $avatarid = get_user_meta($user_id, $key="wp_user_avatar");
            if(!empty($avatarid)){
                $id=$avatarid[0];
                $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
                $image = $media[0]->guid;
                $user_image = '<img src="'.$image.'" >';
            } else {
                $avatar = get_avatar_url($user_id);
                
                $user_image = '<img src="'.$avatar.'" >';
                //$user_image = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
            }
        $user->data->image=urldecode($user_image);
        $response['code'] = 0;
        $response['data'] = $user->data;   
        $response['roles'] = $user->roles[0];
        $response['msg'] = "User successfully logged in!";
    }
    echo json_encode($response);
    exit();
}

/* * ******** login api end ************ */
/* * ************* all_event api *********** */

/* * ******** all_event api end ************ */
/* * ************* search_event api *********** */
if ($getit == "add_user") {
    $username = trim($_REQUEST['username']);
    $password = $_REQUEST['password'];
    $email = $_REQUEST['email'];
    $response = array();
    $output = wp_create_user($username, $password, $email);
    if (is_numeric($output)) {
        $response['code'] = 0;
        $response['user_id'] = $output;
        $userdata = array();
        $userdata['ID'] = $output;
        $userdata['role'] = "customer";
        $userdata['user_login'] = $username;
        $userdata['display_name'] = $username;
        $userdata['user_email'] = $email;

        $pos = stripos(trim($username)," ");
        if($pos>0){
            $firstname=substr(trim($username),0,$pos);
            $lastname=substr(trim($username),$pos+1);
            $userdata['first_name'] = $firstname;
            $userdata['last_name'] = $lastname;
        } else {
            $firstname=trim($username);
            $lastname="";
            $userdata['first_name'] = $firstname;
            $userdata['last_name'] = $lastname;
        }
        
        //$userdata['user_status'] = "2";
        $userdata['user_nicename'] = trim($username);
        $data = wp_insert_user($userdata);
        
//       $metas = array(
//            'first_name' => $username,
//            'last_name' => $_REQUEST['last_name'],
//        );
//        foreach ($metas as $key => $value) {
//            update_user_meta($output, $key, $value);
//        }
            $capabilities=array('contributor' => true,'bbp_participant' => true);
            $metas = array(
                'dob' => '',
                'gender' => '',
                'phone' => '',
                'nickname' => $username,
                'email_for_fans' => '',
                'first_name'=>$firstname,
                'last_name'=>$lastname,
                'Your_website' => '',
                'What_type_of_music_do_you_listen_to' => '',
                'What_is_your_favorite_type_of_food' => '',
                'What_is_your_hometown' => '',
                'What_do_you_like_to_spend_money_on' => '',
                'What_is_your_favorite_animal_and_why' => '',
                'If_you_had_one_super_power_what_would_it_be' => '',
                'What_is_your_favorite_city_you_have_traveled_to' => '',
                'What_is_your_favorite_video_game' => '',
                'What_are_your_favorite_quotes' => '',
                'Your_awesome_skills' => '',
                'About_us' => '',
                'occupation' => '',
                'Relationship_status' => '',
                'studiedat'=> '',
                'wp_capabilities'=>$capabilities,
                'wp_user_level'=>'0'
            );

            $testarray=array();
            foreach ($metas as $key => $value) {
                $testarray[$key]=$value;
                update_user_meta($userdata["ID"], $key, $value);
            }
            // wp_update_user( array( 'ID' => $userdata["ID"], 'role' => "contributor,bbp_participant" ) );
            $user_id=$userdata["ID"];

            xprofile_set_field_data('6', $user_id, $metas['dob']);
            xprofile_set_field_data('9', $user_id, $metas['gender']);
            xprofile_set_field_data('5', $user_id, $metas['phone']);
            xprofile_set_field_data('14', $user_id, $metas['nickname']);
            xprofile_set_field_data('2', $user_id, $metas['email_for_fans']);
            xprofile_set_field_data('1', $user_id, $username);
            xprofile_set_field_data('16', $user_id, $metas['What_type_of_music_do_you_listen_to']);
            xprofile_set_field_data('20', $user_id, $metas['What_is_your_favorite_type_of_food']);
            xprofile_set_field_data('4', $user_id, $metas['What_is_your_hometown']);
            xprofile_set_field_data('18', $user_id, $metas['What_do_you_like_to_spend_money_on']);
            xprofile_set_field_data('19', $user_id, $metas['What_is_your_favorite_animal_and_why']);
            xprofile_set_field_data('17', $user_id, $metas['If_you_had_one_super_power_what_would_it_be']);
            xprofile_set_field_data('21', $user_id, $metas['What_is_your_favorite_city_you_have_traveled_to']);
            xprofile_set_field_data('22', $user_id, $metas['What_is_your_favorite_video_game']);
            xprofile_set_field_data('15', $user_id, $metas['What_are_your_favorite_quotes']);
            xprofile_set_field_data('3', $user_id, $metas['Your_awesome_skills']);
            xprofile_set_field_data('13', $user_id, $metas['About_us']);
            xprofile_set_field_data('12', $user_id, $metas['Your_website']);

        $user_image = "<img src=\"https://www.gravatar.com/avatar/75a380075b73765b3c30789ea4ccacaa?s=96&#038;r=g&#038;d=mm\" class=\"avatar user-280-avatar avatar-96 photo\" width=\"96\" height=\"96\" alt=\"Profile photo of vikkichji\" />";
        $userdata["image"]=urldecode($user_image);
        $response['data'] = $userdata; 
        $response['msg'] = "User successfully registered!";

    } else {
        foreach ($output->errors as $error) {
            $response['code'] = 1;
            $response['error'] = $error[0];
        }
    }

    echo json_encode($response);
    exit;
}

if($getit=="hello"){
    $user_id=209;//$_REQUEST['user_id'];
    $avatarid = get_user_meta($user_id, $key="wp_user_avatar");
    if(!empty($avatarid)){
        $id=$avatarid[0];
        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
        echo $media[0]->guid;
        $image_url = '<img src="'.$image.'" class=\"avatar user-289-avatar avatar-96 photo\" width=\"96\" height=\"96\" alt=\"Profile photo of Website\" \/>"';
        echo $image_url;
        exit();
    } else {
                $avatar = get_avatar_url($user_id);
                echo "$avatar";
                $image_url = '<img src="'.$avatar.'" >';
                echo $image_url;
        //$image_url = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
        exit();
    }
}

if ($getit == "get_userdata") {
    $user_id = $_REQUEST['user_id'];
    $response = array();
    $output = get_userdata($user_id);
    $meta = get_user_meta($user_id);
    // $user_image = get_avatar_url($user_id, array('size' => 450));  
    // $user_image = get_avatar($user_id);
    $avatarid = get_user_meta($user_id, $key="wp_user_avatar");
    if(!empty($avatarid)){
        $id=$avatarid[0];
        $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
        $image = $media[0]->guid;
        $user_image = '<img src="'.$image.'" >';
    } else {
                $avatar = get_avatar_url($user_id);
                $user_image = '<img src="'.$avatar.'" >';
        //$user_image = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
    }

            $args = array(
                'field'   => 'Gender',
                'user_id' => $user_id
            );
            $gender = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'Name',
                'user_id' => $user_id
            );
            $name = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'Email Address for your fans',
                'user_id' => $user_id
            );
            $emailforfans = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'Your awesome skills:',
                'user_id' => $user_id
            );
            $awesomeskills = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'Phone #',
                'user_id' => $user_id
            );
            $phone = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'What type of music do you listen to?',
                'user_id' => $user_id
            );
            $favmusic = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'Your Website',
                'user_id' => $user_id
            );
            $urweburl = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'About You',
                'user_id' => $user_id
            );
            $aboutus = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'What are your favorite quotes?',
                'user_id' => $user_id
            );
            $favquotes = bp_get_profile_field_data( $args );
            
            $args = array(
                'field'   => 'If you had one super power, what would it be?',
                'user_id' => $user_id
            );
            $onepower = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => 'What do you like to spend money on?',
                'user_id' => $user_id
            );
            $spendmoney = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => "What\'s your favorite animal and why?",
                'user_id' => $user_id
            );
            $favanimal = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => "What\'s your favorite type of food?",
                'user_id' => $user_id
            );
            $favfood = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => "What\'s your favorite city you\'ve traveled to?",
                'user_id' => $user_id
            );
            $favcity = bp_get_profile_field_data( $args );

            $args = array(
                'field'   => "What\'s your favorite video game?",
                'user_id' => $user_id
            );
            $favgame = bp_get_profile_field_data( $args );

            $args = array(
                    'field' => 'Birth Date',
                    'user_id' => $user_id
            );
            $dob = bp_get_profile_field_data( $args );

            $args = array(
                    'field' => 'Your Nickname',
                    'user_id' => $user_id
            );
            $nickname = bp_get_profile_field_data( $args );

            $args = array(
                'field' => "What\'s your hometown?",
                'user_id' => $user_id
            );
            $hometown = bp_get_profile_field_data( $args );
    if (!empty($output)) {
        $response['code'] = 0;
        $response['gender'] = $gender;
        $response['name'] = $name;
        $response['emailforfans'] = $emailforfans;
        $response['awesomeskills'] = $awesomeskills;
        $response['phone'] = $phone;
        $response['favmusic'] = $favmusic;
        $response['urweburl'] = strip_tags($urweburl);
        $response['aboutus']  = $aboutus;
        $response['favquotes'] = $favquotes;
        $response['onepower'] = $onepower;
        $response['spendmoney'] = $spendmoney;
        $response['favanimal'] = $favanimal;
        $response['favfood'] = $favfood;
        $response['favcity'] = $favcity;
        $response['favgame'] = $favgame;
        $response['dob'] = $dob;
        $response['nickname'] = $nickname;
        $response['hometown'] = $hometown;
        $response['user_data'] = $output;
        $response['meta'] = $meta;
        $response['user_img'] = urldecode($user_image);
    } else {
        $response['code'] = 1;
        $response['msg'] = 'User doesn\'t exist';
    }
    echo json_encode($response);
    exit;
}

/*
 * /test
 */
if ($getit == "edit_profile") {
    $response = array();
    $userdata = array();
    $usernamee=explode(" ", trim($_REQUEST['fname']));
    $firstname=$usernamee[0];
    $lastname=$usernamee[1];
    $user_id = $_REQUEST['user_id'];
    $userdata['ID'] = $_REQUEST['user_id'];
    $userdata['description'] = $_REQUEST['descripiton'];
    $userdata['user_url'] = $_REQUEST['website'];

    if($_REQUEST['img']!=undefined)
    {
            $img = base64_decode($_REQUEST['img']);
            $im = imagecreatefromstring($img);
            $wp_upload_dir = wp_upload_dir();
            $filename = "term_thumbnail_" . time() . ".png";
            $path_to_file = $wp_upload_dir['path'] . "/" . $filename;
            $filetype = wp_check_filetype(basename($filename), null);
            if (!function_exists('wp_handle_upload')) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
            }
            @file_put_contents($path_to_file, $img);
            $attachment = array(
                'post_author' => $user_id,
                'post_content' => '',
                'post_content_filtered' => '',
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_excerpt' => '',
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => $filetype['type'],
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_password' => '',
                'to_ping' => '',
                'pinged' => '',
                'post_parent' => 0,
                'menu_order' => 0,
                'guid' => $wp_upload_dir['url'] . '/' . $filename,
            );
            $wpdb->insert("{$wpdb->prefix}posts", $attachment);
            $attach_id = $wpdb->insert_id;
            if ($attach_id == false) {
                //echo "false";
                return false;
            }

            $attach_data = wp_generate_attachment_metadata($attach_id, $path_to_file);
            wp_update_attachment_metadata($attach_id, $attach_data);

            update_attached_file($attach_id, $path_to_file);
            //self::set_term_thumbnail( $term_id, $thumbnail_id );
            update_user_meta($user_id, "{$wpdb->prefix}user_avatar", $attach_id);
    }
        $userdata['ID'] = $user_id;
        $display_name = trim($_REQUEST['fname']);
        $paypalemail = $_REQUEST['paypalemail'];
        // wp_update_user($userdata);
         //wp_update_user( array( 'ID' => $userdata["ID"], 'role' => "contributor,bbp_participant" ) );
        $wpdb->update('wp_users', 
                         array( 'paypal_email'=> $paypalemail, 'display_name'=> $display_name),
                         array( 'ID'=> $user_id )
                    );
        $pos = stripos(trim($display_name)," ");
        if($pos>0){
            $firstname=substr(trim($display_name),0,$pos);
            $lastname=substr(trim($display_name),$pos+1);
        } else {
            $firstname=trim($display_name);
            $lastname="";
        }
$capabilities=array('contributor' => true,'bbp_participant' => true);
    $metas = array(
        'dob' => $_REQUEST['dob'],
        'gender' => $_REQUEST['gender'],
        'phone' => $_REQUEST['phone'],
        'first_name' => $firstname,
        'last_name' => $lastname,
        'nickname' => $display_name,
        'email_for_fans' => $_REQUEST['emailforfans'],
        'What_type_of_music_do_you_listen_to' => $_REQUEST['music_u_listen'],
        'What_is_your_favorite_type_of_food' => $_REQUEST['fav_food'],
        'What_is_your_hometown' => $_REQUEST['hometown'],
        'What_do_you_like_to_spend_money_on' => $_REQUEST['spend_money'],
        'What_is_your_favorite_animal_and_why' => $_REQUEST['fav_animal'],
        'If_you_had_one_super_power_what_would_it_be' => $_REQUEST['super_power'],
        'What_is_your_favorite_city_you_have_traveled_to' => $_REQUEST['fav_city'],
        'What_is_your_favorite_video_game' => $_REQUEST['fav_video'],
        'What_are_your_favorite_quotes' => $_REQUEST['fav_quotes'],
        'Your_awesome_skills' => $_REQUEST['awesome_skill'],
        'Your_website' => $_REQUEST['website'],
        'About_us' => $_REQUEST['about_us'],
        'occupation' => $_REQUEST['occupation'],
        'Relationship_status'=> $_REQUEST['status'],
        'studiedat'=> $_REQUEST['studiedat'],
        'wp_capabilities'=>$capabilities,
    );

                if($_REQUEST['gender']=='Male'){
                    xprofile_set_field_data('8', $user_id, "Male");
                } else if($_REQUEST['gender']=='Female') {
                    xprofile_set_field_data('8', $user_id, "Female");
                } else {
                    xprofile_set_field_data('8', $user_id, "");
                }
            xprofile_set_field_data('6', $user_id, $metas['dob']);
            xprofile_set_field_data('5', $user_id, $metas['phone']);
            xprofile_set_field_data('14', $user_id, $_REQUEST['nickname']);
            xprofile_set_field_data('2', $user_id, $metas['email_for_fans']);
            xprofile_set_field_data('1', $user_id, $display_name);
            xprofile_set_field_data('16', $user_id, $metas['What_type_of_music_do_you_listen_to']);
            xprofile_set_field_data('20', $user_id, $metas['What_is_your_favorite_type_of_food']);
            xprofile_set_field_data('4', $user_id, $metas['What_is_your_hometown']);
            xprofile_set_field_data('18', $user_id, $metas['What_do_you_like_to_spend_money_on']);
            xprofile_set_field_data('19', $user_id, $metas['What_is_your_favorite_animal_and_why']);
            xprofile_set_field_data('17', $user_id, $metas['If_you_had_one_super_power_what_would_it_be']);
            xprofile_set_field_data('21', $user_id, $metas['What_is_your_favorite_city_you_have_traveled_to']);
            xprofile_set_field_data('22', $user_id, $metas['What_is_your_favorite_video_game']);
            xprofile_set_field_data('15', $user_id, $metas['What_are_your_favorite_quotes']);
            xprofile_set_field_data('3', $user_id, $metas['Your_awesome_skills']);
            xprofile_set_field_data('13', $user_id, $metas['About_us']);
            xprofile_set_field_data('12', $user_id, $metas['Your_website']);
    
    $testarray=array();
    foreach ($metas as $key => $value) {
        $testarray[$key]=$value;
        update_user_meta($user_id, $key, $value);
    };
        
        $userdata = get_userdata($user_id);
        $meta = $metas;
        //$user_image = get_avatar($user_id);
        $avatarid = get_user_meta($user_id, $key="wp_user_avatar");
    
        if(!empty($avatarid)){
            $id=$avatarid[0];
            $media = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE (ID=$id AND post_type='attachment')");
            $image = $media[0]->guid;
                $user_image = '<img src="'.$image.'" >';
        } else {
                $avatar = get_avatar_url($user_id);
                $user_image = '<img src="'.$avatar.'" >';
            //$user_image = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
        }
        $userdata->data->image=urldecode($user_image);
    //$output = wp_insert_user($userdata);
    // echo json_encode($userdata);
    // exit;

    if (!empty($userdata)) {
        $response['code'] = 0;
        //$response['user_id'] = $output;
        $response['meta'] = $meta;
        $response['user_img'] = urldecode($user_image);
        $response['user_data'] = $userdata;
    } else {
        foreach ($output->errors as $error) {
            $response['code'] = 1;
            $response['error'] = $error[0];
        }
    }
    echo json_encode($response);
    exit;
}


/*
 * Change password
 */
if ($getit == "change_password") {
    $response = array();
    $user_id = $_REQUEST['user_id'];
    $newpass = $_REQUEST['new_pass'];
    $pass = $_REQUEST['old_pass'];
    $user = get_user_by('id', $user_id);
    if ($user && wp_check_password($pass, $user->data->user_pass, $user->ID)) {
        $output = wp_set_password($newpass, $user_id);
        $response['code'] = 0;
        $response['msg'] = "Password Changed Successfully";
    } else {
        $response['code'] = 1;
        $response['msg'] = "Wrong Password";
    }
    echo json_encode($response);
    exit;
}
/*
 * //Change password
 */
if ($getit == "save_avatar") {
    global $blog_id;
    //echo json_encode($_REQUEST);
    $response = array();
    $user_id = $_REQUEST['user_id'];
    $img = base64_decode($_REQUEST['img']);
    $im = imagecreatefromstring($img);
    $wp_upload_dir = wp_upload_dir();
    $filename = "term_thumbnail_" . time() . ".png";
    $path_to_file = $wp_upload_dir['path'] . "/" . $filename;
    $filetype = wp_check_filetype(basename($filename), null);
    if (!function_exists('wp_handle_upload')) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
    }
    @file_put_contents($path_to_file, $img);
    $attachment = array(
        'post_author' => $user_id,
        'post_content' => '',
        'post_content_filtered' => '',
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
        'post_excerpt' => '',
        'post_status' => 'inherit',
        'post_type' => 'attachment',
        'post_mime_type' => $filetype['type'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_password' => '',
        'to_ping' => '',
        'pinged' => '',
        'post_parent' => 0,
        'menu_order' => 0,
        'guid' => $wp_upload_dir['url'] . '/' . $filename,
    );
    $wpdb->insert("{$wpdb->prefix}posts", $attachment);
    $attach_id = $wpdb->insert_id;
    if ($attach_id == false) {
        //echo "false";
        return false;
    }

    $attach_data = wp_generate_attachment_metadata($attach_id, $path_to_file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    update_attached_file($attach_id, $path_to_file);

    //self::set_term_thumbnail( $term_id, $thumbnail_id );
    update_user_meta($user_id, "{$wpdb->prefix}user_avatar", $attach_id);

    $response['code'] = 0;
    $response['msg'] = "Image Uploaded Successfully";
    $response['user_img'] = '<img src="https://www.gravatar.com/avatar/60b5b4bd980a9174e517b1e62cbe7349?s=96&r=g&d=mm">'; //get_avatar($user_id);
    echo json_encode($response);
    exit;
    /*
      or you may need the image URL
     */
}

/*
 *  forget password
 */
if ($getit == "lostpw") {
    $user_login = sanitize_text_field($_REQUEST['user_login']);
    global $wpdb, $wp_hasher;
    $response = array();
    $user_login = sanitize_text_field($user_login);

    if (empty($user_login)) {
        $response['code'] = 1;
        $response['msg'] = 'The e-mail could not be sent.';
        echo json_encode($response);
        exit();
        //return false;
    } else if (strpos($user_login, '@')) {
        $user_data = get_user_by('email', trim($user_login));
        if (empty($user_data)){
            $response['code'] = 1;
            $response['msg'] = 'The e-mail could not be sent.';
            echo json_encode($response);
            exit();
            //return false;
        }
    } else {
        $login = trim($user_login);
        $user_data = get_user_by('login', $login);
    }

    do_action('lostpassword_post');
    if (!$user_data)
        {
            $response['code'] = 1;
            $response['msg'] = 'The e-mail could not be sent.';
            echo json_encode($response);
            exit();
            //return false;
        }

    // redefining user_login ensures we return the right case in the email
    $user_login = $user_data->user_login;
    $user_email = $user_data->user_email;
    $user_nicename = $user_data->user_nicename;
    do_action('retreive_password', $user_login);  // Misspelled and deprecated
    do_action('retrieve_password', $user_login);

    $allow = apply_filters('allow_password_reset', true, $user_data->ID);

    if (!$allow)
        return false;
    else if (is_wp_error($allow))
        return false;

    $key = wp_generate_password(20, false);
    do_action('retrieve_password_key', $user_login, $key);

    if (empty($wp_hasher)) {
        require_once ABSPATH . 'wp-includes/class-phpass.php';
        $wp_hasher = new PasswordHash(8, true);
    }

    $home_url = get_home_url();
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $hashed = time() . ':' . $wp_hasher->HashPassword($key);
    $wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
    $message .= '<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500" rel="stylesheet"> 
</head>
<body style="background: url(';
$message .= $home_url."/api/img/bgplait.png";
$message .= ') repeat #dddddd;
		margin:0px auto;
		font-weight:400;
		background-size: 160px;">
	<div class="table-responsive">
<table width="600" border="0" cellpadding="10" cellspacing="0" style="margin:0px auto; background:#fffefb; text-align:center;">
  <tr style="background:#fff;">
    <td style="text-align:center; padding-top:20px; padding-bottom:20px; border-bottom:2px solid #ff0000;">
        
        <img src="'.$home_url.'/api/img/logo.png" alt="img" / style="width: 220px;">
    </td>
  </tr>
  <tr>
<td>
<h2 style="font-weight:500; margin-bottom:1px;">Hi '; 
$message .= sprintf(__( '%s'), $user_nicename) . "\r\n\r\n";
$message .= '</h2>
<p>Did you just make a request to reset your passwoard ? <br />
Yes ? Go right ahead.</p>
<a style="background:#ff0000; padding:15px 20px; text-transform:uppercase;
		display:inline-block; color:#fff; border-radius: 4px; text-decoration:none;
        font-weight:500;" href="';
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        $message .='">Reset my password</a>
<p style="color:#666; font-size:14px">If the big red button does not work, copy and paste <br /> 
the following link in your browser.</p>';
$message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . "\r\n\r\n";
   $message .= '</td>
  </tr>
</table>
	</div>
</body>
</html>';
    if (is_multisite())
        $blogname = $GLOBALS['current_site']->site_name;
    else
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    $title = sprintf(__('[%s] Password Reset'), $blogname);

    $title = apply_filters('retrieve_password_title', $title);
    $message = apply_filters('retrieve_password_message', $message, $key, $user_login);

    if ($message && !wp_mail($user_email, $title, $message, $headers)) {
        $response['code'] = 1;
        $response['msg'] = 'The e-mail could not be sent.';
    } else {
        $response['code'] = 0;
        $response['msg'] = 'Link for password reset has been emailed to you. Please check your email.';
    }
    echo json_encode($response);
    exit();
}

/*
 *  email verification
 */
if ($getit == "email_verificatin") {
    $user_id = $_REQUEST['user_id'];
    $email = $_REQUEST['email'];
    $response = array();
    $validate = email_exists($email);
    if (is_numeric($validate)) {
        $response['code'] = 0;
        $response['msg'] = "This Email is Already  Registred";
    } else {
        $user_data = get_user_by('ID', $user_id);
        $user_login = $user_data->data->user_login;
        $key = wp_generate_password(20, false);
        update_user_meta($user_id, 'requested_email', $email);
        $wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
        $msg = "Verify Your email by clicking the url ";
        $msg .= get_site_url().'/emailconfirm/cnfrm.php?action=validateemail&token='.$key."&login=".rawurlencode($user_login);

        $to = $email;
        $subject = "Confirm Email";
        $txt = $msg;
        $headers = "From: outbuzz@example.com";

        if (mail($to, $subject, $txt, $headers)) {
            $response['code'] = 1;
            $response['msg'] = "email verification link is send to " . $email . ". Confirm email by clicking the link";
        } else {
            $response['code'] = 2;
            $response['msg'] = "Some Error Occure, Try Later";
        };
    }
    echo json_encode($response);
    exit;
}

/*
 *  // email verification
 */


if ($getit == "get_catageories") {
    
    $custom_terms = get_terms('tribe_events_cat');
    echo json_encode($custom_terms);
    exit;
}
if ($getit == "catageory_events") {
    $cat_id = $_REQUEST['cat_id'];
    $args = [
        'post_status' => 'publish',
        'post_type' => 'tribe_events',
        'tax_query' => [
            [
                'taxonomy' => 'tribe_events_cat',
                'terms' => $cat_id,
            ],
        ],
            // Rest of your arguments
    ];
    $query = new WP_Query($args);

    foreach ($query->posts as &$data) {
        $catageory = array();
        $pos_count = $wpdb->get_results("SELECT * FROM `wpow_pvc_total` WHERE `postnum`=$data->ID");
        $poslike = $wpdb->get_results("SELECT * FROM `wpow_wti_like_post` WHERE `post_id`='" . $data->ID . "' AND`user_id`='" . $user_id . "'");
        if (empty($poslike)) {
            $data->is_liked = 0;
        } else {
            if ($poslike[0]->value == 0) {
                $data->is_liked = 0;
            } else if ($poslike[0]->value == 1) {
                $data->is_liked = 1;
            }
        }

        if (!empty($pos_count)) {
            $data->viewcount = $pos_count[0]->postcount;
        } else {
            $data->viewcount = 0;
        }
        $data->Eventimage = get_the_post_thumbnail_url($data->ID);
        $getpostmeta = get_post_meta($data->ID);
        $event_venue = get_post_meta($getpostmeta['_EventVenueID'][0]);
        $term_relation = $wpdb->get_results("SELECT * FROM `wpow_term_relationships` WHERE `object_id`=$data->ID");
        if (!empty($term_relation)) {
            foreach ($term_relation as $term_relationship) {
                $termid = $wpdb->get_results("SELECT * FROM `wpow_term_taxonomy` WHERE `term_taxonomy_id`=$term_relationship->term_taxonomy_id");
                foreach ($termid as $termdata) {
                    $term_data = $wpdb->get_results("SELECT * FROM `wpow_terms` WHERE `term_id`=$termdata->term_id");
                    if (!empty($term_data)) {
                        foreach ($term_data as $term_cat) {
                            array_push($catageory, $term_cat->name);
                        }
                    }
                }
            }
        }
        $data->catageories = $catageory;
        $data->Eventvanue = $event_venue['_VenueCity'][0];
        $data->vanueaddress = $event_venue['_VenueAddress'][0];
        $endtime = strtotime($data->EventEndDate);
        $data->EventEndDate = strtoupper(date('d M y h:i A', $endtime));
        $starttime = strtotime($data->EventStartDate);
        $data->EventStartDate = strtoupper(date('d M y h:i A', $starttime));
        $queryrsvp = $wpdb->get_results("SELECT * from wpow_postmeta WHERE meta_key='_tribe_rsvp_for_event' and meta_value='$data->ID'");
        $ticket = array();
        foreach ($queryrsvp as &$ticketdata) {
            $ticketdata->ticketmeta = get_post_meta($ticketdata->post_id);
            $ticketdata->ticketdata = get_post($ticketdata->post_id);
        }
        $wooquery = $wpdb->get_results("SELECT * from wpow_postmeta WHERE meta_key='_tribe_wooticket_for_event' and meta_value='$data->ID'");
        foreach ($wooquery as &$wooticket) {
            $wooticket->ticketmeta = get_post_meta($wooticket->post_id);
            $wooticket->ticketdata = get_post($wooticket->post_id);
        }
        // $ticket['woocommerece'] = $wooquery;
        $data->rsvpticket = $queryrsvp;
        $data->wooticket = $wooquery;
    }
    $response['data'] = $query->posts;
    echo json_encode($response);
    exit;
}

if($getit=="user_events"){
    $page = $_REQUEST['page'];
    $user_id = $_REQUEST['user_id'];
  //  $user_id=145;
    $offset = ($page - 1) * 10;
    $limit = 10;
   $query=$wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS DISTINCT wpow_posts.*, MIN(wpow_postmeta.meta_value) as EventStartDate, MIN(tribe_event_end_date.meta_value) as EventEndDate FROM wpow_posts INNER JOIN wpow_postmeta ON ( wpow_posts.ID = wpow_postmeta.post_id ) INNER JOIN wpow_postmeta AS mt1 ON ( wpow_posts.ID = mt1.post_id ) LEFT JOIN wpow_postmeta as tribe_event_end_date ON ( wpow_posts.ID = tribe_event_end_date.post_id AND tribe_event_end_date.meta_key = '_EventEndDate' ) WHERE 1=1 AND wpow_posts.post_author IN ('$user_id') AND ( wpow_postmeta.meta_key = '_EventStartDate' AND ( mt1.meta_key = '_EventStartDate' ) ) AND wpow_posts.post_type = 'tribe_events' AND (wpow_posts.post_status ='publish' OR wpow_posts.post_status ='draft' OR wpow_posts.post_status = 'tribe-ea-success' OR wpow_posts.post_status = 'tribe-ea-failed' OR wpow_posts.post_status = 'tribe-ea-schedule' OR wpow_posts.post_status = 'tribe-ea-pending' OR wpow_posts.post_status = 'tribe-ea-draft' OR wpow_posts.post_status = 'private') AND wpow_posts.post_password = ''  GROUP BY wpow_posts.ID ORDER BY EventStartDate DESC, wpow_postmeta.meta_value DESC LIMIT $offset, 10");
   $querycount=$wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS DISTINCT wpow_posts.*, MIN(wpow_postmeta.meta_value) as EventStartDate, MIN(tribe_event_end_date.meta_value) as EventEndDate FROM wpow_posts INNER JOIN wpow_postmeta ON ( wpow_posts.ID = wpow_postmeta.post_id ) INNER JOIN wpow_postmeta AS mt1 ON ( wpow_posts.ID = mt1.post_id ) LEFT JOIN wpow_postmeta as tribe_event_end_date ON ( wpow_posts.ID = tribe_event_end_date.post_id AND tribe_event_end_date.meta_key = '_EventEndDate' ) WHERE 1=1 AND wpow_posts.post_author IN ('$user_id') AND ( wpow_postmeta.meta_key = '_EventStartDate' AND ( mt1.meta_key = '_EventStartDate' ) ) AND wpow_posts.post_type = 'tribe_events' AND (wpow_posts.post_status ='publish' OR wpow_posts.post_status ='draft' OR wpow_posts.post_status = 'tribe-ea-success' OR wpow_posts.post_status = 'tribe-ea-failed' OR wpow_posts.post_status = 'tribe-ea-schedule' OR wpow_posts.post_status = 'tribe-ea-pending' OR wpow_posts.post_status = 'tribe-ea-draft' OR wpow_posts.post_status = 'private') AND wpow_posts.post_password = ''  GROUP BY wpow_posts.ID ORDER BY EventStartDate DESC, wpow_postmeta.meta_value DESC");
   $total_posts = count($querycount);
    $pages = ceil($total_posts / 10);
     foreach ($query as &$data) {

        $catageory = array();
        $poslike = $wpdb->get_results("SELECT * FROM `wpow_wti_like_post` WHERE `post_id`='" . $data->ID . "' AND`user_id`='" . $user_id . "'");
        if (empty($poslike)) {
            $data->is_liked = 0;
        } else {
            if ($poslike[0]->value == 0) {
                $data->is_liked = 0;
            } else if ($poslike[0]->value == 1) {
                $data->is_liked = 1;
            }
        }
        $pos_count = $wpdb->get_results("SELECT * FROM `wpow_pvc_total` WHERE `postnum`=$data->ID");
        if (!empty($pos_count)) {
            $data->viewcount = $pos_count[0]->postcount;
        } else {
            $data->viewcount = 0;
        }
        $data->Eventimage = get_the_post_thumbnail_url($data->ID);
        $getpostmeta = get_post_meta($data->ID);
        $event_venue = get_post_meta($getpostmeta['_EventVenueID'][0]);
        $term_relation = $wpdb->get_results("SELECT * FROM `wpow_term_relationships` WHERE `object_id`=$data->ID");
        if (!empty($term_relation)) {
            foreach ($term_relation as $term_relationship) {
                $termid = $wpdb->get_results("SELECT * FROM `wpow_term_taxonomy` WHERE `term_taxonomy_id`=$term_relationship->term_taxonomy_id");
                foreach ($termid as $termdata) {
                    $term_data = $wpdb->get_results("SELECT * FROM `wpow_terms` WHERE `term_id`=$termdata->term_id");
                    if (!empty($term_data)) {
                        foreach ($term_data as $term_cat) {
                            array_push($catageory, $term_cat->name);
                        }
                    }
                }
            }
        }
        $data->catageories = $catageory;
        $data->Eventvanue = $event_venue['_VenueCity'][0];
        $data->vanueaddress = $event_venue['_VenueAddress'][0];
        $endtime = strtotime($data->EventEndDate);
        $data->EventEndDate = strtoupper(date('d M y h:i A', $endtime));
        $starttime = strtotime($data->EventStartDate);
        $data->EventStartDate = strtoupper(date('d M y h:i A', $starttime));



        $queryrsvp = $wpdb->get_results("SELECT * from wpow_postmeta WHERE meta_key='_tribe_rsvp_for_event' and meta_value='$data->ID'");
        $ticket = array();
        foreach ($queryrsvp as &$ticketdata) {
            $ticketdata->ticketmeta = get_post_meta($ticketdata->post_id);
            $ticketdata->ticketdata = get_post($ticketdata->post_id);
        }
        $wooquery = $wpdb->get_results("SELECT * from wpow_postmeta WHERE meta_key='_tribe_wooticket_for_event' and meta_value='$data->ID'");
        foreach ($wooquery as &$wooticket) {
            $wooticket->ticketmeta = get_post_meta($wooticket->post_id);
            $wooticket->ticketdata = get_post($wooticket->post_id);
        }
        // $ticket['woocommerece'] = $wooquery;
        $data->rsvpticket = $queryrsvp;
        $data->wooticket = $wooquery;
    }
    $response = array();
    $response['totalpages'] = $pages;
    $response['data'] = $query; //array_slice($query, $offset, $limit);
    echo json_encode($response);
    exit;
}

if($getit=="edit_event"){
    $response=array();
    $event_id=$_REQUEST['event_id'];
    $eventstartdate = $_REQUEST['eventstartdate'];
    $eventenddate = $_REQUEST['eventenddate'];
    $eventvaneid = $_REQUEST['eventvaneid'];
    $eventcost = $_REQUEST['eventcost'];
    
    $postarr['ID']=$event_id;
    $postarr['post_title'] = $_REQUEST['post_title'];
    $postarr['post_author'] = $_REQUEST['user_id'];
    $postarr['post_content'] = $_REQUEST['post_content'];
    $postarr['post_type'] = "tribe_events";
    $postarr['post_name'] = sanitize_title($_REQUEST['post_title']);
    $postdata = wp_update_post($postarr, $wp_error = false);
    update_post_meta($event_id, '_EventStartDate', $eventstartdate);
    update_post_meta($event_id, '_EventEndDate', $eventenddate);
    update_post_meta($event_id, '_EventVenueID', $eventvaneid);
    update_post_meta($event_id, '_EventCost', $eventcost);
    $response['code']=1;
    $response['msg']="event saved successfuly";
    echo json_encode($response);
    exit;
}

//Delete Comment Api
if ($getit == "deletecomment") {
    $id = $_REQUEST['activity_id'];
    $args = array(
        'id' => $id
    );
    $getcomment = $wpdb->get_row('SELECT *  FROM ' . $wpdb->prefix . 'commentmeta WHERE meta_value = "' . $id . '" AND meta_key = "bp_activity_comment_id"', ARRAY_A);
    if ($getcomment['comment_id']) {
        $deletecomment = $wpdb->query('DELETE  FROM ' . $wpdb->prefix . 'comments WHERE id = "' . $getcomment['comment_id'] . '"');

        $deletecomment = $wpdb->query('DELETE  FROM ' . $wpdb->prefix . 'bp_activity WHERE id = "' . $id . '" AND type = activity_comment');

        $deletecomment = $wpdb->query('DELETE  FROM ' . $wpdb->prefix . 'commentmeta WHERE comment_id = "' . $getcomment['comment_id'] . '"');
        $response['code'] = 0;
        $response['msg'] = "Comment deleted successfuly";
        echo json_encode($response);
        exit;
    }
    elseif (empty($getcomment)) {
    	$deletecomment = bp_activity_delete($args);
    	if($deletecomment){
	    	$response['code'] = 0;
	        $response['msg'] = "Comment deleted successfuly";
	        echo json_encode($response);
	        exit;
    	} else {
    		$response['code'] = 1;
	        $response['msg'] = "Comment not deleted successfuly";
	        echo json_encode($response);
	        exit;
    	}
    }
    else {
        $response['code'] = 1;
        $response['msg'] = "Comment not deleted successfuly";
        echo json_encode($response);
        exit;
    }
    exit;
}

/*
 * //Purchase ticket
 */
exit();
/* * ******** search_event api end ************ */
?>