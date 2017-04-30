<?php
	//=============================[INPUT]=============================
	$url = "https://jobs.tut.by/search/vacancy?text=php&area=16";
	$keyword = "PHP";
	//=================================================================
	
	//============================[SETTINGS]===========================
	$settings = require_once("config.php");
	require_once(__DIR__.DIRECTORY_SEPARATOR."libs/phpquery-master/phpQuery/phpQuery.php");

	if (!is_callable('curl_init')) {
		exit("There is no CURL module.");
	}
	//=================================================================
	
	//===========================[FUNCTIONS]===========================
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

	function filter_result($array, $separator) 
	{
		$arrays_number = count($array);
		
		if ($arrays_number >= 2) 
		{
			$result = array();
			$array = array_values($array);

			$sort_ascending = function ($a, $b) {
				$a = count($a);
				$b = count($b);

				if ($a == $b) {
					return 0;
				}
				
				return ($a < $b) ? -1 : 1;
			};

			uasort($array, $sort_ascending);
			
			foreach ($array[0] as $key => $value) {
				$check = 0;
				$result_temp = $value.$separator;

				for ($i=1; $i <= $arrays_number - 1; ++$i) {
					if (isset($array[$i][$key])) {
						$result_temp .= $array[$i][$key];
						++$check;
					} else break;

				}

				if ($check == $arrays_number - 1) {
					$result_temp = rtrim($result_temp, $separator);
					$result[] = $result_temp;
				}

			}
		} else return $array;

		return $result;
	}
	//=================================================================

	//============================[OUTPUT]=============================
	$data = get_data($url);
	$links = get_links($data);
	$results_count = find_links($links, $keyword, $settings['mode'], $callback);
	$result = filter_result($links, $settings['results_separator']);

	$output = str_pad($results_count, strlen($results_count) + 6, $settings['results_count_separator'], STR_PAD_BOTH).implode($settings['elements_separator'], $result);
	//print_r($output);
	//=================================================================