<?php
namespace Adobe\IMS;


/**
 * Methods for interacting with AdobeIO Gateway
 * 
 * @author bpack
 *
 */
class AdobeIO
{
    /*
     * URL to IMS Gateway
     */
    private $_endpoint;

    
    
    /**
     * Default Constructor 
     * 
     * @param String URL for AdobeIO Endpoint
     */
    public function __construct ($endpoint)
    {
        $this->_endpoint = $endpoint;
    }
    
    
    /**
     * Method for obtaining a User Token from IMS
     * 
     * @param String $client_id
     * @param String $client_secret
     * @param String $jwt_token
     * @return String
     */
    public function getAccessToken ($client_id, $client_secret, $jwt_token)
    {
        // Build POST message
        $postData = sprintf("------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_id\"\r\n\r\n%s\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_secret\"\r\n\r\n%s\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"jwt_token\"\r\n\r\n%s\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--", $client_id, $client_secret, $jwt_token);
        
        // cURL call
        $ch = curl_init($this->_endpoint); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW',
                'Cache-Control: no-cache')
                );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err || strlen($response) == 0) {
            echo "\nFailed to get IMS access token.  Response was: " . $response;
        }
        else {
            $arrResponse = json_decode($response, true);
            if (!$arrResponse || sizeof($arrResponse) <= 0 || !isset($arrResponse['access_token'])) {
                echo "\nFailed to get IMS access token.  Response was: " . $response;
                return false;
            }
            else {
                return $arrResponse['access_token'];
            }
        }
    }
}

