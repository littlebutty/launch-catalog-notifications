<?php
namespace Adobe\Launch;


/**
 * Class for interacting with the Launch or Reactor APIs
 * 
 * @author bpack
 *
 */
class ReactorAPI
{
    
    /**
     * IMS Access Token for authenticated calls to Launch APIs
     * 
     * @var String
     */
    private $_access_token;

    /**
     * URL enpoint for the Launch API
     * 
     * @var String
     */
    private $_api_endpoint;
    
    
    /**
     * Privat function for making the underlining API call.
     * 
     * TODO:  make the payload dynamic as well
     * 
     * @param  String $url
     * @return JSON
     */
    private function sendApiCall($url)
    {
        //$url = $this->_api_endpoint . "?page[size]=999&sort=display_name&filter[platform]=EQ%20web,EQ%20null&max_availability=private";
        $authorization = sprintf("Bearer %s", $this->_access_token);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "",
                CURLOPT_HTTPHEADER => array(
                        "Accept: application/vnd.api+json;revision=1",
                        "Authorization: Bearer " . $authorization . "",
                        "cache-control: no-cache",
                        "x-api-key: Activation-DTM"
                ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
            exit;
        }
        
        $arrExtensions = json_decode($response, true);
        if (!is_array($arrExtensions) || !isset($arrExtensions['data'])) {
            echo "cURL Error when calling Launch APIs.  Reponse: " . var_dump($response);
            exit;
        }
        return $arrExtensions['data'];
    }
    
    
    
    /**
     * Default Constructor
     * Takes in a valid IMS Autheticated Token
     * 
     * @param String $access_token
     */
    public function __construct ($end_point, $access_token)
    {
        $this->_api_endpoint = $end_point;
        $this->_access_token = $access_token;
    }
    
    
    /**
     * Call to get a list of the Extensions currently in the
     * Launch Catalog.
     *
     * @see https://developer.adobelaunch.com/api/extension_packages/list/
     * @return Array
     */
    public function getWebExtensions()
    {
        $url = $this->_api_endpoint . "?page[size]=999&sort=display_name&filter[platform]=EQ%20web,EQ%20null&max_availability=private";
        return $this->sendApiCall($url);
    }
    
    
    /**
     * Call to get a list of the Extensions currently in the
     * Launch Catalog.
     *
     * @see https://developer.adobelaunch.com/api/extension_packages/list/
     * @return Array
     */
    public function getMobileExtensions()
    {
        $url = $this->_api_endpoint . "?page[size]=999&sort=display_name&filter[platform]=EQ%20mobile,EQ%20null&max_availability=private";
        return $this->sendApiCall($url);
    }
    
    
    public function createProperty ()
    {
        
    } 
    
}