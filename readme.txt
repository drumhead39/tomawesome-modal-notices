=== TomAwesome Modal Notices ===
Contributors: TomAwesome
Tags: modal, popup, banner, announcement, notice
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build responsive information modals, banners, and notices with flexible triggers, targeting, scheduling, and styling.

== Description ==

TomAwesome Modal Notices lets you create unlimited, independently configured modal windows without relying on an external service.

Create the displayed message with the familiar WordPress editor, then choose where, when, and how it appears. Every modal can have its own trigger, targeting rules, schedule, frequency, position, colors, typography, animation, close button, and confirmation action.

= Display triggers =

* On page load
* After a configurable delay
* After the visitor scrolls a selected percentage
* Desktop exit intent
* Click on an element matching a CSS selector

= Targeting and scheduling =

* Entire website
* Homepage only
* Selected pages, posts, or custom content
* Selected public post types
* Categories, tags, and public taxonomies
* Archive pages
* Content ID exclusions
* Optional start and end date/time

= Frequency controls =

* Every page load
* Once per browser session
* Once per visitor
* Repeat after a configurable number of days
* Frequency tracked across the website or separately for each page

= Design and accessibility =

* Centered modal, top or bottom banner, side, or corner placement
* Responsive dimensions, padding, colors, typography, and button hover colors
* Fade, scale, and directional slide animations
* Optional close button and customizable confirmation button
* Keyboard focus management, focus trapping, Escape-to-close, dialog semantics, and reduced-motion support

The plugin loads its frontend CSS and JavaScript only when at least one published modal matches the current request. It creates no custom database tables, contains no advertising, and makes no requests to external services.

== Installation ==

1. Install TomAwesome Modal Notices from Plugins > Add New, or upload the plugin ZIP.
2. Activate the plugin.
3. Go to Modal Notices > Add New.
4. Enter an internal title and create the displayed message with the WordPress editor.
5. Configure Display & Behavior, Targeting, and Design.
6. Publish the modal.

== Frequently Asked Questions ==

= What does the scroll percentage mean? =

It is how far down the page a visitor must scroll before the modal opens. For example, 50 opens the modal after the visitor scrolls halfway through the page. The setting is used only with the After scrolling trigger.

= How do I open a modal when a button or link is clicked? =

Select Element click as the trigger and enter the element's CSS selector. For example, use `#open-offer` for an element with the ID `open-offer`, or `.open-offer` for elements with that class.

= How do I find a page or post ID? =

Open the page or post in the WordPress editor and look at its browser URL. The number after `post=` is its content ID.

= How do taxonomy rules work? =

Enter one taxonomy per line using `taxonomy:term-slug,term-slug`. Examples include `category:news,offers` and `post_tag:featured`. The modal can match both the term archive and singular content assigned to the term.

= Will the plugin work with page builders? =

The plugin deliberately isolates modal editor content from the current page's main content filter to avoid common page-builder replacements. Themes and plugins can still affect frontend styling, so test important modals after significant theme or builder updates.

= Does the plugin use cookies or contact an external service? =

It does not use cookies or contact an external service. Frequency options other than Every page load use the visitor's browser sessionStorage or localStorage. The stored value contains the modal ID and display time and remains in that browser.

= What happens to my modals if I uninstall the plugin? =

Saved modal content is preserved to prevent accidental data loss. Delete unwanted Modal Notices before uninstalling if you want to remove that content.

= Why does exit intent not open on my phone? =

Exit intent watches for a desktop pointer leaving the top of the browser window. Touch devices do not provide an equivalent event, so use another trigger when mobile display is important.

== Privacy ==

TomAwesome Modal Notices does not collect or transmit personal data and does not contact the plugin author or any third party.

When a modal uses a frequency setting other than Every page load, the plugin stores the modal ID and display time in browser sessionStorage or localStorage. This local value is used only to determine when that modal may appear again. Clearing browser storage resets the frequency rule.

The plugin adds suggested disclosure text to the WordPress Privacy Policy Guide. Site owners remain responsible for determining whether and how their use of modals and browser storage must be disclosed under applicable law.

== Changelog ==

= 1.1.2 =
* Renamed the plugin and directory identity to TomAwesome Modal Notices with the requested slug tomawesome-modal-notices.
* Preserved the existing modal content type, settings, extension hooks, styles, and browser storage keys for seamless migration.
* Refreshed the WordPress.org listing artwork and submission documentation.

= 1.1.1 =
* Confirmed compatibility with WordPress 7.0 and refreshed the public submission package.

= 1.1.0 =
* Prepared the plugin for public WordPress.org distribution.
* Added centralized setting defaults and stricter validation.
* Corrected scheduled-display comparisons in non-UTC WordPress timezones.
* Prevented frequency-suppressed click triggers from blocking the original link.
* Added reduced-motion support and a WordPress Privacy Policy Guide suggestion.
* Added extension hooks for request matching, content, settings, classes, and modal output.
* Expanded translation support, documentation, security checks, and code organization.

= 1.0.1 =
* Prevented page builders and themes from replacing modal editor content with the current webpage.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.2 =
Public plugin name and directory identity updated while preserving existing modal data and settings.
