<?php
namespace Acl\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Db\Adapter\Adapter;    
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\Client;

class ApiPlugin extends AbstractPlugin{	
	
	public function sendApiData($api_url,$data)
	{
		$client = new Client();
		$adapter = new \Laminas\Http\Client\Adapter\Curl();
		$client->setAdapter($adapter);
		$adapter->setOptions(array(
			'curloptions' => array(
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_SSL_VERIFYHOST => FALSE,
			)
		));
		
		$client->setUri($api_url);
		$client->setMethod('POST');  
	    $client->setParameterPOST($data); 

		$response = $client->send();
		//echo "<pre>";print_r($response);exit;
		$body = $response->getBody(); 
		$records = json_decode($body, true);
		return $records;
	}
}
