<?php
/**
 * Tests for what the settings screen's Publishing section shows per capability.
 *
 * @package Terraviz
 */

declare( strict_types = 1 );

use Terraviz\Settings;
use Terraviz\Support\Capabilities;
use Terraviz\Support\Credential;

/**
 * @covers \Terraviz\Settings::register
 * @covers \Terraviz\Settings::render_publishing_locked
 */
class SettingsPublishingSectionTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// The Settings API keeps sections/fields in globals the test case does
		// not reset; start each render from an empty registry.
		global $wp_settings_sections, $wp_settings_fields;
		$wp_settings_sections = array();
		$wp_settings_fields   = array();
	}

	/**
	 * Register the screen and capture its settings sections as HTML.
	 */
	private function render_sections(): string {
		( new Settings() )->register();
		ob_start();
		do_settings_sections( 'terraviz-settings' );
		return (string) ob_get_clean();
	}

	/**
	 * A user who can open the screen (manage_options) but lacks manage_terraviz
	 * gets the notice — naming the capability and a WP-CLI grant for their own
	 * user id — in place of the credential fields.
	 */
	public function test_manage_options_without_manage_terraviz_sees_notice(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		get_userdata( $user_id )->add_cap( 'manage_options' );
		wp_set_current_user( $user_id );

		$html = $this->render_sections();

		$this->assertStringContainsString( '>Publishing</h2>', $html );
		$this->assertStringContainsString( '<code>manage_terraviz</code>', $html );
		$this->assertStringContainsString( 'wp user add-cap ' . $user_id . ' manage_terraviz', $html );
		$this->assertStringNotContainsString( 'Deactivate and reactivate', $html );
		$this->assertStringNotContainsString( Credential::OPTION . '[client_id]', $html );
	}

	/**
	 * An administrator missing the capability (e.g. the activation hook never
	 * ran) is told how to restore it, not to ask an administrator.
	 */
	public function test_administrator_missing_capability_is_told_to_reactivate(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		// A per-user false overrides whatever the administrator role holds.
		get_userdata( $user_id )->add_cap( Capabilities::MANAGE, false );
		wp_set_current_user( $user_id );

		$html = $this->render_sections();

		$this->assertStringContainsString( 'Deactivate and reactivate', $html );
		$this->assertStringNotContainsString( 'wp user add-cap', $html );
		$this->assertStringNotContainsString( Credential::OPTION . '[client_id]', $html );
	}

	/**
	 * A manage_terraviz holder gets the real credential fields and no notice.
	 */
	public function test_manage_terraviz_holder_sees_credential_fields_not_notice(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		get_userdata( $user_id )->add_cap( Capabilities::MANAGE );
		wp_set_current_user( $user_id );

		$html = $this->render_sections();

		$this->assertStringContainsString( Credential::OPTION . '[client_id]', $html );
		$this->assertStringNotContainsString( 'Your account does not have it', $html );
	}
}
