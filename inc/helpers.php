<?php
/**
 * Helpers
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class Helpers {

    /**
     * Convert date/time to specified timezone and format, use DDTT settings if provided
     *
     * @param string|int $date     Date string or timestamp.
     * @param string|null $format  Date format. If null, uses the format from settings.
     * @param string|null $timezone Timezone string. If null, uses the timezone from settings or WP timezone.
     * @return string Formatted date/time string.
     */
	public static function convert_timezone( $date, $format = null, $timezone = null ) : string {
        if ( empty( $date ) || $date === '0000-00-00 00:00:00' || $date === 0 || $date === '0' ) {
            return __( 'Undefined', 'pluginrx-control-center' );
        }

        $timestamp = is_numeric( $date ) ? (int) $date : strtotime( $date );
        $format    = $format ?: sanitize_text_field( get_option( 'ddtt_dev_timeformat', 'n/j/Y g:i a T' ) );

        // Use provided timezone, then dev timezone, then WP timezone
        $timezone_string = $timezone ?: sanitize_text_field( get_option( 'ddtt_dev_timezone' ) );
        $tz = $timezone_string ? new \DateTimeZone( $timezone_string ) : wp_timezone();

        return wp_date( $format, $timestamp, $tz );
    } // End convert_timezone()


    /**
     * Get the most recent session token login timestamp for a user
     *
     * @param int $user_id User ID
     * @return int|null Timestamp of most recent session token login, or null if none found
     */
    public static function get_session_token_login( $user_id ) {
        $sessions = get_user_meta( $user_id, 'session_tokens', true );

        if ( empty( $sessions ) || ! is_array( $sessions ) ) {
            return null;
        }

        $timestamps = wp_list_pluck( $sessions, 'login' );

        if ( empty( $timestamps ) ) {
            return null;
        }

        return max( $timestamps );
    } // End get_session_token_login()


    /**
     * Get online status for a user based on last activity
     *
     * @param int $user_id User ID
     * @return string Online status message
     */
    public static function online_status( $user_id ) : string {
        // Try custom last activity meta first
        $last_activity = get_user_meta( $user_id, 'ddtt_last_online', true );
        $source = '';

        if ( ! empty( $last_activity ) ) {
            $source = 'ddtt';
        } else {
            // Fallback to session tokens
            $last_activity = self::get_session_token_login( $user_id );
            if ( ! empty( $last_activity ) ) {
                $source = 'session';
            }
        }

        if ( empty( $last_activity ) ) {
            return '';
        }

        // Determine "currently online" cutoff
        $minutes = absint( get_option( 'online_users_last_seen', 5 ) );
        $cutoff  = time() - ( $minutes * 60 );

        if ( $source === 'ddtt' && $last_activity >= $cutoff ) {
            return 'online';
        }
        
        if ( $source === 'ddtt' ) {
            return $last_activity;
        } else { // session fallback
            return $last_activity;
        }
    } // End online_status()

}