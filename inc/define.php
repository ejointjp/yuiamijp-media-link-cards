<?php

// 検索結果数オプション
define(
	'SUAL_LIMIT_VALUES',
	array(
		array(
			'value' => 10,
			'label' => __( '10 items', 'su-applink' ),
		),
		array(
			'value' => 25,
			'label' => __( '25 items', 'su-applink' ),
		),
		array(
			'value' => 50,
			'label' => __( '50 items', 'su-applink' ),
		),
		array(
			'value' => 100,
			'label' => __( '100 items', 'su-applink' ),
		),
		array(
			'value' => 200,
			'label' => __( '200 items', 'su-applink' ),
		),
	)
);

// 言語設定オプション（自動と英語のみ）
define(
	'SUAL_LANG_VALUES',
	array(
		array(
			'value' => 'auto',
			'label' => __( 'Auto', 'su-applink' ),
		),
		array(
			'value' => 'en_us',
			'label' => __( 'English', 'su-applink' ),
		),
	)
);

// 国コードから言語コードへのマッピング
define(
	'SUAL_COUNTRY_TO_LANG_MAP',
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

// 国設定オプション（主要15カ国、使用頻度順）
define(
	'SUAL_COUNTRY_VALUES',
	array(
		// アジア主要国
		array(
			'value' => 'JP',
			'label' => __( 'Japan', 'su-applink' ),
		),
		array(
			'value' => 'KR',
			'label' => __( 'South Korea', 'su-applink' ),
		),
		array(
			'value' => 'CN',
			'label' => __( 'China', 'su-applink' ),
		),
		array(
			'value' => 'TW',
			'label' => __( 'Taiwan', 'su-applink' ),
		),
		array(
			'value' => 'HK',
			'label' => __( 'Hong Kong', 'su-applink' ),
		),
		// 英語圏主要国
		array(
			'value' => 'US',
			'label' => __( 'United States', 'su-applink' ),
		),
		array(
			'value' => 'GB',
			'label' => __( 'United Kingdom', 'su-applink' ),
		),
		array(
			'value' => 'CA',
			'label' => __( 'Canada', 'su-applink' ),
		),
		array(
			'value' => 'AU',
			'label' => __( 'Australia', 'su-applink' ),
		),
		// その他主要国
		array(
			'value' => 'SG',
			'label' => __( 'Singapore', 'su-applink' ),
		),
		array(
			'value' => 'TH',
			'label' => __( 'Thailand', 'su-applink' ),
		),
		array(
			'value' => 'IN',
			'label' => __( 'India', 'su-applink' ),
		),
		array(
			'value' => 'DE',
			'label' => __( 'Germany', 'su-applink' ),
		),
		array(
			'value' => 'FR',
			'label' => __( 'France', 'su-applink' ),
		),
		array(
			'value' => 'BR',
			'label' => __( 'Brazil', 'su-applink' ),
		),
	)
);
