=== Cregis Payment Gateway for WooCommerce ===
Contributors: cregis
Tags: cryptocurrency, bitcoin, ethereum, woocommerce, payment gateway, crypto payments
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept cryptocurrency payments in your WooCommerce store via Cregis Payment Engine.

== Description ==

Cregis Payment Gateway enables your WooCommerce store to accept cryptocurrency payments securely and efficiently through the Cregis Payment Engine. Support for multiple cryptocurrencies including USDT, USDC, BTC, ETH, and more.

= Features =

* Accept multiple cryptocurrencies (USDT, USDC, BTC, ETH, BNB)
* Support for multiple blockchain networks (TRC20, BEP20, ERC20)
* Automatic payment verification via webhooks
* Support for both classic and block-based checkout
* Real-time order status updates
* Customizable payment timeout settings
* Multi-language support (English, Traditional Chinese, Simplified Chinese)
* Secure signature verification
* Comprehensive logging for debugging
* Partial payment and overpayment handling

= Requirements =

* WooCommerce 6.0 or higher
* PHP 7.4 or higher
* Cregis account with API credentials
* SSL certificate (HTTPS) for webhook verification

= Getting Started =

1. Sign up for a Cregis account at https://www.cregis.com
2. Obtain your API credentials (API URL, API Key, Project ID)
3. Install and activate the plugin
4. Navigate to WooCommerce > Settings > Payments > Cregis
5. Enter your API credentials
6. Configure your payment settings
7. Copy the webhook URL and add it to your Cregis dashboard

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Cregis Payment Gateway"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Upload the zip file and click "Install Now"
5. Click "Activate Plugin"

= Configuration =

1. Go to WooCommerce > Settings > Payments
2. Click on "Cregis Crypto Payments"
3. Enter your Cregis API credentials:
   - API URL (production or test environment)
   - API Key
   - Project ID (PID)
4. Configure payment settings:
   - Order valid time (10-1440 minutes)
   - Accepted tokens
   - Checkout language
   - Partial payment settings
5. Copy the webhook URL displayed at the top
6. Add the webhook URL to your Cregis dashboard
7. Save changes and test the payment flow

== Frequently Asked Questions ==

= What cryptocurrencies are supported? =

The plugin supports USDT, USDC, BTC, ETH, BNB, and other cryptocurrencies supported by Cregis. You can configure which tokens to accept in the settings.

= Do I need a Cregis account? =

Yes, you need a Cregis merchant account to use this plugin. Sign up at https://www.cregis.com

= How do refunds work? =

Refunds must be processed through the Cregis dashboard. The plugin will log refund notifications when they occur.

= Is the plugin compatible with WooCommerce Blocks? =

Yes, the plugin fully supports both classic checkout and block-based checkout.

= Where can I find my API credentials? =

Log in to your Cregis dashboard and navigate to the API settings section to find your credentials.

= What is the webhook URL used for? =

The webhook URL allows Cregis to send real-time payment notifications to your store, automatically updating order statuses.

= Can I test the plugin before going live? =

Yes, you can use Cregis test environment credentials for testing. Make sure to switch to production credentials before accepting real payments.

== Screenshots ==

1. Plugin settings page with API configuration
2. Classic checkout with Cregis payment option
3. Block checkout with Cregis payment option
4. Cregis payment page with cryptocurrency options
5. Order details with cryptocurrency payment information

== Changelog ==

= 1.0.0 =
* Initial release
* Support for cryptocurrency payments via Cregis
* Classic and block checkout support
* Webhook integration for automatic order updates
* Multi-currency support
* Comprehensive logging
* Partial payment and overpayment handling

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Cregis Payment Gateway for WooCommerce.

== Support ==

For support, please visit:
* Documentation: https://developer.cregis.com
* Support: https://chat.bytrack.xyz?appId=7tAbr779
* GitHub: https://github.com/0xcregis

== Privacy Policy ==

This plugin sends order data to Cregis servers for payment processing. This includes:
* Order amount and currency
* Customer email address (required for payment notifications)
* Customer name (optional)
* Order items (optional, for display on checkout page)

All data transmission is encrypted via HTTPS. For more information, review the Cregis Privacy Policy at https://www.cregis.com/privacy