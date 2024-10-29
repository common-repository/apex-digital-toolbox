<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookWordPressFront' ) ) {
    class toolboxHookWordPressFront extends toolboxHookController {

        function __construct( $Toolbox ) {
            parent::__construct( $Toolbox );
            $this->setLabel( 'WordPress Frontend' );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'body_class', 'deviceDetection', array(
                'label'       => 'Device Detection',
                'description' => 'Add a class to the body tag of every page containing the OS and device specific tag if available i.e ios ipad. Very useful if you need to target specific devices with CSS.',
                'args'        => 2,
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'fixDomainRedirects', array(
                'label'       => 'Fix Domain Redirects',
                'description' => 'Fix Domain Redirects when the website is being used on a domain that doesn\'t match the one entered in WordPress general settings. Note: This won\'t affect the WordPress administration area as you need to be able to change the URL if you are migrating sites or changing domains.'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'adjustStylesheetPlacement', array(
                'label'       => 'Adjust Stylesheet Placement',
                'description' => 'Force any stylesheet to be placed after everything else - useful for when plugin styles keep overwriting your theme ones through inheritance.'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'setupYouTubeHooks', array(
                'label'       => 'YouTube Embedded Videos',
                'description' => 'Adapts the embed code to allow switching off of various elements.',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'setupYearShortCode', array(
                'label'       => 'Current Year Short code',
                'description' => 'Simple hook to allow the current year to be dynamically added to a page/widget - useful for copyright notices. <code>[' . $this->Toolbox->getShortCode( 'current_year' ) . ']</code>',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'setupPageTitleShortCode', array(
                'label'       => 'Page Title Short code',
                'description' => 'Simple hook to output the current page title - useful for posting current page title to another page i.e. contact us form. <code>[' . $this->Toolbox->getShortCode( 'page_title' ) . ']</code>',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'wp', 'removePageOneLink', array(
                'label'       => 'Fix page one link in pagination',
                'description' => 'Fix issue in pagination that shows page one as <code>.../page/1/</code> which is a duplicate URL - this hook fixes that link',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'initTaxonomyHeadingField', array(
                'label'       => 'Add alternative heading field to taxonomies',
                'description' => 'Creates a new field that can be used as the <code>&lt;h1&gt;...&lt;/h1&gt;</code> value on taxonomy pages',
            ) );
        }

        public function initTaxonomyHeadingField() {
            $taxonomies = [ 'product_cat' ];
            foreach ( $taxonomies as $taxonomy ) {
                add_action( $taxonomy . '_edit_form_fields', [
                    $this,
                    'taxonomy_edit_form_fields'
                ] );
                add_action( $taxonomy . '_add_form_fields', [
                    $this,
                    'taxonomy_edit_form_fields'
                ] );
                add_action( 'edited_' . $taxonomy, [
                    $this,
                    'saved_taxonomy'
                ] );
                add_action( 'created_' . $taxonomy, [
                    $this,
                    'saved_taxonomy'
                ] );
            }
            add_filter( 'single_term_title', [ $this, 'single_term_title' ] );
        }

        public function single_term_title( $name ) {
            /** @var WP_Term $object */
            $term = get_queried_object();
            if ( is_archive() ) {
                $alternative_name = get_term_meta( $term->term_id, 'alternative_taxonomy_page_title', true );
                if ( ! empty( $alternative_name ) ) {
                    return $alternative_name;
                }
            }

            return $name;
        }

        public function taxonomy_edit_form_fields( $term ) {
            $page_title = '';
            if ( ! empty( $term ) ) {
                $page_title = get_term_meta( $term->term_id, 'alternative_taxonomy_page_title', true );
            }
            ?>
            <tr class="form-field">
                <th><label>Alternative Page Title</label></th>
                <td>
                    <input type="text" name="alternative_taxonomy_page_title"
                           value="<?php echo esc_attr( $page_title ); ?>">
                </td>
            </tr>
            <?php
        }

        public function saved_taxonomy( $term_id ) {
            if ( ! isset( $_POST['alternative_taxonomy_page_title'] ) ) {
                return;
            }
            update_term_meta( $term_id, 'alternative_taxonomy_page_title', $_POST['alternative_taxonomy_page_title'] );
        }

        /**
         * Detect user device and OS based off their user agent string
         * Script adapted from: http://www.schiffner.com/code-snippets/php-mobile-device-detection/
         *
         * @param array $classes Classes to be set in the opening body tag
         *
         * @return array;
         * @version 0.3.6.16.11.01
         * @author Nigel Wells
         */
        public function deviceDetection( $classes = array() ) {
            //initialize all known devices as false
            $iPod             = false;
            $iPhone           = false;
            $iPad             = false;
            $iOS              = false;
            $webOSPhone       = false;
            $webOSTablet      = false;
            $webOS            = false;
            $BlackBerry9down  = false;
            $BlackBerry10     = false;
            $RimTablet        = false;
            $BlackBerry       = false;
            $NokiaSymbian     = false;
            $Symbian          = false;
            $Mac              = false;
            $AndroidTablet    = false;
            $AndroidPhone     = false;
            $Android          = false;
            $WindowsPhone     = false;
            $WindowsTablet    = false;
            $Windows          = false;
            $Tablet           = false;
            $Phone            = false;
            $InternetExplorer = false;
            $Safari           = false;
            $SamsungBrowser   = false;
            $Chrome           = false;
            $MicrosoftEdge    = false;

            //Detect special conditions devices & types (tablet/phone form factor)
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "iPod" ) ) {
                $iPod  = true;
                $Phone = true;
                $iOS   = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "iPhone" ) ) {
                $iPhone = true;
                $Phone  = true;
                $iOS    = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "iPad" ) ) {
                $iPad   = true;
                $Tablet = true;
                $iOS    = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "webOS" ) ) {
                $webOS = true;
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Pre" ) || stripos( $_SERVER['HTTP_USER_AGENT'], "Pixi" ) ) {
                    $webOSPhone = true;
                    $Phone      = true;
                }
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "TouchPad" ) ) {
                    $webOSTablet = true;
                    $Tablet      = true;
                }
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "BlackBerry" ) ) {
                $BlackBerry      = true;
                $BlackBerry9down = true;
                $Phone           = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "BB10" ) ) {
                $BlackBerry   = true;
                $BlackBerry10 = true;
                $Phone        = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "RIM Tablet" ) ) {
                $BlackBerry = true;
                $RimTablet  = true;
                $Tablet     = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "SymbianOS" ) ) {
                $Symbian      = true;
                $NokiaSymbian = true;
                $Phone        = true;
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Android" ) ) {
                $Android = true;
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "mobile" ) ) {
                    $AndroidPhone = true;
                    $Phone        = true;
                } else {
                    $AndroidTablet = true;
                    $Tablet        = true;
                }
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "samsungbrowser" ) ) {
                    $SamsungBrowser = true;
                }
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Windows" ) ) {
                $Windows = true;
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Touch" ) ) {
                    $WindowsTablet = true;
                    $Tablet        = true;
                }
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Windows Phone" ) ) {
                    $WindowsPhone = true;
                    $Phone        = true;
                }
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "MSIE" ) ) {
                    $InternetExplorer = true;
                } elseif ( preg_match( '/Trident\/7.0; rv:11.0/', $_SERVER['HTTP_USER_AGENT'] ) ) {
                    $InternetExplorer = true;
                } elseif ( preg_match( '/Edge\//', $_SERVER['HTTP_USER_AGENT'] ) ) {
                    $MicrosoftEdge = true;
                }
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Mac OS" ) ) {
                $Mac = true;
                if ( stripos( $_SERVER['HTTP_USER_AGENT'], "Safari" ) && stripos( $_SERVER['HTTP_USER_AGENT'], "chrome" ) === false ) {
                    $Safari = true;
                }
            }
            if ( stripos( $_SERVER['HTTP_USER_AGENT'], "chrome" ) ) {
                $Chrome = true;
            }

            // Target form factors
            if ( $Phone ) {
                $classes[] = 'phone';
            } else if ( $Tablet ) {
                $classes[] = 'tablet';
            } else {
                $classes[] = 'desktop';
            }

            // Target operating systems
            if ( $iOS ) {
                $classes[] = 'ios';
            } else if ( $Android ) {
                $classes[] = 'android';
            } else if ( $Windows ) {
                $classes[] = 'windows';
            } else if ( $BlackBerry ) {
                $classes[] = 'blackberry';
            } else if ( $webOS ) {
                $classes[] = 'webos';
            } else if ( $Symbian ) {
                $classes[] = 'symbian';
            } else if ( $Mac ) {
                $classes[] = 'macos';
            } else {
            }

            //Target individual devices
            if ( $iPod || $iPhone ) {
                $classes[] = 'iphone';
            } else if ( $iPad ) {
                $classes[] = 'ipad';
            } else if ( $AndroidPhone ) {
                //we're an Android Phone -- do something here
            } else if ( $AndroidTablet ) {
                //we're an Android Tablet -- do something here
            } else if ( $WindowsPhone ) {
                //we're an Windows Phone -- do something here
            } else if ( $WindowsTablet ) {
                //we're an Windows Tablet -- do something here
                $classes[] = 'touch';
            } else if ( $webOSPhone ) {
                //we're a webOS phone -- do something here
            } else if ( $webOSTablet ) {
                //we're a webOS tablet -- do something here
            } else if ( $BlackBerry9down ) {
                //we're an outdated BlackBerry phone -- do something here
            } else if ( $BlackBerry10 ) {
                //we're an new BlackBerry phone -- do something here
            } else if ( $RimTablet ) {
                //we're a RIM/BlackBerry Tablet -- do something here
            } else if ( $NokiaSymbian ) {
                //we're a Nokia Symbian device -- do something here
            } else {
                //we're not a known device.
            }

            // Target browsers
            if ( $InternetExplorer ) {
                $classes[] = 'msie';
            } elseif ( $MicrosoftEdge ) {
                $classes[] = 'msedge';
            } elseif ( $Safari ) {
                $classes[] = 'safari';
            } elseif ( $SamsungBrowser ) {
                $classes[] = 'samsungbrowser';
            } elseif ( $Chrome ) {
                $classes[] = 'chrome';
            }

            // Return the updated classes list
            return $classes;
        }

        /**
         * Adjust pagination to remove page 1 as a link and just use the root URL
         * - For some reason, adding the hook directly resulted in $link not being passed to it causing a fatal error
         */
        public function removePageOneLink() {
            add_filter( 'paginate_links', function ( $link ) {
                $pos = strpos( $link, 'page/1/' );
                if ( $pos !== false ) {
                    $link = substr( $link, 0, $pos );
                }

                return $link;
            } );
        }

        /**
         * Fix Domain Redirects when the website is being used on a domain that does not match the one entered in WordPress general settings
         * Note: This won't affect the WordPress administration area as you need to be able to change the URL if you are migrating sites or changing domains.
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @author Nigel Wells
         */
        public function fixDomainRedirects( $args = array() ) {
            // Only do this outside of the admin area
            if ( ! is_admin() && php_sapi_name() !== 'cli' && ! $this->isAJAX() ) {
                $siteURL    = esc_url( get_option( 'home' ) );
                $isHTTPS    = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on';
                $requestUri = esc_url( 'http' . ( $isHTTPS ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                if ( $siteURL && substr( $requestUri, 0, strlen( $siteURL ) ) != $siteURL ) {
                    header( "HTTP/1.1 301 Moved Permanently" );
                    header( 'Location: ' . $siteURL . $_SERVER['REQUEST_URI'] );
                    die();
                }
            }
        }

        /**
         * Create setting for adjusting stylesheet placement and setup action hook to do it
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @author Nigel Wells
         */
        public function adjustStylesheetPlacement( $args = array() ) {
            // Create hooks depending on where we are at
            if ( is_admin() ) {
                $this->Toolbox->addSetting( array(
                    'name'        => 'adjust_stylesheet_placement',
                    'label'       => 'ID of Enqueue',
                    'type'        => 'string',
                    'value'       => $this->Toolbox->getOption( 'adjust_stylesheet_placement' ),
                    'description' => 'ID given to the stylesheet when registering it via <code>wp_enqueue_style()</code>'
                ), $this->getLabel() );
            }
            add_action( 'wp_print_styles', array( $this, 'adjustPrintStyles' ), 99 );
        }

        /**
         * Force any stylesheet to be placed after everything else - useful for when plugin styles keep overwriting your theme ones through inheritance
         *
         * @return void;
         * @author Nigel Wells
         */
        public function adjustPrintStyles() {
            global $wp_styles;

            $adjustStylesheetPlacement = $this->Toolbox->getOption( 'adjust_stylesheet_placement' );
            if ( ! $adjustStylesheetPlacement ) {
                return;
            }

            $keys   = [];
            $keys[] = $adjustStylesheetPlacement;

            foreach ( $keys as $currentKey ) {
                $keyToSplice = array_search( $currentKey, $wp_styles->queue );

                if ( $keyToSplice !== false && ! is_null( $keyToSplice ) ) {
                    $elementToMove      = array_splice( $wp_styles->queue, $keyToSplice, 1 );
                    $wp_styles->queue[] = $elementToMove[0];
                }

            }

            return;
        }

        /**
         * Sets up settings for YouTube videos and adds filter for the front end
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         */
        public function setupYouTubeHooks( $args = array() ) {
            // Create hooks depending on where we are at
            if ( is_admin() ) {
                // Create settings
                $this->Toolbox->addSetting( array(
                    'name'        => 'youtube_related_videos',
                    'label'       => 'Remove Related Videos',
                    'type'        => 'checkbox',
                    'range'       => [ 1 => 'Yes' ],
                    'value'       => $this->Toolbox->getOption( 'youtube_related_videos' ),
                    'description' => 'Remove the related videos at the end of the video'
                ), 'YouTube' );
                $this->Toolbox->addSetting( array(
                    'name'        => 'youtube_title',
                    'label'       => 'Remove Title',
                    'type'        => 'checkbox',
                    'range'       => [ 1 => 'Yes' ],
                    'value'       => $this->Toolbox->getOption( 'youtube_title' ),
                    'description' => 'Remove the title from the top of the video'
                ), 'YouTube' );
                $this->Toolbox->addSetting( array(
                    'name'        => 'youtube_controls',
                    'label'       => 'Remove playback controls',
                    'type'        => 'checkbox',
                    'range'       => [ 1 => 'Yes' ],
                    'value'       => $this->Toolbox->getOption( 'youtube_controls' ),
                    'description' => 'Remove the the playback controls from the bottom of the video - you can still play and pause by clicking the video'
                ), 'YouTube' );
            } else {
                add_filter( 'embed_oembed_html', [ $this, 'adjustYouTubeEmbed' ], 4, 10 );
            }
        }

        /**
         * Adapts the embed code to allow switching off of various elements.
         *
         * @return string;
         */
        public function adjustYouTubeEmbed( $cache, $url, $attr, $post_ID ) {
            if ( false !== strpos( $url, 'youtube.com' ) ) {
                $prefix = 'feature=oembed';
                if ( $this->Toolbox->getOption( 'youtube_related_videos' ) ) {
                    $cache = str_replace( $prefix, $prefix . '&rel=0', $cache );
                }
                if ( $this->Toolbox->getOption( 'youtube_title' ) ) {
                    $cache = str_replace( $prefix, $prefix . '&showinfo=0', $cache );
                }
                if ( $this->Toolbox->getOption( 'youtube_controls' ) ) {
                    $cache = str_replace( $prefix, $prefix . '&controls=0', $cache );
                }
            }

            return $cache;
        }

        /**
         * Setup the current year short code
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @author Nigel Wells
         */
        public function setupYearShortCode( $args = array() ) {
            add_shortcode( $this->Toolbox->getShortCode( 'current_year' ), array(
                $this,
                'yearShortCode'
            ) );
        }

        /**
         * Short code handler for the current year
         *
         * @param $atts array
         *
         * @return string;
         * @author Nigel Wells
         *
         */
        function yearShortCode( $atts ) {
            return date( 'Y' );
        }

        /**
         * Setup the current year short code
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @author Nigel Wells
         */
        public function setupPageTitleShortCode( $args = array() ) {
            add_shortcode( $this->Toolbox->getShortCode( 'page_title' ), array(
                $this,
                'pageTitleShortCode'
            ) );
        }


        /**
         * Simple hook to output the current page title
         *
         * @param $atts array
         *
         * @return string;
         * @author Nigel Wells
         *
         */
        function pageTitleShortCode( $atts ) {
            global $post;
            $object = get_queried_object();
            if ( is_search() ) {
                $title = 'Search Results';
            } elseif ( is_post_type_archive( 'product' ) ) {
                $title = 'Shop Online';
            } elseif ( is_archive() ) {
                $title = single_cat_title( '', false );
            } elseif ( function_exists( 'is_account_page' ) && is_account_page() ) {
                $title = get_the_title();
                if ( ! is_user_logged_in() ) {
                    $title .= ' - Login';
                } else {
                    $menuItems = wc_get_account_menu_items();
                    foreach ( $menuItems as $endpoint => $menuLabel ) {
                        if ( ! is_wc_endpoint_url( $endpoint ) ) {
                            continue;
                        }
                        $title .= ' - ' . $menuLabel;
                    }
                }
            } elseif ( is_page() ) {
                if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
                    $title = 'Order Received';
                } else {
                    $title = get_the_title();
                }
            } elseif ( ! is_single() || ( is_single() && $post->post_type == 'post' ) ) {
                $title = $object->post_title;
            } else {
                $title = get_the_title();
            }

            // Encode the page for URLs if needed
            if ( isset( $atts['encode'] ) && $atts['encode'] === true ) {
                $title = rawurlencode( $title );
            }

            return $title;
        }

        /**
         * Check if WordPress is executing an AJAX call
         *
         * @return bool
         */
        private function isAJAX() {
            return defined( 'DOING_AJAX' ) && DOING_AJAX;
        }

    }
}
