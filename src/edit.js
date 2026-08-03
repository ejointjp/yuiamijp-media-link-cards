import {
	useBlockProps,
	PlainText,
	InspectorControls,
} from '@wordpress/block-editor';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	BaseControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import ReactLoading from 'react-loading';
import Applink from './components/Applink';
import entityOptions from './entity-options';
import {
	appAtts,
	macAppAtts,
	movieAtts,
	ebookAtts,
	podcastAtts,
	audiobookAtts,
	musicTrackAtts,
	musicAlbumAtts,
	musicVideoAtts,
} from './app-attributes';

// PHPから取得した変数

const {
	options,
	optionsPageUrl,
	limitValues,
	countryValues,
	countryToLangMap,
	restUrl,
} =
	// eslint-disable-next-line no-undef
	sualAjaxValues;

const Edit = ( props ) => {
	const blockProps = useBlockProps( { className: 'sual-editor-wrapper' } );
	const { attributes, setAttributes, isSelected } = props;
	const { app, entity } = attributes;
	const [ result, setResult ] = useState( {} );
	const [ term, setTerm ] = useState( '' );
	const [ tempTerm, setTempTerm ] = useState( '' );
	const [ state, setState ] = useState( '' );
	const [ limit, setLimit ] = useState( options.limit || 10 );
	const [ lang, setLang ] = useState( options.lang || 'auto' );
	const [ country, setCountry ] = useState( options.country || 'JP' );
	const inputRef = useRef( null );
	const hasAutoFocusedRef = useRef( false );

	const fetchData = useCallback( async () => {
		const searchParams = new URLSearchParams();
		// 'auto'が選択されている場合は選択した国に応じた言語を使用
		const actualLang =
			lang === 'auto' ? countryToLangMap[ country ] || 'en_us' : lang;
		searchParams.append( 'lang', actualLang );
		searchParams.append( 'country', country );
		searchParams.append( 'entity', entity );
		searchParams.append( 'term', term );
		searchParams.append( 'limit', limit );
		searchParams.append( 'at', options.token || '11l64V' );

		const url = restUrl + 'search?' + searchParams.toString();

		setAttributes( { app: {} } );

		try {
			// WordPress REST APIを経由してiTunes Search APIを呼び出し。
			// apiFetchは2xx以外をrejectし、nonceの付与と更新も面倒を見る。
			const data = await apiFetch( { url } );
			setResult( data );
			setState( 'result-success' );
		} catch ( e ) {
			setState( 'result-error' );
			// eslint-disable-next-line no-console -- 通信失敗の原因調査に必要なため残す
			console.error( e );
		}
	}, [ lang, country, entity, term, limit, setAttributes ] );

	// Termが変更されている場合はTermを更新
	const setTermIfChanged = () => {
		setTerm( tempTerm );
	};

	const onKeyDown = ( e ) => {
		// URL入力してEnterを押したら
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			setTermIfChanged();
		}
	};

	// apiからの返却があった場合 検索結果0もtrue
	const hasResult = Object.keys( result ).length > 0;
	// アプリが登録されている場合
	const hasApp = Object.keys( app ).length > 0;

	// 取得したデータの種類によって表示する内容を選別する
	const itemAtts = ( item ) => {
		// entity=movieでの検索時、映画はkind: "feature-movie"
		if ( item.kind === 'feature-movie' ) {
			return movieAtts( item );
		}

		// その他の種類
		if ( item.kind === 'software' ) {
			return appAtts( item );
		} else if ( item.kind === 'mac-software' ) {
			return macAppAtts( item );
		} else if ( item.kind === 'ebook' ) {
			return ebookAtts( item );
		} else if ( item.kind === 'podcast' ) {
			return podcastAtts( item );
		} else if ( item.kind === 'song' ) {
			return musicTrackAtts( item );
		} else if ( item.kind === 'music-video' ) {
			return musicVideoAtts( item );
		} else if ( item.kind === 'interactive-booklet' ) {
			return ebookAtts( item );
		} // ブックタイプの追加
		else if ( item.kind === 'book' ) {
			return ebookAtts( item );
		} // 書籍
		else if ( item.wrapperType === 'audiobook' ) {
			return audiobookAtts( item );
		} else if (
			item.wrapperType === 'collection' &&
			item.collectionType === 'Album'
		) {
			return musicAlbumAtts( item );
		}

		return appAtts( item );
	};

	const ResultList = () => {
		if ( ! result.results ) {
			return null;
		}

		const list = result.results.map( ( item, i ) => {
			const itemApp = itemAtts( item );

			return (
				<button
					type="button"
					className={ `sual-editor-item sual-editor-${
						item.kind || item.wrapperType || 'default'
					}` }
					key={ i }
					onClick={ () => {
						setAttributes( { app: itemApp } );
					} }
				>
					<span className="sual-editor-figure">
						<img
							className="sual-editor-img"
							src={ itemApp.iconUrl }
							alt={ itemApp.title }
						/>
					</span>
					<span className="sual-editor-content">
						<span className="sual-editor-title">
							{ itemApp.title }
						</span>
						<span className="sual-editor-artist">
							{ itemApp.artist }
						</span>
					</span>
				</button>
			);
		} );

		return (
			<>
				<div className="sual-editor-result-num">
					{ __( 'Search Results', 'su-applink' ) }{ ' ' }
					{ result.resultCount }
					{ result.cached && (
						<span
							className="sual-editor-cached-badge"
							style={ {
								marginLeft: '10px',
								fontSize: '0.8em',
								backgroundColor: 'rgba(253, 210, 59, 0.2)',
								border: '1px solid #fdd23b',
								padding: '2px 6px',
								borderRadius: '4px',
								color: '#8b7012',
							} }
						>
							⚡️ { __( 'Cached', 'su-applink' ) }
						</span>
					) }
				</div>
				{ result.resultCount > 0 && (
					<div className="sual-editor-list">{ list }</div>
				) }
			</>
		);
	};

	const InfoText = ( { children } ) => {
		return <div className="">{ children }</div>;
	};

	const Display = () => {
		switch ( state ) {
			case 'search':
				return (
					<ReactLoading
						class=""
						type="spin"
						color="rgb(253 210 59)"
						width="20px"
						height="20px"
					/>
				);

			case 'result-error':
				return (
					<InfoText>
						{ __( 'Failed to retrieve data', 'su-applink' ) }
					</InfoText>
				);

			default:
				return '';
		}
	};

	// Termが有効ならfetch
	useEffect( () => {
		if ( term !== '' ) {
			setState( 'search' );
			setResult( {} );
			fetchData();
		}
	}, [ term, entity, limit, lang, country, fetchData ] );

	useEffect( () => {
		if ( hasApp ) {
			setResult( {} );
		}
	}, [ app ] );

	// ブロック新規作成直後に入力欄へ自動フォーカス
	useEffect( () => {
		if (
			isSelected &&
			! hasAutoFocusedRef.current &&
			! hasApp &&
			tempTerm === ''
		) {
			inputRef.current?.focus?.();
			hasAutoFocusedRef.current = true;
		}
	}, [ isSelected, hasApp, tempTerm ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Search Settings', 'su-applink' ) }>
					<BaseControl __nextHasNoMarginBottom>
						<SelectControl
							label={ __( 'Number of Results', 'su-applink' ) }
							value={ limit }
							onChange={ ( value ) => setLimit( value ) }
							options={ limitValues }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>

						<SelectControl
							label={ __( 'Store Country', 'su-applink' ) }
							value={ country }
							onChange={ ( value ) => setCountry( value ) }
							options={ countryValues }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>

						<ToggleControl
							label={ __(
								'Display Applink in English',
								'su-applink'
							) }
							checked={ lang === 'en_us' }
							onChange={ ( checked ) =>
								setLang( checked ? 'en_us' : 'auto' )
							}
							help={ __(
								'If turned off, the language will be determined automatically.',
								'su-applink'
							) }
							__nextHasNoMarginBottom
						/>

						<p>
							<Button
								href={ optionsPageUrl }
								target="_blank"
								rel="nofollow noreferrer noopener"
								variant="tertiary"
							>
								{ __(
									'Set default values on settings page',
									'su-applink'
								) }
							</Button>
						</p>
					</BaseControl>
				</PanelBody>
			</InspectorControls>

			{ isSelected && (
				<div className="sual-editor-control">
					<SelectControl
						className="sual-editor-type"
						value={ entity }
						onChange={ ( value ) => {
							// setEntity(value);
							setAttributes( { entity: value } );
							setTermIfChanged();
						} }
						options={ entityOptions }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<PlainText
						className="sual-editor-input"
						tagName="input"
						ref={ inputRef }
						placeholder={ __(
							'Enter search term and press Enter',
							'su-applink'
						) }
						value={ tempTerm }
						onChange={ ( value ) => setTempTerm( value ) }
						onKeyDown={ onKeyDown }
					/>
				</div>
			) }

			<Display />
			{ hasApp && <Applink app={ app } isEditor={ true } /> }
			{ hasResult && <ResultList /> }
		</div>
	);
};

export default Edit;
