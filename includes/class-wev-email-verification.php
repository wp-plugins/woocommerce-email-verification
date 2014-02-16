<?php
class WEV_Email_Verification{

	public $customer_id;
	public function __construct(){

		register_activation_hook(__FILE__, array( $this, 'install' ));
		register_deactivation_hook(__FILE__, array( $this, 'uninstall' ));

		add_shortcode( 'woocommerce-email-verification', array( $this, 'add_shortcode' ) );
		add_action( 'user_register', array( $this, 'create_temp_user' ) );

		add_action( 'plugins_loaded', array( $this, 'replace_process_registration' ) );
	}

	public function replace_process_registration(){
	global $woocommerce;
	remove_action( 'init', 'woocommerce_process_registration' );
	add_action( 'init', array( $woocommerce, 'process_registration' )  );
	}

	public function wev_process_registration(){
	}

	public function add_shortcode(){
	global $wpdb, $wp_version;

	$did = htmlspecialchars($_GET['passkey']);
	$uid = htmlspecialchars($_GET['id']);


	if($did){
	$result = $wpdb->get_results("SELECT * FROM wev_temp_user WHERE confirm_code = '".$did."'");
	if (count ($result) > 0) {

	$sSql= "SELECT * FROM wev_temp_user WHERE confirm_code = '".$did."'";

	$data = array();

	$data = $wpdb->get_row($sSql, ARRAY_A);


	// Preset the form fields
	$form = array(
		'user_email' => $data['user_email'],
		'user_name' => $data['user_name'],
		'user_pass' => $data['user_pass']
	);

	$email = $form['user_email'];
	$username = $form['user_name'];
	$password = $form['user_pass'];

	remove_filter( 'user_register', array( $this, 'create_temp_user' ) );
	$create_user = $this->wev_create_new_customer($email, $username, $password);

	if ($create_user ){
			echo "Account activation successful. Please, click <a href='".home_url()."/my-account/'>here</a> to login." ;
	}else{

		wp_redirect( home_url() ); exit;
		}		
	}else{	
	wp_redirect( home_url() ); exit;
	}
}
}

	public function wev_create_new_customer( $email, $username, $password ) {
	global $wpdb, $wp_version;
		$pw = $password;
		$password_generated = true;

	$new_customer_data = apply_filters( 'woocommerce_new_customer_data', array(
		'user_login' => $username,
		'user_pass'  => $pw,
		'user_email' => $email,
		'role'       => 'customer'
	) );

	if(is_null(username_exists( $username ))){

		$reg_date = date("Y-m-d H:i:s");

								$sql = $wpdb->prepare(
			"INSERT INTO `".wp_users."`
			(`user_login`, `user_pass`, `user_email`, `user_nicename`, `display_name`, `user_registered`)
			VALUES(%s, %s, %s,%s, %s, %s)",
			array($username,$password,$email,$username, $username,$reg_date)
		);

		$customer_id =	$wpdb->query($sql);

	do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated);
	return true;

	}else{

	//return new WP_Error( 'registration-error', '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
	return false;

	}
}

	public function set_customer_id($user_id){
		 $this->customer_id = $user_id;
	}

	public function create_temp_user($user_id){

		if (!$user_id) return;

		global $wpdb, $wp_version;
		$sSql = $wpdb->prepare("
		SELECT *
		FROM `".wp_users."`
		WHERE `ID` = %d
		LIMIT 1
		",
		array($user_id));
	
		$data = array();
		$data = $wpdb->get_row($sSql, ARRAY_A);

		$form = array('user_email' => $data['user_email'],'user_login' => $data['user_login'],'user_pass' => $data['user_pass']);

		$to = $form['user_email'];
		$un = $form['user_login'];
		$pw = $form['user_pass'];

		$admin_email = get_bloginfo('admin_email');
		$hash = md5($un.$pw);

		$email =$this->send_verification($to, $un, $pw, $hash);

					$sql = $wpdb->prepare(
			"INSERT INTO `".wev_temp_user."`
			(`user_name`, `user_pass`, `user_email`, `confirm_code`)
			VALUES(%s, %s, %s, %s)",
			array($un,$pw,$to,$hash)
		);
		$wpdb->query($sql);

			$sSql = $wpdb->prepare("DELETE FROM `".wp_users."`
					WHERE `ID` = %d
					LIMIT 1", $user_id);
			$wpdb->query($sSql);
			$p = 	wc_add_notice(  'A confirmation link has been sent to your email address. Please follow the instructions in the email to activate your account.' );
			echo $p ;
	}

	/* Verification Email */
	public function send_verification($to, $un, $pw, $hash){
		global $wpdb, $wp_version;
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$headers = "Content-Type: text/htmlrn";

		$subject = 'Activate your '.$blogname.' account'; 
		$message = 'Hello,'. $un.'<br/>';
		$message .= 'Please visit this link to activate your account:';
		$message .= "<br/>";
		$message .= home_url('/').'activate?id='.$un.'&passkey='.$hash;    

		woocommerce_mail($to, $subject, $message, $headers, $attachments);
		return;
	}

	public function install(){
		global $wpdb, $wp_version;

		if($wpdb->get_var("show tables like '".wev_temp_user. "'") != wev_temp_user){
		$sSql = "CREATE TABLE IF NOT EXISTS `". wev_temp_user. "` (";
		$sSql = $sSql . "`user_id` INT NOT NULL AUTO_INCREMENT ,";
		$sSql = $sSql . "`user_name` TEXT NOT NULL,";
		$sSql = $sSql . "`user_pass` TEXT NOT NULL,";
		$sSql = $sSql . "`user_email` TEXT NOT NULL,";
		$sSql = $sSql . "`confirm_code` TEXT NOT NULL,";
		$sSql = $sSql . "PRIMARY KEY (`user_id`)";
		$sSql = $sSql . ")";
		$wpdb->query($sSql);
	}
	}

	public function uninstall(){

	}
}
