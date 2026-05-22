<?php
/**
 * Actions to perform remotely
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class Actions {
    
    /**
     * The data definitions
     *
     * @return array Data array
     */
    public static function definitions() : array {
        $definitions = [
            'update_plugins' => [
                'label'       => __( 'Update Plugins', 'pluginrx-agent' ),
                'description' => __( 'Allow updating plugins from the Control Center.', 'pluginrx-agent' )
            ],
            'update_themes' => [
                'label'       => __( 'Update Themes', 'pluginrx-agent' ),
                'description' => __( 'Allow updating themes from the Control Center.', 'pluginrx-agent' )
            ],
            'update_wordpress' => [
                'label'       => __( 'Update WordPress', 'pluginrx-agent' ),
                'description' => __( 'Allow updating WordPress core from the Control Center.', 'pluginrx-agent' )
            ]
        ];


        /**
         * Apply filter to allow modification of action definitions
         * If adding new action points, a unique key must be used, and a 'callback' key must be provided in the definition
         * For example:
         * 'new_action_point' => [
         *     'label'       => __( 'New Action Point', 'pluginrx-agent' ),
         *     'description' => __( 'Description of new action point.', 'pluginrx-agent' ),
         *     'callback'    => 'your_function_to_perform_action'
         * ]
         * 
         * This adds the option to the permissions list in your Settings page. You must also enable it there to allow it to be performed remotely.
         */
        return apply_filters( 'prxagnt_integration_actions', $definitions );
    } // End definitions()


    /**
     * Perform plugin updates
     *
     * @return array
     */
    public static function do_update_plugins() : array {
        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        add_filter( 'filesystem_method', fn() => 'direct' );

        $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
        $result   = $upgrader->bulk_upgrade( array_keys( get_plugins() ) );

        if ( $result === false ) {
            return [
                'success' => false,
                'message' => __( 'Plugin update process failed to start.', 'pluginrx-agent' ),
            ];
        }

        foreach ( $result as $plugin => $status ) {
            if ( $status === false || is_wp_error( $status ) ) {
                return [
                    'success' => false,
                    'message' => __( 'One or more plugins failed to update.', 'pluginrx-agent' ),
                ];
            }
        }

        return [
            'success' => true,
            'message' => __( 'Plugins updated successfully. Please recheck the site to ensure all updates were applied correctly.', 'pluginrx-agent' ),
        ];
    } // End do_update_plugins()


    /**
     * Perform theme updates
     *
     * @return array
     */
    public static function do_update_themes() : array {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $upgrader = new \Theme_Upgrader( new \Automatic_Upgrader_Skin() );
        $result   = $upgrader->bulk_upgrade( array_keys( wp_get_themes() ) );

        if ( $result === false ) {
            return [
                'success' => false,
                'message' => __( 'Theme update process failed to start.', 'pluginrx-agent' ),
            ];
        }

        foreach ( $result as $theme => $status ) {
            if ( $status === false || is_wp_error( $status ) ) {
                return [
                    'success' => false,
                    'message' => __( 'One or more themes failed to update.', 'pluginrx-agent' ),
                ];
            }
        }

        return [
            'success' => true,
            'message' => __( 'Themes updated successfully. Please recheck the site to ensure all updates were applied correctly.', 'pluginrx-agent' ),
        ];
    } // End do_update_themes()


    /**
     * Perform WordPress core update
     *
     * @return array
     */
    public static function do_update_wordpress() : array {
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        wp_version_check();

        $updates = get_core_updates();

        if ( empty( $updates ) || ! is_array( $updates ) ) {
            return [
                'success' => false,
                'message' => __( 'Unable to retrieve WordPress update information.', 'pluginrx-agent' ),
            ];
        }

        $update = $updates[ 0 ];

        if ( 'upgrade' !== $update->response ) {
            return [
                'success' => true,
                'message' => __( 'WordPress is already at the latest version.', 'pluginrx-agent' ),
            ];
        }

        $upgrader = new \Core_Upgrader( new \Automatic_Upgrader_Skin() );
        $result   = $upgrader->upgrade( $update );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'WordPress core updated successfully. Please recheck the site to confirm the update.', 'pluginrx-agent' ),
        ];
    } // End do_update_wordpress()

}