<?php
/**
 * Force relative URLs Wordpress-Plugin
 * 
 * 
 * @author		Alexander Hacker <info@edv-hacker.de>
 * @license		GPL-3.0
 * @link		https://edv-hacker.de
 * @copyright	2020 Alexander Hacker
 * 
 * @wordpress-plugin
 * Plugin Name:		Force relative URLs
 * Description:		Forces relative URLs
 * Version:			0.1
 * Author:			Alexander Hacker
 * Author URI:		https://edv-hacker.de
 * Text Domain:		force-relative-urls
 * License:			GPL-3.0
 * License URI:		https://www.gnu.org/licenses/gpl-3.0.txt
 */

defined( 'ABSPATH' ) or die( 'Nope.' );

/**
 * Initialize Plugin Update Checker for automatic updates from GitHub repository.
 *
 * @return \YahnisElsts\PluginUpdateChecker\v5\Vcs\PluginUpdateChecker|null
 */
function ha_force_relative_urls_get_updater() {
    static $update_checker = null;
    if ( null !== $update_checker ) {
        return $update_checker;
    }

    $puc_file = plugin_dir_path( __FILE__ ) . 'vendor/plugin-update-checker/plugin-update-checker.php';
    if ( ! file_exists( $puc_file ) ) {
        return null;
    }

    require_once $puc_file;

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/mystryx1337/ha-force-relative-urls/',
        __FILE__,
        'ha-force-relative-urls'
    );

    // Set default branch to main
    $update_checker->setBranch( 'main' );

    // Set GitHub Access Token (if defined/available)
    $token = defined( 'HA_GITHUB_UPDATE_TOKEN' ) ? HA_GITHUB_UPDATE_TOKEN : '';
    if ( empty( $token ) && defined( 'GITHUB_ACCESS_TOKEN' ) ) {
        $token = GITHUB_ACCESS_TOKEN;
    }
    $token = apply_filters( 'ha_github_update_token', $token, 'ha-force-relative-urls' );

    if ( ! empty( $token ) ) {
        $update_checker->setAuthentication( $token );
    }

    return $update_checker;
}
ha_force_relative_urls_get_updater();



function ha_force_relative_urls_replaceScripts() {
	$urls = [];
	$types = [];
	
    $wp_scripts = wp_scripts();
	foreach($wp_scripts->registered as $script){
		//echo substr($script->src,0,30)."\n";
		if(
			( ( substr($script->src,0,4) === 'http' || substr($script->src,0,2) === '//' ) && strpos($script->src, untrailingslashit(site_url())) !== 0 )
			|| substr($script->src,0,2) == '//'
		){
			#echo substr($script->src,0,20)."\n";
			#print_r($script->src); echo "\n";
			$urls[] = $script;
			$types[] = 'js';
		}
	}
    $wp_scripts = wp_styles();
	foreach($wp_scripts->registered as $script){
		if(
			( ( substr($script->src,0,4) === 'http' || substr($script->src,0,2) === '//' ) && strpos($script->src, untrailingslashit(site_url())) !== 0 )
			|| substr($script->src,0,2) == '//'
		){
			$urls[] = $script;
			$types[] = 'css';
		}
	}
	
	#print_r($urls);
	
	foreach($urls as $i => $val){
		$key = $val->handle;
		$url = $val->src;
		$filename = $key.'-'.strtok(basename($url),'?');
		
		/*
		$json_cache = [
			'url' => [
				'timestamp' => time()
			]
		]
		*/
		
		$json = json_decode(get_option( 'ha-ajax-proxy', '{}' ));
		#print_r($json);
		
		$path = WP_PLUGIN_DIR."/ha-force-relative-urls/cache/".$filename;
		
		if(!isset($json->$url) or $json->$url->timestamp + 60*60*24*8 < time()){
			/* gesamter Block: ~1000ms */
			
			//~350ms !
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
			
			//~400ms
			if($types[$i] == 'css'){
				preg_match_all('/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $output, $matches, PREG_PATTERN_ORDER);
				if($matches){
					foreach($matches[3] as $match){ 
						#echo $match."\n";
						$css_name = strtok(basename($match),'?');
						#echo $css_name."\n";
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $match);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$css_output = curl_exec($ch);
						curl_close($ch);
						
						file_put_contents(WP_PLUGIN_DIR."/ha-force-relative-urls/cache/css-".$css_name, $css_output);
						
						$output = str_replace(
							$match,
							"/wp-content/plugins/ha-force-relative-urls/cache/css-".$css_name,
							$output
						);
					}
				}
			}
			
			//~300ms
			file_put_contents($path, $output);
			
			$json->$url = (object) [
				'timestamp' => time()
			];
			
			update_option( 'ha-ajax-proxy', json_encode($json) );
		}
				
		
		#header("Location: " + $path);
		#echo $path;
		
		if($types[$i] == 'css'){
			wp_deregister_style( $key );
			wp_register_style( $key, "/wp-content/plugins/ha-force-relative-urls/cache/".$filename, $val->deps, $val->ver );
		}
		if($types[$i] == 'js'){
			wp_deregister_script( $key );
			wp_register_script( $key, "/wp-content/plugins/ha-force-relative-urls/cache/".$filename, $val->deps, $val->ver );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'ha_force_relative_urls_replaceScripts', 10000 );
#add_action( 'admin_enqueue_scripts', 'ha_force_relative_urls_replaceScripts', 10000 );

if(!function_exists('force_relative_url')){
	function force_relative_url( $url ){
		$url = parse_url ( $url );
		if(@$url['query'] != ""){$url['query'] = "?".$url['query'];}
		if(@$url['fragment'] != ""){$url['fragment'] = "#".$url['fragment'];}
		return $url['path'].@$url['query'].@$url['fragment'];
	}
}
function force_relative_url_filter($content) {
	#if( (is_singular()) && (is_main_query())) ) {
		$url = untrailingslashit( site_url() );
		$content = str_replace(['src="' . $url, 'href="' . $url, 'action="' . $url], ['src="', 'href="', 'action="'], $content);
	#}
	return $content;
}
add_filter('the_content', 'force_relative_url_filter');


function start_wp_head_buffer() {
    ob_start();
}
add_action('wp_head','start_wp_head_buffer',0);
add_action('admin_head','start_wp_head_buffer',0);
add_action('login_head','start_wp_head_buffer',0);
function end_wp_head_buffer() {
    $in = ob_get_clean();

    // here do whatever you want with the header code
	$url = untrailingslashit( site_url() );
	$out = str_replace(
		$url,
		'',
		$in
	);
    echo $out; // output the result unless you want to remove it
}
add_action('wp_head','end_wp_head_buffer', PHP_INT_MAX); //PHP_INT_MAX will ensure this action is called after all other actions that can modify head
add_action('admin_head','end_wp_head_buffer', PHP_INT_MAX);
add_action('login_head','end_wp_head_buffer', PHP_INT_MAX);

add_action( 'wp_enqueue_scripts', 'enqueue_custom_scripts', 1 );
#add_action( 'admin_enqueue_scripts', 'enqueue_custom_admin_scripts', 1 );
function enqueue_custom_scripts(){
	$wp_scripts = wp_scripts();
	
	wp_enqueue_script( 'relativeUrls_JS', force_relative_url(plugins_url( 'ha-force-relative-urls/relativeUrls.js' )), [], null, true );

	#$wp_scripts->base_url = "";
	#$wp_scripts->content_url = "/wp-content";
	#foreach($wp_scripts->registered as $key => $val){
	#	wp_deregister_script($key);
	#	wp_enqueue_script($key, force_relative_url($val->src), $val->deps, $val->ver, false);
	#}
	#print_r($wp_scripts);
}
function enqueue_custom_admin_scripts(){
	enqueue_custom_scripts();
	
	#wp_deregister_script('jquery');
	wp_enqueue_style('jquery_admin_fix_css', '/wp-admin/load-styles.php?c=0&amp;dir=ltr&amp;load%5Bchunk_0%5D=dashicons,admin-bar,common,forms,admin-menu,dashboard,list-tables,edit,revisions,media,themes,about,nav-menus,wp-pointer,widgets&amp;load%5Bchunk_1%5D=,site-icon,l10n,buttons,wp-auth-check,wp-color-picker&amp;ver=5.6.1', [], null, true);
	#wp_enqueue_script('jquery_admin_fix_head_js', '/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=jquery-core,jquery-migrate,utils,zxcvbn-async,jquery-ui-core,jquery-ui-mouse,jquery-ui-resizable,jquery-ui-sortable&amp;ver=5.6.1', [], null, false);
	#wp_enqueue_script('jquery_admin_fix_foot_js', '/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=hoverIntent&amp;ver=5.6.1', [], null, true);
}
?>