<?php
/**
 * Plugin Name: SMTPing Email Verification for Groundhogg
 * Description: Adds an SMTPing email-verification action to Groundhogg flows.
 * Version: 0.2.1
 * Author: Community Contributors
 * License: GPL-2.0-or-later
 * Requires Plugins: groundhogg
 * Text Domain: groundhogg-smtping
 * Requires PHP: 7.4
 */

namespace SMTPing\Groundhogg;

use Groundhogg\Abilities\Schemas\Step_Type_Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GH_SMTPING_VERSION', '0.2.1' );
define( 'GH_SMTPING_FILE', __FILE__ );
define( 'GH_SMTPING_PATH', plugin_dir_path( __FILE__ ) );
define( 'GH_SMTPING_URL', plugin_dir_url( __FILE__ ) );

require_once GH_SMTPING_PATH . 'includes/class-api-client.php';
require_once GH_SMTPING_PATH . 'includes/class-result-tags.php';
require_once GH_SMTPING_PATH . 'includes/class-settings.php';

function bootstrap() {
	require_once GH_SMTPING_PATH . 'includes/class-verify-email-action.php';

	add_action( 'groundhogg/steps/init', function ( $manager ) {
		$manager->add_step( new Verify_Email_Action() );
	} );

	add_action( 'groundhogg/abilities/register_step_types', function () {
		if ( ! class_exists( Step_Type_Schema::class ) ) {
			return;
		}

		Step_Type_Schema::extend( 'smtping_verify_email', array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(),
		) );
	} );

	Settings::instance();
	Result_Tags::instance();
}

if ( did_action( 'groundhogg/loaded' ) ) {
	bootstrap();
} else {
	add_action( 'groundhogg/loaded', __NAMESPACE__ . '\\bootstrap' );
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'activate_plugins' ) || defined( 'GROUNDHOGG_VERSION' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Groundhogg - SMTPing requires Groundhogg to be installed and active.', 'groundhogg-smtping' ) . '</p></div>';
} );

