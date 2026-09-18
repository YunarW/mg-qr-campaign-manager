=== MG QR Campaign Manager ===
Contributors: muffingraphics
Tags: qr code, ga4, utm, analytics, campaign tracking, redirect
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create reusable QR campaign short URLs with GA4 UTM tracking, redirects, and simple scan counts.

== Description ==

MG QR Campaign Manager helps WordPress administrators create clean QR campaign URLs such as `/q/campaign-name/` and redirect them to destination pages with GA4-compatible UTM parameters.

Features include:

* Destination URL management
* utm_source, utm_medium, utm_campaign, utm_content, and utm_term
* Default qr_code medium
* Enable/disable campaign status
* Basic total scan count
* Last scan timestamp
* 302 redirects
* Destination changes without reprinting QR codes
* Migration from the earlier Hackortu QR Campaign Manager data structure

For documentation and examples, see README.md in the repository.

== Installation ==

1. Upload the `mg-qr-campaign-manager` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.
3. Open MG QR Campaigns in WordPress admin.
4. Create and publish a campaign.
5. Put the generated `/q/slug/` URL into your QR code.

If the short URL returns a 404, save WordPress permalinks once under Settings > Permalinks.

== Changelog ==

= 1.1.0 =
* Rebranded plugin for use across multiple Muffin Graphics websites.
* Added migration support for legacy Hackortu QR campaign data.
* Standardized MG-prefixed internal fields and labels.
* Retained short URL, UTM, redirect, and scan tracking features.

== Privacy ==

The built-in counter stores aggregate scan count and the latest scan timestamp only. It does not intentionally store visitor IP addresses or personal identifiers.
