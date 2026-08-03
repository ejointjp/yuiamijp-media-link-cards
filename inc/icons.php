<?php
/**
 * アイコン定義（assets/icons.json）の読み込みと SVG の組み立て
 *
 * フロントの render.php とエディターの src/components/StoreIcon.js が
 * 同じ assets/icons.json を参照する。アイコンを足す・差し替えるときは
 * JSON だけを変更すれば両方に反映される。
 *
 * @package su-applink
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * assets/icons.json を読み込む
 *
 * @return array preview と stores を持つ配列。読み込めない場合は空配列。
 */
function sual_get_icons() {
	static $icons = null;

	if ( null !== $icons ) {
		return $icons;
	}

	$icons = array();
	$path  = plugin_dir_path( __DIR__ ) . 'assets/icons.json';

	if ( ! is_readable( $path ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- 配布ファイルの欠損を握りつぶさないため。
			error_log( 'SU Applink: assets/icons.json が読み込めません: ' . $path );
		}
		return $icons;
	}

	// 自プラグイン内の同梱ファイルのため wp_remote_get は不要。
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$decoded = json_decode( (string) file_get_contents( $path ), true );

	if ( ! is_array( $decoded ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- JSON の破損を握りつぶさないため。
			error_log( 'SU Applink: assets/icons.json の JSON を解析できません: ' . $path );
		}
		return $icons;
	}

	$icons = $decoded;

	return $icons;
}

/**
 * ストア種別に対応するストア定義を返す
 *
 * @param string $type ストア種別（app / ebook / podcast など）。
 * @return array label（ストア名）と icon（アイコン名）を持つ配列。定義が無ければ空配列。
 */
function sual_get_store( $type ) {
	$icons  = sual_get_icons();
	$stores = isset( $icons['stores'] ) && is_array( $icons['stores'] ) ? $icons['stores'] : array();

	if ( isset( $stores[ $type ] ) ) {
		return $stores[ $type ];
	}

	return isset( $stores['default'] ) ? $stores['default'] : array();
}

/**
 * アイコン名から svg タグの文字列を組み立てる
 *
 * 戻り値は同梱 JSON 内の自前マークアップのため、出力側でエスケープしない。
 *
 * @param string $name アイコン名（assets/icons.json の icons のキー）。
 * @return string svg タグ。定義が無ければ空文字。
 */
function sual_get_icon_svg( $name ) {
	$icons = sual_get_icons();

	if ( ! is_string( $name ) || ! isset( $icons['icons'][ $name ]['content'] ) ) {
		return '';
	}

	$icon      = $icons['icons'][ $name ];
	$attr      = isset( $icon['attr'] ) && is_array( $icon['attr'] ) ? $icon['attr'] : array();
	$attr_html = '';

	foreach ( $attr as $attr_name => $value ) {
		$attr_html .= sprintf( ' %s="%s"', esc_attr( $attr_name ), esc_attr( $value ) );
	}

	return sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"%1$s>%2$s</svg>',
		$attr_html,
		$icon['content']
	);
}
