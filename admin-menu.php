<?php
/**
 * NoTrack Admin Menu Functions
 *
 * This file contains the admin menu functions for the NoTrack plugin.
 *
 * @package NoTrack
 * @since 1.0.0
 */

// Security Check: Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
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
    
    // Get custom triggers
    $custom_triggers = get_option('notrack_custom_triggers', '');
    
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
        
        // Update custom triggers
        $custom_triggers = isset($_POST['notrack_custom_triggers']) ? sanitize_text_field($_POST['notrack_custom_triggers']) : '';
        update_option('notrack_custom_triggers', $custom_triggers);
        
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
                    
                    <!-- Custom Trigger Selectors Field -->
                    <tr>
                        <th scope="row">
                            <label for="notrack-custom-triggers">
                                <?php echo esc_html__('Custom Trigger Selectors', 'notrack'); ?>
                            </label>
                        </th>
                        <td>
                            <textarea name="notrack_custom_triggers" id="notrack-custom-triggers" class="large-text code" rows="5"><?php echo esc_textarea($custom_triggers); ?></textarea>
                            <p class="description">
                                <?php echo esc_html__('Enter CSS selectors for elements that should trigger the opt-out when clicked. Separate multiple selectors with commas. Example: .privacy-button, #opt-out-link', 'notrack'); ?>
                            </p>
                        </td>
                    </tr>
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
    $next_scan = wp_next_scheduled('notrack_scheduled_scan');
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
                    <span id="notrack-last-scan-time"><?php echo esc_html($last_scan_date); ?></span>
                </p>
                <p>
                    <strong><?php echo esc_html__('Next scheduled scan:', 'notrack'); ?></strong> 
                    <span id="notrack-next-scan-time"><?php echo esc_html($next_scan_date); ?></span>
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
                
                <div id="notrack-scan-results-table" style="display: none; margin-top: 20px;">
                    <h3><?php echo esc_html__('Detected Tracking Tools', 'notrack'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Service', 'notrack'); ?></th>
                                <th><?php echo esc_html__('ID', 'notrack'); ?></th>
                                <th><?php echo esc_html__('Detection Method', 'notrack'); ?></th>
                                <th><?php echo esc_html__('Location', 'notrack'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="notrack-detected-trackers">
                            <!-- Results will be populated here via JavaScript -->
                        </tbody>
                    </table>
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
                $('#notrack-scan-results-table').hide();
                
                // Reset progress bar
                $('.notrack-progress-bar').css('width', '0%');
                
                // Update status
                $('#notrack-scan-status').text('<?php echo esc_js(__('Starting scan...', 'notrack')); ?>');
                
                // Perform the AJAX scan
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'notrack_scan_site',
                        nonce: '<?php echo wp_create_nonce('notrack_scan_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update progress bar to 100%
                            $('.notrack-progress-bar').css('width', '100%');
                            
                            // Update status
                            $('#notrack-scan-status').text('<?php echo esc_js(__('Scan completed successfully!', 'notrack')); ?>');
                            
                            // Update scan times
                            $('#notrack-last-scan-time').text('<?php echo esc_js(__('Just now', 'notrack')); ?>');
                            if (response.data.next_scan) {
                                $('#notrack-next-scan-time').text(response.data.next_scan);
                            }
                            
                            // Display results if trackers were found
                            if (response.data.count > 0 && response.data.trackers) {
                                // Clear previous results
                                $('#notrack-detected-trackers').empty();
                                
                                // Add each tracker to the table
                                $.each(response.data.trackers, function(index, tracker) {
                                    var row = $('<tr></tr>');
                                    row.append($('<td></td>').text(tracker.service));
                                    row.append($('<td></td>').text(tracker.id || '—'));
                                    row.append($('<td></td>').text(tracker.method));
                                    row.append($('<td></td>').text(tracker.location || '—'));
                                    $('#notrack-detected-trackers').append(row);
                                });
                                
                                // Show the results table
                                $('#notrack-scan-results-table').show();
                            } else {
                                // No trackers found
                                $('#notrack-scan-status').text('<?php echo esc_js(__('Scan completed. No tracking tools were detected.', 'notrack')); ?>');
                            }
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
            <h2><?php echo esc_html__('REST API Endpoints', 'notrack'); ?></h2>
            <p>
                <?php echo esc_html__('NoTrack provides REST API endpoints for external applications to interact with the plugin. These endpoints require administrator privileges.', 'notrack'); ?>
            </p>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Endpoint', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Method', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Description', 'notrack'); ?></th>
                        <th><?php echo esc_html__('Example', 'notrack'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>/wp-json/notrack/v1/scan</code></td>
                        <td><code>POST</code></td>
                        <td><?php echo esc_html__('Triggers a scan for tracking tools on the site.', 'notrack'); ?></td>
                        <td>
                            <pre>curl -X POST <?php echo esc_url(rest_url('notrack/v1/scan')); ?> \
--header "X-WP-Nonce: {nonce}" \
--cookie "wordpress_logged_in_{hash}={cookie}"</pre>
                        </td>
                    </tr>
                    <tr>
                        <td><code>/wp-json/notrack/v1/detected-tools</code></td>
                        <td><code>GET</code></td>
                        <td><?php echo esc_html__('Retrieves the list of detected tracking tools.', 'notrack'); ?></td>
                        <td>
                            <pre>curl <?php echo esc_url(rest_url('notrack/v1/detected-tools')); ?> \
--header "X-WP-Nonce: {nonce}" \
--cookie "wordpress_logged_in_{hash}={cookie}"</pre>
                        </td>
                    </tr>
                    <tr>
                        <td><code>/wp-json/notrack/v1/scan-status</code></td>
                        <td><code>GET</code></td>
                        <td><?php echo esc_html__('Retrieves information about the last scan and next scheduled scan.', 'notrack'); ?></td>
                        <td>
                            <pre>curl <?php echo esc_url(rest_url('notrack/v1/scan-status')); ?> \
--header "X-WP-Nonce: {nonce}" \
--cookie "wordpress_logged_in_{hash}={cookie}"</pre>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>
                <?php echo esc_html__('Note: Authentication is required for all endpoints. You need to include a valid WordPress nonce in the X-WP-Nonce header and be logged in as an administrator.', 'notrack'); ?>
            </p>
            <p>
                <?php echo esc_html__('To get a nonce, you can use the wp-json/wp/v2/nonce endpoint or include wp_rest_nonce() in your WordPress theme.', 'notrack'); ?>
            </p>
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