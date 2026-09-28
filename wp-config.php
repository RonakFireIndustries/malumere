<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'malumere' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'P=H0M(y|@c1m]vH#OMJ5!g5H-2cGRP-Uv`_/e9!Qc}I#00d:4&?n>{nfaf1QT/9(' );
define( 'SECURE_AUTH_KEY',  'ifuG|qX{H4C.{9naou [jFJi`I 7}FV}u&&DV%+:?-bNkwu|B}*)_7GG$Q`m-L^L' );
define( 'LOGGED_IN_KEY',    '{#(aBqnl1dL}b_S+zEryf%^ElpH!Ht96/d]Y:aF:MyOaVva<Y,|~U+U]_V..aL]O' );
define( 'NONCE_KEY',        'c3,qZsiX~kPl>>h{gA*#g`l-c;5:;<Ij)JhnRe@UW`SDx!v5B CpC!s[xN+w,-8v' );
define( 'AUTH_SALT',        '>z9CkPjxyknO?y*yy5BxD!?tqfs2:!Jy;Zro#g(]ceb@^3Xn~1(1i>rIQU3Rvl*B' );
define( 'SECURE_AUTH_SALT', '1m(^:64oa*:vNtj.QRV8] o;`K[#L/$b:C;SnHXHqsp]Hq+wmlzL%U@x9__AHf}9' );
define( 'LOGGED_IN_SALT',   '6w)g#GvH^j,to/W#?EJ*wc*vHx`K2]sE&=mp}?+rP+EWh7]8.Z4qy!y?FP02{kr{' );
define( 'NONCE_SALT',       '#<tjs[91c.%82)mnb(h`F4O[xXyam;KDKWb,b $}_uI&W9;{yUM1mhs+A}UK29V[' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
