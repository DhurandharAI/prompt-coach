<?php
/**
 * Single page.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="page-hero">
		<div class="container">
			<h1><?php the_title(); ?></h1>
		</div>
	</div>

	<main id="main" class="site-main container">
		<article <?php post_class(); ?>>
			<div class="entry-content section">
				<?php the_content(); ?>
			</div>
		</article>
	</main>
	<?php
endwhile;

get_footer();
