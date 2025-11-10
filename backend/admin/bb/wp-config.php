<?php

//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'VALR3pReWVMAdiZf22E3GbW74xg4ujjSrWujuWxi8yFTKCwLkyG64JTLaZhGn0mk');
//END Really Simple Security key
define( 'WPCACHEHOME', '/home/jumamayo/public_html/wp-content/plugins/wp-super-cache/' );

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
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'jumamayo_panel' );
/** Database username */
define( 'DB_USER', 'jumamayo_panel' );
/** Database password */
define( 'DB_PASSWORD', 'Jm2_9923jsd_pan' );
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
define( 'AUTH_KEY',         'dPHXjwr2few`08wi`$ExSa_[j:cYm{9sxpV*+G%D>zx=yr!6N_*k*Dx^7E^qkz[@' );
define( 'SECURE_AUTH_KEY',  '$}O/9u:.2dRM/QWu?BiKSqWhQ#e8.i%>NmQ1GRGWi-zI#7}RQaL2Dhr[uN(E<Wur' );
define( 'LOGGED_IN_KEY',    'BkSmq@O|gYREMd1 VOnIrK0?.abv%1*h61(z91}IELxNq/rq#Oc<TE/p[eV)f=BM' );
define( 'NONCE_KEY',        '%pBnT_cYOX_{.2PG[4[/EdZTk:<of)]}NDh3-rJNG2chL uTf*x)=ttvE1)^+#PG' );
define( 'AUTH_SALT',        'Rhg,;F1F[R^<Yz{v_eMAT&4}>.T3tzs8eG8osjUt;=)C6Vaxq7IPc,Jt4)DeWLtG' );
define( 'SECURE_AUTH_SALT', 'Y`gK?|?.VzI^nAY`=[QL?B;T5qv^u3.<KO;Q8[cG3af(b?*-{!KV[fLlRt;Bf/Rm' );
define( 'LOGGED_IN_SALT',   'QEsAe*A<_dfM,@: Qui&>y^KGQt1JGUSXWiup>/kap;UFMCIxSOHKQ2,?o6es^E<' );
define( 'NONCE_SALT',       '[@($VmHKwv(D!ruaWW]F*2bz@gfweRgw V?Q:sFsPTEg%52eY:m[.:f6=P rhh+%' );
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
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_MEMORY_LIMIT', '1024M');

@ini_set( 'display_errors', 0);

/* Add any custom values between this line and the "stop editing" line. */
define('DISABLE_WP_CRON', false);

/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */

require_once ABSPATH . 'wp-settings.php';