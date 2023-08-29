<?php
    class ZohoCRM_SDK{
        const TIMEFRAME_EXPIRE = 60;//Segundos para evitar la caducidad de access token por precaución

        private $refreshToken;
        private $client_id;
        private $client_secret;
        private $access_token;
        private $userIdentifier;
        private $location;
        private $expires_accesstoken = 0;
        private $version = "v3";
        private $sandbox = "";

        public function __construct($client_id, $client_secret, $refreshToken, $access_token, $userIdentifier, $location, $version = null) {

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
            if ($version == null) {
                $this->version = "" . $this->version . "";
            } else {
                $this->version = $version;
            }
        }

        public function setSandbox($sSandbox = false) {
            if ($sSandbox) {
                $this->sandbox = "sandbox.";
            } else {
                $this->sandbox = "";
            }
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

        public function getUserIdentifier() {

            return $this->userIdentifier;
        }

        public function serverlessFunctions($functionName, $vars = array()) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            //Llamar una función de Zoho CRM
            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/functions/$functionName/actions/execute?auth_type=oauth";
            foreach ($vars as $key => $value) {
                $url .= "&$key=$value";
            }
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function delete($module, $id) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module/$id";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
            
            $resultNoFormatted = $this->callCurl($url, "DELETE", array($authorization), 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function bulkDelete($module, $ids) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            if (!is_array($ids)) {
                throw new Exception("bulkDelete expected second parameter to be an array.");
            }
            if (count($ids) == 0) {
                throw new Exception("bulkDelete expected second parameter to be an array of ids, an empty array is given.");
            }
            if (count($ids) > 100) {
                throw new Exception("bulkDelete expected second parameter size must equals or less than 100.");
            }
            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module?ids=" . implode(",", $ids);
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "DELETE", array($authorization), 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function bulkInsert($module, $records, $trigger = array()) {

            $data = json_encode(array("data" => $records, "trigger" => $trigger));
            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function updateRecord($module, $id, $record, $trigger = array()) {

            $data = json_encode(array("data" => array($record), "trigger" => $trigger));
            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module/$id";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "PUT", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function updateRecords($module, $records, $trigger = array()) {

            $data = json_encode(array("data" => $records, "trigger" => $trigger));
            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "PUT", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function getVariables() {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/settings/variables";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0, array());

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function getVariable($idVar, $idGroup) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/settings/variables/$idVar?group=$idGroup";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0, array());

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function upsertRecords($module, $records, $trigger = array(), $duplicate_check_fields = array()) {

            $data = json_encode(array("data" => $records, "trigger" => $trigger, "duplicate_check_fields" => $duplicate_check_fields));

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/" . $module . "/upsert";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function getSpecificRecord($module, $recordId) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/" . $module . "/$recordId";
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function searchRecords($module, $criteria, $page = 1, $per_page = 200) {

            $datosCompletos = array();

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time() && false) {
                $this->getAccessToken();
            }
            do{
                $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/" . $module . "/search?criteria=" . $criteria . "&page=" . $page . "&per_page=" . $per_page;
                $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
                $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0 );
                $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
                $result = json_decode($json, true);
                if (!$result["error"] && isset($result["data"])){
                    foreach ($result["data"] as $data){
                        $datosCompletos[]=$data;
                    }
                }
                $page++;
                usleep(600000);//Pausa de 0.6s para no bloquear la API.
            } while (isset($result["info"]["more_records"]) && $result["info"]["more_records"] == 1);
            
            return $datosCompletos;
        }

        public function listRecords($module, $page = 1, $per_page = 200, $cvid = null, $fields = null) {

            $datosCompletos = array();
            
            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }
            do{
                $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/" . $module . "?page=" . $page . "&per_page=" . $per_page . "&cvid=" . $cvid . "&fields=" . $fields;
                $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;
                $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0 );
                $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
                $result = json_decode($json, true);
                if (!$result["error"] && isset($result["data"])){
                    foreach ($result["data"] as $data){
                        $datosCompletos[]=$data;
                    }
                }
                $page++;
                usleep(600000);//Pausa de 0.6s para no bloquear la API.
            } while (isset($result["info"]["more_records"]) && $result["info"]["more_records"] == 1);
            
            return $datosCompletos;
        }

        public function coql($select_query) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            //Llamar una función de Zoho CRM
            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/coql";
            $data = json_encode(array("select_query" => $select_query));
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization), 1,  $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function listAttachments($module, $recorId) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module/$recorId/Attachments" ;
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0 );

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function uploadAttachmentURL($module, $recorId, $urlAttach, $name) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }
            
            $data = array("attachmentUrl" => $urlAttach, "file_Name" => $name);

            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module/$recorId/Attachments" ;
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        public function sendMail($module, $recorId, $data) {

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }
            
            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/$module/$recorId/actions/send_mail" ;
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "POST", array($authorization), 1, $data);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

        //NOT TESTED (API Version >= 4)
        public function getAllUsersFromGroup( $idGrupo) {    

            if ($this->expires_accesstoken - self::TIMEFRAME_EXPIRE <= time()) {
                $this->getAccessToken();
            }
            
            $url = "https://" . $this->sandbox . "zohoapis." . $this->location . "/crm/" . $this->version . "/settings/$idGrupo/sources?type=sources" ;
            $authorization = "Authorization: Zoho-oauthtoken " . $this->access_token;

            $resultNoFormatted = $this->callCurl($url, "GET", array($authorization), 0);

            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $resultNoFormatted);
            $result = json_decode($json, true);

            return $result;
        }

    }
?>