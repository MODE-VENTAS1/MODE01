<?php
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

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbv3nun7duadxk' );

/** MySQL database username */
define( 'DB_USER', 'uxmezhsyskxfc' );

/** MySQL database password */
define( 'DB_PASSWORD', 'vrsdd4z8r6ax' );

/** MySQL hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '3_,*o^#M12F$WIci2H-ozD I#slA-)pQ)}]}_cHUTxa<<PL[qK<IwJb}JLpdNuW ' );
define( 'SECURE_AUTH_KEY',   '_3aLa`Z8^~pibAsKKtX?khei?ov}u{Ss02MB8ZL$]W&mXP9*ze))t/):_Y~#yg26' );
define( 'LOGGED_IN_KEY',     ':>)2lxjvxSmoKgE0(4$|du0eMC*K18f^!c(nL.X.hX jpe>p(QC [a@$p`uy9/%v' );
define( 'NONCE_KEY',         '7irTluG),VOTC#Bqz<-.>.8X *SHb|/nENcqo]LLi|R@0z5(&^]HbapPTk&XEH9H' );
define( 'AUTH_SALT',         'No3WK?P+JCHp~7<LltFtlhr>mXIVv3_^ge1~ b?_XP h~<6[jj6swcu{<I%2yP A' );
define( 'SECURE_AUTH_SALT',  '7P[lc$v-}l9sV;x8B(1UYzU?RW`L[@u2&MwuF.RnS?%<hbhn,)/h4E 1&FlQ0m?{' );
define( 'LOGGED_IN_SALT',    '3o5KQZ%xZRPoFs[(syiNw|xId>kMl>:x8CQMW[>V|0%%vX#S^9P`F2jE2A@QO/I/' );
define( 'NONCE_SALT',        'RI2jZO))HrZSi<ywcc9,P0Rr^+FZalbeU{n#X,+OewY[N_{}>TD/8P402/h!iY~D' );
define( 'WP_CACHE_KEY_SALT', '`/Gy FbH/2CxJB,]nlT[<&UY:{{QlC]>m1B{*-VsGZM11dB-%g2=]{+x6IjrS0/-' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'ibs_';




/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
