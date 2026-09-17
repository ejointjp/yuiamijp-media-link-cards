<?php
/**
 * アンインストール時に設定値を削除する
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'yuiamijp-setting' );
delete_option( 'yuiamijp-status' );
delete_option( 'yuiamijp-scan' );
