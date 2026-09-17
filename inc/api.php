<?php
/**
 * iTunes Search API を中継する REST API
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register REST API route for yuiamijp Media Link Cards search
 */
function yuiamijp_register_rest_routes() {
	register_rest_route(
		'yuiamijp-media-link-cards/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'yuiamijp_rest_api_search_callback',
			'permission_callback' => 'yuiamijp_rest_api_search_permission_check',
		)
	);

	// 配信終了アイテムの一括スキャン。設定を触れる管理者だけに許可する。
	register_rest_route(
		'yuiamijp-media-link-cards/v1',
		'/scan',
		array(
			'methods'             => 'POST',
			'callback'            => 'yuiamijp_rest_scan_callback',
			'permission_callback' => 'yuiamijp_rest_scan_permission_check',
			'args'                => array(
				'phase'  => array(
					'type'    => 'string',
					'enum'    => array( 'collect', 'check' ),
					'default' => 'collect',
				),
				'offset' => array(
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'yuiamijp_register_rest_routes' );

/**
 * Permission callback for the REST API route
 */
function yuiamijp_rest_api_search_permission_check() {
	// エディター権限以上を持つユーザーのみ許可（あるいは投稿編集権限）
	return current_user_can( 'edit_posts' );
}

/**
 * Callback function for yuiamijp Media Link Cards search API
 *
 * @param WP_REST_Request $request リクエスト。
 * @return WP_REST_Response
 */
function yuiamijp_rest_api_search_callback( $request ) {
	// パラメータの取得
	$term    = $request->get_param( 'term' );
	$entity  = $request->get_param( 'entity' );
	$limit   = $request->get_param( 'limit' );
	$country = $request->get_param( 'country' );
	$lang    = $request->get_param( 'lang' );
	$at      = $request->get_param( 'at' );

	if ( empty( $term ) ) {
		return new WP_REST_Response( array( 'error' => 'term is required' ), 400 );
	}

	// キャッシュキーの生成 (パラメータをJSONにしてハッシュ化)
	$params    = array(
		'term'     => $term,
		'entity  ' => $entity,
		'limit'    => $limit,
		'country'  => $country,
		'lang'     => $lang,
		'at'       => $at,
	);
	$cache_key = 'yuiamijp_search_' . md5( wp_json_encode( $params ) );

	// トランジェント（キャッシュ）の確認
	$cached_data = get_transient( $cache_key );
	if ( false !== $cached_data ) {
		// キャッシュがある場合は `cached: true` フラグを付与して返す
		$cached_data['cached'] = true;
		return new WP_REST_Response( $cached_data, 200 );
	}

	// キャッシュがない場合、iTunes API にリクエストを投げる。
	// add_query_arg は false 以外を落とさないため、未指定のパラメータが
	// at= のように空で残る。送る前に落としておく。
	$api_args = array_filter(
		array(
			'term'    => rawurlencode( $term ),
			'entity'  => $entity,
			'limit'   => $limit,
			'country' => $country,
			'lang'    => $lang,
			'at'      => $at,
		),
		static function ( $value ) {
			return null !== $value && '' !== $value;
		}
	);

	$api_url = add_query_arg( $api_args, 'https://itunes.apple.com/search' );

	$response = wp_remote_get( $api_url, array( 'timeout' => 15 ) );

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response( array( 'error' => 'Failed to fetch data from iTunes API' ), 500 );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $status_code ) {
		return new WP_REST_Response( array( 'error' => 'iTunes API returned error status: ' . $status_code ), $status_code );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( is_null( $data ) ) {
		return new WP_REST_Response( array( 'error' => 'Invalid JSON from iTunes API' ), 500 );
	}

	// キャッシュフラグを含まない状態で保存（12時間キャッシュ = 43200秒）
	set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

	// 今回の結果として返すため、フラグはfalse(または付与しない)にして返す
	$data['cached'] = false;

	return new WP_REST_Response( $data, 200 );
}
