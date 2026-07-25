<?php
/**
 * Template helpers and the homepage content model.
 *
 * The mockup's homepage was hand-coded HTML. Rather than hard-code the same
 * strings into templates, the content lives here as filterable data so it can be
 * edited without touching markup.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Business details, from the mockup's contact page.
 */
function cupcakelab_business(): array {
	return (array) apply_filters( 'cupcakelab_business', array(
		'name'     => 'Cupcake Lab',
		'group'    => 'MCJC Group',
		'tagline'  => 'Where your friendly cupcake meets exact science.',
		'location' => 'Cubao, Quezon City',
		'phone'    => '0998 853 8586',
		'tel'      => '09988538586',
		'email'    => 'ask@mcjcgroup.com',
		'founded'  => 2012,
	) );
}

/**
 * Seasonal menus shown on the homepage.
 *
 * Only Mother's Day is built out in the mockup; the rest are honest
 * "coming soon" cards rather than dead links.
 */
function cupcakelab_occasions(): array {
	return (array) apply_filters( 'cupcakelab_occasions', array(
		array(
			'emoji' => '🌸',
			'title' => "Mother's Day",
			'date'  => 'May 10',
			'url'   => '/seasonal',
			'live'  => true,
		),
		array(
			'emoji' => '👔',
			'title' => "Father's Day",
			'date'  => 'June 15',
			'url'   => '',
			'live'  => false,
		),
		array(
			'emoji' => '💝',
			'title' => "Valentine's Day",
			'date'  => 'February 14',
			'url'   => '',
			'live'  => false,
		),
		array(
			'emoji' => '🎄',
			'title' => 'Christmas',
			'date'  => 'December 25',
			'url'   => '',
			'live'  => false,
		),
		array(
			'emoji' => '🎃',
			'title' => 'Halloween',
			'date'  => 'October 31',
			'url'   => '',
			'live'  => false,
		),
	) );
}

/**
 * The three MCJC brands.
 *
 * Only Cupcake Lab sells on this install for now; the other two link out to their
 * brochure pages, which is what the agreed scope says.
 */
function cupcakelab_brands(): array {
	return (array) apply_filters( 'cupcakelab_brands', array(
		array(
			'name'    => 'Cupcake Lab',
			'tagline' => 'Where your friendly cupcake meets exact science.',
			'blurb'   => 'Creative cakes and cupcakes for every occasion — birthdays, milestones, and custom celebrations.',
			'url'     => '/shop',
			'cta'     => 'Shop the menu',
		),
		array(
			'name'    => "Lucille's",
			'tagline' => 'Luxury in every layer, Love in every bite.',
			'blurb'   => 'Elegant cake design for weddings and refined celebrations — timeless artistry and floral styling.',
			'url'     => '/lucilles',
			'cta'     => 'View wedding cakes',
		),
		array(
			'name'    => 'GiftLab PH',
			'tagline' => "Gifting on a whole 'nother level.",
			'blurb'   => 'Curated gifting — balloons, cake boxes, flowers and celebration sets, beautifully styled.',
			'url'     => '/giftlab',
			'cta'     => 'Explore gifting',
		),
	) );
}

/**
 * Customer quotes from the mockup's socials page.
 */
function cupcakelab_testimonials(): array {
	return (array) apply_filters( 'cupcakelab_testimonials', array(
		'Cupcake Lab is definitely THE cupcake store for the elite! Its cupcakes are so classy and sophisticated; trust me, once you go Cupcake Lab, you can never go back.',
		'Their red velvet is love! The kind of red velvet that I really like! The cream cheese frosting has just enough sweetness and the bread is really moist.',
		'Everything looked so beautiful and thoughtfully prepared. The whole arrangement felt very premium and special.',
	) );
}

/**
 * Social profiles.
 */
function cupcakelab_socials(): array {
	return (array) apply_filters( 'cupcakelab_socials', array(
		'Instagram' => 'https://www.instagram.com/cupcakelabph',
		'Facebook'  => 'https://www.facebook.com/CupcakeLabph',
		'TikTok'    => 'https://www.tiktok.com/@ms.cupcakelab',
	) );
}

/**
 * Featured products for the homepage.
 *
 * Prefers products tagged "featured" in Woo and falls back to best sellers, so
 * the section is never empty on a fresh install.
 */
function cupcakelab_featured_products( int $limit = 3 ): array {
	$featured = wc_get_products( array(
		'status'   => 'publish',
		'limit'    => $limit,
		'featured' => true,
	) );

	if ( count( $featured ) >= $limit ) {
		return $featured;
	}

	$fallback = wc_get_products( array(
		'status'   => 'publish',
		'limit'    => $limit,
		'orderby'  => 'popularity',
		'exclude'  => array_map( static fn( $p ) => $p->get_id(), $featured ),
	) );

	return array_slice( array_merge( $featured, $fallback ), 0, $limit );
}

/**
 * Resolve a possibly-relative URL from the content model above.
 */
function cupcakelab_url( string $path ): string {
	if ( '' === $path ) {
		return '';
	}
	if ( str_starts_with( $path, 'http' ) ) {
		return $path;
	}
	if ( '/shop' === $path ) {
		return (string) get_permalink( wc_get_page_id( 'shop' ) );
	}

	return home_url( $path );
}
