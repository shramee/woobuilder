<?php
/*
Plugin Name: WooBuilder
Plugin URI: http://pootlepress.com/
Description: WooBuilder let's you take complete control of your product layout, let's you create advanced, professional looking product page layouts.
Author: pootlepress
Version: 1.1.0
Author URI: http://pootlepress.com/
@developer shramee <shramee.srivastav@gmail.com>
*/

/** Plugin admin class */
require 'inc/class-admin.php';
/** Plugin public class */
require 'inc/class-public.php';
/**
 * Pootle Pagebuilder Product Builder main class
 * @static string $token Plugin token
 * @static string $file Plugin __FILE__
 * @static string $url Plugin root dir url
 * @static string $path Plugin root dir path
 * @static string $version Plugin version
 */
class WooBuilder {

	/**
	 * @var 	WooBuilder Instance
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * @var     string Token
	 * @access  public
	 * @since   1.0.0
	 */
	public static $token;

	/**
	 * @var     string Version
	 * @access  public
	 * @since   1.0.0
	 */
	public static $version;

	/**
	 * @var 	string Plugin main __FILE__
	 * @access  public
	 * @since 	1.0.0
	 */
	public static $file;

	/**
	 * @var 	string Plugin directory url
	 * @access  public
	 * @since 	1.0.0
	 */
	public static $url;

	/**
	 * @var 	string Plugin directory path
	 * @access  public
	 * @since 	1.0.0
	 */
	public static $path;

	/**
	 * @var 	WooBuilder_Admin Instance
	 * @access  public
	 * @since 	1.0.0
	 */
	public $admin;

	/**
	 * @var 	WooBuilder_Public Instance
	 * @access  public
	 * @since 	1.0.0
	 */
	public $public;

	/**
	 * Main Pootle Pagebuilder Product Builder Instance
	 *
	 * Ensures only one instance of Storefront_Extension_Boilerplate is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return WooBuilder instance
	 */
	public static function instance() {
		if ( null == self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Checks if it's a PPB Product
	 * @param null $id
	 * @return bool
	 */
	public static function is_ppb_product( $id = null ) {
		$is_product = empty( $id ) ? is_product() : 'product' == get_post_type( $id );
		return $is_product && get_post_meta( get_the_ID(), 'woobuilder', 'single' );
	} // End instance()

	/**
	 * Constructor function.
	 * @access  private
	 * @since   1.0.0
	 */
	private function __construct() {

		self::$token   =   'woobuilder';
		self::$file    =   __FILE__;
		self::$url     =   plugin_dir_url( __FILE__ );
		self::$path    =   plugin_dir_path( __FILE__ );
		self::$version =   '1.1.0';

		add_action( 'init', array( $this, 'init' ) );
	} // End __construct()

	/**
	 * Initiates the plugin
	 * @action init
	 * @since 1.0.0
	 */
	public function init() {
		// Requires WooCommerce and PPBv3.6.0+
		if ( class_exists( 'pootle_page_builder_for_WooCommerce' ) && 1 == version_compare( POOTLEPB_VERSION, '3.6.0' )
		     && class_exists( 'WooCommerce' ) ) {

			//Initiate admin
			$this->_admin();

			//Initiate public
			$this->_public();

			//Mark this add on as active
			add_filter( 'pootlepb_installed_add_ons', array( $this, 'add_on_active' ) );

		}
	} // End init()

	/**
	 * Initiates admin class and adds admin hooks
	 * @since 1.0.0
	 */
	private function _admin() {
		//Instantiating admin class
		$this->admin = WooBuilder_Admin::instance();

		add_action( 'admin_print_styles-post-new.php',		array( $this->admin, 'enqueue' ) );
		add_action( 'admin_print_styles-post.php',			array( $this->admin, 'enqueue' ) );

		add_filter( 'admin_init',							array( $this->admin, 'admin_init' ) );
		add_filter( 'save_post',							array( $this->admin, 'save_post' ) );
		add_filter( 'post_submitbox_misc_actions',			array( $this->admin, 'product_meta_fields' ) );
		//Content block panel tabs
		add_filter( 'pootlepb_content_block_tabs',			array( $this->admin, 'content_block_tabs' ) );
		//Content block panel tabs
		add_filter( 'pootlepb_le_content_block_tabs',		array( $this->admin, 'content_block_tabs' ) );
		//Content block panel fields
		add_filter( 'pootlepb_content_block_fields',		array( $this->admin, 'content_block_fields' ) );


	}

	/**
	 * Initiates public class and adds public hooks
	 * @since 1.0.0
	 */
	private function _public() {
		/** Plugin public class */
		require 'inc/class-modules.php';

		//Instantiating public class
		$this->public = WooBuilder_Public::instance();

		$this->public->init();

		add_filter( 'wc_get_template_part', array( $this->public, 'wc_get_template_part' ), 10, 3 );
		add_action( 'pootlepb_live_editor_init', function () {
			$this->public->set_ppb_product_builder_meta( null, get_the_ID(), get_post_type() );
		} );

		//Adding front end JS and CSS in /assets folder
		add_action( 'wp_enqueue_scripts', array( $this->public, 'enqueue' ) );
		add_filter( 'pootlepb_live_page_template', array( $this->public, 'set_ppb_product_builder_meta' ), 10, 3 );
		//Render product shortcodes
		add_action( 'pootlepb_render_content_block', array( $this->public, 'process_shortcode' ), 52, 2 );
		add_action( 'pootlepb_enqueue_admin_scripts', array( $this->public, 'live_editor_scripts' ), 52, 2 );

	} // End enqueue()

	/**
	 * Marks this add on as active on
	 * @param array $active Active add ons
	 * @return array Active add ons
	 * @since 1.0.0
	 */
	public function add_on_active( $active ) {

		// To allows ppb add ons page to fetch name, description etc.
		$active[ self::$token ] = self::$file;

		return $active;
	}
}

/** Intantiating main plugin class */
WooBuilder::instance( __FILE__ );