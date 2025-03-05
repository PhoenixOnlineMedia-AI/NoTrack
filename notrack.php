<?php
/**
 * NoTrack - WordPress User Tracking Opt-Out Plugin
 *
 * This plugin provides functionality for users to opt out of various tracking
 * mechanisms commonly used on WordPress sites. It gives visitors control over
 * their privacy by allowing them to disable analytics, cookies, and other
 * tracking technologies.
 *
 * @package           NoTrack
 * @author            Your Name
 * @copyright         2023 Your Name
 * @license           GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       NoTrack
 * Plugin URI:        https://github.com/PhoenixOnlineMedia-AI/NoTrack
 * Description:       A WordPress plugin for user tracking opt-out functionality.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       notrack
 * Domain Path:       /languages
 */

/**
 * Security Check: Prevent direct access to the plugin file.
 *
 * This check ensures that the file cannot be accessed directly by typing its URL
 * in a browser. WordPress defines the ABSPATH constant when loading the plugin
 * through its normal execution path. If this constant is not defined, it means
 * someone is trying to access the file directly, which could pose a security risk.
 * In such cases, we terminate the script execution immediately.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Define plugin constants
 */
define( 'NOTRACK_VERSION', '1.0.0' );
define( 'NOTRACK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOTRACK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Class NoTrack
 *
 * Main plugin class that handles initialization, hooks, and core functionality.
 */
class NoTrack {

    /**
     * Constructor
     *
     * Initialize the plugin by setting up hooks and filters.
     */
    public function __construct() {
        // Initialize plugin
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        
        // Register activation and deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize the plugin
     *
     * Load text domain and set up hooks.
     */
    public function init() {
        // Load plugin text domain for translations
        load_plugin_textdomain( 'notrack', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        
        // Add settings page to the admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Add frontend functionality
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // Add shortcode for opt-out form
        add_shortcode( 'notrack_opt_out', array( $this, 'opt_out_shortcode' ) );
    }

    /**
     * Plugin activation
     *
     * Actions to perform when the plugin is activated.
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'cookie_notice' => true,
            'analytics_opt_out' => true,
            'cookie_lifetime' => 30, // days
        );
        
        add_option( 'notrack_options', $default_options );
        
        // Create necessary database tables if needed
        // $this->create_tables();
        
        // Clear any cached data
        wp_cache_flush();
    }

    /**
     * Plugin deactivation
     *
     * Actions to perform when the plugin is deactivated.
     */
    public function deactivate() {
        // Clean up if necessary
        // Note: We don't delete options here to preserve user settings
    }

    /**
     * Add admin menu
     *
     * Create the admin menu and settings page.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'NoTrack Settings', 'notrack' ),
            __( 'NoTrack', 'notrack' ),
            'manage_options',
            'notrack',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Register settings
     *
     * Register the plugin settings with WordPress.
     */
    public function register_settings() {
        register_setting( 'notrack_options', 'notrack_options' );
        
        // Add settings sections and fields here
    }

    /**
     * Settings page
     *
     * Display the settings page content.
     */
    public function settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Settings page HTML will go here
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'NoTrack Settings', 'notrack' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        
        settings_fields( 'notrack_options' );
        do_settings_sections( 'notrack' );
        submit_button();
        
        echo '</form>';
        echo '</div>';
    }

    /**
     * Enqueue scripts
     *
     * Load frontend scripts and styles.
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'notrack-style',
            NOTRACK_PLUGIN_URL . 'assets/css/notrack.css',
            array(),
            NOTRACK_VERSION
        );
        
        wp_enqueue_script(
            'notrack-script',
            NOTRACK_PLUGIN_URL . 'assets/js/notrack.js',
            array( 'jquery' ),
            NOTRACK_VERSION,
            true
        );
        
        // Localize script with plugin data
        wp_localize_script(
            'notrack-script',
            'notrack_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'notrack-nonce' ),
            )
        );
    }

    /**
     * Opt-out shortcode
     *
     * Shortcode to display the opt-out form on pages.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function opt_out_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'title' => __( 'Tracking Preferences', 'notrack' ),
            ),
            $atts,
            'notrack_opt_out'
        );
        
        ob_start();
        
        // Shortcode HTML will go here
        echo '<div class="notrack-opt-out-form">';
        echo '<h3>' . esc_html( $atts['title'] ) . '</h3>';
        echo '<p>' . esc_html__( 'Adjust your tracking preferences below:', 'notrack' ) . '</p>';
        
        // Form fields will go here
        
        echo '</div>';
        
        return ob_get_clean();
    }
}

// Initialize the plugin
$notrack = new NoTrack();