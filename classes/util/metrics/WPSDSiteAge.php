<?php
/**
 * WPSDSiteAge.
 * @author dligthart <info@daveligthart.com>
 * @version 0.4
 * @package wp-stats-dashboard
 */
class WPSDSiteAge extends WPSDStats {
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
	 * allowed_ext
	 * 
	 * @var mixed
	 * @access public
	 */
	var $allowed_ext = array(
		'.aero', '.arpa', '.asia', '.biz', '.cat', 
		'.com', '.coop', '.edu', '.info', '.int', 
		'.jobs', '.mobi', '.museum', '.name', '.net', 
		'.org', '.pro','.travel');
	/**
	 * domain_ok
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access public
	 */	
	var $domain_ok = false;
	
	/**
	 * WPSDSiteAge function.
	 * 
	 * @access public
	 * @param mixed $domain
	 * @return void
	 */
	function WPSDSiteAge($domain) {
		
		parent::WPSDStats();
		
		if($this->checkDomain($domain)) {
		
			$url = preg_replace("/^(http:\/\/)*(www.)*/is", "",  $this->getHost(parse_url($domain)) );

			$url = preg_replace("/\/.*$/is" , "" ,$url);
					
			$this->address = 'http://reports.internic.net/cgi/whois?whois_nic='.$url.'&type=domain';

			if($this->isOutdated() && '' != $url) {
			
				$this->xml = $this->fetchDataRemote($this->address);

				$this->set();
			
			} else {
			
				$this->set_cached();
			}
		}
	}
	
	/**
	 * domainOK function.
	 * 
	 * @access public
	 * @return boolean
	 */
	function domainOK() {
		
		return $this->domain_ok;
	}
	
	/**
	 * checkDomain function.
	 * 
	 * @access public
	 * @param mixed $domain
	 * @return void
	 */
	function checkDomain($domain) {
		
		$domain_ok = false;
				
		foreach ($this->allowed_ext as $e) {
  			if (strrpos($domain, $e)) {
   				$domain_ok = true;
    			break;
    		}
 		}
 		
 		$this->domain_ok = $domain_ok;
 		
 		return $domain_ok;
	}
	
	/**
	 * set function.
	 * 
	 * @access public
	 * @return void
	 */
	function set() {

		preg_match("@Creation Date:(.*?)Expiration Date@si", $this->xml, $matches);

		$creation_date = date('Y-m-d', strtotime($matches[1]));

		$a = strtotime($creation_date);
		
		$b = strtotime("now");
		
		$years = ( intval( ( $b - $a) / 86400) +1 ) / 365;

		if(null == $years) $years = 1;

		$this->values['age'] = $years;
		
		$this->set_cache('age', $years);
	}
	
	/**
	 * set_cached function.
	 * 
	 * @access public
	 * @return void
	 */
	function set_cached() {
		$this->values['age'] = $this->get_cache('age');
	}
	
	/**
	 * get function.
	 * 
	 * @access public
	 * @param mixed $value
	 * @return void
	 */
	function get($value){
		return (isset($this->values[$value]) ? $this->values[$value] : '0');
	}

	/**
	 * getAge function.
	 * 
	 * @access public
	 * @return void
	 */
	function getAge() {
		return $this->get('age');
	}

	/**
	 * getAddress function.
	 * 
	 * @access public
	 * @return void
	 */
	function getAddress() {
		return $this->address;
	}
}
?>