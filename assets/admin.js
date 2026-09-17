/**
 * 設定ページの「Link check」セクション
 *
 * REST の /scan を phase と offset を進めながら繰り返し呼び、進捗を表示する。
 * 完走したらページを再読み込みし、結果テーブルは PHP 側で描画する。
 *
 * 表示文字列は PHP 側で翻訳済みのものを yuiamijpScan.i18n で受け取る。
 * このプラグインは .json 翻訳を同梱しないため、JS 側の __() は使わない。
 */

/* global yuiamijpScan */

( function () {
	const button = document.getElementById( 'yuiamijp-scan-start' );
	const status = document.getElementById( 'yuiamijp-scan-status' );

	if ( ! button || ! status ) {
		return;
	}

	const { i18n, scanUrl } = yuiamijpScan;

	// PHP 側の文字列に含まれる %1$d / %2$d を置き換える。翻訳で順序が入れ替わっても効く
	const progressText = ( template, processed, total ) =>
		template.replaceAll( '%1$d', processed ).replaceAll( '%2$d', total );

	// lookup のレート制限（毎分20回程度）を避けるため、check フェーズではバッチ間に1秒空ける
	const sleep = ( ms ) =>
		new Promise( ( resolve ) => {
			setTimeout( resolve, ms );
		} );

	const runScan = async () => {
		let phase = 'collect';
		let offset = 0;
		let done = false;

		while ( ! done ) {
			const previousPhase = phase;
			const previousOffset = offset;

			const result = await wp.apiFetch( {
				url: scanUrl,
				method: 'POST',
				data: { phase, offset },
			} );

			status.textContent = progressText(
				'collect' === result.phase ? i18n.collecting : i18n.checking,
				result.processed,
				result.total
			);

			if (
				! result.done &&
				result.phase === previousPhase &&
				result.offset <= previousOffset
			) {
				throw new Error( i18n.failed );
			}

			done = result.done;
			phase = result.phase;
			offset = result.offset;

			if ( ! done && 'check' === phase ) {
				await sleep( 1000 );
			}
		}
	};

	button.addEventListener( 'click', async () => {
		button.disabled = true;
		status.textContent = i18n.starting;

		try {
			await runScan();
			status.textContent = i18n.done;
			window.location.reload();
		} catch ( error ) {
			button.disabled = false;
			status.textContent =
				i18n.failed +
				( error && error.message ? ' ' + error.message : '' );
		}
	} );
} )();
