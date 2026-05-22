<?php
/**
 * Menu
 */

namespace PluginRx\Agent;

if ( ! defined( 'ABSPATH' ) ) exit;

class Menu {
    
    /**
     * The single instance of the class
     *
     * @var self|null
     */
    private static ?Menu $instance = null;


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
    private function __construct() {

        // Register menu
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        
    } // End __construct()


    /**
     * Register admin menu and submenus
     */
    public function register_menu() : void {
        // Die if not admin or dev
        if ( ! Bootstrap::has_access() ) {
            return;
        }

        $page_name = Bootstrap::name();
        $capability = 'manage_options';

        add_submenu_page(
            'options-general.php',
            $page_name,
            $page_name,
            $capability,
            'prxagnt-settings',
            [ $this, 'render_settings' ]
        );
    } // End register_menu()


    /**
     * Render settings page
     */
    public function render_settings() : void {
        global $current_screen;
        $slug = 'prxagnt-settings';
        if ( $current_screen->id != 'settings_page_' . $slug ) {
            return;
        }
        ?><div class="wrap prxagnt-wrap <?php echo esc_attr( $slug ); ?>"><?php
            (new Settings())->render_page();
        ?></div><?php
    } // End render_settings()
    
}


Menu::instance();