<?php
/**
 * Fake User Detector Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class FakeUserDetectorAgent {

    /**
     * Constructor
     */
    public function __construct() {
         add_filter( 'prxagnt_integration_requests', [ $this, 'integrations' ], 10, 2 );
    } // End __construct()
    

    /**
     * Define the data points for Fake User Detector
     *
     * @param array $definitions Existing definitions
     * @param mixed $plugin_not_active_response Response if plugin not active
     * @return array Modified definitions
     */
    public function integrations( $definitions, $plugin_not_active_response ) : array {
        $definitions[ 'fake_user_detector_count' ] = [
            'label'       => __( 'Fake User Accounts', 'pluginrx-agent' ),
            'description' => __( 'Share the count of fake users detected by the Fake User Detector plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_fake_user_detector_count( $plugin_not_active_response );
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
    public function get_fake_user_detector_count( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'fake-user-detector/fake-user-detector.php' ) ) {
            return ( new \PluginRx\FakeUserDetector\Indicator() )->count_flagged_users();
        }
        return $plugin_not_active_response;
    } // End get_fake_user_detector_count()

}


new FakeUserDetectorAgent();