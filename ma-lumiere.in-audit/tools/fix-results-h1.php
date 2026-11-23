<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';
update_post_meta( 15, '_ml_ba_treatment', 'Acne Treatment: What to Expect' );
echo "RESULTS_H1_UPDATED\n";