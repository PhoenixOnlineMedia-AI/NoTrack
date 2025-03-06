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
     * Scan for trackers in theme and plugin files
     *
     * This method wraps the notrack_scan_files_for_trackers function and adds
     * additional processing or caching if needed.
     *
     * @since 1.0.0
     * @return array Array of detected trackers
     */
    public function scan_for_trackers() {
        // Check if we have cached results less than 1 hour old
        $cached_results = get_transient('notrack_scan_results');
        if (false !== $cached_results) {
            return $cached_results;
        }
        
        // Perform the scan
        $detected_trackers = notrack_scan_files_for_trackers();
        
        // Cache the results for 1 hour
        set_transient('notrack_scan_results', $detected_trackers, HOUR_IN_SECONDS);
        
        return $detected_trackers;
    }
    
    /**
     * Enable detected trackers
     *
     * This method enables opt-out for tracking services that were detected
     * by the scanner. It updates the plugin settings to include these trackers.
     *
     * @since 1.0.0
     * @return int Number of trackers enabled
     */
    public function enable_detected_trackers() {
        // Get current settings
        $current_trackers = get_option('notrack_trackers', array());
        
        // Get detected trackers
        $detected_trackers = $this->scan_for_trackers();
        
        // Count how many new trackers we enable
        $enabled_count = 0;
        
        // Process each detected tracker
        foreach ($detected_trackers as $tracker) {
            $service_id = $tracker['service'];
            
            // Skip if already enabled
            if (isset($current_trackers[$service_id]['enabled']) && $current_trackers[$service_id]['enabled']) {
                continue;
            }
            
            // Enable this tracker
            $current_trackers[$service_id]['enabled'] = true;
            
            // Set parameters if ID was detected
            if (!empty($tracker['id'])) {
                // Get tracking services data to determine parameter name
                $tracking_services = notrack_get_supported_trackers();
                
                if (isset($tracking_services[$service_id]['parameters'])) {
                    // Get the first parameter key (usually the ID field)
                    $param_keys = array_keys($tracking_services[$service_id]['parameters']);
                    if (!empty($param_keys)) {
                        $id_param = $param_keys[0];
                        
                        // Initialize parameters array if needed
                        if (!isset($current_trackers[$service_id]['parameters'])) {
                            $current_trackers[$service_id]['parameters'] = array();
                        }
                        
                        // Set the ID parameter
                        $current_trackers[$service_id]['parameters'][$id_param] = sanitize_text_field($tracker['id']);
                    }
                }
            }
            
            $enabled_count++;
        }
        
        // Save updated settings
        if ($enabled_count > 0) {
            update_option('notrack_trackers', $current_trackers);
        }
        
        return $enabled_count;
    }
    
    /**
     * Render the tracker scanner page
     *
     * Displays the results of scanning theme and plugin files for tracking services.
     * This helps administrators identify trackers that might be present in their
     * WordPress installation.
     *
     * @since 1.0.0
     * @return void
     */
    public function scanner_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Process form submission to clear cache and rescan
        $rescan = isset($_POST['notrack_rescan']) && wp_verify_nonce($_POST['notrack_scanner_nonce'], 'notrack_scanner');
        if ($rescan) {
            delete_transient('notrack_scan_results');
        }
        
        // Process form submission to enable detected trackers
        $enable_trackers = isset($_POST['notrack_enable_trackers']) && wp_verify_nonce($_POST['notrack_scanner_nonce'], 'notrack_scanner');
        $enabled_count = 0;
        if ($enable_trackers) {
            $enabled_count = $this->enable_detected_trackers();
        }
        
        // Get tracking services for reference
        $tracking_services = notrack_get_supported_trackers();
        
        // Get scan results
        $detected_trackers = $this->scan_for_trackers();
        
        // Group trackers by service
        $grouped_trackers = array();
        foreach ($detected_trackers as $tracker) {
            $service = $tracker['service'];
            if (!isset($grouped_trackers[$service])) {
                $grouped_trackers[$service] = array();
            }
            $grouped_trackers[$service][] = $tracker;
        }
        
        // Begin page output
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <p><?php _e('This page shows tracking services detected in your theme and active plugins. The scanner looks for keywords and patterns associated with common tracking services.', 'notrack'); ?></p>
            
            <?php if ($enabled_count > 0): ?>
                <div class="notice notice-success">
                    <p><?php printf(_n('%d tracking service has been enabled for opt-out.', '%d tracking services have been enabled for opt-out.', $enabled_count, 'notrack'), $enabled_count); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('notrack_scanner', 'notrack_scanner_nonce'); ?>
                <p>
                    <input type="submit" name="notrack_rescan" class="button button-primary" value="<?php esc_attr_e('Rescan Files', 'notrack'); ?>">
                    
                    <?php if (!empty($detected_trackers)): ?>
                        <input type="submit" name="notrack_enable_trackers" class="button" value="<?php esc_attr_e('Enable Detected Trackers', 'notrack'); ?>">
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=notrack')); ?>" class="button">
                        <?php _e('Back to Settings', 'notrack'); ?>
                    </a>
                </p>
            </form>
            
            <div class="notrack-scan-results">
                <?php if (empty($detected_trackers)): ?>
                    <div class="notice notice-success">
                        <p><?php _e('No tracking services were detected in your theme and active plugins.', 'notrack'); ?></p>
                    </div>
                <?php else: ?>
                    <h2><?php printf(_n('%d tracking service detected', '%d tracking services detected', count($grouped_trackers), 'notrack'), count($grouped_trackers)); ?></h2>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Tracking Service', 'notrack'); ?></th>
                                <th><?php _e('Files', 'notrack'); ?></th>
                                <th><?php _e('IDs Found', 'notrack'); ?></th>
                                <th><?php _e('Actions', 'notrack'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_trackers as $service => $trackers): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($tracking_services[$service]['label']); ?></strong>
                                        <p class="description"><?php echo esc_html($tracking_services[$service]['description']); ?></p>
                                    </td>
                                    <td>
                                        <ul>
                                            <?php 
                                            $files = array_unique(array_column($trackers, 'file'));
                                            foreach ($files as $file): 
                                            ?>
                                                <li><?php echo esc_html($file); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <?php 
                                        $ids = array();
                                        foreach ($trackers as $tracker) {
                                            if (!empty($tracker['id'])) {
                                                $ids[] = $tracker['id'];
                                            }
                                        }
                                        
                                        if (!empty($ids)): 
                                        ?>
                                            <ul>
                                                <?php foreach (array_unique($ids) as $id): ?>
                                                    <li><?php echo esc_html($id); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <em><?php _e('No IDs detected', 'notrack'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('options-general.php?page=notrack')); ?>" class="button">
                                            <?php _e('Configure Opt-Out', 'notrack'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
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