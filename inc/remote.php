<?php
/**
 * Remote operations
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class Remote {

    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Remote $instance = null;


    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance() : self {
        return self::$instance ??= new self();
    } // End instance()


    /**
     * Constructor
     */
    public function __construct() {

        // Register REST API routes
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );

    } // End __construct()


    /**
     * Register REST API routes
     */
    public function register_routes() {
         register_rest_route(
            'prx-agent/v1',
            '/request',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_request' ],
                'permission_callback' => [ $this, 'authorize' ],
            ]
        );

        register_rest_route(
            'prx-agent/v1',
            '/action',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_action' ],
                'permission_callback' => [ $this, 'authorize' ],
                'args'                => [
                    'type' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]
        );
    } // End register_routes()


    /**
     * Authorize remote request
     *
     * @param \WP_REST_Request $request
     * @return true|\WP_Error
     */
    public function authorize( \WP_REST_Request $request ) {
        if ( sanitize_key( get_option( 'prxagnt_remote_access' ) ) !== 'yes' ) {
            return new \WP_Error(
                'remote_access_disabled',
                __( 'Remote access is disabled. Please enable remote access in your Agent settings.', 'pluginrx-agent' ),
                [ 'status' => 403 ]
            );
        }

        $api_key = (string) $request->get_header( 'X-PRX-Agent-Key' );

        if ( empty( $api_key ) ) {
            return new \WP_Error(
                'missing_api_key',
                __( 'Missing API key. Please provide a valid API key in your Control Center settings.', 'pluginrx-agent' ),
                [ 'status' => 401 ]
            );
        }

        $stored_key = (string) sanitize_text_field( get_option( 'prxagnt_api_key' ) );

        if ( ! hash_equals( $stored_key, $api_key ) ) {
            return new \WP_Error(
                'invalid_api_key',
                __( 'Invalid API key. Please provide a valid API key in your Control Center settings.', 'pluginrx-agent' ),
                [ 'status' => 401 ]
            );
        }

        $origin = $request->get_header( 'origin' ) ?? $request->get_header( 'Origin' );

        if ( empty( $origin ) ) {
            return new \WP_Error(
                'missing_origin',
                __( 'Missing request origin. Please ensure that your site is properly configured to send the Origin header.', 'pluginrx-agent' ),
                [ 'status' => 403 ]
            );
        }

        $allowed_domains = array_map(
            'trim',
            explode( ',', (string) sanitize_text_field( get_option( 'prxagnt_remote_domains' ) ) )
        );

        if ( ! in_array( $origin, $allowed_domains, true ) ) {
            return new \WP_Error(
                'invalid_domain',
                __( 'Domain is not authorized. Please ensure that your Agent settings include this domain.', 'pluginrx-agent' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    } // End authorize()


    /**
     * Handle remote request
     *
     * @param \WP_REST_Request $request
     * @return array|\WP_Error
     */
    public function handle_request( \WP_REST_Request $request ) {
        $definitions = Requests::definitions();
        $permissions = (array) get_option( 'prxagnt_permissions', [] );
        $result      = [];

        foreach ( $definitions as $key => $definition ) {

            // Skip if not allowed by permissions
            if ( ! in_array( $key, $permissions, true ) ) {
                continue;
            }

            // Use 'callback' if defined (will only be for integrations)
            if ( isset( $definition[ 'callback' ] ) && is_callable( $definition[ 'callback' ] ) ) {
                $result[ 'integrations' ][ $key ] = call_user_func( $definition[ 'callback' ], $request );
                continue;
            }

            // Fallback to Requests::get_{key}()
            $method = 'get_' . $key;

            if ( method_exists( Requests::class, $method ) ) {
                $result[ $key ] = Requests::{ $method }( $request );
                continue;
            }

            // If no handler, return null
            $result[ $key ] = null;
        }

        return $result;
    } // End handle_request()


    /**
     * Handle remote action
     *
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function handle_action( \WP_REST_Request $request ) {
        $type        = sanitize_key( (string) $request->get_param( 'type' ) );
        $definitions = Actions::definitions();

        if ( ! isset( $definitions[ $type ] ) ) {
            return new \WP_Error(
                'unknown_action',
                __( 'Unknown action type.', 'pluginrx-agent' ),
                [ 'status' => 400 ]
            );
        }

        // Skip if not allowed by permissions
        $permissions = (array) get_option( 'prxagnt_permissions', [] );
        if ( ! in_array( $type, $permissions, true ) ) {
            return new \WP_Error(
                'forbidden_action',
                __( 'You do not have permission to perform this action.', 'pluginrx-agent' ),
                [ 'status' => 403 ]
            );
        }

        $definition = $definitions[ $type ];

        if ( isset( $definition[ 'callback' ] ) && is_callable( $definition[ 'callback' ] ) ) {
            return call_user_func( $definition[ 'callback' ], $request );
        }

        $method = 'do_' . $type;

        if ( method_exists( Actions::class, $method ) ) {
            return Actions::{ $method }( $request );
        }

        return new \WP_Error(
            'missing_action_handler',
            __( 'No handler found for this action.', 'pluginrx-agent' ),
            [ 'status' => 500 ]
        );
    } // End handle_action()

}


Remote::instance();