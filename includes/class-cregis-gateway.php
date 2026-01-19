<?php
/**
 * Cregis Payment Gateway
 *
 * @package Cregis_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Cregis extends WC_Payment_Gateway {
    
    public $api_url;
    public $api_key;
    public $pid;
    public $valid_time;
    public $accepted_tokens;
    public $language;
    public $accept_partial;
    public $accept_over;
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'cregis';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = __('Cregis Crypto Payments', 'cregis-woocommerce');
        $this->method_description = __('Accept cryptocurrency payments via Cregis Payment Engine', 'cregis-woocommerce');
        $this->supports = array(
            'products',
            'refunds',
        );
        
        $this->order_button_text = __('Proceed to Cryptocurrency Payment', 'cregis-woocommerce');
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->api_url = $this->get_option('api_url');
        $this->api_key = $this->get_option('api_key');
        $this->pid = $this->get_option('pid');
        $this->valid_time = $this->get_option('valid_time', '60');
        $this->accepted_tokens = $this->get_option('accepted_tokens', array());
        $this->language = $this->get_option('language', 'en');
        $this->accept_partial = $this->get_option('accept_partial', 'yes') === 'yes';
        $this->accept_over = $this->get_option('accept_over', 'yes') === 'yes';
        
        $this->logger = new Cregis_Logger();
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_cregis_webhook', array($this, 'webhook_handler'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        
        if ($this->is_available()) {
            add_filter('woocommerce_get_order_item_totals', array($this, 'add_payment_info_to_order_totals'), 10, 2);
        }
    }
    
    /**
     * Initialize form fields for admin settings
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'cregis-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Cregis Crypto Payments', 'cregis-woocommerce'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'cregis-woocommerce'),
                'type' => 'text',
                'description' => __('Payment method title shown to customers during checkout.', 'cregis-woocommerce'),
                'default' => __('Cryptocurrency', 'cregis-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'cregis-woocommerce'),
                'type' => 'textarea',
                'description' => __('Payment method description shown to customers during checkout.', 'cregis-woocommerce'),
                'default' => __('Pay securely with cryptocurrency via Cregis.', 'cregis-woocommerce'),
                'desc_tip' => true,
            ),
            'api_url' => array(
                'title' => __('API URL', 'cregis-woocommerce'),
                'type' => 'text',
                'description' => __('Cregis API base URL (production or test).', 'cregis-woocommerce'),
                'default' => 'https://api.cregis.io',
                'desc_tip' => true,
            ),
            'api_key' => array(
                'title' => __('API Key', 'cregis-woocommerce'),
                'type' => 'password',
                'description' => __('Your Cregis API key.', 'cregis-woocommerce'),
                'desc_tip' => true,
            ),
            'pid' => array(
                'title' => __('Project ID (PID)', 'cregis-woocommerce'),
                'type' => 'text',
                'description' => __('Your Cregis project ID.', 'cregis-woocommerce'),
                'desc_tip' => true,
            ),
            'valid_time' => array(
                'title' => __('Order Valid Time', 'cregis-woocommerce'),
                'type' => 'number',
                'description' => __('Time in minutes for order expiration (10-1440).', 'cregis-woocommerce'),
                'default' => '60',
                'custom_attributes' => array(
                    'min' => '10',
                    'max' => '1440',
                    'step' => '1'
                ),
                'desc_tip' => true,
            ),
            'accepted_tokens' => array(
                'title' => __('Accepted Tokens', 'cregis-woocommerce'),
                'type' => 'multiselect',
                'description' => __('Select which tokens to accept. Leave empty to accept all.', 'cregis-woocommerce'),
                'options' => array(
                    'USDT-TRC20' => 'USDT (TRC20)',
                    'USDT-BEP20' => 'USDT (BEP20)',
                    'USDT-ERC20' => 'USDT (ERC20)',
                    'USDC-BEP20' => 'USDC (BEP20)',
                    'USDC-ERC20' => 'USDC (ERC20)',
                    'BTC' => 'Bitcoin (BTC)',
                    'ETH' => 'Ethereum (ETH)',
                    'BNB' => 'BNB',
                ),
                'desc_tip' => true,
            ),
            'language' => array(
                'title' => __('Checkout Language', 'cregis-woocommerce'),
                'type' => 'select',
                'description' => __('Default language for Cregis checkout page.', 'cregis-woocommerce'),
                'options' => array(
                    'en' => 'English',
                    'tc' => 'Traditional Chinese',
                    'sc' => 'Simplified Chinese',
                ),
                'default' => 'en',
                'desc_tip' => true,
            ),
            'accept_partial' => array(
                'title' => __('Accept Partial Payment', 'cregis-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Allow customers to pay less than the full amount', 'cregis-woocommerce'),
                'default' => 'yes'
            ),
            'accept_over' => array(
                'title' => __('Accept Overpayment', 'cregis-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Allow customers to pay more than the order amount', 'cregis-woocommerce'),
                'default' => 'yes'
            ),
            'debug_mode' => array(
                'title' => __('Debug Mode', 'cregis-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable detailed logging', 'cregis-woocommerce'),
                'default' => 'no'
            ),
        );
    }
    
    /**
     * Admin options
     */
    public function admin_options() {
        ?>
<h2><?php echo esc_html($this->get_method_title()); ?></h2>
<p><?php echo esc_html($this->get_method_description()); ?></p>

<div class="cregis-webhook-info"
    style="background: #f0f0f1; padding: 15px; margin: 20px 0; border-left: 4px solid #2271b1;">
    <h3 style="margin-top: 0;"><?php esc_html_e('Webhook URL', 'cregis-woocommerce'); ?></h3>
    <p><?php esc_html_e('Configure this URL in your Cregis dashboard:', 'cregis-woocommerce'); ?></p>
    <code style="display: block; background: white; padding: 10px; overflow-x: auto;">
                <?php echo esc_url(WC()->api_request_url('cregis_webhook')); ?>
            </code>
</div>

<table class="form-table">
    <?php $this->generate_settings_html(); ?>
</table>
<?php
    }
    
    /**
     * Process payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wc_add_notice(__('Order not found', 'cregis-woocommerce'), 'error');
            return array('result' => 'failure');
        }
        
        try {
            $api = new Cregis_API($this->api_url, $this->api_key, $this->pid);
            
            $order_data = array(
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
                'payer_id' => strval($order->get_customer_id() ?: $order->get_billing_email()),
                'payer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'payer_email' => $order->get_billing_email(),
                'valid_time' => absint($this->valid_time),
                'callback_url' => WC()->api_request_url('cregis_webhook'),
                'success_url' => $this->get_return_url($order),
                'cancel_url' => wc_get_checkout_url(),
                'remark' => sprintf(__('Order #%s', 'cregis-woocommerce'), $order->get_order_number()),
                'language' => $this->language,
                'accept_partial_payment' => $this->accept_partial,
                'accept_over_payment' => $this->accept_over,
            );
            
            if (!empty($this->accepted_tokens)) {
                $order_data['tokens'] = $this->accepted_tokens;
            }
            
            $items = array();
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $items[] = array(
                    'item_id' => strval($product ? $product->get_id() : 0),
                    'item_name' => $item->get_name(),
                    'item_price' => floatval($item->get_total() / $item->get_quantity()),
                    'price_currency' => $order->get_currency(),
                    'item_quantity' => $item->get_quantity(),
                );
            }
            
            if (!empty($items)) {
                $order_data['order_details'] = array(
                    'items' => $items,
                    'shopping_cost' => floatval($order->get_shipping_total()),
                    'tax_cost' => floatval($order->get_total_tax()),
                );
            }
            
            $response = $api->create_payment($order->get_order_number(), $order_data);
            
            if (!$response['success']) {
                throw new Exception($response['error'] ?? __('Failed to create payment', 'cregis-woocommerce'));
            }
            
            $payment_data = $response['data'];
            
            $order->update_meta_data('_cregis_id', sanitize_text_field($payment_data['cregis_id']));
            $order->update_meta_data('_cregis_checkout_url', esc_url_raw($payment_data['checkout_url']));
            $order->update_meta_data('_cregis_created_time', sanitize_text_field($payment_data['created_time']));
            $order->update_meta_data('_cregis_expire_time', sanitize_text_field($payment_data['expire_time']));
            $order->save();
            
            $order->update_status('pending', __('Awaiting cryptocurrency payment', 'cregis-woocommerce'));
            
            WC()->cart->empty_cart();
            
            $this->logger->log('Payment created', array(
                'order_id' => $order_id,
                'cregis_id' => $payment_data['cregis_id']
            ));
            
            return array(
                'result' => 'success',
                'redirect' => $payment_data['checkout_url']
            );
            
        } catch (Exception $e) {
            $this->logger->log('Payment creation failed', array(
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ), 'error');
            
            wc_add_notice($e->getMessage(), 'error');
            return array('result' => 'failure');
        }
    }
    
    /**
     * Webhook handler
     */
    public function webhook_handler() {
        $webhook = new Cregis_Webhook($this->api_url, $this->api_key, $this->pid);
        $webhook->handle();
    }
    
    /**
     * Thank you page
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $cregis_id = $order->get_meta('_cregis_id');
        
        if ($cregis_id && $order->has_status('pending')) {
            echo '<div class="woocommerce-info">';
            echo '<p>' . esc_html__('Your cryptocurrency payment is being processed.', 'cregis-woocommerce') . '</p>';
            echo '<p>' . esc_html__('You will receive a confirmation email once the payment is confirmed.', 'cregis-woocommerce') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add payment info to order totals display
     */
    public function add_payment_info_to_order_totals($total_rows, $order) {
        if ($order->get_payment_method() !== $this->id) {
            return $total_rows;
        }
        
        $tx_id = $order->get_meta('_cregis_transaction_hash');
        $pay_amount = $order->get_meta('_cregis_pay_amount');
        $pay_currency = $order->get_meta('_cregis_pay_currency');
        
        if ($tx_id && $pay_amount && $pay_currency) {
            $payment_info = array(
                'crypto_payment' => array(
                    'label' => __('Cryptocurrency Payment:', 'cregis-woocommerce'),
                    'value' => sprintf('%s %s', esc_html($pay_amount), esc_html($pay_currency))
                )
            );
            
            $position = array_search('payment_method', array_keys($total_rows), true);
            if ($position !== false) {
                $total_rows = array_slice($total_rows, 0, $position + 1, true) +
                             $payment_info +
                             array_slice($total_rows, $position + 1, null, true);
            } else {
                $total_rows = array_merge($total_rows, $payment_info);
            }
        }
        
        return $total_rows;
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('error', __('Order not found', 'cregis-woocommerce'));
        }
        
        $this->logger->log('Refund requested', array(
            'order_id' => $order_id,
            'amount' => $amount,
            'reason' => $reason
        ));
        
        return new WP_Error('error', __('Refunds must be processed through the Cregis dashboard', 'cregis-woocommerce'));
    }
}