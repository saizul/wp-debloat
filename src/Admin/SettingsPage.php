<?php
/**
 * Settings > Debloat screen.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Admin;

use Saizul\Debloat\Plugin;
use Saizul\Debloat\Presets;
use Saizul\Debloat\Registry;
use Saizul\Debloat\Runner;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Transfer;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Admin screen with grouped toggles, presets and import/export.
 *
 * Every form posts to admin-post.php with its own nonce; each handler checks
 * the capability and the nonce before it changes anything.
 */
final class SettingsPage {

	const SLUG       = 'wp-debloat';
	const CAPABILITY = 'manage_options';
	const FORM_KEY   = 'wp_debloat';

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Screen hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_wp_debloat_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_wp_debloat_preset', array( $this, 'handle_preset' ) );
		add_action( 'admin_post_wp_debloat_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_wp_debloat_export', array( $this, 'handle_export' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin->file() ), array( $this, 'action_links' ) );
	}

	/**
	 * Add Settings > Debloat.
	 */
	public function add_page(): void {
		$this->hook_suffix = (string) add_options_page(
			__( 'Debloat & Harden', 'wp-debloat' ),
			__( 'Debloat', 'wp-debloat' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * URL of the screen.
	 *
	 * @param array<string,string> $args Extra query arguments.
	 */
	public static function url( array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'options-general.php' ) );
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param mixed $links Links.
	 * @return mixed
	 */
	public function action_links( $links ) {
		if ( ! is_array( $links ) || ! current_user_can( self::CAPABILITY ) ) {
			return $links;
		}
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'wp-debloat' ) . '</a>' );

		return $links;
	}

	/**
	 * Load the screen assets.
	 *
	 * @param string $hook_suffix Current screen.
	 */
	public function enqueue( $hook_suffix ): void {
		if ( '' === $this->hook_suffix || $hook_suffix !== $this->hook_suffix ) {
			return;
		}
		$base = plugins_url( 'assets/', $this->plugin->file() );
		wp_enqueue_style( 'wp-debloat-admin', $base . 'admin.css', array(), Plugin::VERSION );
		wp_enqueue_script( 'wp-debloat-admin', $base . 'admin.js', array(), Plugin::VERSION, true );
		wp_localize_script(
			'wp-debloat-admin',
			'wpDebloat',
			array(
				'confirmPreset' => __( 'Apply this preset? It replaces which tweaks are switched on. Values you entered are kept.', 'wp-debloat' ),
				'confirmHigh'   => __( 'This is a high-risk tweak. Test your site carefully after saving. Continue?', 'wp-debloat' ),
				/* translators: %d: number of matching tweaks. */
				'matches'       => __( '%d tweaks shown', 'wp-debloat' ),
			)
		);
	}

	/**
	 * Save the toggles and values.
	 */
	public function handle_save(): void {
		$this->authorize();
		check_admin_referer( 'wp_debloat_save' );
		$raw      = isset( $_POST[ self::FORM_KEY ] ) && is_array( $_POST[ self::FORM_KEY ] ) ? wp_unslash( $_POST[ self::FORM_KEY ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised by Settings::from_array() against the registry.
		$settings = $this->settings_from_form( $raw );
		$this->plugin->save_settings( $settings );
		$this->finish( self::url( array( 'debloat_notice' => 'saved' ) ) );
	}

	/**
	 * Build settings from the posted form.
	 *
	 * Values of tweaks that are not posted are kept, so values survive while a
	 * tweak is switched off.
	 *
	 * @param mixed $raw Posted `wp_debloat` array.
	 */
	public function settings_from_form( $raw ): Settings {
		$raw    = is_array( $raw ) ? $raw : array();
		$values = $this->plugin->settings()->values();
		if ( isset( $raw['values'] ) && is_array( $raw['values'] ) ) {
			$values = array_merge( $values, $raw['values'] );
		}

		return Settings::from_array(
			array(
				'enabled' => isset( $raw['enabled'] ) && is_array( $raw['enabled'] ) ? $raw['enabled'] : array(),
				'values'  => $values,
			),
			$this->plugin->registry()
		);
	}

	/**
	 * Apply a preset.
	 */
	public function handle_preset(): void {
		$this->authorize();
		check_admin_referer( 'wp_debloat_preset' );
		$preset = isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '';
		if ( ! Presets::exists( $preset ) ) {
			wp_die( esc_html__( 'Unknown preset.', 'wp-debloat' ), '', array( 'response' => 400 ) );
		}
		$settings = Presets::apply( $preset, $this->plugin->settings(), $this->plugin->registry() );
		$this->plugin->save_settings( $settings );
		$this->finish(
			self::url(
				array(
					'debloat_notice' => 'preset',
					'preset'         => $preset,
				)
			)
		);
	}

	/**
	 * Import settings from pasted JSON or an uploaded file.
	 */
	public function handle_import(): void {
		$this->authorize();
		check_admin_referer( 'wp_debloat_import' );
		$json = isset( $_POST['debloat_import'] ) ? (string) wp_unslash( $_POST['debloat_import'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, validated by Transfer::import().
		if ( '' === trim( $json ) && isset( $_FILES['debloat_import_file']['tmp_name'] ) && is_uploaded_file( $_FILES['debloat_import_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Path checked by is_uploaded_file().
			$size = isset( $_FILES['debloat_import_file']['size'] ) ? (int) $_FILES['debloat_import_file']['size'] : 0;
			if ( $size > 0 && $size <= Transfer::MAX_SIZE ) {
				$json = (string) file_get_contents( $_FILES['debloat_import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Local uploaded temp file.
			} else {
				$this->finish(
					self::url(
						array(
							'debloat_notice' => 'import_error',
							'code'           => 'too_large',
						)
					)
				);
				return;
			}
		}
		$args = $this->import_json( $json );
		$this->finish( self::url( $args ) );
	}

	/**
	 * Import a JSON document and store it.
	 *
	 * @param string $json JSON text.
	 * @return array<string,string> Query arguments for the notice.
	 */
	public function import_json( string $json ): array {
		$transfer = new Transfer( $this->plugin->registry() );
		try {
			$settings = $transfer->import( $json );
		} catch ( \InvalidArgumentException $error ) {
			return array(
				'debloat_notice' => 'import_error',
				'code'           => sanitize_key( $error->getMessage() ),
			);
		}
		$this->plugin->save_settings( $settings );

		return array(
			'debloat_notice' => 'imported',
			'skipped'        => (string) count( $transfer->unknown() ),
		);
	}

	/**
	 * Download the settings as JSON.
	 */
	public function handle_export(): void {
		$this->authorize();
		check_admin_referer( 'wp_debloat_export' );
		$host     = sanitize_key( str_replace( '.', '-', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
		$filename = 'wp-debloat-' . ( '' !== $host ? $host . '-' : '' ) . gmdate( 'Y-m-d' ) . '.json';
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $this->export_json(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, not HTML.
		exit;
	}

	/**
	 * Current settings as JSON.
	 */
	public function export_json(): string {
		return ( new Transfer( $this->plugin->registry() ) )->export( $this->plugin->settings(), Plugin::VERSION );
	}

	/**
	 * Stop unless the user may manage the settings. Every handler checks its
	 * nonce right after this call.
	 */
	private function authorize(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'wp-debloat' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Redirect back to the screen.
	 *
	 * @param string $url URL.
	 */
	private function finish( string $url ): void {
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the screen.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$registry = $this->plugin->registry();
		$settings = $this->plugin->settings();
		$enabled  = count( $settings->enabled_ids() );
		?>
		<div class="wrap wp-debloat">
			<h1><?php esc_html_e( 'Debloat & Harden', 'wp-debloat' ); ?></h1>
			<p class="wp-debloat-intro">
				<?php
				printf(
					/* translators: 1: number of enabled tweaks, 2: number of tweaks. */
					esc_html__( '%1$d of %2$d tweaks are on. Every tweak is off until you switch it on. Changes take effect on the next page load.', 'wp-debloat' ),
					(int) $enabled,
					(int) $registry->count()
				);
				?>
			</p>
			<?php
			$this->render_notices( $registry );
			$this->render_presets();
			$this->render_form( $registry, $settings );
			$this->render_transfer();
			?>
		</div>
		<?php
	}

	/**
	 * Notices: result of the last action, safe mode and problems.
	 *
	 * @param Registry $registry Registry.
	 */
	private function render_notices( Registry $registry ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display of a redirect result only.
		$notice = isset( $_GET['debloat_notice'] ) ? sanitize_key( wp_unslash( $_GET['debloat_notice'] ) ) : '';
		switch ( $notice ) {
			case 'saved':
				$this->notice( 'success', __( 'Settings saved.', 'wp-debloat' ) );
				break;
			case 'preset':
				$preset = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : '';
				if ( Presets::exists( $preset ) ) {
					/* translators: %s: preset name. */
					$this->notice( 'success', sprintf( __( 'Preset "%s" applied.', 'wp-debloat' ), Presets::label( $preset ) ) );
				}
				break;
			case 'imported':
				$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
				$message = __( 'Settings imported.', 'wp-debloat' );
				if ( $skipped > 0 ) {
					/* translators: %d: number of unknown tweak IDs. */
					$message .= ' ' . sprintf( _n( '%d unknown tweak was skipped.', '%d unknown tweaks were skipped.', $skipped, 'wp-debloat' ), $skipped );
				}
				$this->notice( 'success', $message );
				break;
			case 'import_error':
				$code = isset( $_GET['code'] ) ? sanitize_key( wp_unslash( $_GET['code'] ) ) : '';
				$this->notice( 'error', Transfer::error_message( $code ) );
				break;
		}
		// phpcs:enable

		if ( Runner::safe_mode() ) {
			$this->notice( 'warning', __( 'Safe mode: WP_DEBLOAT_DISABLE is set in wp-config.php, so no tweak is applied. You can still change the settings.', 'wp-debloat' ) );
		}
		foreach ( $this->plugin->runner()->errors() as $id => $message ) {
			/* translators: 1: tweak ID, 2: error message. */
			$this->notice( 'error', sprintf( __( 'Tweak %1$s failed and was not applied: %2$s', 'wp-debloat' ), $id, $message ) );
		}
		foreach ( $registry->rejected() as $message ) {
			/* translators: %s: problem description. */
			$this->notice( 'warning', sprintf( __( 'A tweak added by another plugin was ignored: %s', 'wp-debloat' ), $message ) );
		}
	}

	/**
	 * Print an admin notice.
	 *
	 * @param string $type    success, warning or error.
	 * @param string $message Plain text.
	 */
	private function notice( string $type, string $message ): void {
		printf( '<div class="notice notice-%1$s"><p>%2$s</p></div>', esc_attr( $type ), esc_html( $message ) );
	}

	/**
	 * Preset buttons.
	 */
	private function render_presets(): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wp-debloat-presets">
			<input type="hidden" name="action" value="wp_debloat_preset">
			<?php wp_nonce_field( 'wp_debloat_preset' ); ?>
			<h2><?php esc_html_e( 'Presets', 'wp-debloat' ); ?></h2>
			<div class="wp-debloat-preset-list">
				<?php foreach ( Presets::all() as $preset ) : ?>
					<div class="wp-debloat-preset">
						<button type="submit" name="preset" value="<?php echo esc_attr( $preset ); ?>" class="button<?php echo Presets::RECOMMENDED === $preset ? ' button-primary' : ''; ?>" data-debloat-confirm="preset">
							<?php echo esc_html( Presets::label( $preset ) ); ?>
						</button>
						<span class="description"><?php echo esc_html( Presets::description( $preset ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Toggle form.
	 *
	 * @param Registry $registry Registry.
	 * @param Settings $settings Settings.
	 */
	private function render_form( Registry $registry, Settings $settings ): void {
		?>
		<div class="wp-debloat-toolbar">
			<label class="screen-reader-text" for="wp-debloat-search"><?php esc_html_e( 'Search tweaks', 'wp-debloat' ); ?></label>
			<input type="search" id="wp-debloat-search" class="regular-text" placeholder="<?php esc_attr_e( 'Search tweaks…', 'wp-debloat' ); ?>" autocomplete="off">
			<label><input type="checkbox" id="wp-debloat-only-enabled"> <?php esc_html_e( 'Show only enabled', 'wp-debloat' ); ?></label>
			<span class="wp-debloat-count" aria-live="polite"></span>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wp-debloat-form">
			<input type="hidden" name="action" value="wp_debloat_save">
			<?php wp_nonce_field( 'wp_debloat_save' ); ?>
			<?php foreach ( $registry->by_group() as $group => $tweaks ) : ?>
				<section class="wp-debloat-group" data-group="<?php echo esc_attr( $group ); ?>">
					<h2><?php echo esc_html( Group::label( $group ) ); ?></h2>
					<ul class="wp-debloat-tweaks">
						<?php
						foreach ( $tweaks as $tweak ) {
							$this->render_tweak( $tweak, $settings );
						}
						?>
					</ul>
				</section>
			<?php endforeach; ?>
			<p class="wp-debloat-empty" hidden><?php esc_html_e( 'No tweaks match your search.', 'wp-debloat' ); ?></p>
			<?php submit_button( __( 'Save changes', 'wp-debloat' ) ); ?>
		</form>
		<?php
	}

	/**
	 * One tweak row.
	 *
	 * @param Tweak    $tweak    Tweak.
	 * @param Settings $settings Settings.
	 */
	private function render_tweak( Tweak $tweak, Settings $settings ): void {
		$id      = $tweak->id();
		$on      = $settings->is_enabled( $id );
		$risk    = $tweak->risk();
		$input   = 'wp-debloat-tweak-' . str_replace( '_', '-', $id );
		$search  = strtolower( $id . ' ' . $tweak->title() . ' ' . $tweak->description() );
		$notice  = $on ? $tweak->notice() : '';
		$presets = array_map( array( Presets::class, 'label' ), $tweak->presets() );
		?>
		<li class="wp-debloat-tweak<?php echo $on ? ' is-on' : ''; ?>" data-search="<?php echo esc_attr( $search ); ?>" data-risk="<?php echo esc_attr( $risk ); ?>">
			<div class="wp-debloat-toggle">
				<input type="checkbox" class="wp-debloat-switch" id="<?php echo esc_attr( $input ); ?>" name="<?php echo esc_attr( self::FORM_KEY . '[enabled][' . $id . ']' ); ?>" value="1" <?php checked( $on ); ?> aria-describedby="<?php echo esc_attr( $input . '-desc' ); ?>">
			</div>
			<div class="wp-debloat-body">
				<label for="<?php echo esc_attr( $input ); ?>" class="wp-debloat-title"><?php echo esc_html( $tweak->title() ); ?></label>
				<span class="wp-debloat-risk wp-debloat-risk-<?php echo esc_attr( $risk ); ?>"><?php echo esc_html( Risk::label( $risk ) ); ?></span>
				<code class="wp-debloat-id"><?php echo esc_html( $id ); ?></code>
				<p class="description" id="<?php echo esc_attr( $input . '-desc' ); ?>"><?php echo esc_html( $tweak->description() ); ?></p>
				<?php $this->render_field( $tweak, $settings, $input ); ?>
				<?php if ( array() !== $presets ) : ?>
					<p class="wp-debloat-presets-of">
						<?php
						/* translators: %s: comma-separated preset names. */
						echo esc_html( sprintf( __( 'In presets: %s', 'wp-debloat' ), implode( ', ', $presets ) ) );
						?>
					</p>
				<?php endif; ?>
				<?php if ( '' !== $notice ) : ?>
					<p class="wp-debloat-notice"><?php echo esc_html( $notice ); ?></p>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}

	/**
	 * Value input of a tweak.
	 *
	 * @param Tweak    $tweak    Tweak.
	 * @param Settings $settings Settings.
	 * @param string   $input    Base element ID.
	 */
	private function render_field( Tweak $tweak, Settings $settings, string $input ): void {
		$field = $tweak->field();
		if ( ! $field ) {
			return;
		}
		$stored = $settings->value( $tweak->id() );
		$value  = null === $stored ? $field->default_value() : $stored;
		$name   = self::FORM_KEY . '[values][' . $tweak->id() . ']';
		$id     = $input . '-value';
		echo '<p class="wp-debloat-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $tweak->field_label() ) . '</label> ';
		switch ( $field->type() ) {
			case Field::TYPE_INT:
				printf(
					'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$d" min="%4$d" max="%5$d" step="1"> %6$s',
					esc_attr( $id ),
					esc_attr( $name ),
					(int) $value,
					(int) $field->option( 'min' ),
					(int) $field->option( 'max' ),
					esc_html( (string) $field->option( 'unit', '' ) )
				);
				break;
			case Field::TYPE_SELECT:
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $field->choices() as $choice ) {
					printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $choice ), selected( $value, $choice, false ), esc_html( $tweak->choice_label( $choice ) ) );
				}
				echo '</select>';
				break;
			case Field::TYPE_LINES:
				printf(
					'<br><textarea class="large-text code" rows="4" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( implode( "\n", (array) $value ) )
				);
				break;
		}
		echo '</p>';
	}

	/**
	 * Import and export.
	 */
	private function render_transfer(): void {
		$export = wp_nonce_url( add_query_arg( 'action', 'wp_debloat_export', admin_url( 'admin-post.php' ) ), 'wp_debloat_export' );
		?>
		<div class="wp-debloat-transfer">
			<h2><?php esc_html_e( 'Import and export', 'wp-debloat' ); ?></h2>
			<p><?php esc_html_e( 'Copy your settings to another site: export a JSON file here and import it there. Tweaks that the other site does not have are skipped.', 'wp-debloat' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'Export settings', 'wp-debloat' ); ?></a></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="wp_debloat_import">
				<?php wp_nonce_field( 'wp_debloat_import' ); ?>
				<p>
					<label for="wp-debloat-import-file"><?php esc_html_e( 'JSON file', 'wp-debloat' ); ?></label>
					<input type="file" id="wp-debloat-import-file" name="debloat_import_file" accept="application/json,.json">
				</p>
				<p>
					<label for="wp-debloat-import"><?php esc_html_e( 'Or paste the JSON', 'wp-debloat' ); ?></label><br>
					<textarea id="wp-debloat-import" name="debloat_import" class="large-text code" rows="6"></textarea>
				</p>
				<?php submit_button( __( 'Import settings', 'wp-debloat' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
