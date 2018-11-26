#!/usr/local/opt/php@7.1/bin/php
<?php

use Adobe\IMS\AdobeIO;
use Library\Adobe\Launch\ReactorAPI;

/**
 * Settings
 */
$state = 'development';
$verbose = false;




/*************************/
require sprintf("../config/%s.php", $state);
require "../library/Slack/SlackAPI.php";
require "../library/Adobe/IMS/AdobeIO.php";
require "../library/Adobe/Launch/ReactorAPI.php";


// Metrics
$intExtensionsInCatalog = 0;
$intNewExtensions = 0;
$intLocalCachedExtensions = 0;
$intUpgradedExtensions = 0;


// Slack API Notifier
$slack = new SlackAPI($config['slack']['webhook']);


// Local Database
$strCacheFile = "cache/Extensions.txt";

// Hydrate Local Cache
if (is_readable($strCacheFile)) {
    try {
        $virgin = false;
        $recLocalExtensions = fopen($strCacheFile, "r");
        
        $strLocalCache = fread($recLocalExtensions, filesize($strCacheFile));
        $arrPreviousExtensions = unserialize($strLocalCache);
        ksort($arrPreviousExtensions);
        
        $intLocalCachedExtensions = sizeof($arrPreviousExtensions);
        
        if (!is_resource($recLocalExtensions)) {
            throw new Exception("Failed to read the local cache file.");
        }
    }
    catch (Exception $e) {
        echo "\nFailed to read or write the local stoage file to cache directory.\nPlease make sure this sript can write to this directory.\n";
        exit;
    }
}
else {
    $virgin = true;
    echo "\nNOTICE: Looks like this is the first time executing.  Local cache will been popluated.  Going forward, new extensions will notify.\n\n";
}


// Adobe IMS and AdobeIO Authentication
$adobeIO = new AdobeIO($config['adobe']['ims_end_point']);
$access_token = $adobeIO->getAccessToken($config['adobe']['client_id'], $config['adobe']['client_secret'], $config['adobe']['jwt_token']);

// Adobe Launch API
$reactor = new ReactorAPI($access_token);
$arrExtensions = $reactor->getExtensions();

$arrFoundExtesnions = array();

if ($arrExtensions && sizeof($arrExtensions > 0)) {
    foreach ($arrExtensions as $extension) {
        $arrFoundExtension = array();
        
        $strExtensionIdentifer = $extension['attributes']['name'];
        $strCurrentVersion = $extension['attributes']['version'];
        
        $arrFoundExtension['name'] = $strExtensionIdentifer;
        $arrFoundExtension['display_name'] = $extension['attributes']['display_name'];
        $arrFoundExtension['author'] = $extension['attributes']['author']['name'];
        $arrFoundExtension['description'] = $extension['attributes']['description'];
        $arrFoundExtension['created'] = $extension['attributes']['created_at'];
        $arrFoundExtension['platform'] = $extension['attributes']['platform'];
        $arrFoundExtension['version'] = $strCurrentVersion;
        $arrFoundExtension['exchange'] = $extension['attributes']['exchange_url'];
        
        $arrFoundExtesnions[$strExtensionIdentifer] = $arrFoundExtension;
        
        if ($verbose) {
            echo "Found extension with ID: '" . $strExtensionIdentifer . "' \n";
        }
        $intExtensionsInCatalog++;
        
        // See if we need to notify
        if (!$virgin && is_array($arrPreviousExtensions)) {
            $previouslyFound = array_key_exists($strExtensionIdentifer, $arrPreviousExtensions);
            
            if ($previouslyFound) {
                
                // Check the Version
                $previousVersion = $arrPreviousExtensions[$strExtensionIdentifer]['version'];
                if ($strCurrentVersion != $previousVersion) {
                    
                    // Send notification of Version Update
                    $slack->sendUpgradeMessage($arrFoundExtension['display_name'], $strCurrentVersion, $arrFoundExtension['created']);
                    if ($verbose) {
                        echo "Sent notification of new version of '" . $arrFoundExtension['display_name'] . "' extension.";
                    }
                    $intUpgradedExtensions++;
                }
            }
            else {
                // New Extension!!
                $slack->sendNewExtensionMessage($arrFoundExtension['display_name'], $arrFoundExtension['author'], $arrFoundExtension['created']);
                if ($verbose) {
                    echo "FYI:  sent notification of new extension named '" . $arrFoundExtension['display_name'] . "'.";
                }
                $intNewExtensions++;
            }
        }
    }
}

ksort($arrFoundExtesnions);


// Update the local cache
try {
    if (is_resource($recLocalExtensions)) {
        rewind($recLocalExtensions);
    }
    $recLocalExtensions = fopen("cache/Extensions.txt", "w+");
    $serializedExtensions = serialize($arrFoundExtesnions);
    fwrite($recLocalExtensions, $serializedExtensions);
    fclose($recLocalExtensions);
}
catch (Exception $e) {
    echo "\nFailed to read or write the local stoage file to cache directory.\nPlease make sure this sript can write to this directory.\n";
    exit;
}

// Show Stats
echo "\n================================\n";
echo "Completed successfully at " . date("Y-m-d H:i:s") . "\n";
echo "There are currently " . $intExtensionsInCatalog . " extensions in the catalog.  There were " . $intLocalCachedExtensions . " in the local cache.\n";
echo "There were " . $intUpgradedExtensions . " extensions upgraded, and " . $intNewExtensions . " new extensions released.";
echo "\n================================\n\n";
exit;
