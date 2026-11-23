/**
 * スラッシュメニューとインサーターに、種別ごとの項目を出す
 *
 * description と keywords は省略するとブロック本体の値が使われる。本体の
 * 説明文とキーワードには全種別の名前が入っているため、どれか1つを省くと
 * `/iphone` で関係のない種別まで当たる。必ず種別ごとに指定する。
 */
import { _x } from '@wordpress/i18n';

import entityOptions from './entity-options';
import metadata from './block.json';

const defaultEntity = metadata.attributes.entity.default;

export default entityOptions.map(
	( { value, label, description, icon, aliases, searchTerms } ) => ( {
		name: value,
		title: label,
		description,
		icon,
		keywords: [
			...aliases,
			searchTerms,
			// 訳した項目名は「iPhone アプリ」のように空白を含む。空白なしの
			// 「iPhoneアプリ」で入力しても当たるようにする
			label.replace( /\s+/g, '' ),
			'apple',
			'itunes',
			'embed',
			// 既定の項目がブロック本体の項目に取って代わるので、ブロック名でも
			// 探せるようにしておく
			_x( 'Media Link Card', 'block title', 'yuiamijp-media-link-cards' ),
		],
		attributes: { entity: value },
		isDefault: value === defaultEntity,
		isActive: [ 'entity' ],
		scope: [ 'inserter' ],
	} )
);
