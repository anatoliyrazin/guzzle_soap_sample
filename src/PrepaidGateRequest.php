<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class PrepaidGateRequest {

    protected $apiClient;
    protected $guzzleAsync;

    protected $httpProtocol;
    protected $httpHost;
    protected $httpPath;

    protected $httpURL;
    protected $httpBaseURL;

    protected $xmlRequestAction;
    protected $xmlns;
    protected $xmlRequestCreds = array();
    protected $xmlRequestData;
    protected $xmlBody;

    protected $xmlResponse;
    protected $requestResult;

    public function __construct($config = null) {
        $this->resetConfig($config);
    }

    public function resetConfig($config) {
        if(isset($config['apiClient'])) $this->apiClient = $config['apiClient'];
        if(isset($config['guzzleAsync'])) $this->guzzleAsync = $config['guzzleAsync'];

        if(isset($config['httpProtocol'])) $this->httpProtocol = $config['httpProtocol'];
        if(isset($config['httpHost'])) $this->httpHost = $config['httpHost'];
        if(isset($config['httpPath'])) $this->httpPath = $config['httpPath'];

        $this->httpURL = $this->httpProtocol . "://" . $this->httpHost . $this->httpPath;
        $this->httpBaseURL = $this->httpProtocol . "://" . $this->httpHost . "/";

        if(isset($config['xmlns'])) $this->xmlns = $config['xmlns'];

        if(isset($config['creds'])) {
            $this->xmlRequestCreds['user'] = $config['creds']['user'];
            $this->xmlRequestCreds['pass'] = $config['creds']['pass'];
        }
    }

    public function defineAction($action) {
        $this->xmlRequestAction = $action;
    }

    public function defineData($data) {
        $this->xmlRequestData = "";
        foreach($data as $key => $val) {
            $this->xmlRequestData .= "<$key>$val</$key>";
        }
    }

    public function buildSOAPBody() {
        $this->xmlBody = "<" . $this->xmlRequestAction . " xmlns=\"" . $this->xmlns . "\">
                             <Username>" . $this->xmlRequestCreds['user'] . "</Username>
                             <Password>" . $this->xmlRequestCreds['pass'] . "</Password>
                             " . $this->xmlRequestData . "
                          </" . $this->xmlRequestAction . ">";
    }

    public function buildSOAPPostString() {
        $this->xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
                          <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                              <soap12:Body>
                              ' . $this->xmlBody . '
                              </soap12:Body>
                          </soap12:Envelope>';

        // temprorary solution, better to use reg exp with preg_replace
        $this->xmlPostString = str_replace("&", "&amp;", $this->xmlPostString);
        $this->xmlPostString = str_replace("&amp;amp;", "&amp;", $this->xmlPostString);
    }

    public function sendSOAPRequestCurl() {
        $headers = array("POST " . $this->httpPath . " HTTP/1.1",
                         "Host: " . $this->httpHost,
                         "Content-Type: application/soap+xml; charset=utf-8",
                         "Content-Length: " . strlen($this->xmlPostString));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $this->httpURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_USERPWD, $AuthID.":");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->xmlPostString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->xmlResponse = curl_exec($ch);
        curl_close($ch);
    }

    public function sendSOAPRequestGuzzle() {
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->httpBaseURL,
            // You can set any number of default request options.
            'timeout'  => 10.0,
        ]);

        $request = new Request('POST', $this->httpPath, ['Host' => $this->httpHost,
                                                         'Content-Type' => 'application/soap+xml; charset=utf-8',
                                                         'Content-Length' => strlen($this->xmlPostString)]);

        if(isset($this->guzzleAsync) && $this->guzzleAsync) {
            $promise = $client->sendAsync($request, ['body' => $this->xmlPostString]);
            $promise->then(function($response) {
                $this->xmlResponse = $response->getBody();
                consoleLog('async call responded');
            });

            consoleLog('async call waiting...');
            $promise->wait();
            consoleLog('async call complete');
        } else {
            $response = $client->send($request, ['body' => $this->xmlPostString]);

            // var_dump($response);
            $this->xmlResponse = $response->getBody();
        }
    }

    public function sendSOAPRequest() {
        if($this->apiClient == 'curl') {
            $this->sendSOAPRequestCurl();
        } elseif($this->apiClient == 'guzzle') {
            $this->sendSOAPRequestGuzzle();
        }
    }

    public function parseSOAPResponse() {
        $response = $this->xmlResponse;

        $response2 = str_replace("<soap:Body>","",$response);
        $response2 = str_replace("</soap:Body>","",$response2);

        $xmlParser = simplexml_load_string($response2);
        $xmlResponse = $this->xmlRequestAction . 'Response';
        $xmlResult = $this->xmlRequestAction . 'Result';

        $this->requestResult = $xmlParser->$xmlResponse->$xmlResult;
    }

    public function processRequest() {
        $this->buildSOAPBody();
        $this->buildSOAPPostString();

        $this->sendSOAPRequest();
        $this->parseSOAPResponse();
    }

    public function getRequestResult() {
        return $this->requestResult;
    }
}

?>
