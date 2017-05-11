<?php 
/*
	Plugin Name: Web Service Interface
*/
?>
<?php 
	// $_REQUEST['action'] = 'resendOTP'; 
	$function = 'hbapi_'.$_REQUEST['action'];
	if(strpos($_SERVER['REQUEST_URI'] , 'hbapi') !== false) {
		if(function_exists($function)){			
			add_action('template_redirect', $function);
		}
	}

	function hbapi_register(){
		global $wpdb;
		$result = array();
		$userbyid = get_user_by('id', $_REQUEST['user_id']);	
		if(isset($userbyid)) {
			$result['settings']['success'] = 0;
			$error = 'User id already exist';
			$result['settings']['message'] = $error;			
			echo json_encode($result);exit();
		}
		if (username_exists( $_REQUEST['email_id'] )) {
			$result['settings']['success'] = 0;
			$error = 'User Name Already Exist , Please Use another name';
			$result['settings']['message'] = $error;			
		}
		elseif(email_exists($_REQUEST['email_id']) == true ){
			$error = 'Email Already Exist , Please Enter Another Username';
			$result['settings']['success'] = 0;
			$result['settings']['message'] = $error;
		}else{
			$hashedPassword = wp_hash_password($_REQUEST['password']);
			$wpdb->insert('wp_users', array(
			    'ID' => $_REQUEST['user_id'],
			    'user_login' => $_REQUEST['email_id'],
			    'user_pass' => $hashedPassword, 
			    'user_nicename' => $_REQUEST['username'], 
			    'user_email' => $_REQUEST['email_id'], 
			    'user_registered' => date('Y-m-d h:i:s'), 
			    'user_status' => 0, 
			    'display_name' => $_REQUEST['username']
			));
			add_user_meta( $_REQUEST['user_id'],'country' , $_REQUEST['country']); 
			if(isset($_REQUEST['profile_pic']) && !empty($_REQUEST['profile_pic'])) {
				add_user_meta( $_REQUEST['user_id'],'profile_pic' , $_REQUEST['profile_pic']); 	
			}			
			$result['settings']['success'] = 1;
			$result['settings']['message'] = 'User Inserted Successfully';
		}
		echo json_encode($result);exit();
	}
	function hbapi_editProfile(){
		global $wpdb;
		$userdata = array();
		// $_REQUEST = array();
		// $_REQUEST['email_id'] = 'lamptandm3@gmail.com'; 
		// $_REQUEST['user_id'] = 4; 
		// $_REQUEST['username'] = 'admin3'; 
		// $_REQUEST['password'] = 'admin123'; 
		// $_REQUEST['country'] = 'NYC';
		// $_REQUEST['profile_pic'] = 'URL COMES HERE';

		$userdata['ID'] = $_REQUEST['user_id'];
		$user_id = $_REQUEST['user_id'];
		$userbyid = get_user_by('id', $_REQUEST['user_id']);	
		if(empty($userbyid)) {
			$result['settings']['success'] = 0;
			$error = 'User id does not exist';
			$result['settings']['message'] = $error;			
			echo json_encode($result);exit();
		}

		$userbyEmail = get_user_by('email', $_REQUEST['email_id']);	
		$userbyName = get_user_by('login', $_REQUEST['email_id']);	
		
		if(!empty($userbyEmail) && ($userbyEmail->ID != $_REQUEST['user_id'])){
			$error = 'Email Already Exist , Please Use another email';
			$posts['settings']['success'] = 0;	
			$posts['settings']['message'] = $error;	
		}elseif( !empty($userbyName) && ($userbyName->ID != $_REQUEST['user_id'])) {
			// Name Already Exist
			$error = 'User Name Already Exist , Please Use another name';
			$posts['settings']['success'] = 0;	
			$posts['settings']['message'] = $error;	
		}else {
			if(!empty($_REQUEST['email_id'])){
				$userdata['user_email'] = $_REQUEST['email_id'];
			}
			if(!empty($_REQUEST['username'])){
				$userdata['display_name'] = $_REQUEST['username'];
			}
			if(!empty($_REQUEST['username'])){
				$userdata['user_nicename'] = $_REQUEST['username'];
			}
			if(!empty($_REQUEST['password'])){
				$userdata['user_pass'] = $hashedPassword = wp_hash_password($_REQUEST['password']);;
			}
			if(!empty($_REQUEST['country'])){
				update_user_meta( $_REQUEST['user_id'],'country' ,$_REQUEST['country']);
			}
			if(isset($_REQUEST['profile_pic']) && !empty($_REQUEST['profile_pic'])) {
				update_user_meta( $_REQUEST['user_id'],'profile_pic' , $_REQUEST['profile_pic']); 	
			}	
			wp_update_user( $userdata );
			if(!empty($_REQUEST['email_id'])){
				$wpdb->update('wp_users', array('user_login' => $_REQUEST['email_id']), array('ID' => $_REQUEST['user_id']));
			}
			$posts['settings']['success'] = 1;	
			$posts['settings']['message'] = 'Profile Updated Succesfully';				
		}
		echo json_encode($posts);exit();
	}
?>