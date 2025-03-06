/**
 * NoTrack - WordPress User Tracking Opt-Out Plugin
 * 
 * This file contains the frontend JavaScript functionality for the NoTrack plugin.
 * It handles the opt-out process and interacts with the enabled trackers.
 */

(function($) {
    'use strict';

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
        },

        /**
         * Check if the user has already opted out
         */
        checkOptOutStatus: function() {
            var optedOut = this.getCookie('notrack_opted_out');
            
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
                NoTrack.setOptOutCookie(true);
                alert('You have successfully opted out of tracking. Please refresh the page for the changes to take effect.');
            } else {
                NoTrack.setOptOutCookie(false);
                alert('You have opted back into tracking. Please refresh the page for the changes to take effect.');
            }
            
            // Update UI
            NoTrack.checkOptOutStatus();
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
            var value = optOut ? 'true' : 'false';
            var expiryDate = new Date();
            expiryDate.setFullYear(expiryDate.getFullYear() + 1); // Cookie expires in 1 year
            
            document.cookie = 'notrack_opted_out=' + value + '; expires=' + expiryDate.toUTCString() + '; path=/; SameSite=Lax';
        },

        /**
         * Get a cookie value by name
         */
        getCookie: function(name) {
            var value = '; ' + document.cookie;
            var parts = value.split('; ' + name + '=');
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
    };

    // Initialize NoTrack when the document is ready
    $(document).ready(function() {
        NoTrack.init();
    });

})(jQuery); 