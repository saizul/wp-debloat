<?php
/**
 * Tweak: hide the admin bar on the front end.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Admin;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Hides the toolbar on the front end.
 */
final class AdminBarFrontEnd extends Tweak {

	public function id(): string {
		return 'admin_bar_front_end';
	}

	public function group(): string {
		return Group::ADMIN;
	}

	public function title(): string {
		return __( 'Hide the admin bar on the front end', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Hides the toolbar on the front end for users who cannot manage options (subscribers, customers, authors), or for everyone. The admin is not changed.', 'wp-debloat' );
	}

	public function field(): ?Field {
		return Field::select( 'non_admins', array( 'non_admins', 'everyone' ) );
	}

	public function field_label(): string {
		return __( 'Hide for', 'wp-debloat' );
	}

	public function choice_label( string $choice ): string {
		return 'everyone' === $choice ? __( 'Everyone', 'wp-debloat' ) : __( 'Everyone except administrators', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'show_admin_bar', array( $this, 'show' ), 99 );
	}

	/**
	 * Whether to show the bar.
	 *
	 * @param mixed $show Current decision.
	 */
	public function show( $show ): bool {
		if ( 'everyone' === $this->value() ) {
			return false;
		}

		return (bool) $show && current_user_can( 'manage_options' );
	}
}
