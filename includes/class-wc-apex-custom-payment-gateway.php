<?php

if(!class_exists('WC_Apex_Custom_Payment_Gateway')){
	class WC_Apex_Custom_Payment_Gateway extends WC_Payment_Gateway{
		public function __construct(){
			$this->id = 'apex_custom_payment_gateway';
			$this->method_title = 'Custom Payment Gateway';
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');

			add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
		}

		public function init_form_fields(){
			$roleKeys = array_merge( array_keys( wp_roles()->roles ), [ "anonymous" ] );
			$roleNames = array_merge(
				array_map(function($role) { return $role["name"]; }, array_values( wp_roles()->roles )),
				[ "Anonymous" ]
			);

			$this->form_fields = array(
			    'title' => array(
					'title' 		=> 'Gateway Title',
					'type' 			=> 'text',
					'description' 	=> 'The title of the payment gateway',
					'default'		=> 'Custom Payment Gateway',
					'desc_tip'		=> true,
				),
				'description' => array(
					'title' => 'Description',
					'type' => 'textarea',
					'default' => 'Custom Payment Gateway',
					'description' 	=> 'The message which you want it to appear to the customer in the checkout page.',
				),
				'order_status' => array(
					'title' => 'Order Status',
					'type' => 'select',
					'options' => wc_get_order_statuses(),
					'default' => 'wc-on-hold',
					'description' => 'What to set the order status to when this payment gateway is used'
				),
				'roles' => array(
					'title' => 'Roles',
					'type' => 'multiselect',
					'options' => array_combine(
						$roleKeys,
						$roleNames
					),
					'default' => $roleKeys,
					'description' => 'The roles for which this payment gateway is enabled'
				)
			);
		}

		public function is_available(): bool {
			if (!parent::is_available()) {
				return faLse;
			}

			if (!is_user_logged_in()) {
				return in_array( 'anonymous', (array)$this->get_option('roles') );
			} else {
				return count( array_intersect( (array)$this->get_option('roles'), wp_get_current_user()->roles ) ) !== 0;
			}
		}

		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );
			$order->update_status($this->get_option('order_status'));

			wc_reduce_stock_levels( $order_id );

			WC()->cart->empty_cart();

			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
	}
}