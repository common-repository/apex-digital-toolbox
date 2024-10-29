<?php
/*
Plugin Name: Apex Digital Toolbox
Plugin URI: https://www.apexdigital.co.nz/
Description: Adds additional functionality that to make it easier to setup sites
Version: 1.4.13
Author: Apex Digital
Author URI: https://www.apexdigital.co.nz/
*/

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.1 401 Unauthorized' );
    exit;
}
// Load the main controller
define( 'APEX_TOOLBOX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'APEX_TOOLBOX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once( APEX_TOOLBOX_PLUGIN_PATH . 'controllers/toolboxController.php' );
// Start her up!
$Toolbox = new toolboxController();
