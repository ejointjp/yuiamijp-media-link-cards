#!/usr/bin/env bash
# .wordpress-org/のアイコンとバナーを、このフォルダーのSVGから作る。
#
# Google Chromeのheadlessモードで描く（ImageMagickのSVG描画は、フォントの扱いと線の精度が
# 足りない）。256pxのアイコンと1544pxのバナーを描いてから、ImageMagickで半分に縮めて
# 128pxと772pxを作る。banner.svgはicon.svgを<image>で読み込むので、同じフォルダーに置いたまま描く。
# icon.svgは文字を含まないので、そのまま.wordpress-org/へ写す。
#
# 使い方: bash bin/wordpress-org-assets/render.sh
# 必要なもの: Google Chrome、ImageMagick（magick）、Noto Sans JP（バナーの文字）

set -euo pipefail
cd "$(dirname "$0")"

CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
OUT="../../.wordpress-org"

[ -x "$CHROME" ] || { echo "Google Chromeがない: $CHROME" >&2; exit 1; }
command -v magick >/dev/null || { echo "ImageMagick（magick）がない" >&2; exit 1; }
# ChromeはCoreTextでフォントを探すので、fontconfig（fc-list）ではなくフォントのファイルで確かめる
if ! compgen -G "$HOME/Library/Fonts/NotoSansJP-*" >/dev/null \
	&& ! compgen -G "/Library/Fonts/NotoSansJP-*" >/dev/null; then
	echo "注意: Noto Sans JPが見つからない。バナーの文字が別のフォントで描かれる" >&2
fi

mkdir -p "$OUT"

# $1: SVG、$2: 幅、$3: 高さ、$4: 出力PNG
render() {
	"$CHROME" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 \
		--window-size="$2,$3" --screenshot="$4" "file://$PWD/$1" >/dev/null 2>&1
}

render icon.svg 256 256 "$OUT/icon-256x256.png"
render banner.svg 1544 500 "$OUT/banner-1544x500.png"
# ImageMagickは出力のPNGに日付のチャンク（tIMEと、date:create / date:modify / date:timestampのtEXt）を書くので、
# 原本のSVGが変わっていなくても実行のたびに128pxと772pxのバイト列が変わる。日付のチャンクだけ書かない
# （-stripはcHRMやbKGDも消す）
magick -define png:exclude-chunks=date,time "$OUT/icon-256x256.png" -resize 128x128 "$OUT/icon-128x128.png"
magick -define png:exclude-chunks=date,time "$OUT/banner-1544x500.png" -resize 772x250 "$OUT/banner-772x250.png"
cp icon.svg "$OUT/icon.svg"

magick identify "$OUT"/*.png
