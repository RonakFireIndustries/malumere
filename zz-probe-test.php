<?php
// Standalone probe, NOT part of WordPress.
header( 'Content-Type: text/plain' );
echo "PLAIN_PHP_OK\n";
echo json_encode( array( 'ok' => true ) ) . "\n";