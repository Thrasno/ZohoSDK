<?php
/**
 * Books
 *
 * @version      1.0
 * @author       Francisco <francisco@conpas.net>
 *
 */
class ZohoBooks_SDK
{
    public $sendMethod = "GET";
    const TIMEFRAME_EXPIRE = 60;//Segundos para evitar la caducidad de access token por precaución

    private $refreshToken;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $location;
    private $expires_accesstoken = 0;
    private $version = "v3";
    private $orgID;
    /*$this->add_raw = false;
    $this->add_scope = false;*/

    public function __construct($client_id, $client_secret, $refreshToken, $access_token, $orgID, $location, $version = null) {

        if(!extension_loaded("curl")) {
            throw new Exception("Extension \"Curl\" not loaded.");
        }
        $this->refreshToken = $refreshToken;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->location = $location;
        $this->access_token = $access_token;
        if ($version == null) {
            $this->version = "" . $this->version . "";
        } else {
            $this->version = $version;
        }
        $this->orgID = $orgID;
    }

    public function getAccessToken() {

        //Recoger acces token
        $url = "https://accounts.zoho." . $this->location . "/oauth/v2/token?refresh_token=" . $this->refreshToken . "&client_id=" . $this->client_id . "&client_secret=" . $this->client_secret . "&grant_type=refresh_token";
        $resultNoFormatted = $this->callCurl($url, "POST", array(), 1);

        $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
        $result = json_decode($json, true);

        if (isset($result["access_token"])) {
            $this->access_token = $result["access_token"];
            $this->expires_accesstoken = $result["expires_in"] + time();
        } else {
            throw new Exception("No se ha podido recoger el access token. Curl result: " . $resultNoFormatted);
        }
        return $this->access_token;
    }

    private function callCurl($url, $customRequest, $httpHeader, $post, $postfields = array()) {

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, $post);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $resultNoFormatted = curl_exec($ch);
            $errorCurl = curl_error($ch);
            curl_close($ch);
        } catch(Exception $exception) {
            throw new Exception("Excepción llamada Curl. Exception Message: " . $exception->getMessage() . ". Exception Trace: " . $exception->getTraceAsString());
        }
        if ($errorCurl) {
            throw new Exception("Curl error: $errorCurl");
        }
        return $resultNoFormatted;

    }

    public function listRecords($module,$criteria,$page = 1,$perPage = 200)
    {
        $datosCompletos=array();
        do{
            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();    
            }
            $url = "https://www.zohoapis.".$this->location."/books/".$this->version."/".$module."?organization_id=".$this->orgID."&".$criteria."&page=".$page."&per_page=".$perPage;

            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);
            $json = preg_replace('/("[\w]+":)(-?\d+(\.\d+)?(E[-+]?\d+)?)/', '\\1"\\2"', $resultNoFormatted);
            $result = json_decode($json, true);
            if ($result["code"]==0 && isset($result[$module])){
                foreach ($result[$module] as $data){
                    $datosCompletos[]=$data;
                }
            }
            $page+=1;
            usleep(6000);
        } while (isset($result["page_context"]["has_more_page"]) && $result["page_context"]["has_more_page"] == 1);
        
        return $datosCompletos;
    }

    public function listSettingsRecords($module,$page = 1,$perPage = 200)
    {
        $datosCompletos=array();
        do{
            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();    
            }
            $url = "https://www.zohoapis.".$this->location."/books/".$this->version."/settings/".$module."?organization_id=".$this->orgID."&page=".$page."&per_page=".$perPage;

            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);
            $json = preg_replace('/("[\w]+":)(-?\d+(\.\d+)?(E[-+]?\d+)?)/', '\\1"\\2"', $resultNoFormatted);
            $result = json_decode($json, true);
            if ($result["code"]==0 && isset($result[$module])){
                foreach ($result[$module] as $data){
                    $datosCompletos[]=$data;
                }
            }
            $page+=1;
            usleep(6000);
        } while (isset($result["page_context"]["has_more_page"]) && $result["page_context"]["has_more_page"] == 1);
        
        return $datosCompletos;
    }

    public function getRecordById($module,$id)
    {

        if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
            $this->getAccessToken();    
        }
        $url = "https://www.zohoapis.".$this->location."/books/".$this->version."/".$module."/".$id."?organization_id=".$this->orgID;

        $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
        $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);
        $json = preg_replace('/("[\w]+":)(-?\d+(\.\d+)?(E[-+]?\d+)?)/', '\\1"\\2"', $resultNoFormatted);
        $result = json_decode($json, true);
        
        return $result;
    }

    public function getSettingsRecordById($module,$id)
    {

        if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
            $this->getAccessToken();    
        }
        $url = "https://www.zohoapis.".$this->location."/books/".$this->version."/settings/".$module."/".$id."?organization_id=".$this->orgID;

        $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
        $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);
        $json = preg_replace('/("[\w]+":)(-?\d+(\.\d+)?(E[-+]?\d+)?)/', '\\1"\\2"', $resultNoFormatted);
        $result = json_decode($json, true);
        
        return $result;
    }

    public function updateRecordById($module,$id,$data){

        if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
            $this->getAccessToken();    
        }

        $url = "https://www.zohoapis.".$this->location."/books/".$this->version."/".$module."/".$id."?organization_id=".$this->orgID;

        $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
        $content = "Content-Type: application/json";

        $resultNoFormatted = $this->callCurl($url, "PUT", array($authorization,$content), 0,json_encode($data));
        $json = preg_replace('/("[\w]+":)(-?\d+(\.\d+)?(E[-+]?\d+)?)/', '\\1"\\2"', $resultNoFormatted);
        $result = json_decode($json, true);
        
        return $result;
    }

    public function createRecord($module,$data){

        if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
            $this->getAccessToken();    
        }

        $url = "https://www.zohoapis.".$this->location."/books/".$this->version."/".$module."?organization_id=".$this->orgID;

        $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
        $content = "Content-Type: application/json";

        $resultNoFormatted = $this->callCurl($url, "POST", array($authorization,$content), 1,json_encode($data));
        $json = preg_replace('/("[\w]+":)(-?\d+(\.\d+)?(E[-+]?\d+)?)/', '\\1"\\2"', $resultNoFormatted);
        $result = json_decode($json, true);
        
        return $result;
    }


}
