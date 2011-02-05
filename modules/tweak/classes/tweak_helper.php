<?

if(!function_exists('close_form')) {
	function close_form() {
		return "</form>";
	}
}

if(!function_exists('array_merge_recursive_distinct')) {
	function array_merge_recursive_distinct() {
		$arrays = func_get_args();
		$base = array_shift($arrays);
		if(!is_array($base)) $base = empty($base) ? array() : array($base);
		foreach($arrays as $append) {
			if(!is_array($append)) $append = array($append);
			foreach($append as $key => $value) {
				if(!array_key_exists($key, $base) and !is_numeric($key)) {
					$base[$key] = $append[$key];
					continue;
				}
				if(is_array($value) || (isset($base[$key]) && is_array($base[$key]))) {
					$base[$key] = array_merge_recursive_distinct($base[$key], $append[$key]);
				} else if(is_numeric($key)) {
					if(!in_array($value, $base)) $base[] = $value;
				} else {
					$base[$key] = $value;
				}
			}
		}
		return $base;
	}
}

class Tweak_Helper {
	public function __contruct() {
		
	}
}
