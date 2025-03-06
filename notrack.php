<?php
/**
 * NoTrack - WordPress User Tracking Opt-Out Plugin
 *
 * This plugin provides functionality for users to opt out of various tracking
 * mechanisms commonly used on WordPress sites. It gives visitors control over
 * their privacy by allowing them to disable analytics, cookies, and other
 * tracking technologies.
 *
 * SECURITY RECOMMENDATIONS:
 * - Always test thoroughly in a staging environment before deploying to production
 * - Regularly update the plugin to ensure security patches are applied
 * - Consider implementing additional nonces for AJAX requests in future versions
 * - Monitor for any unusual activity in tracking opt-outs
 * - Ensure your WordPress installation is kept up-to-date
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
            'keywords' => array('analytics', 'ga', 'gtag', 'google tag manager', 'gtm'),
            'id_pattern' => '/UA-\d{4,10}-\d{1,4}|G-[A-Z0-9]{10,12}/',
        ),
        'microsoft_clarity' => array(
            'label' => __( 'Microsoft Clarity', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'project_id' => '', // Clarity project ID
            ),
            'description' => __( 'Disables Microsoft Clarity session recording and heatmap functionality.', 'notrack' ),
            'keywords' => array('clarity', 'microsoft', 'heatmap', 'session recording'),
            'id_pattern' => '/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/',
        ),
        'hotjar' => array(
            'label' => __( 'Hotjar', 'notrack' ),
            'opt_out_type' => 'cookie',
            'parameters' => array(
                'site_id' => '', // Hotjar site ID
            ),
            'description' => __( 'Sets the _hjOptOut cookie to prevent Hotjar from collecting data.', 'notrack' ),
            'keywords' => array('hotjar', 'heatmap', 'session recording', 'user feedback'),
            'id_pattern' => '/\d{7,9}/',
        ),
        'facebook_pixel' => array(
            'label' => __( 'Facebook Pixel', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'pixel_id' => '', // Facebook Pixel ID
            ),
            'description' => __( 'Prevents Facebook Pixel from tracking user activity and conversions.', 'notrack' ),
            'keywords' => array('facebook', 'fb', 'pixel', 'meta', 'conversion'),
            'id_pattern' => '/\d{15,16}/',
        ),
        'linkedin_insight' => array(
            'label' => __( 'LinkedIn Insight Tag', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'partner_id' => '', // LinkedIn Partner ID
            ),
            'description' => __( 'Disables LinkedIn Insight Tag tracking for conversion and retargeting.', 'notrack' ),
            'keywords' => array('linkedin', 'insight', 'conversion', 'professional', 'b2b'),
            'id_pattern' => '/\d{6,8}/',
        ),
        'twitter_pixel' => array(
            'label' => __( 'Twitter Pixel', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'pixel_id' => '', // Twitter Pixel ID
            ),
            'description' => __( 'Prevents Twitter Pixel from tracking website conversions.', 'notrack' ),
            'keywords' => array('twitter', 'x', 'pixel', 'conversion', 'social'),
            'id_pattern' => '/[a-z0-9]{5,10}/',
        ),
        'pinterest_tag' => array(
            'label' => __( 'Pinterest Tag', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'tag_id' => '', // Pinterest Tag ID
            ),
            'description' => __( 'Disables Pinterest conversion tracking and audience building.', 'notrack' ),
            'keywords' => array('pinterest', 'pin', 'tag', 'conversion', 'social'),
            'id_pattern' => '/\d{13}/',
        ),
        'tiktok_pixel' => array(
            'label' => __( 'TikTok Pixel', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'pixel_id' => '', // TikTok Pixel ID
            ),
            'description' => __( 'Prevents TikTok Pixel from tracking user interactions and conversions.', 'notrack' ),
            'keywords' => array('tiktok', 'pixel', 'conversion', 'social'),
            'id_pattern' => '/[A-Z0-9]{20}/',
        ),
        'snapchat_pixel' => array(
            'label' => __( 'Snapchat Pixel', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'pixel_id' => '', // Snapchat Pixel ID
            ),
            'description' => __( 'Disables Snapchat Pixel tracking for conversions and audience targeting.', 'notrack' ),
            'keywords' => array('snapchat', 'snap', 'pixel', 'conversion', 'social'),
            'id_pattern' => '/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/',
        ),
        'hubspot' => array(
            'label' => __( 'HubSpot', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'hub_id' => '', // HubSpot Hub ID
            ),
            'description' => __( 'Prevents HubSpot tracking code from collecting visitor data.', 'notrack' ),
            'keywords' => array('hubspot', 'crm', 'marketing', 'automation'),
            'id_pattern' => '/\d{7,8}/',
        ),
        'matomo' => array(
            'label' => __( 'Matomo (Piwik)', 'notrack' ),
            'opt_out_type' => 'cookie',
            'parameters' => array(
                'site_id' => '', // Matomo Site ID
                'matomo_url' => '', // Matomo installation URL
            ),
            'description' => __( 'Sets the matomo_ignore cookie to prevent Matomo from tracking user activity.', 'notrack' ),
            'keywords' => array('matomo', 'piwik', 'analytics', 'open source'),
            'id_pattern' => '/\d{1,4}/',
        ),
        'intercom' => array(
            'label' => __( 'Intercom', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'app_id' => '', // Intercom App ID
            ),
            'description' => __( 'Disables Intercom chat widget and user tracking functionality.', 'notrack' ),
            'keywords' => array('intercom', 'chat', 'support', 'messaging'),
            'id_pattern' => '/[a-z0-9]{8}/',
        ),
        'mixpanel' => array(
            'label' => __( 'Mixpanel', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'project_token' => '', // Mixpanel Project Token
            ),
            'description' => __( 'Prevents Mixpanel from tracking user events and interactions.', 'notrack' ),
            'keywords' => array('mixpanel', 'analytics', 'event', 'tracking'),
            'id_pattern' => '/[a-f0-9]{32}/',
        ),
        'amplitude' => array(
            'label' => __( 'Amplitude', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'api_key' => '', // Amplitude API Key
            ),
            'description' => __( 'Disables Amplitude analytics tracking for user behavior analysis.', 'notrack' ),
            'keywords' => array('amplitude', 'analytics', 'behavior', 'product'),
            'id_pattern' => '/[a-f0-9]{32}/',
        ),
        'segment' => array(
            'label' => __( 'Segment', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'write_key' => '', // Segment Write Key
            ),
            'description' => __( 'Prevents Segment from collecting and routing user data to connected services.', 'notrack' ),
            'keywords' => array('segment', 'analytics', 'customer data', 'integration'),
            'id_pattern' => '/[a-zA-Z0-9]{22,32}/',
        ),
        'fullstory' => array(
            'label' => __( 'FullStory', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'org_id' => '', // FullStory Org ID
            ),
            'description' => __( 'Disables FullStory session recording and user experience analytics.', 'notrack' ),
            'keywords' => array('fullstory', 'session', 'recording', 'ux', 'analytics'),
            'id_pattern' => '/[A-Z0-9]{7}/',
        ),
        'crazy_egg' => array(
            'label' => __( 'Crazy Egg', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'account_id' => '', // Crazy Egg Account ID
            ),
            'description' => __( 'Prevents Crazy Egg from creating heatmaps and tracking user clicks.', 'notrack' ),
            'keywords' => array('crazy egg', 'heatmap', 'click tracking', 'user behavior'),
            'id_pattern' => '/\d{8}/',
        ),
        'lucky_orange' => array(
            'label' => __( 'Lucky Orange', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'site_id' => '', // Lucky Orange Site ID
            ),
            'description' => __( 'Disables Lucky Orange heatmaps, recordings, and chat functionality.', 'notrack' ),
            'keywords' => array('lucky orange', 'heatmap', 'recording', 'chat'),
            'id_pattern' => '/\d{5,6}/',
        ),
        'mouseflow' => array(
            'label' => __( 'Mouseflow', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'website_id' => '', // Mouseflow Website ID
            ),
            'description' => __( 'Prevents Mouseflow from recording user sessions and creating heatmaps.', 'notrack' ),
            'keywords' => array('mouseflow', 'session', 'recording', 'heatmap'),
            'id_pattern' => '/[a-f0-9]{32}/',
        ),
        'pardot' => array(
            'label' => __( 'Pardot (Salesforce)', 'notrack' ),
            'opt_out_type' => 'script',
            'parameters' => array(
                'account_id' => '', // Pardot Account ID
                'campaign_id' => '', // Pardot Campaign ID
            ),
            'description' => __( 'Disables Pardot marketing automation tracking for lead generation.', 'notrack' ),
            'keywords' => array('pardot', 'salesforce', 'marketing', 'automation', 'b2b'),
            'id_pattern' => '/\d{10,15}/',
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
                            sanitize_text_field($tracker_config['parameters']['tracking_id']) : '';
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
                        // Get project ID if available
                        $project_id = isset($tracker_config['parameters']['project_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['project_id']) : '';
                        ?>
                        // Disable Microsoft Clarity
                        window['clarity'] = window['clarity'] || function() {};
                        window['clarity'].q = [];
                        window['clarity'].q.push(['disable', true]);
                        <?php
                        break;
                        
                    case 'facebook_pixel':
                        // Get pixel ID if available
                        $pixel_id = isset($tracker_config['parameters']['pixel_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['pixel_id']) : '';
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
                        // Get partner ID if available
                        $partner_id = isset($tracker_config['parameters']['partner_id']) ? 
                            sanitize_text_field($tracker_config['parameters']['partner_id']) : '';
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
    if ( is_array( $trackers ) ) {
        foreach ( $trackers as $tracker_id => $tracker_config ) {
            // Ensure tracker_id is sanitized
            $tracker_id = sanitize_key( $tracker_id );
            
            if ( ! empty( $tracker_config['enabled'] ) ) {
                $enabled_trackers[] = $tracker_id;
            }
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
 * Security measures:
 * - Nonce verification to prevent CSRF attacks
 * - Sanitization of all input data
 * - Validation of opt-out value
 *
 * @since 1.0.0
 * @return void
 */
function notrack_ajax_update_preferences() {
    // Check nonce for security
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'notrack-nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'notrack' ) ) );
    }
    
    // Process form data
    $form_data = array();
    if ( isset( $_POST['form_data'] ) ) {
        // Sanitize the form data string before parsing
        $sanitized_form_data = sanitize_text_field( $_POST['form_data'] );
        parse_str( $sanitized_form_data, $form_data );
    }
    
    // Set opt-out cookie
    $opt_out = isset( $form_data['opt_out'] ) && $form_data['opt_out'] === '1';
    
    // Set cookie expiration to 1 year
    $expiry = time() + ( 365 * 24 * 60 * 60 );
    
    // Set the cookie with secure parameters
    setcookie( 
        'notrack_opted_out', 
        $opt_out ? 'true' : 'false', 
        array(
            'expires' => $expiry,
            'path' => '/',
            'domain' => '',
            'secure' => is_ssl(),
            'httponly' => false, // Must be accessible by JavaScript
            'samesite' => 'Lax'
        )
    );
    
    // Send success response
    wp_send_json_success( array(
        'message' => $opt_out ? 
            __( 'You have successfully opted out of tracking.', 'notrack' ) : 
            __( 'You have opted back into tracking.', 'notrack' ),
        'opted_out' => $opt_out
    ) );
}

/**
 * Scan theme and plugin files for tracking services
 *
 * This function recursively scans theme and active plugin files for keywords and patterns
 * defined in the tracking services array. It helps identify trackers that might be present
 * in the WordPress installation without explicit configuration.
 *
 * The scan covers files with extensions .php, .js, .html, .twig, and .liquid, while
 * excluding common dependency directories like 'node_modules' and 'vendor'.
 *
 * @since 1.0.0
 * @return array Array of detected trackers with service name, ID (if found), and detection method
 */
function notrack_scan_files_for_trackers() {
    $detected_trackers = array();
    $allowed_extensions = array('php', 'js', 'html', 'twig', 'liquid');
    $excluded_dirs = array('node_modules', 'vendor');
    
    // Get tracking services data
    $tracking_services = notrack_get_supported_trackers();
    
    // Directories to scan
    $directories = array();
    
    // Add theme directories
    $directories[] = get_template_directory(); // Parent theme
    
    // Add child theme directory if different from parent theme
    if (get_stylesheet_directory() !== get_template_directory()) {
        $directories[] = get_stylesheet_directory();
    }
    
    // Add active plugins
    $active_plugins = wp_get_active_and_valid_plugins();
    foreach ($active_plugins as $plugin_path) {
        // Get plugin directory (not the main file)
        $plugin_dir = dirname($plugin_path);
        
        // Skip the NoTrack plugin itself to avoid false positives
        if (basename($plugin_dir) !== 'notrack') {
            $directories[] = $plugin_dir;
        }
    }
    
    // Scan each directory
    foreach ($directories as $directory) {
        notrack_scan_directory_for_trackers(
            $directory, 
            $tracking_services, 
            $detected_trackers, 
            $allowed_extensions, 
            $excluded_dirs
        );
    }
    
    return $detected_trackers;
}

/**
 * Recursively scan a directory for tracking services
 *
 * Helper function for notrack_scan_files_for_trackers() that recursively scans
 * a directory and its subdirectories for files that might contain tracking code.
 *
 * @since 1.0.0
 * @param string $directory Directory path to scan
 * @param array $tracking_services Array of tracking services to look for
 * @param array &$detected_trackers Reference to array where detected trackers are stored
 * @param array $allowed_extensions File extensions to scan
 * @param array $excluded_dirs Directories to exclude from scanning
 * @return void
 */
function notrack_scan_directory_for_trackers($directory, $tracking_services, &$detected_trackers, $allowed_extensions, $excluded_dirs) {
    // Skip if directory doesn't exist or isn't readable
    if (!is_dir($directory) || !is_readable($directory)) {
        return;
    }
    
    $dir_iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    
    foreach ($iterator as $file) {
        // Skip directories, especially excluded ones
        if ($file->isDir()) {
            $dir_name = $file->getBasename();
            if (in_array($dir_name, $excluded_dirs)) {
                // Skip this directory in the iteration
                $iterator->next();
                continue;
            }
            continue;
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            continue;
        }
        
        // Read file content
        $file_content = file_get_contents($file->getPathname());
        if ($file_content === false) {
            continue; // Skip if file can't be read
        }
        
        // Check for each tracking service
        foreach ($tracking_services as $service_id => $service_data) {
            // Skip if no keywords defined
            if (empty($service_data['keywords'])) {
                continue;
            }
            
            // Check for keywords
            $found_keyword = false;
            foreach ($service_data['keywords'] as $keyword) {
                if (stripos($file_content, $keyword) !== false) {
                    $found_keyword = true;
                    break;
                }
            }
            
            // If keyword found, try to extract ID using pattern
            if ($found_keyword) {
                $tracker_info = array(
                    'service' => $service_id,
                    'file' => str_replace(ABSPATH, '', $file->getPathname()),
                    'detection_method' => 'file_scan'
                );
                
                // Try to extract ID if pattern exists
                if (!empty($service_data['id_pattern'])) {
                    $pattern = $service_data['id_pattern'];
                    if (preg_match($pattern, $file_content, $matches)) {
                        $tracker_info['id'] = $matches[0];
                    }
                }
                
                // Add to detected trackers
                $detected_trackers[] = $tracker_info;
                
                // No need to check other keywords for this service
                break;
            }
        }
    }
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
     * Add plugin admin menu
     *
     * Adds the NoTrack settings page to the WordPress admin menu under Settings.
     * This creates a dedicated page where administrators can configure which tracking
     * services to enable opt-out functionality for.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'NoTrack Settings', 'notrack' ),
            __( 'NoTrack', 'notrack' ),
            'manage_options',
            'notrack',
            array( $this, 'settings_page' )
        );
        
        // Add submenu page for tracker scanner
        add_submenu_page(
            'options-general.php',
            __( 'NoTrack Scanner', 'notrack' ),
            __( 'NoTrack Scanner', 'notrack' ),
            'manage_options',
            'notrack-scanner',
            array( $this, 'scanner_page' )
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
        
        // Save settings if form was submitted
        if ( isset( $_POST['submit'] ) && check_admin_referer( 'notrack_settings', 'notrack_nonce' ) ) {
            update_option( 'notrack_trackers', $this->sanitize_trackers( $_POST['notrack_trackers'] ) );
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Settings saved successfully!', 'notrack' ); ?></p>
            </div>
            <?php
        }
        
        // Get current settings
        $trackers = get_option( 'notrack_trackers', array() );
        
        // Begin page output
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <p><?php _e( 'Configure which tracking services users can opt out of on your site.', 'notrack' ); ?></p>
            
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=notrack-scanner')); ?>" class="button">
                    <?php _e('Scan Site for Trackers', 'notrack'); ?>
                </a>
                <span class="description"><?php _e('Automatically detect tracking services in your theme and plugins.', 'notrack'); ?></span>
            </p>
            
            <form method="post" action="">
                <?php
                settings_fields( 'notrack_settings' );
                do_settings_sections( 'notrack' );
                wp_nonce_field( 'notrack_settings', 'notrack_nonce' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
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

    /**
     * Enqueue scripts
     *
     * Load frontend scripts and styles.
     * 
     * @since 1.0.0
     * @return void
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
        
        // Get enabled trackers from options for localization
        $trackers = get_option( 'notrack_trackers', array() );
        $enabled_trackers = array();
        
        // Build array of enabled tracker keys
        if ( is_array( $trackers ) ) {
            foreach ( $trackers as $tracker_id => $tracker_config ) {
                // Ensure tracker_id is sanitized
                $tracker_id = sanitize_key( $tracker_id );
                
                if ( ! empty( $tracker_config['enabled'] ) ) {
                    $enabled_trackers[] = $tracker_id;
                }
            }
        }
        
        // Localize script with plugin data
        wp_localize_script(
            'notrack-script',
            'notrack_data',
            array(
                'enabled_trackers' => $enabled_trackers,
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'notrack-nonce' ),
            )
        );
    }

    /**
     * Scan for trackers
     *
     * @since 1.0.0
     * @return array Array of detected trackers
     */
    public function scan_for_trackers() {
        // Check if we have cached results and no rescan was requested
        $rescan = isset($_GET['rescan']) && $_GET['rescan'] === '1';
        $cached_trackers = get_transient('notrack_detected_trackers');
        
        if (!$rescan && $cached_trackers !== false) {
            return $cached_trackers;
        }
        
        // Use the consolidated detection function
        $detected_trackers = notrack_detect_tracking_tools();
        
        // Cache the results for 1 hour
        set_transient('notrack_detected_trackers', $detected_trackers, HOUR_IN_SECONDS);
        
        return $detected_trackers;
    }
    
    /**
     * Enable detected trackers
     *
     * @since 1.0.0
     * @return int Number of trackers enabled
     */
    public function enable_detected_trackers() {
        // Get detected trackers from the option
        $detected_trackers = get_option('notrack_detected_tools', array());
        if (empty($detected_trackers)) {
            return 0;
        }
        
        // Get current settings
        $options = get_option('notrack_options', array());
        if (!isset($options['trackers'])) {
            $options['trackers'] = array();
        }
        
        // Count how many new trackers we enable
        $enabled_count = 0;
        
        // Loop through detected trackers
        foreach ($detected_trackers as $tracker) {
            $service = $tracker['service'];
            
            // Skip if already enabled
            if (isset($options['trackers'][$service]) && $options['trackers'][$service]['enabled']) {
                continue;
            }
            
            // Get tracking service data
            $tracking_services = notrack_get_supported_trackers();
            if (!isset($tracking_services[$service])) {
                continue;
            }
            
            // Enable this tracker
            if (!isset($options['trackers'][$service])) {
                $options['trackers'][$service] = array(
                    'enabled' => true,
                    'id' => !empty($tracker['id']) ? $tracker['id'] : '',
                    'opt_out_type' => $tracking_services[$service]['opt_out_type'],
                );
            } else {
                $options['trackers'][$service]['enabled'] = true;
                
                // Update ID if we found one and none was set
                if (!empty($tracker['id']) && empty($options['trackers'][$service]['id'])) {
                    $options['trackers'][$service]['id'] = $tracker['id'];
                }
            }
            
            $enabled_count++;
        }
        
        // Save updated options
        update_option('notrack_options', $options);
        
        return $enabled_count;
    }
    
    /**
     * Display the scanner page
     *
     * @since 1.0.0
     */
    public function scanner_page() {
        // Check if we need to run a manual scan
        if (isset($_GET['action']) && $_GET['action'] === 'scan') {
            check_admin_referer('notrack_manual_scan');
            notrack_detect_tracking_tools();
            wp_redirect(admin_url('admin.php?page=notrack-scanner&scan=complete'));
            exit;
        }

        // Get detected trackers
        $detected_trackers = $this->scan_for_trackers();
        
        // Get the last scan time
        $last_scan_time = get_option('notrack_last_scan_time', 0);
        $last_scan_date = $last_scan_time ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_scan_time) : __('Never', 'notrack');
        
        // Get next scheduled scan
        $next_scan = wp_next_scheduled('notrack_scan');
        $next_scan_date = $next_scan ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scan) : __('Not scheduled', 'notrack');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('NoTrack Scanner', 'notrack'); ?></h1>
            
            <?php if (isset($_GET['scan']) && $_GET['scan'] === 'complete'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Scan completed successfully!', 'notrack'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="notrack-scan-info">
                <p>
                    <strong><?php echo esc_html__('Last scan:', 'notrack'); ?></strong> 
                    <?php echo esc_html($last_scan_date); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__('Next scheduled scan:', 'notrack'); ?></strong> 
                    <?php echo esc_html($next_scan_date); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=notrack-scanner&action=scan'), 'notrack_manual_scan')); ?>" class="button button-primary">
                        <?php echo esc_html__('Run Manual Scan', 'notrack'); ?>
                    </a>
                </p>
            </div>
            
            <h2><?php echo esc_html__('Detected Tracking Services', 'notrack'); ?></h2>
            
            <?php if (empty($detected_trackers)): ?>
                <p><?php echo esc_html__('No tracking services detected.', 'notrack'); ?></p>
            <?php else: ?>
                <p><?php echo esc_html__('The following tracking services were detected on your site:', 'notrack'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Service', 'notrack'); ?></th>
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
                                    <?php if (!empty($tracker['id'])): ?>
                                        <br><small><?php echo esc_html__('ID:', 'notrack'); ?> <?php echo esc_html($tracker['id']); ?></small>
                                    <?php endif; ?>
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
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=notrack-settings&enable=' . urlencode($tracker['service']))); ?>" class="button button-small">
                                        <?php echo esc_html__('Enable Opt-Out', 'notrack'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=notrack-settings&enable=all')); ?>" class="button button-primary">
                        <?php echo esc_html__('Enable All Opt-Outs', 'notrack'); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <style>
                .notrack-scan-info {
                    background: #f9f9f9;
                    border: 1px solid #ccd0d4;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
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
        </div>
        <?php
    }
}

// Initialize the plugin
$notrack = new NoTrack();

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
 * Consolidates results from all scanning methods and saves to options
 * 
 * This function runs all three scanning methods (files, headers, and HTML body),
 * combines the results into a unique list of detected trackers, and saves
 * the results to the 'notrack_detected_tools' option.
 * 
 * @since 1.0.0
 * @return array Array of detected trackers
 */
function notrack_detect_tracking_tools() {
    // Get tracking services
    $tracking_services = notrack_get_supported_trackers();
    
    // Run all three scanning methods
    $file_trackers = notrack_scan_files_for_trackers();
    $header_trackers = notrack_scan_headers_for_trackers();
    $body_trackers = notrack_scan_body_for_trackers();
    
    // Merge results from all scanning methods
    $all_trackers = array_merge($file_trackers, $header_trackers, $body_trackers);
    
    // Create a unique list based on service name
    $unique_trackers = array();
    $tracker_names = array();
    
    foreach ($all_trackers as $tracker) {
        if (!in_array($tracker['service'], $tracker_names)) {
            $tracker_names[] = $tracker['service'];
            $unique_trackers[] = $tracker;
        }
    }
    
    // Save the results to the options table
    update_option('notrack_detected_tools', $unique_trackers, false);
    
    // Log the scan time
    update_option('notrack_last_scan_time', current_time('timestamp'), false);
    
    return $unique_trackers;
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

// Hook the notrack_enqueue_scripts function to wp_enqueue_scripts
add_action('wp_enqueue_scripts', 'notrack_enqueue_scripts');

// Hook the AJAX handler
add_action('wp_ajax_notrack_update_preferences', 'notrack_ajax_update_preferences');
add_action('wp_ajax_nopriv_notrack_update_preferences', 'notrack_ajax_update_preferences');