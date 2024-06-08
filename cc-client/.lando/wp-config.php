<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

if ( getenv('WP_HOME') ) {
	define('WP_HOME', getenv('WP_HOME'));
}

if ( getenv('WP_SITEURL') ) {
	define('WP_SITEURL', getenv('WP_SITEURL'));
}

/** This will ensure these are only loaded on Lando */
if (getenv('LANDO_INFO')) {
	/**  Parse the LANDO INFO  */
	$lando_info = json_decode(getenv('LANDO_INFO'));
  
	/** Get the database config */
	$database_config = $lando_info->database;
	/** The name of the database for WordPress */
	define('DB_NAME', $database_config->creds->database);
	/** MySQL database username */
	define('DB_USER', $database_config->creds->user);
	/** MySQL database password */
	define('DB_PASSWORD', $database_config->creds->password);
	/** MySQL hostname */
	define('DB_HOST', $database_config->internal_connection->host);
  
	/** URL routing (Optional, may not be necessary) */
	if ( ! defined('WP_HOME') && isset($lando_info->appserver_nginx->urls[0]) ) {
		define('WP_HOME', $lando_info->appserver_nginx->urls[0] );
	}
	if ( ! defined('WP_SITEURL') && isset($lando_info->appserver_nginx->urls[0]) ) {
		define('WP_SITEURL', $lando_info->appserver_nginx->urls[0] );
	}
  }

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define('AUTH_KEY',         'eSR2AH_MXC!`LKW{o|_51XQi`S^.+% ~kxPR/R+<kTax^W^(N?joIx2x|0[!TSrB');
define('SECURE_AUTH_KEY',  'd]w@TpXahzs`PTF68eqM@p7l(NNcvq+RJ>eD+|HK|b5M-C@TH|s&tlz,7=Okn|Tc');
define('LOGGED_IN_KEY',    'GB#/<E1hJKwx<=ts%FQu#eTig8#)1;/26B8+)|;1c%WEt&!Hx{El,CPf}gW;M[T0');
define('NONCE_KEY',        'Cub5~G-|]uPK`}h/@-+$cG_OZSxwqag2sKOYSV!R+$lVNd)Y-{wf4q3lI-~W&S6;');
define('AUTH_SALT',        'Yv@zh K/vTis),@hd%-^;|y67{kW^M[<9y?j) +0[v=%tz+ta3Iv+g6R2)lY<Rp<');
define('SECURE_AUTH_SALT', 'OqOP)Dtxd6Feu:hXfDCFoh tW2pr-N!h|aM@x,Mr|<>420t=h||=dr}^=6+Y;C s');
define('LOGGED_IN_SALT',   'zw>vENAP-8fZMt9r(LO7w?6V>?e.dbo>[zHIu}_*fEA9_IeD$5O=DNY]P~sKun[~');
define('NONCE_SALT',       'DZ1e-[#vllh<L)%q2K4sT27]yZ<t9-,(JAGWLO;NmmslLP/`5u=XC/3O4P[^99Rt');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG', true );
define( 'SCRIPT_DEBUG', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
