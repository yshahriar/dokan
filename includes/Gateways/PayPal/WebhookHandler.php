<?php

namespace WeDevs\Dokan\Gateways\PayPal;

use WeDevs\Dokan\Gateways\PayPal\Factories\EventFactory;
use WeDevs\Dokan\Gateways\PayPal\Utilities\Processor;

/**
 * Class WebhookHandler
 * @package WeDevs\Dokan\Gateways\PayPal
 *
 * @since DOKAN_LITE_SINCE
 *
 * @author weDevs
 */
class WebhookHandler {

    /**
     * WebhookHandler constructor.
     *
     * @since DOKAN_LITE_SINCE
     */
    public function __construct() {
        $this->hooks();
    }

    /**
     * Init all the hooks
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     */
    public function hooks() {
        add_action( 'woocommerce_api_dokan-paypal', [ $this, 'handle_events' ] );
    }

    /**
     * Handle events which are coming from PayPal
     *
     * @since DOKAN_LITE_SINCE
     *
     * @return void
     * @throws \WeDevs\Dokan\Exceptions\DokanException
     */
    public function handle_events() {
        //if the gateway is disabled then we are not processing further execution
        if ( ! Helper::is_ready() ) {
            exit;
        }

        $request_body = file_get_contents( 'php://input' );
        $event        = json_decode( $request_body );

        if ( ! $event ) {
            return;
        }

        dokan_log( "[Dokan PayPal Marketplace] Webhook request body:\n" . print_r( $event, true ) );

        EventFactory::handle( $event );

        wp_send_json_success(
            [
				'status'  => 200,
				'message' => 'success',
			]
        );
    }

    /**
     * Register webhook and remove old webhook endpoints from PayPal
     *
     * @since 3.0.3
     * @since 3.2.0 updated register webhook logic
     *
     * @return bool
     */
    public function register_webhook() {
        if ( ! Helper::is_api_ready() ) {
            return false;
        }

        $processor = Processor::init();
        $response  = $processor->get_webhooks();

        if ( is_wp_error( $response ) ) {
            dokan_log( 'Dokan PayPal Marketplace listing webhook error: ' . Helper::get_error_message( $response ) );
            return false;
        }

        $hooks = wp_list_pluck( $response, 'url', 'id' );

        // check webhook already exists or not
        $existing_web_hook_id = get_option( Helper::get_webhook_key(), '' );
        if ( ! empty( $existing_web_hook_id ) && array_key_exists( $existing_web_hook_id, $hooks ) ) {
            return true;
        }

        $site_url = str_replace( [ 'http://', 'https://' ], '', home_url( '/' ) );
        foreach ( $hooks as $hook_id => $hook_url ) {
            // remove all dokan webhooks for current site
            if ( false !== strpos( $hook_url, $site_url . 'wc-api/dokan-paypal' ) ) {
                $processor->delete_webhook( $hook_id );
            }
        }

        // create required webhook
        $events     = Helper::get_webhook_events_for_notification();
        $response   = $processor->create_webhook( home_url( 'wc-api/dokan-paypal', 'https' ), $events );
        if ( is_wp_error( $response ) ) {
            dokan_log( 'Could not create webhook automatically: ' . print_r( $response, true ) );
        }

        //store this webhook to database
        update_option( Helper::get_webhook_key(), $response['id'] );

        return true;
    }

    /**
     * Delete webhook on PayPal end
     *
     * @since 3.2.0
     *
     * @return bool
     */
    public function deregister_webhook() {
        if ( ! Helper::is_api_ready() ) {
            return false;
        }

        $processor = Processor::init();
        $response  = $processor->get_webhooks();
        if ( is_wp_error( $response ) ) {
            dokan_log( 'Dokan PayPal Marketplace listing webhook error: ' . print_r( $response, true ) );
            return false;
        }

        $hooks      = wp_list_pluck( $response, 'url', 'id' );
        $site_url   = str_replace( [ 'http://', 'https://' ], '', home_url( '/' ) );
        foreach ( $hooks as $hook_id => $hook_url ) {
            // remove all dokan webhooks for current site
            if ( false !== strpos( $hook_url, $site_url . 'wc-api/dokan-paypal' ) ) {
                $processor->delete_webhook( $hook_id );
            }
        }

        // delete database reference
        delete_option( Helper::get_webhook_key() );

        return true;
    }
}
