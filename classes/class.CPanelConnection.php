<?php

class CPanelConnection {
    
    protected $username;
    protected $password;
    protected $url;
    protected $curl;
    
    protected $curlError = null;
    protected $httpCode = null;
     
    public function __construct($params)
    {
       $this->username = $params['serverusername'];
       $this->password = $params['serverpassword'];
       $this->url = $this->buildBasicURL($params);
       $this->prepare();
    }
    
    public function buildBasicURL($params)
    {
        return $params['serverhttpprefix'] . '://' . (isset($params['serverip']) ? $params['serverip'] : $params['serverhostname']) . ':2087';
    }
    
    public function buildURL($url,$data = null)
    {
        if(is_object($data))
        {
            $data = $this->getVars($data);
        }
        
        return $url . (empty($data) ? '' : '?' . http_build_query($data));
    }
    
    public function getVars($object)
    {
        return get_object_vars($object);
    }
    
    public function prepare()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);       
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);       
        curl_setopt($this->curl, CURLOPT_HEADER,0);               
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,1);       
        $header[0] = "Authorization: Basic " . base64_encode($this->username . ":" . $this->password) . "\n\r";
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    }

    public function setUrl($action)
    {
       curl_setopt($this->curl, CURLOPT_URL, $this->url . "/json-api/" . $action);
       return $this;
    }
    
   public function execute()
   {
       $response = curl_exec($this->curl);
       self::checkCurl($this->curl);
       self::checkCode($this->curl);
       self::checkJson($response);

       return self::checkCPanelError($this->parse($response));
   }
   
   public static function checkCurl($curl)
   {
      if(!$curl)
      {
        throw new Exception(curl_error($curl));
      }
   }

   public static function checkCode($curl)
   {
      $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
      if($code != '200')
      {
        throw new Exception('Error occured!'); 
      }
   }
   
   public static function checkJson($json)
   {
      json_decode($json);
       if(json_last_error() != 0)
       {
           throw new Exception('Error occured!: '. json_last_error_msg());
       }     
   }
   
   public static function checkCPanelError($response)
   {
   	  if(isset($response->metadata->result) && $response->metadata->result == 0)
      {	
          throw new Exception($response->metadata->reason);	  
      }
      if($response->cpanelresult->error && $response->cpanelresult->data->result == 0)
      {
          throw new Exception($response->cpanelresult->error);
      }
      if(isset($response->cpanelresult->error) && $response->cpanelresult->result == 0)
      { 	
          throw new Exception($response->cpanelresult->data->reason);
      }

      
      return $response;
   }
        
    public function parse($result)
    {
      $response = json_decode($result);
      return $response;
    }
   
}