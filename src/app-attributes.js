/**
 * アートワークURLを選ぶ
 *
 * 種別によって返るフィールドが違う（software は artworkUrl512、podcast は
 * artworkUrl600、それ以外は artworkUrl100 が上限）。カードの表示幅は 5rem
 * = 80px なので、2倍ディスプレイでは 160px 必要になる。artworkUrl100 では
 * 足りないため、大きいものから順に見る。
 *
 * 100px 止まりの種別（ebook / audiobook / music 系）は、Apple のアートワーク
 * URL末尾のサイズ部分を差し替えると同じ画像の大きい版が取れる。パターンに
 * 合わないURLはそのまま返す。
 *
 * @param {Object} item iTunes Search API の結果1件。
 * @return {string|undefined} アートワークのURL。
 */
const artworkUrl = ( item ) => {
	const large = item.artworkUrl600 || item.artworkUrl512;
	if ( large ) {
		return large;
	}

	const small = item.artworkUrl100 || item.artworkUrl60 || item.artworkUrl30;
	if ( typeof small !== 'string' ) {
		return small;
	}

	return small.replace( /\/\d+x\d+bb\.([a-z]+)$/, '/512x512bb.$1' );
};

export const appAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'app',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
	};
};

export const macAppAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'mac-app',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
	};
};

export const movieAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'movie',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
		previewUrl: item.previewUrl,
	};
};

export const ebookAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'ebook',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
		previewUrl: item.previewUrl,
	};
};

export const podcastAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'podcast',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
		previewUrl: item.previewUrl,
	};
};

export const audiobookAtts = ( item ) => {
	return {
		id: item.collectionId,
		type: 'audiobook',
		title: item.collectionCensoredName || item.collectionName,
		url: item.collectionViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
		previewUrl: item.previewUrl,
	};
};

export const musicTrackAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'music-track',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
		previewUrl: item.previewUrl,
	};
};

export const musicAlbumAtts = ( item ) => {
	return {
		id: item.collectionId,
		type: 'music-album',
		title: item.collectionCensoredName || item.collectionName,
		url: item.collectionViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
	};
};

export const musicVideoAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'music-video',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl: artworkUrl( item ),
		previewUrl: item.previewUrl,
	};
};
