<?php
/**
 * Broken Link Notifier Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class BrokenLinkNotifierAgent {

    /**
     * Constructor
     */
    public function __construct() {
         add_filter( 'prxagnt_integration_requests', [ $this, 'integrations' ], 10, 2 );
    } // End __construct()
    

    /**
     * Define the data points for Broken Link Notifier
     *
     * @param array $definitions Existing definitions
     * @param mixed $plugin_not_active_response Response if plugin not active
     * @return array Modified definitions
     */
    public function integrations( $definitions, $plugin_not_active_response ) : array {
        $definitions[ 'broken_link_notifier_count' ] = [
            'label'       => __( 'Broken Links', 'pluginrx-agent' ),
            'description' => __( 'Share the number of broken links detected by the Broken Link Notifier plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_broken_link_notifier_count( $plugin_not_active_response );
            },
        ];

        return $definitions;
    } // End integrations()


    /**
     * Get the WP Mail Logging error count
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Count of mail errors or fallback
     */
    public function get_broken_link_notifier_count( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'broken-link-notifier/broken-link-notifier.php' ) ) {
            return ( new \BLNOTIFIER_HELPERS() )->count_broken_links();
        }
        return $plugin_not_active_response;
    } // End get_broken_link_notifier_count()

}


new BrokenLinkNotifierAgent();