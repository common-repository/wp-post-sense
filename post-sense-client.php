<?php
class PostSenseClient
{
	var $plugin_name="WP Post Sense Client";
	var $plugin_agent="WP-PostSense";
	var $plugin_version="0.1";
	var $plugin_uri="http://www.mashget.com";
	var $default_serv="http://test.mashget.com/wp-content/plugins/wp-post-sense/post-sense-service.php";
	
	var $wps_ops;
	var $cachedir;
	var $wcache;
	
	function PostSenseClient()
	{
		if(!class_exists("WCache"))
		{
			require_once(dirname(__FILE__) . "/inc/wcache.class.php");
		}
		$this->cachedir=dirname(__FILE__)."/psc-cache/";
		$this->wcache = new WCache($this->cachedir);
		
		//$this->wps_ops=get_option("wp-post-sense");
		if(!$this->wps_ops)
		{
			$this->wps_ops=$this->load_default_options();
		}
	}
	
	function load_default_options()
	{
		$wps_df_ops= array (
			"service_url" => $this->default_serv,
			"data_format" => function_exists("json_decode")? "json":"php-serialize",
			"cache_time" => 3600,
		);
		//update_option("wp-post-sense",$wps_df_ops);
		return $wps_df_ops;
	}
	
	function get_related_posts($keywords, $limit)
	{
		$servurl=$this->wps_ops['service_url'];
		$format=$this->wps_ops['data_format'];
		if(!$servurl || !$format)return false;
		if(!is_array($keywords))$keywords=array($keywords);
		$res=$this->get_service_response($servurl, 
			array(
				"cmd"=>"getpost",
				"format"=>$this->wps_ops['data_format'],
				"keywords"=>join(",", $keywords),
				"limit"=>$limit
				),
			$this->wps_ops['cache_time']
		);
		//echo ($res);
		$res=PostSenseClient::decode_res($res,$format);
		var_dump($res);
	}
	
	function get_service_version()
	{
		$servurl=$this->wps_ops['service_url'];
		$format=$this->wps_ops['data_format'];
		if(!$servurl || !$format)return false;
		$res=$this->get_service_response($servurl, 
			array(
				"cmd"=>"getversion",
				"format"=>$format,
				),
			$this->wps_ops['cache_time']
		);
		$res=PostSenseClient::decode_res($res,$format);
		return $res;
	}
	
	function get_service_response($url, $data, $cachetime)
	{
		if(!$cachetime || $cachetime < 0)$cachetime=30;
		$key=$url.serialize($data);
		while($this->wcache->save("wps_client_$key", $cachetime,  array(&$raw_content)))
		{
			$this->add_request_args($data);
			
			$useragent="{$this->plugin_agent}/{$this->plugin_version} ($this->plugin_uri)";		
			if(function_exists("curl_init"))
			{
				$ch  = curl_init( $url );			
				curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);		
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);		
				$raw_content = curl_exec( $ch );	
				if(curl_error($ch))
				{
					$raw_content="";
				}		
				curl_close( $ch );
			}
			else 
			{
				$queryArr=array();
				foreach ($data as $k=>$v)
				{
					$queryArr[]=$k."=".urlencode($v);
				}		
				$url.= strpos($url,"?")===false? "?":"&";
				$url.= join("&", $queryArr);
				ini_set('user_agent',$useragent);
				$raw_content=file_get_contents($url);
			}
		}
		return $raw_content;
	}
	
	function decode_res($res, $format)
	{
		if(!$res) return null;

		switch ($format)
		{
			case "php-serialize":
				$res = unserialize($res);
				break;
				
			case "json":
				if(function_exists("json_decode"))
				{
					$res = json_decode($res);
				}
				else
				{
					$res=null;
				}
				break;
		}
		
		return $res;
	}
	
	function add_request_args(&$data)
	{
		$data['client']="http://www.mashget.com";
	}
}

$ps_client=&new PostSenseClient();
//$ps_client->get_related_posts("barack obama", 6);
$ps_client->get_related_posts("barack obama", 6);
?>
