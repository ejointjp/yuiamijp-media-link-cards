<?php
/**
 * 定数と選択肢の定義
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// 国コードから言語コードへのマッピング
define(
	'YUIAMIJP_COUNTRY_TO_LANG_MAP',
	array(
		'JP' => 'ja_jp',
		'KR' => 'ko_kr',
		'CN' => 'zh_cn',
		'TW' => 'zh_tw',
		'HK' => 'zh_tw',
		'US' => 'en_us',
		'GB' => 'en_us',
		'CA' => 'en_us',
		'AU' => 'en_us',
		'SG' => 'en_us',
		'TH' => 'en_us',
		'IN' => 'en_us',
		'DE' => 'de_de',
		'FR' => 'fr_fr',
		'BR' => 'pt_pt',
	)
);

/**
 * Get limit select options with translated labels.
 *
 * @return array
 */
function yuiamijp_get_limit_values() {
	$values = array(
		array(
			'value' => 10,
			'label' => __( '10 items', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 25,
			'label' => __( '25 items', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 50,
			'label' => __( '50 items', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 100,
			'label' => __( '100 items', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 200,
			'label' => __( '200 items', 'yuiamijp-media-link-cards' ),
		),
	);

	return $values;
}

/**
 * Get language select options with translated labels.
 *
 * @return array
 */
function yuiamijp_get_lang_values() {
	return array(
		array(
			'value' => 'auto',
			'label' => __( 'Auto', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'en_us',
			'label' => __( 'English', 'yuiamijp-media-link-cards' ),
		),
	);
}

/**
 * Get country select options with translated labels.
 *
 * @return array
 */
function yuiamijp_get_country_values() {
	return array(
		// アジア主要国
		array(
			'value' => 'JP',
			'label' => __( 'Japan', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'KR',
			'label' => __( 'South Korea', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'CN',
			'label' => __( 'China', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'TW',
			'label' => __( 'Taiwan', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'HK',
			'label' => __( 'Hong Kong', 'yuiamijp-media-link-cards' ),
		),
		// 英語圏主要国
		array(
			'value' => 'US',
			'label' => __( 'United States', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'GB',
			'label' => __( 'United Kingdom', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'CA',
			'label' => __( 'Canada', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'AU',
			'label' => __( 'Australia', 'yuiamijp-media-link-cards' ),
		),
		// その他主要国
		array(
			'value' => 'SG',
			'label' => __( 'Singapore', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'TH',
			'label' => __( 'Thailand', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'IN',
			'label' => __( 'India', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'DE',
			'label' => __( 'Germany', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'FR',
			'label' => __( 'France', 'yuiamijp-media-link-cards' ),
		),
		array(
			'value' => 'BR',
			'label' => __( 'Brazil', 'yuiamijp-media-link-cards' ),
		),
	);
}

// 配信終了スキャンのバッチサイズ。
// 投稿は50件ずつ parse_blocks() で走査し、ID は100件ずつ lookup へ問い合わせる。
// lookup は id をカンマ区切りで受け、結果は limit に切られない（168件で実測済み）。
// 100件で URL は約1150文字。
define( 'YUIAMIJP_SCAN_POSTS_PER_PAGE', 50 );
define( 'YUIAMIJP_SCAN_LOOKUP_BATCH', 100 );
