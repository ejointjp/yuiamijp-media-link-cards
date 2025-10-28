<?php

/**
 * Plugin Name:       SU Blocks - Applink
 * Description:       Easily create promotional links for iPhone / iPad / Mac apps, music tracks, Apple Books, and more.
 * Requires at least: 6.8.3
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Takashi Fujiskai
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       su-applink
 *
 * @package           su-applink
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'inc/define.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/admin-page.php';


function sual_init() {
	register_block_type( __DIR__ . '/build' );

	// Load plugin text domain
	load_plugin_textdomain( 'su-applink', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'sual_init' );

/**
 * Categories
 *
 * @param array $categories Categories.
 * @param array $post Post.
 */
function sual_categories( $categories, $post ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'su-blocks',
				'title' => __( 'SU Blocks', 'su-applink' ),
			),
		)
	);
}
add_filter( 'block_categories_all', 'sual_categories', 10, 2 );



// オプション値の初期化
function sual_register_activation() {
	$options = get_option( 'sual-setting' );

	if ( ! $options ) {
		$default = array(
			'token'   => '11l64V',
			'country' => 'JP',
			'lang'    => 'auto',
		);

		update_option( 'sual-setting', $default );
	}
}
// プラグイン有効時に実行
register_activation_hook( __FILE__, 'sual_register_activation' );

function sual_admin_enqueue_scripts() {
	// PHPからJavaScriptに値を渡す
	wp_add_inline_script(
		'wp-block-editor',
		'const sualAjaxValues = ' . wp_json_encode(
			array(
				'optionsPageUrl'   => admin_url( 'options-general.php?page=su-applink' ),
				'options'          => get_option( 'sual-setting' ),
				'limitValues'      => SUAL_LIMIT_VALUES,
				'countryValues'    => SUAL_COUNTRY_VALUES,
				'langValues'       => SUAL_LANG_VALUES,
				'countryToLangMap' => SUAL_COUNTRY_TO_LANG_MAP,
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'sual_admin_enqueue_scripts' );
