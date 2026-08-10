<?php
/**
 * アンインストール時に設定値を削除する
 *
 * @package su-blocks-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sual-setting' );
