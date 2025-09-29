<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dharmik-lathiya.vercel.app
 * @since             1.0.0
 * @package           Postartify
 *
 * @wordpress-plugin
 * Plugin Name:       PostArtify
 * Plugin URI:        https://dharmik-lathiya.vercel.app
 * Description:       AI Based Featured Image Generator
 * Version:           1.0.0
 * Author:            Dharmik Lathiya
 * Author URI:        https://dharmik-lathiya.vercel.app/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       postartify
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'POSTARTIFY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-postartify-activator.php
 */
function activate_postartify() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-postartify-activator.php';
	Postartify_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-postartify-deactivator.php
 */
function deactivate_postartify() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-postartify-deactivator.php';
	Postartify_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_postartify' );
register_deactivation_hook( __FILE__, 'deactivate_postartify' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-postartify.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_postartify() {

	$plugin = new Postartify();
	$plugin->run();

}
run_postartify();
