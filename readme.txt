=== yuiamijp Media Link Cards ===
Contributors: ejointjp
Tags: block, apple, itunes, app store, affiliate
Requires at least: 6.3
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create rich promotional links for iPhone / iPad / Mac apps, Apple Books, music tracks and more. Supports Apple affiliate parameters.

== Description ==

This plugin adds a custom block that lets you search the Apple ecosystem (App Store, Apple Books, Apple Music/iTunes) and embed a rich card with title, artwork, author/artist, preview button, and a store button.

- Supports iPhone/iPad/Mac apps, Apple Books, music tracks, albums, and music videos
- Compatible with Apple affiliate links (PHG token)
- Country and language selection for search
- Adjustable number of search results
- Simple settings page for defaults

= External service =

This plugin relies on the iTunes Search API, a third-party service provided by Apple, to search the Apple ecosystem and to retrieve the title, artwork, artist and store URL of the item you pick. The plugin cannot provide its search feature without it.

**When a request is made.** Only in the block editor, when a logged-in user with the `edit_posts` capability types a search term and presses Enter. Your site's front end never contacts the service: published posts render from data already stored in the block, so your visitors make no request to Apple.

**What is sent.** The search term you typed, the content type (app, book, podcast, music, and so on), the store country, the display language, the number of results, and — only if you have entered one yourself — your affiliate token. No personal data, and no information about your site or its visitors, is sent.

**Caching.** Responses are stored in your own database as transients for 12 hours to reduce the number of requests.

- Service endpoint: https://itunes.apple.com/search
- About the API: https://performance-partners.apple.com/search-api
- Apple Media Services Terms and Conditions: https://www.apple.com/legal/internet-services/itunes/
- Apple Privacy Policy: https://www.apple.com/legal/privacy/

= Affiliate links =

This plugin adds **no affiliate parameter by default**. The PHG token setting is empty after installation, and no token is sent unless you enter your own.

If you join the Apple Services Performance Partner program and enter your own token on the settings page, that token is appended as the `at` parameter to the store links the block outputs, and to the search requests described above. Only the token you enter is ever used — the plugin never falls back to a token belonging to the author or anyone else. Clearing the field stops the parameter from being added.

- Apple Services Performance Partner program: https://performance-partners.apple.com/

= Disclaimer =

This plugin is an independent project. It is not affiliated with, endorsed by, or sponsored by Apple Inc.

Apple, App Store, Mac App Store, Apple Books, Apple Music, Apple Podcasts and iTunes are trademarks of Apple Inc., registered in the U.S. and other countries. These names are used here only to describe the services the plugin can link to.

== Screenshots ==

1. Select a content category, enter a search term, and pick an item from suggestions to embed its link widget.
2. Example of front-end display.
3. Settings are available from the WordPress admin.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/yuiamijp-media-link-cards` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. (Optional) Open Settings -> Media Link Cards and set default values (PHG token, country, language, results count).
4. In the block editor, insert the "Media Link Card" block and search for content.

== Frequently Asked Questions ==

= Does this support affiliate links? =
Yes, but only if you opt in. The PHG token setting is empty after installation and nothing is appended to your links until you enter your own token on the settings page. See "Affiliate links" in the description for details.

= Does the plugin send anything to a third party? =
Yes. Searching in the block editor queries Apple's iTunes Search API. Your site's front end never contacts it. See "External service" in the description for exactly what is sent and when.

= Which countries and languages are supported? =
Common countries are available (JP, US, GB, CA, AU, SG, TH, IN, DE, FR, BR, etc.). Language can be set to auto or English; auto maps based on the selected country.

== Changelog ==

= 1.0.0 =
Initial release.
