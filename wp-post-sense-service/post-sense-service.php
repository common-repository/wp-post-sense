<?php
require_once(dirname(__FILE__) . '/../../../wp-config.php');

class PostSenseService
{
	var $plugin_name="WP Post Sense Service";
	var $plugin_version="0.1";
	var $plugin_uri="http://www.mashget.com";
	var $cachedir;
	var $wcache;
	var $maxitems=10;
	var $client;
	
	function PostSenseService()
	{
		if(!class_exists("WCache"))
		{
			require_once(dirname(__FILE__) . "/inc/wcache.class.php");
		}
		$this->cachedir=dirname(__FILE__)."/pss-cache/";
		$this->wcache = new WCache($this->cachedir);
		
		$args= strtoupper($_SERVER['REQUEST_METHOD'])=="POST" ? $_POST : $_GET;		
		if(isset($args['cmd']))
		{
			$res=$this->do_cmd($args);
			if($res)
			{			
				//var_dump(json_decode($res));
				echo $res;
			}
		}
		else if(isset($args['v']))
		{
			echo $this->plugin_name."/".$this->plugin_version;
		}
		else if(isset($args['clear']))
		{
			$this->clean_expire(3600);
		}
	}
	
	function do_cmd($args)
	{
		if(!isset($args['format']) || !isset($args['client']))return null;
		
		$this->client=$args['client'];
		unset($args['client']);
		
		//$key=serialize($args);
		//while($this->wcache->save("wps_serv_$key", 3600,  array(&$res)))
		{
			$res=null;
			switch ($args['cmd'])
			{
				case "getposts":
					$res=PostSenseService::get_related_posts($args);
					if(sizeof($res))
					{
						foreach ($res as $post)
						{
							$post->link.="?psc=".urlencode($this->client);
						}
					}
					break;
					
				case "getversion":
					$res=$this->plugin_version;
					break;
					
				case "getcfg":				
					$res=array(
						"service_url" => get_option('home') . "/wp-content/plugins/wp-post-sense/post-sense-service.php",
						"data_format" => array("json", "php"),
						"cache_time" => 3600,
					);
									
					break;
			}	
			
			$res=PostSenseService::encode_output($res, $args['format']);
		}		
		return $res;
	}
	
	function get_related_posts($args)
	{
		$keywords=$args['keywords'];
		if(isset($args['limit']))$limit=intval($args['limit']);
		if(!$limit)$limit=5;
		if($limit > $this->maxitems)$limit=$this->maxitems;	
		if(!$keywords)return null;
		$terms = explode(',', trim($keywords, " \n\t\r\0\x0B,"));			
		//var_dump($terms);
		$term_ids=array();
		$taxm_ids=array();
		$term_slugs=array();
		foreach ($terms as $term)
		{
			$term=trim($term);
			$slug = sanitize_title($term);
			$id = is_term($slug, 'post_tag');
			if($id) 
			{
				$term_ids[]=$id['term_id'];
				$taxm_ids[]=$id['term_taxonomy_id'];
				$term_slugs[]=$slug;
			}
			else
			{
				$this->logUnfound($term);
			}
		}
		if(sizeof($taxm_ids)==0)return null;
		
		sort($term_slugs);	
		$key=join(",", $term_slugs);
		
		$olimit=$limit;
		$limit=$this->maxitems;
		
		while($this->wcache->save("wps_serv_getrelated_{$key}_{$limit}", 3600,  array(&$related_posts)))
		{
			global $wpdb;
			
			//For Common
			/*
			$taxmlist = join(", ", $taxm_ids);
			$q = "SELECT p.ID, count(t_r.object_id) as cnt FROM $wpdb->term_relationships t_r, $wpdb->posts p WHERE t_r.term_taxonomy_id IN ($taxmlist) AND t_r.object_id  = p.ID AND p.post_status = 'publish' GROUP BY t_r.object_id ORDER BY cnt DESC, p.post_date DESC LIMIT ".intval(floor($limit*1.5));
			$rpids=$wpdb->get_results($q);*/
			
			//For MashGet Only
			$termlist=join(", ", $term_ids);
			$q = "SELECT post_id AS ID, count( post_id ) AS cnt FROM wp_post_tag_map WHERE term_id in ($termlist) GROUP BY post_id ORDER BY cnt DESC, ID DESC LIMIT ".intval(floor($limit*1.5));
			$rpids=$wpdb->get_results($q);
			
			$pidArr=array();
			foreach ($rpids as $rpid)
			{
				$pidArr[]=$rpid->ID;
			}
			if(sizeof($pidArr))
			{
				$q="SELECT p.ID, p.post_title FROM $wpdb->posts p WHERE p.ID in (".join(", ",$pidArr).") ORDER BY p.post_date DESC limit $limit";
				$related_posts = $wpdb->get_results($q);
				foreach ($related_posts as $post)
				{
					$post->link = get_permalink($post->ID);
					unset($post->ID);
				}
			}
		}
		
		if($related_posts && $limit!=$olimit)
		{
			$related_posts=array_slice($related_posts, 0, $olimit);
		}
		
		return $related_posts;
	}
	
	function encode_output($res, $format)
	{
		if(!$res) return null;

		switch ($format)
		{
			case "php":
				$res = serialize($res);
				break;
				
			case "json":
				if(function_exists("json_encode"))
				{
					$res = json_encode($res);
				}
				else
				{
					$res=null;
				}
				break;
			
			default:
				$res=null;
				break;
		}
		
		return $res;
	}
	
	function clean_expire($timespan)
	{
		$dir=$this->cachedir;
		if (!is_dir($dir))return 0;
		$fs = @scandir($dir);
		array_shift($fs);
		array_shift($fs);		
		$n=0;
		foreach($fs as $f)
		{
			$fn=$dir.$f;
			if (is_file($fn))
			{
				$ts=time()-filemtime($fn);
				if($ts < $timespan)continue;
			 	@unlink($fn);
			 	$n++;
			}
		}
		
	}
	
	function logUnfound($keyword)
	{
		
	}
	
	function filterlink($link)
	{
		$psc=$_GET['psc'];
		return $link."?psc=".urlencode($psc);
	}
}
$ps_service=&new PostSenseService();
//$ps_service->clean_expire(3600);
?>