<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add a new settings tab to the WooCommerce settings tabs array.
 *
 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels.
 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels.
 */
function sprucely_add_settings_tab( $settings_tabs ) {
    $settings_tabs['payment_surcharges'] = __( 'Payment Surcharges', 'sprucely-designed' );
    return $settings_tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'sprucely_add_settings_tab', 50 );

/**
 * Uses the WooCommerce admin fields API to output settings via the @hook woocommerce_admin_field_* action.
 */
function sprucely_settings_tab() {
    woocommerce_admin_fields( sprucely_get_settings() );
}
add_action( 'woocommerce_settings_payment_surcharges', 'sprucely_settings_tab' );

/**
 * Uses the WooCommerce options API to save settings via the @hook woocommerce_update_options_* action.
 */
function sprucely_update_settings() {
    woocommerce_update_options( sprucely_get_settings() );
}
add_action( 'woocommerce_update_options_payment_surcharges', 'sprucely_update_settings' );

/**
 * Get all the settings for this plugin for @hook woocommerce_admin_fields output.
 *
 * @return array Array of settings for @hook woocommerce_admin_fields.
 */
function sprucely_get_settings() {
    $settings = array(
        'section_title' => array(
            'name'     => __( 'Payment Surcharges Settings', 'sprucely-designed' ),
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'payment_surcharges_section_title'
        )
    );

    // Get available payment gateways
    $payment_gateways = WC()->payment_gateways->payment_gateways();

    foreach ( $payment_gateways as $gateway ) {
        if ( $gateway->enabled == 'yes' ) {
            $gateway_id = $gateway->id;
            $gateway_title = $gateway->get_title();

            // Add settings for each payment method
            $settings[ $gateway_id . '_section' ] = array(
                'name' => sprintf( __( '%s Surcharge Settings', 'sprucely-designed' ), $gateway_title ),
                'type' => 'title',
                'desc' => '',
                'id'   => $gateway_id . '_section_title'
            );

            $settings[ $gateway_id . '_fixed_fee' ] = array(
                'name' => __( 'Fixed Fee', 'sprucely-designed' ),
                'type' => 'text',
                'desc' => __( 'Fixed fee amount', 'sprucely-designed' ),
                'id'   => $gateway_id . '_fixed_fee'
            );

            $settings[ $gateway_id . '_percentage_fee' ] = array(
                'name' => __( 'Percentage Fee', 'sprucely-designed' ),
                'type' => 'text',
                'desc' => __( 'Percentage fee (without % sign)', 'sprucely-designed' ),
                'id'   => $gateway_id . '_percentage_fee'
            );

            $settings[ $gateway_id . '_min_fee' ] = array(
                'name' => __( 'Minimum Fee', 'sprucely-designed' ),
                'type' => 'text',
                'desc' => __( 'Minimum fee amount', 'sprucely-designed' ),
                'id'   => $gateway_id . '_min_fee'
            );

            $settings[ $gateway_id . '_max_fee' ] = array(
                'name' => __( 'Maximum Fee', 'sprucely-designed' ),
                'type' => 'text',
                'desc' => __( 'Maximum fee amount', 'sprucely-designed' ),
                'id'   => $gateway_id . '_max_fee'
            );

            $settings[ $gateway_id . '_section_end' ] = array(
                'type' => 'sectionend',
                'id'   => $gateway_id . '_section_end'
            );
        }
    }

    $settings['section_end'] = array(
         'type' => 'sectionend',
         'id' => 'payment_surcharges_section_end'
    );

    return apply_filters( 'sprucely_settings', $settings );
}

/**
 * Validate and sanitize the options.
 *
 * @param mixed  $value The unsanitized value.
 * @param mixed  $option The option array.
 * @param mixed  $raw_value The raw unsanitized value.
 * @return mixed The sanitized value.
 */
function sprucely_validate_and_sanitize_options( $value, $option, $raw_value ) {
    $option_id = $option['id'];

    // Only process our own options
    if ( strpos( $option_id, 'fixed_fee' ) !== false || strpos( $option_id, 'percentage_fee' ) !== false ||
         strpos( $option_id, 'min_fee' ) !== false || strpos( $option_id, 'max_fee' ) !== false ) {

        // Sanitize as a decimal number
        $sanitized_value = wc_format_decimal( $raw_value );

        // Additional validation can be added here if necessary
        // For example, check if the value is a non-negative number

        return $sanitized_value;
    }

    return $value;
}
add_filter( 'woocommerce_admin_settings_sanitize_option', 'sprucely_validate_and_sanitize_options', 10, 3 );


