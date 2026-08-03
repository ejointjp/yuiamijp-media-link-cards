import { PreviewIcon, StoreIcon } from './StoreIcon';
import { __ } from '@wordpress/i18n';

const Applink = ( { app, isEditor = false } ) => {
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
		<div className={ `sual sual-${ app.type }` }>
			<LinkWrapper
				className="sual-figure"
				href={ app.url }
				target="_blank"
				rel="noopener nofollow noreferrer"
			>
				<img
					className="sual-img"
					src={ app.iconUrl }
					alt={ app.title }
				/>
			</LinkWrapper>
			<div className="sual-content">
				<div className="sual-info">
					<LinkWrapper
						className="sual-title"
						href={ app.url }
						target="_blank"
						rel="noopener nofollow noreferrer"
					>
						{ app.title }
					</LinkWrapper>
					<div className="sual-artist">{ app.artist }</div>
				</div>

				<div className="sual-btns">
					{ app.previewUrl && (
						<LinkWrapper
							className="sual-audition sual-btn"
							href={ app.previewUrl }
							target="_blank"
							rel="noopener nofollow noreferrer"
						>
							<PreviewIcon />
							<span className="sual-btn-label">
								{ __( 'Preview', 'su-applink' ) }
							</span>
						</LinkWrapper>
					) }
					<LinkWrapper
						className="sual-store sual-btn"
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

export default Applink;
