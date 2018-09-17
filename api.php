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
		$from_date = $_POST['date'];

		$arr_from_date = getdate($from_date);

		$from = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr_from_date['mon'], 1, $arr_from_date['year']));
		$to = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr_from_date['mon'] + 1, 0, $arr_from_date['year']));

		$month = date('F', $from_date);

		$prev_month = mktime(0, 0, 0, $arr_from_date['mon'] - 1, 1, $arr_from_date['year']);
		$next_month = mktime(0, 0, 0, $arr_from_date['mon'] + 1, 1, $arr_from_date['year']);

		$sql_login = 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, COUNT(id) AS count_data ';
		$sql_login .= 'FROM login ';
		$sql_login .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
		$sql_login .= 'GROUP BY date';
		$arr_login = (query_select($default->mysqli, $sql_login)) ? query_select($default->mysqli, $sql_login) : array();

		$arr_login_lookup = array();

		foreach ($arr_login as $login)
		{
			$arr_login_lookup[$login->date_format] = $login->count_data;
		}

		$sql_log = 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, COUNT(id) AS count_data ';
		$sql_log .= 'FROM `log` ';
		$sql_log .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
		$sql_log .= 'GROUP BY `date`, higo_router_id ';
		$sql_log .= 'ORDER BY `date_format` ASC, higo_router_name ASC';
		$arr_log = (query_select($default->mysqli, $sql_log)) ? query_select($default->mysqli, $sql_log) : array();

		$arr_log_lookup = array();

		foreach ($arr_log as $log)
		{
			$arr_log_lookup[$log->date_format] = clone $log;
		}

		$sql_confirm = 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, COUNT(id) AS count_data ';
		$sql_confirm .= 'FROM confirmation_page ';
		$sql_confirm .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
		$sql_confirm .= 'GROUP BY date';
		$arr_confirm = (query_select($default->mysqli, $sql_confirm)) ? query_select($default->mysqli, $sql_confirm) : array();

		$arr_confirm_lookup = array();

		foreach ($arr_confirm as $confirm)
		{
			$arr_confirm_lookup[$confirm->date_format] = $confirm->count_data;
		}

		$sql_alogin = 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, COUNT(id) AS count_data ';
		$sql_alogin .= 'FROM alogin ';
		$sql_alogin .= "WHERE higo_router_id = {$higo_router_id}  AND date >= '{$from}' AND date <= '{$to}' ";
		$sql_alogin .= 'GROUP BY `date`, higo_router_id';
		$arr_alogin = (query_select($default->mysqli, $sql_alogin)) ? query_select($default->mysqli, $sql_alogin) : array();

		$arr_alogin_lookup = array();

		foreach ($arr_alogin as $alogin)
		{
			$arr_alogin_lookup[$alogin->date_format] = $alogin->count_data;
		}

		$arr_date = array();

		if ($from_date != '')
		{
			while ($from_date <= mktime(0, 0, 0, $arr_from_date['mon'] + 1, 0, $arr_from_date['year']))
			{
				$arr_date[] = $from_date;
				$arr_from_date = getdate($from_date);
				$from_date = mktime(0, 0, 0, $arr_from_date['mon'], $arr_from_date['mday'] + 1, $arr_from_date['year']);
			}
		}

		$arr_data = array();

		foreach ($arr_date as $date)
		{
			$data = new stdClass();
			$data->date = date('Y-m-d', $date);
			$data->date_timestamp = $date;
			$data->login = (isset($arr_login_lookup[date('Y-m-d', $date)])) ? $arr_login_lookup[date('Y-m-d', $date)] : 0;
			$data->data = (isset($arr_log_lookup[date('Y-m-d', $date)])) ? $arr_log_lookup[date('Y-m-d', $date)]->count_data : 0;
			$data->confirm = (isset($arr_confirm_lookup[date('Y-m-d', $date)])) ? $arr_confirm_lookup[date('Y-m-d', $date)] : 0;
			$data->success = (isset($arr_alogin_lookup[date('Y-m-d', $date)])) ? $arr_alogin_lookup[date('Y-m-d', $date)] : 0;
			$arr_data[] = clone $data;
		}

		$json['higo_router_id'] = $higo_router_id;
		$json['merchant_name'] = $merchant_name;
		$json['month'] = $month;
		$json['prev_month'] = $prev_month;
		$json['next_month'] = $next_month;
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