<?php

namespace SMTPing\Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_gh_smtping_save', array( $this, 'save' ) );
		add_action( 'admin_post_gh_smtping_test', array( $this, 'test' ) );
	}

	public function admin_menu() {
		add_options_page(
			__( 'Groundhogg SMTPing', 'groundhogg-smtping' ),
			__( 'Groundhogg SMTPing', 'groundhogg-smtping' ),
			'manage_options',
			'groundhogg-smtping',
			array( $this, 'render' )
		);
	}

	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'groundhogg-smtping' ) );
		}

		check_admin_referer( 'gh_smtping_save' );

		if ( ! defined( 'GH_SMTPING_API_KEY' ) && isset( $_POST['api_key'] ) && '' !== trim( wp_unslash( $_POST['api_key'] ) ) ) {
			update_option( 'gh_smtping_api_key', sanitize_text_field( wp_unslash( $_POST['api_key'] ) ), false );
		}

		$settings = array(
			'mark_avoid_unmarketable' => ! empty( $_POST['mark_avoid_unmarketable'] ),
		);
		update_option( 'gh_smtping_settings', $settings, false );
		Result_Tags::instance()->ensure();

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'options-general.php?page=groundhogg-smtping' ) ) );
		exit;
	}

	public function test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to test these settings.', 'groundhogg-smtping' ) );
		}

		check_admin_referer( 'gh_smtping_test' );
		$result = ( new API_Client() )->credits();

		if ( is_wp_error( $result ) ) {
			set_transient( 'gh_smtping_test_' . get_current_user_id(), array( 'ok' => false, 'message' => $result->get_error_message() ), 60 );
		} else {
			$message = sprintf(
				__( 'Connection successful. Remaining credits: %s.', 'groundhogg-smtping' ),
				number_format_i18n( isset( $result['remaining'] ) ? (int) $result['remaining'] : 0 )
			);
			set_transient( 'gh_smtping_test_' . get_current_user_id(), array( 'ok' => true, 'message' => $message ), 60 );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=groundhogg-smtping' ) );
		exit;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = wp_parse_args( get_option( 'gh_smtping_settings', array() ), array( 'mark_avoid_unmarketable' => true ) );
		$has_key = '' !== API_Client::get_api_key();
		$constant = defined( 'GH_SMTPING_API_KEY' ) && GH_SMTPING_API_KEY;
		$test = get_transient( 'gh_smtping_test_' . get_current_user_id() );
		delete_transient( 'gh_smtping_test_' . get_current_user_id() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Groundhogg SMTPing', 'groundhogg-smtping' ); ?></h1>
			<?php if ( $test ) : ?>
				<div class="notice <?php echo $test['ok'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( $test['message'] ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Adds “Verify with SMTPing” as a Groundhogg flow action. Each site uses its own API key.', 'groundhogg-smtping' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gh_smtping_save">
				<?php wp_nonce_field( 'gh_smtping_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gh-smtping-api-key"><?php esc_html_e( 'SMTPing API key', 'groundhogg-smtping' ); ?></label></th>
						<td>
							<input id="gh-smtping-api-key" name="api_key" type="password" class="regular-text" autocomplete="new-password" <?php disabled( $constant ); ?> placeholder="<?php echo $has_key ? esc_attr__( 'Configured — leave blank to keep', 'groundhogg-smtping' ) : ''; ?>">
							<p class="description"><?php echo $constant ? esc_html__( 'Configured with GH_SMTPING_API_KEY in wp-config.php.', 'groundhogg-smtping' ) : esc_html__( 'The saved key is never displayed again or sent to the browser.', 'groundhogg-smtping' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Avoid results', 'groundhogg-smtping' ); ?></th>
						<td><label><input type="checkbox" name="mark_avoid_unmarketable" value="1" <?php checked( $settings['mark_avoid_unmarketable'] ); ?>> <?php esc_html_e( 'Mark the contact as Spam/unmarketable', 'groundhogg-smtping' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save settings', 'groundhogg-smtping' ) ); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gh_smtping_test">
				<?php wp_nonce_field( 'gh_smtping_test' ); ?>
				<?php
				$button_attributes = $has_key ? array() : array( 'disabled' => 'disabled' );
				submit_button( __( 'Test connection', 'groundhogg-smtping' ), 'secondary', 'submit', false, $button_attributes );
				?>
			</form>
		</div>
		<?php
	}
}

