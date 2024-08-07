<?php
class ZohoProjects_SDK
{
    const TIMEFRAME_EXPIRE = 60; //Segundos para evitar la caducidad de access token por precaución

    private $refreshToken;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $location;
    private $portalID;
    private $expires_accesstoken = 0;

    public function __construct($client_id, $client_secret, $refreshToken, $access_token, $location, $portalID)
    {

        /*if(version_compare(phpversion(), '5.6', '<')) {
                throw new Exception("PHP version must be 5.6 or higher. Used " . phpversion());
            }*/
        if (!extension_loaded("curl")) {
            throw new Exception("Extension \"Curl\" not loaded.");
        }
        $this->refreshToken = $refreshToken;
        $this->portalID = $portalID;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->location = $location;
        $this->access_token = $access_token;
    }


    public function getAccessToken()
    {

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

    private function callCurl($url, $customRequest, $httpHeader, $post, $postfields = array())
    {
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
        } catch (Exception $exception) {
            throw new Exception("Excepción llamada Curl. Exception Message: " . $exception->getMessage() . ". Exception Trace: " . $exception->getTraceAsString());
        }
        if ($errorCurl) {
            throw new Exception("Curl error: $errorCurl");
        }
        return $resultNoFormatted;
    }

    public function getRefreshToken()
    {

        return $this->refreshToken;
    }

    public function addTaskAttachement($file, $project, $task)
    {

        if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
            $this->getAccessToken();
        }

        $data = array("uploaddoc" => $file);
        //print_r($data);
        $url = "https://projectsapi.zoho." . $this->location . "/restapi/portal/" . $this->portalID . "/projects/" . $project . "/tasks/" . $task . "/attachments/";
        $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
        $accept = "Accept: application/json";

        $resultNoFormatted = $this->callCurl($url, "POST", array($authorization, $accept), 1, $data);

        $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
        $result = json_decode($json, true);
        return $result;
    }

    //NOT Tested
    public function getTaskLayoutDetails($project)
    {

        if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
            $this->getAccessToken();
        }

        $url = "https://projectsapi.zoho." . $this->location . "/restapi/portal/" . $this->portalID . "/projects/" . $project . "/tasklayouts";
        var_dump($url);
        $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

        $resultNoFormatted = $this->callCurl($url, "GET", array($authorization),0);

        $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
        $result = json_decode($json, true);
        return $result;
    }
}
