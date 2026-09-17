<?php
/**
 * 配信終了アイテムの一括スキャン
 *
 * 管理画面のボタンから REST の /scan を繰り返し呼び、2つのフェーズで進める。
 *
 * 1. collect — 投稿を50件ずつ parse_blocks() で走査し、カードの id と使用記事を集める
 * 2. check   — 集めた id を100件ずつ iTunes lookup へ問い合わせ、返らなかった id を配信終了とする
 *
 * 途中経過はトランジェント yuiamijp_scan_progress に退避し、完走時に inc/status.php の
 * yuiamijp_save_scan_record() で option へ確定保存する。投稿本文は書き換えない。
 *
 * 通信に失敗したり、レスポンスの JSON が壊れていたバッチは判定を保留し、前回の判定を
 * 引き継ぐ。「返らなかった id は配信終了」という判定は、レスポンスが正常なときにだけ使う。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 走査対象の投稿タイプを返す
 *
 * 検索対象から除外されていない投稿タイプに、再利用ブロック（wp_block）を加える。
 * 再利用ブロックの中身は別投稿に保存されるため、明示的に含めないとカードを見落とす。
 *
 * @return string[]
 */
function yuiamijp_scan_post_types() {
	$types             = get_post_types( array( 'exclude_from_search' => false ) );
	$types['wp_block'] = 'wp_block';

	return array_values( $types );
}

/**
 * 走査対象の投稿ステータスを返す
 *
 * internal でないステータスすべて。publish / draft / pending / private に加え、
 * テーマやプラグインが登録したカスタムステータスも含む。auto-draft・inherit（リビジョン）・
 * trash は internal なので除外される。
 *
 * @return string[]
 */
function yuiamijp_scan_post_statuses() {
	return array_values( get_post_stati( array( 'internal' => false ) ) );
}

/**
 * parse_blocks() の結果からカードを再帰的に集める
 *
 * innerBlocks を降りるので、グループやカラムの中にあるカードも拾う。
 * app.id を持たないカードは判定できないため対象外。
 *
 * @param array $blocks  parse_blocks() の戻り値。
 * @param array $items   収集先。ID をキーに title / type / icon / posts を持つ。参照渡し。
 * @param int   $post_id 走査中の投稿 ID。
 */
function yuiamijp_collect_cards( array $blocks, array &$items, $post_id ) {
	foreach ( $blocks as $block ) {
		if ( isset( $block['blockName'] ) && 'yuiamijp/media-link-cards' === $block['blockName'] ) {
			$app = isset( $block['attrs']['app'] ) && is_array( $block['attrs']['app'] ) ? $block['attrs']['app'] : array();
			$id  = isset( $app['id'] ) ? (int) $app['id'] : 0;

			if ( $id > 0 ) {
				if ( ! isset( $items[ $id ] ) ) {
					$items[ $id ] = array(
						'title' => isset( $app['title'] ) ? (string) $app['title'] : '',
						'type'  => isset( $app['type'] ) ? (string) $app['type'] : '',
						'icon'  => isset( $app['iconUrl'] ) ? (string) $app['iconUrl'] : '',
						'posts' => array(),
					);
				}

				if ( ! in_array( (int) $post_id, $items[ $id ]['posts'], true ) ) {
					$items[ $id ]['posts'][] = (int) $post_id;
				}
			}
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			yuiamijp_collect_cards( $block['innerBlocks'], $items, $post_id );
		}
	}
}

/**
 * lookup の結果から、問い合わせた ID のうち見つかったものを返す
 *
 * 結果の trackId と collectionId の両方と突き合わせる。アプリ・ブック・曲は
 * trackId で、アルバム・オーディオブックは collectionId で返るため。
 *
 * @param int[] $requested 問い合わせた ID。
 * @param array $results   lookup の results 配列。
 * @return int[] 見つかった ID。
 */
function yuiamijp_match_lookup_results( array $requested, array $results ) {
	$found = array();

	foreach ( $results as $result ) {
		foreach ( array( 'trackId', 'collectionId' ) as $key ) {
			if ( isset( $result[ $key ] ) ) {
				$found[ (int) $result[ $key ] ] = true;
			}
		}
	}

	$matched = array();

	foreach ( $requested as $id ) {
		if ( isset( $found[ (int) $id ] ) ) {
			$matched[] = (int) $id;
		}
	}

	return $matched;
}

/**
 * iTunes lookup へ問い合わせ、見つかった ID を返す
 *
 * 通信失敗・200以外・JSON の破損はすべて WP_Error で返す。呼び出し側はそのバッチの
 * 判定を保留する。lookup の結果は毎回最新を取るのでトランジェントには載せない。
 *
 * @param int[]  $ids     問い合わせる ID（YUIAMIJP_SCAN_LOOKUP_BATCH 件まで）。
 * @param string $country ストアの国コード。
 * @return int[]|WP_Error 見つかった ID。
 */
function yuiamijp_lookup_ids( array $ids, $country ) {
	$url = add_query_arg(
		array(
			'id'      => implode( ',', array_map( 'intval', $ids ) ),
			'country' => $country,
		),
		'https://itunes.apple.com/lookup'
	);

	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

	if ( is_wp_error( $response ) ) {
		yuiamijp_log_scan_error( $response->get_error_message() );
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		yuiamijp_log_scan_error( 'iTunes lookup returned HTTP ' . $code );
		return new WP_Error( 'yuiamijp_lookup_http', 'iTunes lookup returned HTTP ' . $code );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
		yuiamijp_log_scan_error( 'iTunes lookup returned invalid JSON' );
		return new WP_Error( 'yuiamijp_lookup_json', 'iTunes lookup returned invalid JSON' );
	}

	return yuiamijp_match_lookup_results( $ids, $data['results'] );
}

/**
 * スキャン中のエラーを WP_DEBUG 有効時にログへ残す
 *
 * 管理画面には件数だけを出すので、原因はここで追えるようにしておく。
 *
 * @param string $message ログに残す内容。
 */
function yuiamijp_log_scan_error( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- 通信失敗の原因を握りつぶさないため。
		error_log( 'yuiamijp Media Link Cards: ' . $message );
	}
}

/**
 * 進行中のスキャンの中間データを返す
 *
 * @return array|false 中間データ。なければ false。
 */
function yuiamijp_get_scan_progress() {
	$progress = get_transient( 'yuiamijp_scan_progress' );

	return is_array( $progress ) ? $progress : false;
}

/**
 * 中間データを退避する（1時間で失効）
 *
 * @param array $progress 中間データ。
 */
function yuiamijp_set_scan_progress( array $progress ) {
	set_transient( 'yuiamijp_scan_progress', $progress, HOUR_IN_SECONDS );
}

/**
 * collect フェーズを1ページ分進める
 *
 * @param int $offset 投稿のオフセット。0 なら新しいスキャンとして中間データを捨てる。
 * @return array|WP_Error REST レスポンスの本体。
 */
function yuiamijp_scan_collect( $offset ) {
	$offset = max( 0, (int) $offset );

	if ( 0 === $offset ) {
		$progress = array(
			'items'  => array(),
			'errors' => array(),
		);
	} else {
		$progress = yuiamijp_get_scan_progress();

		if ( false === $progress ) {
			return new WP_Error(
				'yuiamijp_scan_expired',
				__( 'The scan data has expired. Start the scan again.', 'yuiamijp-media-link-cards' ),
				array( 'status' => 409 )
			);
		}
	}

	$query = new WP_Query(
		array(
			'post_type'              => yuiamijp_scan_post_types(),
			'post_status'            => yuiamijp_scan_post_statuses(),
			'posts_per_page'         => YUIAMIJP_SCAN_POSTS_PER_PAGE,
			'offset'                 => $offset,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $query->posts as $post ) {
		if ( ! has_blocks( $post->post_content ) ) {
			continue;
		}

		yuiamijp_collect_cards( parse_blocks( $post->post_content ), $progress['items'], $post->ID );
	}

	$total     = (int) $query->found_posts;
	$processed = min( $total, $offset + count( $query->posts ) );

	if ( $processed < $total && count( $query->posts ) > 0 ) {
		yuiamijp_set_scan_progress( $progress );

		return array(
			'phase'     => 'collect',
			'offset'    => $processed,
			'total'     => $total,
			'processed' => $processed,
			'done'      => false,
			'errors'    => 0,
		);
	}

	// 走査完了。カードが1件もなければここで完走扱いにする。
	if ( empty( $progress['items'] ) ) {
		yuiamijp_save_scan_record( array(), array() );
		delete_transient( 'yuiamijp_scan_progress' );

		return array(
			'phase'     => 'check',
			'offset'    => 0,
			'total'     => 0,
			'processed' => 0,
			'done'      => true,
			'errors'    => 0,
		);
	}

	yuiamijp_set_scan_progress( $progress );

	return array(
		'phase'     => 'check',
		'offset'    => 0,
		'total'     => count( $progress['items'] ),
		'processed' => 0,
		'done'      => false,
		'errors'    => 0,
	);
}

/**
 * check フェーズを1バッチ分進める
 *
 * @param int $offset ID 一覧のインデックス。
 * @return array|WP_Error REST レスポンスの本体。中間データが失効していれば WP_Error。
 */
function yuiamijp_scan_check( $offset ) {
	$offset   = max( 0, (int) $offset );
	$progress = yuiamijp_get_scan_progress();

	if ( false === $progress ) {
		return new WP_Error(
			'yuiamijp_scan_expired',
			__( 'The scan data has expired. Start the scan again.', 'yuiamijp-media-link-cards' ),
			array( 'status' => 409 )
		);
	}

	$ids   = array_keys( $progress['items'] );
	$total = count( $ids );
	$batch = array_slice( $ids, $offset, YUIAMIJP_SCAN_LOOKUP_BATCH );

	if ( ! empty( $batch ) ) {
		$options = get_option( 'yuiamijp-setting' );
		$country = isset( $options['country'] ) ? $options['country'] : 'JP';
		$found   = yuiamijp_lookup_ids( $batch, $country );

		if ( is_wp_error( $found ) ) {
			$progress['errors'] = array_merge( $progress['errors'], $batch );
		} else {
			foreach ( $batch as $id ) {
				$progress['items'][ $id ]['state'] = in_array( (int) $id, $found, true ) ? 'available' : 'unavailable';
			}
		}
	}

	$processed = min( $total, $offset + count( $batch ) );

	if ( $processed < $total ) {
		yuiamijp_set_scan_progress( $progress );

		return array(
			'phase'     => 'check',
			'offset'    => $processed,
			'total'     => $total,
			'processed' => $processed,
			'done'      => false,
			'errors'    => count( $progress['errors'] ),
		);
	}

	// 全バッチ終了。確認できなかった ID は前回の判定を引き継ぐ。前回もなければ unknown。
	$previous = yuiamijp_get_scan_record();

	foreach ( $progress['items'] as $id => $item ) {
		if ( ! isset( $item['state'] ) ) {
			$progress['items'][ $id ]['state'] = isset( $previous['items'][ $id ]['state'] ) ? $previous['items'][ $id ]['state'] : 'unknown';
		}
	}

	yuiamijp_save_scan_record( $progress['items'], $progress['errors'] );
	delete_transient( 'yuiamijp_scan_progress' );

	return array(
		'phase'     => 'check',
		'offset'    => $total,
		'total'     => $total,
		'processed' => $total,
		'done'      => true,
		'errors'    => count( $progress['errors'] ),
	);
}

/**
 * /scan の権限チェック。設定を触れる管理者だけに許可する
 *
 * @return bool
 */
function yuiamijp_rest_scan_permission_check() {
	return current_user_can( 'manage_options' );
}

/**
 * /scan のコールバック
 *
 * @param WP_REST_Request $request リクエスト。phase と offset を持つ。
 * @return WP_REST_Response|WP_Error
 */
function yuiamijp_rest_scan_callback( $request ) {
	$phase  = $request->get_param( 'phase' );
	$offset = (int) $request->get_param( 'offset' );

	$result = 'check' === $phase ? yuiamijp_scan_check( $offset ) : yuiamijp_scan_collect( $offset );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return new WP_REST_Response( $result, 200 );
}
