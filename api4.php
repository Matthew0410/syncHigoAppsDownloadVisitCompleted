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

		$sql_login2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
		$sql_login2 .= 'SELECT DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, mac ';
		$sql_login2 .= 'FROM login ';
		$sql_login2 .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
		$sql_login2 .= 'GROUP BY date_format, mac) l ';
		$sql_login2 .= 'GROUP BY date_format';
		$arr_login2 = (query_select($default->mysqli, $sql_login2)) ? query_select($default->mysqli, $sql_login2) : array();

		$arr_login2_lookup = array();

		foreach ($arr_login2 as $login2)
		{
			$arr_login2_lookup[$login2->date_format] = $login2->count_data;
		}

		$sql_log2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
		$sql_log2 .= 'SELECT DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, mac ';
		$sql_log2 .= 'FROM `log` ';
		$sql_log2 .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
		$sql_log2 .= 'GROUP BY date_format, mac) l ';
		$sql_log2 .= 'GROUP BY date_format';
		$arr_log2 = (query_select($default->mysqli, $sql_log2)) ? query_select($default->mysqli, $sql_log2) : array();

		$arr_log2_lookup = array();

		foreach ($arr_log2 as $log2)
		{
			$arr_log2_lookup[$log2->date_format] = clone $log2;
		}

		$sql_confirm2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
		$sql_confirm2 .= 'SELECT DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, mac ';
		$sql_confirm2 .= 'FROM confirmation_page ';
		$sql_confirm2 .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
		$sql_confirm2 .= 'GROUP BY date_format, mac) l ';
		$sql_confirm2 .= 'GROUP BY date_format';
		$arr_confirm2 = (query_select($default->mysqli, $sql_confirm2)) ? query_select($default->mysqli, $sql_confirm2) : array();

		$arr_confirm2_lookup = array();

		foreach ($arr_confirm2 as $confirm2)
		{
			$arr_confirm2_lookup[$confirm2->date_format] = $confirm2->count_data;
		}

		$sql_alogin2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
		$sql_alogin2 .= 'SELECT DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, mac ';
		$sql_alogin2 .= 'FROM alogin ';
		$sql_alogin2 .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
		$sql_alogin2 .= 'GROUP BY date_format, mac) l ';
		$sql_alogin2 .= 'GROUP BY date_format';
		$arr_alogin2 = (query_select($default->mysqli, $sql_alogin2)) ? query_select($default->mysqli, $sql_alogin2) : array();

		$arr_alogin2_lookup = array();

		foreach ($arr_alogin2 as $alogin2)
		{
			$arr_alogin2_lookup[$alogin2->date_format] = $alogin2->count_data;
		}

		$arr_time = array();

		for ($i = 0; $i < 24; $i++)
		{
			$arr_time[] = ($i > 9) ? $i.':00:00' : '0'.$i.':00:00';
		}

		$arr_data = array();

		foreach ($arr_time as $time)
		{
			$arr_explode_time = explode(':', $time);

			$data = new stdClass();
			$data->time = str_pad(($arr_explode_time[0] + 1) . ':00', 5, '0', STR_PAD_LEFT);
			$data->login = (isset($arr_login2_lookup[$time])) ? $arr_login2_lookup[$time] : 0;
			$data->data = (isset($arr_log2_lookup[$time])) ? $arr_log2_lookup[$time]->count_data : 0;
			$data->confirm = (isset($arr_confirm2_lookup[$time])) ? $arr_confirm2_lookup[$time] : 0;
			$data->success = (isset($arr_alogin2_lookup[$time])) ? $arr_alogin2_lookup[$time] : 0;
			$arr_data[] = clone $data;
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