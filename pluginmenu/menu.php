<?php
/**
 * Menu Version 3.0
 */

// Add admin menu
if ( ! function_exists( 'skynet_add_plugin_menu' ) ) {
    function skynet_add_plugin_menu() {
        add_submenu_page(
            'options-general.php',
            'SkynetAccessibility Scanner Setting',
            'SkynetAccessibility Scanner',
            'manage_options',
            'skynet-scanneraskynettechnologies',
            'skynet_display_plugin_menu'
        );
    }
    add_action( 'admin_menu', 'skynet_add_plugin_menu' );
}

// Display settings page
if ( ! function_exists( 'skynet_display_plugin_menu' ) ) {
    function skynet_display_plugin_menu() {
        echo '<div class="wrap"><h1>SkynetAccessibility Scanner Settings</h1></div>';
    }
}

/**
 * Create plugin menu on installation.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'skynet_create_plugin_menu_scanner' ) ) {
    function skynet_create_plugin_menu_scanner() {
        skynet_populate_plugin_menu_scanner();
    }
}

/**
 * Populate table on installation.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'skynet_populate_plugin_menu_scanner' ) ) {
    function skynet_populate_plugin_menu_scanner() {

        $plugins = array(
            'SkynetAccessibility Scanner'           => array(
                'plugin_link' => 'skynetaccessibilityscanner/skynettechnologies-skynetaccessibilityscanner.php',
                'admin_URL'   => 'admin.php?page=skynet-scanner',
                'dev_URL'     => 'https://skynettechnologies.com/skynetaccessibilityscanner/',
                'retired'     => 0,
                'updated'     => '2025-29-05',
            )
        );

        $plugin_menu = get_option( 'skynet-plugin-menu' );

        foreach ( $plugins as $plugin_name => $plugin_details ) {
            if ( isset( $plugin_menu[ $plugin_name ] ) ) {
                if ( strtotime( $plugin_menu[ $plugin_name ]['updated'] ) <= strtotime( $plugin_details['updated'] ) ) {
                    $plugin_menu[ $plugin_name ] = $plugin_details;
                }
            } else {
                $plugin_menu[ $plugin_name ] = $plugin_details;
            }
        }

        ksort( $plugin_menu );

        update_option( 'skynet-plugin-menu', $plugin_menu );
    }
}
