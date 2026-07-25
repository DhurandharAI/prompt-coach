<?php
/**
 * Search form.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

$cupcakelab_id = 'search-' . wp_unique_id();
?>
<form role="search" method="get" class="cl-filters" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $cupcakelab_id ); ?>">
		<?php esc_html_e( 'Search', 'cupcakelab' ); ?>
	</label>
	<input type="search" id="<?php echo esc_attr( $cupcakelab_id ); ?>" name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search cakes, cupcakes…', 'cupcakelab' ); ?>" />
	<button class="btn" type="submit"><?php esc_html_e( 'Search', 'cupcakelab' ); ?></button>
</form>
