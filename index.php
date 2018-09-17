<?php
	set_time_limit(0);

	$arr_now = getdate();
	$current_month = mktime(0, 0, 0, $arr_now['mon'], 1, $arr_now['year']);

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
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="jquery.mobile-1.4.5.min.css">
		<link type="text/css" rel="stylesheet" href="patternLock.min.css">
		<style type="text/css">
			.rate-color {
				background: #99badd;
			}
		</style>
		<script type="text/javascript" src="jquery-1.11.1.min.js"></script>
		<script type="text/javascript" src="jquery.mobile-1.4.5.min.js"></script>
		<script type="text/javascript" src="patternLock.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				var lock = new PatternLock('#patternHolder', {
					matrix: [5, 5],
					onDraw: function(pattern) {
						if (lock.getPattern() == '9,8,7,12,17,18,19,14,13') {
							var arrHigoRouter = <?php echo json_encode($arr_higo_router); ?>;

							$.each(arrHigoRouter, function(k, higoRouter) {
								var merchantList = '<li class="m-list" onclick="loadMerchantData('+higoRouter.id+",'"+higoRouter.name+"','"+<?php echo $current_month; ?>+"'"+')" data-filtertext="'+higoRouter.name+'"><a href="#">'+higoRouter.name+'</a></li>';

								$('#merchant-list').append(merchantList);
								$('#merchant-list').listview('refresh');
							});

							$('#patternHolder').hide();
							$('#merchant-listview').show();
						}
						else {
							lock.error();
						}
					}
				});
			});

			function back() {
				$('#merchant-dataview').hide();
				$('#merchant-listview').show();
			}

			function back2() {
				$('#merchant-dataview2').hide();
				$('#merchant-dataview').show();
			}

			function back3() {
				$('#merchant-unique-dataview').hide();
				$('#merchant-dataview').show();
			}

			function back4() {
				$('#merchant-unique-dataview2').hide();
				$('#merchant-unique-dataview').show();
			}

			function back5() {
				$('#merchant-macaddress-dataview').hide();
				$('#merchant-dataview2').show();
			}

			function back6() {
				$('#merchant-user-dataview').hide();
				$('#merchant-dataview2').show();
			}

			function back7() {
				$('#merchant-browsing-history-dataview').hide();
				$('#merchant-dataview2').show();
			}

			function loadMerchantData(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-listview').hide();
						$('#merchant-dataview').show();

						$('#merchant-name').html(data.merchant_name);
						$('#month').html(data.month);

						$('#merchant-data').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var dataRate = (higoData.login > 0) ? Math.round((higoData.data / higoData.login) * 100) : 0;
							var confirmRate = (higoData.data > 0) ? Math.round((higoData.confirm / higoData.data) * 100) : 0;
							var successRate = (higoData.confirm > 0) ? Math.round((higoData.success / higoData.confirm) * 100) : 0;

							var merchantData = '<tr id="merchant-'+higoData.date_timestamp+'" style="cursor: pointer;"><td style="border: 1px #000 solid;">'+higoData.date+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.login+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.data+'</td>';
							merchantData += '<td class="rate-color" style="border: 1px #000 solid;">'+dataRate+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.confirm+'</td>';
							merchantData += '<td class="rate-color" style="border: 1px #000 solid;">'+confirmRate+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.success+'</td>';
							merchantData += '<td class="rate-color" style="border: 1px #000 solid;">'+successRate+'</td>';
							merchantData += '</tr>';
							$('#merchant-data').append(merchantData);

							$('#merchant-'+higoData.date_timestamp).unbind('click');

							$('#merchant-'+higoData.date_timestamp).click(function() {
								loadMerchantData2(data.higo_router_id, data.merchant_name, higoData.date_timestamp);
							});
						});

						$('#prev-month').unbind('click');
						$('#next-month').unbind('click');
						$('#merchant-unique').unbind('click');
						$('#merchant-download').unbind('click');
						$('#merchant-download-all').unbind('click');
						$('#merchant-download-mac').unbind('click');
						$('#merchant-download-mac').unbind('click');

						$('#prev-month').click(function() {
							loadMerchantData(data.higo_router_id, data.merchant_name, data.prev_month);
						});

						$('#next-month').click(function() {
							loadMerchantData(data.higo_router_id, data.merchant_name, data.next_month);
						});

						$('#merchant-data-table').table('refresh');

						$('#merchant-unique').click(function() {
							loadMerchantUniqueData(higoRouterId, merchantName, date);
						});

						$('#merchant-download').click(function() {
							merchantDownload(higoRouterId, merchantName, date);
						});

						$('#merchant-download-all').click(function() {
							merchantDownloadAll(date);
						});

						$('#merchant-download-mac').click(function() {
							merchantDownloadMac(higoRouterId, merchantName, date);
						});

						$('#merchant-download-all-mac').click(function(){
							merchantDownloadAllMac(higoRouterId, merchantName, date)
						});

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/api.php'
				});
			}

			function loadMerchantData2(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-listview').hide();
						$('#merchant-dataview').hide();
						$('#merchant-dataview2').show();

						$('#merchant-name2').html(data.merchant_name);
						$('#date').html(data.date);

						$('#merchant-data2').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var dataRate = (higoData.login > 0) ? Math.round((higoData.data / higoData.login) * 100) : 0;
							var confirmRate = (higoData.data > 0) ? Math.round((higoData.confirm / higoData.data) * 100) : 0;
							var successRate = (higoData.confirm > 0) ? Math.round((higoData.success / higoData.confirm) * 100) : 0;

							var merchantData2 = '<tr>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.time+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.login+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.data+'</td>';
							merchantData2 += '<td class="rate-color" style="border: 1px #000 solid;">'+dataRate+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.confirm+'</td>';
							merchantData2 += '<td class="rate-color" style="border: 1px #000 solid;">'+confirmRate+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.success+'</td>';
							merchantData2 += '<td class="rate-color" style="border: 1px #000 solid;">'+successRate+'</td>';
							merchantData2 += '</tr>';
							$('#merchant-data2').append(merchantData2);
						});

						$('#prev-date').unbind('click');
						$('#next-date').unbind('click');
						$('#mac-address').unbind('click');
						$('#user').unbind('click');
						$('#browsing-history').unbind('click');
						$('#merchant-download2').unbind('click');

						$('#prev-date').click(function() {
							loadMerchantData2(data.higo_router_id, data.merchant_name, data.prev_date);
						});

						$('#next-date').click(function() {
							loadMerchantData2(data.higo_router_id, data.merchant_name, data.next_date);
						});

						$('#mac-address').click(function() {
							loadMerchantMacAddressData(data.higo_router_id, data.merchant_name, date);
						});

						$('#user').click(function() {
							loadMerchantUserData(data.higo_router_id, data.merchant_name, date);
						});

						$('#browsing-history').click(function() {
							loadMerchantBrowsingHistoryData(data.higo_router_id, data.merchant_name, date);
						});

						$('#merchant-download2').click(function() {
							merchantDownload2(data.higo_router_id, data.merchant_name, date);
						});

						$('#merchant-download2-mac').click(function() {
							merchantDownload2Mac(data.higo_router_id, data.merchant_name, date);
						});

						$('#merchant-data-table2').table('refresh');

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/api2.php'
				});
			}

			function loadMerchantMacAddressData(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-dataview2').hide();
						$('#merchant-macaddress-dataview').show();

						$('#merchant-macaddress-name').html(data.merchant_name);
						$('#merchant-macaddress-date').html(data.date);

						$('#merchant-macaddress-data').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var merchantData = '<tr onclick="showMacAddress(this, '+e+');">';
							merchantData += '<td style="border: 1px #000 solid; background: #7F7FFF; cursor: pointer; font-weight: bold;">'+higoData.mac+' ('+higoData.location_count+' Lokasi)</td>';
							merchantData += '</tr>';

							$.each(higoData.arr_higo_router, function(key, higoRouter) {
								merchantData += '<tr class="mac-address-'+e+'" style="cursor: pointer; display: none;" onclick="showMacAddress2(this, \''+e+'-'+key+'\');">';
								merchantData += '<td style="border: 1px #000 solid; background: #CCCCFF; padding-left: 50px;">'+higoRouter.name+' ('+higoRouter.time_count+' kali)</td>';
								merchantData += '</tr>';

								$.each(higoRouter.arr_date, function(key2, higoRouterDate) {
									merchantData += '<tr class="mac-address-parent-'+e+' mac-address2-'+e+'-'+key+'" style="display: none;">';
									merchantData += '<td style="border: 1px #000 solid; padding-left: 100px;">'+higoRouterDate+'</td>';
									merchantData += '</tr>';
								});
							});

							$('#merchant-macaddress-data').append(merchantData);
						});

						$('#merchant-macaddress-prev-date').unbind('click');
						$('#merchant-macaddress-next-date').unbind('click');

						$('#merchant-macaddress-prev-date').click(function() {
							loadMerchantMacAddressData(data.higo_router_id, data.merchant_name, data.prev_date);
						});

						$('#merchant-macaddress-next-date').click(function() {
							loadMerchantMacAddressData(data.higo_router_id, data.merchant_name, data.next_date);
						});

						$('#merchant-macaddress-data-table').table('refresh');

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/apimac.php'
				});
			}

			function loadMerchantUserData(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-dataview2').hide();
						$('#merchant-user-dataview').show();

						$('#merchant-user-name').html(data.merchant_name);
						$('#merchant-user-date').html(data.date);

						$('#merchant-user-data').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var merchantData = '<tr>';
							merchantData += '<td style="border: 1px #000 solid;"><img src="'+higoData.image+'" style="height: 180px; width: 180px;"></td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.type+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">';

							var dataProfile = [];

							if (higoData.socmed_id != '') {
								dataProfile.push('Sosmed ID: '+higoData.socmed_id);
							}

							if (higoData.name != '') {
								dataProfile.push('Name: '+higoData.name);
							}

							if (higoData.email != '') {
								dataProfile.push('Email: '+higoData.email);
							}

							if (higoData.gender != '') {
								dataProfile.push('Gender: '+higoData.gender);
							}

							if (higoData.phone != '') {
								dataProfile.push('Phone: '+higoData.phone);
							}

							if (higoData.birthday != '') {
								dataProfile.push('Birthday: '+higoData.birthday);
							}

							merchantData += dataProfile.join('<br>');
							merchantData += '</td>';
							merchantData += '<td style="border: 1px #000 solid;">';

							var dataFollower = [];

							if (higoData.type == 'twitter' || higoData.type == 'instagram') {
								dataFollower.push('Username: '+higoData.username);
							}

							if (higoData.type == 'twitter' || higoData.type == 'instagram') {
								dataFollower.push('Followers: '+higoData.followers_count);
							}

							if (higoData.type == 'twitter' || higoData.type == 'instagram') {
								dataFollower.push('Friends: '+higoData.friends_count);
							}

							merchantData += dataFollower.join('<br>');
							merchantData += '</td>';
							merchantData += '</tr>';

							$('#merchant-user-data').append(merchantData);
						});

						$('#merchant-user-prev-date').unbind('click');
						$('#merchant-user-next-date').unbind('click');

						$('#merchant-user-prev-date').click(function() {
							loadMerchantUserData(data.higo_router_id, data.merchant_name, data.prev_date);
						});

						$('#merchant-user-next-date').click(function() {
							loadMerchantUserData(data.higo_router_id, data.merchant_name, data.next_date);
						});

						$('#merchant-user-data-table').table('refresh');

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/apiuser.php'
				});
			}

			function loadMerchantBrowsingHistoryData(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-dataview2').hide();
						$('#merchant-browsing-history-dataview').show();

						$('#merchant-browsing-history-name').html(data.merchant_name);
						$('#merchant-browsing-history-date').html(data.date);

						$('#merchant-browsing-history-data').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var merchantData = '<tr>';
							merchantData += '<td style="border: 1px #000 solid; background: #7F7FFF; font-weight: bold;" colspan="3">'+e+'</td>';
							merchantData += '</tr>';

							$.each(higoData, function(key, dataSearch) {
								merchantData += '<tr>';
								merchantData += '<td style="border: 1px #000 solid;">'+dataSearch.time+'</td>';
								merchantData += '<td style="border: 1px #000 solid;">';
								merchantData += dataSearch.url;
								merchantData += '<br><br>';
								merchantData += dataSearch.webshrink_category;
								merchantData += '<br>';
								merchantData += dataSearch.webshrink_category2;
								merchantData += '<br>';
								merchantData += dataSearch.webshrink_category3;
								merchantData += '<br>';
								merchantData += dataSearch.webshrink_category4;
								merchantData += '<br>';
								merchantData += dataSearch.webshrink_category5;
								merchantData += '<br>';
								merchantData += dataSearch.webshrink_category6;
								merchantData += '</td>';
								merchantData += '</tr>';
							});

							$('#merchant-browsing-history-data').append(merchantData);
						});

						$('#merchant-browsing-history-prev-date').unbind('click');
						$('#merchant-browsing-history-next-date').unbind('click');

						$('#merchant-browsing-history-prev-date').click(function() {
							loadMerchantBrowsingHistoryData(data.higo_router_id, data.merchant_name, data.prev_date);
						});

						$('#merchant-browsing-history-next-date').click(function() {
							loadMerchantBrowsingHistoryData(data.higo_router_id, data.merchant_name, data.next_date);
						});

						$('#merchant-browsing-history-data-table').table('refresh');

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/apibrowsinghistory.php'
				});
			}

			function loadMerchantUniqueData(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-dataview').hide();
						$('#merchant-unique-dataview').show();

						$('#merchant-unique-name').html(data.merchant_name+' (Unique By MAC)');
						$('#month2').html(data.month);

						$('#merchant-unique-data').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var dataRate = (higoData.login > 0) ? Math.round((higoData.data / higoData.login) * 100) : 0;
							var confirmRate = (higoData.data > 0) ? Math.round((higoData.confirm / higoData.data) * 100) : 0;
							var successRate = (higoData.confirm > 0) ? Math.round((higoData.success / higoData.confirm) * 100) : 0;

							var merchantData = '<tr id="merchant-unique-'+higoData.date_timestamp+'" style="cursor: pointer;"><td style="border: 1px #000 solid;">'+higoData.date+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.login+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.data+'</td>';
							merchantData += '<td class="rate-color" style="border: 1px #000 solid;">'+dataRate+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.confirm+'</td>';
							merchantData += '<td class="rate-color" style="border: 1px #000 solid;">'+confirmRate+'</td>';
							merchantData += '<td style="border: 1px #000 solid;">'+higoData.success+'</td>';
							merchantData += '<td class="rate-color" style="border: 1px #000 solid;">'+successRate+'</td>';
							merchantData += '</tr>';
							$('#merchant-unique-data').append(merchantData);

							$('#merchant-unique-'+higoData.date_timestamp).unbind('click');

							$('#merchant-unique-'+higoData.date_timestamp).click(function() {
								loadMerchantUniqueData2(data.higo_router_id, data.merchant_name, higoData.date_timestamp);
							});
						});

						$('#prev-month2').unbind('click');
						$('#next-month2').unbind('click');

						$('#prev-month2').click(function() {
							loadMerchantUniqueData(data.higo_router_id, data.merchant_name, data.prev_month);
						});

						$('#next-month2').click(function() {
							loadMerchantUniqueData(data.higo_router_id, data.merchant_name, data.next_month);
						});

						$('#merchant-unique-data-table').table('refresh');

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/api3.php'
				});
			}

			function loadMerchantUniqueData2(higoRouterId, merchantName, date) {
				$('#loading').show();

				$.ajax({
					data: {
						id: higoRouterId,
						name: merchantName,
						date: date
					},
					dataType: 'JSON',
					error: function() {
						alert('Server Error');
						$('#loading').hide();
					},
					success: function(data) {
						if (!data.success) {
							alert(data.message);
							$('#loading').hide();

							return false;
						}

						$('#merchant-unique-dataview').hide();
						$('#merchant-unique-dataview2').show();

						$('#merchant-unique-name2').html(data.merchant_name+' (Unique By MAC)');
						$('#date2').html(data.date);

						$('#merchant-unique-data2').children().remove();

						$.each(data.arr_data, function(e, higoData) {
							var dataRate = (higoData.login > 0) ? Math.round((higoData.data / higoData.login) * 100) : 0;
							var confirmRate = (higoData.data > 0) ? Math.round((higoData.confirm / higoData.data) * 100) : 0;
							var successRate = (higoData.confirm > 0) ? Math.round((higoData.success / higoData.confirm) * 100) : 0;

							var merchantData2 = '<tr>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.time+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.login+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.data+'</td>';
							merchantData2 += '<td class="rate-color" style="border: 1px #000 solid;">'+dataRate+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.confirm+'</td>';
							merchantData2 += '<td class="rate-color" style="border: 1px #000 solid;">'+confirmRate+'</td>';
							merchantData2 += '<td style="border: 1px #000 solid;">'+higoData.success+'</td>';
							merchantData2 += '<td class="rate-color" style="border: 1px #000 solid;">'+successRate+'</td>';
							merchantData2 += '</tr>';
							$('#merchant-unique-data2').append(merchantData2);
						});

						$('#prev-date2').unbind('click');
						$('#next-date2').unbind('click');

						$('#prev-date2').click(function() {
							loadMerchantUniqueData2(data.higo_router_id, data.merchantname, data.prev_date);
						});

						$('#next-date2').click(function() {
							loadMerchantUniqueData2(data.higo_router_id, data.merchant_name, data.next_date);
						});

						$('#merchant-unique-data-table2').table('refresh');

						$('#loading').hide();
					},
					timeout: 0,
					type: 'POST',
					url: 'http://localhost/sync/api4.php'
				});
			}

			function merchantDownload(higoRouterId, merchantName, date) {
				window.location.href = 'http://localhost/sync/apidownload2.php?id='+higoRouterId+'&name='+merchantName+'&date='+date;
			}

			function merchantDownload2(higoRouterId, merchantName, date) {
				window.location.href = 'http://localhost/sync/apidownload3.php?id='+higoRouterId+'&name='+merchantName+'&date='+date;
			}

			function merchantDownloadMac(higoRouterId, merchantName, date) {
				window.location.href = 'http://localhost/sync/apidownloadmac.php?id='+higoRouterId+'&name='+merchantName+'&date='+date;
			}

			function merchantDownload2Mac(higoRouterId, merchantName, date) {
				window.location.href = 'http://localhost/sync/apidownload2mac.php?id='+higoRouterId+'&name='+merchantName+'&date='+date;
			}

			function merchantDownloadAll(date) {
				window.location.href = 'http://localhost/sync/apidownload.php?date='+date;
			}

			function merchantDownloadAllMac(higoRouterId, merchantName, date) {
				window.location.href = 'http://localhost/sync/apidownloadallmac.php?id='+higoRouterId+'&name='+merchantName+'&date='+date;
			}

			function showMacAddress(ui, unique) {
				if ($(ui).hasClass('active')) {
					$(ui).removeClass('active');
					$('.mac-address-'+unique).hide();
					$('.mac-address-parent-'+unique).hide();
				}
				else {
					$(ui).addClass('active');
					$('.mac-address-'+unique).show();
				}
			}

			function showMacAddress2(ui, unique) {
				if ($(ui).hasClass('active')) {
					$(ui).removeClass('active');
					$('.mac-address2-'+unique).hide();
				}
				else {
					$(ui).addClass('active');
					$('.mac-address2-'+unique).show();
				}
			}
		</script>
		<style media="screen">


			html {
				font-family: arial !important;
				font-size: 12px;
			}
		</style>
	</head>
	<body style="font-family: Calibri;">
		<div id="patternHolder" style="left: 50%; position: fixed; top: 50%; transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); -webkit-transform: translate(-50%, -50%);"></div>
		<div id="merchant-listview" style="display: none;">
			<div data-role="header">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="http://localhost/sync/mikrotik.php" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-cloud">Mikrotik</a>
				</div>
			</div>
			<form class="ui-filterable">
				<input id="rich-autocomplete-input" data-type="search" placeholder="Merchants...">
			</form>
			<ul id="merchant-list" data-role="listview" data-filter="true" data-inset="true" data-input="#rich-autocomplete-input">
			</ul>
		</div>
		<div id="merchant-dataview" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back" onclick="back();">Back</button>
				<h1 id="merchant-name"></h1>
				<button id="merchant-unique" class="ui-btn-right ui-btn ui-btn-inline ui-corner-all ui-btn-icon-right ui-icon-eye">Unique</button>
			</div>
			<div data-role="header">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a id="merchant-download" href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-action">Download</a>
					<a id="merchant-download-all" href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-action">Download All</a>
					<a id="merchant-download-mac" href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-action">Download MAC</a>
					<a id="merchant-download-all-mac" href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-action">Download All MAC</a>
				</div>
			</div>
			<div data-role="header">
				<button id="prev-month" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="month"></h1>
				<button id="next-month" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<table id="merchant-data-table" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid;">Tanggal</th>
						<th data-priority="2" style="border: 1px #000 solid;">PopupBox +<br>Opening Page +<br>Login Page</th>
						<th data-priority="3" style="border: 1px #000 solid;">Data</th>
						<th data-priority="4" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="5" style="border: 1px #000 solid;">Confirmation Page (TVC)</th>
						<th data-priority="6" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="7" style="border: 1px #000 solid;">Successful Page</th>
						<th data-priority="8" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
					</tr>
				</thead>
				<tbody id="merchant-data">
				</tbody>
			</table>
		</div>
		<div id="merchant-unique-dataview" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back"onclick="back3();">Back</button>
				<h1 id="merchant-unique-name"></h1>
			</div>
			<div data-role="header">
				<button id="prev-month2" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="month2"></h1>
				<button id="next-month2" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<table id="merchant-unique-data-table" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid;">Tanggal</th>
						<th data-priority="2" style="border: 1px #000 solid;">PopupBox +<br>Opening Page +<br>Login Page</th>
						<th data-priority="3" style="border: 1px #000 solid;">Data</th>
						<th data-priority="4" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="5" style="border: 1px #000 solid;">Confirmation Page (TVC)</th>
						<th data-priority="6" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="7" style="border: 1px #000 solid;">Successful Page</th>
						<th data-priority="8" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
					</tr>
				</thead>
				<tbody id="merchant-unique-data">
				</tbody>
			</table>
		</div>
		<div id="merchant-dataview2" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back"onclick="back2();">Back</button>
				<h1 id="merchant-name2"></h1>
			</div>
			<div data-role="header">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a id="merchant-download2" href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-action">Download</a>
					<a id="merchant-download2-mac" href="#" class="ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-action">Download MAC</a>
				</div>
			</div>
			<div data-role="header">
				<button id="prev-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="date"></h1>
				<button id="next-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<div data-role="header">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a id="mac-address" href="#" class="ui-btn ui-corner-all ui-icon-phone ui-btn-icon-right">MAC Address</a>
					<a id="user" href="#" class="ui-btn ui-corner-all ui-icon-user ui-btn-icon-right">User</a>
					<a id="browsing-history" href="#" class="ui-btn ui-corner-all ui-icon-search ui-btn-icon-right">Browsing History</a>
				</div>
			</div>
			<table id="merchant-data-table2" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid;">Jam</th>
						<th data-priority="2" style="border: 1px #000 solid;">PopupBox +<br>Opening Page +<br>Login Page</th>
						<th data-priority="3" style="border: 1px #000 solid;">Data</th>
						<th data-priority="4" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="5" style="border: 1px #000 solid;">Confirmation Page (TVC)</th>
						<th data-priority="6" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="7" style="border: 1px #000 solid;">Successful Page</th>
						<th data-priority="8" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
					</tr>
				</thead>
				<tbody id="merchant-data2">
				</tbody>
			</table>
		</div>
		<div id="merchant-unique-dataview2" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back"onclick="back4();">Back</button>
				<h1 id="merchant-unique-name2"></h1>
			</div>
			<div data-role="header">
				<button id="prev-date2" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="date2"></h1>
				<button id="next-date2" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<table id="merchant-unique-data-table2" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid;">Jam</th>
						<th data-priority="2" style="border: 1px #000 solid;">PopupBox +<br>Opening Page +<br>Login Page</th>
						<th data-priority="3" style="border: 1px #000 solid;">Data</th>
						<th data-priority="4" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="5" style="border: 1px #000 solid;">Confirmation Page (TVC)</th>
						<th data-priority="6" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
						<th data-priority="7" style="border: 1px #000 solid;">Successful Page</th>
						<th data-priority="8" class="rate-color" style="border: 1px #000 solid;">Rate %</th>
					</tr>
				</thead>
				<tbody id="merchant-unique-data2">
				</tbody>
			</table>
		</div>
		<div id="merchant-macaddress-dataview" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back"onclick="back5();">Back</button>
				<h1 id="merchant-macaddress-name"></h1>
			</div>
			<div data-role="header">
				<button id="merchant-macaddress-prev-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="merchant-macaddress-date"></h1>
				<button id="merchant-macaddress-next-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<table id="merchant-macaddress-data-table" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid; text-align: center;">User Travel Journey</th>
					</tr>
				</thead>
				<tbody id="merchant-macaddress-data">
				</tbody>
			</table>
		</div>
		<div id="merchant-user-dataview" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back"onclick="back6();">Back</button>
				<h1 id="merchant-user-name"></h1>
			</div>
			<div data-role="header">
				<button id="merchant-user-prev-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="merchant-user-date"></h1>
				<button id="merchant-user-next-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<table id="merchant-user-data-table" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid;">Image</th>
						<th data-priority="2" style="border: 1px #000 solid;">Type</th>
						<th data-priority="3" style="border: 1px #000 solid;">Data</th>
						<th data-priority="4" style="border: 1px #000 solid;">Followers</th>
					</tr>
				</thead>
				<tbody id="merchant-user-data">
				</tbody>
			</table>
		</div>
		<div id="merchant-browsing-history-dataview" style="display: none;">
			<div data-role="header">
				<button class="ui-btn-left ui-btn ui-btn-inline ui-corner-all ui-btn-icon-left ui-icon-back"onclick="back7();">Back</button>
				<h1 id="merchant-browsing-history-name"></h1>
			</div>
			<div data-role="header">
				<button id="merchant-browsing-history-prev-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-l ui-btn-icon-notext"></button>
				<h1 id="merchant-browsing-history-date"></h1>
				<button id="merchant-browsing-history-next-date" class="ui-btn ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-notext"></button>
			</div>
			<table id="merchant-browsing-history-data-table" data-role="table" data-mode="reflow" class="ui-responsive">
				<thead>
					<tr>
						<th data-priority="1" style="border: 1px #000 solid;">Time</th>
						<th data-priority="2" style="border: 1px #000 solid;">URL</th>
					</tr>
				</thead>
				<tbody id="merchant-browsing-history-data">
				</tbody>
			</table>
		</div>
		<div id="loading" style="background-color: #FFF; opacity: 1; width: 100%; height: 100%; left: 0; top: 0; position: fixed; z-index: 9000000; display: none;">
			<img src="loading.gif" style="left: 50%; position: fixed; top: 50%; transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); -webkit-transform: translate(-50%, -50%);">
		</div>
	</body>
</html>
