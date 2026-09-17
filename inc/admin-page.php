<?php
/**
 * 管理画面の設定ページ
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 設定画面を「設定」メニューに追加する
 */
function yuiamijp_add_admin_page() {
	add_options_page(
		'yuiamijp Media Link Cards',
		'Media Link Cards',
		'manage_options',
		'yuiamijp-media-link-cards',
		'yuiamijp_options_page_html'
	);
}
add_action( 'admin_menu', 'yuiamijp_add_admin_page' );

/**
 * 設定ページの HTML を出力する
 */
function yuiamijp_options_page_html() {
	?>
	<div class="wrap">
	<h2>yuiamijp Media Link Cards</h2>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'yuiamijp-setting' );
		do_settings_sections( 'yuiamijp-setting' );
		submit_button();
		?>
	</form>

	<?php yuiamijp_render_scan_section(); ?>
	</div>
	<?php
}

/**
 * 設定値をサニタイズする
 *
 * @param array $input 送信された設定値。
 * @return array サニタイズ済みの設定値。
 */
function yuiamijp_sanitize_options( $input ) {
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
function yuiamijp_page_init() {
	register_setting(
		'yuiamijp-setting',
		'yuiamijp-setting',
		array(
			'sanitize_callback' => 'yuiamijp_sanitize_options',
		)
	);
	add_settings_section( 'yuiamijp-setting-section-id', '', '', 'yuiamijp-setting' );

	add_settings_field( 'token', __( 'PHG Token', 'yuiamijp-media-link-cards' ), 'yuiamijp_token_callback', 'yuiamijp-setting', 'yuiamijp-setting-section-id' );
	add_settings_field( 'limit', __( 'Default Search Results', 'yuiamijp-media-link-cards' ), 'yuiamijp_limit_callback', 'yuiamijp-setting', 'yuiamijp-setting-section-id' );
	add_settings_field( 'country', __( 'Default Store Country', 'yuiamijp-media-link-cards' ), 'yuiamijp_country_callback', 'yuiamijp-setting', 'yuiamijp-setting-section-id' );
	add_settings_field( 'lang', __( 'Default Language', 'yuiamijp-media-link-cards' ), 'yuiamijp_lang_callback', 'yuiamijp-setting', 'yuiamijp-setting-section-id' );
}
add_action( 'admin_init', 'yuiamijp_page_init' );

/**
 * PHG トークンの入力欄を出力する
 */
function yuiamijp_token_callback() {
	$options = get_option( 'yuiamijp-setting' );
	$token   = isset( $options['token'] ) ? $options['token'] : '';
	printf( '<input type="text" name="yuiamijp-setting[token]" size="30" value="%s">', esc_attr( $token ) );
}

/**
 * 検索結果数の選択欄を出力する
 */
function yuiamijp_limit_callback() {
	$options    = get_option( 'yuiamijp-setting' );
	$option_val = isset( $options['limit'] ) ? $options['limit'] : 10;
	$values     = yuiamijp_get_limit_values();

	echo '<select name="yuiamijp-setting[limit]">';
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
function yuiamijp_country_callback() {
	$options    = get_option( 'yuiamijp-setting' );
	$option_val = isset( $options['country'] ) ? $options['country'] : 'JP';
	$values     = yuiamijp_get_country_values();

	echo '<select name="yuiamijp-setting[country]">';
	foreach ( $values as $item ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $item['value'] ), selected( $option_val, $item['value'], false ), esc_html( $item['label'] ) );
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__( 'Select the country of the App Store to search.', 'yuiamijp-media-link-cards' ) . '</p>';
}

/**
 * 表示言語のチェックボックスを出力する
 */
function yuiamijp_lang_callback() {
	$options    = get_option( 'yuiamijp-setting' );
	$option_val = isset( $options['lang'] ) ? $options['lang'] : 'auto';

	printf(
		'<label><input type="checkbox" id="lang-checkbox" name="yuiamijp-setting[lang]" value="en_us" %1$s> %2$s</label>',
		checked( 'en_us', $option_val, false ),
		esc_html__( 'Display cards in English', 'yuiamijp-media-link-cards' )
	);
	echo '<p class="description">' . esc_html__( 'If unchecked, the language will be determined automatically.', 'yuiamijp-media-link-cards' ) . '</p>';
}

/**
 * 設定ページでだけ Link check 用のスクリプトを読み込む
 *
 * 表示文字列は PHP 側で翻訳して渡す。このプラグインは .json 翻訳を同梱しないため、
 * JavaScript 側の __() は言語パックの生成状況に左右される。
 *
 * @param string $hook_suffix 現在の管理画面のフック名。
 */
function yuiamijp_enqueue_scan_script( $hook_suffix ) {
	if ( 'settings_page_yuiamijp-media-link-cards' !== $hook_suffix ) {
		return;
	}

	$path = plugin_dir_path( __DIR__ ) . 'assets/admin.js';

	wp_enqueue_script(
		'yuiamijp-scan',
		plugin_dir_url( __DIR__ ) . 'assets/admin.js',
		array( 'wp-api-fetch' ),
		(string) filemtime( $path ),
		true
	);

	wp_localize_script(
		'yuiamijp-scan',
		'yuiamijpScan',
		array(
			'scanUrl' => esc_url_raw( rest_url( 'yuiamijp-media-link-cards/v1/scan' ) ),
			'i18n'    => array(
				'starting'   => __( 'Starting…', 'yuiamijp-media-link-cards' ),
				/* translators: 1: number of posts scanned so far, 2: total number of posts */
				'collecting' => __( 'Scanning posts… %1$d / %2$d', 'yuiamijp-media-link-cards' ),
				/* translators: 1: number of items checked so far, 2: total number of items */
				'checking'   => __( 'Checking items… %1$d / %2$d', 'yuiamijp-media-link-cards' ),
				'done'       => __( 'Scan complete. Reloading…', 'yuiamijp-media-link-cards' ),
				'failed'     => __( 'Scan failed.', 'yuiamijp-media-link-cards' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'yuiamijp_enqueue_scan_script' );

/**
 * Link check セクションを出力する
 *
 * スキャンの実行ボタンと前回の結果。配信終了アイテムの一覧には、使用している
 * 投稿への編集リンクを付ける。再利用ブロックに入っていたカードは、その再利用
 * ブロック自体の編集画面を指す。
 */
function yuiamijp_render_scan_section() {
	$record = yuiamijp_get_scan_record();
	?>
	<h2><?php echo esc_html__( 'Link check', 'yuiamijp-media-link-cards' ); ?></h2>
	<p><?php echo esc_html__( 'Checks every card on this site against Apple\'s catalog and marks the items that are no longer available. Marked cards are shown without links on the front end.', 'yuiamijp-media-link-cards' ); ?></p>
	<p>
		<button type="button" class="button button-secondary" id="yuiamijp-scan-start"><?php echo esc_html__( 'Start scan', 'yuiamijp-media-link-cards' ); ?></button>
		<span id="yuiamijp-scan-status" class="description"></span>
	</p>
	<?php
	if ( empty( $record ) ) {
		echo '<p>' . esc_html__( 'Never scanned.', 'yuiamijp-media-link-cards' ) . '</p>';
		return;
	}

	printf(
		'<p>%s</p>',
		esc_html(
			sprintf(
				/* translators: %s: date and time of the last scan */
				__( 'Last scanned: %s', 'yuiamijp-media-link-cards' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $record['finished'] )
			)
		)
	);

	printf(
		'<p>%s</p>',
		esc_html(
			sprintf(
				/* translators: 1: number of items checked, 2: number of items no longer available */
				__( '%1$d items checked, %2$d no longer available.', 'yuiamijp-media-link-cards' ),
				(int) $record['total'],
				(int) $record['dead']
			)
		)
	);

	if ( ! empty( $record['errors'] ) ) {
		printf(
			'<div class="notice notice-warning inline"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of items that could not be checked */
					__( 'Could not check %d items. Their previous status was kept. Try again later.', 'yuiamijp-media-link-cards' ),
					count( $record['errors'] )
				)
			)
		);
	}

	$dead_items = array_filter(
		$record['items'],
		static function ( $item ) {
			return isset( $item['state'] ) && 'unavailable' === $item['state'];
		}
	);

	if ( empty( $dead_items ) ) {
		return;
	}
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"></th>
				<th scope="col"><?php echo esc_html__( 'Title', 'yuiamijp-media-link-cards' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Type', 'yuiamijp-media-link-cards' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'ID', 'yuiamijp-media-link-cards' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Used in', 'yuiamijp-media-link-cards' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $dead_items as $id => $item ) : ?>
				<?php
				$store = yuiamijp_get_store( $item['type'] );
				$label = isset( $store['label'] ) ? $store['label'] : $item['type'];
				$links = array();

				foreach ( $item['posts'] as $post_id ) {
					$edit_link = get_edit_post_link( $post_id );

					// 削除済みの投稿は飛ばす
					if ( ! $edit_link ) {
						continue;
					}

					$post_title = get_the_title( $post_id );
					$links[]    = sprintf(
						'<a href="%s">%s</a>',
						esc_url( $edit_link ),
						esc_html( '' !== $post_title ? $post_title : '#' . $post_id )
					);
				}
				?>
				<tr>
					<td>
						<?php if ( ! empty( $item['icon'] ) ) : ?>
							<img src="<?php echo esc_url( $item['icon'] ); ?>" alt="" width="40" height="40" />
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $item['title'] ); ?></td>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo esc_html( (string) $id ); ?></td>
					<td><?php echo wp_kses_post( implode( ', ', $links ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
