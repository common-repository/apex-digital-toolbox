<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxFieldModel' ) ) {
	class toolboxFieldModel {
		private $_prefix = 'apex_toolbox_';
		private $_name = '';
		private $_label = '';
		private $_description = '';
		private $_value = '';
		private $_range = [];
		private $_type = '';

		public function __construct( $args ) {
			if ( isset( $args['name'] ) ) {
				$this->setName( $args['name'] );
			}
			if ( isset( $args['label'] ) ) {
				$this->setLabel( $args['label'] );
			}
			if ( isset( $args['description'] ) ) {
				$this->setDescription( $args['description'] );
			}
			if ( isset( $args['value'] ) ) {
				$this->setValue( $args['value'] );
			}
			if ( isset( $args['range'] ) ) {
				$this->setRange( $args['range'] );
			}
			if ( isset( $args['type'] ) ) {
				$this->setType( $args['type'] );
			}
		}

		/**
		 * @return string
		 */
		public function getName() {
			return $this->_name;
		}

		/**
		 * @param string $name
		 */
		public function setName( $name ) {
			$this->_name = $name;
		}

		/**
		 * @return string
		 */
		public function getLabel() {
			$label = $this->_label;
			if ( ! $label ) {
				$label = ucwords( str_replace( '_', ' ', $this->getName() ) );
			}

			return $label;
		}

		/**
		 * @param string $label
		 */
		public function setLabel( $label ) {
			$this->_label = $label;
		}

		/**
		 * @return string
		 */
		public function getDescription() {
			return $this->_description;
		}

		/**
		 * @param string $description
		 */
		public function setDescription( $description ) {
			$this->_description = $description;
		}

		/**
		 * @return mixed
		 */
		public function getValue() {
			$value = $this->_value;
			switch ( $this->getType() ) {
				case 'checkbox' :
					break;
				default :
					$value = stripslashes($value);
			}
			return $value;
		}

		/**
		 * @param string $value
		 */
		public function setValue( $value ) {
			// Validate the value a little
			switch ( $this->getType() ) {
				case 'url' :
					$value = esc_url( $value );
					break;
				default :
					if(is_string($value)) $value = esc_attr( $value );
			}
			$this->_value = $value;
		}

		/**
		 * @return array
		 */
		public function getRange() {
			return $this->_range;
		}

		/**
		 * @param string $range
		 */
		public function setRange( $range ) {
			$this->_range = $range;
		}

		/**
		 * @return string
		 */
		public function getType() {
			// Tidy up the type
			$type = trim( strtolower( $this->_type ) );
			// Valid field types
			$valid = Array( 'input', 'url', 'checkbox', 'textarea' );
			// Default invalid types to text fields
			if ( ! in_array( $type, $valid ) ) {
				$type = 'text';
			}

			return $type;
		}

		/**
		 * @param string $type
		 */
		public function setType( $type ) {
			$this->_type = $type;
		}

		/**
		 * Output field settings in admin area
		 *
		 * @author Nigel Wells
		 * @return string;
		 */
		public function outputField() {
			$input = '';
			$descriptionAbove = false;
			switch ( $this->getType() ) {
				case 'checkbox' :
					$descriptionAbove = true;
					foreach($this->getRange() as $index => $range) {
						$input .= '<label><input type="checkbox" name="' . $this->getPrefix() . $this->getName() . '[]" value="' . $index . '"' . ( in_array( $index, $this->getValue() ) ? ' checked="checked"' : '' ) . ' /> ' . $range . '</label><br />';
					}
					break;
				case 'textarea' :
					$input = '<textarea rows="5" class="regular-text code" name="' . $this->getPrefix() . $this->getName() . '">' . $this->getValue() . '</textarea>';
					break;
				case 'url' :
				case 'text' :
					$input = '<input type="' . $this->getType() . '" class="regular-text code" name="' . $this->getPrefix() . $this->getName() . '" value="' . $this->getValue() . '" />';
			}
			$html = $input;
			if ( $this->getDescription() ) {
				if($descriptionAbove) {
					$html = '<p class="description">' . $this->getDescription() . '</p>' . $html;
				} else {
					$html .= '<p class="description">' . $this->getDescription() . '</p>';
				}
			}

			return $html;
		}

		/**
		 * @return string
		 */
		public function getPrefix() {
			return $this->_prefix;
		}
	}
}