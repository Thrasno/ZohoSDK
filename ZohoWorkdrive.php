<?php

    class ZohoWorkdrive_SDK {

        const TIMEFRAME_EXPIRE = 60;//Segundos para evitar la caducidad de access token por precaución

        private $refreshToken;
        private $client_id;
        private $client_secret;
        private $access_token;
        private $userIdentifier;
        private $location;
        private $expires_accesstoken = 0;
        private $version = 0;
        
        public function __construct($client_id, $client_secret, $refreshToken, $access_token, $location) {

            /*if(version_compare(phpversion(), '5.6', '<')) {
                throw new Exception("PHP version must be 5.6 or higher. Used " . phpversion());
            }*/
            if(!extension_loaded("curl")) {
                throw new Exception("Extension \"Curl\" not loaded.");
            }
            $this->refreshToken = $refreshToken;
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->location = $location;
            $this->access_token = $access_token;
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

        public function getRefreshToken() {

            return $this->refreshToken;
        }

        public function downloadFile($id) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            //Llamar una función de Zoho CRM
            $url = "https://workdrive.zoho." . $this->location . "/api/v1/download/$id";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "GET", $headers, 0);

            return $resultNoFormatted;
        }
        public function getFile($id) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            //Llamar una función de Zoho CRM
            $url = "https://workdrive.zoho." . $this->location . "/api/v1/files/$id";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "GET", $headers, 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
        public function listFiles($id) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            //Llamar una función de Zoho CRM
            $url = "https://workdrive.zoho." . $this->location . "/api/v1/files/$id/files";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "GET", $headers, 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
        public function uploadFile($filename, $parent_id, $filePath) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }
            
            $data = array(
                "filename" => $filename, 
                "parent_id" => $parent_id, 
                "content" => new CURLFile($filePath)
            );

            //Llamar una función de Zoho CRM
            $url = "https://workdrive.zoho." . $this->location . "/api/v1/upload";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "POST", $headers, 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
        public function trashFile($idFfile) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }
            $data = array("data" => array("attributes" => array("status" => "51"), "type" => "files"));

            //Llamar una función de Zoho CRM
            $url = "https://www.zohoapis." . $this->location . "/workdrive/api/v1/files/$idFfile";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "PATCH", $headers, 1, json_encode($data));

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
        public function renameFile($idFile, $name) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }
            $data = array("data" => array("attributes" => array("name" => $name), "type" => "files"));

            //Llamar una función de Zoho CRM
            $url = "https://www.zohoapis." . $this->location . "/workdrive/api/v1/files/$idFile";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "PATCH", $headers, 1, json_encode($data));

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
        public function getFiles($idFolder) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }
            //Llamar una función de Zoho CRM
            $url = "https://www.zohoapis." . $this->location . "/workdrive/api/v1/files/$idFolder/files";
            $headers = array("Authorization: Zoho-oauthtoken " . $this->access_token, "Accept: application/vnd.api+json");

            $resultNoFormatted = $this->callCurl($url, "GET", $headers, 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
    }
?>
