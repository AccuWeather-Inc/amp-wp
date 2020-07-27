<?php
/**
 * Tests for AMP_Options_Manager.
 *
 * @package AMP
 */

use AmpProject\AmpWP\Option;
use AmpProject\AmpWP\Tests\Helpers\AssertContainsCompatibility;

/**
 * Tests for AMP_Options_Manager.
 *
 * @covers AMP_Options_Manager
 */
class Test_AMP_Options_Manager extends WP_UnitTestCase {

	use AssertContainsCompatibility;

	/**
	 * Whether the external object cache was enabled.
	 *
	 * @var bool
	 */
	private $was_wp_using_ext_object_cache;

	private $original_theme_directories;

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		$this->was_wp_using_ext_object_cache = $GLOBALS['_wp_using_ext_object_cache'];
		delete_option( AMP_Options_Manager::OPTION_NAME ); // Make sure default reader mode option does not override theme support being added.
		remove_theme_support( 'amp' );
		$GLOBALS['wp_settings_errors'] = [];

		global $wp_theme_directories;
		$this->original_theme_directories = $wp_theme_directories;
		register_theme_directory( ABSPATH . 'wp-content/themes' );
		delete_site_transient( 'theme_roots' );
	}

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	public function tearDown() {
		parent::tearDown();
		$GLOBALS['_wp_using_ext_object_cache'] = $this->was_wp_using_ext_object_cache;
		unregister_post_type( 'foo' );
		unregister_post_type( 'book' );

		foreach ( get_post_types() as $post_type ) {
			remove_post_type_support( $post_type, 'amp' );
		}

		global $wp_theme_directories;
		$wp_theme_directories = $this->original_theme_directories;
		delete_site_transient( 'theme_roots' );
	}

	/**
	 * Test constants.
	 */
	public function test_constants() {
		$this->assertEquals( 'amp-options', AMP_Options_Manager::OPTION_NAME );
	}

	/**
	 * Tests the init method.
	 *
	 * @covers AMP_Options_Manager::init()
	 */
	public function test_init() {
		AMP_Options_Manager::init();
		$this->assertEquals( 10, has_action( 'admin_notices', [ AMP_Options_Manager::class, 'render_php_css_parser_conflict_notice' ] ) );
		$this->assertEquals( 10, has_action( 'admin_notices', [ AMP_Options_Manager::class, 'insecure_connection_notice' ] ) );
	}

	/**
	 * Test register_settings.
	 *
	 * @covers AMP_Options_Manager::register_settings()
	 */
	public function test_register_settings() {
		AMP_Options_Manager::register_settings();
		AMP_Options_Manager::init();
		$registered_settings = get_registered_settings();
		$this->assertArrayHasKey( AMP_Options_Manager::OPTION_NAME, $registered_settings );
		$this->assertEquals( 'array', $registered_settings[ AMP_Options_Manager::OPTION_NAME ]['type'] );
		$this->assertEquals( 10, has_action( 'update_option_' . AMP_Options_Manager::OPTION_NAME, [ 'AMP_Options_Manager', 'maybe_flush_rewrite_rules' ] ) );
	}

	/**
	 * Test maybe_flush_rewrite_rules.
	 *
	 * @covers AMP_Options_Manager::maybe_flush_rewrite_rules()
	 */
	public function test_maybe_flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->init();
		AMP_Options_Manager::register_settings();
		$dummy_rewrite_rules = [ 'previous' => true ];

		// Check change to supported_post_types.
		update_option( 'rewrite_rules', $dummy_rewrite_rules );
		AMP_Options_Manager::maybe_flush_rewrite_rules(
			[ Option::SUPPORTED_POST_TYPES => [ 'page' ] ],
			[]
		);
		$this->assertEmpty( get_option( 'rewrite_rules' ) );

		// Check update of supported_post_types but no change.
		update_option( 'rewrite_rules', $dummy_rewrite_rules );
		update_option(
			AMP_Options_Manager::OPTION_NAME,
			[
				[ Option::SUPPORTED_POST_TYPES => [ 'page' ] ],
				[ Option::SUPPORTED_POST_TYPES => [ 'page' ] ],
			]
		);
		$this->assertEquals( $dummy_rewrite_rules, get_option( 'rewrite_rules' ) );

		// Check changing a different property.
		update_option( 'rewrite_rules', [ 'previous' => true ] );
		update_option(
			AMP_Options_Manager::OPTION_NAME,
			[
				[ 'foo' => 'new' ],
				[ 'foo' => 'old' ],
			]
		);
		$this->assertEquals( $dummy_rewrite_rules, get_option( 'rewrite_rules' ) );
	}

	/**
	 * Test get_options.
	 *
	 * @covers AMP_Options_Manager::get_options()
	 * @covers AMP_Options_Manager::get_option()
	 * @covers AMP_Options_Manager::update_option()
	 * @covers AMP_Options_Manager::validate_options()
	 */
	public function test_get_and_set_options() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		global $wp_settings_errors;
		wp_using_ext_object_cache( true ); // turn on external object cache flag.
		AMP_Options_Manager::register_settings(); // Adds validate_options as filter.
		delete_option( AMP_Options_Manager::OPTION_NAME );
		$this->assertEquals(
			[
				Option::THEME_SUPPORT           => AMP_Theme_Support::READER_MODE_SLUG,
				Option::SUPPORTED_POST_TYPES    => [ 'post', 'page' ],
				Option::ANALYTICS               => [],
				Option::ALL_TEMPLATES_SUPPORTED => true,
				Option::SUPPORTED_TEMPLATES     => [ 'is_singular' ],
				Option::SUPPRESSED_PLUGINS      => [],
				Option::VERSION                 => AMP__VERSION,
				Option::MOBILE_REDIRECT         => false,
				Option::READER_THEME            => 'legacy',
				Option::PLUGIN_CONFIGURED       => false,
			],
			AMP_Options_Manager::get_options()
		);
		$this->assertSame( false, AMP_Options_Manager::get_option( 'foo' ) );
		$this->assertSame( 'default', AMP_Options_Manager::get_option( 'foo', 'default' ) );

		// Test supported_post_types validation.
		AMP_Options_Manager::update_option(
			Option::SUPPORTED_POST_TYPES,
			[ 'post' ]
		);
		$this->assertSame(
			[ 'post' ],
			AMP_Options_Manager::get_option( Option::SUPPORTED_POST_TYPES )
		);

		// Test supported_templates validation.
		AMP_Options_Manager::update_option(
			Option::SUPPORTED_TEMPLATES,
			[
				'is_search',
				'is_category',
			]
		);
		$this->assertSame(
			[
				'is_search',
				'is_category',
			],
			AMP_Options_Manager::get_option( Option::SUPPORTED_TEMPLATES )
		);

		// Test analytics validation with missing fields.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				'bad' => [],
			]
		);
		$errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$this->assertEquals( 'missing_analytics_vendor_or_config', $errors[0]['code'] );
		$wp_settings_errors = [];

		// Test analytics validation with bad JSON.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				'__new__' => [
					'type'   => 'foo',
					'config' => 'BAD',
				],
			]
		);
		$errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$this->assertEquals( 'invalid_analytics_config_json', $errors[0]['code'] );
		$wp_settings_errors = [];

		// Test analytics validation with good fields.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				'__new__' => [
					'type'   => 'foo',
					'config' => '{"good":true}',
				],
			]
		);
		$this->assertEmpty( get_settings_errors( AMP_Options_Manager::OPTION_NAME ) );

		// Test analytics validation with duplicate check.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				'__new__' => [
					'type'   => 'foo',
					'config' => '{"good":true}',
				],
			]
		);
		$errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$this->assertEquals( 'duplicate_analytics_entry', $errors[0]['code'] );
		$wp_settings_errors = [];

		// Confirm format of entry ID.
		$entries = AMP_Options_Manager::get_option( Option::ANALYTICS );
		$entry   = current( $entries );
		$id      = substr( md5( $entry['type'] . $entry['config'] ), 0, 12 );
		$this->assertArrayHasKey( $id, $entries );
		$this->assertEquals( 'foo', $entries[ $id ]['type'] );
		$this->assertEquals( '{"good":true}', $entries[ $id ]['config'] );

		// Confirm adding another entry works.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				'__new__' => [
					'type'   => 'bar',
					'config' => '{"good":true}',
				],
			]
		);
		$entries = AMP_Options_Manager::get_option( Option::ANALYTICS );
		$this->assertCount( 2, AMP_Options_Manager::get_option( Option::ANALYTICS ) );
		$this->assertArrayHasKey( $id, $entries );

		// Confirm updating an entry works.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				$id => [
					'id'     => $id,
					'type'   => 'foo',
					'config' => '{"very_good":true}',
				],
			]
		);
		$entries = AMP_Options_Manager::get_option( Option::ANALYTICS );
		$this->assertEquals( 'foo', $entries[ $id ]['type'] );
		$this->assertEquals( '{"very_good":true}', $entries[ $id ]['config'] );

		// Confirm deleting an entry works.
		AMP_Options_Manager::update_option(
			Option::ANALYTICS,
			[
				$id => [
					'id'     => $id,
					'type'   => 'foo',
					'config' => '{"very_good":true}',
					'delete' => true,
				],
			]
		);
		$entries = AMP_Options_Manager::get_option( Option::ANALYTICS );
		$this->assertCount( 1, $entries );
		$this->assertArrayNotHasKey( $id, $entries );
	}

	/**
	 * Test get_options for toggling the default value of plugin_configured.
	 *
	 * @covers AMP_Options_Manager::get_option()
	 * @covers AMP_Options_Manager::get_options()
	 */
	public function test_get_options_changing_plugin_configured_default() {
		// Ensure plugin_configured is false when existing option is absent.
		delete_option( AMP_Options_Manager::OPTION_NAME );
		$this->assertFalse( AMP_Options_Manager::get_option( Option::PLUGIN_CONFIGURED ) );

		// Ensure plugin_configured is true when existing option is absent from an old version.
		update_option( AMP_Options_Manager::OPTION_NAME, [ Option::VERSION => '1.5.2' ] );
		$this->assertTrue( AMP_Options_Manager::get_option( Option::PLUGIN_CONFIGURED ) );

		// Ensure plugin_configured is true when explicitly set as such in the DB.
		update_option(
			AMP_Options_Manager::OPTION_NAME,
			[
				Option::VERSION           => AMP__VERSION,
				Option::PLUGIN_CONFIGURED => false,
			]
		);
		$this->assertFalse( AMP_Options_Manager::get_option( Option::PLUGIN_CONFIGURED ) );

		// Ensure plugin_configured is false when explicitly set as such in the DB.
		update_option(
			AMP_Options_Manager::OPTION_NAME,
			[
				Option::VERSION           => AMP__VERSION,
				Option::PLUGIN_CONFIGURED => true,
			]
		);
		$this->assertTrue( AMP_Options_Manager::get_option( Option::PLUGIN_CONFIGURED ) );
	}

	/** @return array */
	public function get_data_for_testing_get_options_default_template_mode() {
		return [
			'core_theme'    => [
				'twentytwenty',
				AMP_Theme_Support::TRANSITIONAL_MODE_SLUG,
				null,
			],
			'child_of_core' => [
				'child-of-core',
				AMP_Theme_Support::READER_MODE_SLUG,
				null,
			],
			'custom_theme'  => [
				'twentytwenty',
				AMP_Theme_Support::TRANSITIONAL_MODE_SLUG,
				[],
			],
		];
	}

	/**
	 * Test the expected default mode when various themes are active.
	 *
	 * @dataProvider get_data_for_testing_get_options_default_template_mode
	 *
	 * @covers AMP_Options_Manager::get_options()
	 * @param string     $theme               Theme.
	 * @param string     $expected_mode       Expected mode.
	 * @param null|array $added_theme_support Added theme support (or not if null).
	 */
	public function test_get_options_default_template_mode( $theme, $expected_mode, $added_theme_support ) {
		$theme_dir = basename( dirname( AMP__DIR__ ) ) . '/' . basename( AMP__DIR__ ) . '/tests/php/data/themes';
		register_theme_directory( $theme_dir );

		delete_option( AMP_Options_Manager::OPTION_NAME );
		remove_theme_support( 'amp' );
		switch_theme( $theme );
		if ( is_array( $added_theme_support ) ) {
			add_theme_support( 'amp', $added_theme_support );
		}
		AMP_Core_Theme_Sanitizer::extend_theme_support();
		$this->assertEquals( $expected_mode, AMP_Options_Manager::get_option( Option::THEME_SUPPORT ) );
	}

	/**
	 * Test get_options when supported_post_types option is list of post types and when post type support is added for default values.
	 *
	 * @covers AMP_Options_Manager::get_options()
	 */
	public function test_get_options_migration_supported_post_types_defaults() {
		foreach ( get_post_types() as $post_type ) {
			remove_post_type_support( $post_type, 'amp' );
		}

		register_post_type(
			'book',
			[
				'public'   => true,
				'supports' => [ 'amp' ],
			]
		);

		// Make sure the post type support get migrated.
		delete_option( AMP_Options_Manager::OPTION_NAME );
		$this->assertEquals(
			[
				'post', // Enabled by default.
				'page', // Enabled by default.
				'book',
			],
			AMP_Options_Manager::get_option( Option::SUPPORTED_POST_TYPES )
		);
	}

	/**
	 * Test get_options when all_templates_supported theme support is used.
	 *
	 * @covers AMP_Options_Manager::get_options()
	 */
	public function test_get_options_migration_all_templates_supported_defaults() {
		delete_option( AMP_Options_Manager::OPTION_NAME );
		add_theme_support( 'amp', [ 'templates_supported' => 'all' ] );
		$this->assertTrue( AMP_Options_Manager::get_option( Option::ALL_TEMPLATES_SUPPORTED ) );

		delete_option( AMP_Options_Manager::OPTION_NAME );
		add_theme_support(
			'amp',
			[
				'templates_supported' => [
					'is_search'  => true,
					'is_archive' => false,
				],
			]
		);
		$this->assertFalse( AMP_Options_Manager::get_option( Option::ALL_TEMPLATES_SUPPORTED ) );
		$this->assertEquals(
			[
				'is_singular',
				'is_search',
			],
			AMP_Options_Manager::get_option( Option::SUPPORTED_TEMPLATES )
		);
	}

	/**
	 * Test that get_options() will migrate options properly when there is theme support and post type support flags.
	 *
	 * @covers AMP_Options_Manager::get_options()
	 */
	public function test_get_options_migration_from_old_version_selective_templates_forced() {
		$options = [
			'theme_support'           => 'transitional',
			'supported_post_types'    => [
				'post',
			],
			'analytics'               => [],
			'all_templates_supported' => false,
			'supported_templates'     => [
				'is_singular',
				'is_404',
				'is_category',
			],
			'version'                 => '1.5.5',
		];
		update_option( AMP_Options_Manager::OPTION_NAME, $options );

		$this->assertEquals( $options, get_option( AMP_Options_Manager::OPTION_NAME ) );

		add_post_type_support( 'page', 'amp' );
		add_theme_support(
			'amp',
			[
				'templates_supported' => [
					'is_singular' => true,
					'is_404'      => false,
					'is_date'     => true,
				],
			]
		);
		$migrated_options = AMP_Options_Manager::get_options();

		$this->assertFalse( $migrated_options[ Option::ALL_TEMPLATES_SUPPORTED ] );
		$this->assertEqualSets(
			[
				'is_singular',
				'is_date',
				'is_category',
			],
			array_unique( $migrated_options[ Option::SUPPORTED_TEMPLATES ] )
		);
		$this->assertEquals(
			[
				'post',
				'page',
			],
			$migrated_options[ Option::SUPPORTED_POST_TYPES ]
		);

		// Now verify that the templates_supported=>all theme support flag is also migrated.
		update_option( AMP_Options_Manager::OPTION_NAME, $options );
		add_theme_support(
			'amp',
			[ 'templates_supported' => 'all' ]
		);
		$migrated_options = AMP_Options_Manager::get_options();
		$this->assertTrue( $migrated_options[ Option::ALL_TEMPLATES_SUPPORTED ] );
		$this->assertEqualSets(
			[
				'post',
				'page',
				'attachment',
			],
			array_unique( $migrated_options[ Option::SUPPORTED_POST_TYPES ] )
		);
	}

	/**
	 * Test get_options when supported_templates option is list of templates and when theme support is used.
	 *
	 * @covers AMP_Options_Manager::get_options()
	 */
	public function test_get_options_migration_supported_templates() {
		// Make sure the theme support get migrated to DB option.
		delete_option( AMP_Options_Manager::OPTION_NAME );
		add_theme_support(
			'amp',
			[
				'templates_supported' => [
					'is_archive'  => true,
					'is_search'   => false,
					'is_404'      => false,
					'is_singular' => true,
				],
			]
		);
		$this->assertEqualSets(
			[
				'is_archive',
				'is_singular',
			],
			array_unique( AMP_Options_Manager::get_option( Option::SUPPORTED_TEMPLATES ) )
		);
	}

	/**
	 * Test get_options when active theme is switched to be the same as the Reader theme.
	 *
	 * @covers AMP_Options_Manager::get_options()
	 * @covers AMP_Options_Manager::get_option()
	 * @covers AMP_Options_Manager::update_option()
	 */
	public function test_get_options_when_reader_theme_same_as_active_theme() {
		if ( ! wp_get_theme( 'twentytwenty' ) ) {
			$this->markTestSkipped();
		}
		if ( ! wp_get_theme( 'twentynineteen' ) ) {
			$this->markTestSkipped();
		}
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		switch_theme( 'twentytwenty' );
		AMP_Options_Manager::update_options(
			[
				Option::THEME_SUPPORT => AMP_Theme_Support::READER_MODE_SLUG,
				Option::READER_THEME  => 'twentynineteen',
			]
		);
		$this->assertEquals( AMP_Theme_Support::READER_MODE_SLUG, AMP_Options_Manager::get_option( Option::THEME_SUPPORT ) );
		$this->assertEquals( 'twentynineteen', AMP_Options_Manager::get_option( Option::READER_THEME ) );

		switch_theme( 'twentynineteen' );
		$this->assertEquals( AMP_Theme_Support::TRANSITIONAL_MODE_SLUG, AMP_Options_Manager::get_option( Option::THEME_SUPPORT ) );

		switch_theme( 'twentytwenty' );
		$this->assertEquals( AMP_Theme_Support::READER_MODE_SLUG, AMP_Options_Manager::get_option( Option::THEME_SUPPORT ) );
		$this->assertEquals( 'twentynineteen', AMP_Options_Manager::get_option( Option::READER_THEME ) );
	}

	/**
	 * Tests the update_options method.
	 *
	 * @covers AMP_Options_Manager::update_options
	 */
	public function test_update_options() {
		// Confirm updating multiple entries at once works.
		AMP_Options_Manager::update_options(
			[
				Option::THEME_SUPPORT => 'reader',
				Option::READER_THEME  => 'twentysixteen',
			]
		);

		$this->assertEquals( 'reader', AMP_Options_Manager::get_option( Option::THEME_SUPPORT ) );
		$this->assertEquals( 'twentysixteen', AMP_Options_Manager::get_option( Option::READER_THEME ) );
	}

	public function get_test_get_options_defaults_data() {
		return [
			'reader'                               => [
				null,
				AMP_Theme_Support::READER_MODE_SLUG,
			],
			'transitional_without_template_dir'    => [
				[
					'paired' => true,
				],
				AMP_Theme_Support::TRANSITIONAL_MODE_SLUG,
			],
			'transitional_implied_by_template_dir' => [
				[
					'template_dir' => 'amp',
				],
				AMP_Theme_Support::TRANSITIONAL_MODE_SLUG,
			],
			'standard_paired_false'                => [
				[
					'paired' => false,
				],
				AMP_Theme_Support::STANDARD_MODE_SLUG,
			],
			'transitional_no_args'                 => [
				[],
				AMP_Theme_Support::TRANSITIONAL_MODE_SLUG,
			],
			'standard_via_native'                  => [
				null,
				AMP_Theme_Support::STANDARD_MODE_SLUG,
				[
					Option::THEME_SUPPORT => 'native',
				],
			],
			'standard_via_paired'                  => [
				null,
				AMP_Theme_Support::TRANSITIONAL_MODE_SLUG,
				[
					Option::THEME_SUPPORT => 'paired',
				],
			],
			'reader_mode_persists_non_paired'      => [
				[
					'paired' => false,
				],
				AMP_Theme_Support::READER_MODE_SLUG,
				[
					Option::THEME_SUPPORT => 'disabled',
				],
			],
			'reader_mode_persists_paired'          => [
				[
					'paired' => true,
				],
				AMP_Theme_Support::READER_MODE_SLUG,
				[
					Option::THEME_SUPPORT => 'disabled',
				],
			],
		];
	}

	/**
	 * Test get_options defaults.
	 *
	 * @dataProvider get_test_get_options_defaults_data
	 * @covers AMP_Options_Manager::get_options()
	 * @covers AMP_Options_Manager::get_option()
	 *
	 * @param array|null $args           Theme support args.
	 * @param string     $expected_mode  Expected mode.
	 * @param array      $initial_option Initial option in DB.
	 */
	public function test_get_options_theme_support_defaults( $args, $expected_mode, $initial_option = [] ) {
		update_option( AMP_Options_Manager::OPTION_NAME, $initial_option );
		if ( isset( $args ) ) {
			add_theme_support( 'amp', $args );
		}
		$this->assertEquals( $expected_mode, AMP_Options_Manager::get_option( Option::THEME_SUPPORT ) );
	}

	/**
	 * Test handle_updated_theme_support_option for reader mode.
	 *
	 * @covers AMP_Options_Manager::handle_updated_theme_support_option()
	 * @covers ::amp_admin_get_preview_permalink()
	 */
	public function test_handle_updated_theme_support_option_disabled() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		AMP_Validation_Manager::init();

		$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		AMP_Options_Manager::update_option( Option::SUPPORTED_POST_TYPES, [ 'page' ] );
		AMP_Options_Manager::update_option( Option::THEME_SUPPORT, AMP_Theme_Support::READER_MODE_SLUG );
		AMP_Options_Manager::handle_updated_theme_support_option();
		$amp_settings_errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$new_error           = end( $amp_settings_errors );
		$this->assertStringStartsWith( 'Reader mode activated!', $new_error['message'] );
		$this->assertStringContains( esc_url( amp_get_permalink( $page_id ) ), $new_error['message'], 'Expect amp_admin_get_preview_permalink() to return a page since it is the only post type supported.' );
		$this->assertCount( 0, get_posts( [ 'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ] ) );
	}

	/**
	 * Test handle_updated_theme_support_option for standard when there is one auto-accepted issue.
	 *
	 * @covers AMP_Options_Manager::handle_updated_theme_support_option()
	 * @covers ::amp_admin_get_preview_permalink()
	 */
	public function test_handle_updated_theme_support_option_standard_success_but_error() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id = self::factory()->post->create( [ 'post_type' => 'post' ] );
		AMP_Options_Manager::update_option( Option::THEME_SUPPORT, AMP_Theme_Support::STANDARD_MODE_SLUG );
		AMP_Options_Manager::update_option( Option::SUPPORTED_POST_TYPES, [ 'post' ] );

		$filter = static function() {
			$validation = [
				'results' => [
					[
						'error'     => [ 'code' => 'example' ],
						'sanitized' => false,
					],
				],
			];
			return [
				'body' => wp_json_encode( $validation ),
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );
		AMP_Options_Manager::handle_updated_theme_support_option();
		remove_filter( 'pre_http_request', $filter );
		$amp_settings_errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$new_error           = end( $amp_settings_errors );
		$this->assertStringStartsWith( 'Standard mode activated!', $new_error['message'] );
		$this->assertStringContains( esc_url( amp_get_permalink( $post_id ) ), $new_error['message'], 'Expect amp_admin_get_preview_permalink() to return a post since it is the only post type supported.' );
		$invalid_url_posts = get_posts(
			[
				'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
				'fields'    => 'ids',
			]
		);
		$this->assertEquals( 'updated', $new_error['type'] );
		$this->assertCount( 1, $invalid_url_posts );
		$this->assertStringContains( 'review 1 issue', $new_error['message'] );
		$this->assertStringContains( esc_url( get_edit_post_link( $invalid_url_posts[0], 'raw' ) ), $new_error['message'], 'Expect edit post link for the invalid URL post to be present.' );
	}

	/**
	 * Test handle_updated_theme_support_option for standard when there is one auto-accepted issue.
	 *
	 * @covers AMP_Options_Manager::handle_updated_theme_support_option()
	 * @covers ::amp_admin_get_preview_permalink()
	 */
	public function test_handle_updated_theme_support_option_standard_validate_error() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		self::factory()->post->create( [ 'post_type' => 'post' ] );

		AMP_Options_Manager::update_option( Option::THEME_SUPPORT, AMP_Theme_Support::STANDARD_MODE_SLUG );
		AMP_Options_Manager::update_option( Option::SUPPORTED_POST_TYPES, [ 'post' ] );

		$filter = static function() {
			return [
				'body' => '<html amp><head></head><body></body>',
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );
		AMP_Options_Manager::handle_updated_theme_support_option();
		remove_filter( 'pre_http_request', $filter );

		$amp_settings_errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$new_error           = end( $amp_settings_errors );
		$this->assertStringStartsWith( 'Standard mode activated!', $new_error['message'] );
		$invalid_url_posts = get_posts(
			[
				'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
				'fields'    => 'ids',
			]
		);
		$this->assertCount( 0, $invalid_url_posts );
		$this->assertEquals( 'error', $new_error['type'] );
	}

	/**
	 * Test handle_updated_theme_support_option for transitional mode.
	 *
	 * @covers AMP_Options_Manager::handle_updated_theme_support_option()
	 * @covers ::amp_admin_get_preview_permalink()
	 */
	public function test_handle_updated_theme_support_option_paired() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id = self::factory()->post->create( [ 'post_type' => 'post' ] );
		AMP_Options_Manager::update_option( Option::THEME_SUPPORT, AMP_Theme_Support::TRANSITIONAL_MODE_SLUG );
		AMP_Options_Manager::update_option( Option::SUPPORTED_POST_TYPES, [ 'post' ] );

		$filter = static function() {
			$validation = [
				'results' => [
					[
						'error'     => [ 'code' => 'foo' ],
						'sanitized' => false,
					],
					[
						'error'     => [ 'code' => 'bar' ],
						'sanitized' => false,
					],
				],
			];
			return [
				'body' => wp_json_encode( $validation ),
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );
		AMP_Options_Manager::handle_updated_theme_support_option();
		remove_filter( 'pre_http_request', $filter );
		$amp_settings_errors = get_settings_errors( AMP_Options_Manager::OPTION_NAME );
		$new_error           = end( $amp_settings_errors );
		$this->assertStringStartsWith( 'Transitional mode activated!', $new_error['message'] );
		$this->assertStringContains( esc_url( amp_get_permalink( $post_id ) ), $new_error['message'], 'Expect amp_admin_get_preview_permalink() to return a post since it is the only post type supported.' );
		$invalid_url_posts = get_posts(
			[
				'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
				'fields'    => 'ids',
			]
		);
		$this->assertEquals( 'updated', $new_error['type'] );
		$this->assertCount( 1, $invalid_url_posts );
		$this->assertStringContains( 'review 2 issues', $new_error['message'] );
		$this->assertStringContains( esc_url( get_edit_post_link( $invalid_url_posts[0], 'raw' ) ), $new_error['message'], 'Expect edit post link for the invalid URL post to be present.' );
	}
}
