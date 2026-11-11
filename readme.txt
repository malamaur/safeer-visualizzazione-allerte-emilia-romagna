=== SafeER Visualizzazione Allerte Emilia Romagna ===
Contributors: malamaur
Tags: weather, alerts, civil protection, emilia-romagna, cap
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shows the official Emilia-Romagna weather alert bulletin (CAP feed) for the alert zone of your municipality.

== Description ==

SafeER was developed for the public bodies of the Unione delle Terre d'Argine (the Union and the municipalities of Carpi, Campogalliano, Novi di Modena and Soliera, in the province of Modena), and can be used by any municipality or organization in Emilia-Romagna by selecting its alert zone.

SafeER is an independent third-party plugin by Maurizio Malagoli (malamaur). It is not affiliated with, endorsed by or developed by the Emilia-Romagna Region or the Allerta Meteo Emilia-Romagna service; it only displays the public data they publish.

The plugin reads the CAP (Common Alerting Protocol) feed published by Allerta Meteo Emilia-Romagna
(https://allertameteo.regione.emilia-romagna.it/o/xmlcap) and displays, for the selected alert zone:

* the overall alert level (green, yellow, orange, red) with the day it refers to;
* the level of each phenomenon: hydraulic, hydrogeological, thunderstorms, wind, snow,
  extreme temperatures, freezing rain, sea state and coastal storms (the last two only for coastal zones);
* today and, once it has been issued, tomorrow;
* bulletin number, issue date and the notes of each bulletin in a pop-up window;
* a legend of the 18 regional alert zones and, optionally, a link to the regional portal (off by default).

Data is cached (30 minutes by default). If the regional service is unreachable, the last valid copy is shown with a notice.

The user interface of the plugin is in Italian, as it is meant for municipalities in Emilia-Romagna.

= Customization =

Settings > SafeER Allerte, with a live preview at the top of the page:

* Municipality and data: alert zone, municipality name, title, cache duration.
* Logo: image from the media library and its height.
* Background and colors: background color or image (with an adjustable overlay), text, table,
  borders, level label, bell waves, maximum width, optional Titillium Web font.
* Levels: color, table label and description for each of the four levels.
* Phenomena: which ones to show and their labels.
* Display: tomorrow column, bell, waves colored by level, Info buttons, issue data, zone,
  legend, link to the regional portal, overall level computed on today only or today and tomorrow,
  indication of the day the overall level refers to.

= Shortcode =

`[allerta_meteo]`

All main settings can be overridden per page:

`[allerta_meteo zona="F1" comune="Carpi" titolo="Allerta meteo" logo="https://example.com/logo.png" sfondo="#1d3557" testo="#ffffff" domani="si" campana="si" info="si" emissione="si" giorno="si" legenda="si" link="si"]`

A classic widget, "SafeER Allerte", is also available.

= External service =

This plugin connects to the Allerta Meteo Emilia-Romagna service, operated by the Emilia-Romagna Region
(Agenzia per la sicurezza territoriale e la protezione civile and Arpae), to download the current alert bulletin.
A request is sent from your server to https://allertameteo.regione.emilia-romagna.it/o/xmlcap when the cache expires;
no personal data of your visitors is sent. Terms and privacy: https://allertameteo.regione.emilia-romagna.it/

If the "Titillium Web" option is enabled, the font is loaded from Google Fonts (https://fonts.google.com),
which receives the visitor's IP address. Privacy policy: https://policies.google.com/privacy

== Installation ==

1. Upload the `safeer-visualizzazione-allerte-emilia-romagna` folder to `wp-content/plugins/`, or upload the zip from Plugins > Add New.
2. Activate the plugin.
3. Go to Settings > SafeER Allerte and choose the alert zone and the municipality name.
4. Add the `[allerta_meteo]` shortcode to a page, or use the "SafeER Allerte" widget.

== Frequently Asked Questions ==

= Which alert zone should I choose? =

Each municipality belongs to one of the 18 regional zones (A1 to H2). For example, the municipalities
of the Unione delle Terre d'Argine (Carpi, Campogalliano, Novi di Modena, Soliera) are in zone F1.
Check the regional portal if in doubt.

= Why is tomorrow's column empty? =

The bulletin for the next day is usually issued during the previous day. Until it is published,
the column shows "N.D." (not available).

== Screenshots ==

1. The alert box: bell with waves, overall level with the day it refers to, levels for today and tomorrow, bulletin data.
2. The "Info" window with the notes of the bulletin.
3. The same box on a smartphone.
4. The settings page with the live preview.

== Changelog ==

= 2.3.1 =
* Fix: the phenomena table was empty with the real regional bulletin. The bulletin covers two days in one document (parameters with the _1224 and _2436 suffixes): today and tomorrow are now read from the right part of the document.
* Fix: the "tomorrow" column is filled as soon as the bulletin is issued, without a second request to the regional service.
* The Info window of each day shows only the part of the bulletin note about that day.
* Data cached by previous versions is ignored after the update.

= 2.3.0 =
* Plugin renamed to "SafeER Visualizzazione Allerte Emilia Romagna" to make clear it is an independent third-party plugin.

= 2.2.0 =
* Plugin renamed to "Visualizzazione Allerte della Regione Emilia-Romagna".

= 2.1.1 =
* Tested up to WordPress 7.1.
* The link to the regional portal is now off by default (it can be enabled in the settings).

= 2.1.0 =
* Overall level shows the day it refers to.
* Escaping and asset versioning fixes reported by Plugin Check.

= 2.0.0 =
* New layout, logo, background, colors, labels and display options.

= 1.0.0 =
* First release.
