<?php

/**
 * Plugin Name:       SU Blocks - Applink
 * Description:       Easily create promotional links for iPhone / iPad / Mac apps, music tracks, Apple Books, and more.
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Takashi Fujisaki
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
require_once plugin_dir_path( __FILE__ ) . 'inc/icons.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/api.php';

/**
 * ブロックを登録し、エディター用スクリプトに翻訳を紐付ける
 */
function sual_init() {
	$block_type = register_block_type( __DIR__ . '/build' );

	if ( ! is_wp_error( $block_type ) && function_exists( 'wp_set_script_translations' ) ) {
		$script_handles = array_merge(
			(array) $block_type->editor_script_handles,
			(array) $block_type->script_handles
		);

		foreach ( $script_handles as $handle ) {
			wp_set_script_translations( $handle, 'su-applink', plugin_dir_path( __FILE__ ) . 'languages' );
		}
	}
}
add_action( 'init', 'sual_init' );

/**
 * ブロックカテゴリーに SU Blocks を追加する
 *
 * 投稿エディターだけでなくサイトエディターとウィジェット画面でも登録する。
 * これらの画面では $editor_context->post が空になるため、post の有無で
 * 絞り込むとカテゴリーが登録されず、ブロックがインサーターのカテゴリー
 * 一覧から消える。
 *
 * @param array $categories 既存のブロックカテゴリー。
 * @return array
 */
function sual_block_categories( $categories ) {
	// 既存のカテゴリーが存在するかチェック
	$exists = wp_list_pluck( $categories, 'slug' );
	if ( ! in_array( 'su-blocks', $exists, true ) ) {
		array_push(
			$categories,
			array(
				'slug'  => 'su-blocks',
				'title' => 'SU Blocks',
			)
		);
	}

	return $categories;
}
add_filter( 'block_categories_all', 'sual_block_categories' );



/**
 * プラグイン有効化時にオプション値を初期化する
 */
function sual_register_activation() {
	$options = get_option( 'sual-setting' );

	if ( ! $options ) {
		// token は空で始める。既定でアフィリエイトトークンを仕込まない。
		$default = array(
			'token'   => '',
			'country' => 'JP',
			'lang'    => 'auto',
		);

		update_option( 'sual-setting', $default );
	}
}
// プラグイン有効時に実行
register_activation_hook( __FILE__, 'sual_register_activation' );

/**
 * 管理画面のブロックエディターへ設定値を JavaScript のグローバル変数として渡す
 */
function sual_admin_enqueue_scripts() {
	$limit_values   = sual_get_limit_values();
	$country_values = sual_get_country_values();
	$lang_values    = sual_get_lang_values();

	// PHPからJavaScriptに値を渡す
	wp_add_inline_script(
		'wp-block-editor',
		'const sualAjaxValues = ' . wp_json_encode(
			array(
				'optionsPageUrl'   => admin_url( 'options-general.php?page=su-applink' ),
				'options'          => get_option( 'sual-setting' ),
				'limitValues'      => $limit_values,
				'countryValues'    => $country_values,
				'langValues'       => $lang_values,
				'countryToLangMap' => SUAL_COUNTRY_TO_LANG_MAP,
				// nonce は wp-api-fetch がコア側で付与するため渡さない。
				'restUrl'          => esc_url_raw( rest_url( 'su-applink/v1/' ) ),
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'sual_admin_enqueue_scripts' );

/**
 * Add settings link to plugins page
 *
 * @param array $links Existing links.
 * @return array
 */
function sual_add_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'options-general.php?page=su-applink' ),
		__( 'Settings', 'su-applink' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sual_add_action_links' );
