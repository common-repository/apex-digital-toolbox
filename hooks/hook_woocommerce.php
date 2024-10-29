<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookWoocommerce' ) ) {
    class toolboxHookWoocommerce extends toolboxHookController {
        const PURCHASE_ORDER_NUMBER_META_FIELD = '_purchase_order_number';

        function __construct( $Toolbox ) {
            parent::__construct( $Toolbox );
            $this->setLabel( 'WooCommerce' );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'woocommerce_product_tabs', 'disableWooCommerceReviews', array(
                'label'       => 'Disable reviews on all products',
                'description' => 'Will disable reviews from being available on any product and hide the tab on the product page',
                'priority'    => 98,
                'args'        => 1
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'woocommerce_subcategory_count_html', 'disableCategoryProductCount', array(
                'label'       => 'Disable product count',
                'description' => 'Removes the total products available for a given category',
                'priority'    => 98,
                'args'        => 1
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'disableProductCategoryList', array(
                'label'       => 'Remove product category list',
                'description' => 'Removes the list of categories a product is in on the single product page',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'enhanceJupiterWooCommerce', array(
                'label'       => 'Enhance Jupiter WooCommerce Experience',
                'description' => 'Various tweaks to the category, cart, and checkout pages to improve the user experience',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'accountMenu', array(
                'label'       => 'Account Menu',
                'description' => 'Dynamically populate a menu with WooCommerce dashboard menu items',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'woocommerce_style_smallscreen_breakpoint', 'fixBreakpoint', array(
                'label'       => 'Fix Breakpoint',
                'description' => 'The tablet breakpoint in WooCommerce is 768px whereas all theme settings are 767px - turn this on to change WooCommerce to match the theme',
                'args'        => 1
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'woocommerce_after_add_to_cart_form', 'enableStructuredData', array(
                'label'       => 'Enable Structured Data',
                'description' => 'When using Elementor the hook to output the schema markup isn\'t triggered - enable this hook to have it output'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'relevanssi_search_ok', 'enableProductFiltersRelevanssiCompatability', array(
                'label'       => 'Enable Product Filter Compatability with Relevanssi',
                'description' => 'Allow the Product Filters plugin to use Relevanssi when performing post queries - this allows filtering on the search page when searching for custom fields',
                'args'        => 2
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'wp_head', 'disableProductFiltersScrollOnSafariBackNavigation', array(
                'label'       => "Improve Product Filters plugin back navigation",
                "description" => "The XForWooCommerce Product Filters plugin will scroll back to the top of the products when navigating back to a page with filters as on Safari, this hook disables that behaviour.",
                'priority'    => 0
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'woocommerce_payment_gateways', 'enableCustomPaymentGateway', array(
                'label'       => 'Enable Custom Payment Gateway',
                'description' => 'Enable a custom payment gateway for WooCommerce which works by immediately setting the order status to on-hold',
                'args'        => 1
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'enableWooCommerceSearchRedirect', array(
                'label'       => 'Redirect search to WooCommerce',
                'description' => 'Enable redirects from native WordPress search to WooCommerce search'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'wp_loaded', 'hideWooCommerceMenuItems', array(
                'label'       => 'Hide WooCommerce Account Menu Items',
                'description' => 'Hide specific WooCommerce account menu items e.g. Downloads, Orders, etc',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'wpseo_replacements', 'registerCustomYoastVariables', array(
                'label'       => 'Register Yoast Custom Variables',
                'description' => 'Enable the use of custom yoast variables - currently the only variable is %%primary_category_with_fallback%%',
                'args'        => 2
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'woocommerce_get_script_data', 'disableWCCartFragmentsRequestTimeout', array(
                'label'       => 'Disable WooCommerce Cart Fragments Request Timeout',
                'description' => 'By default WooCommerce Cart Fragments requests timeout after 5000ms (5 seconds) which sometimes is not enough when the server is under load. Enable this hook to disable the timeout.',
                'args'        => 2
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'woocommerce_init', 'unsetInvalidSessionCookie', array(
                'label'       => 'Unset Invalid Session Cookies',
                'description' => 'If a session becomes invalid for some reason, for example if the secret keys of the site are updated, WooCommerce won\'t properly clean up the cookie which can cause bugs'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'enablePurchaseOrderNumberCheckoutField', array(
                'label'       => 'Enable Purchase Order Number Checkout Field',
                'description' => 'Enable a purchase order number field on the checkout page',
            ) );
        }

        /**
         * When using Elementor the hook to output the schema markup isn't triggered - enable this hook to have it output
         *
         * @return void
         */
        public function enableStructuredData() {
            $data = new WC_Structured_Data();
            $data->generate_product_data();
        }

        /**
         * Change the inclusion of WooCommerce small screen CSS to work from the expected breakpoint
         *
         * @param $breakpoint
         *
         * @return string
         */
        public function fixBreakpoint( $breakpoint ): string {
            return '767px';
        }

        /**
         * Will disable reviews from being available on any product and hide the tab on the product page
         *
         * @param array $tabs Tabs available for output
         *
         * @return array;
         * @version 0.4.0.17.04.20
         * @author Nigel Wells
         */
        public function disableWooCommerceReviews( $tabs ) {
            if ( isset( $tabs['reviews'] ) ) {
                unset( $tabs['reviews'] );
            }

            return $tabs;
        }

        public function accountMenu() {
            if ( ! function_exists( 'WC' ) ) {
                return;
            }

            $loginLabel = $this->Toolbox->getOption( "wc_my_account_login_text" );
            $loginLabel = $loginLabel !== false ? $loginLabel : "Login";

            $this->Toolbox->addSetting( array(
                'name'        => 'wc_my_account_login_text',
                'label'       => 'Login Label',
                'type'        => 'string',
                'value'       => $loginLabel,
                'description' => 'The text to show when the user is not logged in. Defaults to "Login"'
            ), $this->getLabel() );

            $myAccountLabel = $this->Toolbox->getOption( "wc_my_account_text" );
            $myAccountLabel = $myAccountLabel !== false ? $myAccountLabel : "My Account";

            $this->Toolbox->addSetting( array(
                'name'        => 'wc_my_account_text',
                'label'       => 'My Account Label',
                'type'        => 'string',
                'value'       => $myAccountLabel,
                'description' => 'The text to show when the user is logged in. Defaults to "My Account"'
            ), $this->getLabel() );

            add_filter( 'wp_get_nav_menu_items', function ( $items, $menu ) use ( $loginLabel, $myAccountLabel ) {
                if ( ! in_array( $menu->slug, [ 'my-account', 'login' ] ) ) {
                    return $items;
                }
                $parent = 0;
                $order  = 1;
                if ( $menu->slug == 'login' ) {
                    if ( is_user_logged_in() ) {
                        $items[] = $this->createNavMenuItem( '<span class="text">' . apply_filters('apex_toolbox_my_account_label', $myAccountLabel) . '</span>', '#', false, 1 );
                        $parent  = $items[ key( $items ) ]->ID;
                        $order ++;
                    } else {
                        $iconSettings = [
                            'value'   => 'fa-user',
                            'library' => 'fa-solid'
                        ];
                        // If not logged in then just return the one item
                        $title = '<span class="text">' . $loginLabel . '</span>';
                        if ( \Elementor\Plugin::instance()->experiments->is_feature_active( 'e_font_icon_svg' ) ) {
                            $iconSettings['value'] = str_replace( 'fa-', 'fas-', $iconSettings['value'] );
                            // TODO: This is needed for the cart icon until Elementor fixes the bug wp_enqueue_style( 'elementor-icons' );
                            $icon = \Elementor\Icons_Manager::render_font_icon( $iconSettings );
                        } else {
                            $icon = '<i class="fas ' . $iconSettings['value'] . '"></i>';
                        }
                        if ( $icon ) {
                            $title .= '<span class="sub-arrow">' . $icon . '</span>';
                        }
                        $items[] = $this->createNavMenuItem( $title, wc_get_page_permalink( 'myaccount' ), false, 1 );

                        return $items;
                    }
                }
                $menuItems            = wc_get_account_menu_items();
                $myAccountUrl         = wc_get_page_permalink( 'myaccount' );
                $current_endpoint_url = is_wc_endpoint_url() ? wc_get_endpoint_url( WC()->query->get_current_endpoint() ) : wc_get_account_endpoint_url( 'dashboard' );
                foreach ( $menuItems as $endpoint => $menuLabel ) {
                    $menuUrl = wc_get_endpoint_url( $endpoint, '', $myAccountUrl );
                    if ( $endpoint == 'customer-logout' ) {
                        $menuUrl .= '?customer-logout=true';
                    } elseif ( $endpoint == 'dashboard' ) {
                        $menuUrl = $myAccountUrl;
                    }
                    $is_active = $current_endpoint_url === $menuUrl;
                    $items[]   = $this->createNavMenuItem( $menuLabel, $menuUrl, $is_active, $order, $parent );
                    $order ++;
                }

                return $items;
            }, 20, 2 );
        }

        private function createNavMenuItem( $title, $url, $is_active, $order, $parent = 0 ): stdClass {
            $classes = [];
            if ( $is_active ) {
                //  page_item page-item-17 current_page_item menu-item-28 nav-item elementskit-mobile-builder-content active
                $classes[] = 'active';
                $classes[] = 'current-menu-item';
            }
            $item                   = new stdClass();
            $item->ID               = 1000000 + $order + $parent;
            $item->db_id            = $item->ID;
            $item->title            = $title;
            $item->url              = $url;
            $item->menu_order       = $order;
            $item->menu_item_parent = $parent;
            $item->type             = '';
            $item->object           = '';
            $item->object_id        = '';
            $item->classes          = $classes;
            $item->target           = '';
            $item->attr_title       = '';
            $item->description      = '';
            $item->xfn              = '';
            $item->status           = '';

            return $item;
        }

        /**
         * Removes the total products available for a given category
         *
         * @return void;
         * @version 0.4.0.17.04.20
         * @author Nigel Wells
         */
        public function disableCategoryProductCount() {
        }

        /**
         * Removes the list of categories a product is in on the single product page
         *
         * @return void;
         * @version 0.4.0.17.04.21
         * @author Nigel Wells
         */
        public function disableProductCategoryList() {
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

            return;
        }

        public function enhanceJupiterWooCommerce() {
            // Create settings
            $this->Toolbox->addSetting( array(
                'name'        => 'wc_shipping_text',
                'label'       => 'Shipping and Delivery text',
                'type'        => 'textarea',
                'value'       => $this->Toolbox->getOption( 'wc_shipping_text' ),
                'description' => 'HTML text to show above the table of shipping options available'
            ), $this->getLabel() );
            $this->Toolbox->addSetting( array(
                'name'        => 'wc_hide_shipping_table',
                'label'       => 'Shipping Table Visibility',
                'type'        => 'checkbox',
                'range'       => [ 1 => 'Yes' ],
                'value'       => $this->Toolbox->getOption( 'wc_hide_shipping_table' ),
                'description' => 'Hide the shipping table options'
            ), $this->getLabel() );

            if ( ! function_exists( 'WC' ) ) {
                return;
            }

            // Setup various hooks
            add_action( 'wp_enqueue_scripts', function () {
                wp_enqueue_style( 'apex-woocommerce-styles', APEX_TOOLBOX_PLUGIN_URL . 'assets/css/apex-woocommerce-styles.min.css?' . filemtime( APEX_TOOLBOX_PLUGIN_PATH . 'assets/css/apex-woocommerce-styles.min.css' ) );
                wp_enqueue_script( 'apex-woocommerce-script', APEX_TOOLBOX_PLUGIN_URL . 'assets/js/apex-woocommerce-scripts.js?' . filemtime( APEX_TOOLBOX_PLUGIN_PATH . 'assets/js/apex-woocommerce-scripts.js' ) );
            } );

            add_action( 'woocommerce_before_main_content', function () {
                if ( $this->isShowingShopFilter() ) {
                    echo '<div class="woocommerce-archive-category-container"><div class="woocommerce-archive-category-filters">';
                    do_action( 'product_filters_plugin' );
                    echo '</div><div class="woocommerce-archive-category-list">';
                }
            }, 50 );
            add_action( 'woocommerce_after_main_content', function () {
                if ( $this->isShowingShopFilter() ) {
                    echo '</div></div>';
                }
            }, 10 );
            add_action( 'template_redirect', function () {
                remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 12 );
                add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 6 );

                remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 11 );
                add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 7 );
                if ( $this->hasShippingDetails() ) {
                    add_action( 'woocommerce_single_product_summary', function () {
                        echo '<a class="woocommerce-shipping-more-details" href="javascript:;" onclick="apex_viewShippingDelivery();">Shipping &amp; Delivery</a>';
                    }, 31 );
                }
            } );
            if ( $this->hasShippingDetails() ) {
                add_filter( 'woocommerce_product_tabs', [ $this, 'showShippingDetails' ] );
            }

            add_filter( 'body_class', function ( $classes ) {
                if ( ! function_exists( 'WC' ) || empty( WC()->cart ) ) {
                    return $classes;
                }
                if ( WC()->cart->get_cart_contents_count() ) {
                    $classes[] = 'woocommerce-items-in-cart';
                } else {
                    $classes[] = 'woocommerce-cart-empty';
                }

                return $classes;
            } );

            add_filter( 'woocommerce_cart_totals_order_total_html', function ( $html ) {
                $html = trim( str_replace( 'Tax estimated for New Zealand', 'GST', $html ) );
                if ( ! strpos( $html, 'includes_tax' ) && wc_tax_enabled() && WC()->cart->display_prices_including_tax() ) {
                    $html .= '<small class="includes_tax">(includes $0.00 GST)<br />' . get_bloginfo( 'name' ) . ' is not registered for GST</small>';
                }

                return $html;
            } );
            add_action( 'woocommerce_review_order_before_submit', function () {
                echo '<div class="checkout-buttons-container">';
            }, 1 );
            add_action( 'woocommerce_review_order_after_submit', function () {
                echo '</div>';
            } );
            add_filter( 'woocommerce_thankyou_order_received_text', function ( $text ) {
                $text .= ' You will receive an email confirming your order.';

                return $text;
            } );
            add_action( 'woocommerce_cart_totals_after_order_total', function () {
                if ( wc_coupons_enabled() ) {
                    ?>
                    <tr class="order-coupon">
                        <td colspan="2">
                            <form class="woocommerce-cart-form"
                                  action="<?php echo wc_get_cart_url(); ?>"
                                  method="post">
                                <?php printf( '<h4 class="mk-coupon-title"><a href="javascript:;" onclick="jQuery(\'#coupon_toggle\').slideToggle();">%s</a></h4>', __( 'Do you have a coupon code?', 'mk_framework' ) ); ?>
                                <div id="coupon_toggle" class="coupon" style="display: none;">
                                    <input type="text" name="coupon_code" class="input-text"
                                           id="coupon_code"
                                           value=""
                                           placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
                                    <input type="submit" class="button"
                                           name="apply_coupon"
                                           value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>" />
                                    <?php do_action( 'woocommerce_cart_coupon' ); ?>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php
                }
            } );
            add_filter( 'paginate_links', function ( $link ) {
                if ( $pos = strpos( $link, 'page/1/' ) ) {
                    $link = substr( $link, 0, $pos );
                }

                return $link;
            } );
            add_filter( 'woocommerce_cart_shipping_method_full_label', function ( $label, $method ) {
                if ( $method->cost == 0 ) {
                    $label = str_replace( '&#36;', '', $label );
                    $label = str_replace( '0.00', 'Free', $label );
                }

                return $label;
            }, 10, 2 );
            add_filter( 'wpseo_breadcrumb_single_link', function ( $output, $link ) {
                global $post;
                if ( is_object( $post ) && $post->post_type == 'product' ) {
                    if ( substr( $link['url'], - 6 ) == '/shop/' ) {
                        $output = '';
                    }
                }

                return $output;
            }, 10, 2 );
        }

        public function wooCommerceBeforeMainContent() {

        }

        private function isShowingShopFilter() {
            $filter = false;
            if ( is_product_category() && $this->checkProductsDisplaying() ) {
                $filter = true;
            }

            return apply_filters( 'apex_toolbox_show_shop_filter', $filter );
        }

        private function checkProductsDisplaying() {
            global $wp_query;

            if ( ! is_product_category() ) {
                return false;
            }
            // Get the query object
            $object       = $wp_query->get_queried_object();
            $display_type = get_term_meta( $object->term_id, 'display_type', true );
            if ( $display_type == 'subcategories' ) {
                return false;
            }

            return true;
        }

        private function hasShippingDetails() {
            if ( $this->Toolbox->getOption( 'wc_shipping_text' ) ) {
                return true;
            } elseif ( intval( $this->Toolbox->getOption( 'wc_hide_shipping_table' ) ) ) {
                return false;
            }
            try {
                $shippingZone = new WC_Shipping_Zone( 1 );

                return true;
            } catch ( Exception $exception ) {
                return false;
            }
        }

        public function showShippingDetails( $tabs ) {
            $tabs['shipping_tab'] = array(
                'title'    => __( 'Shipping & Delivery', 'apex_toolbox' ),
                'priority' => 50,
                'callback' => function () {
                    echo '<h2>Shipping &amp; Delivery</h2>';
                    if ( $shippingText = $this->Toolbox->getOption( 'wc_shipping_text' ) ) {
                        echo html_entity_decode( $shippingText );
                    }
                    $hideShipping = intval( $this->Toolbox->getOption( 'wc_hide_shipping_table' ) );
                    if ( ! $hideShipping ) {
                        try {
                            $shippingZone = new WC_Shipping_Zone( 1 );
                            echo '<table class="shop_attributes"><tbody>';
                            foreach ( $shippingZone->get_shipping_methods() as $method ) {
                                echo '<tr>
					<th>' . $method->title . '</th>
					<td>' . ( $method->cost > 0 ? wc_price( $method->cost ) : 'Free' ) . '</td>
				</tr>';
                            }
                            echo '</tbody></table>';
                        } catch ( Exception $exception ) {
                        }
                    }
                }
            );

            return $tabs;
        }

        public function enableProductFiltersRelevanssiCompatability( $search_ok, $query ) {
            if ( $search_ok ) {
                return true;
            }

            if ( ! empty( $query->query['prdctfltr_active'] ) ) {
                // Use Relevanssi's count for found product count rather than the WP computed one which is usually incorrect.
                $query->query_vars['no_found_rows'] = true;

                return true;
            }

            return false;
        }

        /**
         * Safari, unlike other browsers, fires a "popstate" event when doing user navigation
         * that results in a page load. The XForWooCommerce Product Filters plugin listens for popstate events
         * to handle navigation of filter states without page loads and on this inital event, despite not
         * needing to navigate state, scrolls to the top anyway.
         *
         * The fix for this below is to swallow any popstate events that occur during the pageshow event.
         * We cannot however always swallow the first popstate event as on browsers which do not share Safaris
         * behaviour this event will be caused by a legitimate user interaction.
         */
        public function disableProductFiltersScrollOnSafariBackNavigation() {
            ?>
            <script>
                let withinPageShowEvent = false;

                window.addEventListener("pageshow", () => {
                    withinPageShowEvent = true;

                    // Push this to the back of the event loop queue to execute once the pageshow event ends
                    setTimeout(() => {
                        withinPageShowEvent = false;
                    }, 0);
                });

                window.addEventListener("popstate", (e) => {
                    if (withinPageShowEvent && e.state.filters !== undefined) {
                        e.stopImmediatePropagation();
                    }
                });
            </script>
            <?php
        }

        public function enableCustomPaymentGateway( $gateways ) {
            $this->Toolbox->includeFile("class-wc-apex-custom-payment-gateway.php");
            $gateways[] = 'WC_Apex_Custom_Payment_Gateway';
            return $gateways;
        }

        public function enableWooCommerceSearchRedirect() {
            // We use the pre_get_posts action as we want to run this as early as possible but
            // need to wait until the main query is parsed to use is_search() and is_post_type_archive().
            add_action('pre_get_posts', function ( $query ) {
                if ($query !== $GLOBALS['wp_the_query']) {
                    return;
                }

                if ( is_search() && ! is_post_type_archive( 'product' ) && !is_admin() ) {
                    wp_redirect( wc_get_page_permalink( 'shop' ) . "?s=" . urlencode( get_query_var( 's' ) ) );

                    exit();
                }
            } );
        }

        public function hideWooCommerceMenuItems() {
            $hiddenWooCommerceMenuItems = $this->Toolbox->getOption('wc_hidden_menu_items');

            if ($hiddenWooCommerceMenuItems === false || $hiddenWooCommerceMenuItems === "") {
                $hiddenWooCommerceMenuItems = [];
            }

            $this->Toolbox->addSetting(array(
                'name'        => 'wc_hidden_menu_items',
                'label'       => 'Hide WooCommerce Account Menu Items',
                'type'        => 'checkbox',
                'range'       => wc_get_account_menu_items(),
                'value'       => $hiddenWooCommerceMenuItems
            ), $this->getLabel());

            add_filter('woocommerce_account_menu_items', function ($menuItems) use ($hiddenWooCommerceMenuItems) {
                foreach ($menuItems as $menuItem => $displayName) {
                    if (in_array($menuItem, $hiddenWooCommerceMenuItems)) {
                        unset($menuItems[$menuItem]);
                    }
                }

                return $menuItems;
            }, 1000);
        }

        /**
         * If we have a session but it is not valid WooCommerce will incorrectly generate nonces
         * which will always be invalid on the next request. We can solve this by destroying the
         * invalid session and removing the cookie so that WooCommerce will generate nonces as
         * though we are logged out.
         */
        public function unsetInvalidSessionCookie() {
            if ( !function_exists( 'WC' ) ) {
                return;
            }

            if ( WC()->session !== null && WC()->session->has_session() && WC()->session->get_session_cookie() === false ) {
                WC()->session->forget_session();
                unset( $_COOKIE[ apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH ) ] );
            }
        }

        /**
         * Get the primary product category according to Yoast and fall back to the first category if it is not present.
         *
         * @param int $postId
         *
         * @return string
         */
        private function getPrimaryCategoryWithFallback($postId) {
            $primaryProductCategory = get_post_meta($postId, '_yoast_wpseo_primary_product_cat', true);

            $productCategory = '';

            if (!empty($primaryProductCategory)) {
                $productCategory = get_term($primaryProductCategory, 'product_cat')->name;
            } else {
                $productCategories = wp_get_post_terms($postId, 'product_cat');

                if (count($productCategories) > 0) {
                    $productCategory = $productCategories[0]->name;
                }
            }

            return $productCategory;
        }

        /**
         * Register Custom Yoast Variables
         *
         * @param array $replacements The array to insert the custom variables into
         * @param object $args Information about the post that the custom variables are relevant to.
         *
         * @return array The new $replacements array
         */
        public function registerCustomYoastVariables($replacements, $args) {
            if (!isset($args->post_type) || $args->post_type !== 'product') {
                return $replacements;
            }

            $replacements['%%primary_category_with_fallback%%'] = $this->getPrimaryCategoryWithFallback($args->ID);

            return $replacements;
        }

        /**
         * Disable the timeout for WooCommerce Cart Fragments requests.
         */
        public function disableWCCartFragmentsRequestTimeout($params, $handle) {
            if ($handle === 'wc-cart-fragments') {
                $params['request_timeout'] = "0";
            }

            return $params;
        }

        public function enablePurchaseOrderNumberCheckoutField() {
            add_action(
                "woocommerce_admin_order_data_after_billing_address",
                function ($wc_order) {
                    $purchase_order_number = $wc_order->get_meta( self::PURCHASE_ORDER_NUMBER_META_FIELD );

                    if ( empty( $purchase_order_number ) ) {
                        $purchase_order_number = 'Not provided';
                    }

                    echo '<p><strong>Purchase Order Number:</strong><br />' . wp_kses_post( $purchase_order_number ) . '</p>';
                }
            );

            add_action(
                "woocommerce_checkout_fields",
                function ($fields) {
                    $fields['order'][self::PURCHASE_ORDER_NUMBER_META_FIELD] = array(
                        "label" => "Purchase Order Number",
                        "priority" => apply_filters( 'apex_toolbox_purchase_order_number_field_priority', 10 ),
                    );

                    return $fields;
                }
            );

            add_action(
                "woocommerce_checkout_update_order_meta",
                function ($order_id) {
                    update_post_meta( $order_id, self::PURCHASE_ORDER_NUMBER_META_FIELD, sanitize_text_field( $_POST[self::PURCHASE_ORDER_NUMBER_META_FIELD] ) );
                }
            );

            add_action(
                "woocommerce_email_order_meta_fields",
                function ($fields, $_, $order) {
                    $purchaseOrderNumber = get_post_meta( $order->get_id(), self::PURCHASE_ORDER_NUMBER_META_FIELD, true );

                    if ( empty( $purchaseOrderNumber ) ) {
                        return $fields;
                    }

                    return [
                        ...$fields,
                        [
                            'label' => 'Purchase Order Number',
                            'value' => $purchaseOrderNumber
                        ]
                    ];
                },
                10,
                3
            );

            add_action(
                "woocommerce_after_order_details",
                function ($order) {
                    if ( ! $order instanceof WC_Order ) {
                        return;
                    }

                    $purchaseOrderNumber = get_post_meta( $order->get_id(), self::PURCHASE_ORDER_NUMBER_META_FIELD, true );

                    if ( empty( $purchaseOrderNumber ) ) {
                        return;
                    }

                    echo '<h2>Purchase Order Number</h2><p>' . esc_html( $purchaseOrderNumber ) . '</p>';
                }
            );

            add_action(
                "cfw_before_thank_you_customer_information",
                function ($order) {
                    $purchaseOrderNumber = get_post_meta( $order->get_id(), self::PURCHASE_ORDER_NUMBER_META_FIELD, true );

                    if ( empty ( $purchaseOrderNumber ) ) {
                        return;
                    }
                    ?>
                        <div class="row">
                            <div class="col-lg-6">
                                <h6>Purchase Order Number</h6>
                                <p><?php echo $purchaseOrderNumber; ?></p>
                            </div>
                        </div>
                    <?php
                }
            );
        }
    }
}
