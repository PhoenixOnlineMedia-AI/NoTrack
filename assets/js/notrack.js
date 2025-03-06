/**
 * NoTrack - WordPress User Tracking Opt-Out Plugin
 * 
 * This file contains the frontend JavaScript functionality for the NoTrack plugin.
 * It handles the opt-out process and interacts with the enabled trackers.
 */

(function($) {
    'use strict';

    /**
     * Set a cookie with the specified name, value, and attributes
     * 
     * @param {string} name - The name of the cookie
     * @param {string} value - The value to store in the cookie
     * @param {number} maxAge - The maximum age of the cookie in seconds
     * @param {string} path - The path for which the cookie is valid
     */
    function setCookie(name, value, maxAge, path) {
        var cookieString = name + '=' + value + '; max-age=' + maxAge + '; path=' + path + '; SameSite=Lax';
        if (window.location.protocol === 'https:') {
            cookieString += '; Secure';
        }
        document.cookie = cookieString;
    }

    /**
     * Get a cookie value by name
     * 
     * @param {string} name - The name of the cookie to retrieve
     * @return {string|null} The cookie value or null if not found
     */
    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    /**
     * Opt out of tracking by setting the necessary cookies
     * 
     * This function sets the main opt-out cookie and additional cookies
     * for specific tracking services that use cookie-based opt-out mechanisms.
     * It processes the enabled trackers from the notrack_data object.
     */
    function notrack_opt_out() {
        // Set the main NoTrack opt-out cookie (1 year expiration)
        setCookie('notrack_opted_out', 'true', 31536000, '/');
        
        // Check if we have tracker data available
        if (typeof notrack_data !== 'undefined' && notrack_data.enabled_trackers) {
            // Process each enabled tracker
            notrack_data.enabled_trackers.forEach(function(trackerId) {
                // Set tracker-specific opt-out cookies
                switch (trackerId) {
                    case 'hotjar':
                        // Set Hotjar opt-out cookie
                        setCookie('_hjOptOut', '1', 31536000, '/');
                        break;
                        
                    case 'google_analytics':
                        // For Google Analytics, we use the window variable in wp_head
                        // but we can also set a cookie to remember the preference
                        setCookie('ga_opt_out', 'true', 31536000, '/');
                        break;
                        
                    case 'microsoft_clarity':
                        // For Microsoft Clarity, we use the window variable in wp_head
                        // but we can also set a cookie to remember the preference
                        setCookie('clarity_opt_out', 'true', 31536000, '/');
                        break;
                        
                    // Add more trackers as needed
                }
            });
        }
        
        // Notify the user
        alert('You have successfully opted out of tracking. The page will now reload to apply your preferences.');
        
        // Reload the page to ensure all tracking scripts are properly disabled
        window.location.reload();
    }

    // NoTrack main object
    var NoTrack = {
        /**
         * Initialize the NoTrack functionality
         */
        init: function() {
            // Set up event listeners
            this.setupEventListeners();
            
            // Check if user has already opted out
            this.checkOptOutStatus();
        },

        /**
         * Set up event listeners for opt-out buttons/forms
         */
        setupEventListeners: function() {
            // Listen for opt-out button clicks
            $('.notrack-opt-out-button').on('click', this.handleOptOut);
            
            // Listen for opt-out form submissions
            $('.notrack-opt-out-form form').on('submit', this.handleOptOutForm);
            
            // Listen for the specific opt-out button with ID 'notrack-opt-out'
            $('#notrack-opt-out').on('click', function(e) {
                e.preventDefault();
                
                // Check if the user is opting out or in
                var action = $(this).data('action');
                
                if (action === 'opt-out') {
                    // Call the opt-out function
                    notrack_opt_out();
                } else {
                    // Opt back in
                    setCookie('notrack_opted_out', 'false', 31536000, '/');
                    
                    // Reset tracker-specific cookies
                    if (typeof notrack_data !== 'undefined' && notrack_data.enabled_trackers) {
                        notrack_data.enabled_trackers.forEach(function(trackerId) {
                            switch (trackerId) {
                                case 'hotjar':
                                    // Remove Hotjar opt-out cookie
                                    document.cookie = '_hjOptOut=; max-age=0; path=/;';
                                    break;
                                    
                                case 'google_analytics':
                                    document.cookie = 'ga_opt_out=; max-age=0; path=/;';
                                    break;
                                    
                                case 'microsoft_clarity':
                                    document.cookie = 'clarity_opt_out=; max-age=0; path=/;';
                                    break;
                            }
                        });
                    }
                    
                    alert('You have opted back into tracking. The page will now reload to apply your preferences.');
                    window.location.reload();
                }
            });
        },

        /**
         * Check if the user has already opted out
         */
        checkOptOutStatus: function() {
            var optedOut = getCookie('notrack_opted_out');
            
            if (optedOut === 'true') {
                // Update UI to show opted-out status
                $('.notrack-status').addClass('opted-out').text('You have opted out of tracking.');
                $('.notrack-opt-out-button').text('Opt In').data('action', 'opt-in');
            } else {
                // Update UI to show opted-in status
                $('.notrack-status').removeClass('opted-out').text('Tracking is currently enabled.');
                $('.notrack-opt-out-button').text('Opt Out').data('action', 'opt-out');
            }
        },

        /**
         * Handle opt-out button click
         */
        handleOptOut: function(e) {
            e.preventDefault();
            
            var action = $(this).data('action');
            
            if (action === 'opt-out') {
                notrack_opt_out();
            } else {
                setCookie('notrack_opted_out', 'false', 31536000, '/');
                alert('You have opted back into tracking. Please refresh the page for the changes to take effect.');
                
                // Update UI
                NoTrack.checkOptOutStatus();
            }
        },

        /**
         * Handle opt-out form submission
         */
        handleOptOutForm: function(e) {
            e.preventDefault();
            
            // Get form data
            var formData = $(this).serialize();
            
            // Process form data via AJAX
            $.ajax({
                url: notrack_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'notrack_update_preferences',
                    nonce: notrack_data.nonce,
                    form_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        NoTrack.checkOptOutStatus();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        /**
         * Set the opt-out cookie
         */
        setOptOutCookie: function(optOut) {
            if (optOut) {
                notrack_opt_out();
            } else {
                setCookie('notrack_opted_out', 'false', 31536000, '/');
            }
        }
    };

    // Make the notrack_opt_out function globally available
    window.notrack_opt_out = notrack_opt_out;

    // Initialize NoTrack when the document is ready
    $(document).ready(function() {
        NoTrack.init();
    });

})(jQuery); 