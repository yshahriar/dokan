<?php
namespace WeDevs\Dokan\ReverseWithdrawal;

use WeDevs\Dokan\ReverseWithdrawal\Admin\Settings;
use WeDevs\Dokan\Traits\ChainableContainer;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Reverse Withdrawal Class
 *
 * This class will be the base class for the reverse withdrawal feature
 *
 * @since DOKAN_SINCE
 */
class ReverseWithdrawal {

    use ChainableContainer;

    /**
     * Cloning is forbidden.
     *
     * @since DOKAN_SINCE
     */
    public function __clone() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Cloning is forbidden.', 'dokan-lite' ), DOKAN_PLUGIN_VERSION );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since DOKAN_SINCE
     */
    public function __wakeup() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Unserializing instances of this class is forbidden.', 'dokan-lite' ), DOKAN_PLUGIN_VERSION );
    }

    /**
     * Class constructor
     *
     * @since DOKAN_SINCE
     */
    public function __construct() {
        $this->set_controllers();
        $this->init_hooks();
    }

    private function set_controllers() {
        $this->container['settings']      = new Settings();
        $this->container['hooks']         = new Hooks();
        $this->container['admin_hooks']   = new Admin\Hooks();

        if ( wp_doing_ajax() ) {
            //$this->container['ajax'] = new Ajax();
        }

        if ( ! is_admin() ) {
            // load frontend specific files
        }
    }

    private function init_hooks() {
        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] ); // include rest api class
    }

    /**
     * Rest api class map
     *
     * @param array $classes
     *
     * @since DOKAN_SINCE
     *
     * @return array
     */
    public function rest_api_class_map( $classes ) {
        $class[ DOKAN_DIR . '/includes/REST/ReverseWithdrawalController.php' ] = '\WeDevs\Dokan\REST\ReverseWithdrawalController';

        return array_merge( $classes, $class );
    }
}