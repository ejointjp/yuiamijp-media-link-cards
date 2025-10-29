<?php

// 検索結果数オプション
define(
	'SUAL_LIMIT_VALUES',
	array(
		array(
			'value' => 10,
			'label' => '10 items',
		),
		array(
			'value' => 25,
			'label' => '25 items',
		),
		array(
			'value' => 50,
			'label' => '50 items',
		),
		array(
			'value' => 100,
			'label' => '100 items',
		),
		array(
			'value' => 200,
			'label' => '200 items',
		),
	)
);

// 言語設定オプション（自動と英語のみ）
define(
	'SUAL_LANG_VALUES',
	array(
		array(
			'value' => 'auto',
			'label' => 'Auto',
		),
		array(
			'value' => 'en_us',
			'label' => 'English',
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
			'label' => 'Japan',
		),
		array(
			'value' => 'KR',
			'label' => 'South Korea',
		),
		array(
			'value' => 'CN',
			'label' => 'China',
		),
		array(
			'value' => 'TW',
			'label' => 'Taiwan',
		),
		array(
			'value' => 'HK',
			'label' => 'Hong Kong',
		),
		// 英語圏主要国
		array(
			'value' => 'US',
			'label' => 'United States',
		),
		array(
			'value' => 'GB',
			'label' => 'United Kingdom',
		),
		array(
			'value' => 'CA',
			'label' => 'Canada',
		),
		array(
			'value' => 'AU',
			'label' => 'Australia',
		),
		// その他主要国
		array(
			'value' => 'SG',
			'label' => 'Singapore',
		),
		array(
			'value' => 'TH',
			'label' => 'Thailand',
		),
		array(
			'value' => 'IN',
			'label' => 'India',
		),
		array(
			'value' => 'DE',
			'label' => 'Germany',
		),
		array(
			'value' => 'FR',
			'label' => 'France',
		),
		array(
			'value' => 'BR',
			'label' => 'Brazil',
		),
	)
);
