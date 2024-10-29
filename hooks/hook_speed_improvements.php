<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookSpeedImprovements' ) ) {
    class toolboxHookSpeedImprovements extends toolboxHookController {
        private $inlineJS = [];
        private $inlineCSS = [];

        function __construct( $Toolbox ) {
            parent::__construct( $Toolbox );
            $this->setLabel( 'Speed Improvements' );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'wp_enqueue_scripts', 'inlineJQuery', array(
                'label'       => 'Inline jQuery',
                'description' => 'Outputs jQuery directly in the page source code rather than having it as a downloadable asset',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'wp_enqueue_scripts', 'dequeueBlockLibrary', array(
                'label'       => 'Remove Block Libraries',
                'description' => 'Removes all WordPress block CSS files from loading - not needed when using Elementor',
                'priority'    => 1000000
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'init', 'inlineStyles', array(
                'label'       => 'Inline CSS Stylesheets',
                'description' => 'Output stylesheets inline rather than loading an external resource i.e. small theme files',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'wp', 'setupInlineJavaScriptHooks', array(
                'label'       => 'Output Inline JavaScript',
                'description' => 'Outputs any inline JavaScript files',
            ) );
        }


        /**
         * Setup the site map settings or register the shortcode if needed
         *
         * @param array $args Any arguments passed to the callback
         */
        public function inlineJQuery( $args = [] ) {
            $this->inlineJS( 'jquery-core' );
        }

        private function inlineJS( $handle ) {
            $this->inlineJS[] = $handle;
        }

        public function inlineStyles( $args = [] ) {
            if ( is_admin() ) {
                $this->Toolbox->addSetting( array(
                    'name'        => 'inline_css',
                    'label'       => 'CSS Handles',
                    'type'        => 'text',
                    'value'       => $this->Toolbox->getOption( 'inline_css' ),
                    'description' => 'Enter the CSS handle(s) (comma separated) that are to be outputted inline in the head tag.'
                ), $this->getLabel() );
            } else {
                if ( ( $this->Toolbox->getOption( 'inline_css' ) ) ) {
                    $handles = explode( ',', $this->Toolbox->getOption( 'inline_css' ) );
                    // Trim them up in case spaces were entered
                    $handles = array_map( 'trim', $handles );
                    foreach ( $handles as $handle ) {
                        $this->inlineCSS( $handle );
                    }
                }
            }
        }

        private function inlineCSS( $handle ) {
            $this->inlineCSS[] = $handle;
        }

        public function dequeueBlockLibrary() {
            $this->dequeueStyles( [
                'wp-block-library',
                'wc-blocks-vendors-style',
                'wc-blocks-style'
            ] );
        }

        private function dequeueStyles( $styles ) {
            $styles = apply_filters( 'apex_toolbox_dequeue_styles', $styles );
            foreach ( $styles as $handle ) {
                wp_deregister_style( $handle );
            }
        }

        public function setupInlineJavaScriptHooks( $args = [] ) {
            if ( is_admin() ) {
                return;
            }
            add_action( 'wp_enqueue_scripts', [
                $this,
                'processEnqueueSpeedImprovements'
            ], 10000000 );
            add_filter( 'script_loader_tag', [ $this, 'filterScriptTagOutput' ], 10, 2 );
            add_filter( 'style_loader_tag', [ $this, 'filterStyleTagOutput' ], 10, 2 );
        }

        public function filterScriptTagOutput( $html, $handle ) {
            if ( is_admin() ) {
                return $html;
            }

            if ( in_array( $handle, $this->inlineJS ) ) {
                return '';
            }

            return $html;
        }

        public function filterStyleTagOutput( $html, $handle ) {
            if ( is_admin() ) {
                return $html;
            }

            if ( in_array( $handle, $this->inlineCSS ) ) {
                return '';
            }

            return $html;
        }


        public function processEnqueueSpeedImprovements( $args = [] ) {
            global $wp_scripts, $wp_styles;

            foreach (
                [
                    'JS'  => [ $wp_scripts, $this->inlineJS ],
                    'CSS' => [ $wp_styles, $this->inlineCSS ]
                ] as $type => $typeData
            ) {
                foreach ( $typeData[1] as $handle ) {
                    if ( empty( $typeData[0]->{'registered'}[ $handle ] ) ) {
                        continue;
                    }
                    $parse = wp_parse_url( $typeData[0]->{'registered'}[ $handle ]->{'src'} );
                    if ( empty( $parse['path'] ) ) {
                        continue;
                    }
                    $fullPath = ABSPATH . $parse['path'];
                    echo '<!-- Start output ' . $type . ' handle [' . $handle . ' - ' . $parse['path'] . '] -->
' . ( $type == 'JS' ? '<script type="text/javascript">' : '<style>' ) . '
' . file_get_contents( $fullPath ) . '
' . ( $type == 'JS' ? '</script>' : '</style>' ) . '
<!-- End ' . $type . ' Handle [' . $handle . '] -->
';
                }
            }
        }
    }
}
