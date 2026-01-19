<?php
/**
 * WooCommerce Compatibility Handler
 *
 * @package Cregis_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cregis_WC_Compatibility {
    
    /**
     * Initialize compatibility features
     */
    public static function init() {
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', array(__CLASS__, 'handle_custom_query_var'), 10, 2);
    }
    
    /**
     * Check if HPOS is enabled
     */
    public static function is_hpos_enabled() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
    
    /**
     * Handle custom query variables
     */
    public static function handle_custom_query_var($query, $query_vars) {
        if (!empty($query_vars['cregis_id'])) {
            $query['meta_query'][] = array(
                'key' => '_cregis_id',
                'value' => esc_attr($query_vars['cregis_id']),
            );
        }
        
        return $query;
    }
    
    /**
     * Get orders by Cregis ID
     */
    public static function get_orders_by_cregis_id($cregis_id) {
        return wc_get_orders(array(
            'limit' => 1,
            'cregis_id' => $cregis_id,
            'return' => 'objects',
        ));
    }
    
    /**
     * Update order meta with HPOS compatibility
     */
    public static function update_order_meta($order, $meta_key, $meta_value) {
        if (!$order instanceof WC_Order) {
            return false;
        }
        
        $order->update_meta_data($meta_key, $meta_value);
        $order->save();
        
        return true;
    }
    
    /**
     * Get order meta with HPOS compatibility
     */
    public static function get_order_meta($order, $meta_key, $single = true) {
        if (!$order instanceof WC_Order) {
            return '';
        }
        
        return $order->get_meta($meta_key, $single);
    }
    
    /**
     * Delete order meta with HPOS compatibility
     */
    public static function delete_order_meta($order, $meta_key) {
        if (!$order instanceof WC_Order) {
            return false;
        }
        
        $order->delete_meta_data($meta_key);
        $order->save();
        
        return true;
    }
}

Cregis_WC_Compatibility::init();