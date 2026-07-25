<?php
/**
 * 404.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

get_header();
?>

<main id="main" class="site-main container">
	<div class="section section__head section__head--center">
		<p class="eyebrow"><?php esc_html_e( 'Error 404', 'cupcakelab' ); ?></p>
		<h1><?php esc_html_e( 'We could not find that page', 'cupcakelab' ); ?></h1>
		<p class="lede">
			<?php esc_html_e( 'It may have moved, or the link may be out of date. The menu is still where you left it.', 'cupcakelab' ); ?>
		</p>
		<div class="hero__actions">
			<a class="btn" href="<?php echo esc_url( (string) get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
				<?php esc_html_e( 'Browse the menu', 'cupcakelab' ); ?>
			</a>
			<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Go home', 'cupcakelab' ); ?>
			</a>
		</div>
	</div>
</main>

<?php
get_footer();
