<?php

/**
 * A set of unit tests for functions in wp-includes/general-template.php
 *
 * @group template
 */
class Tests_General_Template extends WP_UnitTestCase {

	public $wp_site_icon;
	public $site_icon_id;
	public $site_icon_url;

	public $custom_logo_id;
	public $custom_logo_url;

	function setUp() {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';
		$this->wp_site_icon = $GLOBALS['wp_site_icon'];
	}

	function tearDown() {
		$this->_remove_custom_logo();

		parent::tearDown();
	}

	/**
	 * @group site_icon
	 */
	function test_get_site_icon_url() {
		$this->assertEmpty( get_site_icon_url() );

		$this->_set_site_icon();
		$this->assertEquals( $this->site_icon_url, get_site_icon_url() );

		$this->_remove_site_icon();
		$this->assertEmpty( get_site_icon_url() );
	}

	/**
	 * @group site_icon
	 */
	function test_site_icon_url() {
		$this->expectOutputString( '' );
		site_icon_url();

		$this->_set_site_icon();
		$this->expectOutputString( $this->site_icon_url );
		site_icon_url();
		$this->_remove_site_icon();
	}

	/**
	 * @group site_icon
	 */
	function test_has_site_icon() {
		$this->assertFalse( has_site_icon() );

		$this->_set_site_icon();
		$this->assertTrue( has_site_icon() );

		$this->_remove_site_icon();
		$this->assertFalse( has_site_icon() );
	}

	/**
 	 * @group site_icon
	 * @group multisite
	 */
	function test_has_site_icon_returns_true_when_called_for_other_site_with_site_icon_set() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$blog_id = $this->factory->blog->create();
		switch_to_blog( $blog_id );
		$this->_set_site_icon();
		restore_current_blog();

		$this->assertTrue( has_site_icon( $blog_id ) );
	}

	/**
	 * @group site_icon
	 * @group multisite
	 */
	function test_has_site_icon_returns_false_when_called_for_other_site_without_site_icon_set() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$blog_id = $this->factory->blog->create();

		$this->assertFalse( has_site_icon( $blog_id ) );
	}

	/**
	 * @group site_icon
	 */
	function test_wp_site_icon() {
		$this->expectOutputString( '' );
		wp_site_icon();

		$this->_set_site_icon();
		$output = array(
			sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( get_site_icon_url( 32 ) ) ),
			sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( get_site_icon_url( 192 ) ) ),
			sprintf( '<link rel="apple-touch-icon-precomposed" href="%s" />', esc_url( get_site_icon_url( 180 ) ) ),
			sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( get_site_icon_url( 270 ) ) ),
			'',
		);
		$output = implode( "\n", $output );

		$this->expectOutputString( $output );
		wp_site_icon();

		$this->_remove_site_icon();
	}

	/**
	 * @group site_icon
	 */
	function test_wp_site_icon_with_filter() {
		$this->expectOutputString( '' );
		wp_site_icon();

		$this->_set_site_icon();
		$output = array(
			sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( get_site_icon_url( 32 ) ) ),
			sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( get_site_icon_url( 192 ) ) ),
			sprintf( '<link rel="apple-touch-icon-precomposed" href="%s" />', esc_url( get_site_icon_url( 180 ) ) ),
			sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( get_site_icon_url( 270 ) ) ),
			sprintf( '<link rel="apple-touch-icon" sizes="150x150" href="%s" />', esc_url( get_site_icon_url( 150 ) ) ),
			'',
		);
		$output = implode( "\n", $output );

		$this->expectOutputString( $output );
		add_filter( 'site_icon_meta_tags', array( $this, '_custom_site_icon_meta_tag' ) );
		wp_site_icon();
		remove_filter( 'site_icon_meta_tags', array( $this, '_custom_site_icon_meta_tag' ) );

		$this->_remove_site_icon();
	}

	/**
	 * Builds and retrieves a custom site icon meta tag.
	 *
	 * @since 4.3.0
	 *
	 * @param $meta_tags
	 * @return array
	 */
	function _custom_site_icon_meta_tag( $meta_tags ) {
		$meta_tags[] = sprintf( '<link rel="apple-touch-icon" sizes="150x150" href="%s" />', esc_url( get_site_icon_url( 150 ) ) );

		return $meta_tags;
	}

	/**
	 * Sets a site icon in options for testing.
	 *
	 * @since 4.3.0
	 */
	function _set_site_icon() {
		if ( ! $this->site_icon_id ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this->wp_site_icon, 'additional_sizes' ) );
			$this->_insert_attachment();
			remove_filter( 'intermediate_image_sizes_advanced', array( $this->wp_site_icon, 'additional_sizes' ) );
		}

		update_option( 'site_icon', $this->site_icon_id );
	}

	/**
	 * Removes the site icon from options.
	 *
	 * @since 4.3.0
	 */
	function _remove_site_icon() {
		delete_option( 'site_icon' );
	}

	/**
	 * Inserts an attachment for testing site icons.
	 *
	 * @since 4.3.0
	 */
	function _insert_attachment() {
		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$this->site_icon_url = $upload['url'];


		// Save the data
		$this->site_icon_id = $this->_make_attachment( $upload );
		return $this->site_icon_id;
	}

	/**
	 * @group custom_logo
	 *
	 * @since 4.5.0
	 */
	function test_has_custom_logo() {
		$this->assertFalse( has_custom_logo() );

		$this->_set_custom_logo();
		$this->assertTrue( has_custom_logo() );

		$this->_remove_custom_logo();
		$this->assertFalse( has_custom_logo() );
	}

	/**
	 * @group custom_logo
	 * @group multisite
	 */
	function test_has_custom_logo_returns_true_when_called_for_other_site_with_custom_logo_set() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$blog_id = $this->factory->blog->create();
		switch_to_blog( $blog_id );
		$this->_set_custom_logo();
		restore_current_blog();

		$this->assertTrue( has_custom_logo( $blog_id ) );
	}

	/**
	 * @group custom_logo
	 * @group multisite
	 */
	function test_has_custom_logo_returns_false_when_called_for_other_site_without_custom_logo_set() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$blog_id = $this->factory->blog->create();

		$this->assertFalse( has_custom_logo( $blog_id ) );
	}

	/**
	 * @group custom_logo
	 *
	 * @since 4.5.0
	 */
	function test_get_custom_logo() {
		$this->assertEmpty( get_custom_logo() );

		$this->_set_custom_logo();
		$custom_logo = get_custom_logo();
		$this->assertNotEmpty( $custom_logo );
		$this->assertInternalType( 'string', $custom_logo );

		$this->_remove_custom_logo();
		$this->assertEmpty( get_custom_logo() );
	}

	/**
	 * @group custom_logo
	 * @group multisite
	 */
	function test_get_custom_logo_returns_logo_when_called_for_other_site_with_custom_logo_set() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		$blog_id = $this->factory->blog->create();
		switch_to_blog( $blog_id );

		$this->_set_custom_logo();
		$home_url = get_home_url( $blog_id, '/' );
		$size     = get_theme_support( 'custom-logo', 'size' );
		$size     = $size ? $size : 'full';
		$image    = wp_get_attachment_image( $this->custom_logo_id, $size, false, array(
			'class'     => "custom-logo attachment-$size",
			'data-size' => $size,
			'itemprop'  => 'logo',
		) );
		restore_current_blog();

		$expected_custom_logo =  '<a href="' . $home_url . '" class="custom-logo-link" rel="home" itemprop="url">' . $image . '</a>';
		$this->assertEquals( $expected_custom_logo, get_custom_logo( $blog_id ) );
	}

	/**
	 * @group custom_logo
	 *
	 * @since 4.5.0
	 */
	function test_the_custom_logo() {
		$this->expectOutputString( '' );
		the_custom_logo();

		$this->_set_custom_logo();
		$size  = get_theme_support( 'custom-logo', 'size' );
		$size  = $size ? $size : 'full';
		$image = wp_get_attachment_image( $this->custom_logo_id, $size, false, array(
			'class'     => "custom-logo attachment-$size",
			'data-size' => $size,
			'itemprop'  => 'logo',
		) );

		$this->expectOutputString( '<a href="http://' . WP_TESTS_DOMAIN . '/" class="custom-logo-link" rel="home" itemprop="url">' . $image . '</a>' );
		the_custom_logo();
	}

	/**
	 * Sets a site icon in options for testing.
	 *
	 * @since 4.5.0
	 */
	function _set_custom_logo() {
		if ( ! $this->custom_logo_id ) {
			$this->_insert_custom_logo();
		}

		set_theme_mod( 'custom_logo', $this->custom_logo_id );
	}

	/**
	 * Removes the site icon from options.
	 *
	 * @since 4.5.0
	 */
	function _remove_custom_logo() {
		remove_theme_mod( 'custom_logo' );
	}

	/**
	 * Inserts an attachment for testing custom logos.
	 *
	 * @since 4.5.0
	 */
	function _insert_custom_logo() {
		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );
		$upload   = wp_upload_bits( basename( $filename ), null, $contents );

		// Save the data.
		$this->custom_logo_url = $upload['url'];
		$this->custom_logo_id  = $this->_make_attachment( $upload );
		return $this->custom_logo_id;
	}
}
