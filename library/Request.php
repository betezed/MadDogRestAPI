<?php

class Request {
    public $url_elements;
    public $verb;
    public $parameters;
    public $db;
 
    public function __construct() {
        $this->db = new DatabaseModel();
        $this->verb = $_SERVER['REQUEST_METHOD'];
        if (isset($_SERVER['PATH_INFO'])) {
            $this->url_elements = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        }
        else
            $this->url_elements = [];
        $this->parseIncomingParams();
        // initialise json as default format
        $this->format = 'json';
        if(isset($this->parameters['format'])) {
            $this->format = $this->parameters['format'];
        }
        return true;
    }
 
    public function parseIncomingParams() {
        $parameters = array();
 
        // first of all, pull the GET vars
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $parameters);
        }
 
        // now how about PUT/POST bodies? These override what we got from GET
        $body = file_get_contents("php://input");
        $content_type = false;
        if(isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];
        }
        if (strpos($content_type, 'application/json') !== false) {
            $body_params = json_decode($body);
            if($body_params) {
                foreach($body_params as $param_name => $param_value) {
                    $parameters[$param_name] = $param_value;
                }
            }
            $this->format = "json";
        } else if (strpos($content_type, 'application/x-www-form-urlencoded') !== false) {
            parse_str($body, $postvars);
            foreach($postvars as $field => $value) {
                $parameters[$field] = $value;
            }
            $this->format = "html";
        }
        $this->parameters = $parameters;
    }
}
