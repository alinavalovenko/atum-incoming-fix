<?php namespace w8\plugins\w8_atum_purchase_order_incoming_stock;

class Class_Admin {
	use \W8_Trait_Singleton;

	private $plugin;

	private function initialise() {
		$this->setup_globals();
		$this->load_dependencies();
		$this->define_hooks();
	}

	private function setup_globals() {
		$this->plugin = Class_Plugin::instance();
	}

	private function load_dependencies() {
	}

	private function define_hooks() {
		add_action( 'save_post_atum_purchase_order', array( $this, 'update_ca_incoming' ), PHP_INT_MAX );

		add_action( 'acp/editing/saved', array( $this, 'on_incoming_save' ), 10, 3 );
		add_action( 'atum/orders/after_delete_item', array( $this, 'on_purchase_order_item_delete' ) );
		add_action( 'wp_trash_post', array( $this, 'on_purchase_move_to_trash' ) );
	}

	public function update_ca_incoming( $post_ID ) {
		global $wpdb;

		$atum_order        = new \Atum\PurchaseOrders\Models\PurchaseOrder( $post_ID );
		$atum_order_status = $atum_order->get_status();
		$line_items        = $atum_order->get_items( 'line_item' );
		$meta_key          = "_w8_atum_purchase_order_{$post_ID}";

		foreach ( $line_items as $item_id => $item ) {
			$product = $item->get_product();
			/** @var \WC_Product $product */
			if ( empty( $product ) ) {
				continue;
			};

			$product_id = ( $product->get_type() == 'variation' ) ? $product->get_parent_id() : $product->get_id();

			if ( 'completed' == $atum_order_status ) {
				// remove incoming stock for current item if order is complete
				delete_post_meta( $product_id, $meta_key );
			} else {
				// update incoming stock for current item if order is not complete
				$order_qty               = $item->get_quantity();
				update_post_meta( $product_id, 'ca_incoming', $order_qty);
				update_post_meta( $product_id, $meta_key, $order_qty );
			}
		};
	}

	public function on_incoming_save( $column, $id, $value ) {
		if ( '5b3b6b0e40b7b' == $column->get_name() ) {
			global $wpdb;

			$query = "SELECT ID FROM {$wpdb->posts} WHERE post_type='atum_purchase_order' AND post_status='atum_pending' ORDER BY post_modified DESC LIMIT 1";
			if ( $order_id = intval( $wpdb->get_var( $query ) ) ) {
				$atum_order = new \Atum\PurchaseOrders\Models\PurchaseOrder( $order_id );
				$order_item = $atum_order->add_product( wc_get_product( $id ), $value );
//				$atum_order->calculate_totals( false ); // do not recalculate taxes
				$atum_order->save();
			}
		}
	}

	public function on_purchase_order_item_delete( $item_id ) {
		$item = new \Atum\PurchaseOrders\Items\POItemProduct( $item_id );

		// update total incoming stock
		$incoming = floatval( get_post_meta( $item->get_product_id(), 'ca_incoming', true ) );
		if ( $incoming > 0 ) {
			update_post_meta(
				$item->get_product_id(),
				'ca_incoming',
				floatval( $incoming - $item->get_quantity() )
			);
		}
	}

	public function on_purchase_move_to_trash( $post_id ) {
		$atum_order = new \Atum\PurchaseOrders\Models\PurchaseOrder( $post_id );
		if ( $atum_order ) {
			$atum_order_status = $atum_order->get_status();
			$line_items        = $atum_order->get_items( 'line_item' );
			if ( 'pending' == $atum_order_status ) {
				foreach ( $line_items as $item_id => $item ) {
					$product                 = $item->get_product();
					$product_id              = ( $product->get_type() == 'variation' ) ? $product->get_parent_id() : $product->get_id();
					$product_incoming_value = get_post_meta( $product_id, 'ca_incoming', true );
					$order_qty               = $item->get_quantity();
					$incoming_value = $product_incoming_value - $order_qty;
					update_post_meta( $product_id, 'ca_incoming', $incoming_value);
				}
			}
		}
	}
}
