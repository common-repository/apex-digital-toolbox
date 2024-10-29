<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookElementor' ) ) {
    class toolboxHookElementor extends toolboxHookController {
      function __construct( $Toolbox ) {
        parent::__construct( $Toolbox );
        $this->setLabel( 'Elementor' );
        $this->addHook( APEX_TOOLBOX_HOOK_FILTER, 'init', 'registerWidgetListCategories', array(
            'label'       => 'List Categories - Widget',
            'description' => 'Show a list of taxonomy links based on their parent and split them in them into columns with dividers',
        ) );
      }

      /**
       * Register the list categories widget with Elementor
       */
      public function registerWidgetListCategories( ) {
          $this->includeFile('WidgetListCategories.php');
      }

      /**
       * Include an Elementor specific file
       *
       * @param $file
       */
      private function includeFile( $file ) {
        $this->Toolbox->includeFile( 'elementor/' . $file );
      }
    }
}
