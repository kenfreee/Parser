<?php 
	require_once(__DIR__.DIRECTORY_SEPARATOR."libs/phpquery-master/phpQuery/phpQuery.php");

	if (!is_callable('curl_init')) {
		exit("There is no CURL module.");
	}

	function get_data($url) 
	{
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);				
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
		curl_setopt($ch, CURLOPT_FAILONERROR, true);		
		$data = curl_exec($ch);
		
		if(curl_errno($ch)) {
			$error = curl_error($ch);
		}

		curl_close($ch);

		if ($data === false) {
			exit("CURL - Session error: $error");
		}
		return $data;
	}

	function get_links($html) 
	{
		$result = array();

		$pq = phpQuery::newDocument($html);

		$links = $pq->find('a');	


		foreach ($links as $link) {
			$link = pq($link);

			$text[] = $link->text();
			$href[] = $link->attr('href');
		}

		$result['text'] = $text;
		$result['href'] = $href;

		phpQuery::unloadDocuments();

		return $result;
	}

	function find_links(&$links, $keyword, $mode, $callback = false) 
	{
		$mode = substr(strtolower($mode), 5);

		foreach ($links[$mode] as $key => &$value) {
			if (is_callable($callback)) {
				$value = call_user_func($callback, $value, $keyword);
			}
		}

		$links[$mode] = array_filter($links[$mode]);

		return count($links[$mode]);
	}

	$callback = function($value, $keyword) {

		if (stripos($value, $keyword) === false) {
			$value = "";
		}

		return $value;
	};

	function filter_result($array, $separator = "[:]") 
	{
		$result = array();
		$array_keys = array_keys($array);
		$array_size = count($array_keys);
		$array_largest = count($array[$array_keys[0]]);

		for ($i=0; $i !== $array_size - 1; ++$i) {
			if (count($array[$array_keys[$i + 1]]) > $array_largest) {
				$array_largest = count($array[$array_keys[$i + 1]]);
			}

		}

		for ($i=0; $i < $array_largest; ++$i) 
		{
			$check = 0;
			$result_temp = "";
			
			
			for ($j = 0; $j < $array_size; ++$j) 
			{
				if (isset($array[$array_keys[$j]][$i])) {
					++$check;
					$result_temp .= $array[$array_keys[$j]][$i].$separator;
				} else continue 2;

				if ($check === $array_size) {
					$result_temp = rtrim($result_temp, $separator);
					$result[] = $result_temp;
				}

			}  
		}		

		return $result;
	}
	
	//$data = get_data("https://news.tut.by/world/");
	$data = get_data("https://jobs.tut.by/search/vacancy?text=php&area=16");	
	$links = get_links($data);
	//$numbers_of_results = find_links($links, "world", "MODE_HREF", $callback);
	$numbers_of_results = find_links($links, "PHP", "MODE_TEXT", $callback);
	$result = filter_result($links);

	$output = "#$numbers_of_results#".implode("[@]", $result);
	//print_r($output);