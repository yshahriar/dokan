<?php

namespace WeDevs\Dokan\Gateways\PayPal;

/**
 * Class VendorWithdrawMethod
 * @package WeDevs\Dokan\Gateways\PayPal
 *
 * @since DOKAN_LITE_SINCE
 *
 * @author weDevs
 */
class VendorWithdrawMethod {

    /**
     * RegisterWithdrawMethod constructor.
     *
     * @since DOKAN_LITE_SINCE
     */
    public function __construct() {
        add_filter( 'dokan_withdraw_methods', [ $this, 'register_methods' ] );
        add_action( 'dokan_payment_settings_before_form', [ $this, 'handle_vendor_message' ], 10, 2 );
        add_action( 'template_redirect', [ $this, 'deauthorize_vendor' ] );
    }

    /**
     * Register methods
     *
     * @param array $methods
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public function register_methods( $methods ) {
        // check if admin provided all the required api keys
        if ( ! Helper::is_ready() ) {
            return $methods;
        }

        $methods['dokan-paypal-marketplace'] = [
            'title'    => __( 'Dokan PayPal Marketplace', 'dokan-lite' ),
            'callback' => [ $this, 'paypal_connect_button' ],
        ];

        return $methods;
    }

    /**
     * This enables dokan vendors to connect their PayPal account to the site PayPal gateway account
     *
     * @param $store_settings
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function paypal_connect_button( $store_settings ) {
        global $current_user;

        $email = isset( $store_settings['payment']['dokan_paypal_marketplace']['email'] ) ? esc_attr( $store_settings['payment']['dokan_paypal_marketplace']['email'] ) : $current_user->user_email;

        $is_seller_enabled = Helper::is_seller_enable_for_receive_payment( get_current_user_id() );

        $merchant_id           = Helper::get_seller_merchant_id( get_current_user_id() );
        $primary_email         = get_user_meta( get_current_user_id(), Helper::get_seller_primary_email_confirmed_key(), true );
        $nonce                 = wp_create_nonce( 'dokan-paypal-marketplace-connect' );
        $disconnect_paypal_url = wp_nonce_url(
            add_query_arg(
                [ 'action' => 'dokan-paypal-marketplace-disconnect' ],
                dokan_get_navigation_url( 'settings/payment' )
            ),
            'dokan-paypal-marketplace-disconnect'
        );

        dokan_get_template(
            'gateways/paypal/vendor-settings-payment.php',
            [
                'email'             => $email,
                'is_seller_enabled' => $is_seller_enabled,
                'nonce'             => $nonce,
                'merchant_id'       => $merchant_id,
                'primary_email'     => $primary_email,
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'disconnect_url'    => $disconnect_paypal_url,
                'load_connect_js'   => ! $is_seller_enabled && empty( $merchant_id ),
            ]
        );
    }

    /**
     * Deauthorize vendor
     *
     * @since 3.0.3
     *
     * @return void
     */
    public function deauthorize_vendor() {
        if ( ! isset( $_GET['action'] ) || 'dokan-paypal-marketplace-disconnect' !== $_GET['action'] ) {
            return;
        }

        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'dokan-paypal-marketplace-disconnect' ) ) {
            return;
        }

        $vendor_id = get_current_user_id();

        if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
            return;
        }

        // delete user metas
        $delete_metas = [
            Helper::get_seller_merchant_id_key(),
            Helper::get_seller_enabled_for_received_payment_key(),
            Helper::get_seller_payments_receivable_key(),
            Helper::get_seller_primary_email_confirmed_key(),
            Helper::get_seller_enable_for_ucc_key(),
        ];

        foreach ( $delete_metas as $meta_key ) {
            delete_user_meta( $vendor_id, $meta_key );
        }

        $url = add_query_arg(
            [
                'status'  => 'success',
                'message' => __( 'PayPal account disconnected successfully.', 'dokan-lite' ),
            ],
            dokan_get_navigation_url( 'settings/payment' )
        );

        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Handle PayPal error message for payment settings
     *
     * @param $current_user
     * @param $profile_info
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function handle_vendor_message( $current_user, $profile_info ) {
        $_get_data = wp_unslash( $_GET );//phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $status     = isset( $_get_data['status'] ) ? sanitize_text_field( rawurldecode( $_get_data['status'] ) ) : '';
        $message    = isset( $_get_data['message'] ) ? esc_html( rawurldecode( $_get_data['message'] ) ) : '';
        $class      = $status === 'error' ? 'dokan-error' : 'dokan-message';

        if ( ! empty( $status ) && ! empty( $message ) ) {
            echo "<div class='{$class}'>{$message}</div>";
        }
    }
}
