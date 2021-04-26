<?php

namespace WeDevs\Dokan\Gateways\PayPal\PaymentMethod;

use WC_Logger;
use WC_Payment_Gateway;
use WeDevs\Dokan\Gateways\PayPal\Helper;
use WeDevs\Dokan\Gateways\PayPal\Utilities\Processor;

/**
 * Class DokanPayPal
 * @package WeDevs\Dokan\Gateways\PayPal\PaymentMethod
 *
 * @since DOKAN_LITE_SINCE
 *
 * @author weDevs
 */
class DokanPayPal extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function __construct() {
        $this->init_fields();

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->init_hooks();

        if ( ! $this->is_valid_for_use() ) {
            $this->enabled = 'no';
        }
    }

    /**
     * Init essential fields
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function init_fields() {
        $this->id                 = Helper::get_gateway_id();
        $this->icon               = false;
        $this->has_fields         = true;
        $this->method_title       = __( 'Dokan PayPal Marketplace', 'dokan-lite' );
        $this->method_description = __( 'Pay Via PayPal Marketplace', 'dokan-lite' );
        $this->icon               = apply_filters( 'woocommerce_paypal_icon', DOKAN_PLUGIN_ASSEST . '/images/paypal-marketplace.png' );

        $title                = $this->get_option( 'title' );
        $this->title          = empty( $title ) ? __( 'PayPal Marketplace', 'dokan-lite' ) : $title;
        $this->test_mode      = $this->get_option( 'test_mode' );
        $this->app_user       = $this->get_option( 'app_user' );
        $this->app_pass       = $this->get_option( 'app_pass' );
        $this->app_id         = $this->get_option( 'app_id' );
        $this->test_app_user  = $this->get_option( 'test_app_user' );
        $this->test_app_pass  = $this->get_option( 'test_app_pass' );
        $this->debug          = $this->get_option( 'debug' );
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled'        => [
                'title'   => __( 'Enable/Disable', 'dokan-lite' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Dokan PayPal Marketplace', 'dokan-lite' ),
                'default' => 'no',
            ],
            'title'          => [
                'title'       => __( 'Title', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'dokan-lite' ),
                'default'     => __( 'PayPal Marketplace', 'dokan-lite' ),
                'desc_tip'    => true,
            ],
            'description'    => [
                'title'       => __( 'Description', 'dokan-lite' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'dokan-lite' ),
                'default'     => __( 'Pay via PayPal Marketplace; you can pay with your credit card if you don\'t have a PayPal account', 'dokan-lite' ),
            ],
            'test_mode'      => [
                'title'       => __( 'PayPal sandbox', 'dokan-lite' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable PayPal sandbox', 'dokan-lite' ),
                'default'     => 'no',
                /* translators: %s: paypal developer url */
                'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'dokan-lite' ), 'https://developer.paypal.com/' ),
            ],
            'partner_id'     => [
                'title'       => __( 'PayPal Merchant ID/Partner ID', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'To get Merchant ID goto Paypal Dashboard --> Account Settings --> Business Information section.', 'dokan-lite' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => 'PayPal Merchant ID/Partner ID',
            ],
            'app_user'       => [
                'title'       => __( 'Client ID', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'For this payment method your need an application credential', 'dokan-lite' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => 'Client ID',
            ],
            'app_pass'       => [
                'title'       => __( 'Client Secret', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'For this payment method your need an application credential', 'dokan-lite' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => 'Client Secret',
            ],
            'test_app_user'  => [
                'title'       => __( 'Sandbox Client ID', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'For this system please sign up in developer account and get your  application credential', 'dokan-lite' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => 'Sandbox Client ID',
            ],
            'test_app_pass'  => [
                'title'       => __( 'Sandbox Client Secret', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'For this system please sign up in developer account and get your  application credential', 'dokan-lite' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => 'Sandbox Client Secret',
            ],
            'button_type'    => [
                'title'         => __( 'Payment Button Type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Smart Payment Buttons type is recommended.', 'dokan-lite' ),
                'default'       => 'smart',
                'options'       => [
					'standard' => 'Standard Button',
					'smart'    => 'Smart Payment Buttons',
				],
            ],
            'ucc_mode'       => [
                'title'   => __( 'Allow Unbranded Credit Card', 'dokan-lite' ),
                'type'    => 'checkbox',
                'label'   => __( 'Allow Unbranded Credit Card', 'dokan-lite' ),
                'default' => 'no',
            ],
            'max_error'      => [
                'title'       => __( 'Error Message', 'dokan-lite' ),
                'type'        => 'text',
                'description' => __( 'This is the error message displayed to a shopper when attempting to add too many vendor items to the cart due to PayPal limitation.', 'dokan-lite' ),
                'default'     => __( 'Cart item quantity total exceeded - item not added to cart. Please checkout to purchase the items in your cart.', 'dokan-lite' ),
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Initialize necessary actions
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ &$this, 'process_admin_options' ] );
        add_action( 'admin_footer', [ $this, 'admin_script' ] );
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public function is_valid_for_use() {
        if ( ! in_array( get_woocommerce_currency(), Helper::get_supported_currencies(), true ) ) {
            return false;
        }

        return true;
    }

    /**
     * Display information in frontend
     * after checkout process button
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function payment_fields() {
        echo $this->get_option( 'description' );

        $payment_fields = apply_filters( 'dokan_paypal_payment_fields', true );

        if ( $payment_fields && Helper::is_ucc_enabled_for_all_seller_in_cart() ) {
            dokan_get_template( 'gateways/paypal/3DS-payment-option.php' );
        }
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return array
     */
    public function process_payment( $order_id ) {
        $order      = wc_get_order( $order_id );
        $sub_orders = get_children(
            [
                'post_parent' => $order_id,
                'post_type'   => 'shop_order',
            ]
        );
        // PayPal store orphaned order for 3 hours, so we don't need to check if multiple order created on paypal end.

        $process_payment = apply_filters( 'dokan_paypal_process_payment', [ 'order' => $order ] );

        if ( isset( $process_payment['product_type'] ) && 'product_pack' === $process_payment['product_type'] ) {
            return $process_payment['data'];
        }

        $purchase_units = [];

        if ( $order->get_meta( 'has_sub_order' ) ) {
            foreach ( $sub_orders as $item ) {
                $sub_order        = wc_get_order( $item->ID );
                $purchase_units[] = $this->make_purchase_unit_data( $sub_order );
            }
        } else {
            $purchase_units[] = $this->make_purchase_unit_data( $order );
        }

        $create_order_data = [
            'intent'              => 'CAPTURE',
            'payer'               => $this->get_shipping_address( $order, true ),
            'application_context' => [
                'return_url'          => $this->get_return_url( $order ),
                'cancel_url'          => $order->get_cancel_order_url_raw(),
                'brand_name'          => 'DOKAN',
                'user_action'         => 'PAY_NOW',
                'payment_method'      => [
                    'payer_selected'  => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
                ],
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
            ],
            'purchase_units'      => $purchase_units,
        ];

        $processor        = Processor::init();
        $create_order_url = $processor->create_order( $create_order_data );

        if ( is_wp_error( $create_order_url ) ) {
            $error_message = Helper::get_error_message( $create_order_url );
            wc_add_wp_error_notices( $error_message );
            Helper::log_paypal_error( $order->get_id(), $create_order_url, 'dpm_create_order' );

            return [
                'result'   => 'failure',
                'redirect' => false,
                'messages' => '<ul class="woocommerce-error" role="alert"><li>' . $error_message . '</li></ul>',
            ];
        }
        //store paypal debug id & create order id
        $order->update_meta_data( '_dokan_paypal_create_order_debug_id', $create_order_url['paypal_debug_id'] );
        $order->update_meta_data( '_dokan_paypal_order_id', $create_order_url['id'] );
        $order->update_meta_data( '_dokan_paypal_redirect_url', $create_order_url['links'][1]['href'] );
        $order->save_meta_data();

        return [
            'result'              => 'success',
            'id'                  => $order_id,
            'paypal_redirect_url' => $create_order_url['links'][1]['href'],
            'paypal_order_id'     => $create_order_url['id'],
            'redirect'            => $create_order_url['links'][1]['href'],
            'success_redirect'    => $this->get_return_url( $order ),
            'cancel_redirect'     => $order->get_cancel_order_url_raw(),
        ];
    }

    /**
     * Get the state to send to paypal
     *
     * @param string $cc
     * @param string $state
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return string
     */
    public function get_paypal_state( $cc, $state ) {
        if ( 'US' === $cc ) {
            return $state;
        }

        $states = WC()->countries->get_states( $cc );

        if ( isset( $states[ $state ] ) ) {
            return $states[ $state ];
        }

        return $state;
    }

    /**
     * Make purchase unit data
     *
     * @param \WC_Order $order
     *
     * @return array
     */
    public function make_purchase_unit_data( \WC_Order $order ) {
        $subtotal       = $order->get_subtotal();
        $tax_total      = $this->get_tax_amount( $order );
        $shipping_total = wc_format_decimal( $order->get_shipping_total(), 2 );

        $seller_id     = dokan_get_seller_id_by_order( $order->get_id() );
        $merchant_id   = Helper::get_seller_merchant_id( $seller_id );
        $platform_fee  = wc_format_decimal( dokan()->commission->get_earning_by_order( $order, 'admin' ), 2 );

        $product_items = $this->get_product_items( $order );

        //if tax fee recipient is 'admin' then it will added with platform fee
        if ( 'admin' === get_post_meta( $order->get_id(), 'tax_fee_recipient', true ) ) {
            $subtotal     += $tax_total;
            $tax_total    = 0.00;
        }

        //if shipping fee recipient is 'admin' then it will added with platform fee
        if ( 'admin' === get_post_meta( $order->get_id(), 'shipping_fee_recipient', true ) ) {
            $subtotal       += $shipping_total;
            $shipping_total = 0.00;
        }

        $purchase_units = [
            'reference_id'        => $order->get_order_key(),
            'amount'              => [
                'currency_code' => get_woocommerce_currency(),
                'value'         => wc_format_decimal( $order->get_total(), 2 ),
                'breakdown'     => [
                    'item_total'        => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => wc_format_decimal( $subtotal, 2 ),
                    ],
                    'tax_total'         => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => wc_format_decimal( $tax_total, 2 ),
                    ],
                    'shipping'          => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => wc_format_decimal( $shipping_total, 2 ),
                    ],
                    'handling'          => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => '0.00',
                    ],
                    'shipping_discount' => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => '0.00',
                    ],
                    'discount' => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => $order->get_total_discount(),
                    ],
                    'insurance'         => [
                        'currency_code' => get_woocommerce_currency(),
                        'value'         => '0.00',
                    ],
                ],
            ],
            'payee'               => [
                'merchant_id' => $merchant_id,
            ],
            'items'               => $product_items,
            'shipping'            => $this->get_shipping_address( $order ),
            'payment_instruction' => [
                'disbursement_mode' => 'INSTANT',
                'platform_fees'     => [
                    [
                        'amount' => [
                            'currency_code' => get_woocommerce_currency(),
                            'value'         => $platform_fee,
                        ],
                    ],
                ],
            ],
            'invoice_id'          => $order->get_parent_id() ? $order->get_parent_id() : $order->get_id(),
            'custom_id'           => $order->get_id(),
        ];

        return $purchase_units;
    }

    /**
     * Get shipping address as paypal format
     *
     * @param \WC_Order $order
     *
     * @param bool $payer
     *
     * @return array
     */
    public function get_shipping_address( \WC_Order $order, $payer = false ) {
        $address = [
            'address' => [
                'name'           => [
                    'given_name' => $order->get_billing_first_name(),
                    'surname'    => $order->get_billing_last_name(),
                ],
                'address_line_1' => $order->get_billing_address_1(),
                'address_line_2' => $order->get_billing_address_2(),
                'admin_area_2'   => $order->get_billing_city(),
                'admin_area_1'   => $order->get_billing_state(),
                'postal_code'    => $order->get_billing_postcode(),
                'country_code'   => $order->get_billing_country(),
            ],
        ];

        if ( $payer ) {
            $address['name'] = [
                'given_name' => $order->get_billing_first_name(),
                'surname'    => $order->get_billing_last_name(),
            ];
        }

        return $address;
    }

    /**
     * Get product items as PayPal format
     *
     * NB: PayPal check all the values with item qty and item value and match with the main order value.
     * So if the shipping and tax fee recipient is admin then
     * we are dividing the tax total and shipping total based on no of items in a order.
     *
     * @param $order
     *
     * @return array
     */
    public function get_product_items( \WC_Order $order ) {
        $items = [];

        $extra_fee      = 0;
        $tax_total      = $this->get_tax_amount( $order );
        $shipping_total = wc_format_decimal( $order->get_shipping_total(), 2 );

        $no_of_items = count( $order->get_items( 'line_item' ) );

        //if tax fee recipient is 'admin' then it will added with line item value.
        if ( 'admin' === get_post_meta( $order->get_id(), 'tax_fee_recipient', true ) ) {
            $extra_fee += ( $tax_total / $no_of_items );
        }

        //if shipping fee recipient is 'admin' then will added with line item value.
        if ( 'admin' === get_post_meta( $order->get_id(), 'shipping_fee_recipient', true ) ) {
            $extra_fee += ( $shipping_total / $no_of_items );
        }

        foreach ( $order->get_items( 'line_item' ) as $key => $line_item ) {
            $product  = wc_get_product( $line_item->get_product_id() );
            $category = $product->is_downloadable() || $product->is_virtual() ? 'DIGITAL_GOODS' : 'PHYSICAL_GOODS';

            //dividing the extra fee by the quantity
            $item_extra_fee = ( $extra_fee / $line_item->get_quantity() );

            //get single item price by quantity. sometimes there will be product warranty add-on
            $item_price = ( $line_item->get_subtotal() / $line_item->get_quantity() ) + $item_extra_fee;
            $item_price = wc_format_decimal( $item_price, 2 );

            $items[] = [
                'name'        => $line_item->get_name(),
                'sku'         => $product->get_sku(),
                'category'    => $category,
                'unit_amount' => [
                    'currency_code' => get_woocommerce_currency(),
                    'value'         => $item_price,
                ],
                'quantity'    => $line_item->get_quantity(),
            ];
        }

        return $items;
    }

    /**
     * Get tax amount
     *
     * @param $order
     *
     * @return int
     */
    public function get_tax_amount( \WC_Order $order ) {
        $tax_amount = 0;
        foreach ( $order->get_taxes() as $tax ) {
            $tax_amount += $tax['tax_total'] + $tax['shipping_tax_total'];
        }

        return wc_format_decimal( $tax_amount, 2 );
    }

    /**
     * Admin options with extra information
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function admin_options() {
        if ( $this->is_valid_for_use() ) {
            dokan_get_template( 'gateways/paypal/admin-options.php' );
            parent::admin_options();
        } else {
            ?>
            <div class="inline error">
                <p>
                    <strong><?php esc_html_e( 'Gateway disabled', 'dokan-lite' ); ?></strong>: <?php esc_html_e( 'Dokan PayPal Marketplace does not support your store currency.', 'dokan-lite' ); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Admin script
     *
     * @since DOKAN_SINCE_LITE
     *
     * @return void
     */
    public function admin_script() {
        ?>
        <script type="text/javascript">
            ;(function ($) {
                var inputToggle = function () {
                    let payment_id_prefix = 'woocommerce_<?php echo $this->id; ?>_';

                    let settings_input_ids = [
                        'app_user',
                        'app_pass'
                    ];

                    if ( $(`#${payment_id_prefix}test_mode`).is(':checked') ) {
                        settings_input_ids.map(function (id) {
                            $('#' + payment_id_prefix + 'test_' + id).closest('tr').show();
                            $('#' + payment_id_prefix + id).closest('tr').hide();
                        });
                    } else {
                        settings_input_ids.map(function (id) {
                            $('#' + payment_id_prefix + id).closest('tr').show();
                            $('#' + payment_id_prefix + 'test_' + id).closest('tr').hide();
                        });
                    }
                };
                inputToggle();

                let payment_id_prefix = 'woocommerce_<?php echo $this->id; ?>_';
                let test_mode = $(`#${payment_id_prefix}test_mode`);

                test_mode.on('change', function () {
                    inputToggle();
                });

            })(jQuery);
        </script>
        <?php
    }

    /**
     * Process admin options
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool|void
     */
    public function process_admin_options() {
        parent::process_admin_options();

        // delete token transient after settings is being updated
        delete_transient( '_dokan_paypal_marketplace_access_token' );
        delete_transient( '_dokan_paypal_marketplace_client_token' );

        //create webhook automatically
        $instance = dokan()->payment_gateway->paypal_marketplace->paypal_webhook;
        if ( Helper::is_enabled() ) {
            //if gateway is enabled, automatically create webhook for this site
            if ( $instance instanceof \WeDevs\Dokan\Gateways\PayPal\WebhookHandler ) {
                $instance->register_webhook();
            }
        } else {
            //if gateway is disabled, delete created webhook for this site
            if ( $instance instanceof \WeDevs\Dokan\Gateways\PayPal\WebhookHandler ) {
                $instance->deregister_webhook();
            }
        }

        /*
         * hack applied to process vendor subscription payment,
         * from kapil bhai: Amra method e seller er id and details dei. Tai admin er tao evabe rakha
         */
        //todo: remove below lines from here
        //update_user_meta( dokan_get_current_user_id(), '_dokan_paypal_merchant_id', $this->get_option( 'partner_id' ) );
        //update_user_meta( dokan_get_current_user_id(), '_dokan_paypal_enable_for_receive_payment', true );
    }

    /**
     * Check if this payment method is available with conditions
     * This payment method is only available if the 2 scenario passed which is mentioned below
     *
     * Eg 1: if there is multi-vendor in a cart and one vendor is able to receive payment and another vendor is not,
     * We will not show the payment method in Checkout
     *
     * Eg 2: if there is multi-vendor/single-vendor in a cart and both cannot receive payment via paypal,
     * Then the payment method will not show in Checkout
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return bool
     */
    public function is_available() {
        $is_available = parent::is_available();

        if ( ! $is_available ) {
            return false;
        }

        //we are returning true for admin. otherwise admin will get error on the dashboard.
        if ( is_admin() ) {
            return true;
        }

        // check if admin provided all the api information right
        if ( ! Helper::is_ready() ) {
            return false;
        }

        //paypal does not allow if there are more than 10 products in the cart
        if ( is_object( WC()->cart ) && count( WC()->cart->get_cart() ) > 10 ) {
            return false;
        } elseif ( WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $item ) {
                $product_id = $item['data']->get_id();
                $seller_id  = get_post_field( 'post_author', $product_id );

                // check if cart item is a vendor subscription product
                if ( Helper::is_vendor_subscription_product( $product_id ) ) {
                    continue;
                }

                if ( ! Helper::is_seller_enable_for_receive_payment( $seller_id ) ) {
                    return false;
                }
            }
        }

        if ( is_checkout_pay_page() ) {
            global $wp;

            //get order id if this is a order review page
            $order_id = isset( $wp->query_vars['order-pay'] ) ? $wp->query_vars['order-pay'] : null;

            $order = wc_get_order( $order_id );

            //return if this is not an order object
            if ( ! is_object( $order ) ) {
                return false;
            }

            //paypal does not allow if there is more than 10 products in the order
            if ( count( $order->get_items( 'line_item' ) ) > 10 ) {
                return false;
            }

            // check cart item has vendor subscription product.
            if ( Helper::is_vendor_subscription_order( $order ) ) {
                return true;
            }

            foreach ( $order->get_items( 'line_item' ) as $key => $line_item ) {
                $seller_id = get_post_field( 'post_author', $line_item->get_product_id() );

                if ( ! Helper::is_seller_enable_for_receive_payment( $seller_id ) ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Return whether or not this gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true a
     * redirect will occur to the settings page instead.
     *
     * @since DOKAN_LITE_SINCE
     * @return bool
     */
    public function needs_setup() {
        if (
            empty( Helper::get_partner_id() ) ||
            empty( Helper::get_client_id() ) ||
            empty( Helper::get_client_secret() ) ) {
            return true;
        }

        return false;
    }
}

