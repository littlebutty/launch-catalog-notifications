# Launch Catalog Notifier
Simple script that notifies one or more slack channels of updates to Adobe Launch Catalog

This is a simple PHP script that periodically checked the Adobe Launch APIs to pull the Extension Catalog and cache it locally. Each time it runs it creates a diff on that local cache and for any delta posts a corresponding message to one or more slack webhooks.


## Prerequisites

 - PHP 7 or higher
 - PHP Composer
 - Provisioned for Adobe Launch and Developer Access Granted

## Getting Started

1. You will need to obtain Launch Access Tokens.  Follow these instructions found here: <https://developer.adobelaunch.com/api/guides/access_tokens/>
2. You will need to create a Slack incoming webhook integration.  <https://api.slack.com/incoming-webhooks>
3. Either edit the default config or add an additional one to the `/src/config` directory.  Additional ones can be referenced in the command line
4. Run `composer install` inside the repo root directory
5. Run the php script in the `/src/bin/queryCatalog.php` script  (it will need write permission to the `/src/bin/cache` directory
6. Create a cron or some sort of timed execution of the script
