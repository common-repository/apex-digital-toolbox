<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookWordPressAdmin' ) ) {
    class toolboxHookWordPressAdmin extends toolboxHookController {
        public $_menu = array();
        private static $WPAI_MONITORING_HOOK = 'check_wpai_imports';

        function __construct( $Toolbox ) {
            parent::__construct( $Toolbox );
            $this->setLabel( 'WordPress Administration' );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'admin_menu', 'createAdminMenu', array(
                'label'       => 'WordPress Menu',
                'description' => 'Adds the Apex Toolbox menus into the main WordPress menu.'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'enableSessions', array(
                'label'       => 'Sessions',
                'description' => 'Allows sessions to be used within the WordPress dashboard - they will be cleaned up on logout. This is used to help display notices via form submissions or other such functionality.',
                'priority'    => 1
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'changeEmailSender', array(
                'label'       => 'Email Sender',
                'description' => 'Change the email sender information for the FROM address. By default Wordpress sets this to "WordPress <wordpress@domain.com> which isn\'t ideal',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'parentPageToChildRedirect', array(
                'label'       => 'Parent Page to Child Redirect',
                'description' => 'Allows parent pages to automatically redirect to the first child page available. WordPress needs hierarchy but menus end up showing blank pages as touch devices will show a sub-menu rather than going to the parent page',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'map_meta_cap', 'privacyPolicyPermission', array(
                'label'       => 'Privacy Policy Permission',
                'description' => 'Give access to the editor role to edit the privacy policy',
                'args'        => 4
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'disableAdminNotifications', array(
                'label'       => 'Disable Administrator Email Notifications',
                'description' => 'Disable emails sent to <b>' . get_option( 'admin_email' ) . '</b> for:<ul style="list-style: disc; margin-left: 3em;"><li>Password change</li><li>New user registration</li><li>Comment notifications</li></ul>',
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'monitorWPAI', array(
                'label'       => 'Enable monitoring of WP All Import' . ( !is_plugin_active('wp-all-import-pro/wp-all-import-pro.php') ? ' (WP ALL IMPORT NOT INSTALLED)' : '' ),
                'description' => 'Enable scheduled checks to ensure that WPAI imports are still running on schedule'
            ) );
        }

        /**
         * Create WordPress administration menu
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        function createAdminMenu( $args = array() ) {
            $this->addPage(
                'Hooks',
                array( $this, 'outputHooksPage' )
            );
            $this->addPage(
                'Settings',
                array( $this, 'outputSettingsPage' )
            );
            // Register the submission callback
            add_action( 'admin_action_hooksPageSubmission', array( $this, 'hooksPageSubmission' ) );
            add_action( 'admin_action_settingsPageSubmission', array(
                $this,
                'settingsPageSubmission'
            ) );
        }

        /**
         * Disable emails sent to admin for:
         * - Password change
         * - New user registration
         * - Comment notifications
         *
         * Due to the setup of WordPress the emails are technically sent, but we send them to a noreply address
         *
         * @return void;
         * @author Nigel Wells
         */
        function disableAdminNotifications( $args = array() ) {
            add_filter( 'wp_new_user_notification_email_admin', [
                $this,
                'alterAdminNotificationEmail'
            ], 10, 3 );
            add_filter( 'wp_password_change_notification_email', [
                $this,
                'alterAdminNotificationEmail'
            ], 10, 3 );

            add_filter( 'comment_moderation_recipients', [
                $this,
                'alterCommentNotificationRecipients',
            ], 10, 2
            );
        }

        function alterCommentNotificationRecipients( $emails, $comment_id ) {
            foreach ( $emails as $index => $email ) {
                if ( $email == get_option( 'admin_email' ) ) {
                    $emails[ $index ] = 'noreply@apexdigital.co.nz';
                }
            }

            return $emails;
        }

        /**
         * Change the TO address for admin emails to a noreply address
         *
         * @param $email
         * @param $user
         * @param $blog_name
         *
         * @return mixed
         */
        function alterAdminNotificationEmail( $email, $user, $blog_name ) {
            if ( $email['to'] == get_option( 'admin_email' ) ) {
                $email['to'] = 'noreply@apexdigital.co.nz';
            }

            return $email;
        }

        /**
         * Outputs the hooks page HTML
         *
         * @return void;
         * @version 0.3.5.16.11.01
         * @author Nigel Wells
         */
        function outputHooksPage() {
            $savedHooks = $this->Toolbox->getOption( 'hooks' );
            echo '<div class="wrap">
				<h1>Apex Toolbox Hooks</h1>
				' . $this->displayNotices() . '
				<p>Below are the hooks currently available for activation. Once turned on some will provide additional functionality under <a href="' . admin_url( 'admin.php' ) . '?page=apex_toolbox_settings">settings</a> or other menu items.</p>
				<form method="post" action="' . admin_url( 'admin.php' ) . '?action=hooksPageSubmission">
				<input type="hidden" name="page" value="' . $_GET['page'] . '" />
				<input type="hidden" name="nonce" value="' . wp_create_nonce( $this->Toolbox->getPrefix() . 'update_hooks' ) . '" />';
            foreach ( $this->Toolbox->getAvailableHookControllers() as $controllerObject ) {
                $availableHooks = $controllerObject->getHooks();
                if ( count( $availableHooks ) ) {
                    echo '<h2>' . $controllerObject->getLabel() . '</h2>';
                    foreach ( $availableHooks as $index => $hookModal ) {
                        $checked  = false;
                        $readOnly = false;
                        if ( isset( $savedHooks[ $controllerObject->getName() ] ) && in_array( $hookModal->getMethod(), $savedHooks[ $controllerObject->getName() ] ) ) {
                            $checked = true;
                        }
                        if ( $this->Toolbox->isHookDefault( $controllerObject->getName(), $hookModal->getMethod() ) ) {
                            $checked  = true;
                            $readOnly = true;
                        }
                        echo '<label><input type="checkbox" name="hooks[' . $controllerObject->getName() . '][]" value="' . $hookModal->getMethod() . '"' . ( $checked ? ' checked="checked"' : '' ) . ( $readOnly ? ' disabled="disabled"' : '' ) . '> ' . $hookModal->getLabel() . '</label>';
                        if ( $hookModal->getDescription() ) {
                            echo '<p class="description">' . $hookModal->getDescription() . '</p>';
                        }
                        echo '<br />';
                    }
                }
            }
            echo submit_button( 'Update Hooks' ) . '
			</form>
			</div>';
        }

        /**
         * Adjusts the permission for the privacy policy option to allow both administrators and editors to manage it
         *
         * @param $caps
         * @param $cap
         * @param $user_id
         * @param $args
         *
         * @return array|mixed
         */
        public function privacyPolicyPermission( $caps, $cap, $user_id, $args ) {
            if ( ! is_user_logged_in() ) {
                return $caps;
            }

            $user_meta = get_userdata( $user_id );
            if ( array_intersect( [ 'editor', 'administrator' ], $user_meta->roles ) ) {
                if ( 'manage_privacy_options' === $cap ) {
                    $manage_name = is_multisite() ? 'manage_network' : 'manage_options';
                    $caps        = array_diff( $caps, [ $manage_name ] );
                }
            }

            return $caps;
        }

        /**
         * Handles the submission of the hooks page
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        function hooksPageSubmission() {
            if ( ! current_user_can( 'administrator' ) || ! is_admin() ) {
                header( 'HTTP/1.1 401 Unauthorized' );
                exit;
            }
            if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->Toolbox->getPrefix() . 'update_hooks' ) ) {
                $this->addNotice( 'Failed to update - form may be stale. Please try again.', 2 );
                $this->redirect();
                exit;
            }
            $hooks = ( isset( $_POST['hooks'] ) ? $_POST['hooks'] : array() );
            $this->Toolbox->setOption( 'hooks', $hooks );
            // Note what happened and reload the page
            $this->addNotice( 'Hooks have been successfully updated' );
            $this->redirect();
        }

        /**
         * Outputs the settings page HTML
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        function outputSettingsPage() {
            echo '<div class="wrap">
				<h1>Apex Toolbox Settings</h1>
				' . $this->displayNotices() . '
				<p>Settings available from the <a href="' . admin_url( 'admin.php' ) . '?page=apex_toolbox_hooks">hooks</a> that have been activated.</p>
				<form method="post" action="' . admin_url( 'admin.php' ) . '?action=settingsPageSubmission">
				<input type="hidden" name="page" value="' . $_GET['page'] . '" />
				<input type="hidden" name="nonce" value="' . wp_create_nonce( $this->Toolbox->getPrefix() . 'update_settings' ) . '" />';
            $settings = apply_filters( $this->Toolbox->getPrefix() . 'settings', array() );
            if ( count( $settings ) ) {
                $oldSection = '';
                foreach ( $settings as $sections ) {
                    foreach ( $sections as $sectionName => $fields ) {
                        foreach ( $fields as $field ) {
                            if ( $sectionName != $oldSection ) {
                                if ( $oldSection ) {
                                    echo '</table>';
                                }
                                echo '<h2>' . $sectionName . '</h2>
					<table class="form-table">';
                            }
                            echo '<tr>
						<th scope="row">
							<label for="' . $field->getPrefix() . $field->getName() . '">' . __( $field->getLabel(), $field->getPrefix() ) . '</label>
						</th>
						<td>
							' . $field->outputField() . '
						</td>
					</tr>';
                            $oldSection = $sectionName;
                        }
                    }
                }
                echo '</table>';
            }
            echo submit_button( 'Update Settings' ) . '
			</form>
			</div>';
        }

        /**
         * Handles the submission of the hooks page
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        function settingsPageSubmission() {
            if ( ! current_user_can( 'administrator' ) || ! is_admin() ) {
                header( 'HTTP/1.1 401 Unauthorized' );
                exit;
            }
            if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->Toolbox->getPrefix() . 'update_settings' ) ) {
                $this->addNotice( 'Failed to update - form may be stale. Please try again.', 2 );
                $this->redirect();
                exit;
            }
            $settings = apply_filters( $this->Toolbox->getPrefix() . 'settings', array() );
            if ( count( $settings ) ) {
                foreach ( $settings as $sections ) {
                    foreach ( $sections as $sectionName => $fields ) {
                        foreach ( $fields as $field ) {
                            $settingValue = ( isset( $_POST[ $field->getPrefix() . $field->getName() ] ) ? $_POST[ $field->getPrefix() . $field->getName() ] : '' );
                            $field->setValue( $settingValue );
                            $this->Toolbox->setOption( $field->getName(), $field->getValue() );
                        }
                    }
                }
            }
            // Direct to success page
            $this->addNotice( 'Settings have been successfully updated' );
            $this->redirect();
        }

        /**
         * Create a menu item for the plugin
         *
         * @param string $name Menu label
         * @param array $callback Array containing the controller and callback function
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        public function addPage( $name, $callback ) {
            $slug                       = str_replace( '-', '_', sanitize_title( $name ) );
            $securityPermissionRequired = 'manage_options';
            // Keep track of what we've done
            $this->_menu[] = array( 'name' => $name, 'slug' => $slug );
            // Create main menu item if needed
            if ( count( $this->_menu ) == 1 ) {
                add_menu_page(
                    'Apex Toolbox',
                    'Apex Toolbox',
                    $securityPermissionRequired,
                    $this->Toolbox->getPrefix() . $this->_menu[0]['slug'],
                    $callback,
                    'dashicons-admin-tools'
                );
            }
            // Create the sub page
            add_submenu_page(
                $this->Toolbox->getPrefix() . $this->_menu[0]['slug'],
                'Apex Toolbox | ' . $name,
                $name,
                $securityPermissionRequired,
                $this->Toolbox->getPrefix() . $slug,
                $callback
            );
        }

        /**
         * Enable sessions to be used on the site
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        public function enableSessions( $args = array() ) {
            if ( is_admin() && ! wp_doing_ajax() ) {
                if ( ! session_id() ) {
                    session_start();
                }
                add_action( 'wp_logout', array( $this, 'sessionEnd' ) );
                add_action( 'wp_login', array( $this, 'sessionEnd' ) );
            }
        }

        /**
         * Destroy the session when logging out
         *
         * @return void;
         * @version 0.3.1.16.10.10
         * @author Nigel Wells
         */
        public function sessionEnd() {
            session_destroy();
        }

        /**
         * return void;
         */
        public function changeEmailSender( $args = array() ) {
            $mail_from_email = $this->Toolbox->getOption( 'mail_from_email' );
            if ( ! $mail_from_email ) {
                $mail_from_email = get_option( 'admin_email' );
            }
            $this->Toolbox->addSetting( array(
                'name'        => 'mail_from_email',
                'label'       => 'From Email Address',
                'type'        => 'text',
                'value'       => $mail_from_email,
                'description' => 'Email address to appear in the mail sender properties'
            ), $this->getLabel() );
            $mail_from_name = $this->Toolbox->getOption( 'mail_from_name' );
            if ( ! $mail_from_name ) {
                $mail_from_name = get_option( 'blogname' );
            }
            $this->Toolbox->addSetting( array(
                'name'        => 'mail_from_name',
                'label'       => 'From Name',
                'type'        => 'text',
                'value'       => $mail_from_name,
                'description' => 'From name to appear in the mail sender properties'
            ), $this->getLabel() );
            add_filter( 'wp_mail_from', [ $this, 'wp_mail_from' ], 99 );
            add_filter( 'wp_mail_from_name', [ $this, 'wp_mail_from_name' ], 99 );
            add_action( 'phpmailer_init', [ $this, 'wp_mail_returnpath_phpmailer_init' ], 99 );
        }

        public function wp_mail_from( $mail_from_email ) {
            $new_mail_from_email = $this->Toolbox->getOption( 'mail_from_email' );
            if ( $new_mail_from_email ) {
                $mail_from_email = $new_mail_from_email;
            }

            return $mail_from_email;
        }

        public function wp_mail_from_name( $mail_from_name ) {
            $new_mail_from_name = $this->Toolbox->getOption( 'mail_from_name' );
            if ( $new_mail_from_name ) {
                $mail_from_name = $new_mail_from_name;
            }

            return html_entity_decode( $mail_from_name );
        }

        function wp_mail_returnpath_phpmailer_init( $phpmailer ) {
            if ( filter_var( $phpmailer->Sender, FILTER_VALIDATE_EMAIL ) !== true ) {
                $phpmailer->Sender = $phpmailer->From;
            }
        }

        function parentPageToChildRedirect( $args = array() ) {
            add_action( 'add_meta_boxes_page', [
                $this,
                'parentPageToChildRedirect_add_meta_boxes'
            ] );
            add_action( 'save_post_page', [ $this, 'parentPageToChildRedirect_save_post' ] );
            add_action( 'wp', [ $this, 'checkParentPageToChildRedirect' ] );
            add_filter( 'nav_menu_link_attributes', [
                $this,
                'parentPageToChildRedirect_nav_menu_link_attributes'
            ], 10, 3 );
            wp_register_script( 'data-disable-href', '', [], '', true );
            wp_enqueue_script( 'data-disable-href' );
            wp_add_inline_script( 'data-disable-href', '
                  jQuery("a[data-disable-href]").each(function() {
                    jQuery(this).prop("href", "javascript:;");
                    if(jQuery("ul.jet-menu").length) {
                        jQuery(this).on("touchstart", function() {
                            jQuery(this).parent().parent().find(".jet-menu-hover").removeClass("jet-menu-hover");
                            jQuery(this).parent().toggleClass("jet-menu-hover");
                        });
                    }
                  });' );
        }

        function parentPageToChildRedirect_add_meta_boxes() {
            add_meta_box( 'parent_child_redirect_meta_box', __( 'Parent to Child Redirect', 'apex_toolbox' ), [
                $this,
                'parentChildRedirectPostBox'
            ], 'page', 'side', 'low' );
        }

        function parentChildRedirectPostBox( $post ) {
            // Only display if there are sub-pages
            $pages = get_pages( array( 'child_of' => $post->ID ) );
            if ( count( $pages ) > 0 ) {
                $page_parent_child_redirect = intval( get_post_meta( $post->ID, 'page_parent_child_redirect', true ) );
                echo '<label><input type="checkbox" name="page_parent_child_redirect" value="1"' . ( $page_parent_child_redirect === 1 ? ' checked="checked"' : '' ) . ' /> Turn on</label>';
            } else {
                echo '<p>If pages are added with this page as its parent then you will be able to switch on an automatic redirect to the first sub-page.</p>';
            }
        }

        function parentPageToChildRedirect_save_post( $post_id ) {
            $page_parent_child_redirect = ( isset( $_REQUEST['page_parent_child_redirect'] ) ? intval( $_REQUEST['page_parent_child_redirect'] ) : 0 );
            update_post_meta( $post_id, 'page_parent_child_redirect', $page_parent_child_redirect );
        }

        function parentPageToChildRedirect_nav_menu_link_attributes( $atts, $item, $args ) {
            if ( $item->object == 'page' ) {
                $page_parent_child_redirect = intval( get_post_meta( $item->object_id, 'page_parent_child_redirect', true ) );
                if ( $page_parent_child_redirect === 1 ) {
                    $atts['data-disable-href'] = 'true';
                }
            }

            return $atts;
        }

        function checkParentPageToChildRedirect() {
            global $post;
            if ( is_admin() || empty( $post ) || $post->post_type !== 'page' ) {
                return;
            }
            // Start the redirect chain
            $this->enforceRedirectToChildPage( $post->ID );
        }

        function enforceRedirectToChildPage( $post_id, $initial = true ) {
            $foundId = 0;
            // Check meta value set
            $page_parent_child_redirect = intval( get_post_meta( $post_id, 'page_parent_child_redirect', true ) );
            if ( $page_parent_child_redirect !== 1 ) {
                return $foundId;
            }
            $pageIds = get_pages( [ 'child_of' => $post_id, 'sort_column' => 'menu_order' ] );
            if ( $pageIds ) {
                $firstChild = $pageIds[0];
                $foundId    = $firstChild->ID;
                // Check child to see if it is the same as we don't want to create a redirect chain
                if ( $grandChildId = $this->enforceRedirectToChildPage( $foundId, false ) ) {
                    $foundId = $grandChildId;
                }
            }
            // Do the redirect if something was found
            if ( $initial ) {
                wp_redirect( get_permalink( $foundId ), 301 );
                exit;
            }

            return $foundId;
        }

        public function monitorWPAI() {
            $this->configureWPAIMonitoringSettings();
            add_action( self::$WPAI_MONITORING_HOOK, array( $this, 'action_check_wpai_imports' ) );
            $this->scheduleWPAIMonitoringEvent();
        }

        private function getWPAIMonitoringSettings() {
            $timeout = $this->Toolbox->getOption( 'wpai_monitoring_timeout' );
            $imports = $this->Toolbox->getOption( 'wpai_monitoring_imports' );

            return [
                'timeout' => !empty($timeout) ? $timeout : 120,
                'imports' => !empty($imports) ? $imports : ''
            ];
        }

        private function configureWPAIMonitoringSettings() {
            $settings = $this->getWPAIMonitoringSettings();

            $this->Toolbox->addSetting( array(
                'name'        => 'wpai_monitoring_timeout',
                'label'       => 'WPAI Monitoring Timeout',
                'type'        => 'text',
                'value'       => $settings['timeout'],
                'description' => 'How many minutes after the last activity should a notification be sent out'
            ), $this->getLabel() );

            $this->Toolbox->addSetting( array(
                'name'        => 'wpai_monitoring_imports',
                'label'       => 'WPAI Monitoring Imports',
                'type'        => 'text',
                'value'       => $settings['imports'],
                'description' => 'A comma delimited list of the IDs of WPAI imports to monitor'
            ), $this->getLabel() );
        }

        public function action_check_wpai_imports() {
            global $wpdb;

            if (!is_plugin_active('wp-all-import-pro/wp-all-import-pro.php')) {
                return;
            }

            $settings = $this->getWPAIMonitoringSettings();

            $imports = $wpdb->get_results( 'SELECT id, friendly_name, last_activity FROM ' . $wpdb->prefix . 'pmxi_imports WHERE id IN (' . $settings['imports'] . ')');

            $inactiveImports = [];

            foreach ($imports as $import) {
                $last_activity_timestamp = strtotime( $import->last_activity );
                $activity_cutoff_timestamp = time() - ( intval( $settings['timeout'] ) * 60 );

                if ( $last_activity_timestamp < $activity_cutoff_timestamp ) {
                    $alreadySentTransient = $this->Toolbox->getPrefix() . 'wpai_monitoring_sent_' . $import->id . '_' . $last_activity_timestamp;

                    // Only send a single email per day to notify about an inactive import to avoid spamming
                    if ( !get_transient( $alreadySentTransient ) ) {
                        set_transient( $alreadySentTransient, true, DAY_IN_SECONDS );
                        $inactiveImports[] = $import;
                    }
                }
            }

            if ( count( $inactiveImports ) != 0 ) {
                $inactiveImportsHTML = implode('', array_map(
                    function( $inactiveImport ) {
                        return '<li>' . $inactiveImport->friendly_name . ' (last activity was at ' . $inactiveImport->last_activity . ' UTC)</li>';
                    },
                    $inactiveImports
                ) );

                $message = 'The following WPAI Imports appear to be inactive as they have not run in the past ' . $settings['timeout'] . ' minutes : <br><ul>' . $inactiveImportsHTML . '</ul>';

                wp_mail(
                    'cron.notifications@apexdigital.co.nz',
                    'WPAI Imports Inactive',
                    $message,
                    [
                        "Content-Type: text/html"
                    ]
                );
            }
        }

        private function scheduleWPAIMonitoringEvent() {
            if ( ! wp_next_scheduled( self::$WPAI_MONITORING_HOOK ) ) {
                $settings = $this->getWPAIMonitoringSettings();

                wp_schedule_single_event( time() + intval($settings['timeout']) * 60, self::$WPAI_MONITORING_HOOK );
            }
        }
    }
}