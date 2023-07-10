<?php
define('ADMIN_COOKIE_PATH', '/__APPS/easylaws/site/dashboard'); // Added by System Security
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */
@set_time_limit(0);
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'app_easylaws_new');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'voodoog');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '_G0j#-JAlb!sOya-g3IB7,()DCTBh,/uSGUq|V-K?~aB/cAqr`tU}<*R+E-F=W^.');
define('SECURE_AUTH_KEY', 'R@yD8gxe[X1e=dF[:{MJUD+YA!N<x7kSgTB,o~<<~Hqum:W72XB?IR>l!7;x8VOH');
define('LOGGED_IN_KEY', '?2+dD:igzC.3IHW@=)FV63eQ>M`FH((n7o+mB-|Z6SfXHh0(/E[rG&RPe_saWkRa');
define('NONCE_KEY', '47k*L&2KV*VFrzl[J;3JSivj-]~^S=#t>@B:g`V!MV7^wl(jVK(hyf^0jG=kCQee');
define('AUTH_SALT', 'sS^7_9w;|s!cB(s=9OP$>KSG[sx~hbZ#UYz3<nzhc3*=h9LV#swI&W<!HHsVjUE|');
define('SECURE_AUTH_SALT', 'vu/!0*I8$}f9~1KxNmb|J-O|FjHCq$t>|c9-aHch-!]|AHM1Jr}`mVffO&L}CdS9');
define('LOGGED_IN_SALT', '$y=X-a}EAw*#-6(j,Ppz]7*}r&%o[7p+rnyG~7i(s6R`]@vA+-DY9QE|M!8-^L@*');
define('NONCE_SALT', 'Fh=w89?)<JFJ;-~BumhtrDySkL]D.q`hxP)+*-~FPnw~N+E(+8oFx8g>U]zlU/)A');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'bw_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);


 /** Disabling revisions articles */
define('WP_POST_REVISIONS', 0);

 /** Disabling the theme editor and extension */
define('DISALLOW_FILE_EDIT', true);

 /** Range of automatic backups */
define('AUTOSAVE_INTERVAL', 7200);

 /** Memory limit is increased */
define('WP_MEMORY_LIMIT', '1024M');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
