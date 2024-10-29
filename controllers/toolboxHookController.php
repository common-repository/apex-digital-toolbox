<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookController' ) ) {
	define( 'APEX_TOOLBOX_HOOK_ACTION', 1 );
	define( 'APEX_TOOLBOX_HOOK_FILTER', 2 );

	class toolboxHookController {
		private $_label = 'Not set';
		private $_hooks = Array();
		public $Toolbox;

		/**
		 * @param $Toolbox toolboxController
		 */
		public function __construct( $Toolbox ) {
			$this->Toolbox = $Toolbox;
		}

		/**
		 * @return array
		 */
		public function getHooks() {
			return $this->_hooks;
		}

		/**
		 * Add a hook as being available to the plugin
		 *
		 * @param int $type Type of hook being added i.e. hook or action
		 * @param string $hook WordPress hook reference
		 * @param string $method Callback method in the call for the hook
		 * @param array $args Any special arguments to pass in to the hook
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return void;
		 */
		public function addHook( $type, $hook, $method, $args = Array() ) {
			$this->_hooks[] = new toolboxHookModel( $type, $hook, $method, $args );
		}

		/**
		 * @return string
		 */
		public function getLabel() {
			return $this->_label;
		}

		/**
		 * @param string $label
		 */
		public function setLabel( $label ) {
			$this->_label = $label;
		}

		/**
		 * Get the name of the controller that extended this controller minus the prefix and suffix
		 *
		 * @author Nigel Wells
		 * @return string;
		 * @throws ReflectionException
		 */
		public function getName() {
			$ReflectionClass = new ReflectionClass( $this );
			$filename        = $ReflectionClass->getFileName();
			$filename        = substr( $filename, strrpos( $filename, '/' ) + 1 );
			$filename        = substr( $filename, 5, strlen( $filename ) - 9 );

			return $filename;
		}

		/**
		 * Add a notice to be displayed on page load
		 *
		 * @param string $notice Text to be displayed
		 * @param int $type What sort of notice to display - defaults to success
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.07
		 * @return void;
		 */
		public function addNotice( $notice, $type = 1) {
			$this->setupNotices();
			$_SESSION[ $this->Toolbox->getPrefix() . 'notices' ][] = Array(
				'text' => $notice,
				'type' => $type
			);
		}

		/**
		 * Prepare the notices session array to make sure it can be added to or reset
		 *
		 * @param boolean $reset Reset the notice array to clear all active notices
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.11
		 * @return void;
		 */
		private function setupNotices( $reset = false ) {
			if ( ! isset( $_SESSION[ $this->Toolbox->getPrefix() . 'notices' ] ) || $reset === true ) {
				$_SESSION[ $this->Toolbox->getPrefix() . 'notices' ] = Array();
			}
		}

		/**
		 * Get a list of all the current notices
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.07
		 * @return array;
		 */
		private function getNotices() {
			$this->setupNotices();

			return $_SESSION[ $this->Toolbox->getPrefix() . 'notices' ];
		}

		/**
		 * Display a notice in any output screens if required
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.07
		 * @return string;
		 */
		public function displayNotices() {
			$html = '';
			foreach ( $this->getNotices() as $notice ) {
				// Check the type of notice to display
				if ( $notice['type'] == 2 ) {
					$type = 'warning';
				} elseif ( $notice['type'] == 3 ) {
					$type = 'danger';
				} else {
					$type = 'success';
				}
				// Output the notice
				$html .= '<div class="notice notice-' . $type . '"><p>' . $notice['text'] . '</p></div>';
			}
			// Reset notices session so they don't keep appearing
			$this->setupNotices( true );

			// Return the notices
			return $html;
		}

		/**
		 * Redirect to a specific page
		 *
		 * @param string $page Page to redirect to
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.10
		 * @return void;
		 */
		public function redirect($page = '') {
			if(!$page && isset($_REQUEST['page'])) {
				$page = $_REQUEST['page'];
			}
			wp_redirect( admin_url( 'admin.php' ) . '?page=' . $page);
			exit();
		}

	}
}