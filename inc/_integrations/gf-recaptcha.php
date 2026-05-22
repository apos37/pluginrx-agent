<?php
/**
 * Gravity Forms reCAPTCHA Integration
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class GravityFormsRecaptchaAgent {

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
        $definitions[ 'gf_recaptcha_reauth' ] = [
            'label'       => __( 'Gravity Forms reCAPTCHA Reauthentication', 'pluginrx-agent' ),
            'description' => __( 'Share if Google reCAPTCHA requires reauthentication for v3 Enterprise.', 'pluginrx-agent' ),
            'callback'    => function() use ( $plugin_not_active_response ) {
                return $this->get_recaptcha_reauth( $plugin_not_active_response );
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
    public function get_recaptcha_reauth( $plugin_not_active_response ) : int|string {
        if ( is_plugin_active( 'gravityformsrecaptcha/recaptcha.php' ) ) {

            // TODO: NOT WORKING, but may not need it. Just leave it for now, though.
            
            $r = $this->check_workspace_reauth();
            // return json_encode( $r );
            return $r[ 'ok' ] && $r[ 'reauth_required' ];
        }
        return $plugin_not_active_response;
    } // End get_recaptcha_reauth()


    /**
     * Check if reauthentication is required for Google reCAPTCHA v3 Enterprise
     *
     * @return array Result of the check including 'ok', 'reauth_required', 'error', and 'response' keys
     */
    private function check_workspace_reauth() {
        if ( ! class_exists( 'GF_RECAPTCHA' ) ) {
            return [ 'ok' => false, 'reauth_required' => null, 'error' => 'GF_RECAPTCHA class not loaded', 'response' => null ];
        }

        $recaptcha = GF_RECAPTCHA::get_instance();
        if ( ! $recaptcha ) {
            return [ 'ok' => false, 'reauth_required' => null, 'error' => 'GF_RECAPTCHA instance unavailable', 'response' => null ];
        }

        $plugin_settings = [ ];

        if ( method_exists( $recaptcha, 'get_plugin_settings' ) ) {
            $plugin_settings = (array) $recaptcha->get_plugin_settings();
        }

        if ( empty( $plugin_settings ) && method_exists( $recaptcha, 'get_plugin_settings_instance' ) ) {
            $inst = $recaptcha->get_plugin_settings_instance();
            if ( is_object( $inst ) && method_exists( $inst, 'get_settings' ) ) {
                $plugin_settings = (array) $inst->get_settings( [ ] );
            }
        }

        if ( empty( $plugin_settings ) ) {
            $candidates = [ 'gravityformsrecaptcha', 'gravityformsrecaptcha_settings', 'gf_recaptcha_settings', 'gf_recaptcha' ];
            foreach ( $candidates as $opt ) {
                $val = get_option( $opt );
                if ( false === $val ) {
                    continue;
                }
                if ( is_string( $val ) ) {
                    $maybe = @unserialize( $val );
                    if ( false !== $maybe && is_array( $maybe ) ) {
                        $val = $maybe;
                    } else {
                        $maybe = json_decode( $val, true );
                        if ( is_array( $maybe ) ) {
                            $val = $maybe;
                        }
                    }
                }
                if ( is_array( $val ) ) {
                    $plugin_settings = $val;
                    break;
                }
            }
        }

        $refresh_token = $plugin_settings[ 'refresh_token' ] ?? $plugin_settings[ 'refreshToken' ] ?? '';
        $client_id     = $plugin_settings[ 'client_id' ] ?? '';
        $client_secret = $plugin_settings[ 'client_secret' ] ?? '';

        if ( empty( $refresh_token ) ) {
            return [ 'ok' => false, 'reauth_required' => null, 'error' => 'No refresh token found', 'response' => null ];
        }

        $body = [ 'refresh_token' => $refresh_token, 'grant_type' => 'refresh_token' ];
        if ( $client_id ) {
            $body[ 'client_id' ] = $client_id;
        }
        if ( $client_secret ) {
            $body[ 'client_secret' ] = $client_secret;
        }

        $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [ 'body' => $body, 'timeout' => 10 ] );
        if ( is_wp_error( $resp ) ) {
            return [ 'ok' => false, 'reauth_required' => null, 'error' => $resp->get_error_message(), 'response' => null ];
        }

        $raw  = wp_remote_retrieve_body( $resp );
        $data = json_decode( $raw, true );

        $reauth_required = false;

        if ( is_array( $data ) ) {
            $error_string = ( $data[ 'error' ] ?? '' ) . ' ' . ( $data[ 'error_description' ] ?? '' );
            if ( stripos( $error_string, 'invalid_rapt' ) !== false || stripos( $error_string, 'invalid_grant' ) !== false ) {
                $reauth_required = true;
            }
        } else {
            if ( stripos( $raw, 'invalid_rapt' ) !== false || stripos( $raw, 'invalid_grant' ) !== false ) {
                $reauth_required = true;
            }
        }

        return [
            'ok'             => true,
            'reauth_required' => $reauth_required,
            'error'          => null,
            'response'       => $data ?? $raw,
        ];
    } // End check_workspace_reauth()


}


// new GravityFormsRecaptchaAgent();