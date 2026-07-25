<?php
/**
 * Site footer.
 *
 * The legal menu is deliberately prominent: PLAN.md §4 notes that PayMongo's card
 * activation depends on an acquirer finding terms, privacy, refund and delivery
 * policies on a publicly reachable site.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

$business = cupcakelab_business();
?>

<footer class="site-footer">
	<div class="container">
		<div class="grid grid--4">
			<div>
				<p class="site-footer__brand">Cupcake<span>Lab</span></p>
				<p><?php echo esc_html( $business['tagline'] ); ?></p>
				<p>
					<?php
					printf(
						/* translators: %d: year founded */
						esc_html__( 'Baking celebrations since %d.', 'cupcakelab' ),
						(int) $business['founded']
					);
					?>
				</p>
			</div>

			<div>
				<h4><?php esc_html_e( 'Our brands', 'cupcakelab' ); ?></h4>
				<ul>
					<?php foreach ( cupcakelab_brands() as $brand ) : ?>
						<li>
							<a href="<?php echo esc_url( cupcakelab_url( $brand['url'] ) ); ?>">
								<?php echo esc_html( $brand['name'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div>
				<h4><?php esc_html_e( 'Contact us', 'cupcakelab' ); ?></h4>
				<ul>
					<li><?php echo esc_html( $business['location'] ); ?></li>
					<li>
						<a href="tel:<?php echo esc_attr( $business['tel'] ); ?>">
							<?php echo esc_html( $business['phone'] ); ?>
						</a>
					</li>
					<li>
						<a href="mailto:<?php echo esc_attr( $business['email'] ); ?>">
							<?php echo esc_html( $business['email'] ); ?>
						</a>
					</li>
				</ul>
			</div>

			<div>
				<h4><?php esc_html_e( 'Follow', 'cupcakelab' ); ?></h4>
				<ul>
					<?php foreach ( cupcakelab_socials() as $label => $url ) : ?>
						<li>
							<a href="<?php echo esc_url( $url ); ?>" rel="noopener noreferrer" target="_blank">
								<?php echo esc_html( $label ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<div class="site-footer__legal">
			<p>
				<?php
				printf(
					/* translators: 1: year, 2: group name */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'cupcakelab' ),
					esc_html( (string) wp_date( 'Y' ) ),
					esc_html( $business['group'] )
				);
				?>
			</p>

			<?php
			if ( has_nav_menu( 'legal' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'legal',
					'container'      => 'nav',
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'link_before'    => '',
				) );
			} else {
				echo '<nav aria-label="' . esc_attr__( 'Legal', 'cupcakelab' ) . '">';
				$legal = array(
					'/terms'    => __( 'Terms of Service', 'cupcakelab' ),
					'/privacy'  => __( 'Privacy Policy', 'cupcakelab' ),
					'/refunds'  => __( 'Refunds & Cancellations', 'cupcakelab' ),
					'/delivery' => __( 'Delivery Terms', 'cupcakelab' ),
				);
				foreach ( $legal as $path => $label ) {
					printf( '<a href="%s">%s</a>', esc_url( home_url( $path ) ), esc_html( $label ) );
				}
				echo '</nav>';
			}
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
