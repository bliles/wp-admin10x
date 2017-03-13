=== Wordpress Admin10X ===
Contributors: brandonliles
Tags: admin, performance
Requires at least: 4.7
Tested up to: 4.7
Stable tag: 4.7
License: MIT

This plugin improves the performance of Wordpress Admin when a wordpress instance has many users.

== Description ==

When a Wordpress instance has hundreds of thousands of users, the query to get the list of WP
authors can become very slow (see https://core.trac.wordpress.org/ticket/28160). This plugin
works around this limitation by adding a cache table of authors and optimizing the query.

Copyright (c) 2017 Brandon Liles

== Installation ==

Just install the plugin to your plugins directory and activate it.

== Changelog ==

= 1.0 =
Initial release.