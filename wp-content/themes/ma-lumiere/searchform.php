<?php
/**
 * Accessible search form.
 *
 * @package ma-lumiere
 */
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="visually-hidden"><?php echo esc_html_x( 'Search for:', 'label', 'ma-lumiere' ); ?></span>
		<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search…', 'placeholder', 'ma-lumiere' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
	</label>
	<button type="submit" class="btn btn--primary"><?php echo esc_html_x( 'Search', 'submit button', 'ma-lumiere' ); ?></button>
</form>