# 配信終了アイテムの検出と表示切替

作成日: 2026年9月17日
対象バージョン: 1.1.0

## 背景

Media Link Cardsのカードは、挿入時点のiTunes Search APIの結果を`app`属性へ焼き付けて保存する。
そのあとアイテムがストアから消えても、カードは以前のまま表示され、リンク先はAppleの404になる。

lovemac.jpの移行作業（2026年9月17日）では、ショートコード世代のユニーク206 IDのうちiTunes lookupで
引けたのは79だけだった。残り127は`country`指定を外しても0件。配信終了は例外ではなく常態である。

読者を404へ送らないために、配信終了を検出して表示を切り替える。

## 事前調査（実測）

2026年9月17日に確認した挙動。

- `https://itunes.apple.com/lookup?id=642099621`（Flappy Bird、2014年に配信終了）は`resultCount: 0`を返す
- 同じアイテムのストアURL`https://apps.apple.com/jp/app/id642099621`は**HTTP 404**を返す。
  生存しているアプリは200。配信終了でも200が返るということはない
- lookupは`id=1,2,3`のカンマ区切りで**一括問い合わせができる**。生存2件と配信終了1件の計3 IDを
  投げたところ、生存2件だけが返った。返らなかったIDが配信終了と判定できる
- 音楽アルバムやオーディオブックのような`collectionId`系も同じlookupで引ける

したがって「配信終了かどうか」はlookupのresultCountで確実に判定できる。

ただし、訪問者のブラウザ側で判定する方法はない。CORSのため`apps.apple.com`のHTTPステータスは
JavaScriptから読めず、`<img>`の`onerror`はアートワークしか見られない。mzstaticのアートワークは
配信終了後も配信され続けるため、画像の生死を根拠にすると検出漏れが大量に出る。

## 設計方針

**サーバーからの問い合わせは避けられないが、フロントエンドの描画時にはゼロにできる。**
判定結果をデータベースへ保存し、描画時はそれを読むだけにする。

```
[管理画面] スキャン開始ボタン
   ↓ REST（manage_options）
全投稿をparse_blocks()で走査し、カードのidと使用記事を収集
   ↓
ユニークIDを100件ずつitunes.apple.com/lookupへ問い合わせ
   ↓ 返らなかったidが配信終了
optionへ保存（投稿本文は書き換えない）
   ↓
[フロント] render.phpがoptionを引いて表示を切り替える
```

- 定期実行（WP-Cron）は持たない。管理者がボタンを押したときだけ外部へ問い合わせる
- 投稿本文は書き換えない。リビジョンが増えず、記事を再保存しても判定結果が消えない。
  同じアイテムが複数記事にあっても1レコードで済む

## データモデル

### `yuiamijp-status` — フロントエンドが読む配信終了IDの一覧

```php
array(
    'ids'     => array( 642099621, 1234567890 ), // 配信終了と判定されたIDのみ
    'checked' => 1758000000,                     // 最後にスキャンが完走したUNIX時刻
)
```

`autoload`は`false`。カードを含むページでのみ1クエリ増える。カードのないページには影響しない。
配信終了IDだけを持つので、127件でも数KBに収まる。

### `yuiamijp-scan` — 管理画面が読むスキャンの記録

```php
array(
    'finished' => 1758000000, // スキャン完走時刻
    'total'    => 206,        // 見つかったユニークID数
    'dead'     => 127,        // うち配信終了と判定した数
    'errors'   => array( 111, 222 ), // 通信失敗で確認できなかったID
    'items'    => array(
        '642099621' => array(
            'state' => 'unavailable', // 'available' | 'unavailable'
            'title' => 'Flappy Bird',
            'type'  => 'app',
            'icon'  => 'https://is1-ssl.mzstatic.com/...',
            'posts' => array( 123, 456 ), // 使用している投稿ID
        ),
    ),
)
```

`autoload`は`false`。`yuiamijp-status`はこの記録から導出して書き出す。

キーはブロックの`app.id`（`trackId`または`collectionId`）。iTunesのID空間は共通なので両者は衝突しない。
`id`を持たない古いカードは対象外とし、従来どおり表示する。

### スキャン中の中間データ

トランジェント`yuiamijp_scan_progress`（有効期限1時間）に収集途中の`items`と次のオフセットを退避する。
既存の検索キャッシュ`yuiamijp_search_*`とは別物。

## スキャン処理（`inc/scan.php`）

分割実行する。管理画面のJavaScriptがRESTを繰り返し呼び、1回の呼び出しで一定量だけ進める。
1リクエストが長引いてタイムアウトするのを避けるため。

### フェーズ1: `collect`（投稿の走査）

1. 対象の投稿タイプは`get_post_types( array( 'exclude_from_search' => false ) )`に`wp_block`を加えたもの。
   再利用ブロック内のカードも拾うため`wp_block`を明示的に含める
2. 対象の投稿ステータスは`get_post_stati( array( 'internal' => false ) )`。
   publish / draft / pending / private に加え、テーマやプラグインが登録したカスタムステータスも含む。
   auto-draft・inherit（リビジョン）・trashは除外される
3. `posts_per_page`は50。`offset`を進めながら呼び出す
4. 各投稿の`post_content`を`parse_blocks()`にかけ、`innerBlocks`を**再帰的に**降りて
   `yuiamijp/media-link-cards`を探す
5. `attrs['app']['id']`をキーに、`attrs['app']`の`title` / `type` / `iconUrl`を
   `items`の`title` / `type` / `icon`へ写し、その投稿IDを`posts`へ積む。
   同じIDが複数の投稿にあれば`posts`に追記する
6. 走査済みの`items`と次の`offset`をトランジェントへ退避し、進捗を返す
7. `offset`が投稿の総数に達したらフェーズを`check`に切り替え、`offset`を0に戻して返す。
   次の呼び出しからフェーズ2に入る

### フェーズ2: `check`（生死の問い合わせ）

1. `items`のキー（ユニークID）を**100件ずつ**カンマ区切りにして
   `https://itunes.apple.com/lookup?id=…&country=<設定値>`へ`wp_remote_get()`する。
   100件でもURLは約1100文字で、実用上の上限2000文字に収まる
2. 返ってきたIDを`available`、返らなかったIDを`unavailable`として`items`へ記録する
3. `country`は設定値をそのまま使う。他国のストアで生存していても、読者の国で買えないなら
   配信終了として扱う
4. **通信に失敗したバッチは状態を書き換えない。** そのバッチのIDを`errors`へ記録し、
   前回の判定を引き継いだまま次のバッチへ進む。失敗を握りつぶさず管理画面へ表示する
5. 全バッチが終わったら`yuiamijp-scan`を確定保存し、`state`が`unavailable`のIDだけを集めて
   `yuiamijp-status`へ書き出す。トランジェントは削除する

### 状態の引き継ぎ

スキャンのたびに`yuiamijp-scan`を書き直すが、今回のスキャンで確認できなかったID
（通信失敗したバッチ）は前回の`state`を引き継ぐ。
今回の走査でどの投稿にも見つからなかったIDは記録から落とす。

### レート制限への配慮

iTunes Search APIの制限は毎分20リクエスト程度とされる。100件バッチなら206 IDで3リクエストなので
通常は問題にならない。カードが数千あるサイトを想定し、管理画面のJavaScript側でバッチの間に
1秒の待機を入れる。

## フロントエンドの表示（`src/render.php`）

`inc/status.php`に判定用のヘルパーを置く。

```php
function yuiamijp_is_unavailable( $id ) {
    static $ids = null;
    if ( null === $ids ) {
        $status = get_option( 'yuiamijp-status' );
        $ids    = ( is_array( $status ) && isset( $status['ids'] ) ) ? array_flip( $status['ids'] ) : array();
    }
    return isset( $ids[ $id ] );
}
```

optionの読み込みは1リクエストにつき1回だけ。

配信終了と判定されたときの出力。

```html
<div class="yuiamijp yuiamijp-app yuiamijp-unavailable">
	<span class="yuiamijp-figure">
		<img class="yuiamijp-img" src="…" alt="…" />
	</span>
	<div class="yuiamijp-content">
		<div class="yuiamijp-info">
			<span class="yuiamijp-title">…</span>
			<div class="yuiamijp-artist">…</div>
		</div>
		<div class="yuiamijp-btns">
			<span class="yuiamijp-btn yuiamijp-ended">
				<span class="yuiamijp-btn-label">配信終了</span>
			</span>
		</div>
	</div>
</div>
```

- `.yuiamijp-figure`と`.yuiamijp-title`の`<a>`を`<span>`へ置き換える
- 試聴ボタン（`.yuiamijp-audition`）は出さない。プレビューURLも同時に死んでいるため
- ストアボタン（`.yuiamijp-store`）も出さない。ストアアイコンを残すと購入できるように
  見えるため、置き換えではなく「配信終了」ラベル（`.yuiamijp-ended`）に差し替える
- タイトルと作者名は残す。記事本文がカードを指して書かれている場合に文脈が壊れないため

## スタイル（`src/style.css`）

既存のセマンティックトークン（`--_color-text-muted` / `--_color-bg-subtle`）を使う。
ダークモードはテーマ側で切り替わるため、このファイルでは個別に上書きしない。

```css
.yuiamijp-unavailable {

	.yuiamijp-img {
		filter: grayscale(1);
		opacity: 0.55;
	}

	.yuiamijp-title {
		color: var(--_color-text-muted) !important;
		cursor: default;
	}

	/* リンクがないのでホバーの背景変化を止める */
	&:has(a:hover) {
		background-color: var(--_color-bg);
	}
}

.yuiamijp-ended {
	color: var(--_color-text-muted);
	cursor: default;
	background-color: var(--_color-bg-subtle);
}
```

`.yuiamijp-title`の`color`に`!important`が付いているのは既存の宣言に合わせるため。
テーマのリンク色に勝つために元から付いている。

## エディターの表示（`src/components/MediaLinkCard.js`）

CLAUDE.mdのとおり、カードのマークアップはフロント（`render.php`）とエディタープレビュー
（`MediaLinkCard.js`）の二重管理になっている。クラス名と構造を揃える。

`MediaLinkCard.js`の`LinkWrapper`は`isEditor`のとき既に`<span>`を返すので、
追加するのはルートのクラスとラベルの出し分けだけ。

判定用のIDは`yuiamijpAjaxValues.unavailableIds`（配信終了IDの配列）で渡す。
生存分を含めないので、127件なら1.5KB程度に収まる。
`yuiamijp_admin_enqueue_scripts()`にキーを追加する。CLAUDE.mdのとおり`edit.js`は
このグローバルを分解代入するため、`MediaLinkCard.js`へ渡す経路も合わせて用意する。

## 管理画面（`inc/admin-page.php`）

既存の設定ページ（設定 > Media Link Cards）に「Link check」セクションを追加する。
新しいメニューは作らない。

- 「Start scan」ボタンと進捗表示
- 最終スキャン日時、ユニークID数、配信終了数
- 確認できなかったIDがあればその件数と注意書き
- 配信終了アイテムの一覧テーブル
  - アートワーク（小）、タイトル、種別、ID、使用している記事への編集リンク
  - 記事リンクは`get_edit_post_link()`。`wp_block`に入っていたカードは再利用ブロック自体の
    編集画面を指す

JavaScriptは`assets/admin.js`に素のまま置く。ブロック用のビルドとは切り離す。
`assets/`は`.distignore`で除外していないので配布物に含まれる。

依存に`wp-api-fetch`を指定し、`wp.apiFetch`でRESTを呼ぶ。nonceの付与と更新はコアが面倒を見る。

**表示文字列はPHPの`__()`で翻訳し、`wp_localize_script()`でJavaScriptへ渡す。**
このプラグインは`.json`翻訳を同梱せず`wp_set_script_translations()`にパスを渡していないため、
JavaScript側で`__()`を使うと言語パックの生成状況に左右される。PHP側で解決してから渡す。

## REST API（`inc/api.php`）

既存の名前空間`yuiamijp-media-link-cards/v1`に`/scan`を追加する。

- メソッド: `POST`
- 権限: `current_user_can( 'manage_options' )`。検索用の`/search`（`edit_posts`）より厳しくする
- パラメータ
  - `phase`: `collect` または `check`
  - `offset`: 整数。`collect`では投稿のオフセット、`check`ではID一覧のインデックス
- レスポンス
  - `phase` / `offset`: 次の呼び出しに渡す値
  - `total` / `processed`: 進捗表示用
  - `done`: 全工程が終わったかどうか
  - `errors`: 確認できなかったIDの件数（`yuiamijp-scan`にはID自体を保存するが、
    レスポンスでは件数だけ返す）

## 国際化

新規の表示文字列（英語原文）。

- `No longer available` — カードのバッジ
- `Link check` — セクション見出し
- `Start scan` — ボタン
- `Scanning…` — 実行中
- `Never scanned` — 未実行時
- `Last scanned: %s`
- `%1$d cards checked, %2$d no longer available`
- `Could not check %d items. Try again later.`
- `Title` / `Type` / `ID` / `Used in` — テーブル見出し

追加後、CLAUDE.mdの手順で`.pot`と`.po`を更新する。`build/`を除外しないこと。

```bash
pnpm build
wp i18n make-pot . languages/yuiamijp-media-link-cards.pot --slug=yuiamijp-media-link-cards --exclude=node_modules
msgmerge --update --backup=none languages/yuiamijp-media-link-cards-ja.po languages/yuiamijp-media-link-cards.pot
msgfmt --statistics -o /dev/null languages/yuiamijp-media-link-cards-ja.po
```

## アンインストール（`uninstall.php`）

`yuiamijp-status`と`yuiamijp-scan`を削除する。

```php
delete_option( 'yuiamijp-setting' );
delete_option( 'yuiamijp-status' );
delete_option( 'yuiamijp-scan' );
```

## wordpress.orgの審査対応（`readme.txt`）

readme.txtの`= External service =`は審査を通った文面で、次のように書いてある。

- リクエストが発生するのはブロックエディターで`edit_posts`権限のユーザーが検索したときだけ
- サイトのフロントエンドはサービスへ一切接続しない

**この節の更新が必須。** 新機能は別のエンドポイント`https://itunes.apple.com/lookup`を
管理画面から叩くため、現行の記述と食い違う。次の内容を追記する。

- エンドポイントに`https://itunes.apple.com/lookup`を追加
- リクエストが発生する場面に「管理者が設定ページでスキャンを明示的に実行したとき」を追加
- 送信する内容は「カードに保存済みのアイテムID（Appleが発行した公開のID）とストア国」だけであり、
  個人情報もサイトや訪問者の情報も送らないことを明記
- フロントエンドが接続しない点は変わらないので維持する

あわせてバージョンを1.1.0へ上げる。

- `yuiamijp-media-link-cards.php`のヘッダー`Version:`
- `readme.txt`の`Stable tag:`
- `readme.txt`の`== Changelog ==`に1.1.0の項目を追加

## 変更するファイル

新規。

- `inc/status.php` — 判定結果の読み書きと`yuiamijp_is_unavailable()`
- `inc/scan.php` — 投稿の走査、lookupの問い合わせ、結果の保存
- `assets/admin.js` — 管理画面のスキャン実行と進捗表示

変更。

- `inc/api.php` — `/scan`ルートの追加
- `inc/admin-page.php` — Link checkセクション、結果テーブル、スクリプトのenqueue
- `src/render.php` — 配信終了時の出力の分岐
- `src/style.css` — `.yuiamijp-unavailable` / `.yuiamijp-ended`
- `src/components/MediaLinkCard.js` — エディタープレビューの同じ分岐
- `yuiamijp-media-link-cards.php` — `require_once`の追加、`yuiamijpAjaxValues`へ`unavailableIds`、
  バージョンを1.1.0へ
- `uninstall.php` — option 2件の削除
- `readme.txt` — External serviceの更新、Stable tag、Changelog
- `languages/yuiamijp-media-link-cards.pot` / `languages/yuiamijp-media-link-cards-ja.po`

## 動作確認

このプロジェクトにテストはない。手動で確認する。

1. `pnpm build`を通す
2. 既知の配信終了ID `642099621`（Flappy Bird）を`app.id`に持つカードを投稿へ置く
3. 設定ページでスキャンを実行し、一覧に出て使用記事へのリンクが正しいことを確認する
4. フロントでリンクが消え、「配信終了」バッジが出てアイコンがグレーになることを確認する
5. 生存しているカードが巻き添えで無効化されていないことを確認する
6. lookupのホストを一時的に到達不能にしてスキャンし、既存の判定が書き換わらず、
   エラー件数が管理画面に出ることを確認する
7. エディタープレビューとフロントの見た目が一致することを確認する
8. `pnpm lint:all`と`phpcs`を通す

## 対象外

次は今回作らない。必要になった時点で別途検討する。

- WP-Cronによる定期チェック。配布先の全サイトが自動でAppleへ問い合わせる形になり、
  審査の論点になるうえ、opt-inの設計が別途必要になる
- 誤判定を手で戻す上書きUI。通信失敗時に状態を変えない設計で誤判定は減るため、
  実際に困ってから足す
- 代替リンク（App Storeの検索結果や後継アプリ）への差し替え。後継の指定が人力になる
- 投稿本文の書き換え
- FSEのテンプレート（`wp_template` / `wp_template_part`）内のカードの走査。
  カードがテンプレートに入る例は稀なため

## 既知の制約

- `exclude_from_search => true`で登録されたカスタム投稿タイプは走査対象に入らない
  （`wp_block`のみ例外として明示的に含める）
- `country`を跨いだ判定はしない。日本のストアから消えたアイテムは、米国で生存していても
  配信終了として扱う
- `app.id`を持たない古いカードは判定できず、従来どおり表示する
