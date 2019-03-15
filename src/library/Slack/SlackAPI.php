<?php
namespace Slack;


/**
 * Class for interacting with Slack Webhooks
 * 
 * @author bpack
 *
 */
class SlackAPI
{
    
    /**
     * An array of all the locations a message should be posted to.
     * 
     * @var Array
     */
    private $_webhook_urls;
    
    
    
    
    /**
     * Default Constructor
     */
    public function __construct ()
    {
        $this->_webhook_urls = array();
    }
    
    
    /**
     * Add a URL to the list of locations to be notified
     * 
     * @param String $webHookUrl
     */
    public function addWebHookLocation ($webHookUrl)
    {
        $this->_webhook_urls[] = $webHookUrl;
    }
    
    
    /**
     * Pass in an entire collection of URLs.
     * Array with no keys
     * 
     * @param Array $arrWebHooks
     */
    public function setWebhooksList ($arrWebHooks) 
    {
        if (is_array($arrWebHooks)) {
            $this->_webhook_urls = $arrWebHooks;
        }
    }
    
    
    /**
     * Member function to format the message according to
     * slack specifications.
     * 
     * @param String $message
     * @access private
     * @return String[]
     */
    private function prepareMessage ($message)
    {
        return array("text" => trim($message));
    }

    
    /**
     * Method for iterating over the collection of destinations
     * and sending the message to each webhook URL
     * 
     * @param String $message
     * @return int
     */
    public function postMessage ($message)
    {
        $jsonMessage = json_encode($this->prepareMessage($message));
        
        foreach ($this->_webhook_urls as $webHook){
            $ch = curl_init($webHook);        
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonMessage); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonMessage))
                    );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            curl_exec($ch);
            curl_close($ch);
        }
        
        return sizeof($this->_webhook_urls);
    }
    
    
    /**
     * Method to call when an existing Launch Extension was Upgraded
     * 
     * @param String $name
     * @param String $version
     * @param String $date
     * @return int
     */
    public function sendUpgradeMessage ($environment, $name, $version, $date) 
    {
        $time = strtotime($date);
        $friendlyDate = date('l jS \of F Y h:i:s A', $time);
        $strUpgradeMessage = sprintf("FYI: A new version of the '%s' extension was upgraded to v'%s' in the Launch %s Catalog.", $name, $version, $environment);
        
        return $this->postMessage($strUpgradeMessage);
    }
    

    /**
     * Method to call when a New Launch Extension was Released
     * 
     * @param String $name
     * @param String $author
     * @param String $date
     * @param String $url (URL to Extension Info.  Usuall Exchange Listing)
     * @return int
     */
    public function sendNewExtensionMessage ($environment, $name, $author, $date, $url = '')
    {
        $time = strtotime($date);
        $friendlyDate = date('l jS \of F Y h:i:s A', $time);
        $strNewMessage = sprintf("WooHoo! A new extension called '%s' was released to the %s Launch Catalog by '%s'.", $name, $environment, $author);
        
        if (strlen($url) > 0) {
            $linkMessage = sprintf("Check out the details here: %s.", $url);
            $strNewMessage = $strNewMessage . " " . $linkMessage;
        }
        
        return $this->postMessage($strNewMessage);
    }
    
}

