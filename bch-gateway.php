<?php
/*
Plugin Name: BCH Payment Gateway (Test)
Plugin URI: https://paytaca.com
Description: Pay using BCH
Version: 1.0
Author: Paytaca
Author URI: https://paytaca.com/
	Copyright: © 2023-2024 Paytaca.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Register the WC_BCH_Payment_Gateway as a WooCommerce Payment Gateway
 */

add_filter('woocommerce_payment_gateways', 'add_bch_payment_gateway_class' );
function add_bch_payment_gateway_class($gateways) {
  $gateways[] = 'WC_BCH_Payment_Gateway';
  return $gateways;
}


add_action('plugins_loaded', 'init_bch_payment_gateway_class', 0);
/**
 * WC_BCH_Payment_Gateway class initializer function
 */
function init_bch_payment_gateway_class() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	/**
 	 * Localisation
	 */
	load_plugin_textdomain('wc-gateway-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    
	/**
 	 * Gateway class
 	 */
	class WC_BCH_Payment_Gateway extends WC_Payment_Gateway {
	
        public function __construct() {

            $this->id = 'bch_payment_gateway'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'BCH Payment';
            $this->method_description = 'Pay using BCH'; // will be displayed on the options page
        
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
        
            // Method with all the options fields
            $this->init_form_fields();
        
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->bch_address = $this->get_option( 'bch_address' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
        
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
            // Payment listener using JavaScript
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'         => 'Enable/Disable',
                    'label'         => 'Enable BCH Payment Gateway',
                    'type'          => 'checkbox',
                    'description'   => '',
                    'default'       => 'no',
                    'desc_tip'      => true,
                ),
                'title' => array(
                    'title'         => 'Pay using BCH',
                    'type'          => 'text',
                    'description'   => '',
                    'default'       => '',
                ),
                'bch_address' => array(
                    'title'         => 'BCH Address',
                    'type'          => 'text',
                    'description'   => 'Enter your BCH address to receive payments.',
                    'default'       => '',
                ),
                'description' => array(
                    'title'         => 'Description',
                    'type'          => 'textarea',
                    'description'   => '',
                    'default'       => 'Pay with Bitcoin Cash',
                ),
                'confirmation' => array(
                    'title'         => 'BCH Confirmation',
                    'type'          => 'number',
                    'description'   => '',
                    'default'       => '',
                ),
                'decimals' => array(
                    'title'         => 'BCH Amount Decimals',
                    'type'          => 'select',
                    'description'   => '',
                    'default'       => 'option2',
                    'options'       => array(
                        'option1' => '2',
                        'option2' => '4',
                        'option3' => '6',
                        'option4' => '8'
                    ),
                ),
                'block_explorer' => array(
                    'title'         => 'BCH Block Explorer',
                    'type'          => 'select',
                    'description'   => '',
                    'default'       => 'option1',
                    'options'       => array(
                        'option1' => 'Option 1',
                        'option2' => 'Option 2',
                        'option3' => 'Option 3'
                    ),
                )

            );
        }

        // Process the payment
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            // Mark the order as on-hold (payment pending)
            $order->update_status( 'on-hold', __( 'Payment pending.', 'woocommerce' ) );

            // Reduce stock levels
            wc_reduce_stock_levels( $order_id );


            // Return thank you page URL
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );

            // Empty cart
            // WC()->cart->empty_cart();
        }

        public function payment_scripts() {    
            wp_enqueue_script( 'WC_BCH_Payment_Gateway' );
        }

        // public function process_payment( $order_id ) {
        //     return array(
        //         'result' => 'success',
        //         'redirect' => $this->get_return_url( $order ),
        //     );
        // }
    
	}

    add_action('woocommerce_thankyou', 'send_billing_details');

    function send_to_django_backend($order_id) {
        // Get the order object
        $order = wc_get_order($order_id);

        if ($order->get_payment_method() !== 'bch_payment_gateway') {
            return;
        }

        $store_url = home_url();

        // Send the data to the server
        $url = 'http://127.0.0.1:8000/payment-gateway/get-order/';

        $data = [
            'order_id' => $order_id,
            'store' => $store_url,
        ];

        $response = wp_remote_post($url, [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'cookies' => []
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Error sending: $error_message");
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $response_code = wp_remote_retrieve_response_code($response);

            if ($response_code === 201) {
                error_log('Sent successfully');
            } else {
                error_log("Error sending: Response code $response_code - $response_body");
            }
        }
    }

    add_action( 'woocommerce_thankyou', 'show_qr_code_on_order_received_page', 10, 1 );

    function show_qr_code_on_order_received_page( $order_id ) {

        // HERE define you payment gateway ID (from $this->id in your plugin code)
        $payment_gateway_id = 'bch_payment_gateway';

        // Get an instance of the WC_Payment_Gateways object
        $payment_gateways   = WC_Payment_Gateways::instance();

        // Get the desired WC_Payment_Gateway object
        $payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];
                
        // Get the Bitcoin address from the settings
        $bch_address = $payment_gateway->get_option( 'bch_address' ); // Replace with the option name for your Bitcoin address setting

        // $bch_address1 = 'bitcoincash:qqsrj4cl6rcer4kct74k99fxklkd8uwyx5jjfp07au';

        $order = wc_get_order( $order_id );
        $total =  $order->get_total();

        // $test = get_value();

        $payment_method = $order->get_payment_method();

        // Generate the payment URL using the Bitcoin address and the order ID
        $payment_url = $bch_address . '?amount=0.00001'; // Replace with your payment URL format
        if ( $payment_method === 'bch_payment_gateway' ) {
            // Generate the QR code image URL using the payment URL
            $qr_code_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode( $payment_url );
            // Display the QR code image on the order-received page
            echo '<p>Please scan the QR code to pay:</p><img src="' . $qr_code_url . '">';
            }
            
    } 

    add_action( 'woocommerce_thankyou', 'test', 10, 1 );

    function test( $order_id ) {
        ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery-simple-websocket@1.1.4/dist/jquery.simple.websocket.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.1/socket.io.min.js"></script>
        <script type="text/javascript">
        
        <?php 
        $payment_gateway_id = 'bch_payment_gateway';
        $payment_gateways   = WC_Payment_Gateways::instance();
        $payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];
        $bch_address = $payment_gateway->get_option( 'bch_address' );

        $order = wc_get_order( $order_id );
        $total =  $order->get_total();
        $store_url = home_url();
        ?>
        const address = '<?php echo $bch_address; ?>';
        var order_id = '<?php echo $order_id; ?>';
        var store_url = '<?php echo $store_url; ?>';

        var webSocket = $.simpleWebSocket({ url: `wss://watchtower.cash/ws/watch/bch/${address}/` });
        console.log(store_url);
        webSocket.listen(function(message) {
            // reconnected listening
            console.log(message);

            fetch('http://127.0.0.1:8000/payment-gateway/process-order/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    store_url: store_url,
                    order_id: order_id
                })
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                } else {
                    throw new Error('Network response was not ok.');
                }
            })
            .then(data => {
                console.log(data);
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
            });
        });
        </script>
    <?php
    }
        
    function add_your_payment_gateway($methods) {
        $methods[] = 'WC_Your_Payment_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_your_payment_gateway');

	
	
}


?>
