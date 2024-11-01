<?php 
if(in_array('ios', $enabled)) { 
		
		$keys = get_option('wpsd_keys');
					
		$mtype = 'green';
		
		$label = __('OK', 'wpsd');
		
		if(!$keys)  { 
			
			$mtype = 'red';
			
			$label = __('No', 'wpsd');
		}
		
		
		echo '<li class="wpsd_toggle wpsd_toggle_ios"><span class="metric_label">';
			
		_e('iOS Stats Connected', 'wpsd');
			
		echo '</span> <span class="metric_'.$mtype.'">' . $label . '</span>';
			
		echo '<ul class="wpsd_toggle_contents wpsd_toggle_ios_contents">';
			
		if('red' == $mtype) { 	
			printf('<li class="highlight">%s  %s <br/> %s <br/> <strong>%s</strong> </li>', 
			__('Buy the ipad/iphone stats dashboard app<br/><br/> for just <strong>1 USD</strong> <br/><br/>from', 'wpsd'), 
			' <a href="http://wpsdapp.com" target="_blank">http://wpsdapp.com</a><br/>', 
			'<a href="http://wpsdapp.com" target="_blank"><img src="' . WPSD_PLUGIN_URL . '/resources/images/ipad.png" alt="wpsd ios app" /></a> ',
			__('From now on you can view your social media <br/>and WordPress stats on your iOS Device', 'wpsd'));		
		} 
		else {
			printf('<li class="highlight">%s</li>', __('You supported us by buying the <a href="http://wpsdapp.com" target="_blank">WPSDApp</a>. We salute you! <br/><a href="http://twitter.com/wpsdapp" target="_blank">Follow us on Twitter</a><br/><a href="http://www.facebook.com/wpsdapp" target="_blank">Like us on Facebook</a>.', 'wpsd'));	
		}
		echo '</ul>';	
		
		echo '</li>';					
}
?>  