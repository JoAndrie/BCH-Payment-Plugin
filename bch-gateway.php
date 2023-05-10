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

function add_cors_header() {
    header( 'Access-Control-Allow-Origin: http://127.0.0.1:8000/' );
    header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Content-Type' );
}

function register_cors_header() {
    add_action( 'rest_api_init', function () {
        add_filter( 'rest_pre_serve_request', function( $value ) {
        add_cors_header();
        return $value;
        });
    });
}

register_cors_header();

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
                'description' => array(
                    'title'         => 'Description',
                    'type'          => 'textarea',
                    'description'   => '',
                    'default'       => 'Pay with Bitcoin Cash',
                ),
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

    add_action('woocommerce_checkout_order_processed', 'send_to_django_backend');

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
            'store_url' => $store_url,
        ];

        $response = wp_remote_post($url, [
            'method' => 'POST',
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

    add_action( 'woocommerce_thankyou', 'show_qr_code_on_order_received_page');
    
    function show_qr_code_on_order_received_page( $order_id ) {

        $store_url = home_url();
        $url = 'http://127.0.0.1:8000/payment-gateway/total-bch/';

        $data = [
            'order_id' => $order_id,
            'store_url' => $store_url,
        ];

        $response = wp_remote_post($url, [
            'method' => 'POST',
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

            if ($response_code === 200) {
                $data = json_decode($response_body);
                $total_bch = $data->total_bch;
                $bch_address = $data->bch_address;
                // Use the total_bch value as needed
            } else {
                error_log("Error sending: Response code $response_code - $response_body");
            }
        }


        // HERE define you payment gateway ID (from $this->id in your plugin code)
        $payment_gateway_id = 'bch_payment_gateway';

        // Get an instance of the WC_Payment_Gateways object
        $payment_gateways   = WC_Payment_Gateways::instance();

        // Get the desired WC_Payment_Gateway object
        $payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];

        // $bch_address = $payment_gateway->get_option( 'bch_address' );
        $order = wc_get_order( $order_id );
        $total =  $order->get_total();
        // $test = get_value();

        $payment_method = $order->get_payment_method();

        // Generate the payment URL using the Bitcoin address and the order ID
        $payment_url = $bch_address . '?amount='. $total_bch; // Replace with your payment URL format
        if ( $payment_method === 'bch_payment_gateway' ) {
            // Generate the QR code image URL using the payment URL
            $qr_code_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode( $payment_url );
            // Display the QR code image on the order-received page
            echo '<p>Please scan the QR code to pay:</p><img src="' . $qr_code_url . '">';
            }
        
        
         ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
            <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery-simple-websocket@1.1.4/dist/jquery.simple.websocket.min.js"></script>
            <script type="text/javascript">
        
            address = '<?php echo $bch_address; ?>';
            var order_id = '<?php echo $order_id; ?>';
            var store_url = '<?php echo $store_url; ?>';
            var total_bch = '<?php echo $total_bch; ?>';
            
            var webSocket = $.simpleWebSocket({ url: `wss://watchtower.cash/ws/watch/bch/${address}/` });
            console.log(store_url);
            webSocket.listen(function(message) {
            // reconnected listening
                console.log(message);
                total_received = JSON.parse(message.amount);
                console.log(total_received);

                if (total_bch >= total_received) {
                    console.log("The amount matches the desired amount!");
                    fetch('http://127.0.0.1:8000/payment-gateway/process-order/', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            store_url: store_url,
                            order_id: order_id,
                            total_received: total_received
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
                }
                else {
                    console.log("The amount does not match the desired amount.");
                }
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
