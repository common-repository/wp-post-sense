<?php

class PostSenseAds
{
	var $plugin_name="Post Sense Ads";
	var $plugin_version="0.1.";
	var $plugin_uri="http://www.mashget.com";
	var $google_ad_client;
	var $google_ad_channel;
	var $psc=null;
	
	function PostSenseAds()
	{
		add_action('widgets_init', array(&$this, 'register_ads_widget'));
		if(isset($_GET['psc']))$this->psc=$_GET['psc'];
		if($this->psc)add_action('wp_footer',  array(&$this, 'ps_ad_footer'));
	}

	function ps_ad_footer()
	{
		{
			if (!defined('WP_CONTENT_URL'))define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
			$pluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));
			echo '<script type="text/javascript" src="'.$pluginpath.'/pss-js/ps.js"></script>';
		}
	}

	function register_ads_widget()
	{
		if (!function_exists( 'register_sidebar_widget' ))return;
		register_sidebar_widget($this->plugin_name, array(&$this, 'generate_widget' ));
	}

	function set_ps_ad()
	{
		if($this->psc)
		{
			
		}
		$this->google_ad_client="pub-0338439680126857";
		$this->google_ad_channel="8906587174";
	}
	function generate_widget($args)
	{
		$this->set_ps_ad();
		extract($args);
		{
			echo "<div id=\"advertblock\">";
			?>

<div class='gdsbox'>
  <script type="text/javascript"><!--
  google_ad_client = "<?php echo $this->google_ad_client; ?>";
  google_ad_channel = "<?php echo $this->google_ad_channel; ?>";
  google_ui_features = "rc:0";
  google_ad_width = 300;
  google_ad_height = 250;
  google_ad_format = "300x250_as";
  google_ad_type = "";
  google_color_border = "ffffff";
  google_color_bg = "";
  google_color_link = "CC0066";
  google_color_text = "";
  google_color_url = "";

  //--></script>
  <script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
</div>
<?php
echo $after_widget;
		}
	}
}

$postSenseAds=&new PostSenseAds();
?>
