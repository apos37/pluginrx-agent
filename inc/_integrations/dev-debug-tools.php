<?php
/**
 * Developer Debug Tools Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class DeveloperDebugToolsAgent {

    /**
     * Constructor
     */
    public function __construct() {
         add_filter( 'prxagnt_integration_requests', [ $this, 'integration_requests' ], 10, 2 );
         add_filter( 'prxagnt_integration_actions', [ $this, 'integration_actions' ] );

    } // End __construct()
    

    /**
     * Define the data points for Developer Debug Tools
     *
     * @param array $definitions Existing definitions
     * @param mixed $plugin_not_active_response Response if plugin not active
     * @return array Modified definitions
     */
    public function integration_requests( $definitions, $plugin_not_active_response ) : array {
        $definitions[ 'dev_debug_tools_total_users' ] = [
            'label'       => __( 'Total Users', 'pluginrx-agent' ),
            'description' => __( 'Share the number of total users detected by the Developer Debug Tools plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_dev_debug_tools_total_users( $plugin_not_active_response );
            },
        ];

        $definitions[ 'dev_debug_tools_online_users' ] = [
            'label'       => __( 'Online Users', 'pluginrx-agent' ),
            'description' => __( 'Share the number of online users detected by the Developer Debug Tools plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_dev_debug_tools_online_users( $plugin_not_active_response );
            },
        ];

        $definitions[ 'dev_debug_tools_log_count' ] = [
            'label'       => __( 'Log Count', 'pluginrx-agent' ),
            'description' => __( 'Share the number of log entries detected by the Developer Debug Tools plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_dev_debug_tools_log_count( $plugin_not_active_response );
            },
        ];

        $definitions[ 'dev_debug_tools_log_size' ] = [
            'label'       => __( 'Debug Log Size', 'pluginrx-agent' ),
            'description' => __( 'Share the size of the debug log file as detected by the Developer Debug Tools plugin.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_dev_debug_tools_log_size( $plugin_not_active_response );
            },
        ];

        return $definitions;
    } // End integrations()


    /**
     * Get the Developer Debug Tools total users
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Count of total users or fallback
     */
    public function get_dev_debug_tools_total_users( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
            $user_count = count_users();
            return isset( $user_count[ 'total_users' ] ) ? intval( $user_count[ 'total_users' ] ) : 0;
        }
        return $plugin_not_active_response;
    } // End get_dev_debug_tools_total_users()


    /**
     * Get the WP Mail Logging error count
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Count of mail errors or fallback
     */
    public function get_dev_debug_tools_online_users( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
            $users = ( new \Apos37\DevDebugTools\OnlineUsers() )->get_online_users();
            return count( $users );
        }
        return $plugin_not_active_response;
    } // End get_dev_debug_tools_online_users()


    /**
     * Get the Developer Debug Tools log count
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Count of log entries or fallback
     */
    public function get_dev_debug_tools_log_count( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
            return absint( get_option( 'ddtt_total_error_count', 0 ) );
        }
        return $plugin_not_active_response;
    } // End get_dev_debug_tools_log_count()


    /**
     * Get the Developer Debug Tools log size
     *
     * @param mixed $plugin_not_active_response Value to return if plugin is not active
     * @return int|string Size of log file or fallback
     */
    public function get_dev_debug_tools_log_size( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' ) ) {
            $log_path = get_option( 'ddtt_debug_log_path' );
            if ( ! $log_path ) {
                $log_path = \Apos37\DevDebugTools\Helpers::get_default_debug_log_path( true );
            }
            
            if ( file_exists( $log_path ) ) {
                return filesize( $log_path );
            }
            return 0;
        }
        return $plugin_not_active_response;
    } // End get_dev_debug_tools_log_size()


    /**
     * Define the actions for Developer Debug Tools
     *
     * @param array $definitions Existing definitions
     * @return array Modified definitions
     */
    public function integration_actions( $definitions ) : array {
        $definitions[ 'dev_debug_tools_clear_debug_log' ] = [
            'label'       => __( 'Clear Debug Log', 'pluginrx-agent' ),
            'description' => __( 'Allow clearing the debug log from the Control Center using the Developer Debug Tools plugin.', 'pluginrx-agent' ),
            'callback'    => [ $this, 'do_clear_debug_log' ],
        ];

        return $definitions;
    } // End integration_actions()


    /**
     * Clear debug log
     *
     * @return array
     */
    public function do_clear_debug_log() : array {
        $file_path = WP_CONTENT_DIR . '/debug.log';

        $override = get_option( 'ddtt_debug_log_path' );
        if ( ! empty( $override ) && is_string( $override ) ) {
            $file_path = str_starts_with( $override, '/' )
                ? $override
                : get_home_path() . ltrim( $override, '/' );
        } elseif ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && WP_DEBUG_LOG !== true ) {
            $file_path = str_starts_with( WP_DEBUG_LOG, '/' )
                ? WP_DEBUG_LOG
                : ABSPATH . ltrim( WP_DEBUG_LOG, '/' );
        }

        if ( empty( $file_path ) ) {
            return [
                'success' => false,
                'message' => __( 'Unable to determine debug log path.', 'pluginrx-agent' ),
            ];
        }

        global $wp_filesystem;

        if ( ! $wp_filesystem ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! $wp_filesystem ) {
            return [
                'success' => false,
                'message' => __( 'Filesystem API could not be initialized.', 'pluginrx-agent' ),
            ];
        }

        if ( ! $wp_filesystem->exists( $file_path ) ) {
            return [
                'success' => false,
                'message' => __( 'Debug log file does not exist.', 'pluginrx-agent' ),
            ];
        }

        if ( ! $wp_filesystem->put_contents( $file_path, '' ) ) {
            return [
                'success' => false,
                'message' => __( 'Failed to clear debug log.', 'pluginrx-agent' ),
            ];
        }

        delete_option( 'ddtt_total_error_count' );
        set_transient( 'ddtt_log_cleared', true, 30 );
        error_log( 'Developer Debug Tools: Debug log cleared via PluginRx Agent.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

        return [
            'success' => true,
            'message' => __( 'Debug log cleared successfully. Re-check the site to clear the count and log size.', 'pluginrx-agent' ),
        ];

    } // End do_clear_debug_log()

}


new DeveloperDebugToolsAgent();