<?php
/**
 * WP Mail Logging Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class WPMailLoggingAgent {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'prxagnt_integration_requests', [ $this, 'integration_requests' ], 10, 2 );
        add_filter( 'prxagnt_integration_actions', [ $this, 'integration_actions' ] );
    } // End __construct()
    

    /**
     * Define the data points for WP Mail Logging
     *
     * @param array $definitions Existing definitions
     * @param mixed $plugin_not_active_response Response if plugin not active
     * @return array Modified definitions
     */
    public function integration_requests( $definitions, $plugin_not_active_response ) : array {
        $definitions[ 'wp_mail_logging_error_count' ] = [
            'label'       => __( 'Mail Errors Count', 'pluginrx-agent' ),
            'description' => __( 'Share the count of mail errors logged by WP Mail Logging plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_wp_mail_logging_error_count( $plugin_not_active_response );
            },
        ];

        return $definitions;
    } // End integration_requests()


    /**
     * Get the WP Mail Logging error count
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Count of mail errors or fallback
     */
    public function get_wp_mail_logging_error_count( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'wp-mail-logging/wp-mail-logging.php' ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpml_mails';

            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name} WHERE error IS NOT NULL"
            );
        }

        return $plugin_not_active_response;
    } // End get_wp_mail_logging_error_count()


    /**
     * Define the actions for WP Mail Logging
     *
     * @param array $definitions Existing definitions
     * @return array Modified definitions
     */
    public function integration_actions( $definitions ) : array {
        $definitions[ 'wp_mail_logging_clear_errors' ] = [
            'label'       => __( 'Purge Mail Errors', 'pluginrx-agent' ),
            'description' => __( 'Delete all failed email records logged by the WP Mail Logging plugin.', 'pluginrx-agent' ),
            'callback'    => [ $this, 'do_wp_mail_logging_clear_errors' ],
        ];

        return $definitions;
    } // End integration_actions()


    /**
     * Clear WP Mail Logging error entries
     *
     * @return array
     */
    public function do_wp_mail_logging_clear_errors() : array {
        if ( ! is_plugin_active( 'wp-mail-logging/wp-mail-logging.php' ) ) {
            return [
                'success' => false,
                'message' => __( 'WP Mail Logging plugin is not active.', 'pluginrx-agent' ),
            ];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpml_mails';

        $deleted = (int) $wpdb->query( "DELETE FROM {$table_name} WHERE error IS NOT NULL" );

        return [
            'success' => true,
            'message' => sprintf(
                __( 'Deleted %d failed email log entries.', 'pluginrx-agent' ),
                $deleted
            ),
        ];
    } // End do_wp_mail_logging_clear_errors()

}


new WPMailLoggingAgent();