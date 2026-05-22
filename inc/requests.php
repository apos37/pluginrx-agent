<?php
/**
 * Requests to transmit remotely
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class Requests {

    /**
     * Response when plugin is not active
     *
     * @var string
     */
    private static $plugin_not_active_response = 'N/A';
    

    /**
     * The request definitions
     *
     * @return array Request array
     */
    public static function definitions() : array {
        $definitions = [
            'admin_email' => [
                'label'       => __( 'Admin Email', 'pluginrx-agent' ),
                'description' => __( 'Share the site admin email address.', 'pluginrx-agent' ),
            ],
            'admin_users' => [
                'label'       => __( 'Admin Users', 'pluginrx-agent' ),
                'description' => __( 'Share a list of users with administrator role to ensure proper access control.', 'pluginrx-agent' ),
            ],
            'server_ip' => [
                'label'       => __( 'Server IP', 'pluginrx-agent' ),
                'description' => __( 'Share detected server IP address.', 'pluginrx-agent' ),
            ],
            'abspath' => [
                'label'       => __( 'ABSPATH', 'pluginrx-agent' ),
                'description' => __( 'Share absolute WordPress path.', 'pluginrx-agent' ),
            ],
            'is_multisite' => [
                'label'       => __( 'Multisite Status', 'pluginrx-agent' ),
                'description' => __( 'Share whether this site is part of a multisite network.', 'pluginrx-agent' ),
            ],
            'blog_id' => [
                'label'       => __( 'Blog ID', 'pluginrx-agent' ),
                'description' => __( 'Share site blog ID.', 'pluginrx-agent' ),
            ],
            'wordpress_version' => [
                'label'       => __( 'WordPress Version', 'pluginrx-agent' ),
                'description' => __( 'Share installed WordPress version.', 'pluginrx-agent' ),
            ],
            'php_version' => [
                'label'       => __( 'PHP Version', 'pluginrx-agent' ),
                'description' => __( 'Share current PHP runtime version.', 'pluginrx-agent' ),
            ],
            'wp_debug' => [
                'label'       => __( 'WP_DEBUG Status', 'pluginrx-agent' ),
                'description' => __( 'Share whether WP_DEBUG is enabled.', 'pluginrx-agent' ),
            ],
            'plugins' => [
                'label'       => __( 'Installed Plugins', 'pluginrx-agent' ),
                'description' => __( 'Share list of installed plugins with versions.', 'pluginrx-agent' ),
            ],
            'themes' => [
                'label'       => __( 'Installed Themes', 'pluginrx-agent' ),
                'description' => __( 'Share list of installed themes with versions.', 'pluginrx-agent' ),
            ],
            'admin_path' => [
                'label'       => __( 'Linking to Admin Area', 'pluginrx-agent' ),
                'description' => __( 'Includes links to the WordPress admin area.', 'pluginrx-agent' ),
            ],
        ];


        /**
         * Apply filter to allow modification of request definitions
         * If adding new requests, a unique key must be used, and a 'callback' key must be provided in the definition
         * For example:
         * 'new_request' => [
         *     'label'       => __( 'New Request', 'pluginrx-agent' ),
         *     'description' => __( 'Description of new request.', 'pluginrx-agent' ),
         *     'callback'    => 'your_function_to_get_data'
         * ]
         * 
         * This adds the option to the permissions list in your Settings page. You must also enable it there to allow it to be requested remotely.
         */
        return apply_filters( 'prxagnt_integration_requests', $definitions, self::$plugin_not_active_response );
    } // End definitions()


    /**
     * Get the admin email
     *
     * @return string Admin email
     */
    public static function get_admin_email() : string {
        $admin_email = get_option( 'admin_email', '' );
        return sanitize_email( $admin_email );
    } // End get_admin_email()


    /**
     * Get the list of admin users
     *
     * @return array Admin users
     */
    public static function get_admin_users() : array {
        $admin_users = get_users( [
            'role__in' => [ 'administrator', 'super_admin' ]
        ] );

        $users = [];

        // Pre-check Dev Debug Tools plugin
        $is_dev_debug_tools_active = is_plugin_active( 'dev-debug-tools/dev-debug-tools.php' );

        foreach ( $admin_users as $user ) {
            $id = absint( $user->ID );

            $roles = array_map( 'sanitize_key', (array) $user->roles );

            $online_status = Helpers::online_status( $id );

            $users[] = [
                'user_id'        => $id,
                'user_login'     => sanitize_text_field( $user->user_login ),
                'display_name'   => sanitize_text_field( $user->display_name ),
                'user_email'     => sanitize_email( $user->user_email ),
                'role'           => $roles,
                'is_dev'         => $is_dev_debug_tools_active && \Apos37\DevDebugTools\Helpers::is_dev( $id ),
                'user_registered'=> $user->user_registered ? sanitize_text_field( $user->user_registered ) : '',
                'online_status'  => $online_status ?: 'unknown',
            ];
        }

        return $users;
    } // End get_admin_users()


    /**
     * Get the server IP address
     *
     * @return string Server IP
     */
    public static function get_server_ip() : string {
        $server_ip = $_SERVER[ 'SERVER_ADDR' ] ?? '';
        return sanitize_text_field( $server_ip );
    } // End get_server_ip()


    /**
     * Get the ABSPATH
     *
     * @return string ABSPATH
     */
    public static function get_abspath() : string {
        return sanitize_text_field( ABSPATH );
    } // End get_abspath()


    /**
     * Get whether multisite is enabled
     *
     * @return bool Is multisite
     */
    public static function get_is_multisite() : bool {
        return is_multisite();
    } // End get_is_multisite()


    /**
     * Get the blog ID
     *
     * @return int Blog ID
     */
    public static function get_blog_id() : int {
        return get_current_blog_id();
    } // End get_blog_id()


    /**
     * Get the WordPress version
     *
     * @return string WP version
     */
    public static function get_wordpress_version() : string {
        global $wp_version;
        return sanitize_text_field( $wp_version );
    } // End get_wordpress_version()


    /**
     * Get the PHP version
     *
     * @return string PHP version
     */
    public static function get_php_version() : string {
        return sanitize_text_field( phpversion() );
    } // End get_php_version()


    /**
     * Get the WP_DEBUG status
     *
     * @return bool WP_DEBUG status
     */
    public static function get_wp_debug() : bool {
        return ( defined( 'WP_DEBUG' ) && WP_DEBUG );
    } // End get_wp_debug()


    /**
     * Get the list of installed plugins with full info
     *
     * @return array Installed plugins
     */
    public static function get_plugins() : array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $update_plugins = get_site_transient( 'update_plugins' ) ?: [];
        $plugins        = [];

        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            $update_available = isset( $update_plugins->response[ $plugin_file ] );

            $plugins[] = [
                'name'             => wp_strip_all_tags( $plugin_data[ 'Name' ] ),
                'version'          => wp_strip_all_tags( $plugin_data[ 'Version' ] ),
                'author'           => wp_strip_all_tags( $plugin_data[ 'Author' ] ),
                'slug'             => $plugin_file,
                'plugin_file'      => $plugin_file,
                'active'           => is_plugin_active( $plugin_file ),
                'update_available' => $update_available
                    ? wp_strip_all_tags( $update_plugins->response[ $plugin_file ]->new_version )
                    : null,
            ];
        }

        return $plugins;
    } // End get_plugins()


    /**
     * Get the list of installed themes with full info
     *
     * @return array Installed themes
     */
    public static function get_themes() : array {
        if ( ! function_exists( 'wp_get_themes' ) ) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }

        $all_themes   = wp_get_themes();
        $update_themes = get_site_transient( 'update_themes' ) ?: [];
        $current_slug = wp_get_theme()->get_stylesheet();
        $themes       = [];

        foreach ( $all_themes as $theme_slug => $theme_obj ) {
            $update_available = isset( $update_themes->response[ $theme_slug ] );

            $themes[] = [
                'name'             => wp_strip_all_tags( $theme_obj->get( 'Name' ) ),
                'version'          => wp_strip_all_tags( $theme_obj->get( 'Version' ) ),
                'author'           => wp_strip_all_tags( $theme_obj->get( 'Author' ) ),
                'slug'             => $theme_slug,
                'active'           => ( $current_slug === $theme_slug ),
                'update_available' => $update_available
                    ? wp_strip_all_tags( $update_themes->response[ $theme_slug ][ 'new_version' ] )
                    : null,
            ];
        }

        return $themes;
    } // End get_themes()


    /**
     * Get the admin area path
     *
     * @return string Admin path URL
     */
    public static function get_admin_path() : string {
        return admin_url();
    } // End get_admin_path()

}