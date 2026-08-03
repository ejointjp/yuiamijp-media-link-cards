<?php
/**
 * Applink ブロックのフロント側の出力
 *
 * アイコンとストア名は assets/icons.json が単一の情報源。
 * エディター側のプレビューは src/components/Applink.js が同じ JSON を読む。
 * マークアップを変えるときは両方を揃えること。
 *
 * @package su-applink
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$sual_app         = isset( $attributes['app'] ) ? $attributes['app'] : array();
$sual_type        = isset( $sual_app['type'] ) ? $sual_app['type'] : '';
$sual_url         = isset( $sual_app['url'] ) ? $sual_app['url'] : '';
$sual_title       = isset( $sual_app['title'] ) ? $sual_app['title'] : '';
$sual_artist      = isset( $sual_app['artist'] ) ? $sual_app['artist'] : '';
$sual_icon        = isset( $sual_app['iconUrl'] ) ? $sual_app['iconUrl'] : '';
$sual_preview_url = isset( $sual_app['previewUrl'] ) ? $sual_app['previewUrl'] : '';

if ( empty( $sual_url ) || empty( $sual_title ) ) {
	return;
}

$sual_icons              = sual_get_icons();
$sual_store              = sual_get_store( $sual_type );
$sual_store_label        = isset( $sual_store['label'] ) ? $sual_store['label'] : '';
$sual_store_icon         = isset( $sual_store['icon'] ) ? $sual_store['icon'] : '';
$sual_preview_icon       = isset( $sual_icons['preview'] ) ? $sual_icons['preview'] : '';
$sual_wrapper_attributes = get_block_wrapper_attributes();

?>

<div <?php echo $sual_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="sual sual-<?php echo esc_attr( $sual_type ); ?>">
		<a class="sual-figure" href="<?php echo esc_url( $sual_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
			<img class="sual-img" src="<?php echo esc_url( $sual_icon ); ?>" alt="<?php echo esc_attr( $sual_title ); ?>" />
		</a>
		<div class="sual-content">
			<div class="sual-info">
				<a class="sual-title" href="<?php echo esc_url( $sual_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
					<?php echo esc_html( $sual_title ); ?>
				</a>
				<div class="sual-artist"><?php echo esc_html( $sual_artist ); ?></div>
			</div>

			<div class="sual-btns">
				<?php if ( ! empty( $sual_preview_url ) ) : ?>
					<a class="sual-audition sual-btn" href="<?php echo esc_url( $sual_preview_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
						<?php echo sual_get_icon_svg( $sual_preview_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 自前の定数マークアップ。 ?>
						<span class="sual-btn-label"><?php echo esc_html__( 'Preview', 'su-applink' ); ?></span>
					</a>
				<?php endif; ?>
				<a class="sual-store sual-btn" href="<?php echo esc_url( $sual_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
					<?php echo sual_get_icon_svg( $sual_store_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 自前の定数マークアップ。 ?>
					<span class="sual-btn-label"><?php echo esc_html( $sual_store_label ); ?></span>
				</a>
			</div>
		</div>
	</div>
</div>
