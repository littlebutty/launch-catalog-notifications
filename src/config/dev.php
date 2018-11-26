<?php

/**
 * Development Configuration Settings
 */
$config = array(
        'environment' => array(
                'state'                 => 'production',
                'verbose'               => false,
        ),
        
        'adobe' => array(
                'ims_end_point'         => 'https://ims-na1.adobelogin.com/ims/exchange/jwt/',
                'ims_org'               => 'YOUR_IMS_ORG_GOES_HERE',
                'client_id'             => 'YOUR_ADOBEIO_CLIENT_ID_GOES_HERE',
                'client_secret'         => "YOUR_ADOBEIO_CLIENT_SECRET_GOES_HERE", // https://console.adobe.io/integrations/23306/51468/overview
                'jwt_token'             => "YOUR_ADOBEIO_JWT_TOKEN_GOES_HERE",
        ),
        
        'slack' => array(
                'webhooks' => array(
                                           "YOUR_SLACK_WEB_HOOK_GOES_HERE",
                        
                ),
        ),
);