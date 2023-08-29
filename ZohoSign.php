<?php

class ZohoSign_SDK {

    const TIMEFRAME_EXPIRE = 60; //Segundos para evitar la caducidad de access token por precaución

    private $refreshToken;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $userIdentifier;
    private $location;
    private $expires_accesstoken = 0;

    public function __construct($configuration) {

        if (version_compare(phpversion(), '5.6', '<')) {
            throw new Exception("PHP version must be 5.6 or higher. Used " . phpversion());
        }
        if (!extension_loaded("curl")) {
            throw new Exception("Extension \"Curl\" not loaded.");
        }

        $this->refreshToken = $configuration["refresh_token"];
        $this->client_id = $configuration["client_id"];
        $this->client_secret = $configuration["client_secret"];
        $this->location = $configuration["location"];
        $this->userIdentifier = $configuration["currentUserEmail"];
    }

    public function getAccessToken() {
        //Recoger access token
        $url = "https://accounts.zoho." . $this->location . "/oauth/v2/token?refresh_token=" . $this->refreshToken . "&client_id=" . $this->client_id . "&client_secret=" . $this->client_secret . "&grant_type=refresh_token";
        $respuesta = $this->callCurl($url, array(), "POST", array());

        if (!($respuesta["error"])) {
            $array_token = $respuesta["response"];
            $this->access_token = $array_token["access_token"];
            $this->expires_accesstoken = $array_token["expires_in_sec"] + time();
        } else {
            throw new Exception("No se ha podido recoger el access token. Curl result: " . json_encode($respuesta["response"]));
        }
        return $this->access_token;
    }

    public function getDocumentsList() {

        $array_records = array();

        $page = 1;

        do {

            $url_search = "https://sign.zoho." . $this->location . "/api/v1/requests?data=";

            $httpHeader = array("Authorization: Zoho-oauthtoken " . $this->access_token);

            $data_env = array("page_context" => array("row_count" => 100, "start_index" => 1));

            $respuesta = $this->callCurl($url_search, $httpHeader, "GET", json_encode($data_env));

            if (!$respuesta["error"]) {
                foreach ($respuesta["response"]["requests"] as $data) {
                    $array_records[] = $data;
                }
            }
            $page++;
        } while (isset($respuesta["response"]["page_context"]["has_more_rows"]) && $respuesta["response"]["page_context"]["has_more_rows"] == 1);


        return $array_records;
    }

    public function getDetailsDocument($idDocument) {
        $array_records = array();

        $url_get = "https://sign.zoho." . $this->location . "/api/v1/requests/" . $idDocument;

        $httpHeader = array("Authorization: Zoho-oauthtoken " . $this->access_token);

        $respuesta = $this->callCurl($url_get, $httpHeader, "GET", array());

        if (!$respuesta["error"]) {
            $array_records = $respuesta["response"]["requests"];
        }

        return $array_records;
    }

    public function recallDocument($idDocument) {
        $array_records = array();

        $url_get = "https://sign.zoho." . $this->location . "/api/v1/requests/";

        $postfields = $idDocument;

        $httpHeader = array("Authorization: Zoho-oauthtoken " . $this->access_token);

        $respuesta = $this->callCurl($url_get, $httpHeader, "POST", $postfields);
    }

    public function downloadPDF($idRequest) {

        $url_get = "https://sign.zoho." . $this->location . "/api/v1/requests/" . $idRequest . "/pdf";

        $httpHeader = array("Authorization: Zoho-oauthtoken " . $this->access_token);

        $respuesta = $this->callCurl($url_get, $httpHeader, "GET", array(), true);

        return $respuesta;
    }

    private function callCurl($url, $httpHeader, $method, $postfields = array(), $downloadFile = false) {

        $respuesta = array();
        $error = false;

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

            if ($method == "GET") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if (!empty($postfields))
                    curl_setopt($ch, CURLOPT_URL, "$url?" . http_build_query($postfields));
                else
                    curl_setopt($ch, CURLOPT_URL, $url);

                curl_setopt($ch, CURLOPT_POST, 0);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $exec_ch = curl_exec($ch);

            if ($downloadFile) {

                return $exec_ch;
            }


            $json = preg_replace('/("\w+"):(\d+)(.\d+)*(E)*(\d+)?/', '\\1:"\\2\\3\\4\\5"', $exec_ch);

            $response = json_decode($json, true);


            if (!curl_errno($ch)) {
                switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                    case 200:  # OK para peticiones simples											
                        $error = false;
                        break;
                    case 201:  # OK para masivas											
                        $error = false;
                        break;
                    case 204:   # No content										
                        $error = false;
                        break;
                    default: //202 correcta pero con matices, p.e. alguno de los registros no se ha insertado
                        $error = true;
                        $respuesta["error"] = true;
                        $respuesta["response"] = $response;
                }
            } else {
                $error = true;
                $respuesta["error"] = true;
                $respuesta["response"] = 'Error CURL' . curl_errno($ch);
            }
            if (!$error) {
                $respuesta["error"] = false;
                $respuesta["response"] = $response;
            }
            curl_close($ch);
        } catch (Exception $e) {

            $respuesta["error"] = true;
            $respuesta["response"] = $response;
            throw new Exception("Excepción llamada Curl. Exception Message: " . $e->getMessage() . ". Exception Trace: " . $e->getTraceAsString());
        }

        return $respuesta;
    }

    public function getRefreshToken() {

        return $this->refreshToken;
    }

    public function getUserIdentifier() {

        return $this->userIdentifier;
    }

}
