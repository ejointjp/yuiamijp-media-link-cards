<?php
/**
 * 管理画面の設定ページ
 *
 * @package su-blocks-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 設定画面を「設定」メニューに追加する
 */
function sual_add_admin_page() {
	add_options_page(
		'SU Blocks - Media Link Cards',
		'Media Link Cards',
		'manage_options',
		'su-blocks-media-link-cards',
		'sual_options_page_html'
	);
}
add_action( 'admin_menu', 'sual_add_admin_page' );

/**
 * 設定ページの HTML を出力する
 */
function sual_options_page_html() {
	?>
	<div class="wrap">
	<h2>SU Blocks - Media Link Cards</h2>

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

/**
 * 設定値をサニタイズする
 *
 * @param array $input 送信された設定値。
 * @return array サニタイズ済みの設定値。
 */
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
	// チェックボックスがチェックされている場合はen_us、チェックされていない場合はauto
	if ( isset( $input['lang'] ) && 'en_us' === $input['lang'] ) {
		$sanitized['lang'] = 'en_us';
	} else {
		$sanitized['lang'] = 'auto'; // デフォルト値（チェックボックスがチェックされていない場合）
	}

	return $sanitized;
}

/**
 * 設定項目とセクションを登録する
 */
function sual_page_init() {
	register_setting(
		'sual-setting',
		'sual-setting',
		array(
			'sanitize_callback' => 'sual_sanitize_options',
		)
	);
	add_settings_section( 'sual-setting-section-id', '', '', 'sual-setting' );

	add_settings_field( 'token', __( 'PHG Token', 'su-blocks-media-link-cards' ), 'sual_token_callback', 'sual-setting', 'sual-setting-section-id' );
	add_settings_field( 'limit', __( 'Default Search Results', 'su-blocks-media-link-cards' ), 'sual_limit_callback', 'sual-setting', 'sual-setting-section-id' );
	add_settings_field( 'country', __( 'Default Store Country', 'su-blocks-media-link-cards' ), 'sual_country_callback', 'sual-setting', 'sual-setting-section-id' );
	add_settings_field( 'lang', __( 'Default Language', 'su-blocks-media-link-cards' ), 'sual_lang_callback', 'sual-setting', 'sual-setting-section-id' );
}
add_action( 'admin_init', 'sual_page_init' );

/**
 * PHG トークンの入力欄を出力する
 */
function sual_token_callback() {
	$options = get_option( 'sual-setting' );
	$token   = isset( $options['token'] ) ? $options['token'] : '';
	printf( '<input type="text" name="sual-setting[token]" size="30" value="%s">', esc_attr( $token ) );
}

/**
 * 検索結果数の選択欄を出力する
 */
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

/**
 * 検索対象ストアの国の選択欄を出力する
 */
function sual_country_callback() {
	$options    = get_option( 'sual-setting' );
	$option_val = isset( $options['country'] ) ? $options['country'] : 'JP';
	$values     = sual_get_country_values();

	echo '<select name="sual-setting[country]">';
	foreach ( $values as $item ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $item['value'] ), selected( $option_val, $item['value'], false ), esc_html( $item['label'] ) );
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__( 'Select the country of the App Store to search.', 'su-blocks-media-link-cards' ) . '</p>';
}

/**
 * 表示言語のチェックボックスを出力する
 */
function sual_lang_callback() {
	$options    = get_option( 'sual-setting' );
	$option_val = isset( $options['lang'] ) ? $options['lang'] : 'auto';

	printf(
		'<label><input type="checkbox" id="lang-checkbox" name="sual-setting[lang]" value="en_us" %1$s> %2$s</label>',
		checked( 'en_us', $option_val, false ),
		esc_html__( 'Display cards in English', 'su-blocks-media-link-cards' )
	);
	echo '<p class="description">' . esc_html__( 'If unchecked, the language will be determined automatically.', 'su-blocks-media-link-cards' ) . '</p>';
}
