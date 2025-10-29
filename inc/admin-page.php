<?php

// 管理画面に設定画面を追加
function sual_add_admin_page() {
	add_options_page(
		'SU Applink',
		'SU Applink',
		'manage_options',
		'su-applink',
		'sual_options_page_html'
	);
}
add_action( 'admin_menu', 'sual_add_admin_page' );

// ページの内容
function sual_options_page_html() {
	?>
	<div class="wrap">
	<h2>SU Blocks - Applink</h2>

	<?php
	global $parent_file;
	if ( 'options-general.php' !== $parent_file ) {
		require_once ABSPATH . 'wp-admin/options-head.php';
	}
	?>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'sual-setting' );
		do_settings_sections( 'sual-setting' );
		submit_button();
		?>
	</form>
	</div>
	<?php
}

// オプション値のサニタイズ
function sual_sanitize_options( $input ) {
	$sanitized = array();

	// トークンのサニタイズ（英数字のみ許可）
	if ( isset( $input['token'] ) ) {
		$sanitized['token'] = sanitize_text_field( $input['token'] );
	}

	// limitのサニタイズ（許可された値のみ）
	$allowed_limits = array( 10, 25, 50, 100, 200 );
	if ( isset( $input['limit'] ) && in_array( (int) $input['limit'], $allowed_limits, true ) ) {
		$sanitized['limit'] = (int) $input['limit'];
	} else {
		$sanitized['limit'] = 10; // デフォルト値
	}

	// countryのサニタイズ（許可された値のみ）
	$allowed_countries = array( 'JP', 'KR', 'CN', 'TW', 'HK', 'US', 'GB', 'CA', 'AU', 'SG', 'TH', 'IN', 'DE', 'FR', 'BR' );
	if ( isset( $input['country'] ) && in_array( $input['country'], $allowed_countries, true ) ) {
		$sanitized['country'] = sanitize_text_field( $input['country'] );
	} else {
		$sanitized['country'] = 'JP'; // デフォルト値
	}

	// langのサニタイズ（許可された値のみ）
	$allowed_langs = array( 'auto', 'en_us' );
	if ( isset( $input['lang'] ) && in_array( $input['lang'], $allowed_langs, true ) ) {
		$sanitized['lang'] = sanitize_text_field( $input['lang'] );
	} else {
		$sanitized['lang'] = 'auto'; // デフォルト値
	}

	return $sanitized;
}

// ページの初期化
function sual_page_init() {
	register_setting(
		'sual-setting',
		'sual-setting',
		array(
			'sanitize_callback' => 'sual_sanitize_options',
		)
	);
	add_settings_section( 'sual-setting-section-id', '', '', 'sual-setting' );

	add_settings_field( 'token', __( 'PHG Token', 'su-applink' ), 'sual_token_callback', 'sual-setting', 'sual-setting-section-id' );
	add_settings_field( 'limit', __( 'Default Search Results', 'su-applink' ), 'sual_limit_callback', 'sual-setting', 'sual-setting-section-id' );
	add_settings_field( 'country', __( 'Default Store Country', 'su-applink' ), 'sual_country_callback', 'sual-setting', 'sual-setting-section-id' );
	add_settings_field( 'lang', __( 'Default Language', 'su-applink' ), 'sual_lang_callback', 'sual-setting', 'sual-setting-section-id' );
}
add_action( 'admin_init', 'sual_page_init' );

// トークンの設定セクション
function sual_token_callback() {
	$options = get_option( 'sual-setting' );
	$token   = isset( $options['token'] ) ? $options['token'] : '';
	printf( '<input type="text" name="sual-setting[token]" size="30" value="%s">', esc_attr( $token ) );
}

// 検索結果数の設定セクション
function sual_limit_callback() {
	$options    = get_option( 'sual-setting' );
	$option_val = isset( $options['limit'] ) ? $options['limit'] : 10;
	$values     = sual_get_limit_values();

	echo '<select name="sual-setting[limit]">';
	foreach ( $values as $val ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $val['value'] ),
			selected( (string) $option_val, (string) $val['value'], false ),
			esc_html( $val['label'] )
		);
	}
	echo '</select>';
}

// 国の設定セクション
function sual_country_callback() {
	$options    = get_option( 'sual-setting' );
	$option_val = isset( $options['country'] ) ? $options['country'] : 'JP';
	$values     = sual_get_country_values();

	echo '<select name="sual-setting[country]">';
	foreach ( $values as $item ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $item['value'] ), selected( $option_val, $item['value'], false ), esc_html( $item['label'] ) );
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__( 'Select the country of the App Store to search.', 'su-applink' ) . '</p>';
}

// 言語の設定セクション
function sual_lang_callback() {
	$options    = get_option( 'sual-setting' );
	$option_val = isset( $options['lang'] ) ? $options['lang'] : 'auto';
	$values     = sual_get_lang_values();

	echo '<select name="sual-setting[lang]">';
	foreach ( $values as $item ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $item['value'] ), selected( $option_val, $item['value'], false ), esc_html( $item['label'] ) );
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__( 'Select the display language for Applink.', 'su-applink' ) . '</p>';
}
