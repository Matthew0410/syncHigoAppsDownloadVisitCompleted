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

	$higo_router_id = $_GET['id'];
	$merchant_name = $_GET['name'];
	$post_date = $_GET['date'];

	$arr_date = getdate($post_date);

	$date = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr_date['mon'], $arr_date['mday'], $arr_date['year']));

	$prev_date = mktime(0, 0, 0, $arr_date['mon'], $arr_date['mday'] - 1, $arr_date['year']);
	$next_date = mktime(0, 0, 0, $arr_date['mon'], $arr_date['mday'] + 1, $arr_date['year']);

	$sql_login = 'SELECT mac, DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, COUNT(id) AS count_data ';
	$sql_login .= 'FROM login ';
	$sql_login .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
	$sql_login .= 'GROUP BY date_format, mac';
	$arr_login = (query_select($default->mysqli, $sql_login)) ? query_select($default->mysqli, $sql_login) : array();

	$arr_login_lookup = array();
	$arr_mac_lookup = array();

	foreach ($arr_login as $login)
	{
		$arr_mac_lookup[$login->date_format][$login->mac] = $login->mac;
		$arr_login_lookup[$login->date_format][$login->mac] = $login->count_data;
	}

	$sql_log = 'SELECT mac, DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, COUNT(id) AS count_data ';
	$sql_log .= 'FROM `log` ';
	$sql_log .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
	$sql_log .= 'GROUP BY date_format, mac ';
	$sql_log .= 'ORDER BY `date_format` ASC';
	$arr_log = (query_select($default->mysqli, $sql_log)) ? query_select($default->mysqli, $sql_log) : array();

	$arr_log_lookup = array();

	foreach ($arr_log as $log)
	{
		$arr_log_lookup[$log->date_format][$log->mac] = clone $log;
	}

	$sql_confirm = 'SELECT mac, DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, COUNT(id) AS count_data ';
	$sql_confirm .= 'FROM confirmation_page ';
	$sql_confirm .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
	$sql_confirm .= 'GROUP BY date_format, mac';
	$arr_confirm = (query_select($default->mysqli, $sql_confirm)) ? query_select($default->mysqli, $sql_confirm) : array();

	$arr_confirm_lookup = array();

	foreach ($arr_confirm as $confirm)
	{
		$arr_confirm_lookup[$confirm->date_format][$confirm->mac] = $confirm->count_data;
	}

	$sql_alogin = 'SELECT mac, DATE_FORMAT(`created`, "%H:00:00") AS `date_format`, COUNT(id) AS count_data ';
	$sql_alogin .= 'FROM alogin ';
	$sql_alogin .= "WHERE higo_router_id = {$higo_router_id} AND date = '{$date}' ";
	$sql_alogin .= 'GROUP BY date_format, mac';
	$arr_alogin = (query_select($default->mysqli, $sql_alogin)) ? query_select($default->mysqli, $sql_alogin) : array();

	$arr_alogin_lookup = array();

	foreach ($arr_alogin as $alogin)
	{
		$arr_alogin_lookup[$alogin->date_format][$alogin->mac] = $alogin->count_data;
	}

	$arr_time = array();

	for ($i = 0; $i < 24; $i++)
	{
		$arr_time[] = ($i > 9) ? $i.':00:00' : '0'.$i.':00:00';
	}

	$arr_data = array();

	foreach ($arr_time as $time)
	{
		if (!isset($arr_mac_lookup[$time]))
		{
			$arr_explode_time = explode(':', $time);

			$data = new stdClass();
			$data->time = str_pad(($arr_explode_time[0] + 1) . ':00', 5, '0', STR_PAD_LEFT);
			$data->mac = '';
			$data->login = 0;
			$data->data = 0;
			$data->confirm = 0;
			$data->success = 0;
			$arr_data[$data->time][] = clone $data;

			continue;
		}

		foreach ($arr_mac_lookup[$time] as $mac => $mac_lookup)
		{
			$arr_explode_time = explode(':', $time);

			$data = new stdClass();
			$data->time = str_pad(($arr_explode_time[0] + 1) . ':00', 5, '0', STR_PAD_LEFT);
			$data->mac = $mac;
			$data->login = (isset($arr_login_lookup[$time][$mac])) ? $arr_login_lookup[$time][$mac] : 0;
			$data->data = (isset($arr_log_lookup[$time][$mac])) ? $arr_log_lookup[$time][$mac]->count_data : 0;
			$data->confirm = (isset($arr_confirm_lookup[$time][$mac])) ? $arr_confirm_lookup[$time][$mac] : 0;
			$data->success = (isset($arr_alogin_lookup[$time][$mac])) ? $arr_alogin_lookup[$time][$mac] : 0;
			$arr_data[$data->time][] = clone $data;
		}
	}

	/*========== Start First Sheets ==========*/

	require('phpexcel/PHPExcel.php');

	$phpexcel = new PHPExcel();

	$phpexcel->setActiveSheetIndex(0);
	$phpexcel->getActiveSheet()->setTitle('Non Unique');

	$row = 2;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", $merchant_name);

	$next_row = $row + 1;
	$phpexcel->getActiveSheet()->mergeCells("B{$row}:K{$next_row}");
	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$row += 2;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'Jam');
	$phpexcel->getActiveSheet()->SetCellValue("C{$row}", 'MAC');
	$phpexcel->getActiveSheet()->SetCellValue("D{$row}", 'PUB');
	$phpexcel->getActiveSheet()->SetCellValue("E{$row}", 'Data');
	$phpexcel->getActiveSheet()->SetCellValue("F{$row}", 'Rate 1');
	$phpexcel->getActiveSheet()->SetCellValue("G{$row}", 'TVC');
	$phpexcel->getActiveSheet()->SetCellValue("H{$row}", 'Rate 2');
	$phpexcel->getActiveSheet()->SetCellValue("I{$row}", 'SP');
	$phpexcel->getActiveSheet()->SetCellValue("J{$row}", 'Rate 3');
	$phpexcel->getActiveSheet()->SetCellValue("K{$row}", 'Rate 4');

	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("C{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("D{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("E{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("F{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("G{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("H{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("I{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("J{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("K{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("C{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("D{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("E{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("F{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("G{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("H{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("I{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("J{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("K{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$row += 1;
	$before_row = $row - 1;
	$phpexcel->getActiveSheet()->mergeCells("B{$before_row}:B{$row}");
	$phpexcel->getActiveSheet()->mergeCells("C{$before_row}:C{$row}");
	$phpexcel->getActiveSheet()->mergeCells("D{$before_row}:D{$row}");
	$phpexcel->getActiveSheet()->mergeCells("E{$before_row}:E{$row}");
	$phpexcel->getActiveSheet()->mergeCells("F{$before_row}:F{$row}");
	$phpexcel->getActiveSheet()->mergeCells("G{$before_row}:G{$row}");
	$phpexcel->getActiveSheet()->mergeCells("H{$before_row}:H{$row}");
	$phpexcel->getActiveSheet()->mergeCells("I{$before_row}:I{$row}");
	$phpexcel->getActiveSheet()->mergeCells("J{$before_row}:J{$row}");
	$phpexcel->getActiveSheet()->mergeCells("K{$before_row}:K{$row}");

	$first_row = $row + 1;

	foreach ($arr_data as $time => $data)
	{
	  $row += 1;

	  $phpexcel->getActiveSheet()->SetCellValue("B{$row}", $time);

	  $phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	  $first_data_row = 0;
	  $count_mac = 0;

	  foreach ($data as $k => $v)
	  {
	    $row += ($k == 0) ? 0 : 1;
	    $first_data_row = ($k == 0) ? $row : $first_data_row;
	    $count_mac += ($v->mac == '') ? 0 : 1;

	    $phpexcel->getActiveSheet()->SetCellValue("C{$row}", $v->mac);

	    $phpexcel->getActiveSheet()->SetCellValue("D{$row}", $v->login);
	    $phpexcel->getActiveSheet()->SetCellValue("E{$row}", $v->data);
	    $phpexcel->getActiveSheet()->SetCellValue("F{$row}", "=IF(D{$row} > 0,E{$row}/D{$row}, 0)");

	    $phpexcel->getActiveSheet()->getStyle("F{$row}")->getNumberFormat()->applyFromArray(array(
	      'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	    ));

	    $phpexcel->getActiveSheet()->SetCellValue("G{$row}", $v->confirm);
	    $phpexcel->getActiveSheet()->SetCellValue("H{$row}", "=IF(E{$row} > 0, G{$row}/E{$row}, 0)");

	    $phpexcel->getActiveSheet()->getStyle("H{$row}")->getNumberFormat()->applyFromArray(array(
	      'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	    ));

	    $phpexcel->getActiveSheet()->SetCellValue("I{$row}", $v->success);
	    $phpexcel->getActiveSheet()->SetCellValue("J{$row}", "=IF(G{$row} > 0, I{$row}/G{$row}, 0)");

	    $phpexcel->getActiveSheet()->getStyle("J{$row}")->getNumberFormat()->applyFromArray(array(
	      'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	    ));

	    $phpexcel->getActiveSheet()->SetCellValue("K{$row}", "=IF(D{$row} > 0, I{$row}/D{$row}, 0)");

	    $phpexcel->getActiveSheet()->getStyle("K{$row}")->getNumberFormat()->applyFromArray(array(
	      'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	    ));
	  }

	}

	$last_row = $row;

	$row += 1;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'TOTAL');

	$phpexcel->getActiveSheet()->mergeCells("B{$row}:C{$row}");

	$phpexcel->getActiveSheet()->SetCellValue("D{$row}", "=SUM(D{$first_row}:D{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("E{$row}", "=SUM(E{$first_row}:E{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("G{$row}", "=SUM(G{$first_row}:G{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("I{$row}", "=SUM(I{$first_row}:I{$last_row})");

	$row += 1;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'AVERAGE');

	$phpexcel->getActiveSheet()->mergeCells("B{$row}:C{$row}");

	$phpexcel->getActiveSheet()->SetCellValue("D{$row}", "=AVERAGE(D{$first_row}:D{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("E{$row}", "=AVERAGE(E{$first_row}:E{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("F{$row}", "=AVERAGE(F{$first_row}:F{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("F{$row}")->getNumberFormat()->applyFromArray(array(
	  'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	$phpexcel->getActiveSheet()->SetCellValue("G{$row}", "=AVERAGE(G{$first_row}:G{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("G{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("H{$row}", "=AVERAGE(H{$first_row}:H{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("H{$row}")->getNumberFormat()->applyFromArray(array(
	  'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	$phpexcel->getActiveSheet()->SetCellValue("I{$row}", "=AVERAGE(I{$first_row}:I{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("I{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("J{$row}", "=AVERAGE(J{$first_row}:J{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("J{$row}")->getNumberFormat()->applyFromArray(array(
	  'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	$phpexcel->getActiveSheet()->SetCellValue("K{$row}", "=AVERAGE(K{$first_row}:K{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("K{$row}")->getNumberFormat()->applyFromArray(array(
	  'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	/*========== End First Sheets ==========*/

	/*========== Second Sheets ==========*/

	$phpexcel->createSheet();
	$phpexcel->setActiveSheetIndex(1);
	$phpexcel->getActiveSheet()->setTitle('Unique');

	$row = 2;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", $merchant_name);

	$next_row = $row + 1;
	$phpexcel->getActiveSheet()->mergeCells("B{$row}:M{$next_row}");
	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$row += 2;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'Jam');
	$phpexcel->getActiveSheet()->SetCellValue("C{$row}", 'T MAC');
	$phpexcel->getActiveSheet()->SetCellValue("D{$row}", 'JT MAC');
	$phpexcel->getActiveSheet()->SetCellValue("E{$row}", 'J Mac');
	$phpexcel->getActiveSheet()->SetCellValue("F{$row}", '0 Mac');
	$phpexcel->getActiveSheet()->SetCellValue("G{$row}", 'Rate 5');
	$phpexcel->getActiveSheet()->SetCellValue("H{$row}", '> 0 Mac');
	$phpexcel->getActiveSheet()->SetCellValue("I{$row}", 'Rate 6');
	$phpexcel->getActiveSheet()->SetCellValue("J{$row}", '1 Mac');
	$phpexcel->getActiveSheet()->SetCellValue("K{$row}", 'Rate 7');
	$phpexcel->getActiveSheet()->SetCellValue("L{$row}", '> 1');
	$phpexcel->getActiveSheet()->SetCellValue("M{$row}", 'Rate 8');

	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("C{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("D{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("E{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("F{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("G{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("H{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("I{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("J{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("K{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("L{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("M{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);



	$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("C{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("D{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("E{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("F{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("G{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("H{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("I{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("J{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("K{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("L{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	$phpexcel->getActiveSheet()->getStyle("M{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


	$row += 1;
	$before_row = $row - 1;
	$phpexcel->getActiveSheet()->mergeCells("B{$before_row}:B{$row}");
	$phpexcel->getActiveSheet()->mergeCells("C{$before_row}:C{$row}");
	$phpexcel->getActiveSheet()->mergeCells("D{$before_row}:D{$row}");
	$phpexcel->getActiveSheet()->mergeCells("E{$before_row}:E{$row}");
	$phpexcel->getActiveSheet()->mergeCells("F{$before_row}:F{$row}");
	$phpexcel->getActiveSheet()->mergeCells("G{$before_row}:G{$row}");
	$phpexcel->getActiveSheet()->mergeCells("H{$before_row}:H{$row}");
	$phpexcel->getActiveSheet()->mergeCells("I{$before_row}:I{$row}");
	$phpexcel->getActiveSheet()->mergeCells("J{$before_row}:J{$row}");
	$phpexcel->getActiveSheet()->mergeCells("K{$before_row}:K{$row}");
	$phpexcel->getActiveSheet()->mergeCells("L{$before_row}:L{$row}");
	$phpexcel->getActiveSheet()->mergeCells("M{$before_row}:M{$row}");


	$first_row = $row + 1;

	foreach ($arr_data as $time => $data)
	{
	  $row += 1;

	  $phpexcel->getActiveSheet()->SetCellValue("B{$row}", $time);

	  $phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	  $first_data_row = 0;
	  $count_mac = 0;
		$first2_data_row = 0;
	  $data_0 = 0;
		$first3_data_row = 0;
	  $data_M0 = 0;
		$first4_data_row = 0;
	  $data_1 = 0;
		$first5_data_row = 0;
	  $data_M1 = 0;

	  foreach ($data as $k => $v)
	  {

	    $first_data_row = ($k == 0) ? $row : $first_data_row;
	    $count_mac += ($v->mac == '') ? 0 : 1;
			$first2_data_row = ($k == 0) ? $row : $first2_data_row;
	    $data_0 += ($v->mac != '' && $v->data == 0) ? 1 : 0	;
			$first3_data_row = ($k == 0) ? $row : $first3_data_row;
	    $data_M0 += ($v->mac != '' && $v->data > 0) ? 1 : 0	;
			$first4_data_row = ($k == 0) ? $row : $first4_data_row;
	    $data_1 += ($v->mac != '' && $v->data == 1) ? 1 : 0	;
			$first5_data_row = ($k == 0) ? $row : $first5_data_row;
	    $data_M1 += ($v->mac != '' && $v->data > 1) ? 1 : 0	;

			$phpexcel->getActiveSheet()->SetCellValue("D{$row}", "=IF(C{$row}=0,1,\"\")");
			$phpexcel->getActiveSheet()->SetCellValue("E{$row}", "=IF(C{$row}<>0,1,\"\")");
			//Rate 5
			$phpexcel->getActiveSheet()->SetCellValue("G{$row}", "=IF(C{$row}<>\"\", F{$row}/C{$row}, \"\")");

			$phpexcel->getActiveSheet()->getStyle("G{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));
			//Rate 6
			$phpexcel->getActiveSheet()->SetCellValue("I{$row}", "=IF(C{$row}<>\"\", H{$row}/C{$row}, \"\")");

			$phpexcel->getActiveSheet()->getStyle("I{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));
			//Rate 7
			$phpexcel->getActiveSheet()->SetCellValue("K{$row}", "=IF(C{$row}<>\"\", J{$row}/H{$row}, \"\")");

			$phpexcel->getActiveSheet()->getStyle("K{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));
			//Rate 7
			$phpexcel->getActiveSheet()->SetCellValue("K{$row}", "=IF(OR(H{$row}<>\"\",J{$row}<>\"\"),J{$row}/H{$row},\"\")");

			$phpexcel->getActiveSheet()->getStyle("K{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));
			//Rate 8
			$phpexcel->getActiveSheet()->SetCellValue("M{$row}", "=IF(OR(H{$row}<>\"\",L{$row}<>\"\"),L{$row}/H{$row},\"\")");

			$phpexcel->getActiveSheet()->getStyle("M{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));

			// Excel Style
			$phpexcel->getActiveSheet()->getStyle("D{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("E{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("F{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("G{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("H{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("I{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("J{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("K{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("L{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("M{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$phpexcel->getActiveSheet()->getStyle("N{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			if ($count_mac != '') {
				$phpexcel->getActiveSheet()->SetCellValue("C{$first_data_row}",$count_mac);
			}
			else {
				$phpexcel->getActiveSheet()->SetCellValue("C{$first_data_row}",'');
			}
			if ($data_0 != '') {
				$phpexcel->getActiveSheet()->SetCellValue("F{$first2_data_row}", $data_0);
			}
			else {
				$phpexcel->getActiveSheet()->SetCellValue("F{$first2_data_row}", '');
			}
			if ($data_M0 != '') {
					$phpexcel->getActiveSheet()->SetCellValue("H{$first3_data_row}", $data_M0);
			}
			else {
				$phpexcel->getActiveSheet()->SetCellValue("H{$first3_data_row}", '');
			}
			if ($data_1 != '') {
				$phpexcel->getActiveSheet()->SetCellValue("J{$first4_data_row}", $data_1);
			}
			else {
				$phpexcel->getActiveSheet()->SetCellValue("J{$first4_data_row}", '');
			}
			if ($data_M1 != '') {
				$phpexcel->getActiveSheet()->SetCellValue("L{$first5_data_row}", $data_M1);
			}
			else {
				$phpexcel->getActiveSheet()->SetCellValue("L{$first5_data_row}", '');
			}
	  }

	  $phpexcel->getActiveSheet()->getStyle("C{$first_data_row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	}

	$last_row = $row;

	$row += 1;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'TOTAL');

	$phpexcel->getActiveSheet()->SetCellValue("C{$row}", "=SUM(C{$first_row}:C{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("D{$row}", "=SUM(D{$first_row}:D{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("E{$row}", "=SUM(E{$first_row}:E{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("F{$row}", "=SUM(F{$first_row}:F{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("H{$row}", "=SUM(H{$first_row}:H{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("J{$row}", "=SUM(J{$first_row}:J{$last_row})");
	$phpexcel->getActiveSheet()->SetCellValue("L{$row}", "=SUM(L{$first_row}:L{$last_row})");

	$row += 1;

	$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'AVERAGE');

	$last_row2 = $row - 1;
	$phpexcel->getActiveSheet()->SetCellValue("C{$row}", "=AVERAGE(C{$last_row2}/E{$last_row2})");

	$phpexcel->getActiveSheet()->getStyle("C{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("G{$row}", "=AVERAGE(G{$first_row}:G{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("G{$row}")->getNumberFormat()->applyFromArray(array(
		'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	$phpexcel->getActiveSheet()->SetCellValue("H{$row}", "=AVERAGE(H{$first_row}:H{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("H{$row}")->getNumberFormat()->setFormatCode('0.00');


	$phpexcel->getActiveSheet()->SetCellValue("I{$row}", "=AVERAGE(I{$first_row}:I{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("I{$row}")->getNumberFormat()->applyFromArray(array(
		'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	$phpexcel->getActiveSheet()->SetCellValue("J{$row}", "=AVERAGE(J{$first_row}:J{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("J{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("K{$row}", "=AVERAGE(K{$first_row}:K{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("K{$row}")->getNumberFormat()->applyFromArray(array(
		'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	$phpexcel->getActiveSheet()->SetCellValue("L{$row}", "=AVERAGE(L{$first_row}:L{$last_row})");
	$phpexcel->getActiveSheet()->getStyle("L{$row}")->getNumberFormat()->setFormatCode('0.00');

	$phpexcel->getActiveSheet()->SetCellValue("M{$row}", "=AVERAGE(M{$first_row}:M{$last_row})");

	$phpexcel->getActiveSheet()->getStyle("M{$row}")->getNumberFormat()->applyFromArray(array(
		'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
	));

	/*========== End Second Sheets ==========*/

	$writer = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$merchant_name.' MAC - '.date('Y-m-d', $post_date).'.xls"');
	header('Cache-Control: max-age=0');
	$writer->save('php://output');
	?>
