<?php
/**
 * Minimal WordPress stubs for unit tests.
 *
 * Only what the plugin touches is implemented. State lives in WpStubs so tests
 * can reset and inspect it. Variadic forwarding uses positional arrays only:
 * unpacking arrays with string keys is an error on PHP 7.4.
 *
 * @package Saizul\Debloat
 */

declare(strict_types=1);

// phpcs:ignoreFile

final class WpStubs {
	/** @var array<string,array<int,array<string,callable>>> */
	public static $filters = array();
	/** @var array<int,array{0:string,1:array<int,mixed>}> */
	public static $actions = array();
	/** @var array<string,mixed> */
	public static $options = array();
	/** @var array<int,object> */
	public static $posts = array();
	/** @var bool */
	public static $admin = false;
	/** @var bool */
	public static $logged_in = false;
	/** @var bool|array<string,bool> */
	public static $can = true;
	/** @var array<string,bool> Conditional tags such as is_singular. */
	public static $is = array();
	/** @var mixed */
	public static $queried = null;
	/** @var bool */
	public static $block_theme = false;
	/** @var bool|string */
	public static $block_widget = false;
	/** @var string */
	public static $pagenow = 'index.php';
	/** @var array<string,string[]> Recorded calls: dequeue_style, dequeue_script, deregister_script, meta_box, menu, submenu, widget, status, post_type_support, enqueue_style, enqueue_script. */
	public static $calls = array();
	/** @var array<string,array<string,bool>> */
	public static $supports = array();
	/** @var array<string,mixed> */
	public static $registry = array();
	/** @var array<int,string> */
	public static $doing_it_wrong = array();

	public static function reset(): void {
		self::$filters        = array();
		self::$actions        = array();
		self::$options        = array();
		self::$posts          = array();
		self::$admin          = false;
		self::$logged_in      = false;
		self::$can            = true;
		self::$is             = array();
		self::$queried        = null;
		self::$block_theme    = false;
		self::$block_widget   = false;
		self::$pagenow        = 'index.php';
		self::$calls          = array();
		self::$registry       = array();
		self::$doing_it_wrong = array();
		self::$supports       = array(
			'post'       => array( 'comments' => true, 'trackbacks' => true ),
			'page'       => array( 'comments' => true ),
			'attachment' => array( 'comments' => true ),
		);
		$GLOBALS['pagenow'] = 'index.php';
		unset( $GLOBALS['wp_query'] );
	}

	public static function record( string $type, string $value ): void {
		self::$calls[ $type ][] = $value;
	}

	/** @return string[] */
	public static function calls( string $type ): array {
		return self::$calls[ $type ] ?? array();
	}

	public static function post( int $id, array $fields = array() ): WP_Post {
		$post = new WP_Post(
			array_merge(
				array(
					'ID'           => $id,
					'post_content' => '',
					'post_title'   => 'Post ' . $id,
					'post_type'    => 'post',
					'post_status'  => 'publish',
					'post_parent'  => 0,
				),
				$fields
			)
		);
		self::$posts[ $id ] = $post;

		return $post;
	}

	/** Unique key of a callback, like _wp_filter_build_unique_id(). */
	public static function key( $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}
		if ( is_object( $callback ) ) {
			return spl_object_hash( $callback );
		}
		if ( is_array( $callback ) ) {
			$target = is_object( $callback[0] ) ? spl_object_hash( $callback[0] ) : (string) $callback[0];
			return $target . '::' . (string) $callback[1];
		}
		return '';
	}
}

final class WpDieException extends RuntimeException {
}

final class WpRedirectException extends RuntimeException {
	/** @var int */
	public $status;

	public function __construct( string $url, int $status ) {
		parent::__construct( $url );
		$this->status = $status;
	}
}

final class WpNonceException extends RuntimeException {
}

class WP_Error {
	/** @var string */
	public $code;
	/** @var string */
	public $message;
	/** @var mixed */
	public $data;

	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code    = (string) $code;
		$this->message = (string) $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_data() {
		return $this->data;
	}
}

class WP_Post {
	public $ID           = 0;
	public $post_content = '';
	public $post_title   = '';
	public $post_type    = 'post';
	public $post_status  = 'publish';
	public $post_parent  = 0;

	public function __construct( array $fields ) {
		foreach ( $fields as $key => $value ) {
			$this->$key = $value;
		}
	}
}

class WP_REST_Request {
	/** @var string */
	private $route;

	public function __construct( $method = 'GET', $route = '' ) {
		$this->route = (string) $route;
	}

	public function get_route() {
		return $this->route;
	}
}

class WP_Query {
	public $is_search  = false;
	public $is_404     = false;
	public $main       = true;
	public $query_vars = array();

	public function is_main_query() {
		return $this->main;
	}

	public function is_search() {
		return $this->is_search;
	}

	public function is_404() {
		return $this->is_404;
	}

	public function set_404() {
		$this->is_404    = true;
		$this->is_search = false;
	}
}

class WP_Admin_Bar {
	/** @var string[] */
	public $removed = array();

	public function remove_node( $id ) {
		$this->removed[] = (string) $id;
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Hooks.

function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	WpStubs::$filters[ $name ][ (int) $priority ][ WpStubs::key( $callback ) ] = $callback;
	return true;
}

function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $name, $callback, $priority, $accepted_args );
}

function remove_filter( $name, $callback, $priority = 10 ) {
	$key = WpStubs::key( $callback );
	if ( isset( WpStubs::$filters[ $name ][ (int) $priority ][ $key ] ) ) {
		unset( WpStubs::$filters[ $name ][ (int) $priority ][ $key ] );
		if ( array() === WpStubs::$filters[ $name ][ (int) $priority ] ) {
			unset( WpStubs::$filters[ $name ][ (int) $priority ] );
		}
		return true;
	}
	return false;
}

function remove_action( $name, $callback, $priority = 10 ) {
	return remove_filter( $name, $callback, $priority );
}

function has_filter( $name, $callback = false ) {
	if ( false === $callback ) {
		return ! empty( WpStubs::$filters[ $name ] );
	}
	$key = WpStubs::key( $callback );
	foreach ( WpStubs::$filters[ $name ] ?? array() as $priority => $callbacks ) {
		if ( isset( $callbacks[ $key ] ) ) {
			return $priority;
		}
	}
	return false;
}

function has_action( $name, $callback = false ) {
	return has_filter( $name, $callback );
}

function apply_filters( $name, $value, ...$args ) {
	if ( empty( WpStubs::$filters[ $name ] ) ) {
		return $value;
	}
	ksort( WpStubs::$filters[ $name ] );
	foreach ( WpStubs::$filters[ $name ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$value = call_user_func_array( $callback, array_merge( array( $value ), array_values( $args ) ) );
		}
	}
	return $value;
}

function do_action( $name, ...$args ) {
	WpStubs::$actions[] = array( $name, array_values( $args ) );
	if ( empty( WpStubs::$filters[ $name ] ) ) {
		return;
	}
	ksort( WpStubs::$filters[ $name ] );
	foreach ( WpStubs::$filters[ $name ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			call_user_func_array( $callback, array_values( $args ) );
		}
	}
}

function do_action_ref_array( $name, $args ) {
	WpStubs::$actions[] = array( $name, $args );
	if ( empty( WpStubs::$filters[ $name ] ) ) {
		return;
	}
	ksort( WpStubs::$filters[ $name ] );
	foreach ( WpStubs::$filters[ $name ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

function __return_true() {
	return true;
}

function __return_false() {
	return false;
}

function __return_zero() {
	return 0;
}

function __return_empty_string() {
	return '';
}

function __return_empty_array() {
	return array();
}

function _doing_it_wrong( $function_name, $message, $version ) {
	WpStubs::$doing_it_wrong[] = (string) $message;
}

// i18n and escaping.

function __( $text, $domain = 'default' ) {
	return $text;
}

function _n( $single, $plural, $number, $domain = 'default' ) {
	return 1 === (int) $number ? $single : $plural;
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( $text );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_textarea( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_url( $url ) {
	return htmlspecialchars( (string) $url, ENT_QUOTES );
}

function sanitize_key( $key ) {
	return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_unslash( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wp_unslash', $value );
	}
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( (string) $url, $component );
}

function untrailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' );
}

// Options.

function get_option( $name, $default = false ) {
	return array_key_exists( $name, WpStubs::$options ) ? WpStubs::$options[ $name ] : $default;
}

function update_option( $name, $value, $autoload = null ) {
	WpStubs::$options[ $name ] = $value;
	return true;
}

function delete_option( $name ) {
	unset( WpStubs::$options[ $name ] );
	return true;
}

function get_bloginfo( $show = '', $filter = 'raw' ) {
	return 'version' === $show ? '6.9' : 'Example';
}

function wp_salt( $scheme = 'auth' ) {
	return 'test-salt-' . $scheme;
}

// Requests and users.

function is_admin() {
	return WpStubs::$admin;
}

function wp_doing_ajax() {
	return false;
}

function wp_doing_cron() {
	return false;
}

function is_user_logged_in() {
	return WpStubs::$logged_in;
}

function current_user_can( $capability, ...$args ) {
	if ( is_array( WpStubs::$can ) ) {
		return ! empty( WpStubs::$can[ $capability ] );
	}
	return WpStubs::$can;
}

function is_customize_preview() {
	return ! empty( WpStubs::$is['customize_preview'] );
}

function is_singular( $post_types = '' ) {
	return ! empty( WpStubs::$is['singular'] );
}

function is_attachment( $attachment = '' ) {
	return ! empty( WpStubs::$is['attachment'] );
}

function is_author( $author = '' ) {
	return ! empty( WpStubs::$is['author'] );
}

function is_comment_feed() {
	return ! empty( WpStubs::$is['comment_feed'] );
}

function is_feed( $feeds = '' ) {
	return ! empty( WpStubs::$is['feed'] );
}

function wp_is_block_theme() {
	return WpStubs::$block_theme;
}

function is_active_widget( $callback = false, $widget_id = false, $id_base = false, $skip_inactive = true ) {
	return WpStubs::$block_widget;
}

function get_queried_object() {
	return WpStubs::$queried;
}

function get_queried_object_id() {
	return is_object( WpStubs::$queried ) && isset( WpStubs::$queried->ID ) ? (int) WpStubs::$queried->ID : 0;
}

function has_blocks( $content = null ) {
	if ( $content instanceof WP_Post ) {
		$content = $content->post_content;
	}
	return false !== strpos( (string) $content, '<!-- wp:' );
}

function get_post( $post = null ) {
	$id = is_object( $post ) ? (int) $post->ID : (int) $post;
	return WpStubs::$posts[ $id ] ?? null;
}

function get_post_status( $post = null ) {
	$post = get_post( $post );
	return $post ? (string) $post->post_status : false;
}

function get_permalink( $post = 0 ) {
	$post = get_post( $post );
	return $post ? 'https://example.com/?p=' . $post->ID : false;
}

function wp_get_attachment_url( $attachment_id = 0 ) {
	$post = get_post( $attachment_id );
	return $post ? 'https://example.com/wp-content/uploads/file-' . $post->ID . '.jpg' : false;
}

function get_post_types( $args = array(), $output = 'names' ) {
	$types = array_keys( WpStubs::$supports );
	return array_combine( $types, $types );
}

function post_type_supports( $post_type, $feature ) {
	return ! empty( WpStubs::$supports[ $post_type ][ $feature ] );
}

function remove_post_type_support( $post_type, $feature ) {
	unset( WpStubs::$supports[ $post_type ][ $feature ] );
	WpStubs::record( 'post_type_support', $post_type . ':' . $feature );
}

// URLs.

function home_url( $path = '' ) {
	return 'https://example.com' . ( '' === $path ? '' : '/' . ltrim( (string) $path, '/' ) );
}

function admin_url( $path = '' ) {
	return 'https://example.com/wp-admin/' . ltrim( (string) $path, '/' );
}

function plugins_url( $path = '', $plugin = '' ) {
	return 'https://example.com/wp-content/plugins/wp-debloat/' . ltrim( (string) $path, '/' );
}

function plugin_basename( $file ) {
	return basename( dirname( (string) $file ) ) . '/' . basename( (string) $file );
}

function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = $args[1] ?? '';
	} else {
		$params = array( $args[0] => $args[1] );
		$url    = $args[2] ?? '';
	}
	$parts = explode( '?', (string) $url, 2 );
	$query = array();
	if ( isset( $parts[1] ) ) {
		parse_str( $parts[1], $query );
	}
	foreach ( $params as $key => $value ) {
		if ( false === $value ) {
			unset( $query[ $key ] );
		} else {
			$query[ $key ] = $value;
		}
	}
	return $parts[0] . ( array() === $query ? '' : '?' . http_build_query( $query ) );
}

function remove_query_arg( $key, $url = false ) {
	$keys   = (array) $key;
	$params = array();
	foreach ( $keys as $name ) {
		$params[ $name ] = false;
	}
	return add_query_arg( $params, (string) $url );
}

function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
	throw new WpRedirectException( (string) $location, (int) $status );
}

function status_header( $code ) {
	WpStubs::record( 'status', (string) $code );
}

function nocache_headers() {
	WpStubs::record( 'nocache', '1' );
}

function wp_die( $message = '', $title = '', $args = array() ) {
	throw new WpDieException( (string) $message );
}

// Assets.

function wp_dequeue_style( $handle ) {
	WpStubs::record( 'dequeue_style', (string) $handle );
}

function wp_dequeue_script( $handle ) {
	WpStubs::record( 'dequeue_script', (string) $handle );
}

function wp_deregister_script( $handle ) {
	WpStubs::record( 'deregister_script', (string) $handle );
}

function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	WpStubs::record( 'enqueue_style', (string) $handle );
}

function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {
	WpStubs::record( 'enqueue_script', (string) $handle );
}

function wp_localize_script( $handle, $object_name, $l10n ) {
	WpStubs::$registry[ 'l10n:' . $handle ] = $l10n;
	return true;
}

// Admin.

function remove_meta_box( $id, $screen, $context ) {
	WpStubs::record( 'meta_box', $id . '@' . $screen . ':' . $context );
}

function remove_menu_page( $slug ) {
	WpStubs::record( 'menu', (string) $slug );
	return false;
}

function remove_submenu_page( $parent, $slug ) {
	WpStubs::record( 'submenu', $parent . '>' . $slug );
	return false;
}

function unregister_widget( $widget ) {
	WpStubs::record( 'widget', (string) $widget );
}

function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
	WpStubs::$registry[ 'page:' . $menu_slug ] = array( $page_title, $capability, $callback );
	return 'settings_page_' . $menu_slug;
}

function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
	$nonce = $_REQUEST[ $query_arg ] ?? '';
	if ( 'nonce-' . $action !== $nonce ) {
		throw new WpNonceException( 'Bad nonce for ' . $action );
	}
	return 1;
}

function wp_create_nonce( $action = -1 ) {
	return 'nonce-' . $action;
}

function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
	$field = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '">';
	if ( $display ) {
		echo $field;
	}
	return $field;
}

function wp_nonce_url( $url, $action = -1, $name = '_wpnonce' ) {
	return add_query_arg( $name, wp_create_nonce( $action ), $url );
}

function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true ) {
	echo '<button type="submit" class="button button-' . esc_attr( (string) $type ) . '">' . esc_html( (string) ( $text ?? 'Save Changes' ) ) . '</button>';
}

function checked( $checked, $current = true, $display = true ) {
	$result = (string) $checked === (string) $current ? " checked='checked'" : '';
	if ( $display ) {
		echo $result;
	}
	return $result;
}

function selected( $selected, $current = true, $display = true ) {
	$result = (string) $selected === (string) $current ? " selected='selected'" : '';
	if ( $display ) {
		echo $result;
	}
	return $result;
}

function load_plugin_textdomain( $domain, $deprecated = false, $path = false ) {
	WpStubs::$registry[ 'textdomain:' . $domain ] = $path;
	return true;
}

// Multisite.

function is_multisite() {
	return ! empty( WpStubs::$registry['multisite'] );
}

function get_sites( $args = array() ) {
	return WpStubs::$registry['sites'] ?? array();
}

function switch_to_blog( $site_id ) {
	WpStubs::record( 'switch_to_blog', (string) $site_id );
	WpStubs::$options = WpStubs::$registry[ 'site_options:' . $site_id ] ?? array();
	WpStubs::$registry['current_site'] = (int) $site_id;
	return true;
}

function restore_current_blog() {
	$site = WpStubs::$registry['current_site'] ?? 0;
	WpStubs::$registry[ 'site_options:' . $site ] = WpStubs::$options;
	return true;
}
