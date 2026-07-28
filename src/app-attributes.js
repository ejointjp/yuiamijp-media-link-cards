export const appAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'app',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
	};
};

export const macAppAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'mac-app',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
	};
};

export const movieAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'movie',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
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
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
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
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
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
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
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
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
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
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
	};
};

export const musicVideoAtts = ( item ) => {
	return {
		id: item.trackId,
		type: 'music-video',
		title: item.trackCensoredName || item.trackName,
		url: item.trackViewUrl,
		artist: item.artistName,
		iconUrl:
			item.artworkUrl100 ||
			item.artworkUrl512 ||
			item.artworkUrl60 ||
			item.artworkUrl30,
		previewUrl: item.previewUrl,
	};
};
