<?php
/**
 * Plugin Name: Cregis Payment Gateway for WooCommerce
 * Plugin URI: https://www.cregis.com
 * Description: Accept cryptocurrency payments via Cregis Payment Engine. Supports both classic and block-based checkout.
 * Version: 1.0.0
 * Author: Cregis
 * Author URI: https://www.cregis.com
 * Text Domain: cregis-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CREGIS_WC_VERSION', '1.0.0');
define('CREGIS_WC_PLUGIN_FILE', __FILE__);
define('CREGIS_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CREGIS_WC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Initialize the payment gateway
 */
function cregis_wc_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once CREGIS_WC_PLUGIN_DIR . 'includes/class-cregis-compatibility.php';
    require_once CREGIS_WC_PLUGIN_DIR . 'includes/class-cregis-api.php';
    require_once CREGIS_WC_PLUGIN_DIR . 'includes/class-cregis-gateway.php';
    require_once CREGIS_WC_PLUGIN_DIR . 'includes/class-cregis-webhook.php';
    require_once CREGIS_WC_PLUGIN_DIR . 'includes/class-cregis-logger.php';
    
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once CREGIS_WC_PLUGIN_DIR . 'includes/class-cregis-blocks-support.php';
    }
}
add_action('plugins_loaded', 'cregis_wc_init', 11);

/**
 * Add the gateway to WooCommerce
 */
function cregis_wc_add_gateway($gateways) {
    $gateways[] = 'WC_Gateway_Cregis';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'cregis_wc_add_gateway');

/**
 * Register Blocks integration
 */
function cregis_wc_register_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function($payment_method_registry) {
                $payment_method_registry->register(new WC_Gateway_Cregis_Blocks_Support());
            }
        );
    }
}
add_action('woocommerce_blocks_loaded', 'cregis_wc_register_blocks_support');

/**
 * Declare compatibility with WooCommerce features
 */
function cregis_wc_declare_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'cregis_wc_declare_compatibility');

/**
 * Plugin activation hook
 */
function cregis_wc_activate() {
    if (!get_option('cregis_wc_version')) {
        update_option('cregis_wc_version', CREGIS_WC_VERSION);
    }
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'cregis_wc_activate');

/**
 * Plugin deactivation hook
 */
function cregis_wc_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'cregis_wc_deactivate');

/**
 * Add action links on plugins page
 */
function cregis_wc_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=cregis') . '">' . 
                     esc_html__('Settings', 'cregis-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cregis_wc_plugin_action_links');

/**
 * Load plugin textdomain
 */
function cregis_wc_load_textdomain() {
    load_plugin_textdomain('cregis-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cregis_wc_load_textdomain');