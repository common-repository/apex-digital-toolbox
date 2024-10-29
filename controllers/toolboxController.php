<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxController' ) ) {
	class toolboxController {
		private $_prefix = 'apex_toolbox_';
		private $_options = Array();
		private $_path = '';
		private $_setting = Array();
		private $_enabled = Array();
		private $_objects = Array();

		function __construct() {
			$file        = dirname( __FILE__ ) . '/apex-digital-toolbox.php';
			$this->_path = plugin_dir_path( $file ) . '../';
			// Include any files
			$this->loadIncludeFiles();
			// Load options
			$this->loadOptions();
			// Register any WordPress hooks
			$this->registerHooks();
		}

		/**
		 * Load all the various options saved in WordPress for the plugin
		 *
		 * @author Nigel Wells
		 * @version 0.0.1.16.10.04
		 * @return void;
		 */
		private function loadOptions() {
			// Loop through all options and store them in the class to make it easier to access when needed
			foreach ( $this->getOptions() as $option ) {
				$optionValue               = get_option( $this->getPrefix() . $option );
				$this->_options[ $option ] = $optionValue;
			}
		}

		/**
		 * Get all defined options for the plugin
		 *
		 * @author Nigel Wells
		 * @version 0.0.1.16.10.06
		 * @return array;
		 */
		private function getOptions() {
			// Define available options
			$options = Array(
				'hooks',
			);

			return $options;
		}

		/**
		 * Get a specific options value for the plugin
		 *
		 * @param string $option Option to get the value from the plugin
		 *
		 * @author Nigel Wells
		 * @return mixed;
		 */
		public function getOption( $option ) {
			$value = ( isset( $this->_options[ $option ] ) ? $this->_options[ $option ] : null );
			// If nothing was found then it might not be a default one so check with WordPress
			if ( $value === null ) {
				$value                     = get_option( $this->getPrefix() . $option );
				$this->_options[ $option ] = $value;
			}
			// TODO: Delete this in a future version - exists because we serialized values to save rather than letting WP do it
			if ( is_serialized( $value ) ) {
				$value = unserialize( $value );
			}
			return $value;
		}

		/**
		 * Update option in WordPress
		 *
		 * @param string $option Option to set
		 * @param mixed $value Value to save in the database. Arrays will be automatically serialized
		 *
		 * @author Nigel Wells
		 * @return void;
		 */
		public function setOption( $option, $value ) {
			update_option( $this->getPrefix() . $option, $value );
		}

		/**
		 * Load required libraries for the plugin
		 *
		 * @author Nigel Wells
		 * @version 0.3.9.17.03.21
		 * @return void;
		 */
		private function loadIncludeFiles() {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			require_once( $this->_path . 'controllers/toolboxHookController.php' );
			require_once( $this->_path . 'models/toolboxHookModel.php' );
			require_once( $this->_path . 'models/toolboxFieldModel.php' );
			include_once( $this->_path . 'includes/apex_toolbox_functions.php' );
		}

		public function includeFile($file) {
			require_once( $this->_path . 'includes/' . $file );
		}

		/**
		 * Register hooks & filters with WordPress
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.10
		 * @return void;
		 */
		private function registerHooks() {
			// Get required hooks
			$hooks = $this->getOption( 'hooks' );
			if ( ! is_array( $hooks ) ) {
				$hooks = Array();
			}
			$hooks = array_merge_recursive( $this->getDefaultHooks(), $hooks );
			// To help keep a light fingerprint we only need to load up required hooks
			foreach ( $hooks as $hookSource => $hookArray ) {
				$this->loadHookFile( $hookSource );
				$hookName                    = $this->getHookName( $hookSource );
				$hookObject                  = new $hookName( $this );
				$this->_objects[ $hookName ] = $hookObject;
				foreach ( $hookArray as $hookMethod ) {
					// Locate the hook with the setting we need
					foreach ( $hookObject->getHooks() as $hookModal ) {
						if ( $hookModal->getMethod() == $hookMethod ) {
							$hookModal->createHook( $hookObject );
							// Register this hook as being enabled
							$this->_enabled[ $hookSource ][] = $hookMethod;
						}
					}
				}
			}
		}

		/**
		 * Get a list of default hooks to load - these will ALWAYS be on
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.10
		 * @return array;
		 */
		private function getDefaultHooks() {
			$defaultHooks                       = Array();
			$defaultHooks['word_press_admin'][] = 'createAdminMenu';
			$defaultHooks['word_press_admin'][] = 'enableSessions';

			return $defaultHooks;
		}

		/**
		 * Check if a given controller and method is in the default list of hooks
		 *
		 * @param string $controller Name of the controller without the prefix and suffix
		 * @param string $method Method in the controller to check
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return boolean;
		 */
		public function isHookDefault( $controller, $method ) {
			$defaultHooks = $this->getDefaultHooks();
			if ( isset( $defaultHooks[ $controller ] ) && in_array( $method, $defaultHooks[ $controller ] ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Include a specific hook file into memory
		 *
		 * @param string $file Name of the hook file to load
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return void;
		 */
		private function loadHookFile( $file ) {
			$filename = $this->getHookFilename( $file );
			include_once( $this->_path . 'hooks/' . $filename );
		}

		/**
		 * Get the official name of a hook to be used to initiate the class object
		 *
		 * @param string $name Name of the hook to return
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return string;
		 */
		private function getHookName( $name ) {
			// Strip out parts of a file if it is sent through like that
			if ( substr( $name, 0, 5 ) == 'hook_' ) {
				$name = substr( $name, 5 );
			}
			if ( substr( $name, - 4 ) == '.php' ) {
				$name = substr( $name, 0, strlen( $name ) - 4 );
			}

			// Return the full name of the hook object
			return 'toolboxHook' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $name ) ) );
		}

		/**
		 * Get the physical name of a hooks file
		 *
		 * @param string $name Name of the hook
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return string;
		 */
		private function getHookFilename( $name ) {
			// Only need to do this if passed through a basic name i.e. from a WordPress option
			if ( strpos( $name, '.php' ) ) {
				return $name;
			} else {
				return 'hook_' . $name . '.php';
			}
		}

		/**
		 * @return string
		 */
		public function getPrefix() {
			return $this->_prefix;
		}

		/**
		 * Get all possible hook files in this installation
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return array;
		 */
		public function getAvailableHookControllers() {
			$hooks = array();
			if ( $dh = opendir( $this->_path . 'hooks' ) ) {
				while ( ( $file = readdir( $dh ) ) !== false ) {
					if ( substr( $file, 0, 5 ) == 'hook_' && substr( $file, - 4 ) == '.php' ) {
						$this->loadHookFile( $file );
						$hookName           = $this->getHookName( $file );
						$hooks[ $hookName ] = new $hookName( $this );
					}
				}
				closedir( $dh );
				asort( $hooks ); // Sort the module names, keeping the moduleids as indexes
			}

			return $hooks;
		}

		/**
		 * Add a setting into the main plugins settings page
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return void;
		 */
		public function addSetting( $settingField, $sectionName ) {
			$hookField = new toolboxFieldModel( $settingField );
			// Temporarily set the setting in this object so we can reference once the callback is reached
			$this->_setting[ $sectionName ][] = $hookField;
			add_filter( $this->getPrefix() . 'settings', function ( $settings ) {
				// Add the setting
				$settings[] = $this->_setting;
				// Reset the temporary setting
				$this->_setting = Array();

				// Return the settings
				return $settings;
			} );
		}

		/**
		 * Check if a given controller and method has been enabled
		 *
		 * @param string $hook Name of the hook controller file
		 * @param string $method Method in the controller to check
		 *
		 * @author Nigel Wells
		 * @version 0.2.1.16.10.07
		 * @return boolean;
		 */
		public function isHookEnabled( $hook, $method ) {
			if ( isset( $this->_enabled[ $hook ] ) && in_array( $method, $this->_enabled[ $hook ] ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get a controller that has been declared so we can use its functions somewhere else
		 *
		 * @param string $hookName The name of the controller to get
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.10
		 * @return mixed
		 */
		function getObject( $hookName ) {
			$hookName = $this->getHookName( $hookName );
			if ( isset( $this->_objects[ $hookName ] ) ) {
				return $this->_objects[ $hookName ];
			} else {
				return false;
			}
		}

		/**
		 * Wrapper for hook_word_press_admin to more easily add menu items when required
		 *
		 * @param string $name Menu label
		 * @param array $callback Array containing the controller and callback function
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.10
		 * @return void;
		 */
		function addPage( $name, $callback ) {
			$adminHook = $this->getObject( 'word_press_admin' );
			$adminHook->addPage( $name, $callback );
		}

		/**
		 * Prepends the prefix to any shortcode request
		 *
		 * @author Nigel Wells
		 * @version 0.3.8.16.12.21
		 * @return string
		 */
		function getShortCode($label) {
			return $this->getPrefix() . $label;
		}

	}
}