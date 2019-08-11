<?php

use Adobe\IMS\AdobeIO;
use Adobe\Launch\ReactorAPI;
use Slack\SlackAPI;
use Ahc\Jwt\JWT;


require_once __DIR__ . '/../autoload.php';


/***************************************
/* Command Line Handling
/***************************************/
$usage = "\nLAUNCH CATALOG NOTIFIER\n\n";
$usage .= "DESCRIPTION: This script can be used to query the Adobe Launch Catalog and notify slack of updated or new extensions.\n";
$usage .= "It is intended to be a cron entry to be executed at the frequency the user would like to check for new or updated extensions.\n\n";
$usage .= "  USAGE:  php " . __FILE__ . "\n\n";
$usage .= "    config  => Ex: 'config=development'.  Indicates which file to read for configuration from the config dir.\n";
$usage .= "    verbose => Set to true if you wish verbose or debug output.\n\n";

// Get command line args
for( $i=1; $i<count($argv); $i++ ){
    
    // show script help
    if($argv[$i] == 'help' || $argv[$i] == '-h' || $argv[$i] == '--help' || $argv[$i] == '--h'){
        die ("$usage\n");
    }
    
    list($k,$v) = explode('=',$argv[$i]);
    $$k = $v;
}


// Was config file specified?
if (isset($config) && strlen($config) > 2) {
    $hasFileExtension = strpos($config,'.php');
    
    if ($hasFileExtension > 0) {
        $state = substr($config, 0, $hasFileExtension);
    }
    else {
        $state = $config;
    }
}
else {
    $state = 'default';
}




/***************************************
/* Read Config and Include Dependencies
/***************************************/

try {
    if (!(@include  sprintf(__DIR__ . "/../config/%s.php", $state) )) {
        throw new Exception ('Specific config file does not exist');
    }
}
catch (Exception $e) {
    echo "\nThe config file you specified '" . $state . ".php' was not found.\nPlease make sure you have a file in the config direct matching your input.\n\n";
    exit;
}


$verbose = $config['environment']['verbose'];
$jwt = new JWT($config['adobe']['io']['private_key_path'], 'RS256');
$token = $jwt->encode([
        'iss'    => $config['adobe']['io']['ims_org'],
        'sub'    => $config['adobe']['io']['tech_acct_id'],
        'https://ims-na1.adobelogin.com/s/ent_reactor_admin_sdk' => true,
        'aud'    => "https://ims-na1.adobelogin.com/c/" . $config['adobe']['io']['client_id'],
]);



/***************************************
/* Sanity Checks
/***************************************/
if (!isset($config['adobe']['io']['ims_org']) || $config['adobe']['io']['ims_org'] == 'YOUR_IMS_ORG_GOES_HERE'){
    echo "\nYou have not yet specified your IMS org in the config directory.\n";
    exit;
}
if (!isset($config['adobe']['io']['client_id']) || $config['adobe']['io']['client_id'] == 'YOUR_ADOBEIO_CLIENT_ID_GOES_HERE'){
    echo "\nYou have not yet specified your AdobeIO client ID obtained from https://console.adobe.io/.\n";
    exit;
}
if (!isset($config['adobe']['io']['client_secret']) || $config['adobe']['io']['client_secret'] == 'YOUR_ADOBEIO_CLIENT_SECRET_GOES_HERE'){
    echo "\nYou have not yet specified your AdobeIO client secret obtained from https://console.adobe.io/.\n";
    exit;
}
if (!isset($config['adobe']['io']['tech_acct_id']) || $config['adobe']['io']['tech_acct_id'] == ''){
    echo "\nYou have not yet specified your AdobeIO technical account ID obtained from https://console.adobe.io/.\n";
    exit;
}
if (!isset($config['adobe']['launch']['launch_api']) || $config['adobe']['launch']['launch_api'] == ''){
    echo "\nYou have not yet specified the Adobe Launch Endpoint for the environment you want.\n";
    exit;
}
if (!isset($config['slack']['webhooks']) || $config['slack']['webhooks'][0] == 'YOUR_SLACK_WEB_HOOK_GOES_HERE'){
    echo "\nYou have not yet specified your AdobeIO client secret obtained from https://console.adobe.io/.\n";
    exit;
}



/***************************************
/* Script Processing
/***************************************/

// Metrics
$intExtensionsInCatalog     = 0;
$intNewExtensions           = 0;
$intLocalCachedExtensions   = 0;
$intUpgradedExtensions      = 0;

// Slack API Notifier
$slack = new SlackAPI();
$slack->setWebhooksList($config['slack']['webhooks']);


// Local Database
$strCacheFile = __DIR__ ."/cache/Extensions_" . $config['adobe']['launch']['launch_catalog'] . ".txt";

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
$adobeIO = new AdobeIO($config['adobe']['io']['ims_end_point']);
$access_token = $adobeIO->getAccessToken($config['adobe']['io']['client_id'], $config['adobe']['io']['client_secret'], $token);


// Adobe Launch API
$reactor = new ReactorAPI($config['adobe']['launch']['launch_api'], $access_token);

if (isset($config['adobe']['launch']['launch_catalog']) && strtolower(trim($config['adobe']['launch']['launch_catalog']) == 'mobile')) {
    $arrExtensions = $reactor->getMobileExtensions();
}
else {
    // Assume Web
    $arrExtensions = $reactor->getWebExtensions();
}
    


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
                    $slack->sendUpgradeMessage($config['adobe']['launch']['launch_env'], $arrFoundExtension['display_name'], $strCurrentVersion, $arrFoundExtension['created']);
                    if ($verbose) {
                        echo "Sent notification of new version of '" . $arrFoundExtension['display_name'] . "' extension.\n";
                    }
                    $intUpgradedExtensions++;
                }
            }
            else {
                // New Extension!!
                $slack->sendNewExtensionMessage($config['adobe']['launch']['launch_env'], $arrFoundExtension['display_name'], $arrFoundExtension['author'], $arrFoundExtension['created'], $arrFoundExtension['exchange']);
                if ($verbose) {
                    echo "FYI:  sent notification of new extension named '" . $arrFoundExtension['display_name'] . "'.\n";
                }
                $intNewExtensions++;
            }
        }
    }
}

ksort($arrFoundExtesnions);

if (is_array($arrFoundExtesnions) && sizeof($arrFoundExtesnions) > 0) {
    // Update the local cache
    try {
        if (isset($recLocalExtensions) && is_resource($recLocalExtensions)) {
            rewind($recLocalExtensions);
        }
        $recLocalExtensions = fopen(__DIR__ . "/cache/Extensions_" . $config['adobe']['launch']['launch_catalog'] . ".txt", "w+");
        $serializedExtensions = serialize($arrFoundExtesnions);
        fwrite($recLocalExtensions, $serializedExtensions);
        fclose($recLocalExtensions);
    }
    catch (Exception $e) {
        echo "\nFailed to read or write the local storage file to cache directory.\nPlease make sure this sript can write to this directory.\n";
        exit;
    }
}

// Show Stats
if ($verbose) {
    echo "\n===============================================\n";
    echo "Completed successfully at " . date("Y-m-d H:i:s T") . "\n";
    echo "There are currently " . $intExtensionsInCatalog . " extensions in the catalog.  There were " . $intLocalCachedExtensions . " in the local cache.\n";
    echo "There were " . $intUpgradedExtensions . " extensions upgraded, and " . $intNewExtensions . " new extensions released.";
    echo "\n===============================================\n\n";
}
else {
    echo "\nCompleted successfully at " . date("Y-m-d H:i:s T") . "\n";
    echo "Sent " . ($intUpgradedExtensions + $intNewExtensions) . " notifications.\n";
}
exit;
