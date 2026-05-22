<?php
/**
 * Clear Cache Everywhere Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class ClearCacheEverywhereAgent {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'prxagnt_integration_requests', [ $this, 'integration_requests' ], 10, 2 );
        add_filter( 'prxagnt_integration_actions', [ $this, 'integration_actions' ] );
    } // End __construct()


    /**
     * Define the data points for Clear Cache Everywhere
     *
     * @param array $definitions Existing definitions
     * @param mixed $plugin_not_active_response Response if plugin not active
     * @return array Modified definitions
     */
    public function integration_requests( $definitions, $plugin_not_active_response ) : array {
        $definitions[ 'clear_cache_everywhere_hosting_url' ] = [
            'label'       => __( 'Hosting Cache URL', 'pluginrx-agent' ),
            'description' => __( 'Share the hosting purge cache URL used by the Clear Cache Everywhere plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_clear_cache_everywhere_hosting_url( $plugin_not_active_response );
            },
        ];
        return $definitions;
    } // End integration_requests()


    /**
     * Get the Clear Cache Everywhere hosting purge URL
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Hosting purge URL or the not active response
     */
    public function get_clear_cache_everywhere_hosting_url( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'clear-cache-everywhere/clear-cache-everywhere.php' ) ) {
            return esc_url( get_option( 'clear_cache_everywhere_hosting_purge_url', '' ));
        }
        return $plugin_not_active_response;
    } // End get_clear_cache_everywhere_hosting_url()
    

    /**
     * Define the actions for Clear Cache Everywhere
     *
     * @param array $definitions Existing definitions
     * @return array Modified definitions
     */
    public function integration_actions( $definitions ) : array {
        $definitions[ 'clear_cache_everywhere' ] = [
            'label'       => __( 'Clear Cache Everywhere', 'pluginrx-agent' ),
            'description' => __( 'Allow clearing site cache from the Control Center using the Clear Cache Everywhere plugin.', 'pluginrx-agent' ),
            'callback'    => [ $this, 'do_clear_cache_everywhere' ],
        ];

        return $definitions;
    } // End integration_actions()


    /**
     * Clear site cache
     *
     * @return array
     */
    public function do_clear_cache_everywhere() : array {
        $class_file = WP_PLUGIN_DIR . '/clear-cache-everywhere/includes/clear-cache.php';

        if ( ! class_exists( '\Apos37\ClearCache\Clear' ) && file_exists( $class_file ) ) {
            include_once $class_file;
        }

        if ( ! class_exists( '\Apos37\ClearCache\Clear' ) ) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: URL to the Clear Cache Everywhere plugin page */
                    __( 'Clear Cache Everywhere plugin does not appear to be installed. You can download it from the WordPress plugin repository. <a href="%s" target="_blank" rel="noopener noreferrer">Learn more &gt;</a>', 'pluginrx-agent' ),
                    esc_url( 'https://pluginrx.com/plugin/clear-cache-everywhere/' )
                ),
            ];
        }

        $results = ( new \Apos37\ClearCache\Clear() )->clear_all( true, false );

        if ( ! is_array( $results ) ) {
            return [
                'success' => false,
                'message' => __( 'Unexpected cache clear response.', 'pluginrx-agent' ),
            ];
        }

        $lines = [];

        foreach ( $results as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $title  = $item[ 'title' ] ?? '';
            $result = $item[ 'result' ] ?? null;

            if ( empty( $title ) || $result === null ) {
                continue;
            }

            $lines[] = sprintf(
                '%s: %s',
                $title,
                is_string( $result ) ? ucfirst( $result ) : __( 'Unknown', 'pluginrx-agent' )
            );
        }

        return [
            'success' => true,
            'message' => __( 'Cache Cleared: ', 'pluginrx-agent' ) . implode( ', ', $lines ),
        ];
    } // End do_clear_cache_everywhere()

}


new ClearCacheEverywhereAgent();