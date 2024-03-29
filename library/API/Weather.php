<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WeatherAPI
 *
 * @author = "piter";
 */
class API_Weather {
    
    protected static $_wsdl_url;
    protected static $_client;
    protected static $_self;
    protected static $_connection_time;
    protected static $_compression;
    protected static $_empty_result_message = 'Data Not Found';
    
    protected function __construct($options)
    {
        self::$_compression = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        self::$_wsdl_url = $options['url'];
        
        // Obejscie braku opcji timeoutu w kliencie SOAP Zenda
        $context = stream_context_create(array('http' => array('timeout' => $options['timeout'])));
        self::$_client = new Zend_Soap_Client(self::$_wsdl_url, array('compression' => self::$_compression));
        self::$_client->setStreamContext($context);
    }
    
    public static function instance($options)
    {
        try{
            if(is_null(self::$_self))
            {
                self::$_self = new API_Weather($options);
            }
            return self::$_self;
        } catch (Exception $e) {
            throw new API_Weather_Creation_Exception($e->getMessage());
        }
    }
    
    public function requestCitiesByCountry($countryName)
    {
        try {
            $cities = self::$_client->GetCitiesByCountry(array('CountryName' => $countryName));
            $result = self::_processSOAPCitiesResponse($cities);
            return $result;
        } catch (Exception $e){
            throw new API_Weather_Request_Exception($e->getMessage());
        }
    }
    
    public function requestCityWeather($cityName, $countryName)
    {
        try {
            $weather = self::$_client->GetWeather(array('CityName' => $cityName, 'CountryName' => $countryName));
            $result = self::_processSOAPWeatherResponse($weather);
            return $result;
        } catch (Exception $e){
            throw new API_Weather_Request_Exception($e->getMessage());
        }
    }
    
    protected static function _processSOAPCitiesResponse($response)
    {
        $result = null;
        if($response instanceof stdClass)
        {
            $response = $response->GetCitiesByCountryResult;
            $xml = new SimpleXMLElement($response);
            $query = '//NewDataSet/Table';
            $queryResult = $xml->xpath($query);
            
            $count = count($queryResult);
            if($count)
            {
                $result = array();
                foreach($queryResult as $table)
                {
                    $result[] = (string) $table->City;
                }
            }
        }
        return $result;
    }
    
    protected static function _processSOAPWeatherResponse($result)
    {
        if($result instanceof stdClass)
        {
            $result = $result->GetWeatherResult;
            if($result != self::$_empty_result_message)
            {
                $result = mb_convert_encoding($result, 'UTF-16', 'UTF-8');
                $xml = new SimpleXMLElement($result);
                $result = $xml->xpath('//CurrentWeather');
                $result = (array) $result[0];
            }
        }
        return $result;
    }
}

?>
