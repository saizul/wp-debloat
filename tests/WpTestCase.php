<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests;

use PHPUnit\Framework\TestCase;
use Saizul\Debloat\Plugin;
use Saizul\Debloat\Registry;
use Saizul\Debloat\Tweak\Tweak;
use WP_CLI;
use WpStubs;

/**
 * Base class: resets the WordPress and WP-CLI stubs before every test.
 */
abstract class WpTestCase extends TestCase {

	protected function setUp(): void {
		WpStubs::reset();
		WP_CLI::reset();
		Plugin::reset();
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * A fresh built-in tweak by ID, with an optional value.
	 *
	 * @param mixed $value Value.
	 */
	protected static function tweak( string $id, $value = null ): Tweak {
		$tweak = Registry::create()->get( $id );
		self::assertNotNull( $tweak, 'Unknown tweak ' . $id );
		$tweak->set_value( $value );

		return $tweak;
	}

	/**
	 * Whether a callback is hooked (at any priority).
	 *
	 * @param mixed $callback Callback.
	 */
	protected static function hooked( string $hook, $callback, ?int $priority = null ): bool {
		$found = has_filter( $hook, $callback );

		return null === $priority ? false !== $found : $priority === $found;
	}
}
