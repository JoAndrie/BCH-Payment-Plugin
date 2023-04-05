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

    if ( isset( $_POST['payment_method'] ) && $_POST['payment_method'] === 'bch_payment_gateway' ) {
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

            // Generate the payment URL using the Bitcoin address and the order ID
            $payment_url = $bch_address . '?amount=0.00001'; // Replace with your payment URL format

            // Generate the QR code image URL using the payment URL
            $qr_code_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode( $payment_url );

            

            // Display the QR code image on the order-received page
            echo '<p>Please scan the QR code to pay:</p><img src="' . $qr_code_url . '">';
            
        } 

        add_action( 'woocommerce_thankyou', 'test', 10, 1 );
        function test( $order_id ) {
            ?>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
            <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery-simple-websocket@1.1.4/dist/jquery.simple.websocket.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.1/socket.io.min.js"></script>
            <script type="text/javascript">
        

            var consumer_key = 'ck_e65e294f14b8c6699c018a566044de40f08aa859';
            var consumer_secret = 'cs_cf8c2caad14bb66a12fa73264bbe0d0112995edd';

            <?php 
            $payment_gateway_id = 'bch_payment_gateway';
            $payment_gateways   = WC_Payment_Gateways::instance();
            $payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];
            $bch_address = $payment_gateway->get_option( 'bch_address' );

            $order = wc_get_order( $order_id );
            $total =  $order->get_total();
            ?>
            const address = '<?php echo $bch_address; ?>';
            var order_id = '<?php echo $order_id; ?>';
            
            var webSocket = $.simpleWebSocket({ url: `wss://watchtower.cash/ws/watch/bch/${address}/` });
            var url = `https://paytaca-test.local/wp-json/wc/v3/orders/${order_id}`;
            console.log(order_id);

            webSocket.listen(function(message) {
                    // reconnected listening
                    console.log(message);

                    fetch('http://127.0.0.1:5000/process_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        consumer_key: consumer_key,
                        consumer_secret: consumer_secret,
                        order_id: order_id
                    })
                })
                .then(response => response.json())
                .then(result => console.log(result))
                .catch(error => console.log('error', error));
            });
            </script>
        <?php
        }
    }
    // // Register the payment gateway
    // add_filter( 'woocommerce_payment_gateways', function ( $methods ) {
    //     $methods[] = 'WC_BCH_Payment_Gateway';
    //     return $methods;
    // } );

    // Add your payment gateway to WooCommerce
    function add_your_payment_gateway($methods) {
        $methods[] = 'WC_Your_Payment_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_your_payment_gateway');

	
	
}


?>
