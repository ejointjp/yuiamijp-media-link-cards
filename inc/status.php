<?php
/**
 * 配信終了アイテムの判定結果の読み書き
 *
 * option は2つ。
 *
 * - yuiamijp-status — フロントが読む配信終了 ID の一覧。生存分は持たない
 * - yuiamijp-scan   — 管理画面が読むスキャンの記録（全 ID の状態と使用記事）
 *
 * どちらも autoload しない。yuiamijp-status はカードを含むページでだけ読まれ、
 * 1リクエストにつき1回 static に保持する。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * スキャンの記録を返す
 *
 * @return array yuiamijp-scan の内容。未実行なら空配列。
 */
function yuiamijp_get_scan_record() {
	$record = get_option( 'yuiamijp-scan' );

	return is_array( $record ) ? $record : array();
}

/**
 * 配信終了と判定された ID の一覧を返す
 *
 * @return int[]
 */
function yuiamijp_get_unavailable_ids() {
	static $ids = null;

	if ( null !== $ids ) {
		return $ids;
	}

	$status = get_option( 'yuiamijp-status' );
	$ids    = array();

	if ( is_array( $status ) && isset( $status['ids'] ) && is_array( $status['ids'] ) ) {
		$ids = array_map( 'intval', $status['ids'] );
	}

	return $ids;
}

/**
 * ID が配信終了と判定されているかを返す
 *
 * @param int|string $id カードの app.id（trackId または collectionId）。
 * @return bool
 */
function yuiamijp_is_unavailable( $id ) {
	$id = (int) $id;

	if ( $id <= 0 ) {
		return false;
	}

	return in_array( $id, yuiamijp_get_unavailable_ids(), true );
}

/**
 * スキャン結果を確定保存する
 *
 * yuiamijp-scan に全体を保存し、state が unavailable の ID だけを
 * yuiamijp-status へ書き出す。
 *
 * @param array $items  ID をキーに state / title / type / icon / posts を持つ配列。
 * @param int[] $errors 通信失敗で確認できなかった ID。
 * @return array 保存した yuiamijp-scan の内容。
 */
function yuiamijp_save_scan_record( array $items, array $errors ) {
	$dead = array();

	foreach ( $items as $id => $item ) {
		if ( isset( $item['state'] ) && 'unavailable' === $item['state'] ) {
			$dead[] = (int) $id;
		}
	}

	$now    = time();
	$record = array(
		'finished' => $now,
		'total'    => count( $items ),
		'dead'     => count( $dead ),
		'errors'   => array_values( array_map( 'intval', $errors ) ),
		'items'    => $items,
	);

	update_option( 'yuiamijp-scan', $record, false );
	update_option(
		'yuiamijp-status',
		array(
			'ids'     => $dead,
			'checked' => $now,
		),
		false
	);

	return $record;
}
