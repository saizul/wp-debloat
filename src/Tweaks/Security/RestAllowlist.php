<?php
/**
 * Tweak: REST API allowlist for logged-out visitors.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Refuses REST API requests from logged-out visitors unless the route starts
 * with an allowed namespace or route.
 */
final class RestAllowlist extends Tweak {

	public function id(): string {
		return 'rest_logged_out_allowlist';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Restrict the REST API for logged-out visitors', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Logged-out visitors get a 401 error from every REST API route except the ones listed below (one namespace or route per line, e.g. /contact-form-7/v1). Contact forms, shops, search and other front-end features that call the REST API break unless their route is listed. Test every form and front-end feature after switching it on.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::HIGH;
	}

	public function field(): ?Field {
		return Field::lines( array( '/oembed/1.0' ), '#^/?[A-Za-z0-9_\-./]+$#', 100 );
	}

	public function field_label(): string {
		return __( 'Allowed routes (one per line)', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'rest_pre_dispatch', array( $this, 'guard' ), 5, 3 );
	}

	/**
	 * Refuse routes that are not allowed.
	 *
	 * @param mixed  $result  Response so far.
	 * @param mixed  $server  Server.
	 * @param object $request WP_REST_Request.
	 * @return mixed
	 */
	public function guard( $result, $server, $request ) {
		if ( null !== $result || ! is_object( $request ) || ! method_exists( $request, 'get_route' ) || is_user_logged_in() ) {
			return $result;
		}
		if ( self::is_allowed( (string) $request->get_route(), (array) $this->value() ) ) {
			return $result;
		}

		return new \WP_Error(
			'rest_login_required',
			__( 'The REST API is only available to logged-in users on this site.', 'wp-debloat' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Whether a route matches an allowed prefix on a path segment boundary.
	 *
	 * @param string   $route   Route, e.g. /wp/v2/posts.
	 * @param string[] $allowed Allowed prefixes.
	 */
	public static function is_allowed( string $route, array $allowed ): bool {
		$route = '/' . trim( $route, '/' );
		foreach ( $allowed as $prefix ) {
			$prefix = '/' . trim( (string) $prefix, '/' );
			if ( '/' === $prefix ) {
				continue;
			}
			if ( 0 === strcasecmp( $route, $prefix ) || 0 === stripos( $route, $prefix . '/' ) ) {
				return true;
			}
		}

		return false;
	}
}
