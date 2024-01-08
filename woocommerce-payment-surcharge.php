<?php
/**
 * Plugin Name: WooCommerce Payment Surcharge
 * Description: Adds a surcharge to WooCommerce cart for specific payment methods.
 * Version: 1.0.0
 * Author: Sprucely Designed, LLC
 * Author URI: https://www.sprucely.net
 * WC requires at least: [Minimum WooCommerce version]
 * WC tested up to: [Last WooCommerce version tested]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add a non-taxable surcharge to WooCommerce cart for specific payment methods.
 * 
 * @param WC_Cart $cart The cart object.
 */
function sprucely_add_payment_surcharge( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    $chosen_payment_method = WC()->session->get('chosen_payment_method');

    // Stripe Credit Card.
    if ( $chosen_payment_method === 'stripe_cc' ) {
        $surcharge_percentage = 2.9 / 100; // 2.9% surcharge
        $surcharge_fixed = 0.30;           // Additional fixed surcharge

        $cart_total = $cart->cart_contents_total + $cart->shipping_total;
        $total_with_surcharge = ($cart_total + $surcharge_fixed) / (1 - $surcharge_percentage);
        $surcharge = $total_with_surcharge - $cart_total;

        $cart->add_fee( __( 'Credit Card Surcharge', 'sprucely-designed' ), $surcharge, false, '' );
    }

    // Stripe ACH.
    else if ( $chosen_payment_method === 'stripe_ach' ) {
        $surcharge_percentage = 0.8 / 100; // 0.8% surcharge.
        $max_surcharge = 5.00;             // Maximum surcharge limit.

        $cart_total = $cart->cart_contents_total + $cart->shipping_total;
        $surcharge = min( $cart_total * $surcharge_percentage, $max_surcharge );

        $cart->add_fee( __( 'ACH Surcharge', 'sprucely-designed' ), $surcharge, false, '' );
    }
}

add_action( 'woocommerce_cart_calculate_fees', 'sprucely_add_payment_surcharge', 20, 1 );
