<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://dharmik-lathiya.vercel.app
 * @since      1.0.0
 *
 * @package    Postartify
 * @subpackage Postartify/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Postartify
 * @subpackage Postartify/includes
 * @author     Dharmik Lathiya <dharmiklathiya.it@gmail.com>
 */
class Postartify {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Postartify_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'POSTARTIFY_VERSION' ) ) {
			$this->version = POSTARTIFY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'postartify';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Postartify_Loader. Orchestrates the hooks of the plugin.
	 * - Postartify_i18n. Defines internationalization functionality.
	 * - Postartify_Admin. Defines all hooks for the admin area.
	 * - Postartify_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-postartify-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-postartify-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-postartify-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-postartify-public.php';

		$this->loader = new Postartify_Loader();

		require_once plugin_dir_path( __DIR__ ) . 'includes/class-postartify-cpt.php';

		require_once plugin_dir_path( __DIR__ ) . 'includes/class-postartify-main.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Postartify_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Postartify_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Postartify_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_cpt   = new Postartify_CPT();
		$plugin_main  = new Postartify_Main();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Enable All cpt thumbnail
		$this->loader->add_action( 'after_setup_theme', $plugin_admin, 'enable_featured_image' );

		// CPT Register
		$this->loader->add_action( 'init', $plugin_cpt, 'register_cpt' );

		// admin Menu
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
				$this->loader->add_action( 'admin_init', $plugin_main, 'register_settings' );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_main, 'enqueue_admin_scripts' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_main, 'add_meta_boxes' );
		$this->loader->add_action( 'wp_ajax_postartify_generate_featured', $plugin_main, 'ajax_generate_featured' );
		$this->loader->add_action( 'wp_ajax_postartify_generate_inline', $plugin_main, 'ajax_generate_inline' );
		$this->loader->add_action( 'wp_ajax_postartify_analyze_post', $plugin_main, 'ajax_analyze_post' );
		$this->loader->add_action( 'wp_ajax_postartify_batch_generate', $plugin_main, 'ajax_batch_generate' );
		$this->loader->add_action( 'save_post', $plugin_main, 'auto_generate_featured', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Postartify_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Postartify_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
