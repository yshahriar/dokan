<?php

function dokan_update_fee_settings_274() {
    
    $commission_recipient = dokan_get_option( 'extra_fee_recipient', 'dokan_general', 'seller' );
    $general_option       = get_option( 'dokan_general' );
    
    $general_option['shipping_fee_recipient'] = $commission_recipient;
    $general_option['tax_fee_recipient']      = $commission_recipient;
    
    update_option( 'dokan_general', $general_option );
}

dokan_update_fee_settings_274();