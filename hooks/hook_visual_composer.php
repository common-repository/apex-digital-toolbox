<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookVisualComposer' ) ) {
	class toolboxHookVisualComposer extends toolboxHookController {
		function __construct( $Toolbox ) {
			parent::__construct( $Toolbox );
			$this->setLabel( 'Visual Composer' );
			// Only need to worry about adding hooks if visual composer is actually installed
			$installed = false;
			if ( function_exists( 'vc_map' ) ) {
				$installed = true;
			}
			$this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'vc_load_default_templates', 'removeDefaultTemplates', Array(
				'label'       => 'Remove Default Templates' . (!$installed ? ' (VISUAL COMPOSER NOT INSTALLED)' : ''),
				'description' => 'Cleans up Visual Composer templates area so that it only contains new templates added via the theme'
			) );
			$this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'visualComposerSetup', Array(
				'label'       => 'Load additional include files' . (!$installed ? ' (VISUAL COMPOSER NOT INSTALLED)' : ''),
				'description' => 'Scans a theme directory for additional Visual Composer include files that utilise the vc_map() function'
			) );
		}

		/**
		 * Remove default templates bundled with Visual Composer
		 *
		 * @param array $args Any arguments passed to the callback
		 *
		 * @author Nigel Wells
		 * @version 0.3.5.16.11.01
		 * @return array;
		 */
		public function removeDefaultTemplates( $args = Array() ) {
			return array();
		}

		/**
		 * Setup the site map settings or register the shortcode if needed
		 *
		 * @param array $args Any arguments passed to the callback
		 *
		 * @author Nigel Wells
		 * @version 0.3.9.17.03.17
		 * @return void;
		 */
		public function visualComposerSetup( $args = Array() ) {
			// Create hooks depending on where we are at
			if ( is_admin() ) {
				// Create settings
				$filesFound = '';
				foreach ( $this->locateVCTemplates() as $file ) {
					$filesFound .= $file . '<br />';
				}
				if ( $filesFound ) {
					$filesFound = '<br /><strong>Files found:</strong><br /><code>' . $filesFound . '</code>';
				}
				$this->Toolbox->addSetting( Array(
					'name'        => 'vc_includes',
					'label'       => 'Visual Composer Include',
					'type'        => 'string',
					'value'       => $this->Toolbox->getOption( 'vc_includes' ),
					'description' => 'Directory relative to your themes directory where your Visual Composer templates can be imported from - default direcotry is "vc_templates". Files must begin with "vc_"' . $filesFound
				), $this->getLabel() );
			}
			// Include all the templates if available
			$this->loadVCTemplates();
		}

		/**
		 * Load any Visual Composer templates to map in to the editor
		 *
		 * @author Nigel Wells
		 * @version 1.0.1.17.06.13
		 * @return void
		 */
		private function loadVCTemplates() {
			if ( ! function_exists( 'vc_map' ) || get_template_directory() === get_stylesheet_directory()) {
				return;
			}
			foreach ( $this->locateVCTemplates() as $file ) {
				require_once( $file );
			}
		}

		/**
		 * Locate any files that can be included into visual composer
		 *
		 *
		 * @author Nigel Wells
		 * @version 0.3.9.17.03.17
		 * @return array
		 */
		private function locateVCTemplates() {
			$templates = Array();
			// Set default
			$vcDir = $this->Toolbox->getOption( 'vc_includes' );
			if ( $vcDir && substr( $vcDir, 0, 1 ) !== '/' ) {
				$vcDir = '/' . $vcDir;
			}
			if ( $vcDir && substr( $vcDir, -1) !== '/' ) {
				$vcDir .= '/';
			}
			if ( ! $vcDir ) {
				$vcDir = '/vc_templates/';
			}
			$vcDir = STYLESHEETPATH . $vcDir;
			if ( is_dir( $vcDir ) ) {
				if ( $dh = opendir( $vcDir ) ) {
					while ( ( $file = readdir( $dh ) ) !== false ) {
						if ( substr( $file, 0, 3 ) == 'vc_' && substr( $file, - 4 ) == '.php' ) {
							$templates[] = $vcDir . $file;
						}
					}
				}
			}

			return $templates;
		}
	}
}