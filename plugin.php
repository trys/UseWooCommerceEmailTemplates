<?php
/*
Plugin Name: Use WooCommerce Email Templates
Description: Style all WordPress emails with WooCommerce templates
Plugin URI: http://www.trysmudford.com
Author: Trys Mudford
Author URI: http://www.trysmudford.com
Version: 1.01
*/


/**
 * Do the business in wp_mail
 *
 * @param	array	$atts
 * @return	array
 */
function uwt_wp_mail( $atts ) {

	global $uwt_woocommerce;
	
	if ( ! $uwt_woocommerce && class_exists( 'WooCommerce' ) ) {
		$atts[ 'message' ] = uwt_style_message( $atts[ 'message' ], $atts[ 'subject' ] );
		$atts[ 'headers' ][] = 'from:' . wp_specialchars_decode( esc_html( get_bloginfo( 'name' ) ), ENT_QUOTES ) . ' <' . get_bloginfo( 'admin_email' ) . '>';
	}

	$uwt_woocommerce = false;

	return $atts;
}
add_filter( 'wp_mail', 'uwt_wp_mail' );


/**
 * Style that message
 *
 * @param	string	$message
 * @param	string 	$subject
 * @return	string
 */
function uwt_style_message( $message, $subject ) {

	ob_start();
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $subject ) );
		echo wpautop( wptexturize( $message ) );
		wc_get_template( 'emails/email-footer.php' );
	$message = ob_get_clean();
	
	ob_start();
	wc_get_template( 'emails/email-styles.php' );
	$css = ob_get_clean();

	if ( ! class_exists( 'Emogrifier' ) && class_exists( 'DOMDocument' ) ) {
		include_once( dirname( WC_PLUGIN_FILE ) . '/includes/libraries/class-emogrifier.php' );
	}

	try {
		$emogrifier = new Emogrifier( $message, $css );
		$message = $emogrifier->emogrify();
	} catch ( Exception $e ) {
		$logger = new WC_Logger();
		$logger->add( 'emogrifier', $e->getMessage() );
	}

	return $message;
}


/**
 * Set Content type
 *
 * @param	string	$content_type
 * @return	string
 */
function uwt_wp_mail_content_type( $content_type ) {
	return $content_type === 'text/plain' ? 'text/html' : $content_type;
}
add_filter( 'wp_mail_content_type', 'uwt_wp_mail_content_type' );


/**
 * Set flag to indicate this is a WooCommerce email
 *
 * @return	string
 */
function uwt_woocommerce_flag( $css ) {
	global $uwt_woocommerce;
	$uwt_woocommerce = true;
	return $css;
}
add_filter( 'woocommerce_email_styles', 'uwt_woocommerce_flag' );
