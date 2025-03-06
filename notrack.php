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
 * @author            Phoenix Online Media
 * @copyright         2023 Phoenix Online Media
 * @license           GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       NoTrack
 * Plugin URI:        https://github.com/PhoenixOnlineMedia-AI/NoTrack
 * Description:       A WordPress plugin for user tracking opt-out functionality.
 * Version:           1.0.0
 * Author:            Phoenix Online Media
 * Author URI:        https://phoenixonlinemedia.com
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
 * Get supported tracking services
 *
 * This function returns an array of tracking services supported by the plugin.
 * Each tracker includes:
 * - label: Human-readable name of the tracking service
 * - opt_out_type: The mechanism used to opt out ('script' or 'cookie')
 * - parameters: Any additional parameters needed for the opt-out process
 *
 * The opt-out types work as follows:
 * - 'script': Prevents the tracking script from loading or initializing
 * - 'cookie': Sets opt-out cookies that the tracking service respects
 *
 * @since 1.0.0
 * @return array Array of supported tracking services with their properties
 */
function notrack_get_supported_trackers() {
    return array(
        'google_analytics' => array(
            'label' => __( 'Google Analytics', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'tracking_id' => '', // Google Analytics tracking ID (e.g., UA-XXXXX-Y or G-XXXXXXXX)
            ),
            'description' => __( 'Prevents Google Analytics from tracking page views and user interactions.', 'notrack' ),
        ),
        'microsoft_clarity' => array(
            'label' => __( 'Microsoft Clarity', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(),
            'description' => __( 'Disables Microsoft Clarity session recording and heatmap functionality.', 'notrack' ),
        ),
        'hotjar' => array(
            'label' => __( 'Hotjar', 'notrack' ),
            'opt_out_type' => 'cookie',
            'parameters' => array(),
            'description' => __( 'Sets the _hjOptOut cookie to prevent Hotjar from collecting data.', 'notrack' ),
        ),
    );
}

/**
 * Sanitize tracker settings
 *
 * This function sanitizes the tracker settings before saving them to the database.
 * It ensures that only valid trackers are saved and that their parameters are
 * properly sanitized to prevent security issues.
 *
 * @since 1.0.0
 * @param array $input The unsanitized tracker settings.
 * @return array The sanitized tracker settings.
 */
function notrack_sanitize_trackers( $input ) {
    $sanitized_input = array();
    $supported_trackers = notrack_get_supported_trackers();
    
    // Loop through each supported tracker
    foreach ( $supported_trackers as $tracker_id => $tracker_data ) {
        // Check if the tracker is enabled
        if ( isset( $input[$tracker_id]['enabled'] ) ) {
            $sanitized_input[$tracker_id]['enabled'] = true;
        } else {
            $sanitized_input[$tracker_id]['enabled'] = false;
        }
        
        // Sanitize parameters if they exist
        if ( ! empty( $tracker_data['parameters'] ) ) {
            foreach ( $tracker_data['parameters'] as $param_key => $param_value ) {
                if ( isset( $input[$tracker_id]['parameters'][$param_key] ) ) {
                    // Sanitize the parameter value
                    $sanitized_input[$tracker_id]['parameters'][$param_key] = 
                        sanitize_text_field( $input[$tracker_id]['parameters'][$param_key] );
                } else {
                    $sanitized_input[$tracker_id]['parameters'][$param_key] = '';
                }
            }
        }
    }
    
    return $sanitized_input;
}

/**
 * Output tracking opt-out JavaScript in the head
 *
 * This function is hooked to 'wp_head' with priority 1 to ensure it runs before
 * any tracking scripts are loaded. It checks if the user has opted out of tracking
 * by looking for the 'notrack_opted_out' cookie. If the cookie is set to 'true',
 * it outputs JavaScript code to prevent enabled trackers from functioning.
 *
 * For trackers with opt-out type 'script', it generates JavaScript that prevents
 * the tracking scripts from initializing or loading. Each tracker has its own
 * specific implementation based on how that service works.
 *
 * This approach ensures that tracking is disabled before the tracking scripts
 * have a chance to execute, providing effective opt-out functionality.
 *
 * @since 1.0.0
 * @return void
 */
function notrack_wp_head() {
    // Only proceed if the opt-out cookie is set
    ?>
    <script type="text/javascript">
    (function() {
        // Check if the user has opted out
        function getCookie(name) {
            var value = "; " + document.cookie;
            var parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
            return null;
        }
        
        // If the opt-out cookie is set to true, disable tracking
        if (getCookie('notrack_opted_out') === 'true') {
            <?php
            // Get enabled trackers
            $trackers = get_option('notrack_trackers', array());
            $supported_trackers = notrack_get_supported_trackers();
            
            // Loop through enabled trackers
            foreach ($trackers as $tracker_id => $tracker_config) {
                // Skip if not enabled
                if (empty($tracker_config['enabled'])) {
                    continue;
                }
                
                // Get tracker data
                $tracker_data = isset($supported_trackers[$tracker_id]) ? $supported_trackers[$tracker_id] : null;
                
                // Skip if tracker not found or not script type
                if (!$tracker_data || $tracker_data['opt_out_type'] !== 'script') {
                    continue;
                }
                
                // Output tracker-specific opt-out code
                switch ($tracker_id) {
                    case 'google_analytics':
                        // Get tracking ID if available
                        $tracking_id = isset($tracker_config['parameters']['tracking_id']) ? 
                            $tracker_config['parameters']['tracking_id'] : '';
                        ?>
                        // Disable Google Analytics
                        window['ga-disable-<?php echo esc_js($tracking_id); ?>'] = true;
                        // Prevent gtag from initializing
                        window.dataLayer = window.dataLayer || [];
                        function gtag(){dataLayer.push(arguments);}
                        gtag('consent', 'default', {
                            'analytics_storage': 'denied'
                        });
                        <?php
                        break;
                        
                    case 'microsoft_clarity':
                        ?>
                        // Disable Microsoft Clarity
                        window['clarity'] = window['clarity'] || function() {};
                        window['clarity'].q = [];
                        window['clarity'].q.push(['disable', true]);
                        <?php
                        break;
                        
                    case 'hotjar':
                        // Hotjar uses cookies, but we can also disable it via script
                        ?>
                        // Disable Hotjar
                        window['hjDisableHeatmaps'] = true;
                        window['hjDisableSurveyInvites'] = true;
                        window['hjDisableRecordings'] = true;
                        <?php
                        break;
                }
            }
            ?>
            
            console.log('NoTrack: User has opted out of tracking. Tracking scripts disabled.');
        }
    })();
    </script>
    <?php
}

/**
 * Enqueue NoTrack frontend scripts
 *
 * This function is hooked to 'wp_enqueue_scripts' to load the necessary JavaScript
 * for the NoTrack plugin on the frontend. It enqueues the main notrack.js file and
 * passes data about enabled trackers to the script.
 *
 * The script is loaded in the footer to ensure it doesn't block page rendering and
 * has access to the DOM. The localized data includes an array of enabled tracker keys
 * from the plugin settings, allowing the JavaScript to know which trackers to handle.
 *
 * This approach separates the configuration from the implementation, making the
 * JavaScript more maintainable and the plugin more extensible.
 *
 * @since 1.0.0
 * @return void
 */
function notrack_enqueue_scripts() {
    // Enqueue the main NoTrack script
    wp_enqueue_script(
        'notrack-frontend',
        NOTRACK_PLUGIN_URL . 'assets/js/notrack.js',
        array('jquery'),
        NOTRACK_VERSION,
        true // Load in footer
    );
    
    // Get enabled trackers from options
    $trackers = get_option('notrack_trackers', array());
    $enabled_trackers = array();
    
    // Build array of enabled tracker keys
    foreach ($trackers as $tracker_id => $tracker_config) {
        if (!empty($tracker_config['enabled'])) {
            $enabled_trackers[] = $tracker_id;
        }
    }
    
    // Pass data to script
    wp_localize_script(
        'notrack-frontend',
        'notrack_data',
        array(
            'enabled_trackers' => $enabled_trackers,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('notrack-nonce')
        )
    );
}

/**
 * Handle AJAX requests for updating tracking preferences
 *
 * This function processes AJAX requests from the opt-out form to update
 * user tracking preferences. It verifies the nonce for security and sets
 * the appropriate cookies based on the user's choices.
 *
 * @since 1.0.0
 * @return void
 */
function notrack_ajax_update_preferences() {
    // Check nonce for security
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'notrack-nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'notrack' ) ) );
    }
    
    // Process form data
    $form_data = array();
    if ( isset( $_POST['form_data'] ) ) {
        parse_str( $_POST['form_data'], $form_data );
    }
    
    // Set opt-out cookie
    $opt_out = isset( $form_data['opt_out'] ) && $form_data['opt_out'] === '1';
    
    // Set cookie expiration to 1 year
    $expiry = time() + ( 365 * 24 * 60 * 60 );
    
    // Set the cookie
    setcookie( 'notrack_opted_out', $opt_out ? 'true' : 'false', $expiry, '/', '', is_ssl(), true );
    
    // Send success response
    wp_send_json_success( array(
        'message' => $opt_out ? 
            __( 'You have successfully opted out of tracking.', 'notrack' ) : 
            __( 'You have opted back into tracking.', 'notrack' ),
        'opted_out' => $opt_out
    ) );
}

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
        
        // Add shortcode for simple opt-out button
        add_shortcode( 'notrack_opt_out_button', array( $this, 'opt_out_button_shortcode' ) );
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
     * Register the plugin settings with WordPress Settings API.
     * Creates sections and fields for the tracking services configuration.
     */
    public function register_settings() {
        // Register the tracker settings
        register_setting( 
            'notrack_options',           // Option group
            'notrack_trackers',          // Option name
            array(
                'sanitize_callback' => 'notrack_sanitize_trackers',
                'default' => array()
            )
        );
        
        // Register general plugin settings
        register_setting( 'notrack_options', 'notrack_options' );
        
        // Add settings section for tracking services
        add_settings_section(
            'notrack_tracking_services_section',                // ID
            __( 'Tracking Services', 'notrack' ),               // Title
            array( $this, 'tracking_services_section_callback' ),// Callback
            'notrack'                                           // Page
        );
        
        // Get all supported trackers
        $trackers = notrack_get_supported_trackers();
        
        // Add settings fields for each tracker
        foreach ( $trackers as $tracker_id => $tracker_data ) {
            add_settings_field(
                'notrack_tracker_' . $tracker_id,                // ID
                $tracker_data['label'],                          // Title
                array( $this, 'tracker_field_callback' ),        // Callback
                'notrack',                                       // Page
                'notrack_tracking_services_section',             // Section
                array(
                    'tracker_id' => $tracker_id,
                    'tracker_data' => $tracker_data
                )
            );
        }
    }
    
    /**
     * Tracking Services Section Callback
     *
     * Displays the description for the tracking services section.
     */
    public function tracking_services_section_callback() {
        echo '<p>' . esc_html__( 'Configure which tracking services users can opt out of. Enable the services you use on your site.', 'notrack' ) . '</p>';
    }
    
    /**
     * Tracker Field Callback
     *
     * Renders the form fields for each tracker.
     *
     * @param array $args Arguments passed to the callback.
     */
    public function tracker_field_callback( $args ) {
        $tracker_id = $args['tracker_id'];
        $tracker_data = $args['tracker_data'];
        
        // Get saved options
        $options = get_option( 'notrack_trackers', array() );
        
        // Check if tracker is enabled
        $enabled = isset( $options[$tracker_id]['enabled'] ) ? $options[$tracker_id]['enabled'] : false;
        
        // Output the checkbox for enabling/disabling the tracker
        echo '<div class="notrack-tracker-field">';
        echo '<label>';
        echo '<input type="checkbox" name="notrack_trackers[' . esc_attr( $tracker_id ) . '][enabled]" value="1" ' . checked( $enabled, true, false ) . ' />';
        echo esc_html__( 'Enable', 'notrack' ) . ' ' . esc_html( $tracker_data['label'] ) . ' ' . esc_html__( 'opt-out', 'notrack' );
        echo '</label>';
        
        // Display description if available
        if ( isset( $tracker_data['description'] ) ) {
            echo '<p class="description">' . esc_html( $tracker_data['description'] ) . '</p>';
        }
        
        // If the tracker has parameters, add input fields for them
        if ( ! empty( $tracker_data['parameters'] ) ) {
            echo '<div class="notrack-tracker-parameters">';
            
            foreach ( $tracker_data['parameters'] as $param_key => $param_default ) {
                $param_value = isset( $options[$tracker_id]['parameters'][$param_key] ) ? 
                    $options[$tracker_id]['parameters'][$param_key] : $param_default;
                
                echo '<div class="notrack-parameter-field">';
                echo '<label>';
                echo '<span class="notrack-parameter-label">' . esc_html( ucfirst( str_replace( '_', ' ', $param_key ) ) ) . ':</span>';
                echo '<input type="text" name="notrack_trackers[' . esc_attr( $tracker_id ) . '][parameters][' . esc_attr( $param_key ) . ']" ';
                echo 'value="' . esc_attr( $param_value ) . '" class="regular-text" />';
                echo '</label>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Settings page
     *
     * Display the settings page content with all registered sections and fields.
     */
    public function settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Add settings error/update messages
        settings_errors( 'notrack_messages' );
        
        // Settings page HTML
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'NoTrack Settings', 'notrack' ) . '</h1>';
        echo '<p>' . esc_html__( 'Configure which tracking services to allow users to opt out of.', 'notrack' ) . '</p>';
        
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
        
        // Get enabled trackers
        $trackers = get_option( 'notrack_trackers', array() );
        $supported_trackers = notrack_get_supported_trackers();
        
        // Check if user has opted out
        $opted_out = isset( $_COOKIE['notrack_opted_out'] ) && $_COOKIE['notrack_opted_out'] === 'true';
        
        echo '<div class="notrack-opt-out-form">';
        echo '<h3>' . esc_html( $atts['title'] ) . '</h3>';
        
        // Display current status
        echo '<div class="notrack-status' . ( $opted_out ? ' opted-out' : '' ) . '">';
        if ( $opted_out ) {
            echo esc_html__( 'You have opted out of tracking.', 'notrack' );
        } else {
            echo esc_html__( 'Tracking is currently enabled.', 'notrack' );
        }
        echo '</div>';
        
        // Simple opt-out button
        echo '<button class="notrack-opt-out-button" data-action="' . ( $opted_out ? 'opt-in' : 'opt-out' ) . '">';
        echo $opted_out ? esc_html__( 'Opt In', 'notrack' ) : esc_html__( 'Opt Out', 'notrack' );
        echo '</button>';
        
        // Display information about enabled trackers
        if ( ! empty( $trackers ) ) {
            echo '<div class="notrack-trackers-info">';
            echo '<h4>' . esc_html__( 'Tracking Services', 'notrack' ) . '</h4>';
            echo '<p>' . esc_html__( 'The following tracking services are used on this site:', 'notrack' ) . '</p>';
            echo '<ul>';
            
            foreach ( $trackers as $tracker_id => $tracker_config ) {
                // Skip if not enabled
                if ( empty( $tracker_config['enabled'] ) ) {
                    continue;
                }
                
                // Get tracker data
                $tracker_data = isset( $supported_trackers[$tracker_id] ) ? $supported_trackers[$tracker_id] : null;
                
                if ( $tracker_data ) {
                    echo '<li>';
                    echo '<strong>' . esc_html( $tracker_data['label'] ) . '</strong>';
                    
                    if ( isset( $tracker_data['description'] ) ) {
                        echo '<p class="description">' . esc_html( $tracker_data['description'] ) . '</p>';
                    }
                    
                    echo '</li>';
                }
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }

    /**
     * Opt-out button shortcode
     *
     * Shortcode to display a simple opt-out button on pages.
     * This provides a more minimal alternative to the full opt-out form,
     * allowing users to quickly opt out of tracking with a single button.
     *
     * Usage: [notrack_opt_out_button]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function opt_out_button_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'text' => __( 'Opt Out of Tracking', 'notrack' ),
            ),
            $atts,
            'notrack_opt_out_button'
        );
        
        // Check if user has opted out
        $opted_out = isset( $_COOKIE['notrack_opted_out'] ) && $_COOKIE['notrack_opted_out'] === 'true';
        
        ob_start();
        
        // Output the button with appropriate text based on current opt-out status
        echo '<button id="notrack-opt-out" class="notrack-opt-out-button" data-action="' . 
            ( $opted_out ? 'opt-in' : 'opt-out' ) . '">';
        echo $opted_out ? 
            esc_html__( 'Opt In to Tracking', 'notrack' ) : 
            esc_html( $atts['text'] );
        echo '</button>';
        
        return ob_get_clean();
    }
}

// Initialize the plugin
$notrack = new NoTrack();

// Hook the notrack_wp_head function to wp_head with priority 1
add_action('wp_head', 'notrack_wp_head', 1);

// Hook the notrack_enqueue_scripts function to wp_enqueue_scripts
add_action('wp_enqueue_scripts', 'notrack_enqueue_scripts');

// Hook the AJAX handler
add_action('wp_ajax_notrack_update_preferences', 'notrack_ajax_update_preferences');
add_action('wp_ajax_nopriv_notrack_update_preferences', 'notrack_ajax_update_preferences');