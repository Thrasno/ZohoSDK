<?php

    class ZohoAnalytics_SDK {

        const TIMEFRAME_EXPIRE = 60;//Segundos para evitar la caducidad de access token por precaución

        private $refreshToken;
        private $client_id;
        private $client_secret;
        private $access_token;
        private $location;
        private $expires_accesstoken = 0;
        private $workspace_id;
        private $org_id;
        public function __construct($client_id, $client_secret, $refreshToken, $access_token, $workspace_id, $location, $org_id) {

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
            $this->workspace_id = $workspace_id;
            $this->org_id = $org_id;

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

        public function getOrganizations() {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://analyticsapi.zoho." . $this->location . "/restapi/v2/orgs";
            $header = array("Authorization: Zoho-oauthtoken " . $this->access_token);

            $resultNoFormatted = $this->callCurl($url, "GET", $header, 0 );

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);

            $result = json_decode($json, true);

            return $result;
        }

        public function getRefreshToken() {

            return $this->refreshToken;
        }

        public function exportData($viewId, $config = array()) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://analyticsapi.zoho." . $this->location . "/restapi/v2/workspaces/" . $this->workspace_id . "/views/$viewId/data?CONFIG=" . urlencode(json_encode($config));
            $header = array("Authorization: Zoho-oauthtoken " . $this->access_token, "ZANALYTICS-ORGID: " . $this->org_id);

            $resultNoFormatted = $this->callCurl($url, "GET", $header, 0 );

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
    }
?>
