# Launch Catalog Notifier
Simple script that notifies one or more slack channels of updates to Adobe Launch Catalo including Web, Mobile and Edge

This is a simple PHP script that periodically checked the Adobe Launch APIs to pull the Extension Catalog and cache it locally. Each time it runs it creates a diff on that local cache and for any delta posts a corresponding message to one or more slack webhooks.


## Prerequisites

 - PHP 7 or higher
 - PHP Composer
 - Provisioned for Adobe Launch and Developer Access Granted

## Getting Started

1. Clone the repo locally:  `git clone https://github.com/littlebutty/launch-catalog-notifications.git`
2. Run composer installation:  `composer install`
3. Verify the install worked and your php version is compatible:  `php src/bin/queryCatalog.php -h`  You should see the help notice.  The script will need write permission to the `/src/bin/cache` directory
4. Obtain Launch Access Tokens and settings.  Follow these instructions found here: <https://developer.adobelaunch.com/api/guides/access_tokens/>
5. Add the settings from the previous step by either editing the *default* config or add an additional one to the `/src/config` directory. Additional ones can be specified as a commandline argument.   
6. Run the php script in the `/src/bin/queryCatalog.php` script.  It will use the default config if you do not specify one int as an argument.  Ex: `php src/bin/queryCatalog.php config=development`
7. Create a cron or some sort of timed execution of the script

## Support
This repo is provided as is with now warranty or support and is subject to the license terms.
