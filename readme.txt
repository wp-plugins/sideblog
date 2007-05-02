=== Plugin Name ===
Contributors: kates
Donate link: http://www.katesgasis.com/
Tags: asides
Requires at least: 2.1
Tested up to: 2.1.3
Stable tag: trunk

Yet another asides implementation.

== Description ==

Sideblog is a plugin for Wordpress Blog Platform. It is one way of implementing "Asides" - a series of "short" posts, 1-2 sentences in length.

== Installation ==

= For those with Sidebar Widget compatible themes =

1. Upload `sideblog.php` to the `/wp-content/plugins` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create your asides category (usually, 'asides') if you haven't created on yet
1. Go to 'Options' menu then to 'Sideblog' submenu
1. From the list of categories, select the one you just created above and choose the number of entries to display
1. Click on 'Update Sideblog Options' button
1. Go to 'Presentation' menu, then to 'Sidebar widget'
1. Drag and drop the Sideblog widget to your sidebar
1. Create a post and put it in your asides category

= For those without Sidebar Widget =

1. Upload `sideblog.php` to the `/wp-content/plugins` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create your asides category (usually, 'asides') if you haven't created on yet
1. Open your themes' `sidebar.php` file if you have one and add `<?php sideblog('asides'); ?>`
1. Create a post and put it in your asides category

