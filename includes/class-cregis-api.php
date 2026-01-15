<?php
/**
 * Cregis API Client
 *
 * @package Cregis_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cregis_API {
    
    private $api_url;
    private $api_key;
    private $pid;
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct($api_url, $api_key, $pid) {
        $this->api_url = trailingslashit($api_url);
        $this->api_key = sanitize_text_field($api_key);
        $this->pid = absint($pid);
        $this->logger = new Cregis_Logger();
    }
    
    /**
     * Generate signature for request
     */
    private function generate_signature($params) {
        $sign_params = $params;
        
        unset($sign_params['sign']);
        
        $sign_params = array_filter($sign_params, function($value) {
            return $value !== null && $value !== '';
        });
        
        ksort($sign_params);
        
        $sign_string = $this->api_key;
        foreach ($sign_params as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = wp_json_encode($value);
            }
            $sign_string .= $key . $value;
        }
        
        return md5($sign_string);
    }
    
    /**
     * Generate random nonce
     */
    private function generate_nonce($length = 6) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $chars[wp_rand(0, strlen($chars) - 1)];
        }
        return $nonce;
    }
    
    /**
     * Create payment order
     */
    public function create_payment($order_id, $order_data) {
        $endpoint = 'api/v2/checkout';
        
        $params = array(
            'pid' => $this->pid,
            'order_id' => sanitize_text_field($order_id),
            'order_amount' => sanitize_text_field($order_data['amount']),
            'order_currency' => sanitize_text_field($order_data['currency']),
            'payer_id' => sanitize_text_field($order_data['payer_id']),
            'payer_name' => sanitize_text_field($order_data['payer_name']),
            'payer_email' => sanitize_email($order_data['payer_email']),
            'valid_time' => absint($order_data['valid_time']),
            'callback_url' => esc_url_raw($order_data['callback_url']),
            'success_url' => esc_url_raw($order_data['success_url']),
            'cancel_url' => esc_url_raw($order_data['cancel_url']),
            'nonce' => $this->generate_nonce(),
            'timestamp' => intval(microtime(true) * 1000),
        );
        
        if (!empty($order_data['remark'])) {
            $params['remark'] = sanitize_text_field($order_data['remark']);
        }
        
        if (!empty($order_data['tokens'])) {
            $params['tokens'] = wp_json_encode($order_data['tokens']);
        }
        
        if (!empty($order_data['language'])) {
            $params['language'] = sanitize_text_field($order_data['language']);
        }
        
        if (!empty($order_data['order_details'])) {
            $params['order_details'] = wp_json_encode($order_data['order_details']);
        }
        
        if (isset($order_data['accept_partial_payment'])) {
            $params['accept_partial_payment'] = $order_data['accept_partial_payment'] ? 'true' : 'false';
        }
        
        if (isset($order_data['accept_over_payment'])) {
            $params['accept_over_payment'] = $order_data['accept_over_payment'] ? 'true' : 'false';
        }
        
        $params['sign'] = $this->generate_signature($params);
        
        $this->logger->log('Creating payment order', array(
            'order_id' => $order_id,
            'params' => $params
        ));
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Query payment order
     */
    public function query_payment($cregis_id = null, $order_id = null) {
        $endpoint = 'api/v2/checkout/query';
        
        $params = array(
            'pid' => $this->pid,
            'nonce' => $this->generate_nonce(),
            'timestamp' => intval(microtime(true) * 1000),
        );
        
        if ($cregis_id) {
            $params['cregis_id'] = sanitize_text_field($cregis_id);
        }
        
        if ($order_id) {
            $params['order_id'] = sanitize_text_field($order_id);
        }
        
        $params['sign'] = $this->generate_signature($params);
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Verify webhook signature
     */
    public function verify_webhook_signature($data) {
        if (!isset($data['sign'])) {
            return false;
        }
        
        $received_sign = $data['sign'];
        $calculated_sign = $this->generate_signature($data);
        
        return hash_equals($calculated_sign, $received_sign);
    }
    
    /**
     * Make API request
     */
    private function make_request($endpoint, $params, $method = 'POST') {
        $url = $this->api_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($params),
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->log('API request failed', array(
                'error' => $response->get_error_message(),
                'endpoint' => $endpoint
            ), 'error');
            
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        $data = json_decode($body, true);
        
        if ($http_code !== 200 || empty($data)) {
            $this->logger->log('API response error', array(
                'http_code' => $http_code,
                'body' => $body,
                'endpoint' => $endpoint
            ), 'error');
            
            return array(
                'success' => false,
                'error' => 'Invalid API response'
            );
        }
        
        if (isset($data['code']) && $data['code'] !== '00000') {
            $this->logger->log('API error response', array(
                'code' => $data['code'],
                'message' => $data['msg'] ?? 'Unknown error',
                'endpoint' => $endpoint
            ), 'error');
            
            return array(
                'success' => false,
                'error' => $data['msg'] ?? 'API error',
                'code' => $data['code']
            );
        }
        
        return array(
            'success' => true,
            'data' => $data['data'] ?? array()
        );
    }
}