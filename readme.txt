=== yuiamijp Media Link Cards ===
Contributors: ejointjp
Tags: block, apple, itunes, app store, affiliate
Requires at least: 6.3
Tested up to: 7.1
Stable tag: 1.1.0
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
- Link check: finds cards whose items are no longer available and shows them without links

= External service =

This plugin relies on the iTunes Search API, a third-party service provided by Apple, to search the Apple ecosystem, to retrieve the title, artwork, artist and store URL of the item you pick, and to check whether the items you have already embedded are still available. The plugin cannot provide these features without it.

**When a request is made.** Only in two situations, both started by a logged-in user of your site:

1. In the block editor, when a user with the `edit_posts` capability types a search term and presses Enter.
2. On the settings page, when an administrator (`manage_options` capability) clicks "Start scan" under "Link check". The plugin then looks up the items embedded on your site to find the ones that are no longer available.

Your site's front end never contacts the service: published posts render from data already stored in your database, so the plugin makes no request to Apple for your visitors. The card artwork is loaded from Apple's CDN by the visitor's browser, as with any externally hosted image.

**What is sent.** For a search: the search term you typed, the content type (app, book, podcast, music, and so on), the store country, the display language, the number of results, and — only if you have entered one yourself — your affiliate token. For a link check: the Apple item IDs stored in your cards (public identifiers assigned by Apple) and the store country. No personal data, and no information about your site or its visitors, is sent.

**Caching.** Search responses are stored in your own database as transients for 12 hours to reduce the number of requests. Link check results are stored as options until the next scan.

- Search endpoint: https://itunes.apple.com/search
- Lookup endpoint: https://itunes.apple.com/lookup
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
Yes. Searching in the block editor and running the link check on the settings page both query Apple's iTunes Search API. Your site's front end never contacts it. See "External service" in the description for exactly what is sent and when.

= Which countries and languages are supported? =
Common countries are available (JP, US, GB, CA, AU, SG, TH, IN, DE, FR, BR, etc.). Language can be set to auto or English; auto maps based on the selected country.

= What happens to a card when the item is removed from the store? =
Run the link check from Settings -> Media Link Cards. Cards for items that are no longer available keep their title and artwork but lose their links and show a "No longer available" label, both on the front end and in the editor. Nothing is changed in your post content, and no automatic checks run in the background.

== Changelog ==

= 1.1.0 =
* Added a link check on the settings page that finds cards whose items are no longer available on Apple's stores.
* Cards for unavailable items are shown without links and with a "No longer available" label, on the front end and in the editor.

= 1.0.0 =
Initial release.
