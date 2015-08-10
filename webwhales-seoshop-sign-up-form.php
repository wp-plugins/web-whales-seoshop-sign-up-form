<?php
	/**
	 * Plugin Name: SEOshop Sign Up Form by Web Whales
	 * Description: This plugin provides an affiliate sign up form for SEOshop Partners. Not yet a SEOshop Partner? Request a partnership for free at <a href="http://www.getseoshop.com/partners/?utm_source=Web%20Whales&utm_medium=referral&utm_campaign=Web%20Whales%20SEOshop%20Sign%20Up%20WordPress%20plugin" target="_blank">SEOshop</a>.
	 * Version: 1.0
	 * Author: Web Whales
	 * Author URI: https://webwhales.nl
	 * Contributors: ronald_edelschaap
	 * License: GPLv3
	 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
	 * Text Domain: ww-seoshop-sign-up
	 * Domain Path: /languages
	 *
	 * Requires at least: 4.1
	 * Tested up to: 4.3
	 *
	 * @author  Web Whales
	 * @version 1.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	/**
	 * Class WW_SEOshop_Sign_Up
	 */
	final class WW_SEOshop_Sign_Up {

		const PLUGIN_PREFIX = 'ww_seoshop_sign_up_', PLUGIN_SLUG = 'ww-seoshop-sign-up', PLUGIN_VERSION = '1.0', TEXT_DOMAIN = 'ww-seoshop-sign-up';

		private static $instance, $notices = array( 'error' => array(), 'update' => array() );

		/**
		 * Constructor for the gateway.
		 */
		private function __construct() {
			$this->init();
		}

		/**
		 * Enqueue the plugin script and style sheet when needed
		 */
		public function enqueue_scripts() {
			if ( self::has_shortcode( 'seoshop_sign_up_form' ) ) {
				$css_file = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'ww-seoshop-sign-up.css';
				$js_file  = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'ww-seoshop-sign-up.js';

				if ( is_file( $css_file ) ) {
					wp_enqueue_style( self::PLUGIN_SLUG, plugin_dir_url( __FILE__ ) . 'assets/ww-seoshop-sign-up.css', array(), filemtime( $js_file ) );
				}

				if ( is_file( $js_file ) ) {
					wp_enqueue_script( self::PLUGIN_SLUG, plugin_dir_url( __FILE__ ) . 'assets/ww-seoshop-sign-up.js', array( 'jquery' ), filemtime( $js_file ), true );
					wp_localize_script( self::PLUGIN_SLUG, 'ww_seoshop_options', array( 'sticky_header_offset' => (int) self::get_option( 'sticky_header_height', 0 ) ) );
				}

				if ( self::recaptcha_active() ) {
					wp_enqueue_script( self::PLUGIN_SLUG . '_recaptcha', 'https://www.google.com/recaptcha/api.js' );
				}
			}
		}

		/**
		 * Format a SEOshop dashboard sign in URL
		 *
		 * @param string $user_id
		 * @param string $login_hash
		 *
		 * @return string Returns the formatted URL or an empty string when no sign in URL was set at the plugin settings page
		 */
		public function get_dashboard_sign_in_url( $user_id, $login_hash ) {
			$sign_in_url = self::get_option( 'sign_in_url', '' );

			if ( $sign_in_url != '' ) {
				$sign_in_url = str_replace( array( '%user_id%', '%login_hash%' ), array( $user_id, $login_hash ), $sign_in_url );
			}

			return $sign_in_url;
		}

		/**
		 * Retrieve a plugin option
		 *
		 * @param string $name
		 *
		 * @return string Returns the value or an empty string when the setting does not exist
		 */
		public function option( $name ) {
			return self::get_option( $name, '' );
		}

		/**
		 * Handles the admin settings page
		 */
		public function page_settings() {
			if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'save_seoshop_settings' && check_admin_referer( 'save-seoshop-settings' ) ) {
				$post_fields = array(
					'affiliate_company',
					'affiliate_key',
					'affiliate_url',
					'sign_in_url',
					'sticky_header_height',
					'callback_success',
					'recaptcha_site_key',
					'recaptcha_secret_key'
				);
				$post_data   = $this->array_trim( array_intersect_key( $_POST, array_flip( $post_fields ) ) );
				$updated     = false;

				foreach ( $post_data as $post => $data ) {
					switch ( $post ) {
						case 'sign_in_url':
							if ( strpos( $data, '%user_id%' ) !== false && strpos( $data, '%login_hash%' ) !== false && $data != self::get_option( $post ) ) {
								self::update_option( $post, $data );
								$updated = true;
							}
							break;

						case 'sticky_header_height':
							$data = (int) $data;

							if ( $data != self::get_option( $post ) ) {
								self::update_option( $post, $data );
								$updated = true;
							}
							break;

						default:
							if ( $data != self::get_option( $post ) ) {
								self::update_option( $post, $data );
								$updated = true;
							}
					}
				}

				if ( $updated ) {
					self::$notices['update'][] = '<p>' . __( 'Your SEOshop settings have been updated.', self::TEXT_DOMAIN ) . '</p>';
				}
			}

			self::$notices['update'][] = '<p><strong>' . __( 'Important!', self::TEXT_DOMAIN ) . '</strong> ' . sprintf( __( 'You can use this plugin when you are a registered SEOshop Partner only. Not yet a SEOshop Partner? Request a partnership for free at %s.', self::TEXT_DOMAIN ), '<a href="' . __( 'http://www.getseoshop.com/partners/', self::TEXT_DOMAIN ) . '?utm_source=Web%20Whales&utm_medium=referral&utm_campaign=Web%20Whales%20SEOshop%20Sign%20Up%20WordPress%20plugin" target="_blank">SEOshop</a>' ) . '</p>';

			$this->include_template( 'settings', array( '_this' => $this ) );
		}

		/**
		 * Print error messages on the admin settings page
		 */
		public function print_admin_error_notices() {
			if ( ! empty( self::$notices['error'] ) ) {
				foreach ( self::$notices['error'] as $error_notice ) {
					print '<div class="error">' . $error_notice . '</div>';
				}
			}
		}

		/**
		 * Print update messages on the admin settings page
		 */
		public function print_admin_update_notices() {
			if ( ! empty( self::$notices['update'] ) ) {
				foreach ( self::$notices['update'] as $update_notice ) {
					print '<div class="updated">' . $update_notice . '</div>';
				}
			}
		}

		/**
		 * Register the plugin shortcodes
		 */
		public function register_shortcodes() {
			add_shortcode( 'seoshop_sign_up_form', array( '\WW_SEOshop_Sign_Up', 'do_shortcode_seoshop_sign_up_form' ) );
		}

		/**
		 * Get the plugin text domain
		 *
		 * @return string
		 */
		public function text_domain() {
			return self::TEXT_DOMAIN;
		}

		/**
		 * Add the admin settings page to the admin settings menu
		 */
		public function wp_admin_menu() {
			add_options_page( __( 'SEOshop Affiliate Settings', self::TEXT_DOMAIN ), __( 'SEOshop Affiliate Settings', self::TEXT_DOMAIN ), 'manage_options', self::PLUGIN_SLUG, array(
				&$this,
				'page_settings'
			) );
		}

		/**
		 * Add an settings link to the plugin action links
		 *
		 * @param array $actions
		 *
		 * @return array
		 */
		public function wp_admin_plugin_action_links( $actions ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				$settings_action = '<a href="' . menu_page_url( self::PLUGIN_SLUG, false ) . '">' . esc_html( __( 'Settings' ) ) . '</a>';

				array_unshift( $actions, $settings_action );
			}

			return $actions;
		}

		/**
		 * Execute the sign up action
		 *
		 * @return array Returns the status, status message and status data in an array
		 */
		private function action_process_sign_up_form() {
			$result      = array( 'status' => false, 'message' => '', 'error_fields' => array() );
			$form_fields = array( 'store_name', 'first_name', 'last_name', 'email_address', 'phone_number' );

			if ( self::recaptcha_active() ) {
				$form_fields[] = 'g-recaptcha-response';
			}

			$form_data = $this->array_trim( array_intersect_key( $_POST, array_flip( $form_fields ) ) );

			if ( $this->check_plugin_settings() ) {
				foreach ( $form_fields as $form_field ) {
					switch ( $form_field ) {
						case 'email_address':
							if ( empty( $form_data[ $form_field ] ) || ! filter_var( $form_data[ $form_field ], FILTER_VALIDATE_EMAIL ) ) {
								$result['error_fields'][] = $form_field;
							}

							break;

						case 'phone_number':
							if ( empty( $form_data[ $form_field ] ) ) {
								$result['error_fields'][] = $form_field;
							} else {
								$form_data[ $form_field ] = str_replace( array( '-', ' ' ), '', $form_data[ $form_field ] );

								if ( strlen( $form_data[ $form_field ] ) < 9 || strlen( $form_data[ $form_field ] ) > 12 ) {
									$result['error_fields'][] = $form_field;
								}
							}

							break;

						default:
							if ( empty( $form_data[ $form_field ] ) ) {
								$result['error_fields'][] = $form_field;
							}

							break;
					}
				}

				if ( empty( $result['error_fields'] ) && in_array( 'g-recaptcha-response', $form_fields ) ) {
					$captcha_result = $this->remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
						'secret'   => self::get_option( 'recaptcha_secret_key', '' ),
						'response' => $form_data['g-recaptcha-response'],
					) );

					$captcha_result = ! empty( $captcha_result['body'] ) ? (array) json_decode( $captcha_result['body'], true ) : array();

					if ( empty( $captcha_result['success'] ) ) {
						$result['error_fields'][] = 'g-recaptcha-response';
					}
				}


				if ( empty( $result['error_fields'] ) ) {
					//Add affiliate details
					$form_data['country']            = 'nl';
					$form_data['language']           = 'nl';
					$form_data['module']             = 'seoshop.stores.signup';
					$form_data['source_type']        = 'affiliate';
					$form_data['source_description'] = 'affiliateform';
					$form_data['partner_id']         = self::get_option( 'affiliate_key' );
					$form_data['source_campaign']    = self::get_option( 'affiliate_company' );
					$form_data['form_token']         = $this->get_form_token();
				}


				if ( empty( $result['error_fields'] ) && ! empty( $form_data['form_token'] ) ) {
					//Make the sign up call
					$sign_up_form = $this->remote_post(
						self::get_option( 'affiliate_url' ),
						array(
							'data' => json_encode( $form_data )
						),
						array(
							'cookies' => ! empty( $_SESSION['seoshop_sid'] ) ? array( 'SEOSHOP_SID' => $_SESSION['seoshop_sid'] ) : array(),
							'timeout' => 30,
						)
					);

					$sign_up_result = ! empty( $sign_up_form['body'] ) ? (array) json_decode( $sign_up_form['body'], true ) : array();

					unset( $sign_up_result['form']['fields']['language'], $sign_up_result['form']['fields']['country'] );

					if ( ! empty( $sign_up_form['response'] ) && substr( $sign_up_form['response'], 0, 1 ) == '2' && ! empty( $sign_up_result ) ) {

						if ( ! empty( $sign_up_result['success'] ) && ! empty( $sign_up_result['validated'] ) && ! empty( $sign_up_result['data']['user']['id'] ) && ! empty( $sign_up_result['data']['user']['login_hash'] ) ) {
							$success_callback = self::get_option( 'callback_success' );
							$sign_in_url      = $this->get_dashboard_sign_in_url( $sign_up_result['data']['user']['id'], $sign_up_result['data']['user']['login_hash'] );

							$result['status'] = true;

							$result['message'] = '<strong>' . __( 'Congratulations! Your SEOshop store has been created.', self::TEXT_DOMAIN ) . '</strong><br /><br />';

							if ( ! empty( $sign_up_result['form']['fields']['password']['value'] ) ) {
								$result['message'] .= sprintf( __( 'The password for your SEOshop account is %s.', self::TEXT_DOMAIN ), '<strong>' . $sign_up_result['form']['fields']['password']['value'] . '</strong>' ) . ' ';
							}

							$result['message'] .= __( 'Please use the following link to go to your SEOshop Dashboard.', self::TEXT_DOMAIN );
							$result['message'] .= '<br /><br /><a href="' . $sign_in_url . '" target="_blank">' . $sign_in_url . '</a>';

							if ( ! empty( $success_callback ) ) {
								$result['callback_success'] = esc_js( $success_callback );
							}
						} else {
							$result['message'] = '<strong>' . __( 'Error:', self::TEXT_DOMAIN ) . '</strong> ' . __( 'Not all fields are filled in correctly.', self::TEXT_DOMAIN );
						}
					} else {
						$result['message'] = __( 'An unknown error has occurred. Please try again or contact us for any support.', self::TEXT_DOMAIN );
					}

					if ( ! empty( $sign_up_result['session_id'] ) ) {
						$_SESSION['seoshop_sid'] = $sign_up_result['session_id'];
					}
				} elseif ( ! empty( $result['error_fields'] ) ) {
					$result['message'] = '<strong>' . __( 'Error:', self::TEXT_DOMAIN ) . '</strong> ' . __( 'Not all fields are filled in correctly.', self::TEXT_DOMAIN );


					$success_callback = self::get_option( 'callback_success' );
					if ( ! empty( $success_callback ) ) {
						$result['callback_success'] = $success_callback;
					}
				} else {
					$result['message'] = __( 'An unknown error has occurred. Please try again or contact us for any support.', self::TEXT_DOMAIN );
				}
			} else {
				$result['message'] = '<strong>' . __( 'Error:', self::TEXT_DOMAIN ) . '</strong> ' . __( 'The SEOshop sign up settings are not set up correctly.', self::TEXT_DOMAIN );
			}

			return $result;
		}

		/**
		 * Trim the values of an array recursively
		 *
		 * @param array  $array    The array
		 * @param string $charlist Optionally, the stripped characters can also be specified using the charlist parameter. Simply list all characters that you want to be stripped. With .. you can specify a range of characters.
		 *
		 * @see trim()
		 *
		 * @return array Returns the array with trimmed values
		 */
		private function array_trim( $array, $charlist = null ) {
			foreach ( $array as &$arr ) {
				$arr = is_null( $charlist )
					? ( is_array( $arr ) ? $this->array_trim( $arr ) : trim( $arr ) )
					: ( is_array( $arr ) ? $this->array_trim( $arr, $charlist ) : trim( $arr, $charlist ) );
				unset( $arr );
			}

			return $array;
		}

		/**
		 * Get a valid form token from SEOshop
		 *
		 * @return string|bool Returns the token on success or FALSE on failure
		 */
		private function get_form_token() {
			$token = false;

			if ( $this->check_plugin_settings() ) {
				//Add affiliate details
				$form_data = array(
					'module'             => 'seoshop.stores.signup',
					'source_type'        => 'affiliate',
					'source_description' => 'affiliateform',
					'partner_id'         => self::get_option( 'affiliate_key' ),
					'source_campaign'    => self::get_option( 'affiliate_company' ),
				);

				$token_request = $this->remote_post(
					self::get_option( 'affiliate_url' ),
					array(
						'data' => json_encode( $form_data )
					),
					array(
						'cookies'     => ! empty( $_SESSION['seoshop_sid'] ) ? array( 'SEOSHOP_SID' => $_SESSION['seoshop_sid'] ) : array(),
						'httpversion' => '1.1',
						'timeout'     => 30,
					)
				);

				$token_request_result = ! empty( $token_request['body'] ) ? (array) json_decode( $token_request['body'], true ) : array();

				if ( ! empty( $token_request['response'] ) && substr( $token_request['response'], 0, 1 ) == '2' && ! empty( $token_request_result ) && ! empty( $token_request_result['form']['fields']['form_token']['value'] ) ) {
					$token = $token_request_result['form']['fields']['form_token']['value'];
				}

				if ( ! empty( $token_request_result['session_id'] ) ) {
					$_SESSION['seoshop_sid'] = $token_request_result['session_id'];
				}
			}

			return $token;
		}

		/**
		 * Load some general stuff
		 *
		 * @return void
		 */
		private function init() {
			//Load text domain
			load_plugin_textdomain( self::TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );

			//Add an item to the admin settings menu and a settings link to the plugin page
			add_action( 'admin_menu', array( $this, 'wp_admin_menu' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wp_admin_plugin_action_links' ) );

			//Enqueue the plugin script and style sheet when needed
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

			//Register and handle shortcodes
			$this->process_sign_up_form();
			$this->register_shortcodes();
		}

		/**
		 * Process a posted sign up form
		 */
		private function process_sign_up_form() {
			if ( self::doing_ajax() && ! empty( $_POST['action'] ) && $_POST['action'] == self::PLUGIN_PREFIX . 'process_form' ) {
				self::ajax_print_json( $this->action_process_sign_up_form() );
			}
		}

		/**
		 * Perform an HTTP POST request with cURL
		 *
		 * @param string $url       The URL that will be requested
		 * @param array  $form_data The POST form data
		 * @param array  $options   Optional request data
		 *
		 * @see wp_remote_post()
		 *
		 * @return array|bool Returns the request data on success or FALSE on failure
		 */
		private function remote_post( $url, $form_data, $options = array() ) {
			$set_time_limit = false;

			$options['body']        = ! empty( $form_data ) && is_array( $form_data ) ? $form_data : array();
			$options['httpversion'] = '1.1';
			$options['sslverify']   = false;
			$options['timeout']     = ! empty( $options['timeout'] ) ? (int) $options['timeout'] : 0;

			if ( $options['timeout'] > 0 ) {
				$set_time_limit = (int) ini_get( 'max_execution_time' );
				@set_time_limit( $options['timeout'] + 15 );
			} else {
				unset( $options['timeout'] );
			}

			$response = wp_remote_post( $url, $options );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$headers             = wp_remote_retrieve_headers( $response );
			$headers['response'] = wp_remote_retrieve_response_code( $response );
			$headers['body']     = wp_remote_retrieve_body( $response );

			if ( ! empty( $set_time_limit ) ) {
				set_time_limit( $set_time_limit );
			}

			return $headers;
		}

		/**
		 * Add a new option
		 *
		 * @param string $option
		 * @param mixed  $value
		 * @param string $autoload
		 *
		 * @return bool
		 *
		 * @see add_option()
		 */
		public static function add_option( $option, $value = '', $autoload = 'yes' ) {
			return add_option( self::PLUGIN_PREFIX . $option, $value, '', $autoload );
		}

		/**
		 * Check whether the plugin settings are set up correctly
		 *
		 * @return bool
		 */
		public static function check_plugin_settings() {
			return self::get_option( 'affiliate_company', '' ) != '' && self::get_option( 'affiliate_key', '' ) != '' && self::get_option( 'affiliate_url', '' ) != '' && self::get_option( 'sign_in_url', '' ) != '';
		}

		/**
		 * Removes option by name. Prevents removal of protected WordPress options.
		 *
		 * @param string $option
		 *
		 * @return bool
		 *
		 * @see delete_option()
		 */
		public static function delete_option( $option ) {
			return delete_option( self::PLUGIN_PREFIX . $option );
		}

		/**
		 * Convert the sign up form shortcode to a nice HTML form
		 *
		 * @param array  $args    The shortcode attributes
		 * @param string $content Optional content between the shortcode tags, will be printed after the form
		 *
		 * @return string Returns HTML output
		 */
		public static function do_shortcode_seoshop_sign_up_form( $args, $content = null ) {
			if ( self::check_plugin_settings() ) {
				ob_start();

				$args = shortcode_atts( array( 'class' => '' ), $args );

				self::include_theme_template( 'shortcodes/sign_up_form', array( 'args' => $args ) );

				return ob_get_clean() . ( ! is_null( $content ) ? $content : '' );
			} else {
				return '<em style="background:#EBEBEB;border:1px solid #E0E0E0;border-radius:7px;display:block;margin:20px auto;padding:20px;width:95%;"><strong>' . __( 'Error:', self::TEXT_DOMAIN ) . '</strong> ' . __( 'The SEOshop sign up settings are not set up correctly.', self::TEXT_DOMAIN ) . '</em>';
			}
		}

		/**
		 * Check whether the current request is an AJAX call
		 *
		 * @return bool Returns TRUE or FALSE
		 */
		public static function doing_ajax() {
			return ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest';
		}

		/**
		 * Gets a class instance. Used to prevent this plugin from loading multiple times
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( empty( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Retrieve option value based on name of option.
		 *
		 * @param mixed      $option
		 * @param bool|mixed $default
		 *
		 * @return mixed|void
		 *
		 * @see get_option()
		 */
		public static function get_option( $option, $default = false ) {
			return get_option( self::PLUGIN_PREFIX . $option, $default );
		}

		/**
		 * Include a template file
		 *
		 * @param string $template The template's file name
		 * @param array  $args     Optional arguments, will be converted to PHP variables that can be used in the template file
		 *
		 * @return bool Returns TRUE when the template was successfully loaded or FALSE on failure
		 */
		public static function include_template( $template, $args = array() ) {
			if ( ! empty( $args ) && is_array( $args ) ) {
				extract( $args );
			}

			if ( strpos( $template, '.' ) === false ) {
				$template .= '.phtml';
			}

			$template = plugin_dir_path( __FILE__ ) . 'templates' . DIRECTORY_SEPARATOR . str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $template );

			if ( is_file( $template ) ) {
				include $template;

				return true;
			}

			return false;
		}

		/**
		 * Include a template file. When a template file exists in the "seoshop-sign-up" folder within the active theme folder, that file will overwrite the default template file
		 *
		 * @param string $template The template's file name
		 * @param array  $args     Optional arguments, will be converted to PHP variables that can be used in the template file
		 *
		 * @return bool Returns TRUE when the template was successfully loaded or FALSE on failure
		 */
		public static function include_theme_template( $template, $args = array() ) {
			if ( ! empty( $args ) && is_array( $args ) ) {
				extract( $args );
			}

			$template = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $template );

			if ( strpos( $template, '.' ) === false ) {
				$template .= '.phtml';
			}

			$theme_folder        = realpath( get_stylesheet_directory() ) . DIRECTORY_SEPARATOR . 'seoshop-sign-up' . DIRECTORY_SEPARATOR;
			$parent_theme_folder = realpath( get_template_directory() ) . DIRECTORY_SEPARATOR . 'seoshop-sign-up' . DIRECTORY_SEPARATOR;
			$template_folder     = plugin_dir_path( __FILE__ ) . 'templates' . DIRECTORY_SEPARATOR;

			if ( is_file( $theme_folder . $template ) ) {
				include( $theme_folder . $template );

				return true;
			} elseif ( $theme_folder != $parent_theme_folder && is_file( $parent_theme_folder . $template ) ) {
				include( $parent_theme_folder . $template );

				return true;
			} elseif ( is_file( $template_folder . $template ) ) {
				include( $template_folder . $template );

				return true;
			}

			return false;
		}

		public static function plugin_activate() {
			$current_version  = self::get_option( 'plugin_version', '0' );
			$current_settings = array(
				'affiliate_url' => self::get_option( 'affiliate_url', '' ),
				'sign_in_url'   => self::get_option( 'sign_in_url', '' ),
			);

			switch ( $current_version ) {
				case '0':
				case '0.1':
				default:
					if ( empty( $current_settings['affiliate_url'] ) ) {
						self::add_option( 'affiliate_url', 'https://seoshop.webshopapp.com/api/gateway' );
					}

					if ( empty( $current_settings['sign_in_url'] ) ) {
						self::add_option( 'sign_in_url', 'https://seoshop.webshopapp.com/backoffice/signin?uid=%user_id%&hash=%login_hash%' );
					}

					break;
			}

			self::add_option( 'plugin_version', self::PLUGIN_VERSION );
		}

		/**
		 * Check whether the reCAPTCHA key pair is set
		 *
		 * @return bool
		 */
		public static function recaptcha_active() {
			return self::get_option( 'recaptcha_site_key', '' ) != '' && self::get_option( 'recaptcha_secret_key', '' ) != '';
		}

		/**
		 * Generate the reCAPTCHA HTML code
		 */
		public static function recaptcha_generate() {
			if ( self::recaptcha_active() ) {
				print '<div class="g-recaptcha" data-sitekey="' . self::get_option( 'recaptcha_site_key', '' ) . '"></div>';
			}
		}

		/**
		 * Update the value of an option that was already added.
		 *
		 * @param string $option
		 * @param mixed  $value
		 *
		 * @return bool
		 *
		 * @see update_option()
		 */
		public static function update_option( $option, $value ) {
			return update_option( self::PLUGIN_PREFIX . $option, wp_unslash( $value ) );
		}

		/**
		 * Mark the document as JSON, print a variable as JSON formatted code and exit the script
		 *
		 * @param array $content The variable printed in JSON format. Should be an array. Any other variable type will be converted to an array first. Giving other variable types than an array may result in unexpected behaviour.
		 */
		private static function ajax_print_json( $content ) {
			@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

			send_nosniff_header();
			nocache_headers();

			exit( json_encode( (array) $content ) );
		}

		/**
		 * Check whether the current page is a single post and it's content has the selected shortcode in it
		 *
		 * @param string $shortcode
		 *
		 * @return bool
		 */
		private static function has_shortcode( $shortcode ) {
			if ( is_singular() ) {
				$post         = get_post();
				$post_content = apply_filters( 'ww_seoshop_sign_up_has_shortcode_filter_post_content', $post->post_content );

				return ! empty( $post_content ) && stripos( $post_content, '[' . $shortcode ) !== false;
			}

			return false;
		}
	}


	/**
	 * Load this plugin through the static instance
	 */
	add_action( 'plugins_loaded', array( 'WW_SEOshop_Sign_Up', 'get_instance' ), 99 );


	/**
	 * Register the activation hook
	 */
	register_activation_hook( __FILE__, array( 'WW_SEOshop_Sign_Up', 'plugin_activate' ) );