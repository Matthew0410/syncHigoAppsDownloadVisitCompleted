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

		$sql_login = 'SELECT socmed_id, type, name, email, gender, phone, birthday, image, username, followers_count, friends_count ';
		$sql_login .= 'FROM `log` ';
		$sql_login .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
		$arr_login = (query_select($default->mysqli, $sql_login)) ? query_select($default->mysqli, $sql_login) : array();

		$arr_data = array();

		foreach ($arr_login as $login)
		{
			$login->birthday = ($login->birthday == '0000-00-00 00:00:00') ? '' : date('d F Y', strtotime($login->birthday));
			$arr_data["{$login->type}:{$login->email}:{$login->name}"] = clone $login;
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