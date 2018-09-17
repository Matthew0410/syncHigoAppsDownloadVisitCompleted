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

		$sql_login = 'SELECT mac ';
		$sql_login .= 'FROM login ';
		$sql_login .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
		$sql_login .= 'GROUP BY mac';
		$arr_mac = (query_select($default->mysqli, $sql_login)) ? query_select($default->mysqli, $sql_login) : array();

		$sql_login2 = 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, mac, higo_router_id ';
		$sql_login2 .= 'FROM login ';
		$sql_login2 .= "WHERE mac IN ('".implode("','", $arr_mac)."')";
		$arr_login = (query_select($default->mysqli, $sql_login2)) ? query_select($default->mysqli, $sql_login2) : array();

		$arr_higo_router_id = array();
		$arr_higo_router_higo_router_id_lookup = array();
		$arr_higo_router_date_lookup = array();

		foreach ($arr_login as $login)
		{
			$arr_higo_router_id[$login->higo_router_id] = $login->higo_router_id;
			$arr_higo_router_higo_router_id_lookup[$login->mac][$login->higo_router_id] = $login->higo_router_id;
			$arr_higo_router_date_lookup[$login->mac][$login->higo_router_id][$login->date_format] = $login->date_format;
		}

		$sql_higo_router = 'SELECT id, name ';
		$sql_higo_router .= 'FROM higo_router ';
		$sql_higo_router .= 'WHERE id IN ('.implode(',', $arr_higo_router_id).')';
		$arr_higo_router = (query_select($default->mysqli, $sql_higo_router)) ? query_select($default->mysqli, $sql_higo_router) : array();

		$arr_higo_router_lookup = array();

		foreach ($arr_higo_router as $higo_router)
		{
			$arr_higo_router_lookup[$higo_router->id] = $higo_router->name;
		}

		$arr_data = array();

		foreach ($arr_mac as $mac)
		{
			$data = new stdClass();
			$data->mac = $mac;

			$arr_data_lookup = array();

			foreach ($arr_higo_router_higo_router_id_lookup[$mac] as $router_id)
			{
				$data_lookup = new stdClass();
				$data_lookup->name = $arr_higo_router_lookup[$router_id];
				$data_lookup->arr_date = $arr_higo_router_date_lookup[$mac][$router_id];
				$data_lookup->time_count = count($arr_higo_router_date_lookup[$mac][$router_id]);
				$arr_data_lookup[] = clone $data_lookup;
			}

			$data->location_count = count($arr_data_lookup);
			$data->arr_higo_router = $arr_data_lookup;
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