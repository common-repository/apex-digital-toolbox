<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookModel' ) ) {
	class toolboxHookModel {
		private $_type = 0;
		private $_hook = '';
		private $_method = '';
		private $_args = Array();

		function __construct( $type, $hook, $method, $args = Array() ) {
			$this->_type   = $type;
			$this->_hook   = $hook;
			$this->_method = $method;
			$this->_args   = $args;
		}

		/**
		 * @return string
		 */
		public function getMethod() {
			return $this->_method;
		}

		/**
		 * @return string
		 */
		public function getHook() {
			return $this->_hook;
		}

		/**
		 * @return int
		 */
		public function getType() {
			return $this->_type;
		}

		/**
		 * Register this hook with WordPress
		 *
		 * @param object $controller Controller that contains the method for the callback
		 *
		 * @author Nigel Wells
		 * @version 0.3.9.17.03.21
		 * @return void;
		 */
		public function createHook( $controller ) {
			if ( $this->getType() == APEX_TOOLBOX_HOOK_ACTION ) {
				add_action( $this->getHook(), Array( $controller, $this->getMethod() ), $this->getPriority(), $this->acceptedArgs() );
			} elseif ( $this->getType() == APEX_TOOLBOX_HOOK_FILTER ) {
				add_filter( $this->getHook(), Array( $controller, $this->getMethod() ), $this->getPriority(), $this->acceptedArgs() );
			}
		}

		/**
		 * @return array
		 */
		public function getArgs( $arg = '' ) {
			if ( $arg ) {
				if ( isset( $this->_args[ $arg ] ) ) {
					return $this->_args[ $arg ];
				} else {
					return '';
				}
			} else {
				return $this->_args;
			}
		}

		/**
		 * Get a label for this hook
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return string;
		 */
		public function getLabel() {
			$label = $this->getArgs( 'label' );
			if ( ! $label ) {
				$label = $this->getMethod();
			}

			return $label;
		}

		/**
		 * Get a description for this hook
		 *
		 * @author Nigel Wells
		 * @version 0.1.1.16.10.07
		 * @return string;
		 */
		public function getDescription() {
			return $this->getArgs( 'description' );
		}

		/**
		 * Get the priority for this hook
		 *
		 * @author Nigel Wells
		 * @version 0.3.1.16.10.10
		 * @return int;
		 */
		private function getPriority() {
			$priority = intval( $this->getArgs( 'priority' ) );
			if ( ! $priority ) {
				$priority = 10;
			}
			return $priority;
		}

		/**
		 * Get the total number of accepted arguments for this hook
		 *
		 * @author Nigel Wells
		 * @version 0.3.9.17.03.21
		 * @return int;
		 */
		private function acceptedArgs() {
			$args = intval( $this->getArgs( 'args' ) );
			if ( ! $args ) {
				$args = 0;
			}
			return $args;
		}
	}
}