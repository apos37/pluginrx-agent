<?php
/**
 * Advanced Tools for Gravity Forms Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdvancedToolsForGravityFormsAgent {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'prxagnt_integration_requests', [ $this, 'integration_requests' ], 10, 2 );
        add_filter( 'prxagnt_integration_actions', [ $this, 'integration_actions' ] );
    } // End __construct()
    

    /**
     * Define the data points for Advanced Tools for Gravity Forms
     *
     * @param array $definitions Existing definitions
     * @param mixed $plugin_not_active_response Response if plugin not active
     * @return array Modified definitions
     */
    public function integration_requests( $definitions, $plugin_not_active_response ) : array {
        $definitions[ 'gftools_spam_count' ] = [
            'label'       => __( 'Gravity Forms Spam Count', 'pluginrx-agent' ),
            'description' => __( 'Share the number of spam entries detected by the Advanced Tools for Gravity Forms plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_gftools_spam_count( $plugin_not_active_response );
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
    public function get_gftools_spam_count( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'gf-tools/gf-tools.php' ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'gf_entry';
            $total_spam = ( int ) $wpdb->get_var(
                "SELECT COUNT( id ) FROM {$table} WHERE status = 'spam'"
            );
            return $total_spam;
        }
        return $plugin_not_active_response;
    } // End get_gftools_spam_count()


    /**
     * Define the actions for Advanced Tools for Gravity Forms
     *
     * @param array $definitions Existing definitions
     * @return array Modified definitions
     */
    public function integration_actions( $definitions ) : array {
        $definitions[ 'gftools_delete_spam' ] = [
            'label'       => __( 'Delete All Spam Entries', 'pluginrx-agent' ),
            'description' => __( 'Delete all spam entries from Gravity Forms entries table detected by the Advanced Tools for Gravity Forms plugin.', 'pluginrx-agent' ),
            'callback'    => [ $this, 'do_gftools_delete_spam' ],
        ];

        return $definitions;
    } // End integration_actions()


    /**
     * Action to delete all spam entries from Gravity Forms
     *
     * @return array Result of the action
     */
    public function do_gftools_delete_spam() : array {
        if ( ! is_plugin_active( 'gf-tools/gf-tools.php' ) ) {
            return [
                'success' => false,
                'message' => __( 'Advanced Tools for Gravity Forms plugin is not active.', 'pluginrx-agent' ),
            ];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gf_entry';

        $deleted = (int) $wpdb->query( "DELETE FROM {$table} WHERE status = 'spam'" );

        return [
            'success' => true,
            'message' => sprintf(
                __( 'Deleted %d spam entries from Gravity Forms.', 'pluginrx-agent' ),
                $deleted
            ),
        ];
    } // End do_gftools_delete_spam()

}


new AdvancedToolsForGravityFormsAgent();