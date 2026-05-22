<?php 
/**
 * Plugin settings
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {

    /**
     * @var string Text domain
     */
    private string $text_domain;


    /**
     * @var string Nonce
     */
    private $nonce = 'prxagnt_settings_nonce';
    private $nonce_action = 'prxagnt_save_settings';


    /**
     * @var Settings|null Singleton instance
     */
    private static ?Settings $instance = null;


    /**
     * Get instance
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

        // Set text domain
        $this->text_domain = Bootstrap::textdomain();

        // Save settings
        add_action( 'admin_init', [ $this, 'save' ] );

        // AJAX generate API key
        add_action( 'wp_ajax_prxagnt_generate_api_key', [ $this, 'ajax_generate_api_key' ] );

		// Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End __construct()

    
    /**
     * The page
     *
     * @return void
     */
    public function render_page() {
        // Licensing
        $licensing = LicenseManager::instance();
        $license_id = $licensing->get_license_key();
        $license_comments = $licensing->get_license_comments();

        // API Key
        $api_key = sanitize_text_field( get_option( 'prxagnt_api_key', '' ) );

        // Remote Access
        $remote_access = sanitize_key( get_option( 'prxagnt_remote_access', 'no' ) );
        $control_center_domains = sanitize_text_field( get_option( 'prxagnt_remote_domains', '' ) );

        $requests = Requests::definitions();
        $actions = Actions::definitions();
        $permissions = array_merge( $requests, $actions );

        // Permissions / Allowances
        $saved_permissions = get_option( 'prxagnt_permissions', '__NOT_SAVED__' );
        if ( $saved_permissions === '__NOT_SAVED__' ) {
            // Never saved before → default to all permissions checked
            $request_keys = array_keys( $requests );
            $action_keys = array_keys( $actions );
            $permission_keys = array_merge( $request_keys, $action_keys );
        } else {
            // Already saved → use saved array (could be empty)
            $permission_keys = filter_var_array( (array) $saved_permissions, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        }
        
        // Delete DB option
        $delete_db_option = sanitize_key( get_option( 'prxagnt_delete_db', 'no' ) );
        ?>
		<h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>
        <span class="prxagnt-version"><?php echo esc_html__( 'Version', 'pluginrx-agent' ) . ': ' . esc_html( Bootstrap::version() ); ?></span>
        <form method="post">
            <?php wp_nonce_field( $this->nonce_action, $this->nonce ); ?>

            <h2><?php esc_html_e( 'License', 'pluginrx-agent' ); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="prxagnt-license-id">
                        <th scope="row"><?php echo esc_html__( 'License ID', 'pluginrx-agent' ); ?></th>
                        <td><input type="text" id="prxagnt-license-id" name="prxagnt_license_id" value="<?php echo esc_attr( $license_id ); ?>" style="width: 30rem;"><br><?php echo wp_kses_post( $license_comments ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Remote Access', 'pluginrx-agent' ); ?></h2>
            <p><?php esc_html_e( 'Enable this option to allow the Control Center to remotely access this site. Disabled by default for security. Specify the domain or comma-separated list of domains that are allowed to access this site via the Control Center. Only requests from these domains will be validated.', 'pluginrx-agent' ); ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Allow Remote Access', 'pluginrx-agent' ); ?></th>
                        <td>
                            <input type="checkbox" name="prxagnt_remote_access" value="yes" <?php checked( $remote_access, 'yes' ); ?>>
                            <?php esc_html_e( 'This site can be accessed remotely by the Control Center(s).', 'pluginrx-agent' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Control Center Domain(s)', 'pluginrx-agent' ); ?></th>
                        <td>
                            <input type="text" name="prxagnt_remote_domains" value="<?php echo esc_attr( $control_center_domains ); ?>" style="width: 30rem;" placeholder="e.g. controlcenter.com, dashboard.example.com">
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Permissions', 'pluginrx-agent' ); ?></h2>
            <p><?php esc_html_e( 'Permissions control what the Control Center is allowed to do on this site. All actions are disabled by default. Enable only the actions you explicitly want to allow, such as updates, log access, or cache clearing. The Control Center cannot perform any action that is not enabled here.', 'pluginrx-agent' ); ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach ( $permissions as $key => $item ) : ?>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html( $item[ 'label' ] ); ?>
                                <?php if ( isset( $item[ 'callback' ] ) && ! empty( $item[ 'callback' ] ) ) : ?>
                                    <span class="dashicons dashicons-admin-plugins" title="<?php esc_attr_e( 'Integration', 'pluginrx-agent' ); ?>"></span>
                                <?php endif; ?>
                            </th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="prxagnt_permissions[<?php echo esc_attr( $key ); ?>]"
                                        value="1"
                                        <?php checked( in_array( $key, $permission_keys, true ) ); ?>
                                    >
                                    <?php echo esc_html( $item[ 'description' ] ); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'API Key', 'pluginrx-agent' ); ?></h2>
            <p><?php esc_html_e( 'This API key allows your PluginRx Control Center to securely authenticate with this site. Generate the key here and enter it into the Control Center during pairing. Regenerating the key immediately revokes access for any Control Center using the previous key.', 'pluginrx-agent' ); ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="prxagnt-api-key">
                        <th scope="row"><?php echo esc_html__( 'API Key', 'pluginrx-agent' ); ?></th>
                        <td>
                            <span id="prxagnt-api-key-display"><?php echo esc_html( $api_key ); ?></span>
                            <button class="button button-primary" id="prxagnt-generate-api-key" type="button">
                                <?php esc_html_e( 'Generate New Key', 'pluginrx-agent' ); ?>
                            </button>
                            <button class="button button-secondary" id="prxagnt-copy-api-key" type="button">
                                <?php esc_html_e( 'Copy API Key', 'pluginrx-agent' ); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Data', 'pluginrx-agent' ); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="prxagnt-delete-db">
                        <th scope="row"><?php echo esc_html__( 'Delete All Data on Uninstall', 'pluginrx-agent' ); ?></th>
                        <td><input type="checkbox" id="prxagnt-delete-db" name="prxagnt_delete_db" value="yes" <?php checked( $delete_db_option, 'yes' ); ?>></td>
                    </tr>
                </tbody>
            </table>

            <button class="button button-primary" id="prxagnt-save-settings-btn" type="submit">
                <?php esc_html_e( 'Save Settings', 'pluginrx-agent' ); ?>
            </button>
        </form>
        <?php
    } // End render_page()


    /**
     * Save settings
     *
     * @return void
     */
    public function save() {
        // Verify nonce
        if ( ! isset( $_POST[ $this->nonce ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->nonce ] ) ), $this->nonce_action ) ) {
            return;
        }

        // License ID
        if ( isset( $_POST[ 'prxagnt_license_id' ] ) ) {
            $license_id = sanitize_text_field( wp_unslash( $_POST[ 'prxagnt_license_id' ] ) );
            update_option( 'prxagnt_license_id', $license_id, false );
        }

        // API Key is saved via AJAX

        // Permissions
        $permission_to_save = [];
        if ( isset( $_POST[ 'prxagnt_permissions' ] ) && is_array( $_POST[ 'prxagnt_permissions' ] ) ) {
            $requests = Requests::definitions();
            $actions = Actions::definitions();
            $permissions = array_merge( $requests, $actions );

            foreach ( $permissions as $key => $item ) {
                if ( isset( $_POST[ 'prxagnt_permissions' ][ $key ] ) && sanitize_key( wp_unslash( $_POST[ 'prxagnt_permissions' ][ $key ] ) ) === '1' ) {
                    $permission_to_save[] = $key;
                }
            }
        }
        // Save empty array if user unchecked everything
        update_option( 'prxagnt_permissions', $permission_to_save, false );

        // Remote Access
        $remote_access = ( isset( $_POST[ 'prxagnt_remote_access' ] ) && sanitize_key( wp_unslash( $_POST[ 'prxagnt_remote_access' ] ) ) === 'yes' ) ? 'yes' : 'no';
        update_option( 'prxagnt_remote_access', $remote_access, false );

        // Remote Access Domains
        if ( isset( $_POST[ 'prxagnt_remote_domains' ] ) ) {
            $domains_raw = sanitize_text_field( wp_unslash( $_POST[ 'prxagnt_remote_domains' ] ) );
            // Split by comma
            $domains_array = array_map( 'trim', explode( ',', $domains_raw ) );
            // Strip protocol from each domain
            $domains_array = array_map( function( $d ) {
                return preg_replace( '#^https?://#', '', $d );
            }, $domains_array );
            // Recombine into a comma-separated string
            $domains_sanitized = implode( ',', $domains_array );
            update_option( 'prxagnt_remote_domains', $domains_sanitized, false );
        }

        // Delete DB
        $delete_db = ( isset( $_POST[ 'prxagnt_delete_db' ] ) && sanitize_key( wp_unslash( $_POST[ 'prxagnt_delete_db' ] ) ) === 'yes' ) ? 'yes' : 'no';
        update_option( 'prxagnt_delete_db', $delete_db, false );

        // Redirect back with updated notice
        wp_safe_redirect( add_query_arg( 'settings-updated', 'true', wp_get_referer() ) );
        exit;
    } // End save()


    /**
     * AJAX generate API key
     *
     * @return void
     */
    public function ajax_generate_api_key() {
        if ( ! Bootstrap::has_access() ) {
            wp_send_json_error( [ 'message' => 'unauthorized' ], 403 );
        }

        check_ajax_referer( $this->nonce_action, 'nonce' );

        try {
            $raw_key = bin2hex( random_bytes( 32 ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => 'key_generation_failed' ], 500 );
        }

        update_option( 'prxagnt_api_key', $raw_key, false );

        wp_send_json_success( [
            'api_key' => $raw_key
        ] );
    } // End ajax_generate_api_key()


	/**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        // Check if we are on the correct admin page
        if ( $hook !== 'settings_page_prxagnt-settings' ) {
            return;
        }

		// Register and enqueue your CSS
        $css_path = Bootstrap::url( 'inc/css/' );
        $js_path  = Bootstrap::url( 'inc/js/' );
        $script_version = Bootstrap::script_version();

        // CSS
        wp_enqueue_style( $this->text_domain . '-settings', $css_path . 'settings.css', [], $script_version );

        // JS
        wp_enqueue_script(
            $this->text_domain . '-settings',
            $js_path . 'settings.js',
            [ 'jquery' ],
            $script_version,
            true
        );

        wp_localize_script(
            $this->text_domain . '-settings',
            'prxagnt_settings',
            [
                'nonce'      => wp_create_nonce( $this->nonce_action ),
                'generating' => __( 'Generating', 'pluginrx-agent' ),
            ]
        );
    } // End enqueue_scripts()

}


Settings::instance();