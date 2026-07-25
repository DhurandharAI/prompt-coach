<?php
/**
 * Fallback template for archives and the blog.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

get_header();
?>

<main id="main" class="site-main container">
	<?php if ( have_posts() ) : ?>
		<?php if ( ! is_front_page() ) : ?>
			<div class="section__head">
				<h1><?php echo esc_html( wp_get_document_title() ); ?></h1>
			</div>
		<?php endif; ?>

		<div class="grid grid--3">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
						</a>
					<?php endif; ?>
					<div class="card__body">
						<h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="card__desc"><?php echo esc_html( get_the_excerpt() ); ?></p>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination( array(
			'mid_size'  => 1,
			'prev_text' => __( 'Previous', 'cupcakelab' ),
			'next_text' => __( 'Next', 'cupcakelab' ),
		) );
		?>
	<?php else : ?>
		<div class="section__head">
			<h1><?php esc_html_e( 'Nothing here yet', 'cupcakelab' ); ?></h1>
			<p class="lede"><?php esc_html_e( 'Try the shop instead — that is where the cake is.', 'cupcakelab' ); ?></p>
			<a class="btn" href="<?php echo esc_url( (string) get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
				<?php esc_html_e( 'Browse the menu', 'cupcakelab' ); ?>
			</a>
		</div>
	<?php endif; ?>
</main>

<?php
get_footer();
