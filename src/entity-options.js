/**
 * 検索できる種別の一覧
 *
 * value と label はブロック内の選択欄に、それ以外はスラッシュメニューと
 * インサーターの項目（variations.js）に使う。
 *
 * aliases は言語によらず効かせたい英語の検索語、searchTerms は翻訳者が
 * 同義語へ置き換えられる検索語。どちらも画面には出ない。
 */
import { __, _x } from '@wordpress/i18n';

export default [
	{
		value: 'software',
		label: __( 'iPhone App', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for an iPhone app and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'smartphone',
		aliases: [ 'iphone', 'app' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'iPhone, app',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'iPadSoftware',
		label: __( 'iPad App', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for an iPad app and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'tablet',
		aliases: [ 'ipad', 'app' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'iPad, app',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'macSoftware',
		label: __( 'Mac App', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for a Mac app and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'laptop',
		aliases: [ 'mac', 'app' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'Mac, app',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	// {
	//   value: 'movie',
	//   label: 'Movie'
	// },
	{
		value: 'ebook',
		label: __( 'Book', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for a book and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'book',
		aliases: [ 'books', 'ebooks' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'books, ebooks',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'audiobook',
		label: __( 'Audiobook', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for an audiobook and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'controls-volumeon',
		aliases: [ 'audiobooks' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'audiobooks',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'podcast',
		label: __( 'Podcast', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for a podcast and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'microphone',
		aliases: [ 'podcasts' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'podcasts',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'musicTrack',
		label: __( 'Music Track', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for a music track and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'format-audio',
		aliases: [ 'music', 'tracks', 'songs' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'music, tracks, songs',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'album',
		label: __( 'Music Album', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for a music album and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'album',
		aliases: [ 'music', 'albums' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'music, albums',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
	{
		value: 'musicVideo',
		label: __( 'Music Video', 'yuiamijp-media-link-cards' ),
		description: __(
			'Search for a music video and embed it as a card.',
			'yuiamijp-media-link-cards'
		),
		icon: 'video-alt3',
		aliases: [ 'music', 'videos' ],
		/* translators: Comma-separated search terms for the block inserter. Never displayed. Add synonyms freely. */
		searchTerms: _x(
			'music, videos',
			'block search terms',
			'yuiamijp-media-link-cards'
		),
	},
];
