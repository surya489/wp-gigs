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

define('FS_METHOD', 'direct');

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gigs_plugin' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'YourStrongPass123!' );

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
define( 'AUTH_KEY',         '~&]1doZ94>280?~IiIuO;mlRgifk)v^L[NLSqDH6F5>XvFM=P*hfk3TSK4B/)G>W' );
define( 'SECURE_AUTH_KEY',  'omGjHPpam-rbJ205e2Qs~ehN!lE=a+/wf+K!jDD/700?5rn1zNY-,j3]nj=q(FW ' );
define( 'LOGGED_IN_KEY',    'Gq,TXE:R-< oN# %3oB|%iX|* gu7&)s=WDJ^_M4]X^)7~60N0JZ+fY[*4h_V>kA' );
define( 'NONCE_KEY',        '+e~B=OA~9,O6_D<hrI.%;Om>%96TULRk|. *`QZ_RcJ+qonCqZa 0bv;_CfeGSs$' );
define( 'AUTH_SALT',        'R7:&kN []/1Z{x4MQPV#LcV*4M!zsLT0.4)}(/UP?T8fqm4#HWR88} %G|99VNU^' );
define( 'SECURE_AUTH_SALT', '?llP339mA&<B$-xfRBd}>*z9G,&5NA7Oe:foAS]G68W#{k<#^93S`%Lag|O$S%#9' );
define( 'LOGGED_IN_SALT',   '%w$7kO]or lPOLKz>{FZLN;JA:_y5$1G3Ys-:)5)ubrYItPhxN2H5dmD/kmSn8Y0' );
define( 'NONCE_SALT',       'Wa:Sjy~*Zubv?@KU1S,>j?of2ze gBz$Fp$gkrV`>D!n|wj|VRi<Z>3lIkg?HM9?' );

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

define('FS_METHOD', 'direct');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
