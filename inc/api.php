<?php
/**
 * iTunes Search API を中継する REST API
 *
 * @package su-blocks-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register REST API route for SU Blocks - Media Link Cards search
 */
function sual_register_rest_routes() {
	register_rest_route(
		'su-blocks-media-link-cards/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'sual_rest_api_search_callback',
			'permission_callback' => 'sual_rest_api_search_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'sual_register_rest_routes' );

/**
 * Permission callback for the REST API route
 */
function sual_rest_api_search_permission_check() {
	// エディター権限以上を持つユーザーのみ許可（あるいは投稿編集権限）
	return current_user_can( 'edit_posts' );
}

/**
 * Callback function for SU Blocks - Media Link Cards search API
 *
 * @param WP_REST_Request $request リクエスト。
 * @return WP_REST_Response
 */
function sual_rest_api_search_callback( $request ) {
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
	$cache_key = 'sual_search_' . md5( wp_json_encode( $params ) );

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
