<?php
/**
 * @package NBOS_IDN
 * @version 1.6
 */
/*
Plugin Name: NBOS IDN Wordpress Bridge
Plugin URI: https://github.com/nbostech/Wordpress_IDN_Module
Description: Read only wordpress blog bridge. Make api call to read data from wordpress.
Author: Sharief
Version: 1.6

*/
class NBOS_IDN {
    //static $NBOS_API_URL = 'http://api.qa1.nbos.in';
    //static $CLIENT_ID = 'myapp-module-client';
    //static $CLIENT_SECRET = 'myapp-module-secret';

    public static function getNBOSOptions($key){
        $plugin_options = get_option("nbos_options");
        return trim($plugin_options[$key]);
    }

    public static function verifyToken(){
        $verified = false;
        // fetch request headers first
        foreach (getallheaders() as $name => $value) {
            $values = explode(" ", $value);
            if($name == 'Authorization' && $values[0] == 'Bearer'){
                $access_token = trim($values[1]);
            }
        }
        if(!empty($access_token)) {

            $clientTokenEndPoint = self::getNBOSOptions('app-idn-url').'/oauth/token';
            $clientTokenCredentials =   array( 'client_id' =>self::getNBOSOptions('app-client-key'),
                'client_secret' => self::getNBOSOptions('app-client-secret'),
                'grant_type'=> 'client_credentials',
                'scope'=> 'scope:oauth.token.verify');
            $api_response = wp_remote_post( $clientTokenEndPoint, array('body'=> $clientTokenCredentials));
            $api_data = json_decode( wp_remote_retrieve_body( $api_response ), true );

            if(!empty($api_data['access_token'])){

                $tokenVerifyEndPoint = self::getNBOSOptions('app-idn-url').'/api/oauth/v0/tokens/'.$access_token;
                $headers = array('Authorization'=> "Bearer ".$api_data['access_token']);
                $api_response = wp_remote_post( $tokenVerifyEndPoint, array('headers'=> $headers));

                $api_data = json_decode( wp_remote_retrieve_body( $api_response ), true );

                if( $api_data['messageCode']=='token.invalid' || $api_data['expired'] == true){
                    $error_msg = "Invalid client token";;
                }else{
                    $verified =  true;
                }
            }
        }
        if($verified === false){
            return new WP_Error( 'rest_cookie_invalid_nonce', __( $error_msg ), array( 'status' => 403 ) );
        }else{
            return true;
        }
    }
}

add_filter( 'rest_authentication_errors',array('NBOS_IDN','verifyToken' ), 99 );

class NBOSSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'NBOS IDN Settings',
            'manage_options',
            'my-setting-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'nbos_options' );
        ?>
        <div class="wrap">
            <h2>NBOS IDN Settings</h2>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );
                do_settings_sections( 'my-setting-admin' );
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'my_option_group', // Option group
            'nbos_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'nbos_setting_section_id', // ID
            'APP Credentials', // Title
            array( $this, 'print_section_info' ), // Callback
            'my-setting-admin' // Page
        );
        add_settings_field(
            'app-idn-url', // ID
            'IDN URL', // Title
            array( $this, 'url_callback' ), // Callback
            'my-setting-admin', // Page
            'nbos_setting_section_id' // Section
        );
        add_settings_field(
            'app-client-key', // ID
            'App Client key', // Title
            array( $this, 'key_callback' ), // Callback
            'my-setting-admin', // Page
            'nbos_setting_section_id' // Section
        );

        add_settings_field(
            'app-client-secret',
            'App Client secret',
            array( $this, 'secret_callback' ),
            'my-setting-admin',
            'nbos_setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['app-idn-url'] ) )
            $new_input['app-idn-url'] = sanitize_text_field( $input['app-idn-url'] );

        if( isset( $input['app-client-key'] ) )
            $new_input['app-client-key'] = sanitize_text_field( $input['app-client-key'] );

        if( isset( $input['app-client-secret'] ) )
            $new_input['app-client-secret'] = sanitize_text_field( $input['app-client-secret'] );


        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your Client credentials from NBOs console :';
    }
    /**
     * Get the settings option array and print one of its values
     */
    public function url_callback()
    {
        printf(
            '<input type="text" id="app-idn-url" name="nbos_options[app-idn-url]" value="%s" />',
            isset( $this->options['app-idn-url'] ) ? esc_attr( $this->options['app-idn-url']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function key_callback()
    {
        printf(
            '<input type="text" id="app-client-key" name="nbos_options[app-client-key]" value="%s" />',
            isset( $this->options['app-client-key'] ) ? esc_attr( $this->options['app-client-key']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function secret_callback()
    {
        printf(
            '<input type="password" id="app-client-secret" name="nbos_options[app-client-secret]" value="%s" />',
            isset( $this->options['app-client-secret'] ) ? esc_attr( $this->options['app-client-secret']) : ''
        );
    }
}

if( is_admin() )
    $my_settings_page = new NBOSSettingsPage();
