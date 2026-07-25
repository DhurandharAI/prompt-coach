<?php
/**
 * Site header.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main">
	<?php esc_html_e( 'Skip to content', 'cupcakelab' ); ?>
</a>

<header class="site-header">
	<div class="container site-header__inner">
		<?php
		$business = cupcakelab_business();

		if ( has_custom_logo() ) {
			the_custom_logo();
		} else {
			printf(
				'<a class="site-brand" href="%s">Cupcake<span>Lab</span></a>',
				esc_url( home_url( '/' ) )
			);
		}
		?>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
			<?php esc_html_e( 'Menu', 'cupcakelab' ); ?>
		</button>

		<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'cupcakelab' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 2,
				) );
			} else {
				// Sensible default before anyone builds a menu in the admin.
				echo '<ul>';
				printf(
					'<li><a href="%s">%s</a></li>',
					esc_url( (string) get_permalink( wc_get_page_id( 'shop' ) ) ),
					esc_html__( 'Shop', 'cupcakelab' )
				);
				foreach ( array( '/seasonal' => 'Seasonal', '/about' => 'About', '/contact' => 'Contact' ) as $path => $label ) {
					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( home_url( $path ) ),
						esc_html( $label )
					);
				}
				echo '</ul>';
			}
			?>
		</nav>

		<?php cupcakelab_cart_link(); ?>
	</div>
</header>
