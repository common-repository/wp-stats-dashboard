<?php
/**
 * WPSDPinterest.
 * 
 * @author dligthart <info@daveligthart.com>
 * @version 0.1
 * @package wp-stats-dashboard
 */
class WPSDPinterest extends WPSDStats {

	/**
	 * xml
	 * 
	 * @var mixed
	 * @access public
	 */
	var $xml;
	/**
	 * values
	 * 
	 * @var mixed
	 * @access public
	 */
	var $values;
	/**
	 * address
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access public
	 */
	var $address = '';
	/**
	 * un
	 * 
	 * @var mixed
	 * @access public
	 */
	var $un;
	
	/**
	 * WPSDPinterest function.
	 * 
	 * @access public
	 * @return void
	 */
	function WPSDPinterest() {
		
		parent::WPSDStats();
		
		$form = new WPSDAdminConfigForm();
		
		$this->un = trim($form->getWpsdPinterestUn());
		
		if('' != $this->un) {
			
			$this->address = 'http://pinterest.com/' . $this->un . '/';
			
			if($this->isOutdated()) {
				
				$this->xml = $this->fetchDataRemote($this->address);
				
				$this->set();
				
			} else {
				
				$this->set_cached();
			}
		}
	}
	
	/**
	 * 
	 * Is enabled.
	 */
	function isEnabled() {
		
		return ('' != $this->un);
	}
	
	/**
	 * Set data.
	 * @access private
	 */
	function set() {
	
		$this->values['pins'] = $this->get_count('pins', $this->xml);
		
		$this->set_cache('pinterest_pins', $this->values['pins']);
		
		$this->values['boards'] = $this->get_count('boards', $this->xml);
		
		$this->set_cache('pinterest_boards', $this->values['boards']);
		
		$this->values['followers'] = $this->get_count('followers', $this->xml);
		
		$this->set_cache('pinterest_followers', $this->values['followers']);
		
		$this->values['following'] = $this->get_count('following', $this->xml);
		
		$this->set_cache('pinterest_following', $this->values['following']);
		
		$this->values['likes'] = $this->get_count('likes', $this->xml);
		
		$this->set_cache('pinterest_likes', $this->values['likes']);		
	}

	/**
	 * get_count function.
	 * 
	 * @access public
	 * @param mixed $type
	 * @param mixed $data
	 * @return integer
	 */
	function get_count($type, $data) {
		
		switch($type) {
		
			case 'pins':
				
				preg_match("@{$type}\" content=\"([0-9]+)\"@", $data, $matches);
				
			break;
			
			case 'boards':
				
				preg_match("@([0-9]+) Boards@", $data, $matches);
				
			break;
			
			case 'followers':
				
				preg_match("@{$type}/\">([0-9]+)@", $data, $matches);
				
			break;
			
			case 'following':
				
				preg_match("@{$type}/\">([0-9]+)@", $data, $matches);
				
			break;
			
			case 'likes':
				
				preg_match("@([0-9]+) Likes@", $data, $matches);
				
			break;
		}
			
		
	
		return number_format($matches[1]);
	}
		
	/**
	 * set_cached function.
	 * 
	 * @access public
	 * @return void
	 */
	function set_cached() {
		$this->values['pins'] = $this->get_cache('pinterest_pins');
		$this->values['boards'] = $this->get_cache('pinterest_boards');
		$this->values['followers'] = $this->get_cache('pinterest_followers');
		$this->values['following'] = $this->get_cache('pinterest_following');
		$this->values['likes']	= $this->get_cache('pinterest_likes');
	}
	
	/**
	 * get function.
	 * 
	 * @access public
	 * @param mixed $value
	 * @return integer
	 */
	function get($value){
		return (isset($this->values[$value]) ? $this->values[$value] : '0');
	}

	/**
	 * getFollowers function.
	 * 
	 * @access public
	 * @return integer
	 */
	function getFollowers() {
		return $this->get('followers');
	}
	
	/**
	 * getPins function.
	 * 
	 * @access public
	 * @return integer
	 */
	function getPins() {
		return $this->get('pins');
	}
	
	/**
	 * getBoards function.
	 * 
	 * @access public
	 * @return integer
	 */
	function getBoards() {
		return $this->get('boards');
	}
	
	/**
	 * getFollowing function.
	 * 
	 * @access public
	 * @return integer
	 */
	function getFollowing() {
		return $this->get('following');
	}
	
	/**
	 * getLikes function.
	 * 
	 * @access public
	 * @return integer
	 */
	function getLikes() {
		return $this->get('likes');
	}
	
	/**
	 * Get address.
	 * @return address
	 * @access public
	 */
	function getAddress() {
		return $this->address;
	}
}
?>