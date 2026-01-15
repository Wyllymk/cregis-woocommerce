<?php
/**
 * Cregis Blocks Support
 *
 * @package Cregis_WooCommerce
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

final class WC_Gateway_Cregis_Blocks_Support extends AbstractPaymentMethodType {
    
    private $gateway;
    protected $name = 'cregis';
    
    /**
     * Initialize
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_cregis_settings', array());
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways['cregis'] ?? null;
    }
    
    /**
     * Check if payment method is active
     */
    public function is_active() {
        return !empty($this->gateway) && $this->gateway->is_available();
    }
    
    /**
     * Get payment method script handles
     */
    public function get_payment_method_script_handles() {
        $script_path = '/assets/js/blocks.js';
        $script_url = CREGIS_WC_PLUGIN_URL . $script_path;
        $script_asset_path = CREGIS_WC_PLUGIN_DIR . '/assets/js/blocks.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version' => CREGIS_WC_VERSION
            );
        
        wp_register_script(
            'wc-cregis-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
        
        return array('wc-cregis-blocks');
    }
    
    /**
     * Get payment method data
     */
    public function get_payment_method_data() {
        return array(
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => $this->get_supported_features(),
            'icon' => '',
        );
    }
    
    /**
     * Get supported features
     */
    private function get_supported_features() {
        return array(
            'products',
        );
    }
}