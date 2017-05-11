<?php 
/*
	Plugin Name: Web Service Interface
*/
?>
<?php 


// require_once userpro_path . "index.php";
		global $userpro;
		error_reporting(E_ERROR | E_PARSE);

			
			
	// $_REQUEST['action'] = 'resendOTP'; 
	$function = 'hbapi_'.$_REQUEST['action'];
	if(strpos($_SERVER['REQUEST_URI'] , 'hbapi') !== false) {
		
		
		if(function_exists($function)){
			
			
			add_action('template_redirect', $function);
		}
	}

	function pr($data =array() ,$type = 0){
		echo "<pre>";
		print_r($data);
		if($type == 1){
			exit();
		}
	}

	function DataFormat($date = ''){
		return date('m/d/Y',strtotime($date));
	}

	function hbapi_login(){
		
		$result = array();	
		$password = $_REQUEST['password'];
		$email = $_REQUEST['email_id'];
		$user = get_user_by('email', $email);
		$userdata = get_user_meta($user->ID);
			
		if(strtolower($userdata['device_type'][0]) != strtolower($_REQUEST['device_type']) ){

			update_user_meta($user->ID, 'device_type', $_REQUEST['device_type']);
			update_user_meta($user->ID, 'device_token', $_REQUEST['device_token']);
		}
		// OPEN the below comment after emplementing the OTP  

		// if($userdata['user_status'][0] === 'pending'){
		// 	$result['settings']['success'] = 2;
		//     $result['settings']['message'] = 'Your account is pending, Please confirm your Phone no.';
		//     $result['data'] = array();	   
		//     echo json_encode($result);exit();
		// }

		if(!$user){
		    $result['settings']['success'] = 0;
		    $result['settings']['message'] = 'Email id, you entered is invalid.';
		    $result['data'] = array();	   
		    echo json_encode($result);exit();
		}
		else{ //check password
			if(!wp_check_password($password, $user->user_pass, $user->ID)){ //bad password
		       	$result['settings']['success'] = 0;
		        $result['settings']['message'] = 'Incorrect Password';
		        $result['data'] = array();	   
		        echo json_encode($result);exit();  
		    }else{

		        $data = array();		        
		        $data['user_id'] = $user->ID;
		        $data['first_name'] = $userdata['first_name'][0];
		        $data['last_name'] = $userdata['last_name'][0];
		        $data['contact_number'] = $userdata['booked_phone'][0];
		        $data['user_email'] = $userdata['user_email'][0];
		        $data['profilepicture'] = $userdata['profilepicture'][0];
		        $data['access_token']  = md5($user->ID);     
		        $result['settings']['success'] = 1;
		        $result['settings']['message'] = 'Login Successfully';
		        $result['data'] = array();
		        $result['data'][0] = $data;
		        echo json_encode($result);exit();	        
		    }
		}
	}


	// Forgot password
	function hbapi_reset(){
		global $wpdb;
		// $_REQUEST['action'] ='reset';
		// $_REQUEST['email_id'] = 'rd@satisnet.org';
		$action = $_REQUEST['action'];
		$email = trim($_REQUEST['email_id']);
		$result = array();	
		$error = '';
		$success = '';
		// check if we're in reset form
		if( isset( $action ) && 'reset' == $action ) 
		{
			if( empty( $email ) ) {
				$error = 'Enter a username or e-mail address..';
			} else if( ! is_email( $email )) {
				$error = 'Invalid username or e-mail address.';
			} else if( ! email_exists($email) ) {
				$error = 'There is no user registered with that email address.';
			} else {
			
				// lets generate our new password
				$random_password = wp_generate_password( 12, false );
				// echo $random_password;
				// Get user data by field and data, other field are ID, slug, slug and login
				$user = get_user_by( 'email', $email );
				
				$update_user = wp_update_user( array (
						'ID' => $user->ID, 
						'user_pass' => $random_password
					)
				);
				// pr($random_password);
				
				// if  update user return true then lets send user an email containing the new password
				if( $update_user ) {
					$to = $email;
					$subject = 'Your new password';
					$sender = get_option('name');
					
					$message = 'Your new password is: '.$random_password;
					
					$headers[] = 'MIME-Version: 1.0' . "\r\n";
					$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers[] = "X-Mailer: PHP \r\n";
					$headers[] = 'From: '.$sender.' < '.$email.'>' . "\r\n";
					
					$mail = wp_mail( $to, $subject, $message, $headers );
					if( $mail )
						$success = 'Check your email address for your new password.';
						
				} else {
					$error = 'Oops something went wrong updating your password.';
				}
			}			
			if( ! empty( $error ) ){
				// echo '<div class="error_login"><strong>ERROR:</strong> '. $error .'</div>';
				$result['settings']['success'] = 0;
		        $result['settings']['message'] = $error;	        
			}
			if( ! empty( $success ) ){
				$result['settings']['success'] = 1;
		        $result['settings']['message'] = $success;
			}
		}
		echo json_encode($result);
		exit();
	}


	function hbapi_register(){
		global $userpro;
		$result = array();
  		$form = array();
		$form['user_login'] = $_REQUEST['first_name'];
		$form['first_name'] = $_REQUEST['first_name'];
		$form['user_email'] = $_REQUEST['email_id'];
		$form['last_name'] = $_REQUEST['last_name'];
		$form['user_pass'] = $_REQUEST['password'];
	    $form['user_pass_confirm'] = $_REQUEST['password'];
		$form['profilepicture'] ='';
		$form['group'] = 'default';
		$form['template'] = 'register';
		$form['action'] = 'userpro_process_form';
		$form['unique_id'] = uniqid();
		$form['display_name'] =$_REQUEST['first_name'];
		$form['_wp_http_referer'] = admin_url('admin-ajax.php');
		$form['billing_phone'] = $_REQUEST['contact_number'];
		// pr($_FILES["profilepicture"],1);
		// $_REQUEST['profilepicture']['file'] = $ImagePic;


		if (isset($form['user_login']) ) {
			$user_exists = username_exists( $form['user_login'] );
			$user_login = $form['user_login'];
			$error = 'User Name Already Exist , Please Use another name';
		} else {
			$user_exists = username_exists( 'the_cow_that_did_run_after_the_elephant' );
			$user_login = $form['user_email'];
			$error = 'User Name Already Exist , Please Use another name';
		}
		if(email_exists($form['user_email']) == true ){
			$error = 'Email Already Exist , Please Enter Another Username';
		}
		

		if ( empty($user_exists) and email_exists($form['user_email']) == false ) {
			$user_id = $userpro->new_user( $form['user_login'], $form['user_pass'], $form['user_email'], $form, $type='standard', $approved=0 );
			// echo $user_id;
			add_user_meta( $user_id,'wheel_chair_access' , $_REQUEST['wheel_chair_access']); 
			update_user_meta( $user_id,'booked_phone' ,$_REQUEST['contact_number']); 
			add_user_meta( $user_id,'device_type' , $_REQUEST['device_type']); 
			add_user_meta( $user_id,'device_token' , $_REQUEST['device_token']); 
			// Upload Profile image
			if(!empty($_REQUEST['profile_pic'] )){
				$ImagePic = $_REQUEST['profile_pic'];	
			}elseif(!empty($_FILES['profile_pic'] )){
				$ImagePic = $_FILES['profile_pic'];	
			}
			// $ImagePic = $_FILES['profilepicture'];
			$userpro->do_uploads_dir( $user_id );
			// pr($ImagePic);
			if(!empty($ImagePic)){
				UploadProfile($ImagePic ,$user_id);
			}else{
				UploadProfile($ImagePic,$user_id ,false);
			}
			$otpresult = generateOTP($_REQUEST['contact_number']);		
			add_user_meta( $user_id,'user_otp' , $otpresult['otp']);
			add_user_meta( $user_id,'user_status' ,'pending');

			if (strpos($otpresult['status'], 'ERR') === false) {

				$user_info = get_userdata( $user_id );

		    	$to = $user_info->user_email;

				$from = get_option( 'admin_email' ); 
				$headers[] = 'MIME-Version: 1.0' . "\r\n";
				$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers[] = "X-Mailer: PHP \r\n";
				$headers[] = 'From: ' .$from. "\r\n";
				
				// $subject = get_option('payment_mail_subject');
	   //     		$plain_message = get_option('payment_mail_template');


				$subject =get_option('user_register_mail_subject');
	       		$message = get_option('user_register_mail_template');
	       		$message = str_replace(
	       					array('{user}',
	       						),
	       					array(
	       						  $user_info->display_name,
	       						),
	       					$message);
	       		
				$mail = wp_mail( $to, $subject, nl2br($message), $headers );

				$success = 'Account Created Successfully , Please confirm OTP to activate your Account';   
				$result['settings']['success'] = 1;
				$result['settings']['message'] =$success;
				$result['data'] = array();
				$result['data'][0]['user_id'] = $user_id;
				$result['data'][0]['access_token'] = md5($user_id);	
				// temprary solution for the sms 
				$result['data'][0]['OTP'] = $otpresult['otp'];	
			}else{
				$error ='Error in sending OTP ';
				$result['settings']['success'] = 0;
				$result['settings']['message'] = $error;				
				$result['data'] = array();			
			}
		}else{			
			$result['settings']['success'] = 0;
			$result['settings']['message'] = $error;
			$result['data'] = array();
		}
		echo json_encode($result);exit();
	}


	function hbapi_socialLogin(){
		global $userpro;

		$form = array();
		$form['user_login'] = $_REQUEST['first_name'];
		$form['first_name'] = $_REQUEST['first_name'];
		$form['last_name'] = $_REQUEST['last_name'];
		$form['user_email'] = $_REQUEST['fb_emailid'];
		$form['profilepicture'] =$_REQUEST['profileimageURL'];
		$form['group'] = 'default';
		$form['template'] = 'register';
		$form['action'] = 'userpro_process_form';
		$form['unique_id'] = uniqid();
		$form['display_name'] =$fullname[0];
		$form['_wp_http_referer'] = admin_url('admin-ajax.php');
		$form['billing_phone'] = $_REQUEST['fb_phone'];
		
		$user_exists = username_exists( $form['user_login'] );
		if ( empty($user_exists) and email_exists($form['user_email']) == false ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$user_id = $userpro->new_user( $form['user_login'],$random_password, $form['user_email'], $form, $type='standard', $approved=0 );
			add_user_meta( $user_id,'wheel_chair_access' ,'No'); 
			update_user_meta( $user_id,'booked_phone' , $_REQUEST['fb_phone']); 
			add_user_meta( $user_id,'device_type' , $_REQUEST['device_type']); 
			add_user_meta( $user_id,'device_token' , $_REQUEST['device_token']); 
			$result['settings']['message'] = 'User Registered Successfully'	;
			$result['settings']['success'] = 1;
		}else{
			$user = get_user_by('email', $form['user_email']);
			$user_id = $user->ID;
			$result['settings']['message'] = 'Login Successfully';
			$result['settings']['success'] = 1;
		}

			$data = array();
			$userdata = get_user_meta($user_id);
			// pr($userdata,1);
			$data['user_id'] = $user_id;
			$data['first_name'] = $userdata['first_name'][0];
			$data['last_name'] = $userdata['last_name'][0];
			$data['contact_number'] = $userdata['booked_phone'][0];
			$data['user_email'] = $userdata['user_email'][0];
			$data['profilepicture'] = $userdata['profilepicture'][0];
			$data['access_token'] = md5($user_id);
			$result['data'] = array();
			$result['data'][0] = $data;
			echo json_encode($result);exit();
	}

	function hbapi_changepassword(){
		$result = array();
		$userid = $_REQUEST['user_id'];
		$old_password = $_REQUEST['old_password'];
		$new_password = $_REQUEST['new_password'];
		$user = get_user_by( 'id', $userid);
		if ( $user && wp_check_password( $old_password, $user->data->user_pass, $user->ID) ){
		   wp_set_password( $new_password, $userid );
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'Password change Successfully';
			$result['data'] = array();
		}else{
		   	$result['settings']['success'] = 0;
			$result['settings']['message'] = 'Please Enter valid Old Password';
			$result['data'] = array();
		}	
		echo json_encode($result);exit();	
	}

	function hbapi_editProfile(){

		$userdata = array();
		$userdata['ID'] = $_REQUEST['user_id'];
		$user_id = $_REQUEST['user_id'];
		
		$userbyEmail = get_user_by('email', $_REQUEST['email_id']);	
		$userbyName = get_user_by('login', $_REQUEST['first_name']);	
		// pr($userbyName,1);
		if(($userbyEmail->ID != $_REQUEST['user_id']) && !empty($userbyEmail)){
			// email already exist
			$error = 'Email Already Exist , Please Use another email';
			$posts['settings']['success'] = 0;	
			$posts['settings']['message'] = $error;	
			echo json_encode($posts);exit();
		}elseif( ($userbyName->ID != $_REQUEST['user_id']) && !empty($userbyName)) {
			// Name Already Exist
			$error = 'User Name Already Exist , Please Use another name';
			$posts['settings']['success'] = 0;	
			$posts['settings']['message'] = $error;	
			echo json_encode($posts);exit();
		}

		if(!empty($_REQUEST['first_name'])){
			// $user = get_user_by('email', $email);
			$userdata['first_name'] = $_REQUEST['first_name'];
			update_user_meta($user_id, 'first_name', $_REQUEST['first_name']);	
		}
		if(!empty($_REQUEST['last_name'])){
			$userdata['last_name'] = $_REQUEST['last_name'];
			update_user_meta($user_id, 'last_name', $_REQUEST['last_name']);
		}
		if(!empty($_REQUEST['email_id'])){			
			$userdata['email_id'] = $_REQUEST['email_id'];
			update_user_meta($user_id, 'user_email', $_REQUEST['email_id']);			
		}

		if(!empty($_REQUEST['contact_number'])){
			update_user_meta($user_id, 'billing_phone', $_REQUEST['contact_number']);
			update_user_meta($user_id, 'booked_phone', $_REQUEST['contact_number']);
		}
		if(!empty($_REQUEST['wheel_chair_access'])){
			update_user_meta($user_id, 'wheel_chair_access', $_REQUEST['wheel_chair_access']);
		}
		if(!empty($_REQUEST['profile_pic'] )){
			$ImagePic = $_REQUEST['profile_pic'];	
		}elseif(!empty($_FILES['profile_pic'] )){
			$ImagePic = $_FILES['profile_pic'];	
		}
		if(!empty($ImagePic)){
			UploadProfile($ImagePic ,$_REQUEST['user_id']);
		}
		// pr($userdata,1);
		// UploadProfile($_REQUEST['profilepicture'] , $_REQUEST['user_id']);
		wp_update_user( $userdata );
		$posts['settings']['success'] = 1;	
		$posts['settings']['message'] = 'Profile Updated Succesfully';	
		echo json_encode($posts);exit();
	}

	function UploadProfile($file = array() , $user_id ='',$type = true){
		// type = false means NO IMage 
		// pr($file);

		$uploadUrl = wp_upload_dir();
		
		$path_parts = pathinfo($file["name"]);
        $image_path = preg_replace("/[^A-Za-z0-9]/", '', $path_parts['filename']).'_'.time().'.'.$path_parts['extension'];
        $tempFile = $file['tmp_name'];          //3             
        $targetPath = WP_CONTENT_DIR.'/uploads/userpro/'.$user_id; //4
       // $targetPath ='/var/www/html/colindale/wp-content/uploads/userpro/'.$user_id; //4
        $targetFile =  $targetPath.'/'. $image_path;  //5
		if (!file_exists($targetPath)) {
		    mkdir($targetPath, 755);
		}
		// echo $targetFile;
		if(move_uploaded_file($tempFile,$targetFile)){
			if($type){
				$image_url = get_site_url().'/wp-content/uploads/userpro/'.$user_id.'/'.$image_path;
			}else{
				$image_url = get_site_url() .'/wp-content/uploads/noimage.png';
			}
			update_user_meta($user_id, 'profilepicture', $image_url);
		}else{
			$image_url = get_site_url() .'/wp-content/uploads/noimage.png';
			update_user_meta($user_id, 'profilepicture', $image_url);
		}
	}

	function hbapi_getNews($page = ''){
		$args = array();
		$page = $_REQUEST['page_no'];
		$args['post_type'] = 'post';
		$args['showposts'] = 30;
		$args['paged']  = ($page > 1 ? $page: 1);
		$posts = array();
		$query = new WP_Query( $args );
		$data = array()	;
		while ( $query->have_posts() ) : $query->the_post(); 
			
			$content = trim(strip_tags(substr(get_the_content(),0,150),'<p><b><h1><h2><i><u>'));
			$data[] = array( 
				'news_id' => get_the_id(),
				'news_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
				'news_title' => html_entity_decode(get_the_title(get_the_id())),
				'news_published_date' =>  DataFormat(get_the_date()),
				'news_shortdescription' => strip_tags($content,'<p><b><h1><h2><i><u><p><a>'),
				'news_authorname' => get_the_author(),
				'news_status' => $query->post->post_status,
				);
		endwhile;
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Getting All News Data';			
			$posts['settings']['total_item_count'] = $query->found_posts;
			$posts['data'] = $data;
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No News Available';
			// $posts['total_page_count'] = $query->max_num_pages;
		}
		// pr($posts,1);
		echo json_encode($posts);exit();
	}
 
	function hbapi_getNewsDetail(){
		$args = array();
		$args['post_type'] = 'post';
		$args['p'] = $_REQUEST['news_id'];
		// $args['p'] = '4328';
		$posts = array();
		$query = new WP_Query( $args );
		$data = array();
		while ( $query->have_posts() ) : $query->the_post();  
			
			$content = strip_tags(get_the_content(),'<p><br><br/><a><h1><h2><i><u><a><ul><li><ol>');
			$data[] = array( 
				'news_id' => get_the_id(),
				'news_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
				'news_title' => html_entity_decode(get_the_title(get_the_id())),
				'news_published_date' =>  DataFormat(get_the_date()),
				'news_description' => get_the_content($_REQUEST['news_id']),
				'news_authorname' => get_the_author()
				);
		endwhile;
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Getting News Data';	
			$posts['data'] = $data;					
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No such News Available';
		}
		// pr($posts,1);
		echo json_encode($posts);exit();
	}

	function hbapi_getEventOld(){
		$args = array();
		$page = 1;
		$page = $_REQUEST['page'];
		$args['post_type'] = 'events';
		$args['showposts'] = 30;
		$args['page']  = ($page > 1 ? $page: 1);
		if(!empty($_REQUEST['date'])){
			$args['meta_query']= array(
				array(
					'key'     => 'fc_start',
					'value'   => $_REQUEST['date'],
					'compare' => '=',
				),
			);
		}
		$args['tax_query'] = array();
		$args['tax_query']['relation'] = 'OR';
		if($_REQUEST['venue_event'] != ''){
		
			$venue = explode(',',$_REQUEST['venue_event']);
			$venuetype =array(
						'taxonomy' => 'venue',
						'field'   => 'term_id',
						// 'terms' => array('163,161'),
						'terms' => $venue,
						'operator' => 'IN'
					);
			array_push($args['tax_query'], $venuetype);
		}
		if($_REQUEST['special_access'] != ''){
			$organizer = explode(',',$_REQUEST['special_access']);
			$org_type =array(
						'taxonomy' => 'organizer',
						'field'   => 'name',
						// 'terms' => array('163,161'),
						'terms' => $organizer,
						'operator' => 'IN'
					);
			array_push($args['tax_query'], $org_type);
		}	
		$posts = array();
		$query = new WP_Query( $args );
		$data = array();
		while ( $query->have_posts() ) : $query->the_post(); 
			
			$content = trim(strip_tags(substr(get_the_content(),0,150),'<p><b><h1><h2><i><u>'));
			$data[] = array( 
				'event_id' => get_the_id(),
				'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
				'event_title' => get_the_title(get_the_id()),
				'event_published_date' =>  DataFormat(get_the_date()),
				'event_short_description' => strip_tags($content,'<p><b><h1><h2><i><u><p><a><br><ul><li>'),
				'event_status' => $query->post->post_status,
				'event_start_date' => DataFormat(get_post_meta( get_the_id(), 'fc_start', true )),
				'event_start_time' => get_post_meta( get_the_id(), 'fc_start_time', true ),
				);
		endwhile;
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Get Event Data';			
			$posts['settings']['total_page_count'] = $query->max_num_pages;
			$posts['data'] = $data;
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No Event Available';
			$posts['total_page_count'] = $query->max_num_pages;
		}	
		// pr($posts,1);
		echo json_encode($posts);exit();
	}

	/* code by Rahul */
		function hbapi_getEvent(){
		$args = array();
		$page = 1;
		$page = $_REQUEST['page_no'];
		$args['post_type'] = 'product';
		$args['showposts'] = 30;
		$args['paged']  = ($page > 1 ? $page: 1);

		$args['meta_query'] = array();
		$args['meta_query']['relation'] = 'AND';
		$visibility[] = array(
					'key' => '_visibility',
					'value' => array( 'catalog', 'visible' ),
					'compare' => 'IN'
					);
		$visibility[] = array(
					'key'     => 'WooCommerceEventsEvent',
					'value'   => 'Event',
					'compare' => '=',
				);
					
		array_push($args['meta_query'], $visibility);

		if($_REQUEST['date']){
			$filter_date =array(
						'key'     => 'WooCommerceEventsDate',
						'value'   => $_REQUEST['date'],
						'compare' => '=',
					);
			array_push($args['meta_query'], $filter_date);	
		}
		/*if($_REQUEST['venue_event']){
			$filter_date =array(
						'key'     => 'WooCommerceEventsLocation',
						'value'   => $_REQUEST['venue_event'],
						'compare' => '=',
					);
			array_push($args['meta_query'], $filter_date);	
		}*/
		//if(!empty($_REQUEST['date'])){
			/*$args['meta_query']= array(
				array(
					'key'     => 'WooCommerceEventsEvent',
					'value'   => 'Event',
					'compare' => '=',
				),
			);*/
		//}
		 $args['tax_query'] = array();
		$args['tax_query']['relation'] = 'OR';
		if($_REQUEST['venue_event'] != ''){
			$venue = $_REQUEST['venue_event'];
			$args['meta_query']= array(
				array(
					'key'     => 'WooCommerceEventsLocation',
					'value'   => $venue,
					'compare' => '==',
				),
			);
		}
		/*if($_REQUEST['special_access'] != ''){
			$organizer = explode(',',$_REQUEST['special_access']);
			$org_type =array(
						'taxonomy' => 'organizer',
						'field'   => 'name',
						// 'terms' => array('163,161'),
						'terms' => $organizer,
						'operator' => 'IN'
					);
			array_push($args['tax_query'], $org_type);
		}	*/
		$posts = array();
		$query = new WP_Query( $args );
		$data = array();
		while ( $query->have_posts() ) : $query->the_post(); 
			
			$content = get_post_meta( get_the_id(), 'description', true );
			$short_description = substr($content,0,150);
			$price = get_post_meta(get_the_id(),'_price',true);
			$data[] = array( 
				'event_id' => get_the_id(),
				'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
				'event_title' => get_the_title(get_the_id()),
				'event_published_date' =>  DataFormat(get_the_date()),
				'event_short_description' => strip_tags($short_description,'<a>'),
				'event_status' => $query->post->post_status,
				'event_price' => $price,
				'event_start_date' => DataFormat(get_post_meta( get_the_id(), 'WooCommerceEventsDate', true )),
				'event_start_time' => get_post_meta( get_the_id(), 'WooCommerceEventsHour', true ).":".get_post_meta( get_the_id(), 'WooCommerceEventsMinutes', true ),
				'event_end_time' => get_post_meta( get_the_id(), 'WooCommerceEventsHourEnd', true ).":".get_post_meta( get_the_id(), 'WooCommerceEventsMinutesEnd', true ),
				'event_location' => get_post_meta( get_the_id(), 'WooCommerceEventsLocation', true ),
				'event_latitute' =>get_post_meta(get_the_id() ,'latitude',true),
				'event_longitude' => get_post_meta(get_the_id() ,'longitude',true),
				'event_end_date' => get_post_meta(get_the_id() ,'event_end_date',true)
				);
		endwhile;
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Get Event Data';			
			$posts['settings']['total_item_count'] = $query->found_posts;
			$posts['data'] = $data;
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No Event Available';
		}	
		// pr($posts,1);
		echo json_encode($posts);
		exit();
	}
	

	function hbapi_getEventDetail(){

			$args = array();
			$args['post_type'] = 'product';
			$args['p'] = $_REQUEST['event_id'];
			// $args['p'] = '4347';
			$posts = array();
			$query = new WP_Query( $args );
			$data = array();
			while ( $query->have_posts() ) : $query->the_post(); 
			
			// get Related Images
			// $rhc_top_image_id = get_post_meta(get_the_id(), 'rhc_top_image', true);
			// $rhc_dbox_image_id = get_post_meta(get_the_id(), 'rhc_dbox_image', true);
			// $rhc_tooltip_image_id = get_post_meta(get_the_id(), 'rhc_tooltip_image', true);
			// $rhc_month_image_id = get_post_meta(get_the_id(), 'rhc_month_image', true);
			// $imageArr = array();
			// if($rhc_top_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_top_image_id); 		
			// }
			// if($rhc_dbox_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_dbox_image_id); 		
			// }
			// if($rhc_tooltip_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_tooltip_image_id ); 
			// }
			// if($rhc_month_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_month_image_id);
			// }
			// $imageArr = '';	
			// if($rhc_top_image_id > 0){
				// $imageArr .= get_post_field('guid', $rhc_top_image_id).','; 						
			// }
			// if($rhc_dbox_image_id > 0){
				// $imageArr .= get_post_field('guid', $rhc_dbox_image_id).','; 		
			// }
			// if($rhc_tooltip_image_id > 0){
				// $imageArr .= get_post_field('guid', $rhc_tooltip_image_id ).','; 
			// }
			// if($rhc_month_image_id > 0){
				// $imageArr .= get_post_field('guid', $rhc_month_image_id).',';
			// }
			// $imageArr = substr($imageArr,0 ,-1);
			// var_dump($imageArr);exit();
			
				global $product;
				 $attachment_ids = $product->get_gallery_attachment_ids( get_the_id());

				foreach( $attachment_ids as $attachment_id ) 
				{
				 $image_link .= wp_get_attachment_url( $attachment_id ).',';
				}
				if ($image_link=="") {
					$image_link = "";
				}
				$termid = wp_get_post_terms( get_the_id(), 'organizer');
				$termMeta = get_term_meta($termid[0]->term_id);
				// pr($termid,1);	

				$venueid = wp_get_post_terms( get_the_id(), 'venue');
				// pr($venueid,1);
				$venueMeta = get_term_meta($venueid[0]->term_id);
				// pr($venueMeta,1);		

				//$content = trim(strip_tags(get_the_content(),'<p><b><h1><h2><i><u>'));
				$event_description = get_post_meta( get_the_id(), 'description', true );
				$data= array( 
					'event_id' => get_the_id(),
					'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
					'event_title' => get_the_title(get_the_id()),
					'event_published_date' =>  DataFormat(get_the_date()),
					'event_description' => strip_tags($event_description,'<a>'),
					'event_status' => $query->post->post_status,
					'event_start_date' => DataFormat(get_post_meta( get_the_id(), 'WooCommerceEventsDate', true )),
					'event_start_time' => get_post_meta( get_the_id(), 'WooCommerceEventsHour', true ).":".get_post_meta( get_the_id(), 'WooCommerceEventsMinutes', true ),
					'event_end_time' => get_post_meta( get_the_id(), 'WooCommerceEventsHourEnd', true ).":".get_post_meta( get_the_id(), 'WooCommerceEventsMinutesEnd', true ),
					'event_images' =>$image_link,
					'event_posted_by' =>get_the_author(), 
					'event_contact' => get_post_meta( get_the_id(), 'WooCommerceEventsSupportContact', true ),
					'event_email_id' => get_post_meta( get_the_id(), 'WooCommerceEventsEmail', true ),
					'event_venue' => get_post_meta( get_the_id(), 'WooCommerceEventsLocation', true ),
					//'event_GPS' => get_post_meta( get_the_id(), 'WooCommerceEventsGPS', true ),
					'event_price' => get_post_meta(get_the_id(),'_price',true),
					'event_address' => get_post_meta( get_the_id(), 'address', true ),
					'event_latitute' =>get_post_meta(get_the_id() ,'latitude',true),
					'event_longitude' => get_post_meta(get_the_id() ,'longitude',true),
					'event_end_date' => get_post_meta(get_the_id() ,'event_end_date',true)
				);						
			endwhile;
			if(count($data) > 0){
				$posts['settings']['success'] = 1;
				$posts['settings']['message'] = 'Get Event Data';
				$posts['data'] = $data;			
			}else{
				$posts['settings']['success'] = 0;
				$posts['settings']['message'] = 'No Such Event Available';
			}
			// pr($posts,1);
			echo json_encode($posts);exit();
	}
	
	
	function hbapi_event_booking(){
			
			require plugin_dir_path(__FILE__ ) .'web-service-api.php';
			$product_id = $_REQUEST['event_id'];
			$user_id = $_REQUEST['user_id'];
			
			
			$stock = get_post_meta($product_id,'_stock',true);
			$meta_value = $stock-1;
			
			// print_r( $client->customers->get_orders( 44 ) );
			// exit();
			
			$order_detail = [
				'order' => [
					'customer_id' => $user_id,
					'line_items' => [
						[
							'product_id' => $product_id,
							'quantity' => 1,
							'managing_stock'   => true
          					
						]
					]
				]
			];

		$data = $client->orders->create($order_detail);
		
		
		
		if(count($data) > 0){
			$to =get_post_meta( $product_id, 'WooCommerceEventsEmail',true );
			
	    	$from = get_option( 'admin_email' ); 
			$headers[] = 'MIME-Version: 1.0' . "\r\n";
			$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers[] = "X-Mailer: PHP \r\n";
			$headers[] = 'From: ' .$from. "\r\n";
			
			$subject = get_option('customer_register_mail_subject');
       		$plain_message = get_option('customer_register_mail_template');

       		$find = array ( "{EVENT}" ,"{CUSTOMERNAME}");
       		$event = get_the_title($product_id);
       		$userdata = get_userdata($user_id);
			$customername = $userdata->display_name;
       		$replace = array ( $event , $customername);
       		$message = str_replace($find,$replace,$plain_message);
			$mail = wp_mail( $to, $subject, nl2br($message), $headers );


		 	if(isset($stock)){
		 	update_post_meta($product_id, '_stock', $meta_value, $stock); 
		 	}

		 	$order_id = $data->order->id;
		 	$client->orders->update_status( $order_id, 'Processing' );

			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Event Succesfully Booked';
			$posts['data'] = $data;			
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'Error in booking Event';
		}
	// pr($posts,1);
		echo json_encode($posts);exit();
	}
	
	function hbapi_booked_events(){
			
			require plugin_dir_path(__FILE__ ) .'web-service-api.php';
			$data = array();
			$full_order_data = array();
			$customer_id = $_REQUEST['user_id'];
			
			$full_data = $client->customers->get_orders( $customer_id ,array( 'filter[limit]' => '-1' ) );
			
			$full_order_data = $full_data ->orders;
			
			// print_r($full_data);
			// exit();
			for($i=0; $i<count($full_order_data); $i++){
				$booking_ids[] = $full_order_data[$i]->id;
				$booking_dates[] = $full_order_data[$i]->created_at; 
				$product_ids[] = $full_order_data[$i]->line_items;
			}
			// echo $product_ids[0]->product_id;
			foreach($product_ids as $product_id){
				// print_r($product_id);
					$product_single_id[] = $product_id[0] -> product_id;
					$order_type[] = get_post_meta( $product_id[0] -> product_id, 'WooCommerceEventsEvent', true );
				// if($order_type == 'Event'){
					 // $product_single_id[] = $product_id[0] -> product_id;
				// }
				
			} 
			
			// print_r($product_single_id);
			$same_array = array();
			for($i=0; $i<count($booking_ids); $i++){
				
				// print_r($product_ids[0]);
				if($order_type[$i] == 'Event'){
					$content = get_post_meta( $product_single_id[$i], 'description', true );
					$short_description = substr($content,0,150);
					$content = trim(strip_tags(substr(get_the_content( $product_single_id[$i]),0,150),'<p><b><h1><h2><i><u>'));
					$same_id = $product_single_id[$i].DataFormat($booking_dates[$i]);
					

					if(in_array($same_id, $same_array)){
						continue;
					}
					array_push($same_array, $same_id);
					$data[] = array(
					'booking_id' => $booking_ids[$i],
					'event_id' => $product_single_id[$i],
					'event_short_description' => strip_tags($short_description,'<a>'),
					'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( $product_single_id[$i] )),
					'event_start_date' => DataFormat(get_post_meta( $product_single_id[$i], 'WooCommerceEventsDate', true )),
					'event_title' => get_the_title($product_single_id[$i]),
					'booking_date' => DataFormat($booking_dates[$i]),
					);
				}
			}
			//print_r($same_array);exit();
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Booked Event List';
			$posts['data'] = $data;			
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No booked event found';
		}
	// pr($posts,1);
		echo json_encode($posts);exit();
	}
	
	
	function hbapi_venue_booking(){
		require plugin_dir_path(__FILE__ ) .'web-service-api.php';
		// echo '<pre>';
			
		//print_r( $client->orders->get( 4554 ) );

		$timestamp = strtotime($_REQUEST['booking_date']);
		$user_id = $_REQUEST['user_id'];
		$timeslot = $_REQUEST['time_slot'];;
		$product_id = $_REQUEST['venue_id'];
		$room_type = $_REQUEST['room_type'];
		$facilities = $_REQUEST['facilities'];
		

		/* to get calander id*/
		$new_calender_id = get_post_meta( $product_id, 'calendor_id', true );
		$product_title = get_the_title( $product_id  );
		$term = get_term_by('id', $new_calender_id, 'booked_custom_calendars');
		
		$calendar_id = $term -> term_id;
		$new_calender_name = $term -> name;
		
		$appointment_default_status = get_option('booked_new_appointment_default','draft');
		$time_format = get_option('time_format');
		$date_format = get_option('date_format');
		$calendar_id_for_cf = $calendar_id;
		if ($calendar_id):
			$calendar_id = array($calendar_id);
			$calendar_id = array_map( 'intval', $calendar_id );
			$calendar_id = array_unique( $calendar_id );
		endif;
		// exit();
		
		
		$new_post = apply_filters('booked_new_appointment_args', array(
				'post_title' => date_i18n($date_format,$timestamp).' @ '.date_i18n($time_format,$timestamp).' (User: '.$user_id.')',
				'post_content' => '',
				'post_status' => $appointment_default_status,
				'post_date' => date('Y',strtotime($date)).'-'.date('m',strtotime($date)).'-01 00:00:00',
				'post_author' => $user_id,
				'post_type' => 'booked_appointments'
			));
			$post_id = wp_insert_post($new_post);

			update_post_meta($post_id, '_appointment_timestamp', $timestamp);
			update_post_meta($post_id, '_appointment_timeslot', $timeslot);
			update_post_meta($post_id, '_appointment_user', $user_id);
			
			
			if (apply_filters('booked_update_appointment_calendar', true)) {
				if (!empty($calendar_id)): 
					$calendar_term = get_term_by('id',36,'booked_custom_calendars'); 
					$calendar_name = $calendar_term->name; 
					wp_set_object_terms($post_id,$calendar_id,'booked_custom_calendars'); 
					else: $calendar_name = false; 
					endif;
			}
			
			do_action('booked_new_appointment_created', $post_id);

		
		
		$variation = array("booked_wc_appointment_id" => $post_id,"booked_wc_appointment_cal_name" => $new_calender_name);

		if($room_type != ""){
			//$room_type_array = array("pa_room-type" => $room_type);
			$variation["pa_room-type"] = $room_type;
		}
		if($facilities != ""){
			$variation["pa_facilities"] = $facilities;
		}
		$order_detail = [
				'order' => [
					'customer_id' => $user_id,
					'line_items' => [
						[
							'product_id' => $product_id,
							'quantity' => 1,
							'variations' => $variation
						]
					]
				]
			];
		

		$data = $client->orders->create($order_detail);
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Venue Succesfully Booked';
			$posts['data'] = $data;			
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'Error in Venue Event';
		}
	// pr($posts,1);
		echo json_encode($posts);exit();
	}

	function hbapi_my_bookings_venues(){
			
			require plugin_dir_path(__FILE__ ) .'web-service-api.php';
			$data = array();
			$full_order_data = array();
			$customer_id = $_REQUEST['user_id'];
			
			$full_data = $client->customers->get_orders( $customer_id ,array( 'filter[limit]' => '-1' ) );
			
			$full_order_data = $full_data ->orders;
			// echo '<pre>';
			// print_r($full_data);
			// exit();
			
			for($i=0; $i<count($full_order_data); $i++){
				$booking_ids[] = $full_order_data[$i]->id;
				$booking_dates[] = $full_order_data[$i]->created_at; 
				$product_ids[] = $full_order_data[$i]->line_items;
				$token_amount[] = $full_order_data[$i]->total;
				$order_status[] = $full_order_data[$i]->status;
			}
			
			foreach($product_ids as $product_id){
				
					$product_single_id[] = $product_id[0] -> product_id;
					$order_type[] = get_post_meta( $product_id[0] -> product_id, 'WooCommerceEventsEvent', true );
					$booked_time = $product_id[0] -> meta;
					$booked_time_list[] =  $booked_time[0]->value; 
			} 
			
			// print_r($product_single_id);
			// print_r($booking_ids);
			// print_r($order_type);
			// exit();
			for($i=0; $i<count($booking_ids); $i++){
				
				if($order_type[$i] == 'NotEvent'){
					$paid_token = "";
					$next_date = hbapi_getNextAvailableDate($product_single_id[$i]);
					$paid_token = ($order_status[$i] != "pending") ? $token_amount[$i] : 'pending payment' ; 
					//$content = trim(strip_tags(substr(get_the_content( $product_single_id[$i]),0,150),'<p><b><h1><h2><i><u>'));
					$content = get_post_meta( $product_single_id[$i], 'description', true );
					$short_description = substr($content,0,150);
					$data[] = array(
					'booking_id' => $booking_ids[$i],
					'venue_id' => $product_single_id[$i],
					'venue_short_description' => strip_tags($short_description,'<a>'),
					'venue_default_image' => wp_get_attachment_url( get_post_thumbnail_id( $product_single_id[$i] )),
					'venue_title' => get_the_title($product_single_id[$i]),
					'venue_address' => get_post_meta($product_single_id[$i] ,'address',true),
					'venue_max_capacity' => get_post_meta($product_single_id[$i] ,'max_capacity',true),
					'venue_next_availabilty_date' => DataFormat($next_date),
					'venue_token_amount' => $token_amount[$i],
					'venue_paid_token' => $paid_token,
					'venue_booked_datetime' => $booked_time_list[$i],
					'venue_latitute' =>get_post_meta($product_single_id[$i] ,'latitude',true),
					'venue_longitude' => get_post_meta($product_single_id[$i] ,'longitude',true)
					);
				}
			}
			
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Booked Venue List';
			$posts['data'] = $data;			
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No booked Venue found';
		}
	// pr($posts,1);
		echo json_encode($posts);exit();
	}


	function hbapi_booked_venue_detail(){
			
			require plugin_dir_path(__FILE__ ) .'web-service-api.php';
			$data = array();
			$full_order_data = array();
			$booking_id = $_REQUEST['booking_id'];
			
			$full_data = $client->orders->get( $booking_id );
			
			//$full_order_data = $full_data ->orders;
			// echo '<pre>';
			// print_r($full_data);
			// exit();
			
			
				$booking_ids = $full_data->order->id;
				$booking_dates = $full_data->order->created_at; 
				$product_ids[] = $full_data->order->line_items;
				$token_amount = $full_data->order->total;
				$order_status = $full_data->order->status;
			
			
			foreach($product_ids as $product_id){
				
					$product_single_id = $product_id[0] -> product_id;
					$order_type = get_post_meta( $product_id[0] -> product_id, 'WooCommerceEventsEvent', true );
					$booked_time = $product_id[0] -> meta;
					$booked_time_list =  $booked_time[0]->value; 
			} 
			
			// print_r($product_single_id);
			// exit();
			// print_r($booking_ids);
			// print_r($order_type);
			// exit();
			 //for($i=0; $i<count($booking_ids); $i++){
				
				if($order_type == 'NotEvent'){
					$next_date = hbapi_getNextAvailableDate($product_single_id);
					$term =  get_the_terms( $product_single_id, 'product_cat' );
					$facilityArr= array();
					$i=0;
					foreach ($term as $t) {
					   $parentId = $t->parent;
					   // 92 is the term id of Facility Term
					   if($parentId == 92){
					     $facilityArr[$i]['term_id'] = $t->term_id;
					     $facilityArr[$i]['name'] = $t->name;
					   	 $i++;
					   }				   
					}

					// print_r($facilityArr);
					// exit();	
					//$content = trim(strip_tags(substr(get_the_content( $product_single_id[$i]),0,150),'<p><b><h1><h2><i><u>'));
					//$content = get_post_meta( $product_single_id, 'description', true );
					//$short_description = substr($content,0,150);
					$venue_description = get_post_meta( $product_single_id, 'description', true );
					$paid_token = ($order_status != "pending") ? $token_amount : 'pending payment' ; 
					$data[] = array(
					'booking_id' => $booking_ids,
					'venue_id' => $product_single_id,
					'venue_description' => strip_tags($venue_description,'<a>'),
					'venue_default_image' => wp_get_attachment_url( get_post_thumbnail_id( $product_single_id )),
					'venue_title' => get_the_title($product_single_id),
					'venue_max_capacity' => get_post_meta($product_single_id ,'max_capacity',true),
					'venue_next_availabilty_date' => DataFormat($next_date),
					'venue_paid_token' => $paid_token,
					'venue_address' => get_post_meta($product_single_id ,'address',true),
					'venue_token_amount' => $token_amount,
					'venue_booked_datetime' => $booked_time_list,
					'venue_facility' => $facilityArr,
					'venue_contactno' => get_post_meta($product_single_id ,'contactno',true),
					'venue_latitute' =>get_post_meta($product_single_id ,'latitude',true),
					'venue_longitude' => get_post_meta($product_single_id ,'longitude',true)
					);
				}
				
			// }
			
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Booked Venue Detail';
			$posts['data'] = $data;			
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No booked Venue found';
		}
	// pr($posts,1);
		echo json_encode($posts);exit();
	}


	
	function hbapi_after_payment(){
		global $woocommerce;
	    $order_id = $_REQUEST['order_id'];
	    $user_id = $_REQUEST['user_id'];
	    $payment_status = $_REQUEST['payment_status'];
	    
	    if ( $payment_status == 1){
	    	
	    	$order = new WC_Order( $order_id );
	    	$status = $order->update_status( 'processing' );
	    	if($status){

		   		$user_info = get_userdata( $user_id );

		    	$to = $user_info->user_email;
		    	

		    	$from = get_option( 'admin_email' ); 
				$headers[] = 'MIME-Version: 1.0' . "\r\n";
				$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers[] = "X-Mailer: PHP \r\n";
				$headers[] = 'From: ' .$from. "\r\n";
				
				$subject = get_option('payment_mail_subject');
	       		$plain_message = get_option('payment_mail_template');

	       		$find = array ("{USER}" , "{ORDERID}" , "{LINK}");
	       		$link = $order->get_view_order_url();
	       		$replace = array ( $user_info->first_name , $order_id , $link );
	       		$message = str_replace($find,$replace,$plain_message);

				$mail = wp_mail( $to, $subject, nl2br($message), $headers );

				$posts['settings']['success'] = 1;
				$posts['settings']['message'] = 'Payment Succesfull';

			}
			else {
				
				$posts['settings']['success'] = 0;
				$posts['settings']['message'] = 'Payment faild';

			}
		}
	    else {
	    	$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'Payment failed';
	    }
	   
	    echo json_encode($posts);exit();
	}
	function hbapi_add_comment(){
		$user_id = $_REQUEST['user_id'];
		$content = $_REQUEST['content'];
		$post_id = $_REQUEST['post_id'];
		$time = current_time('mysql');
		$user_info = get_userdata( $user_id );
		$data = array(
	    	'comment_post_ID' => $post_id,
	    	'comment_author' => $user_info->user_login,
	    	'comment_author_email' => $user_info->user_email,
		    'comment_content' => $content,
		    'comment_type' => '',
		    'comment_parent' => 0,
		    'user_id' => $user_id,
		   	'comment_date' => $time,
		    'comment_approved' => 1,
		);
		$comment = wp_insert_comment($data);
		if($comment){

			$to = get_option( 'admin_email' );
			
			$headers[] = 'MIME-Version: 1.0' . "\r\n";
			$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers[] = "X-Mailer: PHP \r\n";
			$headers[] = 'From: '.$user_info->first_name.' < '.$user_info->user_email.'>' . "\r\n";
			$subject = get_option('comment_mail_subject');
       		$plain_message = get_option('comment_mail_template');


       		$find = array ("{USER}" , "{LINK}");
       		$link = get_permalink( $post_id );
       		$replace = array ( $user_info->first_name ,  $link );
       		$message = str_replace($find,$replace,$plain_message);

			$mail = wp_mail( $to, $subject, nl2br($message) , $headers );

			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Comment successfully posted';
		}
		else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'Error in Commenting';
		}

		echo json_encode($posts);exit();


	}

	function hbapi_list_comments(){
		
		$post_id = $_REQUEST['post_id'];
		$next_post = $_REQUEST['show_all'];
		if($next_post == 1){
			$post_per_page = null ;
		}
		else{
			$post_per_page = 5;
		}

		$args = array(
			'status' => 'approve',
			'number' => $post_per_page,
			'post_id' => $post_id, // use post_id, not post_ID
		);
		$comments = get_comments($args);
		
		foreach($comments as $comment) :
			
			$data[]  = array(
				'comment_content' =>$comment->comment_content,
				'comment_ID' =>$comment->comment_ID,
				'comment_author' =>$comment->comment_author,
				'comment_date' =>$comment->comment_date
			);

		endforeach;


		if($data){

			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Comments List';
			$posts['data'] = $data;	
		}
		else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No Comments Found';
		}
		//pr($posts,1);

		echo json_encode($posts);exit();


	}

	function hbapi_delete_comment(){
		
		$comment_id = $_REQUEST['comment_id'];
		
		$comment = wp_delete_comment( $comment_id, true );
		
		if($comment){

			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Comment deleted';
		}
		else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'Error in deleting';
		}

		echo json_encode($posts);exit();


	}
	function hbapi_find_nearby($lat , $lng){
		
		//$venue_id =  $_REQUEST['venue_id'];
		
		$nearbyCities = get_nearby_cities($lat, $lng ,25);
		
		for ($i=0; $i < count($nearbyCities) ; $i++) { 
	 		$post_ids[] = $nearbyCities[$i] -> post_id;
	 		$post_distance[] = $nearbyCities[$i] -> distance ;
		}
		
		$data = array();
		for($i = count($post_ids); $i >= 0; $i--){

				$pid = $post_ids[$i];
				
				$price = get_post_meta($pid,'_price',true);
				$address = get_post_meta($pid ,'address',true);
				$desc = substr(get_post_meta($pid ,'description',true),0,150);
				$capacity = get_post_meta($pid ,'max_capacity',true);
				$date = hbapi_getNextAvailableDate($pid);
				$status =  get_post_status( $post_ids[$i] );
				$venue_type = get_post_meta( $pid, 'WooCommerceEventsEvent', true );
				
				if($venue_type == 'NotEvent' && $status="publish"){
				$data[] = array( 
					'venue_id' => $post_ids[$i],
					'venue_default_image' =>  wp_get_attachment_url( get_post_thumbnail_id( $post_ids[$i] )),
					'venue_title' => get_the_title($post_ids[$i]),
					'venue_address' => $address,
					'venue_short_description' =>strip_tags($desc,'<a>'),
					'venue_next_availabilty_date' =>DataFormat($date),
					'venue_max_capacity' =>  $capacity,
					'venue_added_datetime' => DataFormat($post_ids[$i]),
					'venue_status' =>  get_post_status( $post_ids[$i] ),
					'venue_latitute' =>get_post_meta($post_ids[$i] ,'latitude',true),
					'venue_longitude' => get_post_meta($post_ids[$i] ,'longitude',true),
					'venue_distnace' => $post_distance[$i]
					);
			}
		}


		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Get Venue List';			
			$posts['settings']['total_item_count'] = $loop->found_posts;
			$posts['data'] = $data;
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No venue Available';			
		}	
		// pr($posts,1);
		echo json_encode($posts);exit();

	}

	function get_nearby_cities($lat, $long, $distance){
    global $wpdb;
    $nearbyCities = $wpdb->get_results( 
    "SELECT DISTINCT    
        latitude.post_id,
        latitude.meta_key,
        latitude.meta_value as cityLat,
        longitude.meta_value as cityLong,
        ((ACOS(SIN($lat * PI() / 180) * SIN(latitude.meta_value * PI() / 180) + COS($lat * PI() / 180) * COS(latitude.meta_value * PI() / 180) * COS(($long - longitude.meta_value) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance,
        wp_posts.post_title
    FROM 
        wp_postmeta AS latitude
        LEFT JOIN wp_postmeta as longitude ON latitude.post_id = longitude.post_id
        INNER JOIN wp_posts ON wp_posts.ID = latitude.post_id
    WHERE latitude.meta_key = 'latitude' AND longitude.meta_key = 'longitude'
    HAVING distance < $distance
    ORDER BY distance ASC;"
    );

    if($nearbyCities){
        return $nearbyCities;
    }
}



	
	
	/* code end */



	function hbapi_getEventDetailOld(){

		$args = array();
		$args['post_type'] = 'events';
		$args['p'] = $_REQUEST['event_id'];
		// $args['p'] = '4347';
		$posts = array();
		$query = new WP_Query( $args );
		$data = array();
		while ( $query->have_posts() ) : $query->the_post(); 
			
			// get Related Images
			$rhc_top_image_id = get_post_meta(get_the_id(), 'rhc_top_image', true);
			$rhc_dbox_image_id = get_post_meta(get_the_id(), 'rhc_dbox_image', true);
			$rhc_tooltip_image_id = get_post_meta(get_the_id(), 'rhc_tooltip_image', true);
			$rhc_month_image_id = get_post_meta(get_the_id(), 'rhc_month_image', true);
			// $imageArr = array();
			// if($rhc_top_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_top_image_id); 		
			// }
			// if($rhc_dbox_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_dbox_image_id); 		
			// }
			// if($rhc_tooltip_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_tooltip_image_id ); 
			// }
			// if($rhc_month_image_id > 0){
			// 	$imageArr[]['url'] = get_post_field('guid', $rhc_month_image_id);
			// }
			$imageArr = '';	
			if($rhc_top_image_id > 0){
				$imageArr .= get_post_field('guid', $rhc_top_image_id).','; 						
			}
			if($rhc_dbox_image_id > 0){
				$imageArr .= get_post_field('guid', $rhc_dbox_image_id).','; 		
			}
			if($rhc_tooltip_image_id > 0){
				$imageArr .= get_post_field('guid', $rhc_tooltip_image_id ).','; 
			}
			if($rhc_month_image_id > 0){
				$imageArr .= get_post_field('guid', $rhc_month_image_id).',';
			}
			$imageArr = substr($imageArr,0 ,-1);
			// var_dump($imageArr);exit();
			$termid = wp_get_post_terms( get_the_id(), 'organizer');
			$termMeta = get_term_meta($termid[0]->term_id);
			// pr($termid,1);	

			$venueid = wp_get_post_terms( get_the_id(), 'venue');
			// pr($venueid,1);
			$venueMeta = get_term_meta($venueid[0]->term_id);
			// pr($venueMeta,1);		

			$content = trim(strip_tags(get_the_content(),'<p><b><h1><h2><i><u>'));
			
			$data= array( 
				'event_id' => get_the_id(),
				'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
				'event_title' => get_the_title(get_the_id()),
				'event_published_date' =>  DataFormat(get_the_date()),
				'event_description' => strip_tags($content,'<a>'),
				'event_status' => $query->post->post_status,
				'event_start_date' => DataFormat(get_post_meta( get_the_id(), 'fc_start', true )),
				'event_start_time' => get_post_meta( get_the_id(), 'fc_start_time', true ),
				'event_end_date' => DataFormat(get_post_meta( get_the_id(), 'fc_end', true )),
				'event_end_time' => get_post_meta( get_the_id(), 'fc_end_time', true ),
				'event_images' =>$imageArr,
				'event_posted_by' => $termMeta['websitelabel'][0],
				'event_contact' => $termMeta['phone'][0],
				'event_email_id' => $termMeta['email'][0],
				'event_address' => $venueMeta['address'][0],
				'event_latitude' => $venueMeta['glat'][0],
				'event_longitude' => $venueMeta['glon'][0],
				'event_type' => ''
				);						
		endwhile;
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Get Event Data';
			$posts['data'] = $data;			
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No Such Event Available';
		}
		echo json_encode($posts);exit();
	}

	function hbapi_getTimeSlot($venueid = '' , $vDate = '' ,$type=true){
		// $date,$calendar_id = false
			if($venueid){
				$venue_id = $venueid;
				$date = $vDate;
			}else{
				$venue_id = $_REQUEST['venue_id'];
				$date = $_REQUEST['date'];
				// $venue_id = '2767';
				// $date = '2016-03-14';
			}

			$result = array();
			$data = array();
			$calendar_id = get_post_meta($venue_id ,'calendor_id',true);
			// pr($calendar_id,1);
			$local_time = current_time('timestamp');

			$year = date('Y',strtotime($date));
			$month = date('m',strtotime($date));
			$day = date('d',strtotime($date));

			$start_timestamp = strtotime($year.'-'.$month.'-'.$day.' 00:00:00');
			$end_timestamp = strtotime($year.'-'.$month.'-'.$day.' 23:59:59');

			$time_format = get_option('time_format');
			$date_display = date_i18n('F jS, Y',strtotime($date));
			$day_name = date('D',strtotime($date));

			/*
			Grab all of the appointments for this day
			*/

			$args = array(
				'post_type' => 'booked_appointments',
				'posts_per_page' => -1,
				'post_status' => 'any',
				'meta_query' => array(
					array(
						'key'     => '_appointment_timestamp',
						'value'   => array( $start_timestamp, $end_timestamp ),
						'compare' => 'BETWEEN'
					)
				)
			);

			if ($calendar_id):
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'booked_custom_calendars',
						'field'    => 'id',
						'terms'    => $calendar_id,
					)
				);
			endif;

			$appointments_array = array();

			$bookedAppointments = new WP_Query( $args );
			if($bookedAppointments->have_posts()):
				while ($bookedAppointments->have_posts()):
					$bookedAppointments->the_post();
					global $post;
					$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
					$timeslot = get_post_meta($post->ID, '_appointment_timeslot',true);
					$user_id = get_post_meta($post->ID, '_appointment_user',true);
					$day = date('d',$timestamp);
					$appointments_array[$post->ID]['post_id'] = $post->ID;
					$appointments_array[$post->ID]['timestamp'] = $timestamp;
					$appointments_array[$post->ID]['timeslot'] = $timeslot;
					$appointments_array[$post->ID]['status'] = $post->post_status;
					$appointments_array[$post->ID]['user'] = $user_id;
				endwhile;
				// $appointments_array = apply_filters('booked_appointments_array', $appointments_array);
			endif;

		
			if ($calendar_id):
				$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				if (!$booked_defaults):
					$booked_defaults = get_option('booked_defaults');
				endif;
			else :
				$booked_defaults = get_option('booked_defaults');
			endif;

			$formatted_date = date('Ymd',strtotime($date));
			$booked_defaults = booked_apply_custom_timeslots_filter($booked_defaults,$calendar_id);

			if (isset($booked_defaults[$formatted_date]) && !empty($booked_defaults[$formatted_date])):
				$todays_defaults = (is_array($booked_defaults[$formatted_date]) ? $booked_defaults[$formatted_date] : json_decode($booked_defaults[$formatted_date],true));
			elseif (isset($booked_defaults[$formatted_date]) && empty($booked_defaults[$formatted_date])):
				$todays_defaults = false;
			elseif (isset($booked_defaults[$day_name]) && !empty($booked_defaults[$day_name])):
				$todays_defaults = $booked_defaults[$day_name];
			else :
				$todays_defaults = false;
			endif;

			/*
			There are timeslots available, let's loop through them
			*/
			// print_r($todays_defaults);exit();
			if ($todays_defaults){

				ksort($todays_defaults);

				$temp_count = 0;

				foreach($todays_defaults as $timeslot => $count):

					$appts_in_this_timeslot = array();

					/*
					Are there any appointments in this particular timeslot?
					If so, let's create an array of them.
					*/

					foreach($appointments_array as $post_id => $appointment):
						if ($appointment['timeslot'] == $timeslot):
							$appts_in_this_timeslot[] = $post_id;
						endif;
					endforeach;

					/*
					Calculate the number of spots available based on total minus the appointments booked
					*/

					$spots_available = $count - count($appts_in_this_timeslot);
					$spots_available = ($spots_available < 0 ? 0 : $spots_available);

					/*
					Display the timeslot
					*/
					
					$timeslot_parts = explode('-',$timeslot);
					// print_r($timeslot_parts);exit();
					$buffer = get_option('booked_appointment_buffer',0);

					if ($buffer):
						$current_timestamp = $local_time;
						$buffered_timestamp = strtotime('+'.$buffer.' hours',$current_timestamp);
						$current_timestamp = $buffered_timestamp;
					else:
						$current_timestamp = $local_time;
					endif;

					$this_timeslot_timestamp = strtotime($year.'-'.$month.'-'.$day.' '.$timeslot_parts[0]);

					if ($current_timestamp < $this_timeslot_timestamp){
						$available = true;
					} else {
						$available = false;
					}
					
					$hide_unavailable_timeslots = get_option('booked_hide_unavailable_timeslots',false);

					if ($spots_available && $available || !$hide_unavailable_timeslots):

						$temp_count++;

						if ($timeslot_parts[0] == '0000' && $timeslot_parts[1] == '2400'):
							$timeslotText = __('All day','booked');
						else :
							$timeslotText = date_i18n($time_format,strtotime($timeslot_parts[0])) . (!get_option('booked_hide_end_times') ? '-'.date_i18n($time_format,strtotime($timeslot_parts[1])) : '');
						endif;
						
						if ($hide_unavailable_timeslots && !$available):
							$html = '';
						else:
							// $data[$temp_count] =array();
							// $data[$temp_count]['timeslot'] = $timeslotText;
							// $data[$temp_count]['spots_available'] = $spots_available;
							$data[]= array(
								'timeslots' => $timeslotText ,
								'spots_available' => $spots_available,
								);
 						endif;
					endif;
				endforeach;
			} else {

			}

			$result['timeslots'] = $data;
		
			// Special Access Data 
			$term =  get_the_terms($venue_id , 'product_cat' );
			$facilityArr= array();
			foreach ($term as $t) {
			   $parentId = $t->parent;
			   
			   if($parentId == 92){
			     // $facilityArr[$i]['term_id'] = $t->term_id;
			     // $facilityArr[$i]['name'] = $t->name;
			   	 // $i++;
			   	 $facilityArr[] = array(
			   	 	'term_id' => $t->term_id,
			   	 	'name' => $t->name,
			   	 	);
 			   }				   
			}	
			$result['special_access_Data'] = $facilityArr;
			if($type){
				$data1['settings']['success'] = 1;
				$data1['settings']['message'] = 'Gettting all filter data';
				$data1['data'] = $result;
				echo json_encode($data1);exit();
			}else{
				return $result['timeslots'];
			}
	}

	function getMinMaxPrice($type = false){
		$result = array();
		$args = array(
	        'posts_per_page' => -1,
	        'post_type' => 'product',
	        'orderby' => 'meta_value_num',
	        'order' => 'DESC',
	        'meta_query' => array(
	            array(
	                'key' => '_price',
	            )
	        )       
	    );

	    $loop = new WP_Query($args);

	    $max = get_post_meta($loop->posts[0]->ID, '_price', true);
	    $min = get_post_meta($loop->posts[count($loop->posts)]->ID, '_price', true);
	    if($min == '' ){
	    	$min = 0;
	    }		    
	    $result['min'] = $min;
		$result['max'] = $max;
		if($type){
			return $result;
		}else{
			echo json_encode($result);exit();
		}
	}

	// get postal code 
	function hbapi_getPostCode($type = true){
		$result = array();
		$args = array(
	        'posts_per_page' => -1,
	        'post_type' => 'product',
	        'orderby' => 'meta_value_num',
	        'order' => 'DESC',
	        'meta_query' => array(
	            array(
	                'key' => 'post_code',
	            )
	        )       
	    );

	    $loop = new WP_Query($args);
	    $postcodeArr = array();
	    while ( $loop->have_posts() ) : $loop->the_post(); 
	    	$postcode = get_post_meta($loop->post->ID, 'post_code', true);
	    	array_push($postcodeArr, $postcode);	    			 
		endwhile;
		if($type){
			return $postcodeArr;
		}else{
			echo json_encode($postcodeArr);exit();
		}
		
	}

	// get min max people 
	function hbapi_getMinMaxPeople($type = true){
		$result = array();
		$args = array(
	        'posts_per_page' => -1,
	        'post_type' => 'product',
	        'orderby' => 'meta_value_num',
	        'order' => 'DESC',
	        'meta_query' => array(
	            array(
	                'key' => 'max_capacity',
	            )
	        )       
	    );

	    $loop = new WP_Query($args);

	    $max = get_post_meta($loop->posts[0]->ID, 'max_capacity', true);
	    $min = get_post_meta($loop->posts[count($loop->posts)]->ID, 'max_capacity', true);
	    if($min == '' ){
	    	$min = 1;
	    }		    
	    $result['min'] = $min;
		$result['max'] = $max;
		// pr($result,1);
		if($type){
			return $result;
		}else{
			echo json_encode($result);exit();
		}
	}

	// get venue filter data
	function hbapi_Venue_filter_data(){
		$postcode = hbapi_getPostCode();
		$people = hbapi_getMinMaxPeople();
		$price = getMinMaxPrice(true);
		$result['postcode'] =  $postcode;
		$result['people_min'] = $people['min'];
		$result['people_max'] = $people['max'];
		$result['price min'] = $price['min'];
		$result['price max'] = $price['max'];
		// pr($result,1);
		$data['settings']['success'] = 1;
		$data['settings']['message'] = 'Gettting all filter data';
		$data['data'] = $result;
		echo json_encode($data);exit();

	}

	function hbapi_view_profile_data(){
		$result = array();
		// $user = get_user_by('email', $email);
		$user_id = $_REQUEST['user_id'];
		$userdata = get_user_meta($user_id);
		if(count($userdata) > 0){
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'Get All data of User';
			$result['data']['user_id'] = $user_id;
			$result['data']['first_name'] = $userdata['first_name'][0];			
			$result['data']['last_name'] = $userdata['last_name'][0];
			$result['data']['contact_number'] = $userdata['booked_phone'][0];
			$result['data']['user_email'] = $userdata['user_email'][0];
			$result['data']['profilepicture'] = $userdata['profilepicture'][0];
			$result['data']['wheel_chair_access'] = $userdata['wheel_chair_access'][0];
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'No Such User Available';
		}
		
		echo json_encode($result);exit();	     
	}

	function generateOTP($mobile = ''){
		$username = urlencode("Colindalecommunity");
		$password = urlencode("C0mmunity2016!");
		$api_id = urlencode("3589883");
		$to = urlencode($mobile);
		$otp = mt_rand(100000, 999999);
		$otpMessage = 'Your One time Password is '.$otp;
		$message = urlencode($otpMessage);
			
		$status = file_get_contents("https://api.clickatell.com/http/sendmsg"
			 . "?user=$username&password=$password&api_id=$api_id&to=$to&text=$message");
		// At present OTP is not sending msg , so every time we are passing message success
		// after developement we will change it 
		// $result['status'] =$status;
		$result['status'] = 'Success';
		$result['otp'] = $otp;
		return $result;
	}

	function hbapi_confirmOTP(){
		
		$user_id = $_REQUEST['user_id'];
		// $user_id = '';
		$user_otp = get_user_meta( $user_id,'user_otp' ); 
		
		if($user_otp[0] == $_REQUEST['otp']){
			update_user_meta($user_id, 'user_status','active');
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'Account Activated';
			$result['data'] = array();
			$result['data'][0]['user_id'] = $user_id;
			$result['data'][0]['access_token'] = md5($user_id);		
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'Incorrect OTP';
		}
		echo json_encode($result);exit();
	}

	function hbapi_resendOTP(){
		// $_REQUEST['user_id'] = 82;
		$user_id = $_REQUEST['user_id'];
		$mobile = get_user_meta( $_REQUEST['user_id'],'booked_phone',true); 

		$otpresult = generateOTP($mobile);
		// $otpresult = generateOTP1($mobile);
		// pr($otpresult);
		update_user_meta($user_id, 'user_otp',$otpresult['otp']);
		if (strpos($otpresult['status'], 'ERR') === false) {
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'OTP send Successfully';
			$result['settings']['otp'] = $otpresult['otp'];
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'Error in sending OTP , please try later';
		}
		echo json_encode($result);exit();	 
	}

	function hbapi_editMobileNo(){
		update_user_meta($_REQUEST['user_id'], 'booked_phone',$_REQUEST['mobile_no']);
		update_user_meta($_REQUEST['user_id'], 'billing_phone',$_REQUEST['mobile_no']);
		
		$otpresult = generateOTP($_REQUEST['mobile_no']);
		// $otpresult = generateOTP1($_REQUEST['mobile_no']);

		update_user_meta($user_id, 'user_status',$otpresult['otp']);
		if (strpos($otpresult['status'], 'ERR') === false) {
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'OTP send Successfully';
			$result['settings']['otp'] = $otpresult['otp'];
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'Error in sending OTP , please try later';
		}
		echo json_encode($result);exit();	
	}

	function hbapi_get_event_filter_data(){
		
		$venueList = get_terms('venue', array(
		 	'post_type' => array('post', 'events'),
		 	'fields' => 'all'
		));
		
		$veneuArr = array();
		foreach ($venueList as $key => $value) {
			$temparr = array();
			$temparr['term_id'] = $value->term_id;
			$temparr['name'] = $value->name;
			// $veneuArr[$key]['term_id'] = $value->term_id;
			// $veneuArr[$key]['name'] = $value->name;
			array_push($veneuArr, $temparr);
		}

		$orgList = get_terms('organizer', array(
		 	'post_type' => array('post', 'events'),
		 	'fields' => 'all'
		));
		$orgArr = array();
		foreach ($orgList as $key => $value) {
			$tmparr = array();
			$tmparr['term_id'] = $value->term_id;
			$tmparr['name'] = $value->name;
			array_push($orgArr, $tmparr);
			// $orgArr[$key]['term_id'] = $value->term_id;
			// $orgArr[$key]['name'] = $value->name;
		}
		$result = array();
		$result['venue_event'] = $veneuArr;
		$result['special_access'] = $orgArr;
		// pr($result,1);
		$data['settings']['success'] = 1;
		$data['settings']['message'] = 'getting all data';
		$data['data'] = $result;
		echo json_encode($data);exit();
	}

	function hbapi_test(){
		// $termid = wp_get_post_terms(3025,'venue');
		$calendars = wp_get_post_terms(3025, 'booked_custom_calendars');
		pr($calendars,1);
		$productroom = get_terms('pa_room-type', array(
		 	'post_type' => array('post', 'product'),
		 	'fields' => 'all'
		));
		
		$productfacility = get_terms('pa_facilities', array(
		 	'post_type' => array('post', 'product'),
		 	'fields' => 'all'
		));
		pr($productroom);
		pr($productfacility,1);	
	}

	function generateOTP1($mobile = ''){
		// $mobile = '9974711747';
		$username = urlencode("JJSatisnet");
		$password = urlencode("shree#123");
		$to = urlencode($mobile);
		$otp = mt_rand(100000, 999999);
		$otpMessage = 'Your One time Password is '.$otp;
		$message = urlencode($otpMessage);
		$url ="http://cloud.smsindiahub.in/vendorsms/pushsms.aspx?user=$username&password=$password&msisdn=$to&sid=WEBSMS&msg=$message&fl=0";
		// $status = file_get_contents("http://cloud.smsindiahub.in/vendorsms/pushsms.aspx?user=$username&password=$password&msisdn=$to&sid=WEBSMS&msg=$message&fl=0");	

		 // create curl resource 
        $ch = curl_init(); 

        // set url 
        curl_setopt($ch, CURLOPT_URL, $url); 

        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

        // $output contains the output string 
        $output = curl_exec($ch); 
        // pr($output,1);exit();
        // close curl resource to free up system resources 
        curl_close($ch);    
        $output = json_decode($output);
        // pr($output,1);
        $result = array();
        if(isset($output->ErrorMessage)  && ($output->ErrorMessage == 'success') ){
        	$result['status'] ='success';	
        }else{
        	$result['status'] = 'ERR:';	
        }        
		$result['otp'] = $otp;
		return $result;
	}

	function hbapi_globalsearch(){
		$page = $_REQUEST['page_no'];
		$data = array();
		// Get search result of Events
		$args = array();
		$args['s'] = $_REQUEST['searchword'];
		//$args['post_type'] = 'events';
		$args['posts_per_page'] = 10;
		$args['post_type'] = 'product';
		$args['paged']  = ($page > 1 ? $page: 1);

		

		$posts = array();
		$query = new WP_Query( $args );
		
		$eventdata = array();
		while ( $query->have_posts() ) : $query->the_post(); 
			
			//$content = trim(strip_tags(substr(get_the_content(),0,150),'<p><b><h1><h2><i><u>'));
			$event_id = get_the_id();
			$content = get_post_meta( $event_id, 'description', true );
			//$short_description = substr($content,0,150);
			$short_description = (strlen($content) > 150) ? substr($content,0,150).'...' : $content;
			if($content == ""){
				$short_description = '';
			}
			$event_type = get_post_meta( $event_id, 'WooCommerceEventsEvent', true );
			if($event_type == "Event"){
				$price = get_post_meta($event_id ,'_price',true);
				$data[] = array( 
				/*'event_id' => $event_id,
				'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( $event_id)),
				'event_title' => get_the_title($event_id),
				'event_published_date' =>  DataFormat($event_id),
				'event_short_description' => $short_description ,
				'event_status' => get_post_status( $venue_id ),
				'event_start_date' => DataFormat(get_post_meta( $event_id, 'fc_start', true )),
				'event_start_time' => get_post_meta( $event_id, 'WooCommerceEventsHour', true ).":".get_post_meta( $event_id, 'WooCommerceEventsMinutes', true ),
				'type' =>'event',
*/
				'event_id' => $event_id,
				'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( $event_id)),
				'event_title' => get_the_title($event_id),
				'event_published_date' => get_the_date( 'm/d/Y', $event_id ),
				'event_short_description' => $short_description,
				'event_status' =>get_post_status( $event_id ),
				'event_price' => $price,
				'event_start_date' => DataFormat(get_post_meta( $event_id, 'WooCommerceEventsDate', true )),
				'event_start_time' => get_post_meta( $event_id, 'WooCommerceEventsHour', true ).":".get_post_meta( $event_id, 'WooCommerceEventsMinutes', true ),
				'event_end_time' => get_post_meta( $event_id, 'WooCommerceEventsHourEnd', true ).":".get_post_meta( $event_id, 'WooCommerceEventsMinutesEnd', true ),
				'event_location' => get_post_meta( $event_id, 'WooCommerceEventsLocation', true ),
				'event_latitute' =>get_post_meta($event_id ,'latitude',true),
				'event_longitude' => get_post_meta($event_id ,'longitude',true),
				'type' =>'event',
				);
			}
			if($event_type == "NotEvent"){
				$venue_id = get_the_id();
				$price = get_post_meta($venue_id ,'_price',true);
				$address = get_post_meta($venue_id ,'address',true);
				$desc = substr(get_post_meta($venue_id ,'description',true),0,150);
				$date = hbapi_getNextAvailableDate($venue_id);
				$short_description_venue = (strlen($desc) > 150) ? substr($desc,0,150).'...' : $desc;

				if($desc == ""){
					$desc = '';
				}
				$capacity = get_post_meta($venue_id ,'max_capacity',true);
				$data[] = array( 
				
				/*'venue_id' => $venue_id,
				'venue_default_image' =>  wp_get_attachment_url( get_post_thumbnail_id( $venue_id)),
				'venue_title' => get_the_title($venue_id),
				'venue_address' => $address,
				'venue_short_description' =>strip_tags($desc,'<a>'),
				'venue_next_availabilty_date' =>DataFormat($date),
				'venue_max_capacity' =>  $capacity,
				'venue_added_datetime' => DataFormat($venue_id),
				'venue_status' => get_post_status( $venue_id ),
				'type' =>'venue'*/



				'venue_id' => $venue_id,
				'venue_default_image' =>  wp_get_attachment_url( get_post_thumbnail_id( $venue_id )),
				'venue_title' => get_the_title($venue_id),
				'venue_address' => $address,
				'venue_short_description' =>$short_description_venue,
				'venue_next_availabilty_date' =>DataFormat($date),
				'venue_max_capacity' =>  $capacity,
				'venue_added_datetime' => DataFormat($venue_id),
				'venue_status' =>  get_post_status( $venue_id ),
				'venue_latitute' =>get_post_meta($venue_id ,'latitude',true),
				'venue_longitude' => get_post_meta($venue_id ,'longitude',true),
				'type' =>'venue'
				);

			}
		
		endwhile;
		

		// Get Venue Data 
		
		 $args = array();
		 $args['s'] = $_REQUEST['searchword'];
			$args = array(
				'posts_per_page' => 10,
				);
		$args['page']  = ($page > 1 ? $page: 1);
		

		$args['meta_query'] = array();
		$args['meta_query']['relation'] = 'AND';
		$visibility[] = array(
					'key' => '_visibility',
					'value' => array( 'catalog', 'visible' ),
					'compare' => 'IN'
					);
		$visibility[] = array(
					'key'     => 'WooCommerceEventsEvent',
					'value'   => 'NotEvent',
					'compare' => '=',
				);
					
		array_push($args['meta_query'], $visibility);
		// pr($args,1);
		$loop = new WP_Query( $args );
		
		$venuedata = array();
		
		while ( $loop->have_posts() ) : $loop->the_post();
			// $loop->post->post_status;
			// pr($loop->posts);
			$venue_id = get_the_id();
			$price = get_post_meta($venue_id ,'_price',true);
			$address = get_post_meta($venue_id ,'address',true);
			$desc = substr(get_post_meta($venue_id ,'description',true),0,150);
			$capacity = get_post_meta($venue_id ,'max_capacity',true);
			// $add = explode('[icon type="icon-house"]',$loop->post->post_excerpt);
			// $add = explode('[icon type="icon-twitter"]',$add[1]);
			// $address = strip_tags($add[0]);
			// $desc = explode('[vc_column_text]',$loop->post->post_content);
			// $desc = explode('[/vc_column_text]',$desc[2]);
			$date = hbapi_getNextAvailableDate($venue_id);
			$venue_type = get_post_meta( $venue_id, 'WooCommerceEventsEvent', true );
			
			$venuedata[] = array( 
				'venue_id' => $venue_id,
				'venue_default_image' =>  wp_get_attachment_url( get_post_thumbnail_id( $venue_id)),
				'venue_title' => get_the_title($venue_id),
				'venue_address' => $address,
				'venue_short_description' =>strip_tags($desc,'<a>'),
				'venue_next_availabilty_date' =>DataFormat($date),
				'venue_max_capacity' =>  $capacity,
				'venue_added_datetime' => DataFormat($venue_id),
				'venue_status' =>  $loop->post->post_status,
				'type' =>'venue'
				);
		
		endwhile;
		// // exit();
		// pr($venuedata);
		// pr($eventdata,1);	
		// $data = array_merge($eventdata,$venuedata);
		// shuffle($data);		
		$eventpage = $query->max_num_pages;
		$venuepage = $loop->max_num_pages;
		$tpage = ($eventpage > $venuepage ? $eventpage: $venuepage);
		if(count($data) > 0){	
			$result['settings']['success'] = 1;
			$result['settings']['message'] ='getting all related data';
			$result['settings']['total_page_count'] = $eventpage;
			$result['data'] = $data;
 		}else{
 			$result['settings']['success'] = 0;
			$result['settings']['message'] ='No such data available';
		}
		// pr($data,1);
		echo json_encode($result);exit();


	}

	//get the list of venue 
	function hbapi_getVenueList(){
		
		$page = $_REQUEST['page_no'];
		$args = array(
			'post_type' => 'product',
			'post_status'  => 'publish',
			'posts_per_page' => 10,
			);
		$args['meta_query'] = array();
		$args['meta_query']['relation'] = 'AND';
		$visibility[] = array(
					'key' => '_visibility',
					'value' => array( 'catalog', 'visible' ),
					'compare' => 'IN'
					);
		$visibility[] = array(
					'key'     => 'WooCommerceEventsEvent',
					'value'   => 'NotEvent',
					'compare' => '=',
				);
					
		array_push($args['meta_query'], $visibility);
		
		// Filter By Keyword 
		if(isset($_REQUEST['searchword']) && !empty($_REQUEST['searchword'])) {
			$args['s'] = $_REQUEST['searchword'];
		} 

		// Price Filter Query 
		if($_REQUEST['min_price']){
			$min_price =array(
						'key' => '_price',
			            'value' => array($_REQUEST['min_price'],$_REQUEST['max_price']),
			            'compare' => 'BETWEEN',
			            'type' => 'NUMERIC'
					);
			array_push($args['meta_query'], $min_price);	
		}
		// People Filter Query 
		if($_REQUEST['min_people']){
			$min_people =array(
						'key' => 'max_capacity',
			            'value' => array($_REQUEST['min_people'],$_REQUEST['max_people']),
			            'compare' => 'BETWEEN',
			            'type' => 'NUMERIC'
					);
			array_push($args['meta_query'], $min_people);	
		}

		// Post code Filter Query 
		if($_REQUEST['postal_code']){
			$postal_code =array(
						'key' => 'post_code',
			            'value' => $_REQUEST['postal_code'],
			            'compare' => '='
					);
			array_push($args['meta_query'], $postal_code);	
		}

		// Near By Filter Query 
		if($_REQUEST['lat'] && $_REQUEST['lng']){
			$lat = $_REQUEST['lat'];
			$lng = $_REQUEST['lng'];
			hbapi_find_nearby($lat , $lng);
		}



		$args['paged']  = ($page > 1 ? $page: 1);
		//pr($args,1);
		$loop = new WP_Query( $args );
		
		$data = array();
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				// $loop->post->post_status;
				$pid = get_the_id();
				
				$price = get_post_meta($pid,'_price',true);
				$address = get_post_meta($pid ,'address',true);
				$desc = substr(get_post_meta($pid ,'description',true),0,150);
				$capacity = get_post_meta($pid ,'max_capacity',true);
				$date = hbapi_getNextAvailableDate($pid);
				$venue_type = get_post_meta( $pid, 'WooCommerceEventsEvent', true );
				$postss_type = get_post_types( $pid);
				
				$data[] = array( 
					'venue_id' => $pid,
					'venue_default_image' =>  wp_get_attachment_url( get_post_thumbnail_id( $pid )),
					'venue_title' => get_the_title($pid),
					'venue_address' => $address,
					'venue_short_description' =>strip_tags($desc,'<a>'),
					'venue_next_availabilty_date' =>DataFormat($date),
					'venue_max_capacity' =>  $capacity,
					'venue_added_datetime' => DataFormat($pid),
					'venue_status' =>  $loop->post->post_status,
					'venue_latitute' =>get_post_meta($pid ,'latitude',true),
					'venue_longitude' => get_post_meta($pid ,'longitude',true)
					);
			

			endwhile;
		} else {
		
		}
		// print_r($pid);
		// exit();
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Get Venue List';			
			$posts['settings']['total_item_count'] = count($data);
			$posts['data'] = $data;
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No venue Available';			
		}	
		// pr($posts,1);
		echo json_encode($posts);exit();		
	}

	//get detail of perticular venue 
	function hbapi_getVenueDetail(){
		$venue_id = $_REQUEST['venue_id'];
		$venue_tite = get_the_title($venue_id );
	// Get Event for Venue
		$event_data = array();
		$args = array();
		
		$args['post_type'] = 'product';
		$args['showposts'] = 3;
		$args['meta_query'] = array();
		$args['meta_query']['relation'] = 'AND';
		$visibility[] = array(
					'key' => '_visibility',
					'value' => array( 'catalog', 'visible' ),
					'compare' => 'IN'
					);
		$visibility[] = array(
					'key'     => 'WooCommerceEventsEvent',
					'value'   => 'Event',
					'compare' => '=',
				);
		$visibility[] = array(
					'key'     => 'WooCommerceEventsLocation',
					'value'   => $venue_tite,
					'compare' => '=',
				);
					
		array_push($args['meta_query'], $visibility);

		$query = new WP_Query( $args );
		if ( $query-> have_posts() ) {
		while ( $query->have_posts() ) : $query->the_post(); 

			$event_data[] = array( 
				'event_id' => get_the_id(),
				'event_default_image' => wp_get_attachment_url( get_post_thumbnail_id( get_the_id())),
				'event_title' => get_the_title(get_the_id()),
				'event_published_date' =>  DataFormat(get_the_date()),
				'event_short_description' => strip_tags($short_description,'<a>'),
				'event_status' => $query->post->post_status,
				'event_price' =>get_post_meta(get_the_id() ,'_price',true),
				'event_start_date' => DataFormat(get_post_meta( get_the_id(), 'WooCommerceEventsDate', true )),
				'event_start_time' => get_post_meta( get_the_id(), 'WooCommerceEventsHour', true ).":".get_post_meta( get_the_id(), 'WooCommerceEventsMinutes', true ),
				'event_end_time' => get_post_meta( get_the_id(), 'WooCommerceEventsHourEnd', true ).":".get_post_meta( get_the_id(), 'WooCommerceEventsMinutesEnd', true ),
				'event_location' => get_post_meta( get_the_id(), 'WooCommerceEventsLocation', true ),
				'event_latitute' =>get_post_meta(get_the_id() ,'latitude',true),
				'event_longitude' => get_post_meta(get_the_id() ,'longitude',true)
				);
			endwhile;
		}
		else{
			
		}
		
		wp_reset_query();


		$args = array(
			'post_type' => 'product',
			'p' => $_REQUEST['venue_id'],
			);
		$args['meta_query'] = array();
		$args['meta_query']['relation'] = 'AND';
		$visibility =array(
					'key' => '_visibility',
					'value' => array( 'catalog', 'visible' ),
					'compare' => 'IN'
					);
		array_push($args['meta_query'], $visibility);
		
		$loop = new WP_Query( $args );
		
		$data = array();
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				// $loop->post->post_status;
				$postMeta = get_post_meta($venue_id ,'_product_image_gallery',true);
				if(!empty($postMeta)){
					$imageArr = explode(',', $postMeta);
				}
				$images = '';
				$images = wp_get_attachment_url( get_post_thumbnail_id( $venue_id));
				$images .= ',';
				// $i = 1;
				if(isset($imageArr) && !empty($imageArr)){
					foreach ($imageArr as $key => $value) {
						$images .= get_post_field('guid', $value).',';					
					}
				}
				if(substr($images, -1)  == ','){
					$images = substr($images,0 ,-1);
				}
				$i =0;
				$term =  get_the_terms( $venue_id, 'product_cat' );
				$facilityArr= array();
				foreach ($term as $t) {
				   $parentId = $t->parent;
				   // 92 is the term id of Facility Term
				   if($parentId == 92){
				     $facilityArr[$i]['term_id'] = $t->term_id;
				     $facilityArr[$i]['name'] = $t->name;
				   	 $i++;
				   }				   
				}	
				// pr($facilityArr,1);
				$price = get_post_meta($venue_id ,'_price',true);
				$address = get_post_meta($venue_id ,'address',true);
				$desc = get_post_meta($venue_id ,'description',true);
				$capacity = get_post_meta($venue_id ,'max_capacity',true);
				$contact = get_post_meta($venue_id ,'contactno',true);
				$latitude = get_post_meta($venue_id ,'latitude',true);
				$longitude = get_post_meta(get_the_id() ,'longitude',true);
			
				$date = hbapi_getNextAvailableDate($venue_id);
				// $data= array( 

				$data['venue_id'] = $venue_id;
				$data['venue_default_image'] = $images;
				$data['venue_title'] = get_the_title($venue_id);
				$data['venue_address'] = $address;
				$data['venue_description'] = strip_tags($desc,'<a>');
				$data['venue_next_availabilty_date'] =DataFormat($date);
				$data['venue_max_capacity'] = $capacity;
				$data['venue_added_datetime'] = DataFormat(get_the_date());
				$data['venue_status'] = $loop->post->post_status;
				$data['venue_token_amount'] = $price;
				$data['venue_latitute'] = $latitude;
				$data['venue_longitude'] = $longitude;
				$data['venue_contactno'] = $contact;
				$data['venue_facility'] = $facilityArr;
				$data['upcomming_event'] = $event_data;
					// );
			endwhile;
		} else {
		
		}
		if(count($data) > 0){
			$posts['settings']['success'] = 1;
			$posts['settings']['message'] = 'Get Venue Detail';			
			$posts['data'] = $data;
		}else{
			$posts['settings']['success'] = 0;
			$posts['settings']['message'] = 'No Such venue Available';			
		}	
		// pr($posts,1);
		echo json_encode($posts);exit();		
	}
	
	
	
	

	// get the booked venue of perticular user 
	//Not complated 
	function hbapi_booked_appointments(){
		$my_id = $_REQUEST['user_id'];
		$calendars = get_terms('booked_custom_calendars');
		$calendar_ids = array();
		
		if (!empty($calendars)):
			foreach($calendars as $calendar):
				$calendar_id = $calendar->term_id;
				$term_meta = get_option( "taxonomy_$calendar_id" );
				if ($current_user->user_email == $term_meta['notifications_user_id']):
					$calendar_ids[] = $calendar_id;
				endif;
			endforeach;
		endif;
		// pr($calendar_ids,1);
		$historic = isset($atts['historic']) && $atts['historic'] ? true : false;
		$pending = isset($atts['pending']) && $atts['pending'] ? true : false;

		$time_format = get_option('time_format');
		$date_format = get_option('date_format');
		$appointments_array = booked_agent_appointments($my_id,false,$time_format,$date_format,$calendar_ids,$pending,$historic);
		$total_appts = count($appointments_array);
		$appointment_default_status = get_option('booked_new_appointment_default','draft');		
		pr($appointments_array,1);
	}

	// get the next available data for perticular venue 
	function hbapi_getNextAvailableDate($venueid = ''){
		// $venueid = 3025;
		$i = 1;
		do
		{
		   $date = date('Y-m-d', strtotime('+' . $i++ . ' day'));
		   $timeArr = hbapi_getTimeSlot($venueid,$date,false);
		   // var_dump($timeArr);exit();
		   if(count($timeArr) > 0){
		   	return $date;
		   	// echo $date;exit();
		   }else{
		   	$i++;
		   }
		}while ($i== 1);	
	}

	// add venue to basket like wish list
	function hbapi_register_interest(){
		global $wpdb;
		$venue_id = $_REQUEST['venue_id'];
		$wpdb->venue_basket = $wpdb->prefix . 'venue_basket';
		$user_contain = $wpdb->get_var( "SELECT basket_id FROM $wpdb->venue_basket where user_id = '".$_REQUEST['user_id']."' and venue_id = '".$_REQUEST['venue_id']."' " );
		$user = get_userdata( $_REQUEST['user_id'] );
			
		if(empty($user_contain)){
			$wpdb->insert('wp_venue_basket', array(
			    'user_id' => $_REQUEST['user_id'],
			    'venue_id' => $_REQUEST['venue_id'],
			    'dDate' => date('Y-m-d')
			));		
			// $to = get_option( 'admin_email' );
			$to = get_post_meta($_REQUEST['venue_id'],'venue_owner_email_id',true);
			
			$headers[] = 'MIME-Version: 1.0' . "\r\n";
			$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers[] = "X-Mailer: PHP \r\n";
			$headers[] = 'From: '.$user->first_name.' < '.$user->user_email.'>' . "\r\n";
			$subject = get_option('whishlist_mail_subject');
       		$plain_message = get_option('whishlist_mail_template');
       		$find = array ("{USER}" , "{VENUE}");
       		$replace = array ( $user->first_name , get_the_title($venue_id) );
       		$message = str_replace($find,$replace,$plain_message);

			$mail = wp_mail( $to, $subject, nl2br( $message), $headers );
						
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'Venue Added to basket';			
			$result['settings']['basket_id'] =$wpdb->insert_id;
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'Venue Already Added to basket';			
		}
		// pr($result,1);	
		echo json_encode($result);exit();
	}

	// delete venue from the basket or Wishlist
	function hbapi_venue_basket_delete(){
		global $wpdb;
		$wpdb->venue_basket = $wpdb->prefix . 'venue_basket';
		$user_contain = $wpdb->get_var( "SELECT basket_id FROM $wpdb->venue_basket where user_id = '".$_REQUEST['user_id']."' and venue_id = '".$_REQUEST['venue_id']."' " );
		if(!empty($user_contain)){
			$wpdb->delete( 'wp_venue_basket', array( 'user_id' => $_REQUEST['user_id'] ,'venue_id' => $_REQUEST['venue_id'] ) );	
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'Venue deleted from basket';
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'No Such Venue available in your basket';	
		}
		// pr($result,1);
		echo json_encode($result);exit();
	}	

	// listing of venue available in basket
	function hbapi_venue_basket(){
		global $wpdb;
		$basketData = array();
		$wpdb->venue_basket = $wpdb->prefix . 'venue_basket';
		$basket_contain = $wpdb->get_results( "SELECT * FROM $wpdb->venue_basket where user_id = '".$_REQUEST['user_id']."' ");
		// pr($basket_contain,1);
		$counter = 1;
		foreach ($basket_contain as $key => $value) {
			// pr($value);
			$args = array(
				'post_type' => 'product',
				'p' => $value->venue_id,
				);
			$args['meta_query'] = array();
			$args['meta_query']['relation'] = 'AND';
			$visibility =array(
						'key' => '_visibility',
						'value' => array( 'catalog', 'visible' ),
						'compare' => 'IN'
						);
			array_push($args['meta_query'], $visibility);
			
			$loop = new WP_Query( $args );
			
			$data = array();
			if ( $loop->have_posts() ) {
				while ( $loop->have_posts() ) : $loop->the_post();
					
					$venue_id = get_the_id();
					$price = get_post_meta($venue_id ,'_price',true);
					$add = explode('[icon type="icon-house"]',$loop->post->post_excerpt);
					$add = explode('[icon type="icon-twitter"]',$add[1]);
					$address = strip_tags($add[0]);
					// $desc = explode('[vc_column_text]',$loop->post->post_content);
					// $desc = explode('[/vc_column_text]',$desc[2]);
					$desc = substr(get_post_meta($venue_id ,'description',true),0,150);
					$date = hbapi_getNextAvailableDate($venue_id);
					$data= array( 
						'basket_id' => $value->basket_id,
						'venue_id' => $venue_id,
						'venue_default_image' =>  wp_get_attachment_url( get_post_thumbnail_id( $venue_id )),
						'venue_title' => get_the_title($venue_id),
						'venue_address' => get_post_meta($venue_id ,'address',true),
						'venue_short_description' =>strip_tags($desc,'<a>'),
						'venue_next_availabilty_date' =>DataFormat($date),
						'venue_max_capacity' =>   get_post_meta($venue_id ,'max_capacity',true),
						'venue_added_datetime' => DataFormat($venue_id),
						'venue_status' =>  $loop->post->post_status
						);
					$basketData[] = $data;									
				endwhile;
			}
			
			$counter++;
		}
		// exit();
		if(count($basketData) > 0){
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'Get All Basket Venue';			
			$result['data'] = $basketData;
		}else{
			$result['settings']['success'] = 0;
			$result['settings']['message'] = 'No venue added in basket';			
		}
		echo json_encode($result);exit();
	}

	function hbapi_testbook(){
		// global $wpdb;
		// $user_id = $_REQUEST['user_id'];
		// $ids = $wpdb->get_col(
		// 	$wpdb->prepare( "
		// 		SELECT im.meta_value FROM {$wpdb->posts} AS p
		// 		INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
		// 		INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
		// 		INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
		// 		WHERE p.post_status IN ( 'wc-completed', 'wc-processing' )
		// 		AND pm.meta_key = '_customer_user'
		// 		AND im.meta_key IN ( '_product_id', '_variation_id' )
		// 		AND pm.meta_value = %d
		// 		", $user_id
		// 	)
		// );

		// pr($ids,1);
		$args = array(
			'post_type' => 'booked_appointments',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'author' => $_REQUEST['user_id'],
			'meta_key' => '_appointment_timestamp',
			'orderby' => 'meta_value_num',
			'order' => $order
		);

		$appointments_array = array();
		$bookedAppointments = new WP_Query($args);
		pr($bookedAppointments,1);

		$my_id = $_REQUEST['user_id'];
		
		$historic = isset($atts['historic']) && $atts['historic'] ? true : false;
		$historic = true;
		$time_format = get_option('time_format');
		$date_format = get_option('date_format');
		$appointments_array = booked_user_appointments($my_id,false,$time_format,$date_format,$historic);
		pr($appointments_array,1);

		$total_appts = count($appointments_array);
			$appointment_default_status = get_option('booked_new_appointment_default','draft');

			if (!isset($atts['remove_wrapper'])): echo '<div id="booked-profile-page" class="booked-shortcode">'; endif;

				echo '<div class="booked-profile-appt-list">';

					if ($historic):
						echo '<h4><span class="count">' . number_format($total_appts) . '</span> ' . _n('Past Appointment','Past Appointments',$total_appts,'booked') . '</h4>';
					else:
						echo '<h4><span class="count">' . number_format($total_appts) . '</span> ' . _n('Upcoming Appointment','Upcoming Appointments',$total_appts,'booked') . '</h4>';
					endif;
				
					foreach($appointments_array as $appt):

						$today = date_i18n($date_format);
						$date_display = date_i18n($date_format,$appt['timestamp']);
						if ($date_display == $today){
							$date_display = __('Today','booked');
							$day_name = '';
						} else {
							$day_name = date_i18n('l',$appt['timestamp']).', ';
						}

						$date_to_convert = date('F j, Y',$appt['timestamp']);

						$cf_meta_value = get_post_meta($appt['post_id'], '_cf_meta_value',true);

						$timeslots = explode('-',$appt['timeslot']);
						$time_start = date($time_format,strtotime($timeslots[0]));
						$time_end = date($time_format,strtotime($timeslots[1]));

						$appt_date_time = strtotime($date_to_convert.' '.date('H:i:s',strtotime($timeslots[0])));
						$current_timestamp = current_time('timestamp');

						$google_date_startend = date('Ymd',$appt['timestamp']);
						$google_time_start = date('Hi',strtotime($timeslots[0]));
						$google_time_end = date('Hi',strtotime($timeslots[1]));

						$cancellation_buffer = get_option('booked_cancellation_buffer',0);

						if ($cancellation_buffer):
							if ($cancellation_buffer < 1){
								$time_type = 'minutes';
								$time_count = $cancellation_buffer * 60;
							} else {
								$time_type = 'hours';
								$time_count = $cancellation_buffer;
							}
							$buffered_timestamp = strtotime('+'.$time_count.' '.$time_type,$current_timestamp);
							$date_to_compare = $buffered_timestamp;
						else:
							$date_to_compare = current_time('timestamp');
						endif;

						if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
							$timeslotText = __('All day','booked');
							$google_date_startend_end = date('Ymd',strtotime(date('Y-m-d',$appt['timestamp']) . '+ 1 Day'));
							$google_time_end = '0000';
						else :
							$timeslotText = (!get_option('booked_hide_end_times') ? __('from','booked').' ' : __('at','booked').' ') . $time_start . (!get_option('booked_hide_end_times') ? ' ' . __('to','booked').' '.$time_end : '');
							$google_date_startend_end = $google_date_startend;
						endif;
					
						$status = ($appt['status'] == 'draft' ? __('pending','booked') : __('approved','booked'));
						$status_class = $appt['status'] == 'draft' ? 'pending' : 'approved';
						
						echo '<span class="appt-block bookedClearFix '.(!$historic ? $status_class : 'approved').'" data-appt-id="'.$appt['post_id'].'">';
							if (!$historic):
								if ($appointment_default_status !== 'publish'):
									echo '<span class="status-block">'.($status_class == 'pending' ? '<i class="fa fa-circle-o"></i>' : '<i class="fa fa-check-circle"></i>').'&nbsp;&nbsp;'.$status.'</span>';
								endif;
							endif;
							echo (!empty($appt['calendar_id']) ? '<div class="calendar-name"><strong>'.__('Calendar').':</strong> '.$appt['calendar_id'][0]->name.'</div>' : '');
							echo '<i class="fa fa-calendar"></i>&nbsp;&nbsp;<strong>'.$day_name.$date_display.'</strong><br><i class="fa fa-clock-o"></i>&nbsp;&nbsp;' . $timeslotText;

							do_action('booked_shortcode_appointments_additional_information', $appt['post_id']);

							echo ($cf_meta_value ? '<br><i class="fa fa-info-circle"></i>&nbsp;&nbsp;<a href="#" class="booked-show-cf">'.__('Additional information','booked').'</a><div class="cf-meta-values-hidden">'.$cf_meta_value.'</div>' : '');

							if (!$historic):
								if ($appt_date_time >= $date_to_compare):
									echo '<div class="booked-cal-buttons">';
										if (!get_option('booked_hide_google_link',false)): echo '<a href="//www.google.com/calendar/render?action=TEMPLATE&text='.urlencode(sprintf(__('Appointment with %s','booked'),get_bloginfo('name'))).'&dates='.$google_date_startend.'T'.$google_time_start.'00/'.$google_date_startend_end.'T'.$google_time_end.'00&details=&location=&sf=true&output=xml" target="_blank" rel="nofollow" class="google-cal-button"><i class="fa fa-plus"></i>&nbsp;&nbsp;'.__('Google Calendar','booked').'</a>'; endif;
	
										if ( apply_filters('booked_shortcode_appointments_allow_cancel', true, $appt['post_id']) && !get_option('booked_dont_allow_user_cancellations',false) ) {
											if ( $appt_date_time >= $date_to_compare ) { echo '<a href="#" data-appt-id="'.$appt['post_id'].'" class="cancel">'.__('Cancel','booked').'</a>'; }
										}
	
										do_action('booked_shortcode_appointments_buttons', $appt['post_id']);
									echo '</div>';
								endif;
							endif;
							
						echo '</span>';

					endforeach;

				echo '</div>';

			exit();
	}

	function hbapi_book_venue(){

		$time_format = get_option('time_format');
		$date_format = get_option('date_format');
		$date = $_REQUEST['date'];
		$timestamp = $_REQUEST['timestamp'];
		$timeslot = $_REQUEST['timeslot'];
		$user_id = $_REQUEST['user_id'];
		$venue_id = $_REQUEST['venue_id'];
		$calendar_id = get_post_meta($venue_id ,'calendor_id');



			// Create a new appointment post for a current customer
			$new_post = apply_filters('booked_new_appointment_args', array(
				'post_title' => date_i18n($date_format,$timestamp).' @ '.date_i18n($time_format,$timestamp).' (User: '.$user_id.')',
				'post_content' => '',
				'post_status' => 'publish',
				'post_date' => date('Y',strtotime($date)).'-'.date('m',strtotime($date)).'-01 00:00:00',
				'post_author' => $user_id,
				'post_type' => 'booked_appointments'
			));
			$post_id = wp_insert_post($new_post);

			update_post_meta($post_id, '_appointment_timestamp', $timestamp);
			update_post_meta($post_id, '_appointment_timeslot', $timeslot);
			update_post_meta($post_id, '_appointment_user', $user_id);

			if (apply_filters('booked_update_cf_meta_value', true)) {
				update_post_meta($post_id, '_cf_meta_value', $cf_meta_value);
			}

			if (apply_filters('booked_update_appointment_calendar', true)) {
				if (isset($calendar_id) && $calendar_id): wp_set_object_terms($post_id,$calendar_id,'booked_custom_calendars'); endif;
			}
		
			if (isset($calendar_id[0]) && $calendar_id[0] && !empty($calendar_id)): $calendar_term = get_term_by('id',$calendar_id[0],'booked_custom_calendars'); $calendar_name = $calendar_term->name; else: $calendar_name = false; endif;

			do_action('booked_new_appointment_created', $post_id);

			$timeslots = explode('-',$timeslot);

			$timestamp_start = strtotime('2015-01-01 '.$timeslots[0]);
			$timestamp_end = strtotime('2015-01-01 '.$timeslots[1]);
	
			if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
				$timeslotText = __('All day','booked');
			else :
				$timeslotText = date_i18n($time_format,$timestamp_start).'&ndash;'.date_i18n($time_format,$timestamp_end);
			endif;

			// Send an email to the User?
			$email_content = get_option('booked_approval_email_content');
			$email_subject = get_option('booked_approval_email_subject');
			if ($email_content && $email_subject):
				$user_name = booked_get_name($user_id);
				$user_data = get_userdata( $user_id );
				$email = $user_data->user_email;
				$tokens = array('%name%','%date%','%time%','%customfields%','%calendar%','%email%');
				$replacements = array($user_name,date_i18n($date_format,$timestamp),$timeslotText,$cf_meta_value,$calendar_name,$email);
				$email_content = htmlentities(str_replace($tokens,$replacements,$email_content), ENT_QUOTES | ENT_IGNORE, "UTF-8");
				$email_content = html_entity_decode($email_content, ENT_QUOTES | ENT_IGNORE, "UTF-8");
				$email_subject = str_replace($tokens,$replacements,$email_subject);
				booked_mailer( $email, $email_subject, $email_content );
			endif;

			pr($replacements,1);
			exit;

	}


?>