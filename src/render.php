<?php
/**
 * Media Link Card ブロックのフロント側の出力
 *
 * アイコンとストア名は assets/icons.json が単一の情報源。
 * エディター側のプレビューは src/components/MediaLinkCard.js が同じ JSON を読む。
 * マークアップを変えるときは両方を揃えること。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$yuiamijp_app         = isset( $attributes['app'] ) ? $attributes['app'] : array();
$yuiamijp_type        = isset( $yuiamijp_app['type'] ) ? $yuiamijp_app['type'] : '';
$yuiamijp_url         = isset( $yuiamijp_app['url'] ) ? $yuiamijp_app['url'] : '';
$yuiamijp_title       = isset( $yuiamijp_app['title'] ) ? $yuiamijp_app['title'] : '';
$yuiamijp_artist      = isset( $yuiamijp_app['artist'] ) ? $yuiamijp_app['artist'] : '';
$yuiamijp_icon        = isset( $yuiamijp_app['iconUrl'] ) ? $yuiamijp_app['iconUrl'] : '';
$yuiamijp_preview_url = isset( $yuiamijp_app['previewUrl'] ) ? $yuiamijp_app['previewUrl'] : '';

if ( empty( $yuiamijp_url ) || empty( $yuiamijp_title ) ) {
	return;
}

$yuiamijp_icons              = yuiamijp_get_icons();
$yuiamijp_store              = yuiamijp_get_store( $yuiamijp_type );
$yuiamijp_store_label        = isset( $yuiamijp_store['label'] ) ? $yuiamijp_store['label'] : '';
$yuiamijp_store_icon         = isset( $yuiamijp_store['icon'] ) ? $yuiamijp_store['icon'] : '';
$yuiamijp_preview_icon       = isset( $yuiamijp_icons['preview'] ) ? $yuiamijp_icons['preview'] : '';
$yuiamijp_wrapper_attributes = get_block_wrapper_attributes();

?>

<div <?php echo $yuiamijp_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns markup already escaped by WordPress core. ?>>
	<div class="yuiamijp yuiamijp-<?php echo esc_attr( $yuiamijp_type ); ?>">
		<a class="yuiamijp-figure" href="<?php echo esc_url( $yuiamijp_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
			<img class="yuiamijp-img" src="<?php echo esc_url( $yuiamijp_icon ); ?>" alt="<?php echo esc_attr( $yuiamijp_title ); ?>" />
		</a>
		<div class="yuiamijp-content">
			<div class="yuiamijp-info">
				<a class="yuiamijp-title" href="<?php echo esc_url( $yuiamijp_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
					<?php echo esc_html( $yuiamijp_title ); ?>
				</a>
				<div class="yuiamijp-artist"><?php echo esc_html( $yuiamijp_artist ); ?></div>
			</div>

			<div class="yuiamijp-btns">
				<?php if ( ! empty( $yuiamijp_preview_url ) ) : ?>
					<a class="yuiamijp-audition yuiamijp-btn" href="<?php echo esc_url( $yuiamijp_preview_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
						<?php echo wp_kses( yuiamijp_get_icon_svg( $yuiamijp_preview_icon ), yuiamijp_get_svg_allowed_html() ); ?>
						<span class="yuiamijp-btn-label"><?php echo esc_html__( 'Preview', 'yuiamijp-media-link-cards' ); ?></span>
					</a>
				<?php endif; ?>
				<a class="yuiamijp-store yuiamijp-btn" href="<?php echo esc_url( $yuiamijp_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
					<?php echo wp_kses( yuiamijp_get_icon_svg( $yuiamijp_store_icon ), yuiamijp_get_svg_allowed_html() ); ?>
					<span class="yuiamijp-btn-label"><?php echo esc_html( $yuiamijp_store_label ); ?></span>
				</a>
			</div>
		</div>
	</div>
</div>
