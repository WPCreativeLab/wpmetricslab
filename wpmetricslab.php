<?php
/*
Plugin Name: WPMetricsLab
Plugin URI: https://wpmetricslab.com/
Description: WPMetricsLab tracks UTM parameters and provides deep insights into visitor behavior. It enables precise measurement of campaigns and detailed tracking of anonymous visitor devices, helping you create comprehensive user journeys.
Version: 1.0
Author: WPCreativeLab
Author URI: https://wpcreativelab.com/
Text Domain: wpmetricslab
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// PHP verzió ellenőrzés
if (version_compare(PHP_VERSION, '7.4', '<')) {
    exit(__('WPMetricsLab requires PHP version 7.4 or higher.', 'wpmetricslab'));
}

// Define global constants
define( 'WPMETRICSLAB_VERSION', '1.0' );
define( 'WPMETRICSLAB_SLUG', basename( plugin_dir_path( __FILE__ ) ) );
define( 'WPMETRICSLAB_CORE_FILE', __FILE__ );
define( 'WPMETRICSLAB_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPMETRICSLAB_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

// Composer autoload betöltése
require WPMETRICSLAB_PATH . 'vendor/autoload.php';

use WPMetricsLab\Installer;
use WPMetricsLab\Controllers\ActivityLogController;

function wpmetricslab_activate() {
    $installer = new Installer();
    $installer->run();
}
register_activation_hook(WPMETRICSLAB_CORE_FILE, 'wpmetricslab_activate');

// Az ActivityLogController inicializálása
function wpmetricslab_initialize() {
    $activityLogController = new ActivityLogController();
}
add_action('plugins_loaded', 'wpmetricslab_initialize');
