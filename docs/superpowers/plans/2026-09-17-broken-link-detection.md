# 配信終了アイテムの検出と表示切替 実装計画

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 管理画面からの一括スキャンで配信終了したアイテムを検出し、そのカードをフロントとエディターでリンクなしの「配信終了」表示に切り替える。

**Architecture:** 管理者がボタンを押すとRESTの`/scan`を`phase` / `offset`を進めながら繰り返し呼ぶ。`collect`で全投稿を`parse_blocks()`で走査してカードの`app.id`を集め、`check`でiTunes lookupへ100件ずつ問い合わせる。結果はoption 2つ（`yuiamijp-status`＝配信終了IDだけ、`yuiamijp-scan`＝全記録）に保存し、`render.php`とエディターはそれを読むだけ。投稿本文は書き換えず、フロントからの外部通信はゼロ。

**Tech Stack:** WordPress 6.3+ / PHP 7.4+、REST API、`wp_remote_get()`、ブロックエディター（`@wordpress/scripts`でビルド）、素のJavaScript（管理画面）、phpcs（WPCS）、`wp i18n make-pot` / `msgmerge` / `msgfmt`

設計書: `docs/superpowers/specs/2026-09-17-broken-link-detection-design.md`

## Global Constraints

- 接頭辞はすべて`yuiamijp`。PHP関数`yuiamijp_` / 定数`YUIAMIJP_` / option`yuiamijp-*` / トランジェント`yuiamijp_*` / CSSクラス フロント`yuiamijp-`・エディター専用`yuiamijp-editor-` / テキストドメイン`yuiamijp-media-link-cards`
- `Applink`と`SU Blocks`はコードにもドキュメントにも戻さない
- 表示文字列は必ず`__()`系に通す。追加したら`.pot`と`.po`を更新し、未訳0・fuzzy 0で終える。`.mo` / `.json`は作らない
- `wp i18n make-pot`で`build/`を除外しない。除外するのは`node_modules`だけ
- PHPはphpcs（`.phpcs.xml.dist`を自動で読む。コマンドは`phpcs`だけ）。JS / CSSは`pnpm lint:all`。コード変更後に必ず通す
- CSSの整形はstylelintの`--fix`だけ（`pnpm format`に含まれる）。prettierをCSSに掛けない
- **`wp-scripts format`をファイル引数なしで直接実行しない。** `--check`は未対応で、続く引数を食って引数ゼロになり、`.`全体（`.github/*.yml`・`pnpm-lock.yaml`も含む）を`--write`で4スペースに整形してしまう。使うのは引数を明示した`pnpm format`だけ。整形後は`git status`で意図しないファイルが変わっていないことを確認する
- `src/`を変更したら`pnpm build`。`build/render.php`の実体は`src/render.php`
- `assets/`は配布物に必須（`inc/icons.php`が実行時に読む）。`.distignore`で除外しない
- カードのマークアップはフロント（`src/render.php`）とエディター（`src/components/MediaLinkCard.js`）で二重管理。クラス名・構造を変えたら両方を直す
- `yuiamijpAjaxValues`のキーを増やしたら`src/edit.js`の分解代入も合わせる
- フロントから外部サービスへ接続しない。lookupは管理者（`manage_options`）の明示操作でのみ。WP-Cronは使わない。投稿本文は書き換えない
- 通信失敗・HTTP 200以外・JSON破損・`results`なしのバッチは判定を保留し、前回の判定を引き継ぐ。前回もなければ`unknown`
- Gitのコミットメッセージは日本語。ドキュメント・コードコメントは通常の日本語で、「無い」は「ない」と書き、和欧混植に半角スペースを入れない
- 検証用のPHPスクリプトはリポジトリに入れない（このプロジェクトにテストはない）。作業セッションのscratchpadディレクトリ（システムプロンプトの"Scratchpad directory"のパス。以下`$SCRATCH`）の`yuiamijp-tests/`に置く。作業を始める前に`export SCRATCH=<そのパス>`する
- WordPress環境が必要な動作確認は、環境がなければ実施せずユーザーへ報告する（この環境ではDocker Desktopが起動できない）

---

## ファイル構成

新規。

- `inc/status.php` — option 2つの読み書き。`yuiamijp_get_scan_record()` / `yuiamijp_get_unavailable_ids()` / `yuiamijp_is_unavailable()` / `yuiamijp_save_scan_record()`
- `inc/scan.php` — 走査・lookup・RESTのコールバック。`yuiamijp_scan_collect()` / `yuiamijp_scan_check()` / `yuiamijp_collect_cards()` / `yuiamijp_match_lookup_results()` / `yuiamijp_lookup_ids()` ほか
- `assets/admin.js` — 設定ページのスキャン実行と進捗表示。ビルドを通さない素のJS

変更。

- `inc/define.php` — バッチサイズの定数2つ
- `inc/api.php` — `/scan`ルート
- `inc/admin-page.php` — Link checkセクション、結果テーブル、スクリプトのenqueue
- `src/render.php` — 配信終了時の分岐
- `src/style.css` — `.yuiamijp-unavailable` / `.yuiamijp-ended`
- `src/components/MediaLinkCard.js` — エディタープレビューの同じ分岐
- `src/edit.js` — `unavailableIds`の受け取りと受け渡し
- `yuiamijp-media-link-cards.php` — `require_once`、`unavailableIds`、バージョン
- `uninstall.php` — option 2件の削除
- `package.json` — lint / formatの対象、バージョン
- `.distignore` — `docs`を除外
- `readme.txt` — External service、FAQ、Description、Stable tag、Changelog
- `languages/yuiamijp-media-link-cards.pot` / `-ja.po`
- `CLAUDE.md` — 新機能の節と注意事項

各タスクの終わりにコミットする。コミットメッセージは末尾に次の行を付ける。

```
Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
```

---

### Task 1: 判定結果の保存と読み出し（`inc/status.php`）

**Files:**
- Create: `inc/status.php`
- Modify: `yuiamijp-media-link-cards.php:23-26`（`require_once`の並び）
- Modify: `uninstall.php`
- Test: `$SCRATCH/yuiamijp-tests/test-status.php`（リポジトリ外）

**Interfaces:**
- Produces:
  - `yuiamijp_get_scan_record(): array` — option `yuiamijp-scan`の内容。未実行なら`array()`
  - `yuiamijp_get_unavailable_ids(): int[]` — option `yuiamijp-status`の`ids`。1リクエストにつき1回だけ読み、staticに保持
  - `yuiamijp_is_unavailable( int|string $id ): bool` — `$id`が0以下なら常に`false`
  - `yuiamijp_save_scan_record( array $items, array $errors ): array` — `yuiamijp-scan`に全体を、`yuiamijp-status`に`state === 'unavailable'`のIDだけを書き、保存した記録を返す。`$items`は`id => array( 'state', 'title', 'type', 'icon', 'posts' )`
  - option `yuiamijp-scan`: `array( 'finished' => int, 'total' => int, 'dead' => int, 'errors' => int[], 'items' => array )`
  - option `yuiamijp-status`: `array( 'ids' => int[], 'checked' => int )`

- [ ] **Step 1: 失敗するテストを書く**

`$SCRATCH/yuiamijp-tests/test-status.php`を作る。

```php
<?php
// inc/status.php を WordPress なしで検証する書き捨てスクリプト。リポジトリには入れない。
define( 'ABSPATH', '/nonexistent/' );
define( 'REPO', getenv( 'REPO' ) ? getenv( 'REPO' ) : '/Users/fujisaki/Dev/wordpress-plugins/yuiamijp-media-link-cards' );

$GLOBALS['stub_options'] = array();
function get_option( $name, $default_value = false ) {
	return isset( $GLOBALS['stub_options'][ $name ] ) ? $GLOBALS['stub_options'][ $name ] : $default_value;
}
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['stub_options'][ $name ] = $value;
	return true;
}

require REPO . '/inc/status.php';

$failures = 0;
function check( $label, $actual, $expected ) {
	global $failures;
	if ( $actual === $expected ) {
		echo "  ok   $label\n";
	} else {
		$failures++;
		echo "  FAIL $label\n    expected: " . var_export( $expected, true ) . "\n    actual:   " . var_export( $actual, true ) . "\n";
	}
}

echo "未実行の状態\n";
check( 'get_scan_record は空配列', yuiamijp_get_scan_record(), array() );

echo "yuiamijp_save_scan_record / yuiamijp_is_unavailable\n";
$record = yuiamijp_save_scan_record(
	array(
		111 => array( 'state' => 'available', 'title' => 'A', 'type' => 'app', 'icon' => '', 'posts' => array( 10 ) ),
		222 => array( 'state' => 'unavailable', 'title' => 'B', 'type' => 'ebook', 'icon' => '', 'posts' => array( 20 ) ),
		333 => array( 'state' => 'unknown', 'title' => 'C', 'type' => 'app', 'icon' => '', 'posts' => array( 30 ) ),
	),
	array( '333' )
);
check( 'total は全 ID 数', $record['total'], 3 );
check( 'dead は unavailable の数', $record['dead'], 1 );
check( 'errors は int に正規化', $record['errors'], array( 333 ) );
check( 'finished は現在時刻', abs( time() - $record['finished'] ) < 5, true );
check( 'yuiamijp-status は unavailable の ID だけ', $GLOBALS['stub_options']['yuiamijp-status']['ids'], array( 222 ) );
check( 'unavailable は true', yuiamijp_is_unavailable( 222 ), true );
check( '文字列 ID でも判定できる', yuiamijp_is_unavailable( '222' ), true );
check( 'available は false', yuiamijp_is_unavailable( 111 ), false );
check( 'unknown は false（従来表示）', yuiamijp_is_unavailable( 333 ), false );
check( '0 は false', yuiamijp_is_unavailable( 0 ), false );
check( 'get_scan_record は保存した記録を返す', yuiamijp_get_scan_record()['dead'], 1 );
check( 'get_unavailable_ids は int の配列', yuiamijp_get_unavailable_ids(), array( 222 ) );

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit( $failures ? 1 : 0 );
```

- [ ] **Step 2: 失敗することを確認する**

Run: `php "$SCRATCH/yuiamijp-tests/test-status.php"`
Expected: `Failed opening required '.../inc/status.php'` で終了コード255

- [ ] **Step 3: `inc/status.php`を作る**

```php
<?php
/**
 * 配信終了アイテムの判定結果の読み書き
 *
 * option は2つ。
 *
 * - yuiamijp-status — フロントが読む配信終了 ID の一覧。生存分は持たない
 * - yuiamijp-scan   — 管理画面が読むスキャンの記録（全 ID の状態と使用記事）
 *
 * どちらも autoload しない。yuiamijp-status はカードを含むページでだけ読まれ、
 * 1リクエストにつき1回 static に保持する。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * スキャンの記録を返す
 *
 * @return array yuiamijp-scan の内容。未実行なら空配列。
 */
function yuiamijp_get_scan_record() {
	$record = get_option( 'yuiamijp-scan' );

	return is_array( $record ) ? $record : array();
}

/**
 * 配信終了と判定された ID の一覧を返す
 *
 * @return int[]
 */
function yuiamijp_get_unavailable_ids() {
	static $ids = null;

	if ( null !== $ids ) {
		return $ids;
	}

	$status = get_option( 'yuiamijp-status' );
	$ids    = array();

	if ( is_array( $status ) && isset( $status['ids'] ) && is_array( $status['ids'] ) ) {
		$ids = array_map( 'intval', $status['ids'] );
	}

	return $ids;
}

/**
 * ID が配信終了と判定されているかを返す
 *
 * @param int|string $id カードの app.id（trackId または collectionId）。
 * @return bool
 */
function yuiamijp_is_unavailable( $id ) {
	$id = (int) $id;

	if ( $id <= 0 ) {
		return false;
	}

	return in_array( $id, yuiamijp_get_unavailable_ids(), true );
}

/**
 * スキャン結果を確定保存する
 *
 * yuiamijp-scan に全体を保存し、state が unavailable の ID だけを
 * yuiamijp-status へ書き出す。
 *
 * @param array $items  ID をキーに state / title / type / icon / posts を持つ配列。
 * @param int[] $errors 通信失敗で確認できなかった ID。
 * @return array 保存した yuiamijp-scan の内容。
 */
function yuiamijp_save_scan_record( array $items, array $errors ) {
	$dead = array();

	foreach ( $items as $id => $item ) {
		if ( isset( $item['state'] ) && 'unavailable' === $item['state'] ) {
			$dead[] = (int) $id;
		}
	}

	$now    = time();
	$record = array(
		'finished' => $now,
		'total'    => count( $items ),
		'dead'     => count( $dead ),
		'errors'   => array_values( array_map( 'intval', $errors ) ),
		'items'    => $items,
	);

	update_option( 'yuiamijp-scan', $record, false );
	update_option(
		'yuiamijp-status',
		array(
			'ids'     => $dead,
			'checked' => $now,
		),
		false
	);

	return $record;
}
```

- [ ] **Step 4: テストが通ることを確認する**

Run: `php "$SCRATCH/yuiamijp-tests/test-status.php"`
Expected: すべて`ok`、最後に`ALL PASSED`、終了コード0

- [ ] **Step 5: メインファイルで読み込む**

`yuiamijp-media-link-cards.php`の`require_once`の並びを次のようにする（`inc/status.php`を`inc/api.php`の前に足す）。

```php
require_once plugin_dir_path( __FILE__ ) . 'inc/define.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/icons.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/status.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/api.php';
```

- [ ] **Step 6: アンインストール時に消す**

`uninstall.php`の末尾を次のようにする。

```php
delete_option( 'yuiamijp-setting' );
delete_option( 'yuiamijp-status' );
delete_option( 'yuiamijp-scan' );
```

- [ ] **Step 7: lintを通す**

Run: `php -l inc/status.php && phpcs`
Expected: `No syntax errors detected`、phpcsは指摘なし

- [ ] **Step 8: コミット**

```bash
git add inc/status.php yuiamijp-media-link-cards.php uninstall.php
git commit -m "配信終了アイテムの判定結果を保存するoptionを追加する

yuiamijp-statusにはフロントが読む配信終了IDだけを、yuiamijp-scanには
管理画面が読むスキャンの全記録を保存する。どちらもautoloadせず、
アンインストール時に削除する。

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 2: スキャン処理とRESTルート（`inc/scan.php`）

**Files:**
- Create: `inc/scan.php`
- Modify: `inc/define.php`（末尾に定数）
- Modify: `inc/api.php:15-26`（`yuiamijp_register_rest_routes()`）
- Modify: `yuiamijp-media-link-cards.php`（`require_once`）
- Test: `$SCRATCH/yuiamijp-tests/test-scan.php`（リポジトリ外）

**Interfaces:**
- Consumes: Task 1の`yuiamijp_get_scan_record()` / `yuiamijp_save_scan_record()`
- Produces:
  - 定数`YUIAMIJP_SCAN_POSTS_PER_PAGE = 50` / `YUIAMIJP_SCAN_LOOKUP_BATCH = 100`
  - `yuiamijp_collect_cards( array $blocks, array &$items, int $post_id ): void` — `parse_blocks()`の結果から再帰でカードを集める
  - `yuiamijp_match_lookup_results( array $requested, array $results ): int[]` — lookupの`results`から見つかったIDを返す
  - `yuiamijp_lookup_ids( array $ids, string $country ): int[]|WP_Error`
  - `yuiamijp_scan_collect( int $offset ): array` / `yuiamijp_scan_check( int $offset ): array|WP_Error` — 戻り値は`array( 'phase' => 'collect'|'check', 'offset' => int, 'total' => int, 'processed' => int, 'done' => bool, 'errors' => int )`
  - REST `POST /yuiamijp-media-link-cards/v1/scan`（`manage_options`）。パラメータ`phase`（`collect` | `check`、既定`collect`）と`offset`（整数、既定0）。レスポンスは上の配列。中間データ失効時は`409`で`yuiamijp_scan_expired`
  - トランジェント`yuiamijp_scan_progress`（1時間）: `array( 'items' => array, 'errors' => int[] )`

- [ ] **Step 1: 失敗するテストを書く**

`$SCRATCH/yuiamijp-tests/test-scan.php`を作る。HTTP・トランジェント・optionをスタブし、純粋関数と`check`フェーズを通す（`collect`は`WP_Query`に依存するためここでは通さない）。

```php
<?php
// inc/scan.php を WordPress なしで検証する書き捨てスクリプト。リポジトリには入れない。
// HTTP・トランジェント・option をスタブし、collect 以外の経路（純粋関数と check フェーズ）を通す。
define( 'ABSPATH', '/nonexistent/' );
define( 'REPO', getenv( 'REPO' ) ? getenv( 'REPO' ) : '/Users/fujisaki/Dev/wordpress-plugins/yuiamijp-media-link-cards' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'YUIAMIJP_SCAN_LOOKUP_BATCH', 2 ); // 分割の検証のため本番の100より小さくする

$GLOBALS['stub_options']    = array( 'yuiamijp-setting' => array( 'country' => 'JP' ) );
$GLOBALS['stub_transients'] = array();
$GLOBALS['stub_http']       = null;
$GLOBALS['stub_http_calls'] = 0;

function get_option( $name, $default_value = false ) {
	return isset( $GLOBALS['stub_options'][ $name ] ) ? $GLOBALS['stub_options'][ $name ] : $default_value;
}
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['stub_options'][ $name ] = $value;
	return true;
}
function get_transient( $name ) {
	return isset( $GLOBALS['stub_transients'][ $name ] ) ? $GLOBALS['stub_transients'][ $name ] : false;
}
function set_transient( $name, $value, $expiration = 0 ) {
	$GLOBALS['stub_transients'][ $name ] = $value;
	return true;
}
function delete_transient( $name ) {
	unset( $GLOBALS['stub_transients'][ $name ] );
	return true;
}
function __( $text, $domain = '' ) {
	return $text;
}
function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}
class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
	public function get_error_code() {
		return $this->code;
	}
	public function get_error_message() {
		return $this->message;
	}
}
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}
function wp_remote_get( $url, $args = array() ) {
	$GLOBALS['stub_http_calls']++;
	return $GLOBALS['stub_http'];
}
function wp_remote_retrieve_response_code( $response ) {
	return $response['code'];
}
function wp_remote_retrieve_body( $response ) {
	return $response['body'];
}
function http_ok( array $results ) {
	return array( 'code' => 200, 'body' => json_encode( array( 'resultCount' => count( $results ), 'results' => $results ) ) );
}

require REPO . '/inc/status.php';
require REPO . '/inc/scan.php';

$failures = 0;
function check( $label, $actual, $expected ) {
	global $failures;
	if ( $actual === $expected ) {
		echo "  ok   $label\n";
	} else {
		$failures++;
		echo "  FAIL $label\n    expected: " . var_export( $expected, true ) . "\n    actual:   " . var_export( $actual, true ) . "\n";
	}
}
function item( $title ) {
	return array( 'title' => $title, 'type' => 'app', 'icon' => '', 'posts' => array( 1 ) );
}

echo "yuiamijp_collect_cards\n";
$blocks = array(
	array( 'blockName' => 'core/paragraph', 'attrs' => array(), 'innerBlocks' => array() ),
	array( 'blockName' => 'yuiamijp/media-link-cards', 'attrs' => array( 'app' => array( 'id' => 111, 'title' => 'A', 'type' => 'app', 'iconUrl' => 'https://x/a.png' ) ), 'innerBlocks' => array() ),
	array( 'blockName' => 'core/group', 'attrs' => array(), 'innerBlocks' => array(
		array( 'blockName' => 'core/column', 'attrs' => array(), 'innerBlocks' => array(
			array( 'blockName' => 'yuiamijp/media-link-cards', 'attrs' => array( 'app' => array( 'id' => '222', 'title' => 'B', 'type' => 'ebook', 'iconUrl' => 'https://x/b.png' ) ), 'innerBlocks' => array() ),
		) ),
	) ),
	array( 'blockName' => 'yuiamijp/media-link-cards', 'attrs' => array( 'app' => array( 'title' => 'no id' ) ), 'innerBlocks' => array() ),
	array( 'blockName' => 'yuiamijp/media-link-cards', 'attrs' => array(), 'innerBlocks' => array() ),
	array( 'blockName' => null, 'attrs' => array(), 'innerBlocks' => array() ),
	array( 'blockName' => 'yuiamijp/media-link-cards', 'attrs' => array( 'app' => array( 'id' => 111, 'title' => 'A dup' ) ), 'innerBlocks' => array() ),
);
$items = array();
yuiamijp_collect_cards( $blocks, $items, 10 );
yuiamijp_collect_cards( $blocks, $items, 20 );
check( 'ID を持つカードだけ集まる', array_keys( $items ), array( 111, 222 ) );
check( 'ネストしたカードも拾う', $items[222]['type'], 'ebook' );
check( '文字列の id は int に正規化', is_int( array_keys( $items )[1] ), true );
check( '最初に見つけた属性を保持', $items[111]['title'], 'A' );
check( 'iconUrl は icon へ写す', $items[111]['icon'], 'https://x/a.png' );
check( '投稿 ID は重複なく積む', $items[111]['posts'], array( 10, 20 ) );

echo "yuiamijp_match_lookup_results\n";
$results = array(
	array( 'wrapperType' => 'software', 'trackId' => 443904275 ),
	array( 'wrapperType' => 'collection', 'collectionId' => 1440857781 ),
	array( 'wrapperType' => 'track', 'trackId' => 999, 'collectionId' => 888 ),
);
check( 'trackId / collectionId の両方で突き合わせる', yuiamijp_match_lookup_results( array( 443904275, 1440857781, 642099621, 888 ), $results ), array( 443904275, 1440857781, 888 ) );
check( '空の results は全滅', yuiamijp_match_lookup_results( array( 1, 2 ), array() ), array() );

echo "yuiamijp_lookup_ids\n";
$GLOBALS['stub_http'] = http_ok( array( array( 'trackId' => 111 ) ) );
check( '正常なら見つかった ID を返す', yuiamijp_lookup_ids( array( 111, 222 ), 'JP' ), array( 111 ) );
$GLOBALS['stub_http'] = array( 'code' => 500, 'body' => '' );
check( 'HTTP 200 以外は WP_Error', is_wp_error( yuiamijp_lookup_ids( array( 111 ), 'JP' ) ), true );
$GLOBALS['stub_http'] = array( 'code' => 200, 'body' => '<html>maintenance</html>' );
check( 'JSON でなければ WP_Error', is_wp_error( yuiamijp_lookup_ids( array( 111 ), 'JP' ) ), true );
$GLOBALS['stub_http'] = array( 'code' => 200, 'body' => '{"resultCount":0}' );
check( 'results がなければ WP_Error', is_wp_error( yuiamijp_lookup_ids( array( 111 ), 'JP' ) ), true );
$GLOBALS['stub_http'] = new WP_Error( 'http_request_failed', 'timeout' );
check( '通信失敗はそのまま WP_Error', is_wp_error( yuiamijp_lookup_ids( array( 111 ), 'JP' ) ), true );

echo "yuiamijp_scan_check: 初回スキャン（バッチ2件ずつ、3 ID）\n";
$GLOBALS['stub_transients']['yuiamijp_scan_progress'] = array( 'items' => array( 111 => item( 'A' ), 222 => item( 'B' ), 333 => item( 'C' ) ), 'errors' => array() );
$GLOBALS['stub_http'] = http_ok( array( array( 'trackId' => 111 ) ) );
$GLOBALS['stub_http_calls'] = 0;
$r = yuiamijp_scan_check( 0 );
check( '1バッチ目は未完了', $r['done'], false );
check( '次の offset は 2', $r['offset'], 2 );
check( 'processed / total', array( $r['processed'], $r['total'] ), array( 2, 3 ) );
$r = yuiamijp_scan_check( 2 );
check( '2バッチ目で完走', $r['done'], true );
check( 'lookup は2回', $GLOBALS['stub_http_calls'], 2 );
check( '返った 111 は available', $GLOBALS['stub_options']['yuiamijp-scan']['items'][111]['state'], 'available' );
check( '返らない 222 / 333 は unavailable', array( $GLOBALS['stub_options']['yuiamijp-scan']['items'][222]['state'], $GLOBALS['stub_options']['yuiamijp-scan']['items'][333]['state'] ), array( 'unavailable', 'unavailable' ) );
check( 'yuiamijp-status は配信終了だけ', $GLOBALS['stub_options']['yuiamijp-status']['ids'], array( 222, 333 ) );
check( '中間データは削除', isset( $GLOBALS['stub_transients']['yuiamijp_scan_progress'] ), false );

echo "yuiamijp_scan_check: 2回目、lookup が失敗（前回の判定を引き継ぐ）\n";
$GLOBALS['stub_transients']['yuiamijp_scan_progress'] = array( 'items' => array( 111 => item( 'A' ), 222 => item( 'B' ), 444 => item( 'D' ) ), 'errors' => array() );
$GLOBALS['stub_http'] = array( 'code' => 500, 'body' => '' );
$r = yuiamijp_scan_check( 0 );
$r = yuiamijp_scan_check( 2 );
check( '完走はする', $r['done'], true );
check( 'errors は全 ID', $GLOBALS['stub_options']['yuiamijp-scan']['errors'], array( 111, 222, 444 ) );
check( 'レスポンスの errors は件数', $r['errors'], 3 );
check( '111 は前回の available を引き継ぐ', $GLOBALS['stub_options']['yuiamijp-scan']['items'][111]['state'], 'available' );
check( '222 は前回の unavailable を引き継ぐ', $GLOBALS['stub_options']['yuiamijp-scan']['items'][222]['state'], 'unavailable' );
check( '前回なしの 444 は unknown', $GLOBALS['stub_options']['yuiamijp-scan']['items'][444]['state'], 'unknown' );
check( '前回の 333 は今回見つからないので落ちる', isset( $GLOBALS['stub_options']['yuiamijp-scan']['items'][333] ), false );
check( 'yuiamijp-status は 222 だけ', $GLOBALS['stub_options']['yuiamijp-status']['ids'], array( 222 ) );

echo "yuiamijp_scan_check: 中間データが失効\n";
$r = yuiamijp_scan_check( 0 );
check( 'WP_Error yuiamijp_scan_expired', is_wp_error( $r ) ? $r->get_error_code() : null, 'yuiamijp_scan_expired' );

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit( $failures ? 1 : 0 );
```

- [ ] **Step 2: 失敗することを確認する**

Run: `php "$SCRATCH/yuiamijp-tests/test-scan.php"`
Expected: `Failed opening required '.../inc/scan.php'` で終了コード255

- [ ] **Step 3: `inc/define.php`の末尾に定数を足す**

```php

// 配信終了スキャンのバッチサイズ。
// 投稿は50件ずつ parse_blocks() で走査し、ID は100件ずつ lookup へ問い合わせる。
// lookup は id をカンマ区切りで受け、結果は limit に切られない（168件で実測済み）。
// 100件で URL は約1150文字。
define( 'YUIAMIJP_SCAN_POSTS_PER_PAGE', 50 );
define( 'YUIAMIJP_SCAN_LOOKUP_BATCH', 100 );
```

- [ ] **Step 4: `inc/scan.php`を作る**

```php
<?php
/**
 * 配信終了アイテムの一括スキャン
 *
 * 管理画面のボタンから REST の /scan を繰り返し呼び、2つのフェーズで進める。
 *
 * 1. collect — 投稿を50件ずつ parse_blocks() で走査し、カードの id と使用記事を集める
 * 2. check   — 集めた id を100件ずつ iTunes lookup へ問い合わせ、返らなかった id を配信終了とする
 *
 * 途中経過はトランジェント yuiamijp_scan_progress に退避し、完走時に inc/status.php の
 * yuiamijp_save_scan_record() で option へ確定保存する。投稿本文は書き換えない。
 *
 * 通信に失敗したり、レスポンスの JSON が壊れていたバッチは判定を保留し、前回の判定を
 * 引き継ぐ。「返らなかった id は配信終了」という判定は、レスポンスが正常なときにだけ使う。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 走査対象の投稿タイプを返す
 *
 * 検索対象から除外されていない投稿タイプに、再利用ブロック（wp_block）を加える。
 * 再利用ブロックの中身は別投稿に保存されるため、明示的に含めないとカードを見落とす。
 *
 * @return string[]
 */
function yuiamijp_scan_post_types() {
	$types             = get_post_types( array( 'exclude_from_search' => false ) );
	$types['wp_block'] = 'wp_block';

	return array_values( $types );
}

/**
 * 走査対象の投稿ステータスを返す
 *
 * internal でないステータスすべて。publish / draft / pending / private に加え、
 * テーマやプラグインが登録したカスタムステータスも含む。auto-draft・inherit（リビジョン）・
 * trash は internal なので除外される。
 *
 * @return string[]
 */
function yuiamijp_scan_post_statuses() {
	return array_values( get_post_stati( array( 'internal' => false ) ) );
}

/**
 * parse_blocks() の結果からカードを再帰的に集める
 *
 * innerBlocks を降りるので、グループやカラムの中にあるカードも拾う。
 * app.id を持たないカードは判定できないため対象外。
 *
 * @param array $blocks  parse_blocks() の戻り値。
 * @param array $items   収集先。ID をキーに title / type / icon / posts を持つ。参照渡し。
 * @param int   $post_id 走査中の投稿 ID。
 */
function yuiamijp_collect_cards( array $blocks, array &$items, $post_id ) {
	foreach ( $blocks as $block ) {
		if ( isset( $block['blockName'] ) && 'yuiamijp/media-link-cards' === $block['blockName'] ) {
			$app = isset( $block['attrs']['app'] ) && is_array( $block['attrs']['app'] ) ? $block['attrs']['app'] : array();
			$id  = isset( $app['id'] ) ? (int) $app['id'] : 0;

			if ( $id > 0 ) {
				if ( ! isset( $items[ $id ] ) ) {
					$items[ $id ] = array(
						'title' => isset( $app['title'] ) ? (string) $app['title'] : '',
						'type'  => isset( $app['type'] ) ? (string) $app['type'] : '',
						'icon'  => isset( $app['iconUrl'] ) ? (string) $app['iconUrl'] : '',
						'posts' => array(),
					);
				}

				if ( ! in_array( (int) $post_id, $items[ $id ]['posts'], true ) ) {
					$items[ $id ]['posts'][] = (int) $post_id;
				}
			}
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			yuiamijp_collect_cards( $block['innerBlocks'], $items, $post_id );
		}
	}
}

/**
 * lookup の結果から、問い合わせた ID のうち見つかったものを返す
 *
 * 結果の trackId と collectionId の両方と突き合わせる。アプリ・ブック・曲は
 * trackId で、アルバム・オーディオブックは collectionId で返るため。
 *
 * @param int[] $requested 問い合わせた ID。
 * @param array $results   lookup の results 配列。
 * @return int[] 見つかった ID。
 */
function yuiamijp_match_lookup_results( array $requested, array $results ) {
	$found = array();

	foreach ( $results as $result ) {
		foreach ( array( 'trackId', 'collectionId' ) as $key ) {
			if ( isset( $result[ $key ] ) ) {
				$found[ (int) $result[ $key ] ] = true;
			}
		}
	}

	$matched = array();

	foreach ( $requested as $id ) {
		if ( isset( $found[ (int) $id ] ) ) {
			$matched[] = (int) $id;
		}
	}

	return $matched;
}

/**
 * iTunes lookup へ問い合わせ、見つかった ID を返す
 *
 * 通信失敗・200以外・JSON の破損はすべて WP_Error で返す。呼び出し側はそのバッチの
 * 判定を保留する。lookup の結果は毎回最新を取るのでトランジェントには載せない。
 *
 * @param int[]  $ids     問い合わせる ID（YUIAMIJP_SCAN_LOOKUP_BATCH 件まで）。
 * @param string $country ストアの国コード。
 * @return int[]|WP_Error 見つかった ID。
 */
function yuiamijp_lookup_ids( array $ids, $country ) {
	$url = add_query_arg(
		array(
			'id'      => implode( ',', array_map( 'intval', $ids ) ),
			'country' => $country,
		),
		'https://itunes.apple.com/lookup'
	);

	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

	if ( is_wp_error( $response ) ) {
		yuiamijp_log_scan_error( $response->get_error_message() );
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		yuiamijp_log_scan_error( 'iTunes lookup returned HTTP ' . $code );
		return new WP_Error( 'yuiamijp_lookup_http', 'iTunes lookup returned HTTP ' . $code );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
		yuiamijp_log_scan_error( 'iTunes lookup returned invalid JSON' );
		return new WP_Error( 'yuiamijp_lookup_json', 'iTunes lookup returned invalid JSON' );
	}

	return yuiamijp_match_lookup_results( $ids, $data['results'] );
}

/**
 * スキャン中のエラーを WP_DEBUG 有効時にログへ残す
 *
 * 管理画面には件数だけを出すので、原因はここで追えるようにしておく。
 *
 * @param string $message ログに残す内容。
 */
function yuiamijp_log_scan_error( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- 通信失敗の原因を握りつぶさないため。
		error_log( 'yuiamijp Media Link Cards: ' . $message );
	}
}

/**
 * 進行中のスキャンの中間データを返す
 *
 * @return array|false 中間データ。なければ false。
 */
function yuiamijp_get_scan_progress() {
	$progress = get_transient( 'yuiamijp_scan_progress' );

	return is_array( $progress ) ? $progress : false;
}

/**
 * 中間データを退避する（1時間で失効）
 *
 * @param array $progress 中間データ。
 */
function yuiamijp_set_scan_progress( array $progress ) {
	set_transient( 'yuiamijp_scan_progress', $progress, HOUR_IN_SECONDS );
}

/**
 * collect フェーズを1ページ分進める
 *
 * @param int $offset 投稿のオフセット。0 なら新しいスキャンとして中間データを捨てる。
 * @return array REST レスポンスの本体。
 */
function yuiamijp_scan_collect( $offset ) {
	$offset   = max( 0, (int) $offset );
	$progress = 0 === $offset ? false : yuiamijp_get_scan_progress();

	if ( false === $progress ) {
		$progress = array(
			'items'  => array(),
			'errors' => array(),
		);
	}

	$query = new WP_Query(
		array(
			'post_type'              => yuiamijp_scan_post_types(),
			'post_status'            => yuiamijp_scan_post_statuses(),
			'posts_per_page'         => YUIAMIJP_SCAN_POSTS_PER_PAGE,
			'offset'                 => $offset,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $query->posts as $post ) {
		if ( ! has_blocks( $post->post_content ) ) {
			continue;
		}

		yuiamijp_collect_cards( parse_blocks( $post->post_content ), $progress['items'], $post->ID );
	}

	$total     = (int) $query->found_posts;
	$processed = min( $total, $offset + count( $query->posts ) );

	if ( $processed < $total && count( $query->posts ) > 0 ) {
		yuiamijp_set_scan_progress( $progress );

		return array(
			'phase'     => 'collect',
			'offset'    => $processed,
			'total'     => $total,
			'processed' => $processed,
			'done'      => false,
			'errors'    => 0,
		);
	}

	// 走査完了。カードが1件もなければここで完走扱いにする。
	if ( empty( $progress['items'] ) ) {
		yuiamijp_save_scan_record( array(), array() );
		delete_transient( 'yuiamijp_scan_progress' );

		return array(
			'phase'     => 'check',
			'offset'    => 0,
			'total'     => 0,
			'processed' => 0,
			'done'      => true,
			'errors'    => 0,
		);
	}

	yuiamijp_set_scan_progress( $progress );

	return array(
		'phase'     => 'check',
		'offset'    => 0,
		'total'     => count( $progress['items'] ),
		'processed' => 0,
		'done'      => false,
		'errors'    => 0,
	);
}

/**
 * check フェーズを1バッチ分進める
 *
 * @param int $offset ID 一覧のインデックス。
 * @return array|WP_Error REST レスポンスの本体。中間データが失効していれば WP_Error。
 */
function yuiamijp_scan_check( $offset ) {
	$offset   = max( 0, (int) $offset );
	$progress = yuiamijp_get_scan_progress();

	if ( false === $progress ) {
		return new WP_Error(
			'yuiamijp_scan_expired',
			__( 'The scan data has expired. Start the scan again.', 'yuiamijp-media-link-cards' ),
			array( 'status' => 409 )
		);
	}

	$ids   = array_keys( $progress['items'] );
	$total = count( $ids );
	$batch = array_slice( $ids, $offset, YUIAMIJP_SCAN_LOOKUP_BATCH );

	if ( ! empty( $batch ) ) {
		$options = get_option( 'yuiamijp-setting' );
		$country = isset( $options['country'] ) ? $options['country'] : 'JP';
		$found   = yuiamijp_lookup_ids( $batch, $country );

		if ( is_wp_error( $found ) ) {
			$progress['errors'] = array_merge( $progress['errors'], $batch );
		} else {
			foreach ( $batch as $id ) {
				$progress['items'][ $id ]['state'] = in_array( (int) $id, $found, true ) ? 'available' : 'unavailable';
			}
		}
	}

	$processed = min( $total, $offset + count( $batch ) );

	if ( $processed < $total ) {
		yuiamijp_set_scan_progress( $progress );

		return array(
			'phase'     => 'check',
			'offset'    => $processed,
			'total'     => $total,
			'processed' => $processed,
			'done'      => false,
			'errors'    => count( $progress['errors'] ),
		);
	}

	// 全バッチ終了。確認できなかった ID は前回の判定を引き継ぐ。前回もなければ unknown。
	$previous = yuiamijp_get_scan_record();

	foreach ( $progress['items'] as $id => $item ) {
		if ( ! isset( $item['state'] ) ) {
			$progress['items'][ $id ]['state'] = isset( $previous['items'][ $id ]['state'] ) ? $previous['items'][ $id ]['state'] : 'unknown';
		}
	}

	yuiamijp_save_scan_record( $progress['items'], $progress['errors'] );
	delete_transient( 'yuiamijp_scan_progress' );

	return array(
		'phase'     => 'check',
		'offset'    => $total,
		'total'     => $total,
		'processed' => $total,
		'done'      => true,
		'errors'    => count( $progress['errors'] ),
	);
}

/**
 * /scan の権限チェック。設定を触れる管理者だけに許可する
 *
 * @return bool
 */
function yuiamijp_rest_scan_permission_check() {
	return current_user_can( 'manage_options' );
}

/**
 * /scan のコールバック
 *
 * @param WP_REST_Request $request リクエスト。phase と offset を持つ。
 * @return WP_REST_Response|WP_Error
 */
function yuiamijp_rest_scan_callback( $request ) {
	$phase  = $request->get_param( 'phase' );
	$offset = (int) $request->get_param( 'offset' );

	$result = 'check' === $phase ? yuiamijp_scan_check( $offset ) : yuiamijp_scan_collect( $offset );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return new WP_REST_Response( $result, 200 );
}
```

- [ ] **Step 5: テストが通ることを確認する**

Run: `php "$SCRATCH/yuiamijp-tests/test-scan.php"`
Expected: すべて`ok`、`ALL PASSED`、終了コード0

- [ ] **Step 6: RESTルートを登録する**

`inc/api.php`の`yuiamijp_register_rest_routes()`を次のようにする（既存の`/search`の後に`/scan`を足す）。

```php
function yuiamijp_register_rest_routes() {
	register_rest_route(
		'yuiamijp-media-link-cards/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'yuiamijp_rest_api_search_callback',
			'permission_callback' => 'yuiamijp_rest_api_search_permission_check',
		)
	);

	// 配信終了アイテムの一括スキャン。設定を触れる管理者だけに許可する。
	register_rest_route(
		'yuiamijp-media-link-cards/v1',
		'/scan',
		array(
			'methods'             => 'POST',
			'callback'            => 'yuiamijp_rest_scan_callback',
			'permission_callback' => 'yuiamijp_rest_scan_permission_check',
			'args'                => array(
				'phase'  => array(
					'type'    => 'string',
					'enum'    => array( 'collect', 'check' ),
					'default' => 'collect',
				),
				'offset' => array(
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				),
			),
		)
	);
}
```

- [ ] **Step 7: メインファイルで読み込む**

`yuiamijp-media-link-cards.php`の`require_once`の並びを次のようにする（`inc/scan.php`を`inc/status.php`の直後に足す）。

```php
require_once plugin_dir_path( __FILE__ ) . 'inc/define.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/icons.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/status.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/scan.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/api.php';
```

- [ ] **Step 8: lintを通す**

Run: `php -l inc/scan.php && php -l inc/api.php && php -l inc/define.php && phpcs`
Expected: 構文エラーなし、phpcsは指摘なし

- [ ] **Step 9: コミット**

```bash
git add inc/scan.php inc/define.php inc/api.php yuiamijp-media-link-cards.php
git commit -m "配信終了アイテムを一括で検出するスキャンを追加する

RESTの/scanをcollectとcheckの2フェーズで繰り返し呼ぶ。collectで全投稿を
parse_blocks()で走査してカードのidと使用記事を集め、checkでiTunes lookupへ
100件ずつ問い合わせる。返らなかったidを配信終了とする。

通信失敗やJSONの破損があったバッチは判定を保留し、前回の判定を引き継ぐ。

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 3: フロントの表示切替（`src/render.php` / `src/style.css`）

**Files:**
- Modify: `src/render.php`（全体を置き換える）
- Modify: `src/style.css`（`@container (width >= 32rem) {`の直前に挿入）
- Test: `$SCRATCH/yuiamijp-tests/test-render.php`（リポジトリ外）

**Interfaces:**
- Consumes: Task 1の`yuiamijp_is_unavailable()`
- Produces: 配信終了時のマークアップ。ルートに`yuiamijp-unavailable`、`.yuiamijp-figure`と`.yuiamijp-title`は`<span>`、`.yuiamijp-btns`の中は`<span class="yuiamijp-ended yuiamijp-btn"><span class="yuiamijp-btn-label">No longer available</span></span>`だけ。Task 4のエディター側はこれと同じ構造にする

- [ ] **Step 1: 失敗するテストを書く**

`$SCRATCH/yuiamijp-tests/test-render.php`を作る。`render.php`が使うWordPress関数をスタブし、`$attributes`を渡して`include`する。

```php
<?php
// src/render.php の分岐を WordPress なしで検証する書き捨てスクリプト。リポジトリには入れない。
define( 'ABSPATH', '/nonexistent/' );
define( 'REPO', getenv( 'REPO' ) ? getenv( 'REPO' ) : '/Users/fujisaki/Dev/wordpress-plugins/yuiamijp-media-link-cards' );

$GLOBALS['stub_options'] = array(
	'yuiamijp-status' => array( 'ids' => array( 642099621 ), 'checked' => 1 ),
);
function get_option( $name, $default_value = false ) {
	return isset( $GLOBALS['stub_options'][ $name ] ) ? $GLOBALS['stub_options'][ $name ] : $default_value;
}
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }
function esc_html__( $s, $d = '' ) { return esc_html( $s ); }
function wp_kses( $s, $allowed ) { return $s; }
function get_block_wrapper_attributes() { return 'class="wp-block-yuiamijp-media-link-cards"'; }
function yuiamijp_get_icons() { return array( 'preview' => 'play' ); }
function yuiamijp_get_store( $type ) { return array( 'label' => 'App Store', 'icon' => 'appstore' ); }
function yuiamijp_get_icon_svg( $name ) { return '<svg data-icon="' . $name . '"></svg>'; }
function yuiamijp_get_svg_allowed_html() { return array(); }

require REPO . '/inc/status.php';

function render_card( array $app ) {
	$attributes = array( 'app' => $app );
	ob_start();
	include REPO . '/src/render.php';
	return ob_get_clean();
}

$failures = 0;
function check( $label, $actual, $expected ) {
	global $failures;
	if ( $actual === $expected ) {
		echo "  ok   $label\n";
	} else {
		$failures++;
		echo "  FAIL $label\n    expected: " . var_export( $expected, true ) . "\n    actual:   " . var_export( $actual, true ) . "\n";
	}
}

$base = array(
	'type'       => 'app',
	'title'      => 'Flappy Bird',
	'url'        => 'https://apps.apple.com/jp/app/id642099621',
	'artist'     => 'GEARS',
	'iconUrl'    => 'https://example.com/icon.png',
	'previewUrl' => 'https://example.com/preview.m4a',
);

echo "配信終了のカード\n";
$html = render_card( array_merge( $base, array( 'id' => 642099621 ) ) );
check( 'ルートに yuiamijp-unavailable', str_contains( $html, 'class="yuiamijp yuiamijp-app yuiamijp-unavailable"' ), true );
check( '<a> を出さない', str_contains( $html, '<a ' ), false );
check( 'タイトルは span', str_contains( $html, '<span class="yuiamijp-title">Flappy Bird</span>' ), true );
check( 'アートワークは span で包む', str_contains( $html, '<span class="yuiamijp-figure">' ), true );
check( '試聴ボタンを出さない', str_contains( $html, 'yuiamijp-audition' ), false );
check( 'ストアボタンを出さない', str_contains( $html, 'yuiamijp-store' ), false );
check( '配信終了ラベルを出す', str_contains( $html, '<span class="yuiamijp-ended yuiamijp-btn">' ) && str_contains( $html, 'No longer available' ), true );
check( '作者名は残る', str_contains( $html, '<div class="yuiamijp-artist">GEARS</div>' ), true );

echo "生存しているカード\n";
$html = render_card( array_merge( $base, array( 'id' => 443904275 ) ) );
check( 'yuiamijp-unavailable が付かない', str_contains( $html, 'yuiamijp-unavailable' ), false );
check( 'アートワークがリンク', str_contains( $html, '<a class="yuiamijp-figure" href="https://apps.apple.com/jp/app/id642099621"' ), true );
check( 'ストアボタンあり', str_contains( $html, 'yuiamijp-store yuiamijp-btn' ), true );
check( '試聴ボタンあり', str_contains( $html, 'yuiamijp-audition yuiamijp-btn' ), true );
check( '配信終了ラベルなし', str_contains( $html, 'yuiamijp-ended' ), false );

echo "id を持たない古いカード\n";
$html = render_card( $base );
check( '従来どおりリンク付きで出す', str_contains( $html, '<a class="yuiamijp-title"' ) && ! str_contains( $html, 'yuiamijp-unavailable' ), true );

echo "url が空のカード\n";
check( '何も出さない', render_card( array_merge( $base, array( 'url' => '', 'id' => 642099621 ) ) ), '' );

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit( $failures ? 1 : 0 );
```

- [ ] **Step 2: 失敗することを確認する**

Run: `php "$SCRATCH/yuiamijp-tests/test-render.php"`
Expected: 「配信終了のカード」の項目が`FAIL`（現行の`render.php`は分岐を持たない）。終了コード1

- [ ] **Step 3: `src/render.php`を置き換える**

```php
<?php
/**
 * Media Link Card ブロックのフロント側の出力
 *
 * アイコンとストア名は assets/icons.json が単一の情報源。
 * エディター側のプレビューは src/components/MediaLinkCard.js が同じ JSON を読む。
 * マークアップを変えるときは両方を揃えること。
 *
 * 配信終了と判定されたカード（inc/status.php の yuiamijp_is_unavailable()）は
 * リンクを外し、試聴とストアのボタンの代わりに「配信終了」ラベルを出す。
 *
 * @package yuiamijp-media-link-cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$yuiamijp_app         = isset( $attributes['app'] ) ? $attributes['app'] : array();
$yuiamijp_id          = isset( $yuiamijp_app['id'] ) ? $yuiamijp_app['id'] : 0;
$yuiamijp_type        = isset( $yuiamijp_app['type'] ) ? $yuiamijp_app['type'] : '';
$yuiamijp_url         = isset( $yuiamijp_app['url'] ) ? $yuiamijp_app['url'] : '';
$yuiamijp_title       = isset( $yuiamijp_app['title'] ) ? $yuiamijp_app['title'] : '';
$yuiamijp_artist      = isset( $yuiamijp_app['artist'] ) ? $yuiamijp_app['artist'] : '';
$yuiamijp_icon        = isset( $yuiamijp_app['iconUrl'] ) ? $yuiamijp_app['iconUrl'] : '';
$yuiamijp_preview_url = isset( $yuiamijp_app['previewUrl'] ) ? $yuiamijp_app['previewUrl'] : '';

if ( empty( $yuiamijp_url ) || empty( $yuiamijp_title ) ) {
	return;
}

$yuiamijp_unavailable        = yuiamijp_is_unavailable( $yuiamijp_id );
$yuiamijp_icons              = yuiamijp_get_icons();
$yuiamijp_store              = yuiamijp_get_store( $yuiamijp_type );
$yuiamijp_store_label        = isset( $yuiamijp_store['label'] ) ? $yuiamijp_store['label'] : '';
$yuiamijp_store_icon         = isset( $yuiamijp_store['icon'] ) ? $yuiamijp_store['icon'] : '';
$yuiamijp_preview_icon       = isset( $yuiamijp_icons['preview'] ) ? $yuiamijp_icons['preview'] : '';
$yuiamijp_wrapper_attributes = get_block_wrapper_attributes();
$yuiamijp_card_class         = 'yuiamijp yuiamijp-' . $yuiamijp_type . ( $yuiamijp_unavailable ? ' yuiamijp-unavailable' : '' );

?>

<div <?php echo $yuiamijp_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns markup already escaped by WordPress core. ?>>
	<div class="<?php echo esc_attr( $yuiamijp_card_class ); ?>">
		<?php if ( $yuiamijp_unavailable ) : ?>
			<span class="yuiamijp-figure">
				<img class="yuiamijp-img" src="<?php echo esc_url( $yuiamijp_icon ); ?>" alt="<?php echo esc_attr( $yuiamijp_title ); ?>" />
			</span>
		<?php else : ?>
			<a class="yuiamijp-figure" href="<?php echo esc_url( $yuiamijp_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
				<img class="yuiamijp-img" src="<?php echo esc_url( $yuiamijp_icon ); ?>" alt="<?php echo esc_attr( $yuiamijp_title ); ?>" />
			</a>
		<?php endif; ?>
		<div class="yuiamijp-content">
			<div class="yuiamijp-info">
				<?php if ( $yuiamijp_unavailable ) : ?>
					<span class="yuiamijp-title"><?php echo esc_html( $yuiamijp_title ); ?></span>
				<?php else : ?>
					<a class="yuiamijp-title" href="<?php echo esc_url( $yuiamijp_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
						<?php echo esc_html( $yuiamijp_title ); ?>
					</a>
				<?php endif; ?>
				<div class="yuiamijp-artist"><?php echo esc_html( $yuiamijp_artist ); ?></div>
			</div>

			<div class="yuiamijp-btns">
				<?php if ( $yuiamijp_unavailable ) : ?>
					<span class="yuiamijp-ended yuiamijp-btn">
						<span class="yuiamijp-btn-label"><?php echo esc_html__( 'No longer available', 'yuiamijp-media-link-cards' ); ?></span>
					</span>
				<?php else : ?>
					<?php if ( ! empty( $yuiamijp_preview_url ) ) : ?>
						<a class="yuiamijp-audition yuiamijp-btn" href="<?php echo esc_url( $yuiamijp_preview_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
							<?php echo wp_kses( yuiamijp_get_icon_svg( $yuiamijp_preview_icon ), yuiamijp_get_svg_allowed_html() ); ?>
							<span class="yuiamijp-btn-label"><?php echo esc_html__( 'Preview', 'yuiamijp-media-link-cards' ); ?></span>
						</a>
					<?php endif; ?>
					<a class="yuiamijp-store yuiamijp-btn" href="<?php echo esc_url( $yuiamijp_url ); ?>" target="_blank" rel="noopener nofollow noreferrer">
						<?php echo wp_kses( yuiamijp_get_icon_svg( $yuiamijp_store_icon ), yuiamijp_get_svg_allowed_html() ); ?>
						<span class="yuiamijp-btn-label"><?php echo esc_html( $yuiamijp_store_label ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
```

- [ ] **Step 4: テストが通ることを確認する**

Run: `php "$SCRATCH/yuiamijp-tests/test-render.php"`
Expected: すべて`ok`、`ALL PASSED`、終了コード0

- [ ] **Step 5: スタイルを足す**

`src/style.css`の`@container (width >= 32rem) {`の直前（`:is(.yuiamijp-music-track, ...)`のブロックの後）に次を挿入する。

```css
/* 配信終了と判定されたカード。リンクを外し、アイコンをグレーにする */
.yuiamijp-unavailable {

	.yuiamijp-img {
		opacity: 0.55;
		filter: grayscale(1);
	}

	.yuiamijp-title {
		color: var(--_color-text-muted) !important;
		cursor: default;
	}
}

/* ストアボタンの位置に置く「配信終了」ラベル。ボタンではないのでホバーで変化させない */
.yuiamijp-ended {
	cursor: default;
	color: var(--_color-text-muted);
	background-color: var(--_color-bg-subtle);

	&:hover {
		background-color: var(--_color-bg-subtle);
	}
}

```

- [ ] **Step 6: lintとビルドを通す**

Run: `php -l src/render.php && phpcs && pnpm lint:css && pnpm build && grep -c 'yuiamijp-unavailable' build/render.php build/style-index.css`
Expected: 構文エラーなし、phpcs・stylelintとも指摘なし、ビルド成功、`build/render.php`と`build/style-index.css`の両方で1以上

- [ ] **Step 7: コミット**

```bash
git add src/render.php src/style.css
git commit -m "配信終了と判定されたカードをリンクなしで表示する

yuiamijp_is_unavailable()がtrueのカードは、アートワークとタイトルの<a>を
<span>にし、試聴とストアのボタンの代わりに「配信終了」ラベルを出す。
タイトルと作者名は残すので、記事の文脈は壊れない。

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 4: エディターの表示（`MediaLinkCard.js` / `edit.js`）

**Files:**
- Modify: `src/components/MediaLinkCard.js`（全体を置き換える）
- Modify: `src/edit.js:33-43`（分解代入）と`:374`付近（`MediaLinkCard`の呼び出し）
- Modify: `yuiamijp-media-link-cards.php`（`yuiamijp_admin_enqueue_scripts()`）

**Interfaces:**
- Consumes: Task 1の`yuiamijp_get_unavailable_ids()`。Task 3のマークアップ
- Produces: `yuiamijpAjaxValues.unavailableIds: number[]`。`MediaLinkCard`の`unavailable: boolean`プロップ

- [ ] **Step 1: `src/components/MediaLinkCard.js`を置き換える**

```jsx
/**
 * カードのプレビュー
 *
 * マークアップはフロントの src/render.php と二重管理。クラス名や構造を変えるときは
 * 両方を直す。
 *
 * unavailable が true のカード（配信終了）はリンクを外し、試聴とストアのボタンの
 * 代わりに「配信終了」ラベルを出す。
 */
import { PreviewIcon, StoreIcon } from './StoreIcon';
import { __ } from '@wordpress/i18n';

const MediaLinkCard = ( { app, isEditor = false, unavailable = false } ) => {
	const LinkWrapper = ( { href, children, className, ...props } ) => {
		if ( isEditor || unavailable ) {
			return <span className={ className }>{ children }</span>;
		}
		return (
			<a href={ href } className={ className } { ...props }>
				{ children }
			</a>
		);
	};

	const className = `yuiamijp yuiamijp-${ app.type }${
		unavailable ? ' yuiamijp-unavailable' : ''
	}`;

	return (
		<div className={ className }>
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
					{ unavailable ? (
						<span className="yuiamijp-ended yuiamijp-btn">
							<span className="yuiamijp-btn-label">
								{ __(
									'No longer available',
									'yuiamijp-media-link-cards'
								) }
							</span>
						</span>
					) : (
						<>
							{ app.previewUrl && (
								<LinkWrapper
									className="yuiamijp-audition yuiamijp-btn"
									href={ app.previewUrl }
									target="_blank"
									rel="noopener nofollow noreferrer"
								>
									<PreviewIcon />
									<span className="yuiamijp-btn-label">
										{ __(
											'Preview',
											'yuiamijp-media-link-cards'
										) }
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
						</>
					) }
				</div>
			</div>
		</div>
	);
};

export default MediaLinkCard;
```

- [ ] **Step 2: `src/edit.js`で受け取って渡す**

分解代入に`unavailableIds`を足す。

```js
const {
	options,
	optionsPageUrl,
	limitValues,
	countryValues,
	countryToLangMap,
	restUrl,
	unavailableIds = [],
} =
	// eslint-disable-next-line no-undef
	yuiamijpAjaxValues;
```

`return`の中の`{ hasApp && <MediaLinkCard app={ app } isEditor={ true } /> }`を次に置き換える。`app.id`が文字列で保存されている古いカードに備えて`Number()`で揃える。

```jsx
			{ hasApp && (
				<MediaLinkCard
					app={ app }
					isEditor={ true }
					unavailable={ unavailableIds.includes( Number( app.id ) ) }
				/>
			) }
```

- [ ] **Step 3: PHPからIDを渡す**

`yuiamijp-media-link-cards.php`の`yuiamijp_admin_enqueue_scripts()`で、`wp_json_encode()`に渡す配列の`'restUrl'`の次に1行足す。

```php
				// nonce は wp-api-fetch がコア側で付与するため渡さない。
				'restUrl'          => esc_url_raw( rest_url( 'yuiamijp-media-link-cards/v1/' ) ),
				// 配信終了と判定された ID。エディターのプレビューをフロントと揃えるため。
				'unavailableIds'   => yuiamijp_get_unavailable_ids(),
```

- [ ] **Step 4: lintとビルドを通す**

Run: `pnpm lint:js && phpcs && pnpm build && grep -c 'No longer available' build/index.js`
Expected: ESLintは既存のwarning（`edit.js`の`react-hooks/exhaustive-deps`）以外なし、phpcs指摘なし、ビルド成功、`build/index.js`に1以上

- [ ] **Step 5: コミット**

```bash
git add src/components/MediaLinkCard.js src/edit.js yuiamijp-media-link-cards.php
git commit -m "エディターのプレビューでも配信終了カードをフロントと同じ見た目にする

配信終了IDの配列をyuiamijpAjaxValues.unavailableIdsで渡し、MediaLinkCardの
unavailableプロップでリンクなしの表示に切り替える。

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 5: 管理画面のLink checkセクション（`inc/admin-page.php` / `assets/admin.js`）

**Files:**
- Create: `assets/admin.js`
- Modify: `inc/admin-page.php:27-42`（`yuiamijp_options_page_html()`）と末尾
- Modify: `package.json:10-13`（`format`と`lint:js`）

**Interfaces:**
- Consumes: Task 1の`yuiamijp_get_scan_record()`、Task 2のREST `/scan`、`inc/icons.php`の`yuiamijp_get_store()`
- Produces: `yuiamijpScan`グローバル（`scanUrl: string`, `i18n: { starting, collecting, checking, done, failed }`）。DOM `#yuiamijp-scan-start`（ボタン）と`#yuiamijp-scan-status`（進捗表示）

- [ ] **Step 1: `assets/admin.js`を作る**

```js
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
		template.replace( '%1$d', processed ).replace( '%2$d', total );

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
```

`wp`はESLintの組み込みグローバルなので`/* global */`に書かない（書くと`no-redeclare`でエラーになる）。

- [ ] **Step 2: lintの対象に加える**

`package.json`の`scripts`を次のようにする（`format`と`lint:js`に`assets/admin.js`を足す）。

```json
		"format": "wp-scripts format src/*.js src/components assets/admin.js && wp-scripts lint-style \"src/*.css\" --fix",
		"lint:css": "wp-scripts lint-style \"src/*.css\"",
		"lint:js": "wp-scripts lint-js src/*.js src/components assets/admin.js",
```

- [ ] **Step 3: JSのlintを通す**

Run: `pnpm lint:js && pnpm format && git status --short`
Expected: lintは既存のwarning以外なし。`pnpm format`後の`git status`で変わっているのは`assets/admin.js`と`package.json`だけ（整形差分が出ていないこと。`.github/`や`pnpm-lock.yaml`が変わっていたら`wp-scripts format`の引数が落ちている。`git checkout -- <ファイル>`で戻す）

- [ ] **Step 4: `inc/admin-page.php`にセクションを足す**

`yuiamijp_options_page_html()`を次のようにする（`</form>`の後で`yuiamijp_render_scan_section()`を呼ぶ）。

```php
function yuiamijp_options_page_html() {
	?>
	<div class="wrap">
	<h2>yuiamijp Media Link Cards</h2>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'yuiamijp-setting' );
		do_settings_sections( 'yuiamijp-setting' );
		submit_button();
		?>
	</form>

	<?php yuiamijp_render_scan_section(); ?>
	</div>
	<?php
}
```

ファイル末尾（`yuiamijp_lang_callback()`の後）に次を追加する。

```php

/**
 * 設定ページでだけ Link check 用のスクリプトを読み込む
 *
 * 表示文字列は PHP 側で翻訳して渡す。このプラグインは .json 翻訳を同梱しないため、
 * JavaScript 側の __() は言語パックの生成状況に左右される。
 *
 * @param string $hook_suffix 現在の管理画面のフック名。
 */
function yuiamijp_enqueue_scan_script( $hook_suffix ) {
	if ( 'settings_page_yuiamijp-media-link-cards' !== $hook_suffix ) {
		return;
	}

	$path = plugin_dir_path( __DIR__ ) . 'assets/admin.js';

	wp_enqueue_script(
		'yuiamijp-scan',
		plugin_dir_url( __DIR__ ) . 'assets/admin.js',
		array( 'wp-api-fetch' ),
		(string) filemtime( $path ),
		true
	);

	wp_localize_script(
		'yuiamijp-scan',
		'yuiamijpScan',
		array(
			'scanUrl' => esc_url_raw( rest_url( 'yuiamijp-media-link-cards/v1/scan' ) ),
			'i18n'    => array(
				'starting'   => __( 'Starting…', 'yuiamijp-media-link-cards' ),
				/* translators: 1: number of posts scanned so far, 2: total number of posts */
				'collecting' => __( 'Scanning posts… %1$d / %2$d', 'yuiamijp-media-link-cards' ),
				/* translators: 1: number of items checked so far, 2: total number of items */
				'checking'   => __( 'Checking items… %1$d / %2$d', 'yuiamijp-media-link-cards' ),
				'done'       => __( 'Scan complete. Reloading…', 'yuiamijp-media-link-cards' ),
				'failed'     => __( 'Scan failed.', 'yuiamijp-media-link-cards' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'yuiamijp_enqueue_scan_script' );

/**
 * Link check セクションを出力する
 *
 * スキャンの実行ボタンと前回の結果。配信終了アイテムの一覧には、使用している
 * 投稿への編集リンクを付ける。再利用ブロックに入っていたカードは、その再利用
 * ブロック自体の編集画面を指す。
 */
function yuiamijp_render_scan_section() {
	$record = yuiamijp_get_scan_record();
	?>
	<h2><?php echo esc_html__( 'Link check', 'yuiamijp-media-link-cards' ); ?></h2>
	<p><?php echo esc_html__( 'Checks every card on this site against Apple\'s catalog and marks the items that are no longer available. Marked cards are shown without links on the front end.', 'yuiamijp-media-link-cards' ); ?></p>
	<p>
		<button type="button" class="button button-secondary" id="yuiamijp-scan-start"><?php echo esc_html__( 'Start scan', 'yuiamijp-media-link-cards' ); ?></button>
		<span id="yuiamijp-scan-status" class="description"></span>
	</p>
	<?php
	if ( empty( $record ) ) {
		echo '<p>' . esc_html__( 'Never scanned.', 'yuiamijp-media-link-cards' ) . '</p>';
		return;
	}

	printf(
		'<p>%s</p>',
		esc_html(
			sprintf(
				/* translators: %s: date and time of the last scan */
				__( 'Last scanned: %s', 'yuiamijp-media-link-cards' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $record['finished'] )
			)
		)
	);

	printf(
		'<p>%s</p>',
		esc_html(
			sprintf(
				/* translators: 1: number of items checked, 2: number of items no longer available */
				__( '%1$d items checked, %2$d no longer available.', 'yuiamijp-media-link-cards' ),
				(int) $record['total'],
				(int) $record['dead']
			)
		)
	);

	if ( ! empty( $record['errors'] ) ) {
		printf(
			'<div class="notice notice-warning inline"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of items that could not be checked */
					__( 'Could not check %d items. Their previous status was kept. Try again later.', 'yuiamijp-media-link-cards' ),
					count( $record['errors'] )
				)
			)
		);
	}

	$dead_items = array_filter(
		$record['items'],
		static function ( $item ) {
			return isset( $item['state'] ) && 'unavailable' === $item['state'];
		}
	);

	if ( empty( $dead_items ) ) {
		return;
	}
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"></th>
				<th scope="col"><?php echo esc_html__( 'Title', 'yuiamijp-media-link-cards' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Type', 'yuiamijp-media-link-cards' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'ID', 'yuiamijp-media-link-cards' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Used in', 'yuiamijp-media-link-cards' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $dead_items as $id => $item ) : ?>
				<?php
				$store = yuiamijp_get_store( $item['type'] );
				$label = isset( $store['label'] ) ? $store['label'] : $item['type'];
				$links = array();

				foreach ( $item['posts'] as $post_id ) {
					$edit_link = get_edit_post_link( $post_id );

					// 削除済みの投稿は飛ばす
					if ( ! $edit_link ) {
						continue;
					}

					$post_title = get_the_title( $post_id );
					$links[]    = sprintf(
						'<a href="%s">%s</a>',
						esc_url( $edit_link ),
						esc_html( '' !== $post_title ? $post_title : '#' . $post_id )
					);
				}
				?>
				<tr>
					<td>
						<?php if ( ! empty( $item['icon'] ) ) : ?>
							<img src="<?php echo esc_url( $item['icon'] ); ?>" alt="" width="40" height="40" />
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $item['title'] ); ?></td>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo esc_html( (string) $id ); ?></td>
					<td><?php echo wp_kses_post( implode( ', ', $links ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
```

- [ ] **Step 5: PHPのlintを通す**

Run: `php -l inc/admin-page.php && phpcs`
Expected: 構文エラーなし、phpcs指摘なし

- [ ] **Step 6: コミット**

```bash
git add assets/admin.js inc/admin-page.php package.json
git commit -m "設定ページに配信終了アイテムのスキャンと一覧を追加する

Link checkセクションから管理者がスキャンを実行できる。進捗はassets/admin.jsが
RESTの/scanを繰り返し呼んで表示し、完走後は配信終了アイテムの一覧と使用している
投稿への編集リンクをPHP側で描画する。

表示文字列はPHPで翻訳してwp_localize_script()で渡す。.json翻訳を同梱しないため。

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 6: 翻訳（`.pot` / `.po`）

**Files:**
- Modify: `languages/yuiamijp-media-link-cards.pot`（再生成）
- Modify: `languages/yuiamijp-media-link-cards-ja.po`

**Interfaces:**
- Consumes: Task 2〜5で追加した表示文字列18件

- [ ] **Step 1: ビルドしてから`.pot`を再生成する**

`.pot`は`build/`の参照も持つので、先にビルドを最新にする。

Run:
```bash
pnpm build && wp i18n make-pot . languages/yuiamijp-media-link-cards.pot --slug=yuiamijp-media-link-cards --exclude=node_modules
```
Expected: `Success: POT file successfully generated.`

- [ ] **Step 2: 日本語訳を`.po`に追加する**

まだ`.po`にない`msgid`だけを末尾に追加する（既にあるものは重複させない）。`$SCRATCH/yuiamijp-tests/add-ja.py`として保存して実行する。

```python
#!/usr/bin/env python3
# 新しい表示文字列の日本語訳を .po の末尾に追加する。既にある msgid は飛ばす。
import os

repo = os.environ.get('REPO', '/Users/fujisaki/Dev/wordpress-plugins/yuiamijp-media-link-cards')
po = os.path.join(repo, 'languages/yuiamijp-media-link-cards-ja.po')

entries = [
    ('No longer available', '配信終了'),
    ('Link check', 'リンクチェック'),
    ("Checks every card on this site against Apple's catalog and marks the items that are no longer available. Marked cards are shown without links on the front end.",
     'このサイトのすべてのカードをAppleのカタログと照合し、配信が終了したアイテムに印を付けます。印の付いたカードは、フロントエンドではリンクなしで表示されます。'),
    ('Start scan', 'スキャンを開始'),
    ('Never scanned.', 'まだスキャンしていません。'),
    ('Last scanned: %s', '最終スキャン: %s'),
    ('%1$d items checked, %2$d no longer available.', '%1$d件を確認し、%2$d件が配信終了でした。'),
    ('Could not check %d items. Their previous status was kept. Try again later.',
     '%d件を確認できませんでした。前回の判定を維持しています。しばらくしてから再実行してください。'),
    ('Title', 'タイトル'),
    ('Type', '種別'),
    ('ID', 'ID'),
    ('Used in', '使用している投稿'),
    ('Starting…', '開始しています…'),
    ('Scanning posts… %1$d / %2$d', '投稿を走査しています… %1$d / %2$d'),
    ('Checking items… %1$d / %2$d', 'アイテムを確認しています… %1$d / %2$d'),
    ('Scan complete. Reloading…', 'スキャンが完了しました。再読み込みします…'),
    ('Scan failed.', 'スキャンに失敗しました。'),
    ('The scan data has expired. Start the scan again.', 'スキャンのデータが失効しました。もう一度スキャンを開始してください。'),
]

def quote(s):
    return '"' + s.replace('\\', '\\\\').replace('"', '\\"') + '"'

content = open(po, encoding='utf-8').read()
added = []
for msgid, msgstr in entries:
    if 'msgid ' + quote(msgid) in content:
        continue
    content = content.rstrip('\n') + '\n\nmsgid ' + quote(msgid) + '\nmsgstr ' + quote(msgstr) + '\n'
    added.append(msgid)

open(po, 'w', encoding='utf-8').write(content)
print(f'{len(added)} entries added')
```

Run: `python3 "$SCRATCH/yuiamijp-tests/add-ja.py"`
Expected: `18 entries added`（既存と重なる`msgid`があればその分少ない）

- [ ] **Step 3: `.pot`と突き合わせて整える**

Run:
```bash
msgmerge --update --backup=none languages/yuiamijp-media-link-cards-ja.po languages/yuiamijp-media-link-cards.pot
msgfmt --statistics -o /dev/null languages/yuiamijp-media-link-cards-ja.po
```
Expected: `81 translated messages.`（63 + 18）。`untranslated`と`fuzzy`が出ないこと。fuzzyが出たら該当エントリの`#, fuzzy`を外して訳を確認する

- [ ] **Step 4: 参照が両方に付いていることを確認する**

Run: `grep -B4 'msgid "No longer available"' languages/yuiamijp-media-link-cards.pot`
Expected: 直前の`#:`行に`build/render.php`・`src/render.php`・`build/index.js`・`src/components/MediaLinkCard.js`の4つが含まれる（`build/`を除外していない証拠）

- [ ] **Step 5: コミット**

```bash
git add languages/yuiamijp-media-link-cards.pot languages/yuiamijp-media-link-cards-ja.po
git commit -m "配信終了の検出で追加した文字列の翻訳を更新する

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 7: readme・バージョン・配布設定・CLAUDE.md

**Files:**
- Modify: `readme.txt`
- Modify: `yuiamijp-media-link-cards.php:8`（`Version:`）
- Modify: `package.json:3`（`version`）
- Modify: `.distignore`
- Modify: `CLAUDE.md`

**Interfaces:**
- Consumes: 全タスクの成果

- [ ] **Step 1: `readme.txt`のExternal serviceを書き換える**

`= External service =`から`= Affiliate links =`の直前までを次に置き換える。審査を通った文面の「フロントは接続しない」は維持し、管理画面からのlookupを追記する。

```
= External service =

This plugin relies on the iTunes Search API, a third-party service provided by Apple, to search the Apple ecosystem, to retrieve the title, artwork, artist and store URL of the item you pick, and to check whether the items you have already embedded are still available. The plugin cannot provide these features without it.

**When a request is made.** Only in two situations, both started by a logged-in user of your site:

1. In the block editor, when a user with the `edit_posts` capability types a search term and presses Enter.
2. On the settings page, when an administrator (`manage_options` capability) clicks "Start scan" under "Link check". The plugin then looks up the items embedded on your site to find the ones that are no longer available.

Your site's front end never contacts the service: published posts render from data already stored in your database, so your visitors make no request to Apple.

**What is sent.** For a search: the search term you typed, the content type (app, book, podcast, music, and so on), the store country, the display language, the number of results, and — only if you have entered one yourself — your affiliate token. For a link check: the Apple item IDs stored in your cards (public identifiers assigned by Apple) and the store country. No personal data, and no information about your site or its visitors, is sent.

**Caching.** Search responses are stored in your own database as transients for 12 hours to reduce the number of requests. Link check results are stored as options until the next scan.

- Search endpoint: https://itunes.apple.com/search
- Lookup endpoint: https://itunes.apple.com/lookup
- About the API: https://performance-partners.apple.com/search-api
- Apple Media Services Terms and Conditions: https://www.apple.com/legal/internet-services/itunes/
- Apple Privacy Policy: https://www.apple.com/legal/privacy/

```

- [ ] **Step 2: Description・FAQ・Changelog・Stable tagを更新する**

Descriptionの箇条書き`- Simple settings page for defaults`の次に1行足す。

```
- Link check: finds cards whose items are no longer available and shows them without links
```

FAQの`= Does the plugin send anything to a third party? =`の回答を次に置き換える。

```
Yes. Searching in the block editor and running the link check on the settings page both query Apple's iTunes Search API. Your site's front end never contacts it. See "External service" in the description for exactly what is sent and when.
```

FAQの末尾（`= Which countries and languages are supported? =`の回答の後）に1問足す。

```
= What happens to a card when the item is removed from the store? =
Run the link check from Settings -> Media Link Cards. Cards for items that are no longer available keep their title and artwork but lose their links and show a "No longer available" label, both on the front end and in the editor. Nothing is changed in your post content, and no automatic checks run in the background.
```

Changelogの先頭（`= 1.0.0 =`の前）に足す。

```
= 1.1.0 =
* Added a link check on the settings page that finds cards whose items are no longer available on Apple's stores.
* Cards for unavailable items are shown without links and with a "No longer available" label, on the front end and in the editor.

```

`Stable tag: 1.0.0`を`Stable tag: 1.1.0`にする。

- [ ] **Step 3: バージョンを上げる**

- `yuiamijp-media-link-cards.php`のヘッダー` * Version:           1.0.0`を` * Version:           1.1.0`
- `package.json`の`"version": "1.0.0"`を`"version": "1.1.0"`

- [ ] **Step 4: `.distignore`に`docs`を足す**

`# 開発用ドキュメント`の`CLAUDE.md`の次の行に`docs`を足す。

```
# 開発用ドキュメント
CLAUDE.md
docs
```

- [ ] **Step 5: 配布物の中身を確認する**

Run:
```bash
rsync -rn --exclude-from=.distignore --delete-excluded ./ /tmp/distcheck/ --out-format='%n' | grep -v '/$' | sort | grep -E 'docs/|assets/admin.js|inc/scan.php|inc/status.php|\.po$'
```
Expected: `assets/admin.js`・`inc/scan.php`・`inc/status.php`は出る。`docs/`配下と`.po`は出ない

- [ ] **Step 6: `CLAUDE.md`を更新する**

「## コマンド」節の末尾（`pnpm format`のprettier注意の段落の後）に段落を足す。

```
**`wp-scripts format`をファイル引数なしで直接実行しない。**`--check`は未対応で、続く引数を食って
引数ゼロになり、`.`全体（`.github/*.yml`や`pnpm-lock.yaml`も）を`--write`で4スペースに整形して
しまう。使うのは引数を明示した`pnpm format`だけ。整形後は`git status`で意図しないファイルが
変わっていないことを確認する。
```

「### データの流れ」の次に節を足す。

```
### 配信終了の判定

設定ページの「Link check」から管理者が手動で実行する一括スキャンで、カードのアイテムが
ストアから消えていないかを確かめる。定期実行はない。フロントから外部へ接続することもない。

- `inc/scan.php` — RESTの`/scan`（`manage_options`）を`phase` / `offset`を進めながら繰り返し呼ぶ。
  `collect`で全投稿（`get_post_stati( array( 'internal' => false ) )`のステータス、再利用ブロック
  `wp_block`も含む）を`parse_blocks()`で走査してカードの`app.id`を集め、`check`で
  `https://itunes.apple.com/lookup`へ100件ずつ問い合わせる。返らなかったIDが配信終了
- `inc/status.php` — 結果の保存先。`yuiamijp-status`（配信終了IDだけ。フロントが読む）と
  `yuiamijp-scan`（全記録。管理画面が読む）。どちらもautoloadしない。`uninstall.php`が消す
- `src/render.php`と`src/components/MediaLinkCard.js`は`yuiamijp_is_unavailable()` /
  `yuiamijpAjaxValues.unavailableIds`で判定し、`<a>`を`<span>`にして「配信終了」ラベルを出す
- `assets/admin.js` — 設定ページの進捗表示。ビルドを通さない素のJS。`package.json`の`lint:js`と
  `format`の対象に入れてある。表示文字列はPHPで翻訳して`wp_localize_script()`で渡す
  （`.json`翻訳を同梱しないため。JS側で`__()`を使わない）

通信失敗・HTTP 200以外・JSONの破損があったバッチは判定を保留し、前回の判定を引き継ぐ
（前回もなければ`unknown`）。「返らなかったIDは配信終了」はレスポンスが正常なときだけ。
lookupの結果は検索APIの`limit`に切られない（生存ID 168件で実測済み）。`limit`は送らない。

投稿本文は書き換えない。判定を消したければ再スキャンするか、optionを削除する。
```

「## 命名規約」の`- PHP関数: ...`の行を次に置き換える。

```
- PHP関数: `yuiamijp_` / 定数: `YUIAMIJP_` / オプション: `yuiamijp-setting` `yuiamijp-status` `yuiamijp-scan` /
  トランジェント: `yuiamijp_search_*` `yuiamijp_scan_progress`
```

- [ ] **Step 7: 全部のlintを通す**

Run: `pnpm lint:all && phpcs && git status --short`
Expected: 指摘なし。`git status`に意図したファイルだけ

- [ ] **Step 8: コミット**

```bash
git add readme.txt yuiamijp-media-link-cards.php package.json .distignore CLAUDE.md
git commit -m "1.1.0に向けてreadmeとドキュメントを更新する

External serviceにlookupエンドポイントと、管理者がスキャンを実行したときだけ
問い合わせることを追記する。フロントが接続しない点は変わらない。

docs/を配布物から除外し、CLAUDE.mdに配信終了判定の節を追加する。

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>"
```

---

### Task 8: WordPress環境での動作確認

**Files:** なし（確認のみ）

WordPressが動く環境が要る。この環境ではDocker Desktopが起動できないため、環境がなければ次の一覧をそのままユーザーへ渡して確認を依頼し、実施していないことを明記する。

- [ ] **Step 1: 配信終了カードを用意する**

投稿にMedia Link Cardを置き、コードエディターで`app`属性を次にする（Flappy Bird。2014年に配信終了）。

```json
{"id":642099621,"type":"app","title":"Flappy Bird","url":"https://apps.apple.com/jp/app/id642099621","artist":".GEARS","iconUrl":"https://is1-ssl.mzstatic.com/image/thumb/Purple211/v4/29/b3/f1/29b3f16c-677a-5397-da16-c77ce545a1c7/basic_default-0-0-1x_U007epad-0-6-0-0-sRGB-0-85-220.png/512x512bb.jpg"}
```

`iconUrl`はFlappy Birdのアートワークが取れないためLINEのものを借りている。グレースケールが効いているかを見るためだけの値。

同じ投稿に、生存しているアプリ（例: LINE `443904275`）をブロックの検索から通常どおり挿入する。

- [ ] **Step 2: スキャンを実行する**

設定 > Media Link Cards の「Link check」で「Start scan」を押す。
Expected: 進捗が「Scanning posts… n / N」→「Checking items… n / N」と進み、再読み込み後に「2 items checked, 1 no longer available.」と一覧にFlappy Birdが出て、使用記事の編集リンクが投稿を指す

- [ ] **Step 3: フロントを確認する**

Expected: Flappy Birdのカードはリンクなし・アイコンがグレー・「No longer available」（日本語ロケールで言語パックがあれば「配信終了」）。LINEのカードは従来どおり

- [ ] **Step 4: エディターを確認する**

Expected: 投稿を開くと、Flappy Birdのプレビューがフロントと同じ見た目

- [ ] **Step 5: 通信失敗時に判定が変わらないことを確認する**

`wp-config.php`に一時的に次を足してスキャンする。

```php
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	return false !== strpos( $url, 'itunes.apple.com/lookup' ) ? new WP_Error( 'test', 'blocked' ) : $pre;
}, 10, 3 );
```

Expected: 「Could not check 2 items. Their previous status was kept.」の警告が出て、一覧と表示はStep 2の結果のまま。確認後にフィルターを外す

- [ ] **Step 6: 結果を報告する**

実施できた項目とできなかった項目を分けてユーザーへ報告する。リリース（タグpush）は`/wp-plugin-publish`で行うので、ここでは行わない。
