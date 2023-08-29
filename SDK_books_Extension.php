<?php
/**
 * Books
 *
 * @version      1.0
 * @author       Francisco <francisco@conpas.net>
 *
 */
class SDK_Books_Extension
{
    public $sendMethod = "GET";

    public function __construct($idOrganizacion = null, $authtoken = null, $location = "com")
    {
        //         echo "USE OAuth2.0 Authentication";die();

        if (version_compare(phpversion(), '5.3', '<')) {
            $this->setError("PHP version must be greater than 5.3");
            $this->throwException("SHOW \$userError");
        }
        if (!extension_loaded("curl")) {
            $this->setError("Extension \"Curl\" not loaded.");
            $this->throwException("SHOW \$userError");
        }
        if ($authtoken != null && $idOrganizacion != null) {
            $this->idOrganizacion = $idOrganizacion;
            $this->setAuthtoken($authtoken);
            $this->scope = "booksapi";
            $this->url = "https://books.zoho.$location/api/v3";
        } else if ($idOrganizacion != null) {
            $this->idOrganizacion = $idOrganizacion;
            $this->setAuthtoken(authtoken::getAuthtoken("books"));
            $this->scope = "booksapi";
            $this->url = "https://books.zoho.com/api/v3";
        } else {
            if (!$this->isProduction()) {
                print('<h2>Authtoken not found<br/>Use $books = new Books ($idOrganizacion, $authtoken, $location="com");</h2>');
            }
            exit;
        }
        $this->add_raw = false;
        $this->add_scope = false;
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


    /******************************************************Contacts******************************************************/
    public function listContacts($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("contact_name", "company_name", "first_name", "last_name", "address", "email", "phone", "filter_by", "search_text", "sort_column", "page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_") {
                $notMandatory[] = $key;
            }
        }
        $url = $this->url . "/contacts";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getContact($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/contacts/" . $datos["contacts_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateContact($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array();
        $url = $this->url . "/contacts/" . $datos["contact_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getContactCRM($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("zcrm_account_id", "zcrm_contact_id");
        $url = $this->url . "/contacts";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************Item******************************************************/
    public function listItems($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("name", "description", "rate", "tax_id", "tax_name", "is_taxable", "tax_exemption_id", "account_id", "filter_by", "search_text", "sort_column");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/items";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getItem($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/items/" . $datos["item_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************Taxes******************************************************/
    public function listTaxes($datos = array())
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/settings/taxes";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getTax($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/settings/taxes/" . $datos["tax_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getTaxGroup($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/settings/taxgroups/" . $datos["tax_group_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************Bills******************************************************/
    public function listBills($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/bills";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getBill($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/bills/" . $datos["bill_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function createBill($datos)
    {

        $this->sendMethod = "POST";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/bills";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateBill($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/bills/" . $datos["bill_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function listBillsTemplates()
    {

        $this->sendMethod = "GET";
        $datos = array();
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array();
        $url = $this->url . "/bills/templates";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************PurchaseOrders******************************************************/
    public function createPurchaseOrder($datos)
    {

        $this->sendMethod = "POST";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/purchaseorders";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************Invoices******************************************************/
    public function listInvoices($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_") {
                $notMandatory[] = $key;
            }
        }
        $url = $this->url . "/invoices";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getInvoice($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/invoices/" . $datos["invoice_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function createInvoice($datos)
    {

        $this->sendMethod = "POST";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/invoices";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateInvoice($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/invoices/" . $datos["invoice_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function listInvoicesTemplates()
    {

        $this->sendMethod = "GET";
        $datos = array();
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/invoices/templates";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************chartofaccounts******************************************************/
    public function listChartofAccounts($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("page", "per_page");
        $url = $this->url . "/chartofaccounts";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }
    public function getChartofAccounts($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/chartofaccounts/" . $datos["account_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************CreditNotes******************************************************/
    public function listCreditNotes($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_") {
                $notMandatory[] = $key;
            }
        }
        $url = $this->url . "/creditnotes";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getCreditNote($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/creditnotes/" . $datos["creditnote_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateCreditNote($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/creditnotes/" . $datos["creditnote_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************VendorCredits******************************************************/
    public function listVendorCredits($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/vendorcredits";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getVendorCredit($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/vendorcredits/" . $datos["vendor_credit_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateVendorCredit($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/vendorcredits/" . $datos["vendor_credit_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************Expenses******************************************************/
    public function listExpenses($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/expenses";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function getExpense($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array();
        $url = $this->url . "/expenses/" . $datos["expense_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateExpense($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/expenses/" . $datos["expense_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************customerPayments******************************************************/
    public function listCustomerPayments($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("customer_id", "search_text", "sort_column", "filter_by", "payment_mode", "notes", "amount", "date", "reference_number", "customer_name", "page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/customerpayments";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateCustomerPayment($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/customerpayments/" . $datos["payment_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************vendorPayments******************************************************/
    public function listVendorPayments($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("customer_id", "search_text", "sort_column", "filter_by", "payment_mode", "notes", "amount", "date", "reference_number", "customer_name", "page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/vendorpayments";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    public function updateVendorPayment($datos)
    {

        $this->sendMethod = "PUT";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("JSONString", "organization_id");
        $notMandatory = array("");
        $url = $this->url . "/vendorpayments/" . $datos["payment_id"];
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }

    /******************************************************bankAccounts******************************************************/
    public function listBankAccounts($datos)
    {

        $this->sendMethod = "GET";
        $datos["organization_id"] = $this->idOrganizacion;
        $mandatory = array("organization_id");
        $notMandatory = array("customer_id", "search_text", "sort_column", "filter_by", "payment_mode", "notes", "amount", "date", "reference_number", "customer_name", "page", "per_page");
        foreach ($datos as $key => $value) {
            if (substr($key, 0, 3) === "cf_")
                $notMandatory[] = $key;
        }
        $url = $this->url . "/bankaccounts";
        return $this->callCurl($datos, $mandatory, $notMandatory, $url, "json");
    }
}
