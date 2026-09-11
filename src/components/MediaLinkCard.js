import { PreviewIcon, StoreIcon } from './StoreIcon';
import { __ } from '@wordpress/i18n';

const MediaLinkCard = ( { app, isEditor = false } ) => {
	const LinkWrapper = ( { href, children, className, ...props } ) => {
		if ( isEditor ) {
			return <span className={ className }>{ children }</span>;
		}
		return (
			<a href={ href } className={ className } { ...props }>
				{ children }
			</a>
		);
	};

	return (
		<div className={ `yuiamijp yuiamijp-${ app.type }` }>
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
					{ app.previewUrl && (
						<LinkWrapper
							className="yuiamijp-audition yuiamijp-btn"
							href={ app.previewUrl }
							target="_blank"
							rel="noopener nofollow noreferrer"
						>
							<PreviewIcon />
							<span className="yuiamijp-btn-label">
								{ __( 'Preview', 'yuiamijp-media-link-cards' ) }
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
				</div>
			</div>
		</div>
	);
};

export default MediaLinkCard;
