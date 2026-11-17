<?php
/**
 * Tweak: block REST API user listing for logged-out visitors.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Answers /wp/v2/users requests from logged-out visitors with 401.
 */
final class RestUsers extends Tweak {

	public function id(): string {
		return 'rest_user_enumeration';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Block user enumeration through the REST API', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Logged-out visitors get a 401 error from /wp-json/wp/v2/users, which otherwise lists the user names (slugs) of everyone who published a post. Logged-in users and the block editor are not affected.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'rest_pre_dispatch', array( $this, 'guard' ), 10, 3 );
	}

	/**
	 * Refuse the users routes.
	 *
	 * @param mixed  $result  Response so far.
	 * @param mixed  $server  Server.
	 * @param object $request WP_REST_Request.
	 * @return mixed
	 */
	public function guard( $result, $server, $request ) {
		if ( null !== $result || ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
			return $result;
		}
		if ( ! self::is_users_route( (string) $request->get_route() ) || is_user_logged_in() ) {
			return $result;
		}

		return new \WP_Error(
			'rest_user_cannot_view',
			__( 'Sorry, you are not allowed to list users.', 'wp-debloat' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Whether a route belongs to the users endpoint.
	 *
	 * @param string $route Route.
	 */
	public static function is_users_route( string $route ): bool {
		return 1 === preg_match( '#^/wp/v2/users(?:/|$)#i', '/' . ltrim( $route, '/' ) );
	}
}
