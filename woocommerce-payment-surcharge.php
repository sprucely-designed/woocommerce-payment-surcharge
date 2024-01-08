<?php
/**
 * Plugin Name: WooCommerce Payment Surcharge
 * Description: Adds a surcharge to WooCommerce cart for specific payment methods.
 * Version: 1.0.0
 * Author: Sprucely Designed, LLC
 * Author URI: https://www.sprucely.net
 * WC requires at least: [Minimum WooCommerce version]
 * WC tested up to: [Last WooCommerce version tested]
 *
 * @package sprucely-wc-payment-surcharge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enqueues checkout scripts for the WooCommerce Payment Surcharge plugin.
 *
 * This function is responsible for enqueueing the JavaScript file that handles
 * the dynamic update of payment surcharges on the checkout page. It ensures that
 * the script is only loaded on the WooCommerce checkout page. The script listens
 * for changes in the selected payment method and triggers an AJAX request to
 * update the surcharge fees accordingly. The function also localizes the script,
 * providing it with the necessary AJAX URL and security nonce.
 *
 * @hook wp_enqueue_scripts
 */
function sprucely_enqueue_checkout_scripts() {
	if ( is_checkout() ) {
		wp_enqueue_script( 'sprucely-checkout-js', plugin_dir_url( __FILE__ ) . 'js/sprucely-checkout.js', array(), '1.0.0', true );

		wp_localize_script(
			'sprucely-checkout-js',
			'sprucelyAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sprucely-update-fee' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sprucely_enqueue_checkout_scripts' );

// Include the settings file.
require_once 'wc-payments-surcharge-settings.php';

/**
 * Add a non-taxable surcharge to WooCommerce cart for specific payment methods.
 *
 * @param WC_Cart $cart The cart object.
 */
function sprucely_add_payment_surcharge( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );
	$payment_gateways      = WC()->payment_gateways->payment_gateways();

	if ( isset( $payment_gateways[ $chosen_payment_method ] ) ) {
		// Retrieve and log the custom settings
		$fixed_fee_option      = $chosen_payment_method . '_fixed_fee';
		$percentage_fee_option = $chosen_payment_method . '_percentage_fee';
		$min_fee_option        = $chosen_payment_method . '_min_fee';
		$max_fee_option        = $chosen_payment_method . '_max_fee';

		$fixed_fee      = get_option( $fixed_fee_option, 0 );
		$percentage_fee = get_option( $percentage_fee_option, 0 ) / 100;
		$min_fee        = get_option( $min_fee_option, 0 );
		$max_fee        = get_option( $max_fee_option, PHP_INT_MAX );

		// Log the retrieved options for debugging
		error_log( 'Fixed Fee: ' . $fixed_fee );
		error_log( 'Percentage Fee: ' . $percentage_fee );
		error_log( 'Minimum Fee: ' . $min_fee );
		error_log( 'Maximum Fee: ' . $max_fee );

		// Validate numeric values
		if ( ! is_numeric( $fixed_fee ) || ! is_numeric( $percentage_fee ) || ! is_numeric( $min_fee ) || ! is_numeric( $max_fee ) ) {
			error_log( 'One of the surcharge values is not numeric.' );
			return;
		}

		$cart_total     = $cart->cart_contents_total + $cart->shipping_total;
		$calculated_fee = max( $min_fee, min( $cart_total * $percentage_fee + $fixed_fee, $max_fee ) );

		if ( $calculated_fee > 0 ) {
			$cart->add_fee( __( 'Payment Method Surcharge', 'sprucely-designed' ), $calculated_fee, false, '' );
		}
	}
}


add_action( 'woocommerce_cart_calculate_fees', 'sprucely_add_payment_surcharge', 20, 1 );


/**
 * Handles the AJAX request to update the surcharge fees based on the selected payment method.
 *
 * This function responds to the AJAX call triggered when a customer changes their payment
 * method at checkout. It updates the WooCommerce session with the newly selected payment method
 * and recalculates the cart fees accordingly. After updating the session, it triggers a refresh
 * of the WooCommerce checkout area to reflect the updated surcharge fees. The function is registered
 * to both logged-in and guest AJAX actions to ensure functionality for all customers.
 *
 * @uses WC()->session Set the chosen payment method in the session.
 * @uses WC()->cart->calculate_fees() Recalculate fees based on the new payment method.
 * @uses wp_send_json_success() Send a successful JSON response back to the browser.
 *
 * @hook wp_ajax_sprucely_update_surcharge For logged-in users.
 * @hook wp_ajax_nopriv_sprucely_update_surcharge For guests.
 */
function sprucely_ajax_update_surcharge() {
	check_ajax_referer( 'sprucely-update-fee', 'nonce' );

	$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';

	WC()->session->set( 'chosen_payment_method', $payment_method );

	// Recalculate fees (modify sprucely_add_payment_surcharge to be reusable here).
	WC()->cart->calculate_fees();

	wp_send_json_success();
}
add_action( 'wp_ajax_sprucely_update_surcharge', 'sprucely_ajax_update_surcharge' );
add_action( 'wp_ajax_nopriv_sprucely_update_surcharge', 'sprucely_ajax_update_surcharge' );

