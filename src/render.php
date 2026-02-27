<?php
/**
 * Render Applink block
 *
 * @package su-applink
 */

$app         = isset( $attributes['app'] ) ? $attributes['app'] : array();
$type        = isset( $app['type'] ) ? $app['type'] : '';
$url         = isset( $app['url'] ) ? esc_url( $app['url'] ) : '';
$title       = isset( $app['title'] ) ? esc_html( $app['title'] ) : '';
$artist      = isset( $app['artist'] ) ? esc_html( $app['artist'] ) : '';
$icon        = isset( $app['iconUrl'] ) ? esc_url( $app['iconUrl'] ) : '';
$preview_url = isset( $app['previewUrl'] ) ? esc_url( $app['previewUrl'] ) : '';

if ( empty( $url ) || empty( $title ) ) {
	return;
}

// アイコンSVGの出力関数
if ( ! function_exists( 'sual_get_store_icon_svg' ) ) {
	function sual_get_store_icon_svg( $type ) {
		$svg   = '';
		$label = '';
		switch ( $type ) {
			case 'app':
				$svg   = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" role="img" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"></path></svg>';
				$label = 'App Store';
				break;
			case 'mac-app':
				$svg   = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" role="img" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"></path></svg>';
				$label = 'Mac App Store';
				break;
			case 'movie':
				$svg   = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" role="img" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"></path></svg>';
				$label = 'TV';
				break;
			case 'ebook':
			case 'audiobook':
				$svg   = '<svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>';
				$label = 'Apple Books';
				break;
			case 'podcast':
				$svg   = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><circle cx="12.01" cy="12" r="2"></circle><path d="M11.01 22h2l.5-7h-3l.5 7z"></path><path d="M12 2a10 10 0 0 0-2.45 19.68l-.15-2.12a8 8 0 1 1 5.21 0l-.15 2.12A10 10 0 0 0 12 2z"></path><path d="M15.32 9.61a3.44 3.44 0 0 1 .37.68 3.83 3.83 0 0 1 .23.75 3.57 3.57 0 0 1 .09.8 3.66 3.66 0 0 1-.09.81 3.83 3.83 0 0 1-.23.75 3.44 3.44 0 0 1-.37.68 4.7 4.7 0 0 1-.35.43l-.19 2.62a5.33 5.33 0 0 0 .58-.31A5.86 5.86 0 0 0 17 15.2a5.57 5.57 0 0 0 .55-1 5.89 5.89 0 0 0 .35-1.13 6.06 6.06 0 0 0 .1-1.23 6.22 6.22 0 0 0-.13-1.21A6.09 6.09 0 0 0 17 8.49a6.29 6.29 0 0 0-.73-.89 5.67 5.67 0 0 0-.89-.73 6.3 6.3 0 0 0-1-.56A6.17 6.17 0 0 0 13.21 6a6.11 6.11 0 0 0-2.41 0 5.51 5.51 0 0 0-1.13.36 5.57 5.57 0 0 0-1 .55 5.67 5.67 0 0 0-.89.73 6.29 6.29 0 0 0-.78.85 6.09 6.09 0 0 0-.9 2.14 6.21 6.21 0 0 0-.1 1.21 6.06 6.06 0 0 0 .12 1.21 5.89 5.89 0 0 0 .35 1.13 5.57 5.57 0 0 0 .55 1 6.24 6.24 0 0 0 1.62 1.62 5.33 5.33 0 0 0 .58.31L9 14.51a4.7 4.7 0 0 1-.35-.43 3.44 3.44 0 0 1-.37-.68 3.83 3.83 0 0 1-.23-.75 3.65 3.65 0 0 1-.05-.81 3.56 3.56 0 0 1 .08-.8 3.83 3.83 0 0 1 .23-.75 3.44 3.44 0 0 1 .37-.68 4 4 0 0 1 .5-.61 3.87 3.87 0 0 1 .59-.48 3.44 3.44 0 0 1 .68-.37 3.86 3.86 0 0 1 .75-.24 4.36 4.36 0 0 1 1.61 0 3.86 3.86 0 0 1 .75.24 3.58 3.58 0 0 1 1.27.85 3.49 3.49 0 0 1 .49.61z"></path></svg>';
				$label = 'Apple Podcast';
				break;
			case 'music-track':
			case 'music-album':
			case 'music-video':
				$svg   = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" role="img" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"></path></svg>';
				$label = 'Music';
				break;
			default:
				$svg   = '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M3,17 C2.44771525,17 2,16.5522847 2,16 C2,15.4477153 2.44771525,15 3,15 L13.5,15 C14.5,15 15.5,17 15,17 L3,17 Z M17,17 C16.4477153,17 16,16.5522847 16,16 C16,15.4477153 16.4477153,15 17,15 L21,15 C21.5522847,15 22,15.4477153 22,16 C22,16.5522847 21.5522847,17 21,17 L17,17 Z M12.6333775,3.50103556 C12.908948,3.02241319 13.5203421,2.85780709 13.9989644,3.13337754 C14.4775868,3.408948 14.6421929,4.02034208 14.3666225,4.49896444 L7.45953351,16.4954873 C7.18396306,16.9741097 6.57256898,17.1387158 6.09394661,16.8631454 C5.61532424,16.5875749 5.45071815,15.9761808 5.7262886,15.4975585 L12.6333775,3.50103556 Z M4,18.5 C4.5,17.5 7.5,16.5 6.5,18.2208385 C6.32221095,18.5267848 5.77775177,19.4528267 4.86662246,20.9989644 C4.591052,21.4775868 3.97965792,21.6421929 3.50103556,21.3666225 C3.02241319,21.091052 2.85780709,20.4796579 3.13337754,20.0010356 L4,18.5 Z M9.13337754,4.49896444 C8.85780709,4.02034208 9.02241319,3.408948 9.50103556,3.13337754 C9.97965792,2.85780709 10.591052,3.02241319 10.8666225,3.50103556 L12.6104584,6.52980328 C12.8860289,7.00842564 12.7214228,7.61981972 12.2428004,7.89539018 C11.764178,8.17096063 11.152784,8.00635453 10.8772135,7.52773217 L9.13337754,4.49896444 Z M13,11.5 C12.1018662,10 13,7 13.7163439,8.49589818 L20.3666225,20.0010356 C20.6421929,20.4796579 20.4775868,21.091052 19.9989644,21.3666225 C19.5203421,21.6421929 18.908948,21.4775868 18.6333775,20.9989644 L13,11.5 Z"></path></svg>';
				$label = 'App Store';
				break;
		}

		return array(
			'svg'   => $svg,
			'label' => $label,
		);
	}
}

$store_info         = sual_get_store_icon_svg( $type );
$wrapper_attributes = get_block_wrapper_attributes();

?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="sual sual-<?php echo esc_attr( $type ); ?>">
		<a class="sual-figure" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
			<img class="sual-img" src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
		</a>
		<div class="sual-content">
			<div class="sual-info">
				<a class="sual-title" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
					<?php echo esc_html( $title ); ?>
				</a>
				<div class="sual-artist"><?php echo esc_html( $artist ); ?></div>
			</div>

			<div class="sual-btns">
				<?php if ( ! empty( $preview_url ) ) : ?>
					<a class="sual-audition sual-btn" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
						<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 20 20" aria-hidden="true" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
						<span class="sual-btn-label"><?php echo esc_html__( 'Preview', 'su-applink' ); ?></span>
					</a>
				<?php endif; ?>
				<a class="sual-store sual-btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
					<?php echo $store_info['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="sual-btn-label"><?php echo esc_html( $store_info['label'] ); ?></span>
				</a>
			</div>
		</div>
	</div>
</div>
