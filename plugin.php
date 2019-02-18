<?php

/**
 * @package Silverback Email Services
 * @version 1.1
 */

/**
Plugin Name: Silverback Email Services
Plugin URI: https://github.com/silverbackstudio/wp-email
Description: Send Wordpress emails through Email Services API with templates
Author: Silverback Studio
Version: 2.0
Author URI: http://www.silverbackstudio.it/
Text Domain: svbk-email-services
*/

use Svbk\WP\Email;

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

function svbk_email_init() {
	load_plugin_textdomain( 'svbk-email', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	Email\Wordpress::trackMessages();
}
add_action( 'muplugins_loaded', 'svbk_email_init' );

$options = get_option( 'svbk_email_options' );

if ( !empty($options['provider']) && !function_exists( 'wp_mail' ) ) {

	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {

        $options = get_option( 'svbk_email_options' );
	    $provider = empty($options['provider']) ? '' : $options['provider'];
	    
	    $wp_email = new Email\Wordpress();
		$message = $wp_email->message( $to, $subject, $message, $headers = '', $attachments = array() );

		$email_id = Email\Wordpress::$last_email_id;

        switch( $provider ){
            case 'sendinblue':
                $providerInstance = new  Email\Transactional\SendInBlue();  
                break;
            case 'mandrill':
                $providerInstance = new  Email\Transactional\Mandrill();  
                break;                
        }

        $template_key = 'template_' . $provider . '_' . $email_id;
        $template = empty( $options[$template_key]) ? false : $options[$template_key];
        
		if ( $template ) {
		   $providerInstance->sendTemplate( apply_filters( 'svbk_email_template', $template ), $message, Email\Wordpress::$last_email_data ) ;
		} else {
		   $providerInstance->send( $message );
		}
		
		return;
	}
}

function svbk_email_get_templates(){
	
    $result = wp_cache_get( 'svbk_email_templates' );

    if ( false === $result ) {
        $sendinblue = new  Email\Transactional\SendInBlue();
    	$result = $sendinblue->getTemplates();
    	wp_cache_set( 'svbk_email_templates', $result, null, 5 * MINUTE_IN_SECONDS );
    } 

	return $result;
}

/**
 * top level menu
 */
function svbk_email_options_page() {
	// add top level menu page
	add_submenu_page(
	    //parent_slug
	    'options-general.php',
	    //page_title
		'Email Settings',
		//menu title
		'Email Settings',
		//capability
		'manage_options',
		//menu page slug
		'svbk-email',
		//function
		'svbk_email_options_page_html'
	);
}

/**
 * register our wporg_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'svbk_email_options_page' );

/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * custom option and settings
 */
function svbk_email_settings_init() {

    $options = get_option( 'svbk_email_options' );

	 // register a new setting for "svbk-email" page
	 register_setting( 
	     //option group
	     'svbk-email',
	     //option name
	     'svbk_email_options' 
	 );

	 // register a new section in the "svbk-email" page
	add_settings_section(
	    // id
		'svbk_email_section_general', 
		//title
		__( 'General Settings', 'svbk-email' ),
		 //callback
		'svbk_email_section_general_cb',
		 //page
		'svbk-email'
	);
	

	add_settings_field(
	    //id. As of WP 4.6 this value is used only internally, use $args' label_for to populate the id inside the callback
		'svbk_email_provider', 
		//title
		 __( 'Email Provider', 'svbk-email' ),
		 //callback
		'svbk_email_field_provider_cb',
		//page
		'svbk-email',
		//section
		'svbk_email_section_general',
		[
			'label_for' => 'provider',
			'class' => 'svbk_email_row',
		]
	);

    $trackedMessages = Email\Wordpress::trackedMessages();
	
	$provider = empty($options['provider']) ? '' : $options['provider'];
	
	if ( $provider ) {
	  
    	// register a new section in the "svbk-email" page
    	add_settings_section(
    	    // id
    		'svbk_email_section_templates', 
    		//title
    		__( 'Templates', 'svbk-email' ),
    		 //callback
    		'svbk_email_section_templates_cb',
    		 //page
    		'svbk-email'
    	);		    
	  
	
    	add_settings_field(
    	    //id. As of WP 4.6 this value is used only internally, use $args' label_for to populate the id inside the callback
    		'svbk_email_default_template', 
    		//title
    		 __( 'Default Template', 'svbk-email' ),
    		 //callback
    		'svbk_email_field_select_template_cb',
    		//page
    		'svbk-email',
    		//section
    		'svbk_email_section_templates',
    		[
    			'label_for' => 'default_' . $provider . '_template',
    			'class' => 'svbk_email_row',
    		]
    	);		  
	    
    	foreach( $trackedMessages as $trackedMessage => $trackedMessageLabel ) { 
        	add_settings_field(
        	    //id. As of WP 4.6 this value is used only internally, use $args' label_for to populate the id inside the callback
        		'svbk_email_template_' . $trackedMessage, 
        		//title
        		 $trackedMessageLabel,
        		 //callback
        		'svbk_email_field_select_template_cb',
        		//page
        		'svbk-email',
        		//section
        		'svbk_email_section_templates',
        		[
        			'label_for' => 'template_' . $provider . '_'. $trackedMessage,
        			'class' => 'svbk_email_row',
        		]
        	);		
    	}
	}
}

/**
 * register our wporg_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'svbk_email_settings_init' );

/**
 * custom option and settings:
 * callback functions
 */

function svbk_email_section_general_cb( $args ) { ?>
     <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Choose wich provider you want to use to send emails', 'svbk-email' ); ?></p>
	<?php
}

function svbk_email_section_templates_cb( $args ) { ?>
     <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Choose which template should be used', 'svbk-email' ); ?></p>
	<?php
}


function svbk_email_field_provider_cb( $args ) {
	// get the value of the setting we've registered with register_setting()
	$options = get_option( 'svbk_email_options' );

	// output the field
	?>
     <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
     name="svbk_email_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
     >
         <option value="" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], '', false ) ) : ( '' ); ?>>
        	<?php esc_html_e( '-- Core --', 'svbk-email' ); ?>
         </option>
         <option value="sendinblue" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'sendinblue', false ) ) : ( '' ); ?>>
        	<?php esc_html_e( 'Sendinblue', 'svbk-email' ); ?>
         </option>
         <option value="mandrill" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'mandrill', false ) ) : ( '' ); ?>>
        	<?php esc_html_e( 'Mandrill', 'svbk-email' ); ?>
         </option>
     </select>
	<?php
}

function svbk_email_field_select_template_cb( $args ) {
	// get the value of the setting we've registered with register_setting()
	$options = get_option( 'svbk_email_options' );
	// output the field

    $templates = svbk_email_get_templates();

	?>
     <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
     name="svbk_email_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
     >
         <option value="" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], '', false ) ) : ( '' ); ?>>
        	<?php esc_html_e( '-- No Template --', 'svbk-email' ); ?>
         </option>         
         <?php foreach( $templates as $template_id => $template_name ) : ?>
         <option value="<?php esc_attr_e( $template_id ) ?>" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], $template_id, false ) ) : ( '' ); ?>>
        	<?php echo esc_html( $template_name ); ?>
         </option>
         <?php endforeach; ?>
     </select>
	<?php
}

function svbk_email_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages
	// check if the user have submitted the settings
	// wordpress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'svbk_email_messages', 'svbk_email_message', __( 'Settings Saved', 'svbk-email' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'svbk_email_messages' ); ?>
     <div class="wrap">
         <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
         <form action="options.php" method="post">
        	<?php
        	// output security fields for the registered setting "wporg"
        	settings_fields( 'svbk-email' );
        	// output setting sections and their fields
        	// (sections are registered for "wporg", each field is registered to a specific section)
        	do_settings_sections( 'svbk-email' );
        	// output save settings button
        	submit_button( 'Save Settings' );
        	?>
         </form>
     </div>
	<?php
}
