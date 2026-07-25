<?php
/**
 * Homepage, rebuilt from the mockup's section order:
 *   hero -> occasions -> brands -> featured -> about -> trust
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

get_header();

$business = cupcakelab_business();
?>

<main id="main" class="site-main">

	<section class="hero" id="home">
		<div class="container hero__inner">
			<p class="eyebrow"><?php esc_html_e( 'Cupcake Lab · Cubao, Quezon City', 'cupcakelab' ); ?></p>
			<h1><?php esc_html_e( 'A Symphony of Sweetness', 'cupcakelab' ); ?></h1>
			<p class="hero__lede">
				<?php esc_html_e( 'Handcrafted cakes, cupcakes and desserts made with trusted recipes, quality ingredients, and a balance of flavors for every celebration.', 'cupcakelab' ); ?>
			</p>
			<div class="hero__actions">
				<a class="btn" href="<?php echo esc_url( (string) get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'Order now', 'cupcakelab' ); ?>
				</a>
				<a class="btn btn--ghost" href="#occasions">
					<?php esc_html_e( 'Seasonal menus', 'cupcakelab' ); ?>
				</a>
			</div>
		</div>
	</section>

	<section class="section section--soft" id="occasions">
		<div class="container">
			<div class="section__head section__head--center">
				<p class="eyebrow"><?php esc_html_e( 'Special occasions', 'cupcakelab' ); ?></p>
				<h2><?php esc_html_e( 'Menus for the moments that matter', 'cupcakelab' ); ?></h2>
				<p class="lede">
					<?php esc_html_e( 'Handcrafted limited-edition treats for life’s most meaningful celebrations. Tap an occasion to see the full menu.', 'cupcakelab' ); ?>
				</p>
			</div>

			<div class="grid grid--4">
				<?php foreach ( cupcakelab_occasions() as $occasion ) : ?>
					<?php
					$is_live = ! empty( $occasion['live'] ) && ! empty( $occasion['url'] );
					$tag     = $is_live ? 'a' : 'div';
					$classes = 'occasion' . ( $is_live ? '' : ' occasion--soon' );
					?>
					<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( $classes ); ?>"
						<?php if ( $is_live ) : ?>
							href="<?php echo esc_url( cupcakelab_url( $occasion['url'] ) ); ?>"
						<?php endif; ?>>
						<p class="occasion__emoji" aria-hidden="true"><?php echo esc_html( $occasion['emoji'] ); ?></p>
						<h3 class="occasion__title"><?php echo esc_html( $occasion['title'] ); ?></h3>
						<p class="occasion__date">
							<?php
							echo $is_live
								? esc_html( $occasion['date'] )
								: esc_html__( 'Coming soon', 'cupcakelab' );
							?>
						</p>
					</<?php echo esc_attr( $tag ); ?>>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" id="brands">
		<div class="container">
			<div class="section__head section__head--center">
				<p class="eyebrow"><?php esc_html_e( 'The family', 'cupcakelab' ); ?></p>
				<h2><?php esc_html_e( 'Three Brands, One Experience', 'cupcakelab' ); ?></h2>
				<p class="lede">
					<?php esc_html_e( 'A curated collection of artisanal desserts, celebration cakes and memorable gifting across our three signature brands.', 'cupcakelab' ); ?>
				</p>
			</div>

			<div class="grid grid--3">
				<?php foreach ( cupcakelab_brands() as $brand ) : ?>
					<div class="brand-card">
						<h3 class="brand-card__name"><?php echo esc_html( $brand['name'] ); ?></h3>
						<p class="brand-card__tagline"><?php echo esc_html( $brand['tagline'] ); ?></p>
						<p><?php echo esc_html( $brand['blurb'] ); ?></p>
						<a class="btn btn--ghost" href="<?php echo esc_url( cupcakelab_url( $brand['url'] ) ); ?>">
							<?php echo esc_html( $brand['cta'] ); ?>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	$featured = cupcakelab_featured_products( 3 );
	if ( $featured ) :
		?>
		<section class="section section--soft" id="featured">
			<div class="container">
				<div class="section__head section__head--center">
					<p class="eyebrow"><?php esc_html_e( 'Featured creations', 'cupcakelab' ); ?></p>
					<h2><?php esc_html_e( 'Handpicked favourites', 'cupcakelab' ); ?></h2>
				</div>

				<ul class="products">
					<?php
					// Reuse the shop loop so featured products get the same card and
					// Quick Order button as everywhere else.
					foreach ( $featured as $featured_product ) {
						$post_object = get_post( $featured_product->get_id() );
						if ( ! $post_object ) {
							continue;
						}
						setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						wc_get_template_part( 'content', 'product' );
					}
					wp_reset_postdata();
					?>
				</ul>

				<p style="text-align:center;margin-top:40px">
					<a class="btn" href="<?php echo esc_url( (string) get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
						<?php esc_html_e( 'See the full menu', 'cupcakelab' ); ?>
					</a>
				</p>
			</div>
		</section>
	<?php endif; ?>

	<section class="section" id="about">
		<div class="container">
			<div class="section__head">
				<p class="eyebrow"><?php esc_html_e( 'Our story', 'cupcakelab' ); ?></p>
				<h2><?php esc_html_e( 'Crafting Memories, One Sweet Detail at a Time', 'cupcakelab' ); ?></h2>
			</div>
			<div class="grid grid--2">
				<p>
					<?php
					printf(
						/* translators: %d: year founded */
						esc_html__( 'Since %d, Cupcake Lab has grown into a polished family of brands focused on celebrations that feel personal, elegant and unforgettable. From your everyday cupcake craving to wedding centerpieces and curated gifting, every detail is designed to feel premium yet warm.', 'cupcakelab' ),
						(int) $business['founded']
					);
					?>
				</p>
				<p>
					<?php esc_html_e( 'We create desserts and gifts that turn moments into memories. At the heart of our brands is one simple belief: every celebration deserves to feel special, thoughtful and beautifully made.', 'cupcakelab' ); ?>
				</p>
			</div>
		</div>
	</section>

	<section class="section section--soft">
		<div class="container">
			<div class="section__head section__head--center">
				<p class="eyebrow"><?php esc_html_e( 'Loved by celebration enthusiasts', 'cupcakelab' ); ?></p>
				<h2><?php esc_html_e( 'What our customers say', 'cupcakelab' ); ?></h2>
			</div>
			<div class="grid grid--3">
				<?php foreach ( cupcakelab_testimonials() as $quote ) : ?>
					<blockquote class="brand-card" style="margin:0">
						<p>“<?php echo esc_html( $quote ); ?>”</p>
					</blockquote>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
