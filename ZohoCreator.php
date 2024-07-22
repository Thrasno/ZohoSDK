<?php

    class ZohoCreator_SDK {

        const TIMEFRAME_EXPIRE = 60;//Segundos para evitar la caducidad de access token por precaución

        private $refreshToken;
        private $client_id;
        private $client_secret;
        private $access_token;
        private $userIdentifier;
        private $location;
        private $expires_accesstoken = 0;
        private $owner;
        private $app;
        private $version;

        public function __construct($client_id, $client_secret, $refreshToken, $access_token, $userIdentifier, $location, $owner, $app, $version = "v2") {

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
            $this->userIdentifier = $userIdentifier;
            $this->access_token = $access_token;
            $this->owner = $owner;
            $this->app = $app;
            $this->version = $version;
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
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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
//echo $resultNoFormatted;
            return $resultNoFormatted;

        }

        public function getRefreshToken() {

            return $this->refreshToken;
        }

        public function accessToken() {

            return $this->access_token;
        }


        public function getUserIdentifier() {

            return $this->userIdentifier;
        }


        public function createRecords($form, $records, $fields = array(), $message = true, $tasks = true) {

            $result = array("fields" => $fields, "message" => $message, "tasks" => $tasks);
            $data = json_encode(array("data" => $records, "result" => $result));

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/form/" . $form;
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization, "Content-Type: application/json"), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }
        //NOT TESTED
        public function getRecords($report, $criteria, $from = 1, $limit = 200, $fieldConfig = "quick_view", $fields = null) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/" . $report. "?criteria=$criteria&from=$from&limit=$limit&field_config=$fieldConfig";
            if ($fields != null && $fields != ""){
                $url = $url."&fields=$fields";
            }

            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;


            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization, "Content-Type: application/json"), 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function getRecord($report, $id) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/" . $report. "/$id";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization, "Content-Type: application/json"), 0);
            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function updateRecordById($report, $id, $record, $fields = array(), $message = true, $tasks = true) {


            $result = array("fields" => $fields, "message" => $message, "tasks" => $tasks);
            $data = json_encode(array("data" => $record, "result" => $result));

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/" . $report. "/$id";

            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "PATCH", array($authorization, "Content-Type: application/json"), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        //TODO:Añadir Bucle para actualizar +200registros
        public function updateRecords($report, $criteria, $data, $process_until_limit = true, $fields = array(), $message = true, $tasks= true) {

            $result = array("fields" => $fields, "message" => $message, "tasks" => $tasks);
            $data = json_encode(array("criteria" => $criteria, "data" => $data, "result" => $result));

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/" . $report . "?process_until_limit=$process_until_limit";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;


            $resultNoFormatted = $this->callCurl($url, "PATCH", array($authorization, "Content-Type: application/json"), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        //TODO: Añadir bucle para borrar +200 registros
        public function deleteRecords($report, $criteria, $process_until_limit = true, $message = true, $tasks = true) {

            $result = array("message" => $message, "tasks" => $tasks);
            $data = json_encode(array( "criteria" => $criteria, "result" => $result));

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/" . $report . "?process_until_limit=$process_until_limit";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "DELETE", array($authorization, "Content-Type: application/json"), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function uploadFile($report, $idRecord, $campoUpload, $file) {

            $data = array("file" => $file);

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/$report/$idRecord/$campoUpload/upload";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function downloadFile($report, $idRecord, $campoUpload) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://creator.zoho." . $this->location . "/api/".$this->version."/" . $this->owner . "/" . $this->app . "/report/$report/$idRecord/$campoUpload/download";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);

            return $resultNoFormatted;
        }
    }
?>