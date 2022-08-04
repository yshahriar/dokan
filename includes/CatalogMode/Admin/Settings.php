<?php

namespace WeDevs\Dokan\CatalogMode\Admin;

/**
 * Class Hooks
 *
 * This class will be responsible for admin settings of Catalog Mode feature
 *
 * @since   DOKAN_SINCE
 *
 * @package WeDevs\Dokan\CatalogMode\Admin
 */
class Settings {
    /**
     * Class constructor
     *
     * @since DOKAN_SINCE
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_selling_options', [ $this, 'admin_settings' ], 10, 1 );
    }

    /**
     * This method will register catalog mode settings section under Selling Options settings section
     *
     * @since DOKAN_SINCE
     *
     * @param array $selling_options
     *
     * @return array
     */
    public function admin_settings( $selling_options ) {
        $catalog_mode_settings = [
            'catalog_mode_settings'                => [
                'name'          => 'catalog_mode_settings',
                'label'         => __( 'Product Catalog Mode', 'dokan' ),
                'type'          => 'sub_section',
                'description'   => __( 'Control Catalog Mode Settings For Your Vendors', 'dokan' ),
                'content_class' => 'sub-section-styles',
            ],
            'catalog_mode_hide_add_to_cart_button' => [
                'name'    => 'catalog_mode_hide_add_to_cart_button',
                'label'   => __( 'Remove Add to Cart Button', 'dokan' ),
                'desc'    => __( 'Check to remove Add to Cart option.', 'dokan' ),
                'type'    => 'switcher',
                'default' => 'off',
            ],
            'catalog_mode_hide_product_price'      => [
                'name'    => 'catalog_mode_hide_product_price',
                'label'   => __( 'Hide Product Price', 'dokan' ),
                'desc'    => __( 'Check to hide product price.', 'dokan' ),
                'type'    => 'switcher',
                'default' => 'off',
                'show_if' => [
                    'catalog_mode_hide_add_to_cart_button' => [
                        'equal' => 'on',
                    ],
                ],
            ],
        ];

        return array_merge( $selling_options, $catalog_mode_settings );
    }
}
