<?php
	set_time_limit(0);

	session_set_cookie_params(0);
	session_start();

	if ($_SERVER['HTTP_REFERER'] != 'http://localhost/sync/' && !$_SESSION['referer'])
	{
		if ($_SERVER['REMOTE_ADDR'] != '146.196.107.2')
		{
			header("HTTP/1.0 404 Not Found");
			echo "<h1>404 Not Found</h1>";
			echo "The page that you have requested could not be found.";
			exit();
		}
	}

	$_SESSION['referer'] = true;

	require('routeros_api.class.php');

	$api = new RouterosAPI();
	$api->debug = false;

	$arr_higo_router_ppp = array();
	$arr_higo_router_ppp_lookup = array();

	$user1 = 'mikrotik';
	$user2 = 'higo';
	$pass1 = 'm1kr0t1k';
	$pass2 = 'h1g02015';
	$arr_pass1 = str_split($pass1);
	$arr_pass2 = str_split($pass2);

	$user = $user1 . $user2;
	$pass = '';

	foreach ($arr_pass1 as $key => $pass1)
	{
		$pass .= $pass1;

		if (isset($arr_pass2[$key]))
		{
			$pass .= $arr_pass2[$key];
			unset($arr_pass2[$key]);
		}
	}

	foreach ($arr_pass2 as $pass2)
	{
		$pass .= $pass2;
	}

	$arr_sstp_lookup = array();
	$arr_sstp_server_lookup = array();
	$arr_pptp_server_lookup = array();
	$arr_neighbor_lookup = array();
	$arr_ppp_secret_lookup = array();
	$arr_ppp_active_lookup = array();

	if ($api->connect('128.199.123.110', $user, $pass))
	{
		$arr_interface = $api->comm('/interface/print');

		foreach ($arr_interface as $interface)
		{
			if ($interface['type'] != 'sstp-in' && $interface['type'] != 'pptp-in')
			{
				continue;
			}

			$arr_sstp_lookup[$interface['name']] = $interface;
		}

		$arr_sstp_server = $api->comm('/interface/sstp-server/print');

		foreach ($arr_sstp_server as $sstp_server)
		{
			$arr_sstp_server_lookup[$sstp_server['user']] = $sstp_server;
		}

		$arr_pptp_server = $api->comm('/interface/pptp-server/print');

		foreach ($arr_pptp_server as $pptp_server)
		{
			$arr_pptp_server_lookup[$pptp_server['user']] = $pptp_server;
		}

		$arr_neighbor = $api->comm('/ip/neighbor/print');

		foreach ($arr_neighbor as $neighbor)
		{
			$arr_neighbor_lookup[$neighbor['address']] = $neighbor;
		}

		$arr_ppp_secret = $api->comm('/ppp/secret/print');

		foreach ($arr_ppp_secret as $ppp_secret)
		{
			$arr_ppp_secret_lookup[$ppp_secret['name']] = $ppp_secret;
		}

		$arr_ppp_active = $api->comm('/ppp/active/print');

		foreach ($arr_ppp_active as $ppp_active)
		{
			$arr_ppp_active_lookup[$ppp_active['name']] = $ppp_active;
		}

		$api->disconnect();
	}

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

	$default = new stdClass();

	$default->server = 'localhost';

	$default->database = 'higo_router_wifi';
	$default->username = 'root';
	$default->password = '';

	$default->mysqli = new mysqli($default->server, $default->username, $default->password, $default->database);

	$sql_higo_router = 'SELECT id, name ';
	$sql_higo_router .= 'FROM higo_router ';
	$sql_higo_router .= "WHERE status = 1 AND landing_page = 1 ";
	$sql_higo_router .= 'ORDER BY name';
	$arr_higo_router = (query_select($default->mysqli, $sql_higo_router)) ? query_select($default->mysqli, $sql_higo_router) : array();

	$sql_higo_router_mikrotik = 'SELECT higo_router_id, user, model, serial_number, current_firmware, upgrade_firmware, version ';
	$sql_higo_router_mikrotik .= 'FROM higo_router_mikrotik ';
	$arr_higo_router_mikrotik = (query_select($default->mysqli, $sql_higo_router_mikrotik)) ? query_select($default->mysqli, $sql_higo_router_mikrotik) : array();

	$arr_higo_router_lookup = array();

	foreach ($arr_higo_router as $higo_router)
	{
		$arr_higo_router_lookup[$higo_router->id] = $higo_router->name;
	}

	foreach ($arr_higo_router_mikrotik as $key => $higo_router_mikrotik)
	{
		$higo_router_mikrotik->higo_router_name = (isset($arr_higo_router_lookup[$higo_router_mikrotik->higo_router_id])) ? $arr_higo_router_lookup[$higo_router_mikrotik->higo_router_id] : 'ZZZ In Office';
	}

	echo '<html>';
	echo '<head>';
	echo '<meta http-equiv="refresh" content="5">';
	echo '</head>';
	echo '<body style="font-family: Calibri;">';
	echo '<table style="border: 1px #000 solid; border-collapse: collapse; font-size: 12px;">';
	echo '<tr>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Merchant</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">SSTP User</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">IP Address</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Link Uptime</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Mikrotik Uptime</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Last Down Time</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Last Up Time</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Down Count</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Board</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Version</th>';
	echo '<th style="border: 1px #000 solid; padding: 4px;">Serial Number</th>';
	echo '</tr>';

	$arr_data = array();

	foreach ($arr_higo_router_mikrotik as $key => $higo_router_mikrotik)
	{
		$data = new stdClass();

		$data->higo_router_name = $higo_router_mikrotik->higo_router_name;
		$data->sstp_user = $higo_router_mikrotik->user;
		$data->address = (isset($arr_ppp_active_lookup[$data->sstp_user]['address'])) ? $arr_ppp_active_lookup[$data->sstp_user]['address'] : '&nbsp;';

		$data->address = ($data->address == '&nbsp;' && isset($arr_ppp_secret_lookup[$data->sstp_user]['remote-address'])) ? $arr_ppp_secret_lookup[$data->sstp_user]['remote-address'] : $data->address;

		$data->interface = (isset($arr_neighbor_lookup[$data->address]['interface'])) ? $arr_neighbor_lookup[$data->address]['interface'] : '&nbsp;';

		$data->interface = ($data->interface == '&nbsp;' && isset($arr_sstp_server_lookup[$data->sstp_user]['name'])) ? $arr_sstp_server_lookup[$data->sstp_user]['name'] : $data->interface;

		$data->link_uptime = (isset($arr_ppp_active_lookup[$data->sstp_user]['uptime'])) ? $arr_ppp_active_lookup[$data->sstp_user]['uptime'] : '&nbsp;';
		$data->mikrotik_uptime = (isset($arr_neighbor_lookup[$data->address]['uptime'])) ? $arr_neighbor_lookup[$data->address]['uptime'] : '&nbsp;';

		$data->last_link_down_time = (isset($arr_sstp_lookup[$data->interface]['last-link-down-time'])) ? date('d M Y H:i:s', strtotime(str_replace('/', '-', $arr_sstp_lookup[$data->interface]['last-link-down-time']))) : '&nbsp;';
		$data->last_link_up_time = (isset($arr_sstp_lookup[$data->interface]['last-link-up-time'])) ? date('d M Y H:i:s', strtotime(str_replace('/', '-', $arr_sstp_lookup[$data->interface]['last-link-up-time']))) : '&nbsp;';
		$data->link_downs = (isset($arr_sstp_lookup[$data->interface]['link-downs'])) ? $arr_sstp_lookup[$data->interface]['link-downs'] : '&nbsp;';
		$data->board = (isset($arr_neighbor_lookup[$data->address]['board'])) ? $arr_neighbor_lookup[$data->address]['board'] : '&nbsp;';
		$data->version = (isset($arr_neighbor_lookup[$data->address]['version'])) ? $arr_neighbor_lookup[$data->address]['version'] : '&nbsp;';
		$data->serial_number = $higo_router_mikrotik->serial_number;

		$data->board = ($data->board == '&nbsp;') ? $higo_router_mikrotik->model : $data->board;
		$data->version = ($data->version == '&nbsp;') ? $higo_router_mikrotik->version : $data->version;

		if (preg_match('/931-2nD/', $data->board))
		{
			$data->board = '931-2nD';
		}
		if (preg_match('/941-2nD/', $data->board))
		{
			$data->board = '941-2nD';
		}
		elseif (preg_match('/951Ui-2nD/', $data->board))
		{
			$data->board = '951Ui-2nD';
		}
		elseif (preg_match('/951Ui-2HnD/', $data->board))
		{
			$data->board = '951Ui-2HnD';
		}
		elseif (preg_match('/952Ui-5ac2nD/', $data->board))
		{
			$data->board = '952Ui-5ac2nD';
		}
		elseif (preg_match('/SXT G-2HnD r2/', $data->board))
		{
			$data->board = 'SXT G-2HnD r2';
		}
		elseif (preg_match('/1100Dx4/', $data->board))
		{
			$data->board = '1100Dx4';
		}

		$data->running = (isset($arr_sstp_lookup[$data->interface]['running']) && $arr_sstp_lookup[$data->interface]['running'] == 'true') ? 1 : 0;
		$data->priority = ($data->higo_router_name == 'ZZZ In Office') ? 1 : 0;

		$arr_data[] = clone $data;

		unset($arr_sstp_lookup[$data->interface]);
		unset($arr_sstp_server_lookup[$data->sstp_user]);
		unset($arr_pptp_server_lookup[$data->sstp_user]);
		unset($arr_neighbor_lookup[$data->address]);
		unset($arr_ppp_secret_lookup[$data->sstp_user]);
		unset($arr_ppp_active_lookup[$data->sstp_user]);
	}

	foreach ($arr_ppp_secret_lookup as $sstp_user => $ppp_secret_lookup)
	{
		$data = new stdClass();

		$data->higo_router_name = 'ZZZ Out Office';
		$data->sstp_user = $sstp_user;
		$data->address = $ppp_secret_lookup['remote-address'];
		$data->interface = (isset($arr_sstp_server_lookup[$data->sstp_user]['name'])) ? $arr_sstp_server_lookup[$data->sstp_user]['name'] : '&nbsp;';

		$data->interface = ($data->interface == '&nbsp;' && isset($arr_pptp_server_lookup[$data->sstp_user]['name'])) ? $arr_pptp_server_lookup[$data->sstp_user]['name'] : $data->interface;

		$data->link_uptime = (isset($arr_ppp_active_lookup[$data->sstp_user]['uptime'])) ? $arr_ppp_active_lookup[$data->sstp_user]['uptime'] : '&nbsp;';
		$data->mikrotik_uptime = (isset($arr_neighbor_lookup[$data->address]['uptime'])) ? $arr_neighbor_lookup[$data->address]['uptime'] : '&nbsp;';
		$data->last_link_down_time = (isset($arr_sstp_lookup[$data->interface]['last-link-down-time'])) ? date('d M Y H:i:s', strtotime(str_replace('/', '-', $arr_sstp_lookup[$data->interface]['last-link-down-time']))) : '&nbsp;';
		$data->last_link_up_time = (isset($arr_sstp_lookup[$data->interface]['last-link-up-time'])) ? date('d M Y H:i:s', strtotime(str_replace('/', '-', $arr_sstp_lookup[$data->interface]['last-link-up-time']))) : '&nbsp;';
		$data->link_downs = (isset($arr_sstp_lookup[$data->interface]['link-downs'])) ? $arr_sstp_lookup[$data->interface]['link-downs'] : '&nbsp;';
		$data->board = (isset($arr_neighbor_lookup[$data->address]['board'])) ? $arr_neighbor_lookup[$data->address]['board'] : '&nbsp;';
		$data->version = (isset($arr_neighbor_lookup[$data->address]['version'])) ? $arr_neighbor_lookup[$data->address]['version'] : '&nbsp;';
		$data->serial_number = $higo_router_mikrotik->serial_number;

		if (preg_match('/931-2nD/', $data->board))
		{
			$data->board = '931-2nD';
		}
		if (preg_match('/941-2nD/', $data->board))
		{
			$data->board = '941-2nD';
		}
		elseif (preg_match('/951Ui-2nD/', $data->board))
		{
			$data->board = '951Ui-2nD';
		}
		elseif (preg_match('/951Ui-2HnD/', $data->board))
		{
			$data->board = '951Ui-2HnD';
		}
		elseif (preg_match('/952Ui-5ac2nD/', $data->board))
		{
			$data->board = '952Ui-5ac2nD';
		}
		elseif (preg_match('/SXT G-2HnD r2/', $data->board))
		{
			$data->board = 'SXT G-2HnD r2';
		}
		elseif (preg_match('/1100Dx4/', $data->board))
		{
			$data->board = '1100Dx4';
		}

		$data->running = (isset($arr_sstp_lookup[$data->interface]['running']) && $arr_sstp_lookup[$data->interface]['running'] == 'true') ? 1 : 0;
		$data->priority = ($data->higo_router_name == 'ZZZ Out Office') ? 1 : 0;

		$arr_data[] = clone $data;

		unset($arr_sstp_lookup[$data->interface]);
		unset($arr_sstp_server_lookup[$data->sstp_user]);
		unset($arr_pptp_server_lookup[$data->sstp_user]);
		unset($arr_neighbor_lookup[$data->address]);
		unset($arr_ppp_secret_lookup[$data->sstp_user]);
		unset($arr_ppp_active_lookup[$data->sstp_user]);
	}

	$arr_data_name = array();
	$arr_data_running = array();
	$arr_data_priority = array();

	foreach ($arr_data as $d => $data)
	{
		$arr_data_name[$d] = $data->higo_router_name;
		$arr_data_running[$d] = $data->running;
		$arr_data_priority[$d] = $data->priority;
	}

	array_multisort($arr_data_priority, SORT_ASC, $arr_data_running, SORT_ASC, $arr_data_name, SORT_ASC, $arr_data);

	foreach ($arr_data as $data)
	{
		echo ($data->running > 0) ? '<tr>' : '<tr style="background: #ED4337;">';
		echo '<td style="border: 1px #000 solid; padding: 4px;">'.$data->higo_router_name.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px;">'.$data->sstp_user.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px;">'.$data->address.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px; text-align: right;">'.$data->link_uptime.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px; text-align: right;">'.$data->mikrotik_uptime.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px; text-align: center;">'.$data->last_link_down_time.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px; text-align: center;">'.$data->last_link_up_time.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px; text-align: center;">'.$data->link_downs.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px;">'.$data->board.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px;">'.$data->serial_number.'</td>';
		echo '<td style="border: 1px #000 solid; padding: 4px; text-align: center;">'.$data->version.'</td>';
		echo '</tr>';
	}
?>