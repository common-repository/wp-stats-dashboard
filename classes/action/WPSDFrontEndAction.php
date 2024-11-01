<?php
/**
 * WPSDFrontEndAction.
 * @author Dave Ligthart <info@daveligthart.com>
 * @version 1.8
 * @package wp-stats-dashboard
 */
class WPSDFrontEndAction extends WPSDWPPlugin {

	/**
	 * WPSDFrontEndAction function.
	 * 
	 * @access public
	 * @param mixed $plugin_name
	 * @param mixed $plugin_base
	 * @return void
	 */
	function WPSDFrontEndAction($plugin_name, $plugin_base){
		
		$this->plugin_name = $plugin_name;
		
		$this->plugin_base = $plugin_base;

		$this->add_action('wp_head');
		
		$this->add_filter('xmlrpc_methods', 'add_xmlrpc_methods');
		
		if( is_active_widget(false, false, 'wpsdminigraphwidget', true)) {
						
			wp_deregister_script('swfobject');

			wp_register_script('swfobject',WPSD_PLUGIN_URL . '/resources/js/swfobject.js', array(), '2.2'); 
	
			wp_enqueue_script('swfobject');		
		}
		
		if ( is_active_widget(false, false, 'wpsdmetricswidget', true) ) {
			
			wp_enqueue_script('jquery');		
		}	
		
		$this->add_action('init', 'wp_init');
		
		$this->add_action('wpsd_cron_hook', 'runCron');
	}
	
	/**
	 * init function.
	 * 
	 * @access public
	 * @return void
	 */	
	function wp_init() {
	//	$this->runCron(); // DEBUG.	
		$this->scheduleCron();
	}
	
	/**
	 * wpsd_create_nonce function.
	 * 
	 * @access public
	 * @param mixed $action
	 * @return void
	 */
	function wpsd_create_nonce($action) {
		
		$nonce_life = 86400;
		
		return substr(wp_hash(ceil(time() / ( $nonce_life / 2 )) . $action, 'nonce'), -12, 10);
	}
	
	/**
	 * wp_head function.
	 * 
	 * @access public
	 * @return void
	 */
	function wp_head(){
			
		if ( is_active_widget(false, false, 'wpsdmetricswidget', true) ) {
			
			$nonce = $this->wpsd_create_nonce('wpsd-metrics-nonce');
		
			$this->render('header', array('version' => WPSD_VERSION, 'nonce' => $nonce));
		}	
		
		if( is_active_widget(false, false, 'wpsdminigraphwidget', true)) {
		
			$nonce = $this->wpsd_create_nonce('wpsd-mini-graph-nonce');
		
			$this->render('header_graph', array('version' => WPSD_VERSION, 'nonce' => $nonce));
		}
	}
	
	/**
	 * wp_footer function.
	 * 
	 * @access public
	 * @return void
	 */
	function wp_footer() {
	
		$this->render('footer');
	}
	
	/**
	 * add_xmlrpc_methods function.
	 * 
	 * @access public
	 * @param array $methods. (default: array())
	 * @return void
	 */
	function add_xmlrpc_methods($methods = array() ) {
		
		$methods['wpsd.getStats'] = array(&$this, 'rpc_get_stats');
				
		$methods['wpsd.getMetrics'] = array(&$this, 'rpc_get_metrics');
		
		$methods['wpsd.getKey'] = array(&$this, 'rpc_get_key');
				
		$methods['wpsd.getStatsByDate'] = array(&$this, 'rpc_get_stats_by_date');
		
		$methods['wpsd.getStatsByDateRange'] = array(&$this, 'rpc_get_stats_by_date_range');
		
		$methods['wpsd.getStatsByYearAndMonth'] = array(&$this, 'rpc_get_stats_by_year_and_month');
		
		$methods['wpsd.clearCache'] = array(&$this, 'rpc_clear_cache');
		
		$methods['wpsd.getVersion'] = array(&$this, 'rpc_get_version');
		
		return $methods;
	}
	
	/**
	 * rpc_get_stats function.
	 * 
	 * @access public
	 * @param mixed $key to verify
	 * @param mixed $trend_type
	 * @return void
	 */
	function rpc_get_stats($args) {
		
		if(null != $args) {
			
			$key = $args['key'];
			
			$type = $args['type'];
			
			if(!$this->verifyKey($key)) return -1;
			
			$stats_data = array();
			
			$cached = $this->readCache('get_stats');
			
			if(false !== $cached && !empty($cached)) {
				
				return $cached;		
			}			
			
			switch($type) {
				
				// Hits.
				case 1: 
					
					$stats_data = $this->updateViewStats();
				
				break;		
			}	
			
			return $stats_data;
		}
				
		return -1;
	}
	/**
	 * rpc_get_stats_by_date function.
	 * 
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	function rpc_get_stats_by_date($args) {
		
		if(null != $args) {
			
			$key = $args['key'];
			
			if(!$this->verifyKey($key)) return -1;
			
			$type = $args['type'];
			
			$date = date('Y-m-d', strtotime($args['date']));
						
			$cached = $this->readCache('single_stats');
			
			$types = $this->getStatsTypes();
			
			if(false !== $cached && !empty($cached)) {
			
				if(array_key_exists($date, $cached[$types[$type]])) {
					
					return $cached[$types[$type]];
				}
			}
			
			$stats_data = array();
			
			$remote = new WPSDStatsRemoting(get_option('wpsd_blog_id')); 
									
			switch($type) {
				
				// Hits.
				case 1:  
					$stats_data = $remote->getViewsByDate($date);
				break;
				// Posts.
				case 2: 
					$stats_data = $remote->getPostsByDate($date);
				break;
				// Referrals.
				case 3: 
					$stats_data = $remote->getReferrersByDate($date);				
				break;
				// Search terms.
				case 4: 
					$stats_data = $remote->getSearchEngineTermsByDate($date);
				break;
				// Clicks.
				case 5:	
					$stats_data = $remote->getClicksByDate($date);
				break;				
			}	
			
			$this->deleteCache('single_stats');
			
			$data[$types[$type]][$date] = $stats_data[$date];
			
			$this->writeCache('single_stats', $data);
			
			return $stats_data;
		}
		
		return -1;

	}
	
	/**
	 * rpc_get_stats_by_date_range function.
	 * 
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	function rpc_get_stats_by_date_range($args, $preload = false) {
	
		if(null == $args) return -1;
				
		if(!$this->verifyKey($args['key'])) return -1;
		
		set_time_limit (180);
		
		$type = $args['type'];
		
		if($type < 1) return -1;
			
		$fromDate = $args['from_date'];
		
		$toDate = $args['to_date'];
					
		$stats_data = array();
		
		$day = strtotime($fromDate);
		
		$end = strtotime($toDate);
		
		$this->updateDateRangeCache($type, $fromDate, $toDate);
		
		$cached = $this->readCache("type_{$type}_{$fromDate}_{$toDate}");
		
		$types = $this->getStatsTypes();
						
		if(false === $cached || $preload) { 
	
			while($day < $end) {
     			     	
     			$day = strtotime('+1 day', $day);
     			
     			$temp = $this->getStatsByType($type, $day);
     			
     			if(null != $temp)
     				$stats_data[$types[$type]][] = $temp;
			}
			
			$this->writeCache("type_{$type}_{$fromDate}_{$toDate}", $stats_data[$types[$type]]);		
	
		} 
		else { 	
			
			$stats_data[$types[$type]] = $cached;
		}
		
		return $stats_data;
	}
			
	/**
	 * rpc_get_metrics function.
	 * 
	 * @access public
	 * @param mixed $args
	 * @return array
	 */
	function rpc_get_metrics($args) {
		
		if(null == $args) return -1;
		
		$key = $args['key'];
			
		if(!$this->verifyKey($key)) return -1;
				
		$dao = new WPSDTrendsDao();
		
		$factory = new WPSDStatsFactory();
		
		$types = array(
			'alexa' => 
				array(
					'title' => 'Alexa', 
					'description' => __('The traffic rank is based on three months of aggregated historical traffic data from millions 
					of Alexa Toolbar users and data obtained from other, diverse traffic data sources, and is a combined measure of 
					page views and users (reach).', 'wpsd'), 
					'id' => $factory->alexa),
			'pagerank' =>  
				array(
					'title' => 'Pagerank', 
					'description' => __('PageRank is a numeric value that represents how important a page is on the web. 
					Google figures that when one page links to another page, it is effectively casting a vote for the other page. 
					The more votes that are cast for a page, the more important the page must be.', 'wpsd'), 
					'id' => $factory->pagerank),
			'engagement' =>  
				array(
					'title' => __('Engagement', 'wpsd'), 
					'description' => __('The number of comments added to your blog.', 'wpsd'), 
					'id' => $factory->engagement),
			'bing'	=>  
				array(
					'title' => 'Bing', 
					'description' => __('Number of search results in the Microsoft Bing search engine.', 'wpsd'), 
					'id' => $factory->bing),
			'google' =>  
				array(
					'title' => 'Google Backlinks', 
					'description' => __('The number of google backlinks. A backlink is any link received by a web page from another web page. The number of backlinks is one indication of the popularity or importance of that website or page in search engines.', 'wpsd'), 
					'id' => $factory->google_backlinks),
			'twitter' =>  
				array(
					'title' => 'Twitter Followers', 
					'description' => __('The number of Twitter followers. Twitter is a real-time information network that connects you to the latest stories, ideas, opinions and news about what you find interesting', 'wpsd'), 
					'id' => $factory->twitter_followers),
			'facebook' =>  
				array(
					'title' => 'Facebook Likes', 
					'description' => __('The number of Facebook likes. Facebook is a social networking service.', 'wpsd'), 
					'id' => $factory->facebook),
			'linkedin' =>  
				array(
					'title' => 'LinkedIn Connections', 
					'description' =>  __('The number of LinkedIn connections', 'wpsd'), 
					'id' => $factory->linkedin_connections),
			'klout'	=>  
				array(
					'title' => 'Klout Rank', 
					'description' => __('The Klout Score measures influence based on your ability to drive action. 
					Every time you create content or engage you influence others.', 'wpsd'), 
					'id' => $factory->klout),
			'youtube' =>
				array(
					'title' => 'Youtube Views', 
					'description' => __('The number of video views on Youtube. YouTube allows billions of people to discover, watch and share originally-created videos.', 'wpsd'), 
					'id' => $factory->youtube),
			'pinterest' =>
				array(
					'title' => 'Pinterest Followers', 
					'description' => __('The number followers on Pinterest. Pinterest is a virtual pinboard. Pinterest allows you to organize and share all the beautiful things you find on the web. You can browse pinboards created by other people to discover new things and get inspiration from people who share your interests.', 'wpsd'), 
					'id' => $factory->pinterest_followers),
			'googleplus' =>
				array(
					'title' => 'Google Plus Followers', 
					'description' => __('The number followers on Google Plus. Google Plus aims to make sharing on the web more like sharing in real life. ', 'wpsd'), 
					'id' => $factory->googleplus)
		);
				
		$ret = array();
		
		foreach($types as $type => $arr) {
			
			$title = $arr['title'];
			$desc = $arr['description'];
			$id = $arr['id'];
			
			$ret[$type] = $dao->getStats($id);
			$ret[$type][] = $title;
			$ret[$type][] = $desc;
		}
		
		//$ret['total_views'] = get_option('wpsd_alltime_hits');
		
		//$this->writeCache('metrics', $ret);
		
		return $ret;
	}
	
	/**
	 * rpc_get_stats_by_year_and_month function.
	 * 
	 * @access public
	 * @param mixed $args
	 * @return array
	 */
	function rpc_get_stats_by_year_and_month($args) {
		
		if(null == $args) return -1;
		
		$key = $args['key'];
			
		if(!$this->verifyKey($key)) return -1;
		
		$cached = $this->readCache('yearmonth');
		
		if(false !== $cached) {
			
			return $cached;
		}
		
		$blogId = get_option('wpsd_blog_id');
		
		$remote = new WPSDStatsRemoting($blogId); 
		
		$stats_data = $remote->getViewsByYearAndMonth();
		
		$this->writeCache('yearmonth', $stats_data);
		
		return $stats_data;
	}
	
	/**
	 * rpc_get_key function.
	 * 
	 * @access public
	 * @param mixed $args
	 * @return string key
	 */
	function rpc_get_key($args) {
		
		if(null != $args) {
			
			$username = $args['username'];
			
			$password = $args['password'];
			
			return $this->createKey($username, $password);	
		
		}
		
		return -1;		
	}
	
	/**
	 * rpc_clear_cache function.
	 * 
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	function rpc_clear_cache($args) {
	
		if(null == $args) return -1;
				
		if(!$this->verifyKey($args['key'])) return -1;
	
		$ranges = $this->readCache('date_ranges');
		
		$types = $this->getStatsTypes();
		
		foreach($types as $key => $value) {
			
			foreach($ranges as $r) {
				
				if(null != $r) { 
					
					foreach($r as $range) {
					
						if(null != $range) { 
						
							$from = $range[0];
						
							$to = $range[1];
						
							$this->deleteCache("type_{$key}_{$from}_{$to}");
						}
					}
				}
			}			
		}
		
		$this->deleteCache('date_ranges');
		
		$this->deleteCache('single_stats');	
		
		$this->deleteCache('metrics');	
		
		$this->deleteCache('yearmonth');		
		
		$this->deleteCache('get_stats');
	}
	
	/**
	 * rpc_get_version function.
	 * 
	 * @access public
	 * @return string
	 */
	function rpc_get_version() {
		
		if(defined('WPSD_RPC_VERSION')) {
			
			return WPSD_RPC_VERSION;
		}
		
		return '-1';
	}
	
	/**
	 * createKey function.
	 * 
	 * @access public
	 * @param mixed $username
	 * @param mixed $password
	 * @return string key
	 */
	function createKey($username, $password) {
		
		$temp = md5($username . $password);
		
		if(user_pass_ok($username, $password)) {
			
			$u = get_userdatabylogin($username);
			
			$user = new WP_User($u->ID);	
						
			if($this->getUserAccess($user)) { 			
			
				$this->updateKeys($temp);
			} 
		} 
		else {
		
			return -1;
		}
			
		if($this->verifyKey($temp)) {
			
			return $temp;	
		}
		
		return -1;
	}
	
	/**
	 * verifyKey function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @return boolean
	 */
	function verifyKey($key) {
		
		if('' == trim($key)) return false;
		
		return array_key_exists($key, $this->getKeys());
	}
	
	/**
	 * getStatsTypes function.
	 * 
	 * @access public
	 * @return array
	 */
	function getStatsTypes() {
	
		$types = array(1 => 'views', 2 => 'posts' , 3 => 'referrers', 4 => 'search_engine_terms', 5 => 'clicks');
		
		return $types;
	}
	
	/**
	 * writeCache function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @param mixed $data
	 * @param int $expire. (default: 43200) 12 hours
	 * @return void
	 */
	function writeCache($key, $data, $expire = 43200){
			
		if(!empty($key) && function_exists('set_transient')) { 
			
			global $_wp_using_ext_object_cache;
			
			$temp = $_wp_using_ext_object_cache;
			
			$_wp_using_ext_object_cache = false;
			
			set_transient( 'wpsd_stats_cache_' . $key, $data, $expire);
			
			 $_wp_using_ext_object_cache = $temp;
 		}
	}
	
	/**
	 * readCache function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @return string | false
	 */
	function readCache($key) {
		
		if(!empty($key) && function_exists('get_transient')) { 
			
			global $_wp_using_ext_object_cache;
			
			$temp =  $_wp_using_ext_object_cache;
			
			$_wp_using_ext_object_cache = false;
		
			$cache = get_transient( 'wpsd_stats_cache_' . $key);
			
			$_wp_using_ext_object_cache = $temp;
						
			return $cache;
		}
		return false;
	}
	
	/**
	 * deleteCache function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	function deleteCache($key) {
	
		if(!empty($key) && function_exists('delete_transient')) { 
			
			global $_wp_using_ext_object_cache;
			
			$temp =  $_wp_using_ext_object_cache;
			
			$_wp_using_ext_object_cache = false;

			delete_transient( 'wpsd_stats_cache_' . $key);
			
			$_wp_using_ext_object_cache = $temp;
		}
	}
	
	/**
	 * preloadRpcStats function.
	 * 
	 * @access public
	 * @return void
	 */
	function preloadRpcStats() {
		
		set_time_limit(900);
		 
		$key = get_option('wpsd_rpc_key');
		
		$this->rpc_clear_cache(array('key' => $key));
		
		for($i = 1; $i <= 5; $i++) { 
		
			$this->updateDateRangeCache($i, date('Y-m-d', strtotime('-31 days')), date('Y-m-d', strtotime('now'))); // for full updates.
			
			$this->updateDateRangeCache($i,  date('Y-m-d', strtotime('-2 days')),  date('Y-m-d', strtotime('now'))); // for incremental updates.
		}
						
		$this->updateStatsByDateRange($key, $this->readCache('date_ranges'));
	}
	
	/**
	 * updateStatsByDateRange function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @param mixed $ranges
	 * @return boolean
	 */
	function updateStatsByDateRange($key, $ranges) {
		
		if(null != $ranges && is_array($ranges)) { 
			
			foreach($ranges as $type => $range) {
				
				if(null != $range) { 
					
					foreach($range as $r) {
						
						if(null != $r) {					
							
							if(!empty($r[0]) && !empty($r[1])) {
								
								$args['from_date'] = $r[0];
								
								$args['to_date'] = $r[1];
								
								$args['type'] = $type;
								
								$args['key'] = $key;
																
								$this->rpc_get_stats_by_date_range($args, true); // reload cache.						
							}
						}
					}
				}
			} 	
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * runCron function.
	 * 
	 * @access public
	 * @return void
	 */
	function runCron() {
		
		$this->updateStats();
		
		$this->preloadRpcStats();
	}
	
	/**
	 * updateStats function.
	 * 
	 * @access public
	 * @return void
	 */
	function updateStats() {
		
		global $wpsd_update_cache; 
		
		$wpsd_update_cache = true;
			
		$dao = new WPSDTrendsDao();

		$factory = new WPSDStatsFactory();

		for($i=1; $i<=$factory->last; $i++) {  	// Update stats.

			$dao->update($i, $factory->getStats($i));
		}
	}
	
	/**
	 * updateViewStats function.
	 * 
	 * @access public
	 * @return integer
	 */
	function updateViewStats() {
		
		$stats_data = wpsd_get_chart_xy(); 
						
		$this->writeCache('get_stats', $stats_data, 300);
		
		return $stats_data;
	}
	
	/**
	 * updateDateRangeCache function.
	 * 
	 * @access public
	 * @param mixed $fromDate
	 * @param mixed $toDate
	 * @return array
	 */
	function updateDateRangeCache($type, $fromDate, $toDate) {
		
		$ranges = $this->readCache('date_ranges');
		
		$ranges[$type][$fromDate . '_' . $toDate] = array($fromDate, $toDate);
		
		$this->writeCache('date_ranges', $ranges);
		
		return $ranges;
	}
	
	/**
	 * scheduleCron function.
	 * 
	 * @access public
	 * @return boolean
	 */
	function scheduleCron() {
		// Activate cron
		if(!wp_next_scheduled( 'wpsd_cron_hook')) {					
			wp_schedule_event(time(), 'hourly', 'wpsd_cron_hook');
		}
		
		// Clear old hook.
		if(wp_next_scheduled('wpsd_hourly_register_stats')) {
			wp_clear_scheduled_hook( 'wpsd_hourly_register_stats' );
		}
		
		return true;
	}
	/**
	 * updateKeys function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @return boolean
	 */
	function updateKeys($key) {
		
		if('' == $key) return false;
		
		$keys = $this->getKeys();
		
		$keys[$key] = true;
			
		update_option('wpsd_keys', $keys);
			
		return true;
	}
	/**
	 * getKeys function.
	 * 
	 * @access public
	 * @return array
	 */
	function getKeys() {
		
		$keys = get_option('wpsd_keys');
		
		if(null == $keys) $keys = array();
		
		return $keys;	
	}
	
	/**
	 * getUserAccess function.
	 * 
	 * @access public
	 * @param WP_User $user
	 * @return boolean
	 */
	function getUserAccess($user) {
		
		$role_access = false;
	
		$form = new WPSDAdminConfigForm();
		$role_author = $form->getWpsdRoleAuthor();
		$role_editor = $form->getWpsdRoleEditor();
		$role_subscriber = $form->getWpsdRoleSubscriber();
		$role_contributor = $form->getWpsdRoleContributor();
		
		if(null != $user) {
	
			if($user->caps['editor'] && $role_editor) $role_access = true;
			else if($user->caps['author'] && $role_author) $role_access = true;
			else if($user->caps['contributor'] && $role_contributor) $role_access = true;
			else if($user->caps['subscriber'] && $role_subscriber) $role_access = true;
			else if($user->caps['administrator']) $role_access = true;
		}
	
		return $role_access; 	
	}	
	
	/**
	 * getStatsByType function.
	 * 
	 * @access public
	 * @param mixed $type
	 * @param mixed $day epoch s
	 * @return array
	 */
	function getStatsByType($type, $day) {
	
		$remote = new WPSDStatsRemoting(get_option('wpsd_blog_id')); 
		
		$data = array();
		
		switch($type) { 
     				
     		// Views.
     		case 1: 
     			$data = $remote->getViewsByDate(date('Y-m-d', $day));
			break;
						
			// Posts.
			case 2:
				$data = $remote->getPostsByDate(date('Y-m-d', $day));	
			break;
					
			// Referrers.
			case 3:
				$data = $remote->getReferrersByDate(date('Y-m-d', $day));
			break;
					
			// Search terms.
			case 4:
				$data = $remote->getSearchEngineTermsByDate(date('Y-m-d', $day));
			break;
					
			// Clicks.
			case 5:
				$data = $remote->getClicksByDate(date('Y-m-d', $day));
			break;
		}
	
		if($type > 1 && (!$data 
			|| !is_array($data) 
			|| empty($data)  
			|| '' == $data 
			|| null == $data 
			)) {
			
			$temp = array();
			
			$temp[] = array('__none__' => 0);
			
			return array(date('Y-m-d', $day) => $temp);
		}
		
		if(is_array($data) && $type > 1) { 
		
			foreach($data as $k => $v) {

				if(null != $k && (is_array($v) && count($v) == 0)) {

					$data[$k][] = array('__none__' => 0);
				}
			}
		}
		
		if($type == 1 && (!$data || empty($data) || null == $data)) {
						
			return array(date('Y-m-d', $day) => 0);
		}
		
		return $data;
	}
}	
?>