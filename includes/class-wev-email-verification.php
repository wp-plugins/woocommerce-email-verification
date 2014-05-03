<?php
class WEV_Email_Verification{

	public $customer_id;
	public function __construct(){
		
		add_shortcode( 'woocommerce-email-verification', array( $this, 'add_shortcode' ) );
		add_action( 'user_register', array( $this, 'create_temp_user' ) );

	//	add_action( 'plugins_loaded', array( $this, 'replace_process_registration' ) );
	}

	public function replace_process_registration(){
	global $woocommerce;
	//remove_action( 'init', 'woocommerce_process_registration' );
	//add_action( 'init', array( $woocommerce, 'process_registration' )  );
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
			"INSERT INTO `{$wpdb->base_prefix}users`
			(`user_login`, `user_pass`, `user_email`, `user_nicename`, `display_name`, `user_registered`)
			VALUES(%s, %s, %s,%s, %s, %s)",
			array($username,$password,$email,$username, $username,$reg_date)
		);

			$customer_id =	$wpdb->query($sql);

			$user_id = get_user_by( 'email', $email);
			$ud = $user_id->ID;

			$user = new WP_User( $ud );
			$user ->add_role( 'customer' ); 
	
	do_action( 'woocommerce_created_customer', $ud, $new_customer_data, $password_generated);
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
	if ( !current_user_can( 'manage_options' ) ) {
		global $woocommerce;

		if (!$user_id) return;

		global $wpdb, $wp_version;
		$sSql = $wpdb->prepare("
		SELECT *
		FROM `{$wpdb->base_prefix}users`
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

			$sSql = $wpdb->prepare("DELETE FROM `{$wpdb->base_prefix}users`
					WHERE `ID` = %d
					LIMIT 1", $user_id);
			$wpdb->query($sSql);
		
			if ( ! is_object( $woocommerce ) || version_compare( $woocommerce->version, '2.1', '<' ) ) {
			$woocommerce->add_message( 'A confirmation link has been sent to your email address. Please follow the instructions in the email to activate your account.' );
			}else{
			wc_add_notice('A confirmation link has been sent to your email address. Please follow the instructions in the email to activate your account.',$notice_type = 'success');
			}
		}

	}

	/* Verification Email */
	public function send_verification($to, $un, $pw, $hash){
		global $wpdb, $wp_version;
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$headers = "Content-Type: text/htmlrn";

		$subject = 'Activate your '.$blogname.' account'; 
		$message = 'Hello '. $un.',<br/><br/>';
		$message .= 'To activate your account and access the feature you were trying to view, copy and paste the following link into your web browser:';
		$message .= "<br/><a href='";
		$message .= home_url('/').'activate?passkey='.$hash;
		$message .= "'>".home_url('/').'activate?passkey='.$hash."</a><br/><br/>";
		$message .= "Thank you for registering with us.";
		$message .= '<br/><br/>Yours sincerely,<br/>'.$blogname;


		woocommerce_mail($to, $subject, $message, $headers, $attachments);
		return;
	}
}