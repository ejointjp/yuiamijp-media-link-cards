<?php
/**
 * アンインストール時に設定値を削除する
 *
 * @package su-applink
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sual-setting' );
