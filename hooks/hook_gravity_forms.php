<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookGravityForms' ) ) {
    class toolboxHookGravityForms extends toolboxHookController {
        function __construct( $Toolbox ) {
            parent::__construct( $Toolbox );
            $this->setLabel( 'Gravity Forms' );
            $installed = false;
            if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                $installed = true;
            }
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'setupHooks', array(
                'label'       => 'Add Bootstrap classes & columns' . ( ! $installed ? ' (GRAVITY FORMS NOT INSTALLED)' : '' ),
                'description' => 'Add Bootstrap classes to input fields to provide a more constant styling experience as well as a column divider - requires extra CSS for columns'
            ) );
            $this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'confirmationScroll', array(
                'label'       => 'Confirmation Scrolling' . ( ! $installed ? ' (GRAVITY FORMS NOT INSTALLED)' : '' ),
                'description' => 'When a fixed header is in use the confirmation message gets lost behind it. This hook will ensure it is scrolled in to view.'
            ) );
        }

        /**
         * When a fixed header is in use the confirmation message gets lost behind it. This hook will ensure it is scrolled in to view.
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @author Nigel Wells
         */
        public function confirmationScroll( $args = array() ) {
            // Create hooks depending on where we are at
            if ( is_admin() ) {
                // Create settings
                $this->Toolbox->addSetting( array(
                    'name'        => 'gf_header_selector',
                    'label'       => 'Header DOM Selector',
                    'type'        => 'string',
                    'value'       => $this->Toolbox->getOption( 'gf_header_selector' ),
                    'description' => 'Enter a selector from the DOM where the height of the header can be calculated from'
                ), $this->getLabel() );
                $this->Toolbox->addSetting( array(
                    'name'        => 'gf_header_padding',
                    'label'       => 'Padding After Header',
                    'type'        => 'string',
                    'value'       => ( $this->Toolbox->getOption( 'gf_header_padding' ) ? $this->Toolbox->getOption( 'gf_header_padding' ) : 20 ),
                    'description' => 'How much padding to be added (in pixels) after the header'
                ), $this->getLabel() );
                $this->Toolbox->addSetting( array(
                    'name'        => 'gf_scroll_speed',
                    'label'       => 'Scroll speed',
                    'type'        => 'string',
                    'value'       => ( $this->Toolbox->getOption( 'gf_scroll_speed' ) ? $this->Toolbox->getOption( 'gf_scroll_speed' ) : 250 ),
                    'description' => 'How fast (milliseconds) to scroll the confirmation message in to view'
                ), $this->getLabel() );
                $this->Toolbox->addSetting( array(
                    'name'        => 'gf_breakpoint_ignore',
                    'label'       => 'Breakpoint Ignore',
                    'type'        => 'string',
                    'value'       => ( $this->Toolbox->getOption( 'gf_breakpoint_ignore' ) ? $this->Toolbox->getOption( 'gf_breakpoint_ignore' ) : 0 ),
                    'description' => 'If the fixed header gets disabled on smaller screens then you can disable it but entering the device breakpoint width'
                ), $this->getLabel() );
            } else {
                add_action( 'wp_footer', function () {
                    ?>
                    <script type="text/javascript">
                        jQuery(function() {
                            jQuery(document).bind("gform_confirmation_loaded", function(e, form_id) {
                                var headerSelector = "<?php echo $this->Toolbox->getOption( 'gf_header_selector' ); ?>";
                                var headerPadding = <?php echo intval( $this->Toolbox->getOption( 'gf_header_padding' ) ); ?>;
                                var speed = <?php echo intval( $this->Toolbox->getOption( 'gf_scroll_speed' ) ); ?>;
                                var breakpoint = <?php echo intval( $this->Toolbox->getOption( 'gf_breakpoint_ignore' ) ); ?>;
                                if (jQuery(window).width() > breakpoint) {
                                    var position = jQuery(`#gform_confirmation_wrapper_${form_id}`).offset().top - (jQuery(headerSelector).height() + headerPadding);
                                    jQuery("html,body").animate({ scrollTop: position }, speed);
                                }
                            });
                        });
                    </script>
                    <?php
                } );
            }
        }

        /**
         * Add various hooks to be used to expand Gravity Forms
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return void;
         * @author Nigel Wells
         */
        public function setupHooks( $args = array() ) {
            add_filter( 'gform_field_content', array( $this, 'addBootstrapInputClasses' ), 10, 5 );
            add_filter( 'gform_submit_button', array( $this, 'addBootstrapButtonClasses' ), 10, 2 );
            $this->Toolbox->includeFile( 'gravity_forms.php' );
            if ( ! class_exists( 'GF_Field' ) ) {
                return;
            }
            if ( ! GF_Fields::exists( 'column' ) ) {
                GF_Fields::register( new GF_Field_Column() );
            }
            if ( ! GF_Fields::exists( 'apex_submit' ) ) {
                GF_Fields::register( new Apex_GF_Field_Submit() );
            }
            add_action( 'gform_field_standard_settings', function ( $placement, $form_id ) {
                if ( $placement == 0 ) {
                    echo '<li class="column_description field_setting">Column breaks should be placed between fields to split form into separate columns. You do not need to place any column breaks at the beginning or end of the form, only in the middle.</li>';
                    echo '<li class="submit_description field_setting">Used to place the submit button anywhere in the form rather than just in the footer. Requires the footer icon being hidden via CSS.</li>';
                }
            }, 10, 2 );
            add_filter( 'gform_field_container', function ( $field_container, $field, $form, $css_class, $style, $field_content ) {
                if ( IS_ADMIN ) {
                    return $field_container;
                }
                if ( $field['type'] == 'column' ) {
                    $column_index = 2;
                    foreach ( $form['fields'] as $form_field ) {
                        if ( $form_field['id'] == $field['id'] ) {
                            break;
                        }
                        if ( $form_field['type'] == 'column' ) {
                            $column_index ++;
                        }
                    }

                    return '</ul><ul class="' . GFCommon::get_ul_classes( $form ) . ' column column_' . $column_index . ' ' . $field->cssClass . '">';
                } elseif ( $field['type'] == 'apex_submit' ) {
                    $button_input    = GFFormDisplay::get_form_button( $form['id'], "gform_submit_button_{$form['id']}", $form['button'], __( 'Submit', 'gravityforms' ), 'gform_button', __( 'Submit', 'gravityforms' ), 0 );
                    $button_input    = gf_apply_filters( array(
                        'gform_submit_button',
                        $form['id']
                    ), $button_input, $form );
                    $field_container = str_replace( '</li>', $button_input . '</li>', $field_container );
                }

                return $field_container;
            }, 10, 6 );
        }

        /**
         * Add Bootstrap classes to input fields to provide a more constant styling experience
         *
         * @param array $args Any arguments passed to the callback
         *
         * @return string;
         * @author Nigel Wells
         */
        public function addBootstrapInputClasses( $field_content, $field, $value, $entry_id, $form_id ) {
            // Currently only applies to most common field types, but could be expanded.
            switch ( $field->type ) {
                case 'hidden' :
                case 'list' :
                case 'multiselect' :
                case 'fileupload' :
                case 'html' :
                    break;
                case 'name' :
                case 'address' :
                case 'time' :
                    $field_content = str_replace( '<input ', '<input class=\'form-control\' ', $field_content );
                    $field_content = str_replace( '<select ', '<select class=\'form-control\' ', $field_content );
                    break;
                case 'date' :
                    $field_content = str_replace( 'class=\'datepicker ', 'class=\'datepicker form-control ', $field_content );
                    break;
                case 'textarea' :
                    $field_content = str_replace( 'class=\'textarea', 'class=\'form-control textarea', $field_content );
                    break;
                case 'checkbox' :
                    $field_content = str_replace( 'li class=\'', 'li class=\'checkbox ', $field_content );
                    $field_content = str_replace( '<input ', '<input style=\'margin-left:1px;\' ', $field_content );
                    break;
                case 'radio' :
                    $field_content = str_replace( 'li class=\'', 'li class=\'radio ', $field_content );
                    $field_content = str_replace( '<input ', '<input style=\'margin-left:1px;\' ', $field_content );
                    break;
                default :
                    $field_content = str_replace( 'class=\'medium', 'class=\'form-control medium', $field_content );
                    $field_content = str_replace( 'class=\'large', 'class=\'form-control large', $field_content );
                    break;
            }

            return $field_content;
        }

        /**
         * Add Bootstrap classes to button fields to provide a more constant styling experience
         *
         * @return string;
         * @author Nigel Wells
         */
        public function addBootstrapButtonClasses( $button_input, $form ) {
            $button_input = str_replace( 'class=\'gform_button', 'class=\'btn btn-primary gform_button', $button_input );

            return $button_input;
        }

    }
}
