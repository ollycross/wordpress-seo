<?php
/**
 * WPSEO plugin test file.
 *
 * @package WPSEO\Tests
 */

/**
 * OpenGraph tests.
 *
 * @group OpenGraph
 */
class WPSEO_OpenGraph_Image_Test extends WPSEO_UnitTestCase {

	/**
	 * Provision tests.
	 */
	public function setUp() {
		parent::setUp();
		WPSEO_Frontend::get_instance()->reset();
		remove_all_actions( 'wpseo_opengraph' );
	}

	/**
	 * Clean output buffer after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		WPSEO_Options::set( 'og_default_image', false );
		WPSEO_Options::set( 'og_frontpage_image', false );

		ob_clean();
	}

	/**
	 * Tests instantiating of the object with no argument.
	 *
	 * @covers WPSEO_OpenGraph_Image::__construct
	 */
	public function test_constructor_with_no_argument() {
		$class_instance = new WPSEO_OpenGraph_Image();

		$this->assertAttributeInstanceOf( 'WPSEO_OpenGraph', 'opengraph', $class_instance );
	}

	/**
	 * Tests instantiating of the object with a string argument.
	 *
	 * @covers WPSEO_OpenGraph_Image::__construct
	 */
	public function test_constructor_with_string_argument() {
		$class_instance = new WPSEO_OpenGraph_Image( 'image.jpg' );

		$this->assertAttributeInstanceOf( 'WPSEO_OpenGraph', 'opengraph', $class_instance );
	}

	/**
	 * Tests instantiating of the object OpenGraph 'not being set'.
	 *
	 * @covers WPSEO_OpenGraph_Image::__construct
	 */
	public function test_constructor_with_no_global_wpseo_og_object() {
		$old_og              = $GLOBALS['wpseo_og'];
		$GLOBALS['wpseo_og'] = false;
		$class_instance      = new WPSEO_OpenGraph_Image( 'image.jpg' );

		$GLOBALS['wpseo_og'] = $old_og;

		$this->assertAttributeInstanceOf( 'WPSEO_OpenGraph', 'opengraph', $class_instance );
	}

	/**
	 * Tests whether has images is false by default.
	 */
	public function test_has_images_is_FALSE() {
		$class_instance = $this->setup_class();

		$this->assertFalse( $class_instance->has_images() );
	}

	/**
	 * Tests whether has images is false by default.
	 */
	public function test_add_image_relative() {
		$class_instance = $this->setup_class();

		$class_instance->add_image( [ 'url' => '/test.png' ] );
		$this->assertEquals( $this->sample_array(), $class_instance->get_images() );
	}

	/**
	 * Tests whether has images is false by default.
	 */
	public function test_add_image_twice() {
		$class_instance = $this->setup_class();

		$class_instance->add_image( [ 'url' => 'http://example.org/test.png' ] );
		$class_instance->add_image( [ 'url' => '/test.png' ] );
		$this->assertEquals( $this->sample_array( false ), $class_instance->get_images() );
	}

	/**
	 * Tests whether passing an image string and not an array, works.
	 */
	public function test_add_image_as_string() {
		$class_instance = $this->setup_class();
		$class_instance->add_image( '/test.png' );

		$this->assertEquals( $this->sample_array(), $class_instance->get_images() );
	}

	/**
	 * Tests the wpseo_opengraph_is_valid_image_url filter.
	 *
	 * @covers WPSEO_OpenGraph_Image::is_valid_image_url
	 */
	public function test_is_valid_image_url_filter() {

		$class_instance = $this->setup_class();
		$class_instance->add_image( 'https://via.placeholder.com/350x150' );

		// Without the filter, this isn't considered a valid image URL.
		$this->assertEmpty( $class_instance->get_images() );

		// Enable the filter.
		add_filter( 'wpseo_opengraph_is_valid_image_url', '__return_true', 10, 2 );

		$class_instance->add_image( 'https://via.placeholder.com/350x150' );

		// Verify the image was added.
		$this->assertArrayHasKey( 'https://via.placeholder.com/350x150', $class_instance->get_images() );

		remove_filter( 'wpseo_opengraph_is_valid_image_url', '__return_true' );
	}

	/**
	 * Tests the situations where an image won't added.
	 *
	 * @dataProvider invalid_image_provider
	 *
	 * @param mixed  $image   The image data.
	 * @param string $message The message to show when test fails.
	 */
	public function test_invalid_images( $image, $message ) {

		$class_instance = $this->setup_class();
		$class_instance->add_image( $image );

		$this->assertEmpty( $class_instance->get_images(), $message );
	}

	/**
	 * Provides data that will be skipped when adding it as a image.
	 *
	 * @return array The data.
	 */
	public function invalid_image_provider() {
		return [
			[
				[ 'url' => 'http://example.org/test.svg' ],
				'Adding an SVG as image',
			],
			[
				[ 'link' => '/test.png' ],
				'With url key missing',
			],
			[
				[ 'url' => '' ],
				'With an empty url given',
			],
			[
				[ 'url' => null ],
				'With null given as url',
			],
			[
				[],
				'With empty array',
			],
			[
				null,
				'With null given as data',
			],
			[
				false,
				'With false given as data',
			],
			[
				(object) [ 'url' => null ],
				'With object given as data',
			],
		];
	}

	/**
	 * Test setting the front page image.
	 *
	 * @covers WPSEO_OpenGraph_Image::set_front_page_image
	 */
	public function test_frontpage_image() {
		WPSEO_Options::set( 'og_frontpage_image', 'http://example.org/frontpage.png' );
		WPSEO_Options::set( 'og_default_image', 'http://example.org/test.png' );

		$current_page_on_front = get_option( 'page_on_front' );
		$current_show_on_front = get_option( 'show_on_front' );

		// Create and go to a static front page.
		$page_on_front = $this->create_post( 'page' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_on_front );

		$this->go_to( '/' );

		$class_instance = $this->setup_class();

		/*
		 * With a static frontpage, the image should be the default image, or the
		 * image from the static front page itself, not the frontpage image.
		 */
		$this->assertEquals( $this->sample_array( false ), $class_instance->get_images() );

		update_option( 'show_on_front', $current_show_on_front );
		update_option( 'page_on_front', $current_page_on_front );
	}

	/**
	 * Test setting the front page image via a user-defined image.
	 *
	 * @covers WPSEO_OpenGraph_Image::set_front_page_image
	 */
	public function test_frontpage_image_uses_user_defined() {
		WPSEO_Options::set( 'og_frontpage_image', '/test.png' );

		$current_page_on_front = get_option( 'page_on_front' );
		$current_show_on_front = get_option( 'show_on_front' );

		// Create and go to a static front page.
		$page_on_front = $this->create_post( 'page' );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_on_front );

		// Set user-defined image.
		WPSEO_Meta::set_value( 'opengraph-image', '/user-defined.png', $page_on_front );

		$this->go_to( '/' );

		$class_instance = $this->setup_class();

		$this->assertArrayHasKey( 'http://example.org/user-defined.png', $class_instance->get_images() );

		update_option( 'show_on_front', $current_show_on_front );
		update_option( 'page_on_front', $current_page_on_front );
	}

	/**
	 * Test attachment pages.
	 */
	public function test_set_attachment_page_image() {
		$post_id         = $this->create_post();
		$image           = '/assets/yoast.png';
		$rand            = wp_rand( 1000, 9999 );
		$basename        = str_replace( '.png', '-attachment-test-' . $rand . '.png', basename( $image ) );
		$upload_dir      = wp_upload_dir();
		$source_image    = dirname( __FILE__ ) . '/..' . $image;
		$full_image_path = $upload_dir['path'] . '/' . $basename;

		copy( $source_image, $full_image_path ); // Prevent original from being deleted.

		$file_array = [
			'name'     => $basename,
			'tmp_name' => $full_image_path,
		];
		$attach_id  = media_handle_sideload( $file_array, $post_id );
		$filename   = basename( get_attached_file( $attach_id ) );

		$this->go_to( get_permalink( $attach_id ) );

		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_full_file_array( $upload_dir['url'] . '/' . $filename, $attach_id ), $class_instance->get_images() );

		wp_delete_file( get_attached_file( $attach_id ) );
	}

	/**
	 * Test get singular false.
	 */
	public function test_set_singular_image_FALSE() {
		$post_id = $this->create_post();
		$this->go_to( get_permalink( $post_id ) );

		$class_instance = $this->setup_class();

		$this->assertFalse( $class_instance->has_images() );
	}

	/**
	 * Test get singular false.
	 */
	public function test_set_singular_image_post_meta() {
		$post_id = $this->create_post();
		WPSEO_Meta::set_value( 'opengraph-image', '/test.png', $post_id );
		$this->go_to( get_permalink( $post_id ) );

		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_array(), $class_instance->get_images() );
	}

	/**
	 * Test get singular post with featured image.
	 */
	public function test_set_singular_image_featured() {
		$post_id = $this->create_post();
		$image   = $this->create_featured_image( '/assets/yoast.png', $post_id );

		$this->go_to( get_permalink( $post_id ) );

		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_full_file_array( $image['url'], $image['id'] ), $class_instance->get_images() );
	}

	/**
	 * Test get singular with too small featured image.
	 */
	public function test_set_singular_image_featured_TOO_SMALL() {
		$post_id = $this->create_post();
		$this->create_featured_image( '/assets/small.png', $post_id );

		$this->go_to( get_permalink( $post_id ) );

		$class_instance = $this->setup_class();

		$this->assertEquals( [], $class_instance->get_images() );
	}

	/**
	 * Test our default image fallback.
	 */
	public function test_set_images_default() {
		WPSEO_Options::set( 'og_default_image', 'http://example.org/test.png' );
		$this->go_to_home();

		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_array( false ), $class_instance->get_images() );
	}

	/**
	 * Test the featured image for the posts page.
	 */
	public function test_set_posts_page_image() {
		$frontpage = $this->create_post( 'page' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $frontpage );

		$post_id = $this->create_post( 'page' );
		$image   = $this->create_featured_image( '/assets/yoast.png', $post_id );
		update_option( 'page_for_posts', $post_id );

		$this->go_to( get_permalink( $post_id ) );

		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_full_file_array( $image['url'], $image['id'] ), $class_instance->get_images() );
	}

	/**
	 * Test setting the opengraph image for a taxonomy term.
	 */
	public function test_set_taxonomy_image() {
		$post_id = $this->create_post( 'post' );
		$term_id = $this->factory()->category->create(
			[
				'name' => 'Test Category 1',
				'slug' => 'test1',
			]
		);
		wp_set_object_terms( $post_id, $term_id, 'category' );
		WPSEO_Taxonomy_Meta::set_value( $term_id, 'category', 'opengraph-image', '/test.png' );

		$url = add_query_arg(
			[ 'cat' => $term_id ],
			'/'
		);
		$this->go_to( $url );

		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_array(), $class_instance->get_images() );
	}

	/**
	 * Uploads an image and sets it as an attachment to a post.
	 *
	 * @param string $image    The URL to the image to upload.
	 * @param string $post_id  The post to add the image to.
	 * @param string $use_name Optional. Copy the file to a different filename.
	 *
	 * @return array $attachment. Contains the URL to the attachment image and the attachment ID.
	 */
	public function add_image_attachment_to_post( $image, $post_id, $use_name = '' ) {
		// Copy the image to the upload folder.
		$basename = basename( $image );
		if ( ! empty( $use_name ) ) {
			$basename = $use_name;
		}

		$upload_dir     = wp_upload_dir();
		$source_image   = dirname( __FILE__ ) . '/..' . $image;
		$featured_image = $upload_dir['path'] . '/' . $basename;
		copy( $source_image, $featured_image ); // Prevent original from being deleted.

		$file_array = [
			'name'     => $basename,
			'tmp_name' => $featured_image,
		];
		$attach_id  = media_handle_sideload( $file_array, $post_id );

		// Get the image URL so we can add it in the post content.
		$file           = get_attached_file( $attach_id );
		$attached_image = $upload_dir['url'] . '/' . basename( $file );

		return [
			'image' => $attached_image,
			'id'    => $attach_id,
		];
	}

	/**
	 * Test getting the image from post content.
	 *
	 * @covers WPSEO_OpenGraph_Image::set_images
	 * @covers WPSEO_OpenGraph_Image::add_first_usable_content_image
	 */
	public function test_get_images_from_content() {
		$image_url    = 'https://cdn.yoast.com/app/uploads/2018/03/Caroline_Blog_SEO_FI-600x314.jpg';
		$post_content = '<p>This is a post. It has an image hosted on a cdn:</p>
		<img src="' . $image_url . '"/>
		<p>End of post</p>';

		$post_id = self::factory()->post->create(
			[
				'post_content' => $post_content,
			]
		);

		$opengraph_image = $this
			->getMockBuilder( 'WPSEO_Opengraph_Image_Double' )
			->disableOriginalConstructor()
			->setMethods( [ 'add_image' ] )
			->getMock();

		$opengraph_image
			->expects( $this->once() )
			->method( 'add_image' )
			->with(
				[
					'url' => $image_url,
				]
			);

		// Run our test.
		$this->go_to( get_permalink( $post_id ) );

		$opengraph_image->set_images();
	}

	/**
	 * Test using an image that's already uploaded to another post as OG setting.
	 */
	public function test_uploaded_image_added_by_id() {
		// We create a post, and upload an image to it.
		$post_id = $this->create_post();
		$image   = $this->create_featured_image( '/assets/yoast.png', $post_id );

		// We create another post and use the image, attached to the _other_ post, as its OpenGraph image.
		$post2_id = $this->create_post();
		WPSEO_Meta::set_value( 'opengraph-image', $image['url'], $post2_id );

		$this->assertEquals( $image['url'], WPSEO_Meta::get_value( 'opengraph-image', $post2_id ) );
		$this->go_to( get_permalink( $post2_id ) );
		$class_instance = $this->setup_class();

		$this->assertEquals( $this->sample_full_file_array( $image['url'], $image['id'] ), $class_instance->get_images() );
	}

	/**
	 * Sets up our test class.
	 *
	 * @return WPSEO_OpenGraph_Image
	 */
	private function setup_class() {
		return new WPSEO_OpenGraph_Image( null, new WPSEO_OpenGraph() );
	}

	/**
	 * Creates a post for testing.
	 *
	 * @param string $post_type The post type to create a post of.
	 *
	 * @return int $post_id Post ID.
	 */
	private function create_post( $post_type = 'post' ) {
		return self::factory()->post->create(
			[
				'post_type' => $post_type,
			]
		);
	}

	/**
	 * Creates a featured image.
	 *
	 * @param string  $image   Path.
	 * @param integer $post_id Post ID.
	 *
	 * @return array
	 */
	private function create_featured_image( $image, $post_id ) {

		$basename       = basename( $image );
		$upload_dir     = wp_upload_dir();
		$source_image   = dirname( __FILE__ ) . '/..' . $image;
		$featured_image = $upload_dir['path'] . '/' . $basename;

		copy( $source_image, $featured_image ); // Prevent original from deletion.

		$file_array = [
			'name'     => $basename,
			'tmp_name' => $featured_image,
		];
		$attach_id  = media_handle_sideload( $file_array, $post_id );
		$file       = get_attached_file( $attach_id );
		wp_generate_attachment_metadata( $attach_id, $file );
		update_post_meta( $post_id, '_thumbnail_id', $attach_id );

		return [
			'id'   => $attach_id,
			'path' => $file,
			'url'  => $upload_dir['url'] . '/' . basename( $file ),
		];
	}

	/**
	 * Returns a sample test array.
	 *
	 * @param bool $relative Whether the URL was passed in relative or not.
	 *
	 * @return array
	 */
	private function sample_array( $relative = true ) {
		return [
			'http://example.org/test.png' => [
				'url' => ( ( $relative ) ? '/test.png' : 'http://example.org/test.png' ),
			],
		];
	}

	/**
	 * Returns a sample test array.
	 *
	 * @param string $url The URL for the file.
	 * @param int    $id  Attached file ID.
	 *
	 * @return array An array for our default file.
	 */
	private function sample_full_file_array( $url, $id ) {
		return [
			$url => [
				'url'    => $url,
				'width'  => 500,
				'height' => 500,
				'alt'    => '',
				'type'   => 'image/png',
				'path'   => get_attached_file( $id ),
				'size'   => 'full',
				'id'     => $id,
				'pixels' => 250000,
			],
		];
	}
}
