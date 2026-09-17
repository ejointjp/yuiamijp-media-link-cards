<?php

/**
 * Plugin Name:       yuiamijp Media Link Cards
 * Description:       Easily create promotional links for iPhone / iPad / Mac apps, music tracks, Apple Books, and more.
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Takashi Fujisaki
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yuiamijp-media-link-cards
 * Domain Path:       /languages
 *
 * @package           yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


require_once plugin_dir_path( __FILE__ ) . 'inc/define.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/icons.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/status.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/scan.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/api.php';

/**
 * ブロックを登録し、エディター用スクリプトに翻訳を紐付ける
 *
 * 翻訳ファイルは同梱せず、translate.wordpress.org が配信する言語パックを使う。
 * パスを渡さないことで WordPress が WP_LANG_DIR/plugins を参照する。
 */
function yuiamijp_init() {
	$block_type = register_block_type( __DIR__ . '/build' );

	if ( ! is_wp_error( $block_type ) && function_exists( 'wp_set_script_translations' ) ) {
		$script_handles = array_merge(
			(array) $block_type->editor_script_handles,
			(array) $block_type->script_handles
		);

		foreach ( $script_handles as $handle ) {
			wp_set_script_translations( $handle, 'yuiamijp-media-link-cards' );
		}
	}
}
add_action( 'init', 'yuiamijp_init' );

/**
 * ブロックカテゴリーに yuiamijp を追加する
 *
 * 投稿エディターだけでなくサイトエディターとウィジェット画面でも登録する。
 * これらの画面では $editor_context->post が空になるため、post の有無で
 * 絞り込むとカテゴリーが登録されず、ブロックがインサーターのカテゴリー
 * 一覧から消える。
 *
 * @param array $categories 既存のブロックカテゴリー。
 * @return array
 */
function yuiamijp_block_categories( $categories ) {
	// 既存のカテゴリーが存在するかチェック
	$exists = wp_list_pluck( $categories, 'slug' );
	if ( ! in_array( 'yuiamijp', $exists, true ) ) {
		array_push(
			$categories,
			array(
				'slug'  => 'yuiamijp',
				'title' => 'yuiamijp',
			)
		);
	}

	return $categories;
}
add_filter( 'block_categories_all', 'yuiamijp_block_categories' );



/**
 * 旧オプション名 sual-setting から設定を引き継ぐ
 *
 * slug を変えるとディレクトリ名も変わるため、WordPress からは別プラグインと
 * して扱われる。旧プラグインを無効化してこちらを有効化したときに、一度だけ
 * 設定を移す。こちらにすでに設定があれば何もしない。
 *
 * 移行元を sual-setting だけに限るのは、それより前の世代（alfwp-setting /
 * litoal-setting / wpalb-setting）は既定値にアフィリエイトトークンが入って
 * いたため。他人のサイトから引き継ぐと、設定した覚えのないトークンが復活する。
 * sual 世代は wordpress.org で公開していないので、作者の環境にしか存在しない。
 *
 * 旧プラグインを削除すると uninstall.php が走って旧設定も消える。設定を引き
 * 継ぎたい場合は、先にこちらを有効化すること。
 */
function yuiamijp_migrate_legacy_options() {
	if ( get_option( 'yuiamijp-setting' ) ) {
		return;
	}

	$legacy = get_option( 'sual-setting' );

	if ( ! is_array( $legacy ) ) {
		return;
	}

	// 知っているキーだけを拾う。旧世代にあった nocss などは持ち込まない。
	$migrated = array_intersect_key(
		$legacy,
		array_flip( array( 'token', 'limit', 'country', 'lang' ) )
	);

	if ( $migrated ) {
		update_option( 'yuiamijp-setting', $migrated );
	}
}

/**
 * プラグイン有効化時にオプション値を初期化する
 */
function yuiamijp_register_activation() {
	yuiamijp_migrate_legacy_options();

	$options = get_option( 'yuiamijp-setting' );

	if ( ! $options ) {
		// token は空で始める。既定でアフィリエイトトークンを仕込まない。
		$default = array(
			'token'   => '',
			'country' => 'JP',
			'lang'    => 'auto',
		);

		update_option( 'yuiamijp-setting', $default );
	}
}
// プラグイン有効時に実行
register_activation_hook( __FILE__, 'yuiamijp_register_activation' );

/**
 * 管理画面のブロックエディターへ設定値を JavaScript のグローバル変数として渡す
 */
function yuiamijp_admin_enqueue_scripts() {
	$limit_values   = yuiamijp_get_limit_values();
	$country_values = yuiamijp_get_country_values();
	$lang_values    = yuiamijp_get_lang_values();

	// PHPからJavaScriptに値を渡す
	wp_add_inline_script(
		'wp-block-editor',
		'const yuiamijpAjaxValues = ' . wp_json_encode(
			array(
				'optionsPageUrl'   => admin_url( 'options-general.php?page=yuiamijp-media-link-cards' ),
				'options'          => get_option( 'yuiamijp-setting' ),
				'limitValues'      => $limit_values,
				'countryValues'    => $country_values,
				'langValues'       => $lang_values,
				'countryToLangMap' => YUIAMIJP_COUNTRY_TO_LANG_MAP,
				// nonce は wp-api-fetch がコア側で付与するため渡さない。
				'restUrl'          => esc_url_raw( rest_url( 'yuiamijp-media-link-cards/v1/' ) ),
				// 配信終了と判定された ID。エディターのプレビューをフロントと揃えるため。
				'unavailableIds'   => yuiamijp_get_unavailable_ids(),
			)
		) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'yuiamijp_admin_enqueue_scripts' );

/**
 * Add settings link to plugins page
 *
 * @param array $links Existing links.
 * @return array
 */
function yuiamijp_add_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'options-general.php?page=yuiamijp-media-link-cards' ),
		__( 'Settings', 'yuiamijp-media-link-cards' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'yuiamijp_add_action_links' );
