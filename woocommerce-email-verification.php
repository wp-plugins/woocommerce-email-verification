<?php
/*
 * Plugin Name: WooCommerce Email Verification
 * Version: 2.3.1
 * Plugin URI: http://wordpress.org/plugins/woocommerce-email-verification/
 * Description: Sends a verification link on users mail ID to activate their account after register.
 * Author: subhansanjaya
 * Author URI: http://www.backraw.com/
 * Requires at least: 3.0
 * Tested up to: 3.8
 */
if(! defined( 'ABSPATH' )) exit; // Exit if accessed directly

require('includes/class-wev-email-verification.php');

global $wev;
$wev = new WEV_Email_Verification();

register_activation_hook( __FILE__,  'wev_install'  );

	 function wev_install(){
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