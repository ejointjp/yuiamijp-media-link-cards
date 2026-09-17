/**
 * カードのプレビュー
 *
 * マークアップはフロントの src/render.php と二重管理。クラス名や構造を変えるときは
 * 両方を直す。
 *
 * unavailable が true のカード（配信終了）はリンクを外し、試聴とストアのボタンの
 * 代わりに「配信終了」ラベルを出す。
 */
import { PreviewIcon, StoreIcon } from './StoreIcon';
import { __ } from '@wordpress/i18n';

const MediaLinkCard = ( { app, isEditor = false, unavailable = false } ) => {
	const LinkWrapper = ( { href, children, className, ...props } ) => {
		if ( isEditor || unavailable ) {
			return <span className={ className }>{ children }</span>;
		}
		return (
			<a href={ href } className={ className } { ...props }>
				{ children }
			</a>
		);
	};

	const className = `yuiamijp yuiamijp-${ app.type }${
		unavailable ? ' yuiamijp-unavailable' : ''
	}`;

	return (
		<div className={ className }>
			<LinkWrapper
				className="yuiamijp-figure"
				href={ app.url }
				target="_blank"
				rel="noopener nofollow noreferrer"
			>
				<img
					className="yuiamijp-img"
					src={ app.iconUrl }
					alt={ app.title }
				/>
			</LinkWrapper>
			<div className="yuiamijp-content">
				<div className="yuiamijp-info">
					<LinkWrapper
						className="yuiamijp-title"
						href={ app.url }
						target="_blank"
						rel="noopener nofollow noreferrer"
					>
						{ app.title }
					</LinkWrapper>
					<div className="yuiamijp-artist">{ app.artist }</div>
				</div>

				<div className="yuiamijp-btns">
					{ unavailable ? (
						<span className="yuiamijp-ended yuiamijp-btn">
							<span className="yuiamijp-btn-label">
								{ __(
									'No longer available',
									'yuiamijp-media-link-cards'
								) }
							</span>
						</span>
					) : (
						<>
							{ app.previewUrl && (
								<LinkWrapper
									className="yuiamijp-audition yuiamijp-btn"
									href={ app.previewUrl }
									target="_blank"
									rel="noopener nofollow noreferrer"
								>
									<PreviewIcon />
									<span className="yuiamijp-btn-label">
										{ __(
											'Preview',
											'yuiamijp-media-link-cards'
										) }
									</span>
								</LinkWrapper>
							) }
							<LinkWrapper
								className="yuiamijp-store yuiamijp-btn"
								href={ app.url }
								target="_blank"
								rel="noopener nofollow noreferrer"
							>
								<StoreIcon type={ app.type } />
							</LinkWrapper>
						</>
					) }
				</div>
			</div>
		</div>
	);
};

export default MediaLinkCard;
