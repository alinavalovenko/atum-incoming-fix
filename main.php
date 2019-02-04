<?php namespace w8\plugins\w8_atum_purchase_order_incoming_stock;

/**
 * Plugin Name:       ATUM Plugin Incoming Extension
 * Plugin URI:        https://webcodist.com/
 * Description:       Updates product's incoming stock level from a pending ATUM purchase order.
 * Version:           1.0.0
 * Author:            Alina Valovenko
 * Author URI:        http://valovenko.pro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       w8-atum-purchase-order-incoming-stock
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( __DIR__ . '/includes/W8_Loader.php' );
require_once( __DIR__ . '/includes/W8_Trait_Singleton.php' );

final class Class_Plugin {
	use \W8_Trait_Singleton;

	private $version = '1.0.0';
	private $name    = 'w8_atum_purchase_order_incoming_stock';
	private $path    = __DIR__ . '/';

	/**
	 * @var \W8_Loader null
	 */
	private $loader  = null;

	private $admin   = null;

	public function __get( $constant_name ) {
		return isset( $this->$constant_name ) ? $this->$constant_name : null;
	}

	private function initialise() {
		$this->setup_globals();
		$this->load_dependencies();
		$this->define_hooks();
	}

	private function setup_globals() {
		$this->loader = new \W8_Loader();
		$this->loader->register();
	}

	private function load_dependencies() {
		$this->loader->setPrefixes( array(
			__NAMESPACE__ => array(
				$this->path . 'includes',
				$this->path . 'admin',
				$this->path . 'front',
			),
		) );
	}

	private function define_hooks() {
		register_activation_hook( __FILE__,   array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ), PHP_INT_MAX );
	}

	public function activate() {}

	public function deactivate() {}

	public function init() {
		$this->admin = Class_Admin::instance();
	}
}

Class_Plugin::instance();
