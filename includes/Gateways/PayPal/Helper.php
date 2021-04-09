<?php

namespace WeDevs\Dokan\Gateways\PayPal;

/**
 * Class Helper
 * @package WeDevs\Dokan\Gateways\PayPal
 *
 * @since DOKAN_LITE_SINCE
 *
 * @author weDevs
 */
class Helper {

    /**
     * Get PayPal gateway id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public static function get_gateway_id() {
        return 'dokan_paypal_marketplace';
    }

    /**
     * Get settings of the gateway
     *
     * @param null $key
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return mixed|void
     */
    public static function get_settings( $key = null ) {
        $settings = get_option( 'woocommerce_' . static::get_gateway_id() . '_settings', [] );

        if ( $key && isset( $settings[ $key ] ) ) {
            return $settings[ $key ];
        }

        return $settings;
    }

    /**
     * Check whether it's enabled or not
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = static::get_settings();

        return ! empty( $settings['enabled'] ) && 'yes' === $settings['enabled'];
    }

    /**
     * Check if this gateway is enabled and ready to use
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_ready() {
        if ( ! static::is_enabled() ||
            empty( static::get_partner_id() ) ||
            empty( static::get_client_id() ) ||
            empty( static::get_client_secret() ) ) {
            return false;
        }

        if ( ! is_ssl() && ! static::is_test_mode() ) {
            //return false;
        }

        return true;
    }

    /**
     * Check if the seller is enabled for receive paypal payment
     *
     * @param $seller_id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_seller_enable_for_receive_payment( $seller_id ) {
        return static::get_seller_merchant_id( $seller_id ) && static::get_seller_enabled_for_received_payment( $seller_id );
    }

    /**
     * Unbranded credit card mode is allowed or not
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_ucc_enabled() {
        $wc_base_country = WC()->countries->get_base_country();

        if (
            'smart' === static::get_button_type() &&
            static::is_ucc_mode_allowed() &&
            array_key_exists( $wc_base_country, static::get_advanced_credit_card_debit_card_supported_countries() ) &&
            in_array( get_woocommerce_currency(), static::get_advanced_credit_card_debit_card_supported_currencies( $wc_base_country ), true )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if ucc mode is enabled for all seller in the cart
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_ucc_enabled_for_all_seller_in_cart() {
        $ucc_enabled = static::is_ucc_enabled();

        if ( ! $ucc_enabled ) {
            return false;
        }

        foreach ( WC()->cart->get_cart() as $item ) {
            $product_id = $item['data']->get_id();

            // we do not need to check vendors ucc status if product is a vendor subscription product
            if ( static::is_vendor_subscription_product( $product_id ) ) {
                continue;
            }

            $seller_id = get_post_field( 'post_author', $product_id );

            if ( $ucc_enabled && ! get_user_meta( $seller_id, static::get_seller_enable_for_ucc_key(), true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether the gateway in test mode or not
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_test_mode() {
        $settings = static::get_settings();

        return ! empty( $settings['test_mode'] ) && 'yes' === $settings['test_mode'];
    }

    /**
     * Check whether the test mode is enabled or not
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_debug_log_enabled() {
        $settings = static::get_settings();

        return ! empty( $settings['debug'] ) && 'yes' === $settings['debug'];
    }

    /**
     * Check whether Unbranded Credit Card mode is enabled or not
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function is_ucc_mode_allowed() {
        $settings = static::get_settings();

        return ! empty( $settings['ucc_mode'] ) && 'yes' === $settings['ucc_mode'];
    }

    /**
     * Get list of supported webhook events
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public static function get_supported_webhook_events() {
        return apply_filters(
            'dokan_paypal_get_supported_webhook_events', [
                'CHECKOUT.ORDER.APPROVED'          => 'CheckoutOrderApproved',
                'CHECKOUT.ORDER.COMPLETED'         => 'CheckoutOrderCompleted',
                'MERCHANT.PARTNER-CONSENT.REVOKED' => 'MerchantPartnerConsentRevoked',
            ]
        );
    }

    /**
     * Get advanced credit card debit card supported countries (UCC/Unbranded payments)
     *
     * @see https://developer.paypal.com/docs/business/checkout/reference/currency-availability-advanced-cards/
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public static function get_advanced_credit_card_debit_card_supported_countries() {
        $supported_countries = [
            'AU' => 'Australia',
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'CA' => 'Canada',
            'CY' => 'Cyprus',
            'CZ' => 'Czech',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'FI' => 'Finland',
            'FR' => 'France',
            'GR' => 'Greece',
            'HU' => 'Hungary',
            'IT' => 'Italy',
            'LV' => 'Latvia',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'US' => 'United States',
            'GB' => 'United Kingdom',
        ];

        return apply_filters( 'dokan_paypal_advanced_credit_card_debit_card_supported_countries', $supported_countries );
    }

    /**
     * Get advanced credit card debit card supported currencies
     *
     * @see https://developer.paypal.com/docs/business/checkout/reference/currency-availability-advanced-cards/
     *
     * @param $country_code
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array|bool
     */
    public static function get_advanced_credit_card_debit_card_supported_currencies( $country_code ) {
        $supported_countries = static::get_advanced_credit_card_debit_card_supported_countries();

        if ( ! array_key_exists( $country_code, $supported_countries ) ) {
            return false;
        }

        if ( 'US' === $country_code ) {
            return static::get_advanced_credit_card_debit_card_us_supported_currencies();
        }

        return static::get_supported_currencies();
    }

    /**
     * Get Paypal supported currencies except US
     * for advanced credit card debit card
     *
     * @see https://developer.paypal.com/docs/business/checkout/reference/currency-availability-advanced-cards/
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public static function get_supported_currencies() {
        return apply_filters(
            'dokan_paypal_supported_currencies', [
                'AUD',
                'CAD',
                'CHF',
                'CZK',
                'DKK',
                'EUR',
                'GBP',
                'HKD',
                'HUF',
                'JPY',
                'NOK',
                'NZD',
                'PLN',
                'SEK',
                'SGD',
                'USD',
            ]
        );
    }

    /**
     * Get US supported currencies for advanced credit card debit card
     *
     * @see https://developer.paypal.com/docs/business/checkout/reference/currency-availability-advanced-cards/
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public static function get_advanced_credit_card_debit_card_us_supported_currencies() {
        return apply_filters(
            'dokan_paypal_us_supported_currencies', [
                'AUD',
                'CAD',
                'EUR',
                'GBP',
                'JPY',
                'USD',
            ]
        );
    }

    /**
     * @since DOKAN_LITE_SINCE
     * @param bool|null $test_mode
     * @return string
     */
    public static function get_seller_merchant_id_key( $test_mode = null ) {
        if ( null === $test_mode ) {
            $test_mode = static::is_test_mode();
        }
        return $test_mode ? '_dokan_paypal_test_merchant_id' : '_dokan_paypal_merchant_id';
    }

    /**
     * @since DOKAN_LITE_SINCE
     * @param bool|null $test_mode
     * @return string
     */
    public static function get_seller_enabled_for_received_payment_key( $test_mode = null ) {
        if ( null === $test_mode ) {
            $test_mode = static::is_test_mode();
        }
        return $test_mode ? '_dokan_paypal_test_enable_for_receive_payment' : '_dokan_paypal_enable_for_receive_payment';
    }

    /**
     * @since DOKAN_LITE_SINCE
     * @param bool|null $test_mode
     * @return string
     */
    public static function get_seller_marketplace_settings_key( $test_mode = null ) {
        if ( null === $test_mode ) {
            $test_mode = static::is_test_mode();
        }
        return $test_mode ? '_dokan_paypal_test_marketplace_settings' : '_dokan_paypal_marketplace_settings';
    }

    /**
     * @since DOKAN_LITE_SINCE
     * @param bool|null $test_mode
     * @return string
     */
    public static function get_seller_payments_receivable_key( $test_mode = null ) {
        if ( null === $test_mode ) {
            $test_mode = static::is_test_mode();
        }
        return $test_mode ? '_dokan_paypal_test_payments_receivable' : '_dokan_paypal_payments_receivable';
    }

    /**
     * @since DOKAN_LITE_SINCE
     * @param bool|null $test_mode
     * @return string
     */
    public static function get_seller_primary_email_confirmed_key( $test_mode = null ) {
        if ( null === $test_mode ) {
            $test_mode = static::is_test_mode();
        }
        return $test_mode ? '_dokan_paypal_test_primary_email_confirmed' : '_dokan_paypal_primary_email_confirmed';
    }

    /**
     * @since DOKAN_LITE_SINCE
     * @param bool|null $test_mode
     * @return string
     */
    public static function get_seller_enable_for_ucc_key( $test_mode = null ) {
        if ( null === $test_mode ) {
            $test_mode = static::is_test_mode();
        }
        return $test_mode ? '_dokan_paypal_test_enable_for_ucc' : '_dokan_paypal_enable_for_ucc';
    }

    /**
     *
     * @since DOKAN_LITE_SINCE
     * @param int $seller_id
     * @return string
     */
    public static function get_seller_merchant_id( $seller_id ) {
        return get_user_meta( $seller_id, static::get_seller_merchant_id_key(), true );
    }

    /**
     *
     * @since DOKAN_LITE_SINCE
     * @param int $seller_id
     * @return string
     */
    public static function get_seller_enabled_for_received_payment( $seller_id ) {
        return get_user_meta( $seller_id, static::get_seller_enabled_for_received_payment_key(), true );
    }

    /**
     * Get branded payment supported countries
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public static function get_branded_payment_supported_countries() {
        $supported_countries = [
            'AU' => 'Australia',
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'CA' => 'Canada',
            'CY' => 'Cyprus',
            'CZ' => 'Czech',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'FI' => 'Finland',
            'FR' => 'France',
            'GR' => 'Greece',
            'DE' => 'Germany',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IT' => 'Italy',
            'LV' => 'Latvia',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'US' => 'United States',
            'GB' => 'United Kingdom',
        ];

        return apply_filters( 'dokan_paypal_branded_payment_supported_countries', $supported_countries );
    }

    /**
     * Get PayPal product type based on country
     *
     * @param $country_code
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool|string
     */
    public static function get_product_type( $country_code ) {
        $branded_ucc_supported_countries = static::get_advanced_credit_card_debit_card_supported_countries();
        $branded_supported_countries     = static::get_branded_payment_supported_countries();

        if ( ! array_key_exists( $country_code, array_merge( $branded_ucc_supported_countries, $branded_supported_countries ) ) ) {
            return false;
        }

        if ( array_key_exists( $country_code, $branded_ucc_supported_countries ) ) {
            return 'PPCP';
        }

        if ( array_key_exists( $country_code, $branded_supported_countries ) ) {
            return 'EXPRESS_CHECKOUT';
        }
    }

    /**
     * Log PayPal error data with debug id
     *
     * @param int $id
     * @param \WP_Error $error
     * @param string $meta_key
     *
     * @param string $context
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public static function log_paypal_error( $id, $error, $meta_key, $context = 'post' ) {
        $error_data = $error->get_error_data();

        //store paypal debug id
        if ( isset( $error_data['paypal_debug_id'] ) ) {
            switch ( $context ) {
                case 'post':
                    update_post_meta( $id, "_dokan_paypal_{$meta_key}_debug_id", $error_data['paypal_debug_id'] );
                    break;

                case 'user':
                    update_user_meta( $id, "_dokan_paypal_{$meta_key}_debug_id", $error_data['paypal_debug_id'] );
                    break;
            }
        }

        dokan_log( "[Dokan PayPal Marketplace] $meta_key Error:\n" . print_r( $error, true ), 'error' );
    }

    /**
     * Get user id by merchant id
     *
     * @param $merchant_id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return int
     */
    public static function get_user_id_by_merchant_id( $merchant_id ) {
        global $wpdb;

        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `user_id` FROM $wpdb->usermeta WHERE `meta_key` = %s AND `meta_value`= %s",
                static::get_seller_merchant_id_key(),
                $merchant_id
            )
        );

        return absint( $user_id );
    }

    /**
     * Get Percentage of from a price
     *
     * @param $price
     * @param $extra_amount
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return float|int
     */
    public static function get_percentage( $price, $extra_amount ) {
        $percentage = ( $extra_amount * 100 ) / $price;

        return $percentage;
    }

    /**
     * Get webhook events for notification
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public static function get_webhook_events_for_notification() {
        $events = array_keys( static::get_supported_webhook_events() );

        $events = array_merge(
            $events, [
				'BILLING.SUBSCRIPTION.ACTIVATED',
				'BILLING.SUBSCRIPTION.CANCELLED',
				'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
				'PAYMENT.SALE.COMPLETED',
			]
        );

        $notification_events = array_map(
            function ( $event ) {
                return [ 'name' => $event ];
            }, $events
        );

        return $notification_events;
    }

    /**
     * Get PayPal client id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public static function get_client_id() {
        $key      = static::is_test_mode() ? 'test_app_user' : 'app_user';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }

    /**
     * Get PayPal client secret key
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public static function get_client_secret() {
        $key      = static::is_test_mode() ? 'test_app_pass' : 'app_pass';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }

    /**
     * Get Paypal partner id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public static function get_partner_id() {
        $key      = 'partner_id';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }

    /**
     * Get client id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public static function get_button_type() {
        $key      = 'button_type';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }

    /**
     * Get Cart item quantity exceeded error message
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public static function get_max_quantity_error_message() {
        $key      = 'max_error';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }



    /**
     * Check wheter subscription module is enabled or not
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public static function has_vendor_subscription_module() {
        // don't confused with product_subscription, id for vendor subscription module is product_subscription
        return function_exists( 'dokan_pro' ) && dokan_pro()->module->is_active( 'product_subscription' );
    }

    /**
     * Get subscription product from an order
     * @param \WC_Order $order
     * @since DOKAN_LITE_SINCE
     * @return \WC_Product|bool|null
     */
    public static function get_vendor_subscription_product_by_order( $order ) {
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            if ( 'product_pack' === $product->get_type() ) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Check if the order is a subscription order
     *
     * @param \WC_Order $order
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     **/
    public static function is_vendor_subscription_order( $order ) {
        if ( ! static::has_vendor_subscription_module() ) {
            return false;
        }

        $product = static::get_vendor_subscription_product_by_order( $order );

        return $product ? true : false;
    }

    /**
     * Check if the order is a subscription order
     *
     * @param \WC_Product|int $product
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     **/
    public static function is_vendor_subscription_product( $product ) {
        if ( is_int( $product ) ) {
            $product = wc_get_product( $product );
        }

        if ( ! $product instanceof \WC_Product ) {
            return false;
        }

        if ( ! self::has_vendor_subscription_module() ) {
            return false;
        }

        if ( 'product_pack' === $product->get_type() ) {
            return true;
        }

        return false;
    }
}
