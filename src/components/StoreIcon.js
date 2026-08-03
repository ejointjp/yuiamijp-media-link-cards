/**
 * アイコンの描画
 *
 * 定義は assets/icons.json が単一の情報源で、フロント側は inc/icons.php が
 * 同じ JSON を読む。アイコンを足す・差し替えるときは JSON だけを変更する。
 *
 * content は同梱 JSON 内の自前マークアップのため dangerouslySetInnerHTML で
 * 展開している。svg 要素そのものは React が作るので、CSS が当てにしている
 * `.sual-btn svg` の構造は変わらない。
 */
import icons from '../../assets/icons.json';

export const Icon = ( { name } ) => {
	const icon = icons.icons[ name ];

	if ( ! icon ) {
		return null;
	}

	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="1em"
			height="1em"
			{ ...icon.attr }
			dangerouslySetInnerHTML={ { __html: icon.content } }
		/>
	);
};

export const PreviewIcon = () => <Icon name={ icons.preview } />;

export const StoreIcon = ( { type } ) => {
	const store = icons.stores[ type ] || icons.stores.default;

	return (
		<>
			<Icon name={ store.icon } />
			<span className="sual-btn-label">{ store.label }</span>
		</>
	);
};
