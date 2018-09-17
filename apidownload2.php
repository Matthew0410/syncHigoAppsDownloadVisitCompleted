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
	$from_date = $_GET['date'];

	$arr_from_date = getdate($from_date);

	$from = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr_from_date['mon'], 1, $arr_from_date['year']));
	$to = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr_from_date['mon'] + 1, 0, $arr_from_date['year']));

	$month = date('F', $from_date);
	$year = date('Y', $from_date);

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

	require('phpexcel/PHPExcel.php');

	$phpexcel = new PHPExcel();

	$phpexcel->setActiveSheetIndex(0);
	$phpexcel->getActiveSheet()->setTitle('Non Unique');

	write_excel($phpexcel, $merchant_name, $arr_data);

	$sql_login2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
	$sql_login2 .= 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, mac ';
	$sql_login2 .= 'FROM login ';
	$sql_login2 .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
	$sql_login2 .= 'GROUP BY date_format, mac) l ';
	$sql_login2 .= 'GROUP BY date_format';
	$arr_login2 = (query_select($default->mysqli, $sql_login2)) ? query_select($default->mysqli, $sql_login2) : array();

	$arr_login2_lookup = array();

	foreach ($arr_login2 as $login2)
	{
		$arr_login2_lookup[$login2->date_format] = $login2->count_data;
	}

	$sql_log2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
	$sql_log2 .= 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, mac ';
	$sql_log2 .= 'FROM `log` ';
	$sql_log2 .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
	$sql_log2 .= 'GROUP BY date_format, mac) l ';
	$sql_log2 .= 'GROUP BY date_format';
	$arr_log2 = (query_select($default->mysqli, $sql_log2)) ? query_select($default->mysqli, $sql_log2) : array();

	$arr_log2_lookup = array();

	foreach ($arr_log2 as $log2)
	{
		$arr_log2_lookup[$log2->date_format] = clone $log2;
	}

	$sql_confirm2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
	$sql_confirm2 .= 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, mac ';
	$sql_confirm2 .= 'FROM confirmation_page ';
	$sql_confirm2 .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
	$sql_confirm2 .= 'GROUP BY date_format, mac) l ';
	$sql_confirm2 .= 'GROUP BY date_format';
	$arr_confirm2 = (query_select($default->mysqli, $sql_confirm2)) ? query_select($default->mysqli, $sql_confirm2) : array();

	$arr_confirm2_lookup = array();

	foreach ($arr_confirm2 as $confirm2)
	{
		$arr_confirm2_lookup[$confirm2->date_format] = $confirm2->count_data;
	}

	$sql_alogin2 = 'SELECT date_format, COUNT(date_format) AS count_data FROM ( ';
	$sql_alogin2 .= 'SELECT DATE_FORMAT(`date`, "%Y-%m-%d") AS `date_format`, mac ';
	$sql_alogin2 .= 'FROM alogin ';
	$sql_alogin2 .= "WHERE higo_router_id = {$higo_router_id} AND date >= '{$from}' AND date <= '{$to}' ";
	$sql_alogin2 .= 'GROUP BY date_format, mac) l ';
	$sql_alogin2 .= 'GROUP BY date_format';
	$arr_alogin2 = (query_select($default->mysqli, $sql_alogin2)) ? query_select($default->mysqli, $sql_alogin2) : array();

	$arr_alogin2_lookup = array();

	foreach ($arr_alogin2 as $alogin2)
	{
		$arr_alogin2_lookup[$alogin2->date_format] = $alogin2->count_data;
	}

	$arr_data2 = array();

	foreach ($arr_date as $date)
	{
		$data = new stdClass();
		$data->date = date('Y-m-d', $date);
		$data->date_timestamp = $date;
		$data->login = (isset($arr_login2_lookup[date('Y-m-d', $date)])) ? $arr_login2_lookup[date('Y-m-d', $date)] : 0;
		$data->data = (isset($arr_log2_lookup[date('Y-m-d', $date)])) ? $arr_log2_lookup[date('Y-m-d', $date)]->count_data : 0;
		$data->confirm = (isset($arr_confirm2_lookup[date('Y-m-d', $date)])) ? $arr_confirm2_lookup[date('Y-m-d', $date)] : 0;
		$data->success = (isset($arr_alogin2_lookup[date('Y-m-d', $date)])) ? $arr_alogin2_lookup[date('Y-m-d', $date)] : 0;
		$arr_data2[] = clone $data;
	}

	$phpexcel->createSheet();
	$phpexcel->setActiveSheetIndex(1);
	$phpexcel->getActiveSheet()->setTitle('Unique');

	write_excel($phpexcel, $merchant_name, $arr_data2);

	$phpexcel->setActiveSheetIndex(0);

	$writer = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$merchant_name.' - '.$month.' '.$year.'.xls"');
	header('Cache-Control: max-age=0');
	$writer->save('php://output');

	function write_excel($phpexcel, $merchant_name, $arr_data)
	{
		$row = 2;

		$phpexcel->getActiveSheet()->SetCellValue("B{$row}", $merchant_name);

		$next_row = $row + 1;
		$phpexcel->getActiveSheet()->mergeCells("B{$row}:J{$next_row}");
		$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

		$row += 2;

		$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'Tanggal');
		$phpexcel->getActiveSheet()->SetCellValue("C{$row}", 'PUB');
		$phpexcel->getActiveSheet()->SetCellValue("D{$row}", 'Data');
		$phpexcel->getActiveSheet()->SetCellValue("E{$row}", 'Rate 1');
		$phpexcel->getActiveSheet()->SetCellValue("F{$row}", 'TVC');
		$phpexcel->getActiveSheet()->SetCellValue("G{$row}", 'Rate 2');
		$phpexcel->getActiveSheet()->SetCellValue("H{$row}", 'SP');
		$phpexcel->getActiveSheet()->SetCellValue("I{$row}", 'Rate 3');
		$phpexcel->getActiveSheet()->SetCellValue("J{$row}", 'Rate 4');

		$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("C{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("D{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("E{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("F{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("G{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("H{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("I{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("J{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("C{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("D{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("E{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("F{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("G{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("H{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("I{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$phpexcel->getActiveSheet()->getStyle("J{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

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

		$first_row = $row + 1;

		foreach ($arr_data as $data)
		{
			$row += 1;

			$phpexcel->getActiveSheet()->SetCellValue("B{$row}", date('d', strtotime($data->date)));

			$phpexcel->getActiveSheet()->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$phpexcel->getActiveSheet()->SetCellValue("C{$row}", $data->login);
			$phpexcel->getActiveSheet()->SetCellValue("D{$row}", $data->data);
			$phpexcel->getActiveSheet()->SetCellValue("E{$row}", "=IF(C{$row} > 0, D{$row}/C{$row}, 0)");

			$phpexcel->getActiveSheet()->getStyle("E{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));

			$phpexcel->getActiveSheet()->SetCellValue("F{$row}", $data->confirm);
			$phpexcel->getActiveSheet()->SetCellValue("G{$row}", "=IF(D{$row} > 0, F{$row}/D{$row}, 0)");

			$phpexcel->getActiveSheet()->getStyle("G{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));

			$phpexcel->getActiveSheet()->SetCellValue("H{$row}", $data->success);
			$phpexcel->getActiveSheet()->SetCellValue("I{$row}", "=IF(F{$row} > 0, H{$row}/F{$row}, 0)");

			$phpexcel->getActiveSheet()->getStyle("I{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));

			$phpexcel->getActiveSheet()->SetCellValue("J{$row}", "=IF(C{$row} > 0, H{$row}/C{$row}, 0)");

			$phpexcel->getActiveSheet()->getStyle("J{$row}")->getNumberFormat()->applyFromArray(array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
			));

			$week = date('D', strtotime($data->date));

			if ($week != 'Sat' && $week != 'Sun')
			{
				$phpexcel->getActiveSheet()->getStyle("B{$row}:J{$row}")->applyFromArray(array(
					'font' => array('color' => array('rgb' => '000000'))
				));
			}
			else
			{

				$phpexcel->getActiveSheet()->getStyle("B{$row}:J{$row}")->applyFromArray(array(
					'font' => array('color' => array('rgb' => 'FF0000'))
				));
			}
		}

		$last_row = $row;

		$row += 1;

		$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'TOTAL');
		$phpexcel->getActiveSheet()->SetCellValue("C{$row}", "=SUM(C{$first_row}:C{$last_row})");
		$phpexcel->getActiveSheet()->SetCellValue("D{$row}", "=SUM(D{$first_row}:D{$last_row})");
		$phpexcel->getActiveSheet()->SetCellValue("F{$row}", "=SUM(F{$first_row}:F{$last_row})");
		$phpexcel->getActiveSheet()->SetCellValue("H{$row}", "=SUM(H{$first_row}:H{$last_row})");

		$row += 1;

		$phpexcel->getActiveSheet()->SetCellValue("B{$row}", 'AVERAGE');
		$phpexcel->getActiveSheet()->SetCellValue("C{$row}", "=AVERAGE(C{$first_row}:C{$last_row})");

		$phpexcel->getActiveSheet()->getStyle("C{$row}")->getNumberFormat()->setFormatCode('0.00');

		$phpexcel->getActiveSheet()->SetCellValue("D{$row}", "=AVERAGE(D{$first_row}:D{$last_row})");

		$phpexcel->getActiveSheet()->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.00');

		$phpexcel->getActiveSheet()->SetCellValue("E{$row}", "=AVERAGE(E{$first_row}:E{$last_row})");

		$phpexcel->getActiveSheet()->getStyle("E{$row}")->getNumberFormat()->applyFromArray(array(
			'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
		));

		$phpexcel->getActiveSheet()->SetCellValue("F{$row}", "=AVERAGE(F{$first_row}:F{$last_row})");

		$phpexcel->getActiveSheet()->getStyle("F{$row}")->getNumberFormat()->setFormatCode('0.00');

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

		$phpexcel->getActiveSheet()->getStyle("J{$row}")->getNumberFormat()->applyFromArray(array(
			'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE
		));
	}
?>
