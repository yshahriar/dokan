<?php
namespace WeDevs\Dokan\Dashboard\Templates;

use WeDevs\Dokan\ReverseWithdrawal\Helper;
use WeDevs\Dokan\ReverseWithdrawal\Manager as ReverseWithdrawalManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ReverseWithdrawal {
    /**
     * @var string[] $trn_date
     */
    protected $transaction_date = [
        'from' => '',
        'to'   => ''
    ];

    /**
     * Admin constructor.
     */
    public function __construct() {
        //enqueue required scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 10, 1 );
        add_filter( 'dokan_frontend_localize_script', [ $this, 'localized_scripts' ], 10, 1 );

        add_action( 'dokan_reverse_withdrawal_content_area_header', [ $this, 'render_header' ] );
        add_action( 'dokan_reverse_withdrawal_content', [ $this, 'load_balance_section' ], 10 );
        add_action( 'dokan_reverse_withdrawal_content', [ $this, 'load_filter_section' ], 10 );
        add_action( 'dokan_reverse_withdrawal_content', [ $this, 'load_transactions_table' ], 10 );
    }

    /**
     * Load payment section
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function load_balance_section() {
        $manager = new ReverseWithdrawalManager();
        $balance = $manager->get_store_balance( [
            'vendor_id' => dokan_get_current_user_id(),
        ] );

        if ( is_wp_error( $balance ) ) {
            // print error message and return
            return;
        }

        $args = [
            'balance'   => $balance,
            'threshold' => Helper::get_reverse_balance_limit(),
        ];
        dokan_get_template_part( 'reverse-withdrawal/reverse-balance', '', $args );
    }

    /**
     * Dokan Reverse Withdrawal header Template render
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function render_header() {
        dokan_get_template_part( 'reverse-withdrawal/header' );
    }

    /**
     * Dokan Reverse Withdrawal header Template render
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function load_filter_section() {
        dokan_get_template_part( 'reverse-withdrawal/filters', '', [ 'trn_date' => $this->get_transaction_date() ] );
    }

    /**
     * Dokan Reverse Withdrawal header Template render
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function load_transactions_table() {
        $manager = new ReverseWithdrawalManager();

        $transactions = $manager->get_store_transactions( [
            'vendor_id' => dokan_get_current_user_id(),
            'trn_date'  => $this->get_transaction_date(),
            'orderby'   => 'added',
            'order'     => 'DESC',
        ] );

        dokan_get_template_part( 'reverse-withdrawal/transaction-listing', '', [ 'transactions' => $transactions ] );
    }

    /**
     * Enqueue Frontend Scripts
     *
     * @param string $hook
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function wp_enqueue_scripts( $hook ) {
        global $wp;

        if ( ! dokan_is_seller_dashboard() || ! isset( $wp->query_vars['reverse-withdrawal'] ) ) {
            return;
        }

        // load frontend scripts
        wp_enqueue_script( 'dokan-reverse-withdrawal' );
        wp_enqueue_style( 'dokan-reverse-withdrawal' );
        wp_enqueue_style( 'dokan-date-range-picker');
    }

    /**
     * Localize Reverse Withdrawal Scripts
     *
     * @since DOKAN_SINCE
     *
     * @param array $localize_script
     *
     * @return array
     */
    public function localized_scripts( $localize_script ) {
        $localize_script['reverse_withdrawal'] = [
            'reverse_pay_msg'  => esc_attr__( 'Are you sure you want to pay this amount?', 'dokan-lite' ),
            'nonce'            => wp_create_nonce( 'dokan_reverse_withdrawal_payment' ),
            'on_error_title'   => esc_html__( 'Something went wrong.', 'dokan-lite' ),
            'on_success_title' => esc_html__( 'Success.', 'dokan-lite' ),
            'checkout_url'     => wc_get_checkout_url(),
        ];

        return $localize_script;
    }

    /**
     * Get transaction date
     *
     * @since DOKAN_SINCE
     *
     * @return string[]
     */
    protected function get_transaction_date() {
        // get default transaction date
        $this->transaction_date['from'] = dokan_current_datetime()->modify( '-1 month' )->format( 'Y-m-d' );
        $this->transaction_date['to']   = dokan_current_datetime()->format( 'Y-m-d' );

        if ( isset( $_GET['_nonce'] ) && wp_verify_nonce( $_GET['_nonce'], 'dokan_reverse_withdrawal_filter' ) ) {
            if ( ! empty( $_GET['trn_date']['from'] ) ) {
                $this->transaction_date['from'] = sanitize_text_field( wp_unslash( $_GET['trn_date']['from'] ) );
            }

            if ( ! empty( $_GET['trn_date']['to'] ) ) {
                $this->transaction_date['to'] = sanitize_text_field( wp_unslash( $_GET['trn_date']['to'] ) );
            }
        }

        return $this->transaction_date;
    }
}
