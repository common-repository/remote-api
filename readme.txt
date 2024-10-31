=== Remote API ===
Contributors: tott, automattic
Tags: remote api, api, remote access, lazy loading, lazy widget
Requires at least: 3.0
Tested up to: 3.04
Stable tag: trunk

A set of extendable classes that allow the creation of a remote API. 

== Description ==

A basic use case for this plugin would be lazy loading content segments or performing cross-blog actions.
It includes a simple example for lazy loading widgets, but is mainly aimed for developers who like to built on top of this functionality.

Features:

* Url Format in form of `http://<blogname>/<server_entry_key>/<request_string>/<server_format_key>/<format>` in order to allow server side caching of requests without setting up a huge set of rewrite rules. The request string contains all request parameters
* Variable response formats. Comes with xml and json bundled in response.php but can be extended to your needs
* Exceptions with custom exception handler are used throughout the classes to allow error feedback in the requested response format.

Please have a look at the inline documentation starting from `remote-api.php`. To get a sense of the usage have a look at the examples

== Lazy Loading Widget Example ==

The Lazy Loading Widget example is a basic use case for this script. It's UI is still not very tuned, but should give an impression on what can be done with this remote-api.

When you visit your widget administration at `/wp-admin/widgets.php` you'll notice a widget called "Remote_API_Lazy_Widget". Drag it to one of your sidebars where you would like to have some asynchronously loaded widget appear and give it a Title. Then reload the widgets.php page. 

A new sidebar should appear in which you can drop other widgets. The widgets you'll drop in this sidebar will be loaded asynchronously via a ajax request in place of the placeholder widget.

== Screenshots ==

1. Widget admin interface showing the widget (left), the placeholder widget in the primary sidebar (top-right) and the resulting sidebar / dropzone for the placeholder widget (bottom-right) 

== Changelog ==

= 0.2 =
* Add support for full stops (.) in server parameters and switch to `remote.api` as default url to avoid conflicts with page names.
* Switching to hash_hmac instead of crypt for request format validation string.

= 0.1 =
* Basic implementation