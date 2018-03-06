<?php

/**
 * @package Silverback Email Services
 * @version 1.1
 */

/**
Plugin Name: Silverback Email Services
Plugin URI: https://github.com/silverbackstudio/wp-email
Description: SilverbackStudio Mailing Classes
Author: Silverback Studio
Version: 1.1
Author URI: http://www.silverbackstudio.it/
Text Domain: svbk-email-services
 */


function svbk_email_services_init() {
	load_plugin_textdomain( 'svbk-email-services', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'svbk_email_services_init' );
