<?php
/*
 * Plugin Name: WooCommerce Email Verification
 * Version: 1.0.0
 * Plugin URI: http://wordpress.org/plugins/woocommerce-email-verification/
 * Description: Sends a verification link on users mail ID to activate their account after register.
 * Author: subhansanjaya
 * Author URI: http://www.backraw.com/
 * Requires at least: 3.0
 * Tested up to: 3.8
 */
error_reporting(E_ALL);
if(! defined( 'ABSPATH' )) exit; // Exit if accessed directly

require('includes/class-wev-email-verification.php');
$wev = new WEV_Email_Verification();
