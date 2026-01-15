<?php
/**
 * Cregis Webhook Handler
 *
 * @package Cregis_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cregis_Webhook {
    
    private $api;
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct($api_url, $api_key, $pid) {
        $this->api = new Cregis_API($api_url, $api_key, $pid);
        $this->logger = new Cregis_Logger();
    }
    
    /**
     * Handle webhook request
     */
    public function handle() {
        $raw_post = file_get_contents('php://input');
        
        if (empty($raw_post)) {
            $this->logger->log('Empty webhook payload received', array(), 'error');
            status_header(400);
            die('Invalid payload');
        }
        
        $data = json_decode($raw_post, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->log('Invalid JSON in webhook', array('error' => json_last_error_msg()), 'error');
            status_header(400);
            die('Invalid JSON');
        }
        
        $this->logger->log('Webhook received', array(
            'event_name' => $data['event_name'] ?? 'unknown',
            'event_type' => $data['event_type'] ?? 'unknown',
            'data' => $data
        ));
        
        if (!$this->api->verify_webhook_signature($data)) {
            $this->logger->log('Invalid webhook signature', array(), 'error');
            status_header(401);
            die('Invalid signature');
        }
        
        try {
            $event_name = sanitize_text_field($data['event_name'] ?? '');
            $event_type = sanitize_text_field($data['event_type'] ?? '');
            $order_data = $data['data'] ?? array();
            
            if ($event_name !== 'order') {
                status_header(200);
                die('success');
            }
            
            switch ($event_type) {
                case 'paid':
                    $this->handle_paid($order_data);
                    break;
                    
                case 'paid_partial':
                    $this->handle_partial_paid($order_data);
                    break;
                    
                case 'paid_over':
                    $this->handle_overpaid($order_data);
                    break;
                    
                case 'expired':
                    $this->handle_expired($order_data);
                    break;
                    
                case 'refunded':
                    $this->handle_refunded($order_data);
                    break;
                    
                case 'paid_remain':
                    $this->handle_additional_payment($order_data);
                    break;
                    
                default:
                    $this->logger->log('Unknown event type', array('event_type' => $event_type));
            }
            
            status_header(200);
            die('success');
            
        } catch (Exception $e) {
            $this->logger->log('Webhook processing error', array('error' => $e->getMessage()), 'error');
            status_header(500);
            die('Processing error');
        }
    }
    
    /**
     * Handle paid event
     */
    private function handle_paid($data) {
        $order = $this->get_order_by_order_id($data['order_id']);
        
        if (!$order) {
            throw new Exception('Order not found: ' . $data['order_id']);
        }
        
        if ($order->is_paid()) {
            $this->logger->log('Order already paid', array('order_id' => $order->get_id()));
            return;
        }
        
        $order->update_meta_data('_cregis_transaction_hash', sanitize_text_field($data['tx_id']));
        $order->update_meta_data('_cregis_pay_amount', sanitize_text_field($data['pay_amount']));
        $order->update_meta_data('_cregis_pay_currency', sanitize_text_field($data['pay_currency']));
        $order->update_meta_data('_cregis_payment_address', sanitize_text_field($data['payment_address']));
        $order->update_meta_data('_cregis_transact_time', sanitize_text_field($data['transact_time']));
        $order->save();
        
        $order->payment_complete($data['tx_id']);
        
        $order->add_order_note(
            sprintf(
                __('Cryptocurrency payment received. Transaction hash: %s', 'cregis-woocommerce'),
                $data['tx_id']
            )
        );
        
        $this->logger->log('Payment completed', array(
            'order_id' => $order->get_id(),
            'tx_id' => $data['tx_id']
        ));
    }
    
    /**
     * Handle partial paid event
     */
    private function handle_partial_paid($data) {
        $order = $this->get_order_by_order_id($data['order_id']);
        
        if (!$order) {
            throw new Exception('Order not found: ' . $data['order_id']);
        }
        
        $order->update_meta_data('_cregis_status', 'partial_paid');
        $order->update_meta_data('_cregis_transaction_hash', sanitize_text_field($data['tx_id']));
        $order->update_meta_data('_cregis_pay_amount', sanitize_text_field($data['pay_amount']));
        $order->update_meta_data('_cregis_pay_currency', sanitize_text_field($data['pay_currency']));
        $order->save();
        
        $order->update_status('on-hold', __('Partial cryptocurrency payment received', 'cregis-woocommerce'));
        
        $order->add_order_note(
            sprintf(
                __('Partial payment received: %s %s. Transaction hash: %s', 'cregis-woocommerce'),
                $data['pay_amount'],
                $data['pay_currency'],
                $data['tx_id']
            )
        );
        
        $this->logger->log('Partial payment received', array(
            'order_id' => $order->get_id(),
            'amount' => $data['pay_amount']
        ));
    }
    
    /**
     * Handle overpaid event
     */
    private function handle_overpaid($data) {
        $order = $this->get_order_by_order_id($data['order_id']);
        
        if (!$order) {
            throw new Exception('Order not found: ' . $data['order_id']);
        }
        
        $order->update_meta_data('_cregis_status', 'overpaid');
        $order->update_meta_data('_cregis_transaction_hash', sanitize_text_field($data['tx_id']));
        $order->update_meta_data('_cregis_pay_amount', sanitize_text_field($data['pay_amount']));
        $order->update_meta_data('_cregis_pay_currency', sanitize_text_field($data['pay_currency']));
        $order->save();
        
        $order->payment_complete($data['tx_id']);
        
        $order->add_order_note(
            sprintf(
                __('Overpayment received: %s %s. Transaction hash: %s', 'cregis-woocommerce'),
                $data['pay_amount'],
                $data['pay_currency'],
                $data['tx_id']
            )
        );
        
        $this->logger->log('Overpayment received', array(
            'order_id' => $order->get_id(),
            'amount' => $data['pay_amount']
        ));
    }
    
    /**
     * Handle expired event
     */
    private function handle_expired($data) {
        $order = $this->get_order_by_order_id($data['order_id']);
        
        if (!$order) {
            throw new Exception('Order not found: ' . $data['order_id']);
        }
        
        if ($order->is_paid()) {
            return;
        }
        
        $order->update_status('cancelled', __('Cryptocurrency payment expired', 'cregis-woocommerce'));
        
        $order->add_order_note(__('Payment order expired without payment', 'cregis-woocommerce'));
        
        $this->logger->log('Payment expired', array('order_id' => $order->get_id()));
    }
    
    /**
     * Handle refunded event
     */
    private function handle_refunded($data) {
        $order = $this->get_order_by_order_id($data['order_id']);
        
        if (!$order) {
            throw new Exception('Order not found: ' . $data['order_id']);
        }
        
        $order->update_meta_data('_cregis_refund_id', sanitize_text_field($data['refund_id']));
        $order->update_meta_data('_cregis_refund_amount', sanitize_text_field($data['refund_amount']));
        $order->update_meta_data('_cregis_refund_tx_id', sanitize_text_field($data['refund_tx_id']));
        $order->save();
        
        $order->add_order_note(
            sprintf(
                __('Refund processed: %s %s. Transaction hash: %s', 'cregis-woocommerce'),
                $data['actual_refund_amount'],
                $data['refund_currency'],
                $data['refund_tx_id']
            )
        );
        
        $this->logger->log('Refund processed', array(
            'order_id' => $order->get_id(),
            'refund_amount' => $data['refund_amount']
        ));
    }
    
    /**
     * Handle additional payment event
     */
    private function handle_additional_payment($data) {
        $order = $this->get_order_by_order_id($data['order_id']);
        
        if (!$order) {
            throw new Exception('Order not found: ' . $data['order_id']);
        }
        
        $order->update_meta_data('_cregis_additional_payment', sanitize_text_field($data['additional_pay_amount']));
        $order->update_meta_data('_cregis_additional_tx_id', sanitize_text_field($data['additional_payment_tx_id']));
        $order->save();
        
        if (!$order->is_paid()) {
            $order->payment_complete($data['additional_payment_tx_id']);
        }
        
        $order->add_order_note(
            sprintf(
                __('Additional payment received: %s %s. Transaction hash: %s', 'cregis-woocommerce'),
                $data['additional_pay_amount'],
                $data['additional_pay_currency'],
                $data['additional_payment_tx_id']
            )
        );
        
        $this->logger->log('Additional payment received', array(
            'order_id' => $order->get_id(),
            'amount' => $data['additional_pay_amount']
        ));
    }
    
    /**
     * Get order by order ID
     */
    private function get_order_by_order_id($order_number) {
        $orders = wc_get_orders(array(
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'meta_query' => array(
                array(
                    'key' => '_order_number',
                    'value' => $order_number,
                    'compare' => '='
                )
            )
        ));
        
        if (!empty($orders)) {
            return $orders[0];
        }
        
        $order_id = wc_get_order_id_by_order_key($order_number);
        if ($order_id) {
            return wc_get_order($order_id);
        }
        
        if (is_numeric($order_number)) {
            return wc_get_order($order_number);
        }
        
        return null;
    }
}