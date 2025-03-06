<?php
/**
 * Plugin Name: NoTrack
 * Plugin URI: https://github.com/PhoenixOnlineMedia-AI/NoTrack
 * Description: Provides user tracking opt-out functionality for WordPress sites.
 * Version: 1.0.0
 * Author: Phoenix Online Media AI
 * Author URI: https://github.com/PhoenixOnlineMedia-AI
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: notrack
 * Domain Path: /languages
 *
 * @package NoTrack
 */

// Security Check: Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include admin menu functions
require_once plugin_dir_path(__FILE__) . 'admin-menu.php';

/**
 * Define plugin constants
 */
define( 'NOTRACK_VERSION', '1.0.0' );
define( 'NOTRACK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOTRACK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Get supported tracking services
 * 
 * Returns an array of tracking services supported by the plugin.
 * 
 * @since 1.0.0
 * @return array Array of supported tracking services with their details
 */
function notrack_get_supported_trackers() {
    $trackers = array(
        'google_analytics' => array(
            'label' => __('Google Analytics', 'notrack'),
            'description' => __('Opt out of Google Analytics tracking.', 'notrack'),
            'keywords' => array('google-analytics', 'googleanalytics', 'ga.js', 'analytics.js', 'gtag', 'google tag', 'UA-'),
            'id_pattern' => '/UA-[0-9]+-[0-9]+|G-[A-Z0-9]+/',
            'opt_out_type' => 'script',
            'script_data' => "window['ga-disable-' + trackerID] = true;"
        ),
        'facebook_pixel' => array(
            'label' => __('Facebook Pixel', 'notrack'),
            'description' => __('Opt out of Facebook Pixel tracking.', 'notrack'),
            'keywords' => array('facebook', 'fbq', 'facebook pixel', 'fb pixel', 'fbevents.js'),
            'id_pattern' => '/[0-9]{15,16}/',
            'opt_out_type' => 'script',
            'script_data' => "window.fbq = function() { return null; };"
        ),
        'google_tag_manager' => array(
            'label' => __('Google Tag Manager', 'notrack'),
            'description' => __('Opt out of Google Tag Manager.', 'notrack'),
            'keywords' => array('googletagmanager', 'gtm.js', 'gtm', 'tag manager'),
            'id_pattern' => '/GTM-[A-Z0-9]+/',
            'opt_out_type' => 'script',
            'script_data' => "window.dataLayer = window.dataLayer || []; window.dataLayer.push({'gtm.blacklist': ['google', 'adwords', 'analytics', 'doubleclick', 'twitter', 'facebook']});"
        ),
        'hotjar' => array(
            'label' => __('Hotjar', 'notrack'),
            'description' => __('Opt out of Hotjar analytics and feedback tools.', 'notrack'),
            'keywords' => array('hotjar', 'hjsv', 'hj.q'),
            'id_pattern' => '/[0-9]{7}/',
            'opt_out_type' => 'script',
            'script_data' => "window.hj = function() { return null; }; window._hjSettings = null;"
        ),
        'hubspot' => array(
            'label' => __('HubSpot', 'notrack'),
            'description' => __('Opt out of HubSpot analytics and marketing tools.', 'notrack'),
            'keywords' => array('hubspot', 'hs-script', 'hs-analytics', '_hsq'),
            'id_pattern' => '/[0-9]{7}/',
            'opt_out_type' => 'script',
            'script_data' => "window._hsq = window._hsq || []; window._hsq.push(['doNotTrack']);"
        ),
        'linkedin_insight' => array(
            'label' => __('LinkedIn Insight', 'notrack'),
            'description' => __('Opt out of LinkedIn Insight Tag tracking.', 'notrack'),
            'keywords' => array('linkedin', '_linkedin_data_partner_id', 'linkedin insight'),
            'id_pattern' => '/[0-9]{6,8}/',
            'opt_out_type' => 'script',
            'script_data' => "window._linkedin_data_partner_ids = []; window._linkedin_data_partner_id = null;"
        ),
        'matomo' => array(
            'label' => __('Matomo (Piwik)', 'notrack'),
            'description' => __('Opt out of Matomo (formerly Piwik) analytics.', 'notrack'),
            'keywords' => array('matomo', 'piwik', '_paq'),
            'id_pattern' => '/[0-9]+/',
            'opt_out_type' => 'script',
            'script_data' => "window._paq = window._paq || []; window._paq.push(['optUserOut']);"
        ),
        'twitter_pixel' => array(
            'label' => __('Twitter Pixel', 'notrack'),
            'description' => __('Opt out of Twitter conversion tracking.', 'notrack'),
            'keywords' => array('twitter', 'twq', 'twitter pixel', 'twitter universal tag'),
            'id_pattern' => '/[a-z0-9]{5,10}/',
            'opt_out_type' => 'script',
            'script_data' => "window.twq = function() { return null; };"
        ),
        'google_adsense' => array(
            'label' => __('Google AdSense', 'notrack'),
            'description' => __('Opt out of personalized Google AdSense ads.', 'notrack'),
            'keywords' => array('adsbygoogle', 'adsense', 'google ads', 'pagead'),
            'id_pattern' => '/ca-pub-[0-9]+/',
            'opt_out_type' => 'script',
            'script_data' => "(adsbygoogle=window.adsbygoogle||[]).requestNonPersonalizedAds=1;"
        ),
        'google_remarketing' => array(
            'label' => __('Google Remarketing', 'notrack'),
            'description' => __('Opt out of Google remarketing tracking.', 'notrack'),
            'keywords' => array('remarketing', 'conversion_async.js', 'conversion_id'),
            'id_pattern' => '/[0-9]{9,11}/',
            'opt_out_type' => 'cookie',
            'cookie_name' => 'NID',
            'cookie_value' => 'opt_out',
            'cookie_domain' => '.google.com'
        ),
        'doubleclick' => array(
            'label' => __('DoubleClick', 'notrack'),
            'description' => __('Opt out of DoubleClick ad tracking.', 'notrack'),
            'keywords' => array('doubleclick', 'dc_', 'googleads'),
            'id_pattern' => '/[0-9]{7,9}/',
            'opt_out_type' => 'cookie',
            'cookie_name' => 'id',
            'cookie_value' => 'OPT_OUT',
            'cookie_domain' => '.doubleclick.net'
        ),
        'pinterest_tag' => array(
            'label' => __('Pinterest Tag', 'notrack'),
            'description' => __('Opt out of Pinterest conversion tracking.', 'notrack'),
            'keywords' => array('pinterest', 'pintrk', 'pinterest tag', 'pinterest conversion'),
            'id_pattern' => '/[0-9]{13}/',
            'opt_out_type' => 'script',
            'script_data' => "window.pintrk = function() { return null; };"
        ),
        'microsoft_advertising' => array(
            'label' => __('Microsoft Advertising', 'notrack'),
            'description' => __('Opt out of Microsoft Advertising (Bing Ads) tracking.', 'notrack'),
            'keywords' => array('microsoft', 'bing', 'msn', 'uet', 'bat.js'),
            'id_pattern' => '/[0-9]{7,9}/',
            'opt_out_type' => 'script',
            'script_data' => "window.uetq = [];"
        ),
        'criteo' => array(
            'label' => __('Criteo', 'notrack'),
            'description' => __('Opt out of Criteo retargeting.', 'notrack'),
            'keywords' => array('criteo', 'criteo_q'),
            'id_pattern' => '/[0-9]{4,6}/',
            'opt_out_type' => 'script',
            'script_data' => "window.criteo_q = window.criteo_q || []; window.criteo_q.push({ event: 'setOptOut' });"
        ),
        'taboola' => array(
            'label' => __('Taboola', 'notrack'),
            'description' => __('Opt out of Taboola tracking.', 'notrack'),
            'keywords' => array('taboola', '_tfa'),
            'id_pattern' => '/[0-9]{6,8}/',
            'opt_out_type' => 'script',
            'script_data' => "window._tfa = window._tfa || []; window._tfa.push({ notify: 'event', name: 'opt_out' });"
        ),
        'outbrain' => array(
            'label' => __('Outbrain', 'notrack'),
            'description' => __('Opt out of Outbrain tracking.', 'notrack'),
            'keywords' => array('outbrain', 'obPixel', 'OB_releaseVer'),
            'id_pattern' => '/[A-Z0-9]{10,16}/',
            'opt_out_type' => 'script',
            'script_data' => "window.obApi = function() { return null; };"
        ),
        'quantcast' => array(
            'label' => __('Quantcast', 'notrack'),
            'description' => __('Opt out of Quantcast measurement.', 'notrack'),
            'keywords' => array('quantcast', 'quant.js', '_qevents'),
            'id_pattern' => '/[a-zA-Z0-9]{16}/',
            'opt_out_type' => 'script',
            'script_data' => "window._qevents = window._qevents || []; window._qevents.push({ qacct: trackerID, event: 'refresh_optout' });"
        ),
        'amplitude' => array(
            'label' => __('Amplitude', 'notrack'),
            'description' => __('Opt out of Amplitude analytics.', 'notrack'),
            'keywords' => array('amplitude', 'amplitude.js', 'amplitude.min.js'),
            'id_pattern' => '/[a-z0-9]{32}/',
            'opt_out_type' => 'script',
            'script_data' => "window.amplitude = { getInstance: function() { return { setOptOut: function() {} }; } };"
        ),
        'mixpanel' => array(
            'label' => __('Mixpanel', 'notrack'),
            'description' => __('Opt out of Mixpanel analytics.', 'notrack'),
            'keywords' => array('mixpanel', 'mixpanel.js', 'mixpanel-'),
            'id_pattern' => '/[a-z0-9]{32}/',
            'opt_out_type' => 'script',
            'script_data' => "window.mixpanel = { opt_out_tracking: function() {}, track: function() {} };"
        ),
        'segment' => array(
            'label' => __('Segment', 'notrack'),
            'description' => __('Opt out of Segment analytics.', 'notrack'),
            'keywords' => array('segment', 'analytics.js', 'segment.com/analytics.js'),
            'id_pattern' => '/[a-z0-9]{32}/',
            'opt_out_type' => 'script',
            'script_data' => "window.analytics = { user: function() { return { anonymize: function() {} }; } }; window.analytics.user().anonymize();"
        )
    );
    
    return apply_filters('notrack_supported_trackers', $trackers);
}

/**
 * Sanitize tracker settings
 *
 * This function sanitizes the tracker settings before saving them to the database.
 * It ensures that only valid trackers are saved and that their parameters are
 * properly sanitized to prevent security issues.
 *
 * Security measures:
 * - Validates tracker IDs against supported trackers
 * - Sanitizes all parameter values with sanitize_text_field()
 * - Ensures boolean values are properly cast
 * - Prevents injection of malicious data
 *
 * @since 1.0.0
 * @param array $input The unsanitized tracker settings.
 * @return array The sanitized tracker settings.
 */
function notrack_sanitize_trackers( $input ) {
    $sanitized_input = array();
    $supported_trackers = notrack_get_supported_trackers();
    
    // Ensure input is an array
    if ( ! is_array( $input ) ) {
        return $sanitized_input;
    }
    
    // Loop through each supported tracker
    foreach ( $supported_trackers as $tracker_id => $tracker_data ) {
        // Validate tracker ID
        $tracker_id = sanitize_key( $tracker_id );
        
        // Check if the tracker is enabled
        if ( isset( $input[$tracker_id]['enabled'] ) ) {
            $sanitized_input[$tracker_id]['enabled'] = (bool) $input[$tracker_id]['enabled'];
        } else {
            $sanitized_input[$tracker_id]['enabled'] = false;
        }
        
        // Sanitize parameters if they exist
        if ( ! empty( $tracker_data['parameters'] ) ) {
            $sanitized_input[$tracker_id]['parameters'] = array();
            
            foreach ( $tracker_data['parameters'] as $param_key => $param_default ) {
                // Validate parameter key
                $param_key = sanitize_key( $param_key );
                
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
 * Output tracking opt-out scripts in the head section
 *
 * This function outputs JavaScript in the head section to disable tracking
 * for users who have opted out. It runs before tracking scripts load, allowing
 * it to disable them before they initialize.
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
            // Get enabled trackers from options
            $options = get_option('notrack_options', array());
            $enabled_trackers = isset($options['trackers']) ? $options['trackers'] : array();
            
            // Get detected trackers
            $detected_trackers = get_option('notrack_detected_tools', array());
            
            // Get supported trackers data
            $supported_trackers = notrack_get_supported_trackers();
            
            // Create a map of detected trackers by service name for easy lookup
            $detected_tracker_map = array();
            foreach ($detected_trackers as $tracker) {
                $detected_tracker_map[$tracker['service']] = $tracker;
            }
            
            // Loop through enabled trackers
            foreach ($enabled_trackers as $service => $tracker_config) {
                // Skip if not enabled
                if (empty($tracker_config['enabled'])) {
                    continue;
                }
                
                // Get tracker data from supported trackers
                $tracker_data = isset($supported_trackers[$service]) ? $supported_trackers[$service] : null;
                
                // Skip if tracker not found or not script type
                if (!$tracker_data || $tracker_data['opt_out_type'] !== 'script') {
                    continue;
                }
                
                // Get tracker ID from config or detected tracker
                $tracker_id = '';
                if (!empty($tracker_config['id'])) {
                    $tracker_id = $tracker_config['id'];
                } elseif (isset($detected_tracker_map[$service]) && !empty($detected_tracker_map[$service]['id'])) {
                    $tracker_id = $detected_tracker_map[$service]['id'];
                }
                
                // Ensure the ID is properly escaped
                $tracker_id = esc_js($tracker_id);
                
                // Output tracker-specific opt-out code
                switch ($service) {
                    case 'google_analytics':
                        ?>
                        // Disable Google Analytics
                        window['ga-disable-<?php echo $tracker_id; ?>'] = true;
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
                        
                    case 'facebook_pixel':
                        ?>
                        // Disable Facebook Pixel
                        window.fbq = function() {
                            window.fbq.callMethod ? window.fbq.callMethod.apply(window.fbq, arguments) : window.fbq.queue.push(arguments);
                        };
                        window.fbq.push = window.fbq;
                        window.fbq.loaded = true;
                        window.fbq.version = '2.0';
                        window.fbq.queue = [];
                        window.fbq('consent', 'revoke');
                        <?php
                        break;
                        
                    case 'linkedin_insight':
                        ?>
                        // Disable LinkedIn Insight Tag
                        window._linkedin_data_partner_ids = [];
                        window._linkedin_data_partner_ids.push(<?php echo esc_js($partner_id); ?>);
                        window._linkedin_data_partner_id = null;
                        <?php
                        break;
                        
                    case 'twitter_pixel':
                        // Get pixel ID if available
                        $pixel_id = isset($tracker_config['parameters']['pixel_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['pixel_id']) : '';
                        ?>
                        // Disable Twitter Pixel
                        window.twq = function() {
                            window.twq.exe ? window.twq.exe.apply(window.twq, arguments) : window.twq.queue.push(arguments);
                        };
                        window.twq.version = '1.1';
                        window.twq.queue = [];
                        <?php
                        break;
                        
                    case 'pinterest_tag':
                        // Get tag ID if available
                        $tag_id = isset($tracker_config['parameters']['tag_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['tag_id']) : '';
                        ?>
                        // Disable Pinterest Tag
                        window.pintrk = function() {
                            window.pintrk.queue.push(Array.prototype.slice.call(arguments));
                        };
                        window.pintrk.queue = [];
                        window.pintrk.version = '3.0';
                        window.pintrk('set', {
                            np: '1'  // np = no-pinterest, disables tracking
                        });
                        <?php
                        break;
                        
                    case 'tiktok_pixel':
                        // Get pixel ID if available
                        $pixel_id = isset($tracker_config['parameters']['pixel_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['pixel_id']) : '';
                        ?>
                        // Disable TikTok Pixel
                        window.ttq = window.ttq || {};
                        window.ttq.track = function() {};
                        window.ttq.page = function() {};
                        window.ttq.identify = function() {};
                        window.ttq.instance = function() {};
                        <?php
                        break;
                        
                    case 'snapchat_pixel':
                        // Get pixel ID if available
                        $pixel_id = isset($tracker_config['parameters']['pixel_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['pixel_id']) : '';
                        ?>
                        // Disable Snapchat Pixel
                        window.snaptr = function() {
                            window.snaptr.handleRequest ? window.snaptr.handleRequest.apply(window.snaptr, arguments) : window.snaptr.queue.push(arguments);
                        };
                        window.snaptr.queue = [];
                        <?php
                        break;
                        
                    case 'hubspot':
                        // Get hub ID if available
                        $hub_id = isset($tracker_config['parameters']['hub_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['hub_id']) : '';
                        ?>
                        // Disable HubSpot
                        window._hsq = window._hsq || [];
                        window._hsq.push(['doNotTrack']);
                        <?php
                        break;
                        
                    case 'intercom':
                        // Get app ID if available
                        $app_id = isset($tracker_config['parameters']['app_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['app_id']) : '';
                        ?>
                        // Disable Intercom
                        window.intercomSettings = {
                            app_id: '<?php echo esc_js($app_id); ?>',
                            hide_default_launcher: true
                        };
                        window.Intercom = function() {
                            window.Intercom.q.push(arguments);
                        };
                        window.Intercom.q = [];
                        window.Intercom('boot', {
                            app_id: '<?php echo esc_js($app_id); ?>',
                            hide_default_launcher: true
                        });
                        <?php
                        break;
                        
                    case 'mixpanel':
                        // Get project token if available
                        $project_token = isset($tracker_config['parameters']['project_token']) ? 
                            sanitize_text_field($tracker_config['parameters']['project_token']) : '';
                        ?>
                        // Disable Mixpanel
                        window.mixpanel = {
                            track: function() {},
                            track_links: function() {},
                            track_forms: function() {},
                            identify: function() {},
                            alias: function() {},
                            people: {
                                set: function() {},
                                increment: function() {},
                                track_charge: function() {}
                            },
                            opt_out_tracking: function() {}
                        };
                        window.mixpanel.opt_out_tracking();
                        <?php
                        break;
                        
                    case 'amplitude':
                        // Get API key if available
                        $api_key = isset($tracker_config['parameters']['api_key']) ? 
                            sanitize_text_field($tracker_config['parameters']['api_key']) : '';
                        ?>
                        // Disable Amplitude
                        window.amplitude = {
                            getInstance: function() {
                                return {
                                    init: function() {},
                                    logEvent: function() {},
                                    setUserId: function() {},
                                    setUserProperties: function() {}
                                };
                            }
                        };
                        <?php
                        break;
                        
                    case 'segment':
                        // Get write key if available
                        $write_key = isset($tracker_config['parameters']['write_key']) ? 
                            sanitize_text_field($tracker_config['parameters']['write_key']) : '';
                        ?>
                        // Disable Segment
                        window.analytics = {
                            track: function() {},
                            trackLink: function() {},
                            trackForm: function() {},
                            identify: function() {},
                            page: function() {},
                            group: function() {},
                            alias: function() {},
                            ready: function() {}
                        };
                        <?php
                        break;
                        
                    case 'fullstory':
                        // Get org ID if available
                        $org_id = isset($tracker_config['parameters']['org_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['org_id']) : '';
                        ?>
                        // Disable FullStory
                        window['_fs_host'] = 'www.fullstory.com';
                        window['_fs_script'] = 'edge.fullstory.com/s/fs.js';
                        window['_fs_org'] = '<?php echo esc_js($org_id); ?>';
                        window['_fs_namespace'] = 'FS';
                        window['_fs_run_in_iframe'] = true;
                        window['_fs_capture'] = false;
                        <?php
                        break;
                        
                    case 'crazy_egg':
                        // Get account ID if available
                        $account_id = isset($tracker_config['parameters']['account_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['account_id']) : '';
                        ?>
                        // Disable Crazy Egg
                        window.CE2 = null;
                        window._ceiq = null;
                        <?php
                        break;
                        
                    case 'lucky_orange':
                        // Get site ID if available
                        $site_id = isset($tracker_config['parameters']['site_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['site_id']) : '';
                        ?>
                        // Disable Lucky Orange
                        window.__lo_site_id = <?php echo esc_js($site_id); ?>;
                        window.__wtw_lucky_site_id = window.__lo_site_id;
                        window.__lo_disable = true;
                        <?php
                        break;
                        
                    case 'mouseflow':
                        // Get website ID if available
                        $website_id = isset($tracker_config['parameters']['website_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['website_id']) : '';
                        ?>
                        // Disable Mouseflow
                        window._mfq = window._mfq || [];
                        window._mfq.push(['setVariable', 'disabled', true]);
                        <?php
                        break;
                        
                    case 'pardot':
                        // Get account ID if available
                        $account_id = isset($tracker_config['parameters']['account_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['account_id']) : '';
                        ?>
                        // Disable Pardot
                        window.piAId = '<?php echo esc_js($account_id); ?>';
                        window.piDisable = true;
                        <?php
                        break;
                        
                    case 'hotjar':
                        // All values are hardcoded, no need for escaping
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
 * has access to the DOM. The localized data includes information about enabled trackers
 * and their opt-out types, allowing the JavaScript to handle them appropriately.
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
    $options = get_option('notrack_options', array());
    $enabled_trackers = isset($options['trackers']) ? $options['trackers'] : array();
    
    // Get custom triggers
    $custom_triggers = get_option('notrack_custom_triggers', '');
    
    // Get supported trackers data
    $supported_trackers = notrack_get_supported_trackers();
    
    // Get detected trackers
    $detected_trackers = get_option('notrack_detected_tools', array());
    
    // Create a map of detected trackers by service name for easy lookup
    $detected_tracker_map = array();
    foreach ($detected_trackers as $tracker) {
        $detected_tracker_map[$tracker['service']] = $tracker;
    }
    
    // Build arrays of enabled trackers by type
    $script_trackers = array();
    $cookie_trackers = array();
    
    foreach ($enabled_trackers as $service => $tracker_config) {
        // Skip if not enabled
        if (empty($tracker_config['enabled'])) {
            continue;
        }
        
        // Get tracker data
        $tracker_data = isset($supported_trackers[$service]) ? $supported_trackers[$service] : null;
        if (!$tracker_data) {
            continue;
        }
        
        // Get tracker ID from config or detected tracker
        $tracker_id = '';
        if (!empty($tracker_config['id'])) {
            $tracker_id = $tracker_config['id'];
        } elseif (isset($detected_tracker_map[$service]) && !empty($detected_tracker_map[$service]['id'])) {
            $tracker_id = $detected_tracker_map[$service]['id'];
        }
        
        // Add to appropriate array based on opt-out type
        if ($tracker_data['opt_out_type'] === 'script') {
            $script_trackers[] = array(
                'service' => $service,
                'id' => $tracker_id
            );
        } elseif ($tracker_data['opt_out_type'] === 'cookie') {
            $cookie_trackers[] = array(
                'service' => $service,
                'id' => $tracker_id,
                'cookie_name' => isset($tracker_data['cookie_name']) ? $tracker_data['cookie_name'] : '',
                'cookie_value' => isset($tracker_data['cookie_value']) ? $tracker_data['cookie_value'] : '',
                'cookie_domain' => isset($tracker_data['cookie_domain']) ? $tracker_data['cookie_domain'] : '',
                'cookie_path' => isset($tracker_data['cookie_path']) ? $tracker_data['cookie_path'] : '/'
            );
        }
    }
    
    // Pass data to script
    wp_localize_script(
        'notrack-frontend',
        'notrack_data',
        array(
            'script_trackers' => $script_trackers,
            'cookie_trackers' => $cookie_trackers,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('notrack-nonce'),
            'custom_triggers' => $custom_triggers,
            'enabled_trackers' => array_keys(array_filter($enabled_trackers, function($tracker) {
                return !empty($tracker['enabled']);
            }))
        )
    );
}

/**
 * Scan HTTP headers for tracking service indicators
 *
 * This function performs a HEAD request to the site's URL and checks the response
 * headers for known tracking indicators. Many tracking services add specific HTTP
 * headers that can be used to identify them.
 *
 * The function maps these headers to the corresponding services in the tracking
 * services array and returns matches with detection method 'header'.
 *
 * @since 1.0.0
 * @return array Array of detected trackers with service name, ID (if found), and detection method
 */
function notrack_scan_headers_for_trackers() {
    $detected_trackers = array();
    
    // Get tracking services data
    $tracking_services = notrack_get_supported_trackers();
    
    // Define header mapping (header name => service ID)
    $header_mapping = array(
        'X-GA-Tracking-ID' => 'google_analytics',
        'X-GA-ID' => 'google_analytics',
        'X-FB-Pixel-ID' => 'facebook_pixel',
        'X-Facebook-Pixel' => 'facebook_pixel',
        'X-Hotjar-ID' => 'hotjar',
        'X-LinkedIn-ID' => 'linkedin_insight',
        'X-Pinterest-ID' => 'pinterest_tag',
        'X-TikTok-Pixel' => 'tiktok_pixel',
        'X-Snapchat-Pixel' => 'snapchat_pixel',
        'X-HubSpot' => 'hubspot',
        'X-Matomo-ID' => 'matomo',
        'X-Intercom-ID' => 'intercom',
        'X-Mixpanel-ID' => 'mixpanel',
        'X-Amplitude-ID' => 'amplitude',
        'X-Segment-ID' => 'segment',
        'X-FullStory-ID' => 'fullstory',
        'X-CrazyEgg-ID' => 'crazy_egg',
        'X-LuckyOrange-ID' => 'lucky_orange',
        'X-Mouseflow-ID' => 'mouseflow',
        'X-Pardot-ID' => 'pardot',
        'X-Clarity-ID' => 'microsoft_clarity',
        // Common headers that might contain tracking information
        'X-Analytics' => null, // Need to parse value to determine service
        'X-Tracking' => null,  // Need to parse value to determine service
    );
    
    // Perform HEAD request to site URL
    $response = wp_remote_head(home_url('/'), array(
        'timeout' => 5,
        'sslverify' => false, // Skip SSL verification for internal requests
        'user-agent' => 'NoTrack Scanner/1.0',
    ));
    
    // Check for errors
    if (is_wp_error($response)) {
        // Log error if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NoTrack header scan error: ' . $response->get_error_message());
        }
        return $detected_trackers;
    }
    
    // Get headers from response
    $headers = wp_remote_retrieve_headers($response);
    
    // Convert headers to array if needed
    if (is_object($headers) && method_exists($headers, 'getAll')) {
        $headers = $headers->getAll();
    }
    
    // Check for known tracking headers
    foreach ($header_mapping as $header_name => $service_id) {
        // Skip if service ID is null (requires special parsing)
        if ($service_id === null) {
            continue;
        }
        
        // Check if header exists (case-insensitive)
        $header_value = null;
        foreach ($headers as $name => $value) {
            if (strtolower($name) === strtolower($header_name)) {
                $header_value = $value;
                break;
            }
        }
        
        // If header found, add to detected trackers
        if ($header_value) {
            $tracker_info = array(
                'service' => $service_id,
                'detection_method' => 'header',
                'header' => $header_name,
                'value' => $header_value,
            );
            
            // Try to extract ID using pattern if available
            if (isset($tracking_services[$service_id]['id_pattern'])) {
                $pattern = $tracking_services[$service_id]['id_pattern'];
                if (preg_match($pattern, $header_value, $matches)) {
                    $tracker_info['id'] = $matches[0];
                }
            } else {
                // Use header value as ID if no pattern available
                $tracker_info['id'] = $header_value;
            }
            
            $detected_trackers[] = $tracker_info;
        }
    }
    
    // Special handling for generic tracking headers
    if (isset($headers['X-Analytics']) || isset($headers['x-analytics'])) {
        $analytics_header = isset($headers['X-Analytics']) ? $headers['X-Analytics'] : $headers['x-analytics'];
        
        // Parse X-Analytics header (typically comma-separated key=value pairs)
        $analytics_parts = explode(',', $analytics_header);
        foreach ($analytics_parts as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $key = $pair[0];
                $value = $pair[1];
                
                // Check for known tracking keys
                if (stripos($key, 'ga') !== false || stripos($key, 'google') !== false) {
                    $detected_trackers[] = array(
                        'service' => 'google_analytics',
                        'detection_method' => 'header',
                        'header' => 'X-Analytics',
                        'key' => $key,
                        'value' => $value,
                        'id' => $value,
                    );
                } elseif (stripos($key, 'fb') !== false || stripos($key, 'facebook') !== false) {
                    $detected_trackers[] = array(
                        'service' => 'facebook_pixel',
                        'detection_method' => 'header',
                        'header' => 'X-Analytics',
                        'key' => $key,
                        'value' => $value,
                        'id' => $value,
                    );
                }
                // Add more service checks as needed
            }
        }
    }
    
    // Check for tracking information in Link headers (e.g., preconnect, dns-prefetch)
    if (isset($headers['Link']) || isset($headers['link'])) {
        $link_header = isset($headers['Link']) ? $headers['Link'] : $headers['link'];
        
        // Convert to array if it's not already
        if (!is_array($link_header)) {
            $link_header = array($link_header);
        }
        
        // Define domains to check
        $domain_mapping = array(
            'google-analytics.com' => 'google_analytics',
            'analytics.google.com' => 'google_analytics',
            'googletagmanager.com' => 'google_analytics',
            'connect.facebook.net' => 'facebook_pixel',
            'facebook.com' => 'facebook_pixel',
            'static.hotjar.com' => 'hotjar',
            'script.hotjar.com' => 'hotjar',
            'snap.licdn.com' => 'linkedin_insight',
            'platform.linkedin.com' => 'linkedin_insight',
            'analytics.twitter.com' => 'twitter_pixel',
            'static.ads-twitter.com' => 'twitter_pixel',
            'ct.pinterest.com' => 'pinterest_tag',
            'analytics.tiktok.com' => 'tiktok_pixel',
            'sc-static.net' => 'snapchat_pixel',
            'tr.snapchat.com' => 'snapchat_pixel',
            'js.hs-scripts.com' => 'hubspot',
            'js.hsforms.net' => 'hubspot',
            'matomo.php' => 'matomo',
            'piwik.php' => 'matomo',
            'widget.intercom.io' => 'intercom',
            'cdn.mxpnl.com' => 'mixpanel',
            'api.amplitude.com' => 'amplitude',
            'cdn.segment.com' => 'segment',
            'edge.fullstory.com' => 'fullstory',
            'script.crazyegg.com' => 'crazy_egg',
            'cs.luckyorange.net' => 'lucky_orange',
            'cdn.mouseflow.com' => 'mouseflow',
            'pi.pardot.com' => 'pardot',
            'clarity.ms' => 'microsoft_clarity',
        );
        
        foreach ($link_header as $link) {
            foreach ($domain_mapping as $domain => $service_id) {
                if (stripos($link, $domain) !== false) {
                    $detected_trackers[] = array(
                        'service' => $service_id,
                        'detection_method' => 'header',
                        'header' => 'Link',
                        'value' => $link,
                    );
                    break;
                }
            }
        }
    }
    
    return $detected_trackers;
}

// Hook the notrack_wp_head function to wp_head with priority 1
add_action('wp_head', 'notrack_wp_head', 1);

/**
 * Scan site HTML body for tracking services
 *
 * This function fetches the site's HTML using wp_remote_get(), parses it with
 * DOMDocument, and scans <script> tags (both src attributes and inline content)
 * and <meta> tags for keywords and patterns from the tracking services array.
 *
 * It extracts tracking IDs using the 'id_pattern' and stores matches with
 * detection method 'external_html'.
 *
 * @since 1.0.0
 * @return array Array of detected trackers with service name, ID (if found), and detection method
 */
function notrack_scan_body_for_trackers() {
    $detected_trackers = array();
    
    // Get tracking services data
    $tracking_services = notrack_get_supported_trackers();
    
    // Perform GET request to site URL
    $response = wp_remote_get(home_url('/'), array(
        'timeout' => 10,
        'sslverify' => false, // Skip SSL verification for internal requests
        'user-agent' => 'NoTrack Scanner/1.0',
    ));
    
    // Check for errors
    if (is_wp_error($response)) {
        // Log error if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NoTrack body scan error: ' . $response->get_error_message());
        }
        return $detected_trackers;
    }
    
    // Get body content
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
        return $detected_trackers;
    }
    
    // Use DOMDocument to parse HTML
    libxml_use_internal_errors(true); // Suppress warnings for malformed HTML
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    // Scan <script> tags
    $script_tags = $dom->getElementsByTagName('script');
    foreach ($script_tags as $script) {
        // Check src attribute
        $src = $script->getAttribute('src');
        if (!empty($src)) {
            scan_url_for_trackers($src, $tracking_services, $detected_trackers);
        }
        
        // Check inline script content
        $content = $script->textContent;
        if (!empty($content)) {
            scan_content_for_trackers($content, $tracking_services, $detected_trackers, 'script');
        }
    }
    
    // Scan <meta> tags
    $meta_tags = $dom->getElementsByTagName('meta');
    foreach ($meta_tags as $meta) {
        $name = $meta->getAttribute('name');
        $content = $meta->getAttribute('content');
        
        // Skip empty meta tags
        if (empty($name) && empty($content)) {
            continue;
        }
        
        // Check for tracking-related meta tags
        if (stripos($name, 'google') !== false || 
            stripos($name, 'fb') !== false || 
            stripos($name, 'facebook') !== false || 
            stripos($name, 'twitter') !== false || 
            stripos($name, 'analytics') !== false || 
            stripos($name, 'pixel') !== false) {
            
            // Create meta info string
            $meta_info = $name . ': ' . $content;
            
            // Check against each tracking service
            foreach ($tracking_services as $service_id => $service_data) {
                // Skip if no keywords defined
                if (empty($service_data['keywords'])) {
                    continue;
                }
                
                // Check for keywords in meta info
                $found_keyword = false;
                foreach ($service_data['keywords'] as $keyword) {
                    if (stripos($meta_info, $keyword) !== false) {
                        $found_keyword = true;
                        break;
                    }
                }
                
                // If keyword found, add to detected trackers
                if ($found_keyword) {
                    $tracker_info = array(
                        'service' => $service_id,
                        'detection_method' => 'external_html',
                        'element_type' => 'meta',
                        'element_data' => $meta_info,
                    );
                    
                    // Try to extract ID using pattern if available
                    if (!empty($service_data['id_pattern'])) {
                        $pattern = $service_data['id_pattern'];
                        if (preg_match($pattern, $content, $matches)) {
                            $tracker_info['id'] = $matches[0];
                        }
                    }
                    
                    $detected_trackers[] = $tracker_info;
                    break; // No need to check other services for this meta tag
                }
            }
        }
    }
    
    // Scan <link> tags
    $link_tags = $dom->getElementsByTagName('link');
    foreach ($link_tags as $link) {
        $href = $link->getAttribute('href');
        if (!empty($href)) {
            scan_url_for_trackers($href, $tracking_services, $detected_trackers);
        }
    }
    
    // Scan <iframe> tags
    $iframe_tags = $dom->getElementsByTagName('iframe');
    foreach ($iframe_tags as $iframe) {
        $src = $iframe->getAttribute('src');
        if (!empty($src)) {
            scan_url_for_trackers($src, $tracking_services, $detected_trackers);
        }
    }
    
    // Scan <img> tags (for tracking pixels)
    $img_tags = $dom->getElementsByTagName('img');
    foreach ($img_tags as $img) {
        $src = $img->getAttribute('src');
        if (!empty($src)) {
            // Only check small images that might be tracking pixels
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');
            
            // If width/height are small or not specified, check for tracking
            if ((empty($width) || intval($width) <= 3) && 
                (empty($height) || intval($height) <= 3)) {
                scan_url_for_trackers($src, $tracking_services, $detected_trackers);
            }
        }
    }
    
    return $detected_trackers;
}

/**
 * Helper function to scan a URL for tracking service indicators
 *
 * @since 1.0.0
 * @param string $url URL to scan
 * @param array $tracking_services Array of tracking services to look for
 * @param array &$detected_trackers Reference to array where detected trackers are stored
 * @return void
 */
function scan_url_for_trackers($url, $tracking_services, &$detected_trackers) {
    // Define domains to check
    $domain_mapping = array(
        'google-analytics.com' => 'google_analytics',
        'analytics.google.com' => 'google_analytics',
        'googletagmanager.com' => 'google_analytics',
        'gtag' => 'google_analytics',
        'connect.facebook.net' => 'facebook_pixel',
        'facebook.com/tr' => 'facebook_pixel',
        'static.hotjar.com' => 'hotjar',
        'script.hotjar.com' => 'hotjar',
        'snap.licdn.com' => 'linkedin_insight',
        'platform.linkedin.com' => 'linkedin_insight',
        'analytics.twitter.com' => 'twitter_pixel',
        'static.ads-twitter.com' => 'twitter_pixel',
        'ct.pinterest.com' => 'pinterest_tag',
        'analytics.tiktok.com' => 'tiktok_pixel',
        'sc-static.net' => 'snapchat_pixel',
        'tr.snapchat.com' => 'snapchat_pixel',
        'js.hs-scripts.com' => 'hubspot',
        'js.hsforms.net' => 'hubspot',
        'matomo.php' => 'matomo',
        'piwik.php' => 'matomo',
        'widget.intercom.io' => 'intercom',
        'cdn.mxpnl.com' => 'mixpanel',
        'api.amplitude.com' => 'amplitude',
        'cdn.segment.com' => 'segment',
        'edge.fullstory.com' => 'fullstory',
        'script.crazyegg.com' => 'crazy_egg',
        'cs.luckyorange.net' => 'lucky_orange',
        'cdn.mouseflow.com' => 'mouseflow',
        'pi.pardot.com' => 'pardot',
        'clarity.ms' => 'microsoft_clarity',
    );
    
    foreach ($domain_mapping as $domain => $service_id) {
        if (stripos($url, $domain) !== false) {
            $tracker_info = array(
                'service' => $service_id,
                'detection_method' => 'external_html',
                'element_type' => 'url',
                'element_data' => $url,
            );
            
            // Try to extract ID using pattern if available
            if (isset($tracking_services[$service_id]['id_pattern'])) {
                $pattern = $tracking_services[$service_id]['id_pattern'];
                if (preg_match($pattern, $url, $matches)) {
                    $tracker_info['id'] = $matches[0];
                }
            }
            
            $detected_trackers[] = $tracker_info;
            break;
        }
    }
}

/**
 * Helper function to scan content for tracking service indicators
 *
 * @since 1.0.0
 * @param string $content Content to scan
 * @param array $tracking_services Array of tracking services to look for
 * @param array &$detected_trackers Reference to array where detected trackers are stored
 * @param string $element_type Type of element being scanned (e.g., 'script', 'meta')
 * @return void
 */
function scan_content_for_trackers($content, $tracking_services, &$detected_trackers, $element_type) {
    // Common tracking initialization patterns
    $init_patterns = array(
        'google_analytics' => array(
            '/UA-\d{4,10}-\d{1,4}/',
            '/G-[A-Z0-9]{10,12}/',
            '/gtag\s*\(\s*[\'"]config[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '/ga\s*\(\s*[\'"]create[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,/',
        ),
        'facebook_pixel' => array(
            '/fbq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"](\d{15,16})[\'"]\s*\)/',
            '/FB\.init\s*\(\s*{\s*appId\s*:\s*[\'"](\d{15,16})[\'"]\s*,/',
        ),
        'hotjar' => array(
            '/hjid\s*:\s*(\d{7,9})/',
            '/hotjar\.com.*site=(\d{7,9})/',
        ),
        'linkedin_insight' => array(
            '/_linkedin_data_partner_id\s*=\s*[\'"]?(\d{6,8})[\'"]?/',
        ),
        'twitter_pixel' => array(
            '/twq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"]([a-z0-9]{5,10})[\'"]\s*\)/',
        ),
        'pinterest_tag' => array(
            '/pintrk\s*\(\s*[\'"]load[\'"]\s*,\s*[\'"](\d{13})[\'"]\s*\)/',
        ),
        'tiktok_pixel' => array(
            '/ttq\.load\s*\(\s*[\'"]([A-Z0-9]{20})[\'"]\s*\)/',
        ),
        'snapchat_pixel' => array(
            '/snaptr\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"]([a-z0-9-]{36})[\'"]\s*,/',
        ),
        'hubspot' => array(
            '/hs-script-loader.*api\.hubspot\.com.*\/(\d{7,8})\.js/',
        ),
        'matomo' => array(
            '/_paq\.push\s*\(\s*\[\s*[\'"]setSiteId[\'"]\s*,\s*[\'"]?(\d{1,4})[\'"]?\s*\]\s*\)/',
        ),
        'intercom' => array(
            '/Intercom\s*\(\s*[\'"]boot[\'"]\s*,\s*{\s*app_id\s*:\s*[\'"]([a-z0-9]{8})[\'"]\s*/',
            '/window\.intercomSettings\s*=\s*{\s*app_id\s*:\s*[\'"]([a-z0-9]{8})[\'"]\s*/',
        ),
        'mixpanel' => array(
            '/mixpanel\.init\s*\(\s*[\'"]([a-f0-9]{32})[\'"]\s*,/',
        ),
        'amplitude' => array(
            '/amplitude\.init\s*\(\s*[\'"]([a-f0-9]{32})[\'"]\s*,/',
        ),
        'segment' => array(
            '/analytics\.load\s*\(\s*[\'"]([a-zA-Z0-9]{22,32})[\'"]\s*\)/',
        ),
        'fullstory' => array(
            '/window\[[\'"]_fs_org[\'"]\]\s*=\s*[\'"]([A-Z0-9]{7})[\'"]\s*;/',
        ),
        'crazy_egg' => array(
            '/script\.src\s*=\s*[\'"]https:\/\/script\.crazyegg\.com\/pages\/scripts\/(\d{8})\.js[\'"]\s*;/',
        ),
        'lucky_orange' => array(
            '/window\.__lo_site_id\s*=\s*(\d{5,6})/',
        ),
        'mouseflow' => array(
            '/window\._mfq\s*=\s*window\._mfq\s*\|\|\s*\[\];\s*_mfq\.push\s*\(\s*\[\s*[\'"]setAccount[\'"]\s*,\s*[\'"]([a-f0-9]{32})[\'"]\s*\]\s*\)/',
        ),
        'pardot' => array(
            '/piAId\s*=\s*[\'"]?(\d{10,15})[\'"]?/',
        ),
        'microsoft_clarity' => array(
            '/clarity\s*\(\s*[\'"]set[\'"]\s*,\s*[\'"]([a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})[\'"]\s*\)/',
        ),
    );
    
    // Check for tracking initialization patterns
    foreach ($init_patterns as $service_id => $patterns) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches) && isset($matches[1])) {
                $tracker_info = array(
                    'service' => $service_id,
                    'detection_method' => 'external_html',
                    'element_type' => $element_type,
                    'id' => $matches[1],
                );
                
                // Add a snippet of the matching content for context
                $start_pos = max(0, strpos($content, $matches[0]) - 20);
                $snippet_length = min(strlen($content) - $start_pos, strlen($matches[0]) + 40);
                $snippet = substr($content, $start_pos, $snippet_length);
                $tracker_info['element_data'] = '...' . $snippet . '...';
                
                $detected_trackers[] = $tracker_info;
            }
        }
    }
    
    // Also check for keywords in content
    foreach ($tracking_services as $service_id => $service_data) {
        // Skip if no keywords defined or already detected by pattern
        if (empty($service_data['keywords'])) {
            continue;
        }
        
        // Skip if this service was already detected in this content
        $already_detected = false;
        foreach ($detected_trackers as $tracker) {
            if ($tracker['service'] === $service_id && 
                $tracker['detection_method'] === 'external_html' && 
                $tracker['element_type'] === $element_type) {
                $already_detected = true;
                break;
            }
        }
        
        if ($already_detected) {
            continue;
        }
        
        // Check for keywords
        $found_keyword = false;
        foreach ($service_data['keywords'] as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $found_keyword = true;
                
                // Get context around the keyword
                $start_pos = max(0, stripos($content, $keyword) - 20);
                $snippet_length = min(strlen($content) - $start_pos, strlen($keyword) + 40);
                $snippet = substr($content, $start_pos, $snippet_length);
                
                $tracker_info = array(
                    'service' => $service_id,
                    'detection_method' => 'external_html',
                    'element_type' => $element_type,
                    'element_data' => '...' . $snippet . '...',
                );
                
                // Try to extract ID using pattern if available
                if (!empty($service_data['id_pattern'])) {
                    $pattern = $service_data['id_pattern'];
                    if (preg_match($pattern, $content, $matches)) {
                        $tracker_info['id'] = $matches[0];
                    }
                }
                
                $detected_trackers[] = $tracker_info;
                break; // No need to check other keywords for this service
            }
        }
    }
}

/**
 * Detect tracking tools on the site
 * 
 * This function scans the site for tracking tools using multiple methods:
 * - File scanning
 * - HTTP header scanning
 * - HTML content scanning
 * 
 * @since 1.0.0
 * @return array Array of detected tracking tools
 */
function notrack_detect_tracking_tools() {
    // Create a NoTrack instance
    $notrack = new NoTrack();
    
    // Scan for trackers
    $detected_trackers = $notrack->scan_for_trackers();
    
    // Update the last scan time
    update_option('notrack_last_scan_time', time());
    
    // Save detected trackers to options
    update_option('notrack_detected_tools', $detected_trackers);
    
    return $detected_trackers;
}

/**
 * Callback function for the scheduled scan event
 * 
 * @since 1.0.0
 */
function notrack_scheduled_scan_callback() {
    notrack_detect_tracking_tools();
}

// Register the scheduled event on plugin activation
register_activation_hook(__FILE__, 'notrack_schedule_tracking_scan');

/**
 * Schedule the weekly tracking scan on plugin activation
 * 
 * @since 1.0.0
 */
function notrack_schedule_tracking_scan() {
    // Make sure the event is not already scheduled
    if (!wp_next_scheduled('notrack_scan')) {
        // Schedule the event to run weekly
        wp_schedule_event(time(), 'weekly', 'notrack_scan');
    }
    
    // Run an initial scan on activation
    notrack_detect_tracking_tools();
}

// Hook the scheduled scan to our callback function
add_action('notrack_scan', 'notrack_scheduled_scan_callback');

// Clean up scheduled events on plugin deactivation
register_deactivation_hook(__FILE__, 'notrack_clear_scheduled_scan');

/**
 * Clear the scheduled scan event on plugin deactivation
 * 
 * @since 1.0.0
 */
function notrack_clear_scheduled_scan() {
    $timestamp = wp_next_scheduled('notrack_scan');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'notrack_scan');
    }
}

/**
 * Add admin menu pages for NoTrack
 * 
 * This function adds the main menu and submenu pages for the NoTrack plugin.
 * 
 * @since 1.0.0
 */
function notrack_admin_menu() {
    // Add main menu page
    add_menu_page(
        __('NoTrack Settings', 'notrack'),
        __('NoTrack', 'notrack'),
        'manage_options',
        'notrack-settings',
        'notrack_settings_page',
        'dashicons-privacy',
        80
    );
    
    // Add submenu pages
    add_submenu_page(
        'notrack-settings',
        __('NoTrack Settings', 'notrack'),
        __('Settings', 'notrack'),
        'manage_options',
        'notrack-settings',
        'notrack_settings_page'
    );
    
    add_submenu_page(
        'notrack-settings',
        __('Detected Tools', 'notrack'),
        __('Detected Tools', 'notrack'),
        'manage_options',
        'notrack-detected-tools',
        'notrack_detected_tools_page'
    );
    
    add_submenu_page(
        'notrack-settings',
        __('External Scanner', 'notrack'),
        __('External Scanner', 'notrack'),
        'manage_options',
        'notrack-external-scanner',
        'notrack_external_scanner_page'
    );
    
    add_submenu_page(
        'notrack-settings',
        __('Help & Documentation', 'notrack'),
        __('Help & Documentation', 'notrack'),
        'manage_options',
        'notrack-help',
        'notrack_help_page'
    );
}

// Hook the admin menu function
add_action('admin_menu', 'notrack_admin_menu');

/**
 * Display the main settings page
 * 
 * @since 1.0.0
 */
function notrack_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get enabled trackers from options
    $options = get_option('notrack_options', array());
    $enabled_trackers = isset($options['trackers']) ? $options['trackers'] : array();
    
    // Get supported trackers data
    $supported_trackers = notrack_get_supported_trackers();
    
    // Process form submission
    if (isset($_POST['submit']) && check_admin_referer('notrack_settings', 'notrack_nonce')) {
        $updated_trackers = array();
        
        // Process each tracker
        foreach ($supported_trackers as $service => $tracker_data) {
            $enabled = isset($_POST['trackers'][$service]['enabled']) ? true : false;
            $id = isset($_POST['trackers'][$service]['id']) ? sanitize_text_field($_POST['trackers'][$service]['id']) : '';
            
            $updated_trackers[$service] = array(
                'enabled' => $enabled,
                'id' => $id,
                'opt_out_type' => $tracker_data['opt_out_type']
            );
        }
        
        // Update options
        $options['trackers'] = $updated_trackers;
        update_option('notrack_options', $options);
        
        // Show success message
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'notrack') . '</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('NoTrack Settings', 'notrack'); ?></h1>
        
        <p><?php echo esc_html__('Configure which tracking services users can opt out of on your site.', 'notrack'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('notrack_settings', 'notrack_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <?php foreach ($supported_trackers as $service => $tracker_data): ?>
                        <tr>
                            <th scope="row">
                                <label for="tracker-<?php echo esc_attr($service); ?>">
                                    <?php echo esc_html($tracker_data['label']); ?>
                                </label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php echo esc_html($tracker_data['label']); ?></span>
                                    </legend>
                                    <label>
                                        <input type="checkbox" 
                                               name="trackers[<?php echo esc_attr($service); ?>][enabled]" 
                                               id="tracker-<?php echo esc_attr($service); ?>" 
                                               value="1" 
                                               <?php checked(isset($enabled_trackers[$service]) && $enabled_trackers[$service]['enabled']); ?>>
                                        <?php echo esc_html__('Enable', 'notrack'); ?>
                                    </label>
                                    <p class="description"><?php echo esc_html($tracker_data['description']); ?></p>
                                    
                                    <div class="tracker-id-field" style="margin-top: 10px;">
                                        <label>
                                            <?php echo esc_html__('Tracking ID:', 'notrack'); ?>
                                            <input type="text" 
                                                   name="trackers[<?php echo esc_attr($service); ?>][id]" 
                                                   value="<?php echo esc_attr(isset($enabled_trackers[$service]) ? $enabled_trackers[$service]['id'] : ''); ?>" 
                                                   class="regular-text">
                                        </label>
                                        <p class="description">
                                            <?php echo esc_html__('Leave blank to use auto-detected ID.', 'notrack'); ?>
                                        </p>
                                    </div>
                                </fieldset>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Display the detected tools page
 * 
 * @since 1.0.0
 */
function notrack_detected_tools_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get detected trackers
    $detected_trackers = get_option('notrack_detected_tools', array());
    
    // Get the last scan time
    $last_scan_time = get_option('notrack_last_scan_time', 0);
    $last_scan_date = $last_scan_time ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_scan_time) : __('Never', 'notrack');
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Detected Tracking Tools', 'notrack'); ?></h1>
        
        <div class="notice notice-info">
            <p>
                <strong><?php echo esc_html__('Last scan:', 'notrack'); ?></strong> 
                <?php echo esc_html($last_scan_date); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=notrack-external-scanner')); ?>" class="button button-small">
                    <?php echo esc_html__('Run New Scan', 'notrack'); ?>
                </a>
            </p>
        </div>
        
        <?php if (empty($detected_trackers)): ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html__('No tracking tools have been detected yet. Run a scan to detect tracking tools on your site.', 'notrack'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Service', 'notrack'); ?></th>
                        <th><?php echo esc_html__('ID', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Detection Method', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Location', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Actions', 'notrack'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detected_trackers as $tracker): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($tracker['service']); ?></strong>
                            </td>
                            <td>
                                <?php echo !empty($tracker['id']) ? esc_html($tracker['id']) : '<em>' . esc_html__('Not detected', 'notrack') . '</em>'; ?>
                            </td>
                            <td class="notrack-method-<?php echo esc_attr($tracker['detection_method']); ?>">
                                <?php 
                                $method = $tracker['detection_method'];
                                if ($method === 'file') {
                                    echo esc_html__('File Scan', 'notrack');
                                } elseif ($method === 'header') {
                                    echo esc_html__('HTTP Header', 'notrack');
                                } elseif ($method === 'external_html') {
                                    echo esc_html__('HTML Content', 'notrack');
                                } else {
                                    echo esc_html($method);
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($tracker['detection_method'] === 'file') {
                                    echo esc_html($tracker['file']);
                                } elseif ($tracker['detection_method'] === 'header') {
                                    echo esc_html($tracker['header_name'] . ': ' . $tracker['header_value']);
                                } elseif ($tracker['detection_method'] === 'external_html') {
                                    // Create a variable to hold element info
                                    $element_info = '';
                                    if (!empty($tracker['element_type'])) {
                                        $element_info = '<' . esc_html($tracker['element_type']) . '> ';
                                    }
                                    $element_info .= esc_html($tracker['element_data']);
                                    echo $element_info;
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=notrack-settings')); ?>" class="button button-small">
                                    <?php echo esc_html__('Configure', 'notrack'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <style>
                .notrack-method-file {
                    background-color: #e6f7ff;
                    color: #0073aa;
                }
                .notrack-method-header {
                    background-color: #f0f7e6;
                    color: #5e8000;
                }
                .notrack-method-external_html {
                    background-color: #f7e6f7;
                    color: #800080;
                }
            </style>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display the external scanner page
 * 
 * @since 1.0.0
 */
function notrack_external_scanner_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if we need to run a manual scan
    if (isset($_GET['action']) && $_GET['action'] === 'scan') {
        check_admin_referer('notrack_manual_scan');
        notrack_detect_tracking_tools();
        wp_redirect(admin_url('admin.php?page=notrack-external-scanner&scan=complete'));
        exit;
    }
    
    // Get the last scan time
    $last_scan_time = get_option('notrack_last_scan_time', 0);
    $last_scan_date = $last_scan_time ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_scan_time) : __('Never', 'notrack');
    
    // Get next scheduled scan
    $next_scan = wp_next_scheduled('notrack_scan');
    $next_scan_date = $next_scan ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scan) : __('Not scheduled', 'notrack');
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('External Scanner', 'notrack'); ?></h1>
        
        <?php if (isset($_GET['scan']) && $_GET['scan'] === 'complete'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Scan completed successfully!', 'notrack'); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><?php echo esc_html__('Scan Your Site for Tracking Tools', 'notrack'); ?></h2>
            <p><?php echo esc_html__('The scanner will check your site for tracking tools using three methods:', 'notrack'); ?></p>
            <ul>
                <li><?php echo esc_html__('File Scanning: Examines theme and plugin files for tracking code', 'notrack'); ?></li>
                <li><?php echo esc_html__('Header Scanning: Checks HTTP response headers for tracking indicators', 'notrack'); ?></li>
                <li><?php echo esc_html__('HTML Scanning: Analyzes your site\'s HTML for tracking scripts and pixels', 'notrack'); ?></li>
            </ul>
            
            <div class="notrack-scan-info">
                <p>
                    <strong><?php echo esc_html__('Last scan:', 'notrack'); ?></strong> 
                    <?php echo esc_html($last_scan_date); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__('Next scheduled scan:', 'notrack'); ?></strong> 
                    <?php echo esc_html($next_scan_date); ?>
                </p>
            </div>
            
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=notrack-external-scanner&action=scan'), 'notrack_manual_scan')); ?>" class="button button-primary">
                    <?php echo esc_html__('Run Manual Scan', 'notrack'); ?>
                </a>
                <button id="notrack-ajax-scan" class="button">
                    <?php echo esc_html__('Run AJAX Scan', 'notrack'); ?>
                </button>
            </p>
            
            <div id="notrack-scan-results" style="display: none;">
                <div class="notice notice-info">
                    <p id="notrack-scan-status"><?php echo esc_html__('Scanning...', 'notrack'); ?></p>
                    <div id="notrack-scan-progress">
                        <div class="notrack-progress-bar"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-top: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            }
            .notrack-scan-info {
                background: #f9f9f9;
                border: 1px solid #ccd0d4;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            #notrack-scan-progress {
                height: 20px;
                background-color: #f0f0f0;
                border-radius: 10px;
                margin-top: 10px;
                overflow: hidden;
            }
            .notrack-progress-bar {
                height: 100%;
                width: 0;
                background-color: #0073aa;
                transition: width 0.3s ease;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#notrack-ajax-scan').on('click', function() {
                // Show the results container
                $('#notrack-scan-results').show();
                
                // Reset progress bar
                $('.notrack-progress-bar').css('width', '0%');
                
                // Update status
                $('#notrack-scan-status').text('<?php echo esc_js(__('Starting scan...', 'notrack')); ?>');
                
                // Perform the AJAX scan
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'notrack_ajax_scan',
                        nonce: '<?php echo wp_create_nonce('notrack_ajax_scan'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update progress bar to 100%
                            $('.notrack-progress-bar').css('width', '100%');
                            
                            // Update status
                            $('#notrack-scan-status').text('<?php echo esc_js(__('Scan completed successfully!', 'notrack')); ?>');
                            
                            // Reload the page after a delay
                            setTimeout(function() {
                                window.location.href = '<?php echo esc_js(admin_url('admin.php?page=notrack-detected-tools')); ?>';
                            }, 1500);
                        } else {
                            // Show error
                            $('#notrack-scan-status').text('<?php echo esc_js(__('Error: ', 'notrack')); ?>' + response.data.message);
                        }
                    },
                    error: function() {
                        // Show error
                        $('#notrack-scan-status').text('<?php echo esc_js(__('Error: Could not complete the scan.', 'notrack')); ?>');
                    }
                });
                
                // Simulate progress updates
                var progress = 0;
                var progressInterval = setInterval(function() {
                    progress += 5;
                    if (progress > 90) {
                        clearInterval(progressInterval);
                    }
                    $('.notrack-progress-bar').css('width', progress + '%');
                    
                    // Update status messages based on progress
                    if (progress === 20) {
                        $('#notrack-scan-status').text('<?php echo esc_js(__('Scanning files...', 'notrack')); ?>');
                    } else if (progress === 40) {
                        $('#notrack-scan-status').text('<?php echo esc_js(__('Checking HTTP headers...', 'notrack')); ?>');
                    } else if (progress === 60) {
                        $('#notrack-scan-status').text('<?php echo esc_js(__('Analyzing HTML content...', 'notrack')); ?>');
                    } else if (progress === 80) {
                        $('#notrack-scan-status').text('<?php echo esc_js(__('Processing results...', 'notrack')); ?>');
                    }
                }, 500);
            });
        });
        </script>
    </div>
    <?php
}

/**
 * Display the help and documentation page
 * 
 * @since 1.0.0
 */
function notrack_help_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Help & Documentation', 'notrack'); ?></h1>
        
        <div class="card">
            <h2><?php echo esc_html__('About NoTrack', 'notrack'); ?></h2>
            <p>
                <?php echo esc_html__('NoTrack is a WordPress plugin that provides user tracking opt-out functionality. It allows your site visitors to opt out of various tracking mechanisms commonly used on WordPress sites, giving them control over their privacy.', 'notrack'); ?>
            </p>
        </div>
        
        <div class="card">
            <h2><?php echo esc_html__('How to Use NoTrack', 'notrack'); ?></h2>
            <ol>
                <li>
                    <strong><?php echo esc_html__('Scan Your Site', 'notrack'); ?></strong>
                    <p><?php echo esc_html__('Use the External Scanner to detect tracking tools on your site.', 'notrack'); ?></p>
                </li>
                <li>
                    <strong><?php echo esc_html__('Configure Tracking Services', 'notrack'); ?></strong>
                    <p><?php echo esc_html__('Enable the tracking services you want to allow users to opt out of in the Settings page.', 'notrack'); ?></p>
                </li>
                <li>
                    <strong><?php echo esc_html__('Add Opt-Out Controls to Your Site', 'notrack'); ?></strong>
                    <p><?php echo esc_html__('Use the shortcodes to add opt-out controls to your privacy policy or other pages.', 'notrack'); ?></p>
                </li>
            </ol>
        </div>
        
        <div class="card">
            <h2><?php echo esc_html__('Available Shortcodes', 'notrack'); ?></h2>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Shortcode', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Description', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Example', 'notrack'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[notrack_opt_out]</code></td>
                        <td><?php echo esc_html__('Displays a form with opt-out controls for all enabled tracking services.', 'notrack'); ?></td>
                        <td><code>[notrack_opt_out title="Tracking Preferences"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[notrack_opt_out_button]</code></td>
                        <td><?php echo esc_html__('Displays a simple button to opt out of all tracking.', 'notrack'); ?></td>
                        <td><code>[notrack_opt_out_button text="Opt Out of All Tracking"]</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2><?php echo esc_html__('Frequently Asked Questions', 'notrack'); ?></h2>
            <dl>
                <dt><?php echo esc_html__('How does NoTrack detect tracking tools?', 'notrack'); ?></dt>
                <dd>
                    <p><?php echo esc_html__('NoTrack uses three methods to detect tracking tools:', 'notrack'); ?></p>
                    <ul>
                        <li><?php echo esc_html__('File scanning: Examines theme and plugin files for tracking code', 'notrack'); ?></li>
                        <li><?php echo esc_html__('Header scanning: Checks HTTP response headers for tracking indicators', 'notrack'); ?></li>
                        <li><?php echo esc_html__('HTML scanning: Analyzes your site\'s HTML for tracking scripts and pixels', 'notrack'); ?></li>
                    </ul>
                </dd>
                
                <dt><?php echo esc_html__('How often does NoTrack scan for tracking tools?', 'notrack'); ?></dt>
                <dd>
                    <p><?php echo esc_html__('NoTrack automatically scans for tracking tools once a week. You can also run manual scans at any time from the External Scanner page.', 'notrack'); ?></p>
                </dd>
                
                <dt><?php echo esc_html__('How does the opt-out mechanism work?', 'notrack'); ?></dt>
                <dd>
                    <p><?php echo esc_html__('When a user opts out, NoTrack sets a cookie in their browser. Then, it uses two methods to prevent tracking:', 'notrack'); ?></p>
                    <ul>
                        <li><?php echo esc_html__('For script-based trackers: It injects JavaScript that prevents the tracking scripts from initializing', 'notrack'); ?></li>
                        <li><?php echo esc_html__('For cookie-based trackers: It sets specific opt-out cookies that the tracking services recognize', 'notrack'); ?></li>
                    </ul>
                </dd>
            </dl>
        </div>
        
        <div class="card">
            <h2><?php echo esc_html__('Support', 'notrack'); ?></h2>
            <p>
                <?php echo esc_html__('For support, please visit the GitHub repository:', 'notrack'); ?>
                <a href="https://github.com/PhoenixOnlineMedia-AI/NoTrack" target="_blank">https://github.com/PhoenixOnlineMedia-AI/NoTrack</a>
            </p>
        </div>
        
        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-top: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            }
            dl dt {
                font-weight: bold;
                margin-top: 15px;
            }
            dl dd {
                margin-left: 0;
                margin-bottom: 15px;
            }
        </style>
    </div>
    <?php
}

/**
 * Handle AJAX scan request
 * 
 * @since 1.0.0
 */
function notrack_ajax_scan_callback() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'notrack_ajax_scan')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'notrack')));
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'notrack')));
    }
    
    // Run the scan
    $detected_trackers = notrack_detect_tracking_tools();
    
    // Send success response
    wp_send_json_success(array(
        'message' => __('Scan completed successfully.', 'notrack'),
        'count' => count($detected_trackers)
    ));
}

// Hook the AJAX handler
add_action('wp_ajax_notrack_ajax_scan', 'notrack_ajax_scan_callback');

/**
 * AJAX handler for manual site scans
 * 
 * This function handles AJAX requests for manual scans triggered from the admin panel.
 * It performs security checks, runs the scan, and returns the results.
 * 
 * @since 1.0.0
 * @return void
 */
function notrack_scan_site_callback() {
    // Verify nonce
    check_ajax_referer('notrack_scan_nonce', 'nonce');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => esc_js(__('You do not have permission to perform this action.', 'notrack'))
        ));
    }
    
    // Run the scan
    $detected_trackers = notrack_detect_tracking_tools();
    
    // Prepare the response data
    $response_data = array(
        'message' => esc_js(__('Scan completed successfully.', 'notrack')),
        'count' => count($detected_trackers),
        'last_scan' => human_time_diff(time(), time()),
        'next_scan' => wp_next_scheduled('notrack_scheduled_scan') ? 
            human_time_diff(time(), wp_next_scheduled('notrack_scheduled_scan')) : 
            __('Not scheduled', 'notrack')
    );
    
    // Add detected trackers to the response
    if (!empty($detected_trackers)) {
        $response_data['trackers'] = array();
        
        foreach ($detected_trackers as $tracker) {
            $response_data['trackers'][] = array(
                'service' => esc_js($tracker['service']),
                'id' => isset($tracker['id']) ? esc_js($tracker['id']) : '',
                'method' => esc_js($tracker['method']),
                'location' => isset($tracker['location']) ? esc_js($tracker['location']) : ''
            );
        }
    }
    
    // Send success response
    wp_send_json_success($response_data);
}

// Hook the manual scan AJAX handler
add_action('wp_ajax_notrack_scan_site', 'notrack_scan_site_callback');

/**
 * Register NoTrack REST API routes
 * 
 * This function registers REST API endpoints for the NoTrack plugin,
 * allowing external applications to trigger scans and retrieve detected tools.
 * 
 * @since 1.0.0
 */
function notrack_register_rest_routes() {
    // Register the REST API namespace
    register_rest_route('notrack/v1', '/scan', array(
        'methods' => 'POST',
        'callback' => 'notrack_rest_scan_callback',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
    // Register endpoint to get detected tools
    register_rest_route('notrack/v1', '/detected-tools', array(
        'methods' => 'GET',
        'callback' => 'notrack_rest_get_detected_tools_callback',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
    // Register endpoint to get scan status
    register_rest_route('notrack/v1', '/scan-status', array(
        'methods' => 'GET',
        'callback' => 'notrack_rest_get_scan_status_callback',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
}

// Hook the REST API registration
add_action('rest_api_init', 'notrack_register_rest_routes');

/**
 * REST API callback for triggering a scan
 * 
 * @since 1.0.0
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response The response object
 */
function notrack_rest_scan_callback($request) {
    // Run the scan
    $detected_trackers = notrack_detect_tracking_tools();
    
    // Prepare the response data
    $response_data = array(
        'success' => true,
        'message' => __('Scan completed successfully.', 'notrack'),
        'count' => count($detected_trackers),
        'last_scan' => human_time_diff(time(), time()),
        'next_scan' => wp_next_scheduled('notrack_scheduled_scan') ? 
            human_time_diff(time(), wp_next_scheduled('notrack_scheduled_scan')) : 
            __('Not scheduled', 'notrack')
    );
    
    // Return the response
    return new WP_REST_Response($response_data, 200);
}

/**
 * REST API callback for retrieving detected tools
 * 
 * @since 1.0.0
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response The response object
 */
function notrack_rest_get_detected_tools_callback($request) {
    // Get detected trackers from options
    $detected_trackers = get_option('notrack_detected_tools', array());
    
    // Sanitize the output
    $sanitized_trackers = array();
    foreach ($detected_trackers as $tracker) {
        $sanitized_trackers[] = array(
            'service' => sanitize_text_field($tracker['service']),
            'id' => isset($tracker['id']) ? sanitize_text_field($tracker['id']) : '',
            'method' => sanitize_text_field($tracker['method']),
            'location' => isset($tracker['location']) ? sanitize_text_field($tracker['location']) : ''
        );
    }
    
    // Prepare the response data
    $response_data = array(
        'success' => true,
        'count' => count($sanitized_trackers),
        'trackers' => $sanitized_trackers
    );
    
    // Return the response
    return new WP_REST_Response($response_data, 200);
}

/**
 * REST API callback for retrieving scan status
 * 
 * @since 1.0.0
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response The response object
 */
function notrack_rest_get_scan_status_callback($request) {
    // Get the last scan time
    $last_scan_time = get_option('notrack_last_scan_time', 0);
    $last_scan_date = $last_scan_time ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_scan_time) : __('Never', 'notrack');
    $last_scan_human = $last_scan_time ? human_time_diff($last_scan_time, time()) . ' ' . __('ago', 'notrack') : __('Never', 'notrack');
    
    // Get next scheduled scan
    $next_scan = wp_next_scheduled('notrack_scheduled_scan');
    $next_scan_date = $next_scan ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scan) : __('Not scheduled', 'notrack');
    $next_scan_human = $next_scan ? human_time_diff(time(), $next_scan) : __('Not scheduled', 'notrack');
    
    // Get detected trackers count
    $detected_trackers = get_option('notrack_detected_tools', array());
    
    // Prepare the response data
    $response_data = array(
        'success' => true,
        'last_scan_timestamp' => $last_scan_time,
        'last_scan_date' => $last_scan_date,
        'last_scan_human' => $last_scan_human,
        'next_scan_timestamp' => $next_scan,
        'next_scan_date' => $next_scan_date,
        'next_scan_human' => $next_scan_human,
        'detected_trackers_count' => count($detected_trackers)
    );
    
    // Return the response
    return new WP_REST_Response($response_data, 200);
}