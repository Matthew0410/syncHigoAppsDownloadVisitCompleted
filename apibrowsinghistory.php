<?php
	set_time_limit(0);

	$default = new stdClass();
	$default->prod = TRUE;
	$default->online = TRUE;

	$default->server = 'localhost';

	$default->database = 'higo_router_wifi';
	$default->username = 'root';
	$default->password = '';

	$default->mysqli = new mysqli($default->server, $default->username, $default->password, $default->database);

	$default->now = mktime(0, 0, 0);
	$default->now_date = date('Y-m-d 00:00:00', $default->now);

	function query_select($mysqli, $sql, $obj = FALSE)
	{
		$query_sql = $mysqli->query($sql);

		if ($query_sql->num_rows === 0)
		{
			$query_sql->free_result();

			return NULL;
		}
		elseif ($obj && $query_sql->num_rows === 1)
		{
			$obj_data = $query_sql->fetch_object();
			$query_sql->free_result();

			return $obj_data;
		}
		else
		{
			if ($query_sql->field_count > 1)
			{
				$arr_data = array();

				while ($row = $query_sql->fetch_object())
				{
					$arr_data[] = clone $row;
				}

				$query_sql->free_result();

				return $arr_data;
			}
			else
			{
				$arr_data = array();

				while ($row = $query_sql->fetch_object())
				{
					foreach ($row as $k => $v)
					{
						$arr_data[] = $v;
					}
				}

				$query_sql->free_result();

				return $arr_data;
			}
		}
	}

	$json['success'] = TRUE;
	$json['message'] = '';

	try
	{
		$higo_router_id = $_POST['id'];
		$merchant_name = $_POST['name'];
		$post_date = $_POST['date'];

		$arr_date = getdate($post_date);

		$date = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr_date['mon'], $arr_date['mday'], $arr_date['year']));

		$prev_date = mktime(0, 0, 0, $arr_date['mon'], $arr_date['mday'] - 1, $arr_date['year']);
		$next_date = mktime(0, 0, 0, $arr_date['mon'], $arr_date['mday'] + 1, $arr_date['year']);

		$next = date('Y-m-d H:i:s', $next_date);

		$sql_browsing_history = 'SELECT time, mac_address, host_name, url, webshrink_category, webshrink_category2, webshrink_category3, webshrink_category4, webshrink_category5, webshrink_category6 ';
		$sql_browsing_history .= 'FROM `browsing_history` ';
		$sql_browsing_history .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$date}' AND date < '{$next}' AND url NOT LIKE '%higoapps%'";
		$arr_browsing_history = (query_select($default->mysqli, $sql_browsing_history)) ? query_select($default->mysqli, $sql_browsing_history) : array();

		$arr_data = array();

		foreach ($arr_browsing_history as $browsing_history)
		{
			$arr_data["{$browsing_history->mac_address}---{$browsing_history->host_name}"][] = clone $browsing_history;
		}

		$json['higo_router_id'] = $higo_router_id;
		$json['merchant_name'] = $merchant_name;
		$json['date'] = $date;
		$json['prev_date'] = $prev_date;
		$json['next_date'] = $next_date;
		$json['arr_data'] = $arr_data;
	}
	catch (Exception $e)
	{
		$json['success'] = FALSE;
		$json['message'] = ($e->getMessage() == '') ? 'Server Error' : $e->getMessage();
	}

	header('Content-Type: application/json');
	ob_clean();
	flush();
	echo json_encode($json);
	exit(1);
?>