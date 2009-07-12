=== Autoptimize ===
Contributors: turl
Donate link: http://www.turleando.com.ar/autoptimize/
Tags: css, html, javascript, js, optimize, speed, cache
Requires at least: 2.7
Tested up to: 2.8.1
Stable tag: 0.5

Autoptimize is a Wordpress plugin that speeds up your website, and helps you save bandwidth. 

== Description ==

Autoptimize makes optimizing your site really easy. It concatenates all scripts and styles, minifies and compresses them, adds expires headers, caches them, and moves styles to the page head, and scripts to the footer. It also minifies the HTML code itself, making your page really lightweight.

I also recommend using WP Super Cache in conjuction with Autoptimize to speed up your blog.

You can [report bugs](https://bugs.launchpad.net/autoptimize), [ask questions](https://answers.launchpad.net/autoptimize) and [help with translations](https://translations.launchpad.net/autoptimize) in our [Launchpad page](https://launchpad.net/autoptimize).

== Installation ==

1. Upload the `autoptimize` folder to  to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to `Settings -> Autoptimize` and enable the options you want. Generally this means "Optimize HTML/CSS/JavaScript", but if you experience problems you might want to disable some.

== Frequently Asked Questions ==

= What does the plugin do to help speed up my site? =

It concatenates all scripts and styles, minifies and compresses them, adds expires headers, caches them, and moves styles to the page head, and scripts to the footer. It also minifies the HTML code itself, making your page really lightweight.

= Where can I report an error? =

You can fill in a bug in our [bug tracker](https://bugs.launchpad.net/autoptimize), or contact the author through Twitter (@turl) or email (turl at tuxfamily dot org).

= Can I help translating the plugin? =

Sure, you can help with translations in the [Launchpad translation page](https://translations.launchpad.net/autoptimize)

== Changelog ==

= 0.5 =
* Support localization
* Fix the move and don't move system (again)
* Improve url detection in CSS
* Support looking for scripts and styles on just the header
* Fix an issue with data: uris getting modified
* Spanish translation

= 0.4 =
* Write plugin description in English
* Set default config to everything off
* Add link from plugins page to options page
* Fix problems with scripts that shouldn't be moved and were moved all the same

= 0.3 =
* Disable CSS media on @imports - caused an infinite loop

= 0.2 =
* Support CSS media
* Fix an issue in the IE Hacks preservation mechanism
* Fix an issue with some urls getting broken in CSS

= 0.1 =
* First released version.
