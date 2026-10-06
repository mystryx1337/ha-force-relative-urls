<?php
defined( 'ABSPATH' ) or die( 'Nope.' );

class Ajax_Proxy {
	function proxy() {
		$url = $_GET['url'];
		$filename = basename($url);
		
		/*
		$json_cache = [
			'url' => [
				'timestamp' => time()
			]
		]
		*/
		
		$json = json_decode(get_option( 'ha-ajax-proxy', '{}' ));
		
		$path = WP_PLUGIN_DIR."/ha-force-relative-urls/cache/".$filename;
		
		if(!isset($json[$url]) or $json[$url]['timestamp'] + 60*60*24*14 < time()){
		
			// create curl resource
			$ch = curl_init();

			// set url
			curl_setopt($ch, CURLOPT_URL, $url);

			//return the transfer as a string
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			// $output contains the output string
			$output = curl_exec($ch);

			// close curl resource to free up system resources
			curl_close($ch);  
			
			file_put_contents($path, $output);
			
			$json[$url] = [
				'timestamp' => time()
			];
			
			update_option( 'ha-ajax-proxy', json_encode($json) );
		}
				
		
		$cache_url = plugins_url( "cache/" . $filename, __FILE__ );
		wp_redirect( $cache_url );
		exit;
		#echo files_get_contents(WP_PLUGIN_DIR."/ha-force-relative-urls/cache/".$filename);
		wp_die();
		
	}
}

$Ajax_Proxy = new Ajax_Proxy();

add_action( 'wp_ajax_proxy', [ $Ajax_Proxy, 'proxy'] );
add_action( 'wp_ajax_nopriv_proxy', [ $Ajax_Proxy, 'proxy'] );


?>