<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit\Fixtures;

use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Tweak;

/**
 * Configurable tweak for registry, runner and settings tests.
 */
final class CustomTweak extends Tweak {

	/** @var string */
	public $tweak_id;
	/** @var string */
	public $tweak_group;
	/** @var string */
	public $tweak_risk;
	/** @var Field|null */
	public $tweak_field;
	/** @var bool */
	public $throws = false;
	/** @var int */
	public $applied = 0;

	public function __construct( string $id = 'custom_tweak', string $group = 'woocommerce', string $risk = 'low', ?Field $field = null ) {
		$this->tweak_id    = $id;
		$this->tweak_group = $group;
		$this->tweak_risk  = $risk;
		$this->tweak_field = $field;
	}

	public function id(): string {
		return $this->tweak_id;
	}

	public function group(): string {
		return $this->tweak_group;
	}

	public function title(): string {
		return 'Custom tweak';
	}

	public function description(): string {
		return 'Added by a test.';
	}

	public function risk(): string {
		return $this->tweak_risk;
	}

	public function field(): ?Field {
		return $this->tweak_field;
	}

	public function apply(): void {
		if ( $this->throws ) {
			throw new \RuntimeException( 'Boom' );
		}
		$this->applied++;
	}
}
