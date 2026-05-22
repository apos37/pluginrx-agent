=== PluginRx Agent ===
Contributors: apos37
Tags: site management, remote access, monitoring, maintenance, api
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.6
License: Proprietary
License URI: https://pluginrx.com/proprietary-license-agreement/

Secure site-side agent that exposes controlled system data and maintenance actions to the PluginRx Control Center.

== Description ==

**PluginRx Agent** is the site-side companion plugin required for any WordPress site managed by the **PluginRx Control Center** plugin. It acts as a secure, permission-based interface that exposes system information and performs explicitly allowed maintenance actions when requested by an authorized Control Center instance.

The Agent never initiates outbound management actions on its own. It responds only to authenticated, signed REST API requests originating from a paired Control Center and only for actions that the site owner has explicitly enabled.

This plugin is intended to be installed on every site you wish to monitor or manage remotely.

**Core Responsibilities:**

- Collect and expose system and configuration data
- Enforce fine-grained permission controls for remote actions
- Execute approved maintenance tasks locally
- Protect the site from unauthorized or destructive operations

**Features:**

- Expose WordPress core version and update availability
- Expose PHP version and environment details
- Report active themes and plugins with version data
- Report multisite status, admin email, ABSPATH, and server IP
- Report `WP_DEBUG` status
- Secure pairing with a PluginRx Control Center instance
- Capability-based action permissions (off by default)

**Permitted Remote Actions (Opt-In):**

- Update WordPress core
- Update plugins and themes
- Clear caches (when Clear Cache Everywhere plugin is installed)

**Plugin Integrations (Detected Automatically):**

Developer Debug Tools
- Expose debug log line count and file size
- Report online users and total users
Broken Link Notifier  
- Expose broken link counts
Fake User Detector  
- Expose number of flagged potential fake users
Clear Cache Everywhere  
- Execute cache clears on request

All integrations are passive until explicitly enabled by the site owner.

The PluginRx Agent does not replace backups, security plugins, or hosting controls. Its sole purpose is controlled visibility and maintenance execution under owner-defined rules. Daily backups of your site are highly recommended.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/pluginrx-agent/` directory.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Navigate to Settings > PluginRx Agent.
4. Add the Control Center Domain(s).
5. Set the permissions you want to allow.
6. Generate an API Key.
7. Use the API Key to connect this site to the PluginRx Control Center.

== Frequently Asked Questions ==

= Is this safe to install on production sites? =
Yes. The agent only responds to authenticated REST requests from a paired Control Center and only executes actions you explicitly allow.

= Do I need this plugin on the Control Center site? =
If you want to manage the site that the Control Center is on, then yes you will need install it on that site as well. Otherwise, it is not necessary for the Control Center to work.

= Can I limit what the Control Center can do? =
Yes. Every supported action can be individually enabled or disabled per site.

= Does this allow arbitrary code execution? =
No. The Agent exposes a fixed, whitelisted set of actions. No arbitrary commands or file writes are permitted.

= Does this use XML-RPC or WP Cron? =
No. Communication is handled exclusively through authenticated REST API requests over HTTPS.

== Screenshots ==

1. Settings page showing permissions
2. PluginRx Control Center plugin dashboard showing what they see

== Changelog ==
= 1.0.6 =
* Compatibility: Increased minimum required WordPress version to 6.0
* Compatibility: Tested with WordPress 7.0

= 1.0.5 =
* Fix: Validating license returning an error after clearing transients

= 1.0.4.8 =
* Fix: Checking for updates was failing and not checking often enough
* Tweak: Removed invalid license notice if not connecting to server

= 1.0.4 =
* Update: Added Hosting Cache URL request
* Update: Added Admin Users request
* Update: Added Purge Mail Errors action

= 1.0.3 =
* Update: Added Clear Debug Log action with Developer Debug Tools plugin integration
* Update: Added Form Spam request with Advanced Tools for Gravity Forms plugin integration

* 1.0.2 =
* Update: Added integrations support with hooks
* Fix: Plugins updater not working on some sites
* Tweak: Added more description API errors

= 1.0.1 =
* Initial release of PluginRx Agent