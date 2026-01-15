<?php
/**
 * Cregis Logger
 *
 * @package Cregis_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cregis_Logger {
    
    private $logger;
    private $debug_mode;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = wc_get_logger();
        $settings = get_option('woocommerce_cregis_settings', array());
        $this->debug_mode = isset($settings['debug_mode']) && $settings['debug_mode'] === 'yes';
    }
    
    /**
     * Log message
     */
    public function log($message, $data = array(), $level = 'info') {
        if (!$this->debug_mode && $level === 'info') {
            return;
        }
        
        $context = array('source' => 'cregis-woocommerce');
        
        $log_message = $message;
        if (!empty($data)) {
            $log_message .= ' | ' . wp_json_encode($data);
        }
        
        switch ($level) {
            case 'error':
                $this->logger->error($log_message, $context);
                break;
            case 'warning':
                $this->logger->warning($log_message, $context);
                break;
            case 'debug':
                $this->logger->debug($log_message, $context);
                break;
            default:
                $this->logger->info($log_message, $context);
        }
    }
}