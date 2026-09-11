<?php
/**
 * アイコン定義（assets/icons.json）の読み込みと SVG の組み立て
 *
 * フロントの render.php とエディターの src/components/StoreIcon.js が
 * 同じ assets/icons.json を参照する。アイコンを足す・差し替えるときは
 * JSON だけを変更すれば両方に反映される。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * assets/icons.json を読み込む
 *
 * @return array preview と stores を持つ配列。読み込めない場合は空配列。
 */
function yuiamijp_get_icons() {
	static $icons = null;

	if ( null !== $icons ) {
		return $icons;
	}

	$icons = array();
	$path  = plugin_dir_path( __DIR__ ) . 'assets/icons.json';

	if ( ! is_readable( $path ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- 配布ファイルの欠損を握りつぶさないため。
			error_log( 'yuiamijp Media Link Cards: assets/icons.json が読み込めません: ' . $path );
		}
		return $icons;
	}

	// 自プラグイン内の同梱ファイルのため wp_remote_get は不要。
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$decoded = json_decode( (string) file_get_contents( $path ), true );

	if ( ! is_array( $decoded ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- JSON の破損を握りつぶさないため。
			error_log( 'yuiamijp Media Link Cards: assets/icons.json の JSON を解析できません: ' . $path );
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
function yuiamijp_get_store( $type ) {
	$icons  = yuiamijp_get_icons();
	$stores = isset( $icons['stores'] ) && is_array( $icons['stores'] ) ? $icons['stores'] : array();

	if ( isset( $stores[ $type ] ) ) {
		return $stores[ $type ];
	}

	return isset( $stores['default'] ) ? $stores['default'] : array();
}

/**
 * wp_kses() に渡す svg 用の許可タグ定義を返す
 *
 * assets/icons.json のマークアップを出力するために必要な要素と属性だけを許可する。
 * script / style / use / foreignObject と、on* のイベント属性は含めない。
 *
 * 属性名は wp_kses が小文字化して照合するためすべて小文字で書く。
 * viewBox は viewbox として出力されるが、HTML パーサーが SVG 用に
 * 正規の綴りへ戻すため表示には影響しない。
 *
 * @return array wp_kses 用の許可タグ配列。
 */
function yuiamijp_get_svg_allowed_html() {
	$shape_attr = array(
		'fill'             => true,
		'fill-rule'        => true,
		'fill-opacity'     => true,
		'clip-rule'        => true,
		'stroke'           => true,
		'stroke-width'     => true,
		'stroke-linecap'   => true,
		'stroke-linejoin'  => true,
		'stroke-opacity'   => true,
		'stroke-dasharray' => true,
		'opacity'          => true,
		'transform'        => true,
	);

	return array(
		'svg'      => array_merge(
			$shape_attr,
			array(
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'role'        => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'class'       => true,
			)
		),
		'g'        => $shape_attr,
		'path'     => array_merge( $shape_attr, array( 'd' => true ) ),
		'circle'   => array_merge(
			$shape_attr,
			array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			)
		),
		'ellipse'  => array_merge(
			$shape_attr,
			array(
				'cx' => true,
				'cy' => true,
				'rx' => true,
				'ry' => true,
			)
		),
		'rect'     => array_merge(
			$shape_attr,
			array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
			)
		),
		'line'     => array_merge(
			$shape_attr,
			array(
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			)
		),
		'polygon'  => array_merge( $shape_attr, array( 'points' => true ) ),
		'polyline' => array_merge( $shape_attr, array( 'points' => true ) ),
	);
}

/**
 * アイコン名から svg タグの文字列を組み立てる
 *
 * 戻り値は出力側で yuiamijp_get_svg_allowed_html() を使って wp_kses() に通すこと。
 *
 * @param string $name アイコン名（assets/icons.json の icons のキー）。
 * @return string svg タグ。定義が無ければ空文字。
 */
function yuiamijp_get_icon_svg( $name ) {
	$icons = yuiamijp_get_icons();

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
