# Launch Catalog Notifier
Simple script that notified one or more slack channels of updates to Adobe Launch Catalog

This is a simple PHP script that periodcially checked the Adobe Launch APIs to pull the Extension Catalog and cache it locally. Each time it runs it creates a diff on that local cache and for any delta posts a corrsepnding message to one or more slack webhooks.
