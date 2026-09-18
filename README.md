# MG QR Campaign Manager

MG QR Campaign Manager is a lightweight WordPress plugin for creating trackable short URLs for QR-code campaigns. It appends UTM parameters to the destination URL, redirects visitors through a clean `/q/slug/` URL, and keeps a simple scan counter inside WordPress.

Built for teams that manage QR campaigns across multiple websites and want consistent GA4 attribution without changing printed QR codes whenever a destination changes.

## Features

- Create reusable short URLs such as `https://example.com/q/campaign-name/`
- Set destination URLs from the WordPress admin
- Add GA4-compatible UTM parameters:
  - `utm_source`
  - `utm_medium`
  - `utm_campaign`
  - `utm_content`
  - `utm_term`
- Default `utm_medium` to `qr_code`
- Enable or disable individual QR campaigns
- Basic total scan counter
- Record the most recent scan time
- Change the destination URL without reprinting the physical QR code
- 302 redirects to avoid permanently caching campaign destinations
- Migration support from the earlier Hackortu QR Campaign Manager data structure

## Typical Flow

```text
Printed QR Code
    ↓
https://example.com/q/nextg571/
    ↓
302 Redirect
    ↓
https://example.com/landing-page/?utm_source=komik_nextg&utm_medium=qr_code&utm_campaign=promo_hackortu&utm_content=nextg571
    ↓
GA4 Traffic Acquisition
```

## Installation

1. Download or clone this repository.
2. If installing manually, place the plugin folder in:
   `wp-content/plugins/mg-qr-campaign-manager/`
3. Activate **MG QR Campaign Manager** from **Plugins** in WordPress.
4. Open **MG QR Campaigns** in the WordPress admin menu.
5. Add a new campaign and publish it.
6. Use the generated `/q/slug/` URL when creating your QR code.

If the short URL returns a 404 after activation, go to **Settings → Permalinks** and click **Save Changes** once.

## Example Campaign

| Field | Example |
|---|---|
| Destination URL | `https://example.com/play/` |
| utm_source | `komik_nextg` |
| utm_medium | `qr_code` |
| utm_campaign | `promo_hackortu` |
| utm_content | `nextg_571` |

The generated QR URL might be:

```text
https://example.com/q/nextg-571/
```

Visitors are redirected to the destination URL with the UTM parameters attached.

## Viewing QR Traffic in GA4

In Google Analytics 4, open:

**Reports → Acquisition → Traffic acquisition**

Useful dimensions include:

- **Session source / medium**
- **Session campaign**
- Manual campaign content dimensions when you use `utm_content`

For the example above, source / medium would be reported as:

```text
komik_nextg / qr_code
```

## Why Use a Redirect URL?

A QR code printed directly with a long UTM URL works, but the destination becomes difficult to change later. With MG QR Campaign Manager, the printed QR only contains the short URL. The destination and UTM values can then be updated from WordPress while the physical QR remains unchanged.

## Requirements

- WordPress 6.0 or newer recommended
- PHP 7.4 or newer recommended
- Pretty permalinks recommended

## Privacy

The plugin's built-in counter stores only the total number of scans and the time of the latest scan. It does not intentionally store visitor IP addresses, device identifiers, or personal information.

GA4 tracking occurs after the visitor is redirected to the destination page and therefore depends on the analytics implementation and consent configuration of that website.

## Version

Current version: **1.1.0**

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

---

Created as part of a practical WordPress and web-tooling workflow from [WiseTechList](https://wisetechlist.com).
