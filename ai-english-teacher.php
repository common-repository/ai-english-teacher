<?php
/**
 * Plugin Name: AI English Teacher
 * Description: This plugin uses OpenAI to correct English grammar and rephrase sentences on your website.
 * Version: 1.0
 * Author: Raihan
 * Author URI: http://wpdrug.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ai-english-teacher
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

class AIET_English_Teacher {
	
	public function __construct(){
		//Initialize the actions
		add_action('init', array( $this, 'aiet_localization_setup' ));
		// Add the admin menu
		add_action('admin_menu', array( $this, 'aiet_add_admin_menu' ));
		// Register the API key setting
		add_action('admin_init', array( $this, 'aiet_register_settings' ));
		//Frontend scripts
		$enable_grammar_checker = get_option('aiet_settings_options');
		if(!empty($enable_grammar_checker) && is_array($enable_grammar_checker)){
			$enable_grammar_checker = $enable_grammar_checker['aiet_grammar_checker_frontend'];
		}
		if($enable_grammar_checker == 1){
			add_action('wp_enqueue_scripts', array( $this, 'aiet_enqueue_scripts' ));
			add_action('wp_footer', array( $this, 'aiet_footer_contents' ));
		}
		//Admin area scripts
		add_action('admin_enqueue_scripts', array( $this, 'aiet_enqueue_scripts' ));
		add_action('admin_footer', array( $this, 'aiet_footer_contents' ));
		
	}
	
	//Initialize plugin for localization
	public function aiet_localization_setup() {
        load_plugin_textdomain( 'ai-english-teacher', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
	
	//AI English Teacher menu
	public function aiet_add_admin_menu() {
		add_menu_page(
			'AI English Teacher',
			'AI English Teacher',
			'manage_options',
			'ai-english-teacher',
			array( $this, 'aiet_admin_page' ),
			'dashicons-awards',
			30
		);
	}

	// Define the admin page
	public function aiet_admin_page() {
		?>
		<div class="wrap">
		<?php settings_errors(); ?>
			<h1><?php echo esc_html__('AI English Teacher','ai-english-teacher'); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'aiet_settings' ); ?>
				<?php do_settings_sections( 'ai-english-teacher' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	//Register settings
	public function aiet_register_settings() {
		register_setting(
			'aiet_settings', // option_group
			'aiet_settings_options', // option_name
			array($this, 'aiet_settings_sanitize')
		);
		add_settings_section(
			'aiet_section',
			'Settings',
			array($this, 'aiet_section_callback'),
			'ai-english-teacher'
		);
		add_settings_field(
			'aiet_api_key',
			'API Key',
			array($this, 'aiet_api_key_callback'),
			'ai-english-teacher',
			'aiet_section'
		);
		add_settings_field(
			'aiet_grammar_checker_frontend',
			'Turn on the grammar checker for the frontend',
			array($this, 'aiet_grammar_checker_frontend_callback'),
			'ai-english-teacher',
			'aiet_section'
		);
	}

	// Define the API key setting section
	public function aiet_section_callback() {
		//
	}
	
	// Define the API key setting field
	public function aiet_api_key_callback() {
		$api_key = get_option('aiet_settings_options');
		if(!empty($api_key) && is_array($api_key)){
			$api_key = $api_key['aiet_api_key'];
		}
		echo '<input type="text" placeholder="OpenAI API key" name="aiet_settings_options[aiet_api_key]" value="' . esc_attr($api_key) . '" /><br><span>Get the API key from <a target="_blank" href="https://platform.openai.com/account/api-keys">here</a>.</span>';
	}

	// Define the Enable grammar checker setting field
	public function aiet_grammar_checker_frontend_callback() {
		$enable_grammar_checker = get_option('aiet_settings_options');
		if(!empty($enable_grammar_checker) && is_array($enable_grammar_checker)){
			$enable_grammar_checker = esc_attr( $enable_grammar_checker['aiet_grammar_checker_frontend'] );
		}
		echo '<input type="checkbox" name="aiet_settings_options[aiet_grammar_checker_frontend]" value="1" ' . checked(1, $enable_grammar_checker, false) . ' />';
	}

	// Save options
	public function aiet_settings_sanitize($input) {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		$sanitary_values = array();
		
		$aiet_api_key = $input['aiet_api_key'];
		$api_status = $this->aiet_openai_api_status($aiet_api_key);
		
		if ( isset( $aiet_api_key ) && $api_status == 200 ) {
			$sanitary_values['aiet_api_key'] = sanitize_text_field( $aiet_api_key );
		}else {
			$sanitary_values['aiet_api_key'] = '';
			add_settings_error('aiet_api_key', 'aiet_api_key', 'API key error: Please enter a valid API key.', 'error');
		}

		$sanitary_values['aiet_grammar_checker_frontend'] = intval($input['aiet_grammar_checker_frontend']);
		
		return apply_filters( 'aiet_save_admin_menu', $sanitary_values, $input );
	}
	
	//Enqueue scripts
	public function aiet_enqueue_scripts(){
		$api_key = get_option('aiet_settings_options');
		if(!empty($api_key) && is_array($api_key)){
			$api_key = $api_key['aiet_api_key'];
		}
		if( $api_key != '' && current_user_can( 'administrator' ) ){
			wp_register_script( 'aiet-scripts', plugin_dir_url( __FILE__ ) . 'assets/scripts.js', null, null, true );
			wp_enqueue_script('aiet-scripts-min', plugin_dir_url( __FILE__ ) . 'assets/scripts.min.js', null, null, true);
			wp_localize_script( 'aiet-scripts-min', 'aietScriptData', array(
			   'api' => $api_key,
			));
			wp_enqueue_style('aiet-style', plugin_dir_url( __FILE__ ) . 'assets/style.css');
		}

	}
	
	//Check API key
	private function aiet_openai_api_status($input_api_key){
		$api_key = get_option('aiet_settings_options');
		if(!empty($api_key) && is_array($api_key)){
			$api_key = $api_key['aiet_api_key'];
		}
		if(!empty($input_api_key)){
			$api_key = $input_api_key;
		}
		if(!empty($api_key)){
			$url = 'https://api.openai.com/v1/models';
			$headers = array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $api_key
			);

			$args = array(
				'headers' => $headers,
				'sslverify' => false
			);

			$response = wp_remote_get($url, $args);
			$status_code = wp_remote_retrieve_response_code($response);

			if (!is_wp_error($response) && $status_code == 200) {
				$body = wp_remote_retrieve_body($response);
				// Process the response body as needed
			}

			return $status_code;
		}

	}
	
	//Form
	public function aiet_footer_contents() {
		$api_key = get_option('aiet_settings_options');
		if(!empty($api_key) && is_array($api_key)){
			$api_key = $api_key['aiet_api_key'];
		}
		if( $api_key != '' && current_user_can( 'administrator' ) ){
			require_once('temp/form.php');
		}
	}
}

new AIET_English_Teacher();

