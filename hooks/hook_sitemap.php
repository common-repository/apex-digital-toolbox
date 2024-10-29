<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookSitemap' ) ) {
	class toolboxHookSitemap extends toolboxHookController {
		function __construct( $Toolbox ) {
			parent::__construct( $Toolbox );
			$this->setLabel( 'Sitemap Generator' );
			$this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'init', 'sitemapSetup', Array(
				'label'       => 'Enable Sitemap Generator',
				'description' => 'Creates a shortcode to be used for generating HTML sitemap pages'
			) );
		}

		/**
		 * Setup the site map settings or register the shortcode if needed
		 *
		 * @param array $args Any arguments passed to the callback
		 *
		 * @author Nigel Wells
		 * @version 0.3.8.16.12.21
		 * @return void;
		 */
		public function sitemapSetup( $args = Array() ) {
			// Create hooks depending on where we are at
			if ( is_admin() ) {
				add_action( 'admin_menu', Array( $this, 'sitemapSetupAdmin' ) );
			} else {
				// Register shortcode
				if ( $this->Toolbox->isHookEnabled( 'sitemap', 'sitemapSetup' ) ) {
					add_shortcode( $this->Toolbox->getShortCode( 'sitemap' ), Array( $this, 'sitemapShortcode' ) );
				}
			}
		}

		/**
		 * Setup the site map settings
		 *
		 * @param array $args Any arguments passed to the callback
		 *
		 * @author Nigel Wells
		 * @return void;
		 */
		public function sitemapSetupAdmin( $args = Array() ) {
			$this->Toolbox->addPage(
				'Sitemap Generator',
				Array( $this, 'sitemapSetupOutput' )
			);
			// Create settings
			$this->Toolbox->addSetting( Array(
				'name'        => 'sitemap_exclude_ids',
				'label'       => 'Exclude IDs',
				'type'        => 'string',
				'value'       => $this->Toolbox->getOption( 'sitemap_exclude_ids' ),
				'description' => 'Comma seperated list of IDs to exclude from the sitemap'
			), $this->getLabel() );
			$this->Toolbox->addSetting( Array(
				'name'        => 'sitemap_post_types',
				'label'       => 'Post Types',
				'type'        => 'checkbox',
				'range'       => get_post_types(),
				'value'       => $this->Toolbox->getOption( 'sitemap_post_types' ),
				'description' => 'Select which post types to include'
			), $this->getLabel() );
			$this->Toolbox->addSetting( Array(
				'name'        => 'sitemap_taxonomy',
				'label'       => 'Taxonomies',
				'type'        => 'checkbox',
				'range'       => get_taxonomies(),
				'value'       => $this->Toolbox->getOption( 'sitemap_taxonomy' ),
				'description' => 'Select which taxonomies to include'
			), $this->getLabel() );
		}

		/**
		 * Outputs the settings page HTML
		 *
		 * @author Nigel Wells
		 * @return void;
		 */
		function sitemapSetupOutput() {
			echo '<div class="wrap">
				<h1>Apex Toolbox Sitemap Generator</h1>
				' . $this->displayNotices() . '
				<p>Creates a shortcode that can be added to any page to generate a sitemap based on the hierarchy of post types and taxonomies setup on the website. The sitemap page itself will always be excluded from the list of pages.</p>
				<p>Add the shortcode <code>[' . $this->Toolbox->getShortCode( 'sitemap' ) . ']</code> to any page you want the sitemap displayed on.</p>
				
			</div>';
		}

		/**
		 * Shortcode handler for the Sitemap
		 *
		 * @author Nigel Wells
		 * @return string;
		 */
		function sitemapShortcode( $atts ) {
			global $wpdb;
			$a = shortcode_atts( array(
				'exclude' => '',
			), $atts );
			// If nothing specific is mentioned then grab it from the settings
			if ( ! $a['exclude'] ) {
				$a['exclude'] = $this->Toolbox->getOption( 'sitemap_exclude_ids' );
			}
			// Get types to loop through
			$types = $this->Toolbox->getOption( 'sitemap_post_types' );
			// Default to just pages if nothing specific has been set
			if ( empty( $types ) ) {
				$types = Array( 'page' );
			}
			// Exclude any page containing the shortcode
			$sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = "page" AND post_status="publish" AND post_content LIKE "%[' . $this->Toolbox->getShortCode( 'sitemap' ) . '%"';
			if ( $id = $wpdb->get_var( $sql ) ) {
				if ( $a['exclude'] ) {
					$a['exclude'] .= ',';
				}
				$a['exclude'] .= $id;
			}
			// Loop through post types
			$html = '';
			foreach ( $types as $post_type ) {
				$childpages = wp_list_pages([
					'post_type' => $post_type,
                    'sort_column' => 'menu_order',
					'title_li' => '',
					'echo' => 0,
					'exclude' => $a['exclude']
				]);
				if ( $childpages ) {
					$post = get_post_type_object($post_type);
					$html .= '<h2>' . $post->label . '</h2>';
					$html .= '<ul>' . $childpages . '</ul>';
				}
			}
			// Get taxonomy to loop through
			$types = $this->Toolbox->getOption( 'sitemap_taxonomy' );
			// Default to just pages if nothing specific has been set
			foreach ( $types as $taxonomy ) {
				$childpages = wp_list_categories( [
					'taxonomy' => $taxonomy,
					'title_li' => '',
					'echo' => 0
				]);
				if ( $childpages ) {
					$tax = get_taxonomy( $taxonomy );
					$html .= '<h2>' . $tax->label . '</h2>';
					$html .= '<ul>' . $childpages . '</ul>';
				}
			}
			// Encapsulate the results in a div
			if ( $html ) {
				$html = '<div class="' . $this->Toolbox->getPrefix() . 'sitemap">' . $html . '</div>';
			}

			return $html;
		}

	}

}