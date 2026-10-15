<?php
/*+***********************************************************************************
 * Báo cáo Marketing: sheet Tổng Hợp Data (Khách lớp học).
 * Tiền quảng cáo mỗi ngày do marketing nhập. Ngân sách và chi phí tính lại từ số đó.
 * Lead, hẹn, tham gia giữ số trên sheet.
 *************************************************************************************/

class Reports_MktSheetReportService {

	const SHEET_ID = '1X8sByQC8DlHIZBwDkIAf9RbKi96VGkg-fDvT03xhdrY';
	const CACHE_FILE = 'cache/mk_tong_hop_data.json';
	const CACHE_SECONDS = 900;
	const VAT = 1.1;

	public static function payload($db = null) {
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		self::ensureTable($db);
		$parsed = self::parsedSheet();
		if (!empty($parsed['error'])) {
			return array(
				'error' => $parsed['error'],
				'months' => array(),
				'classes' => array(),
				'perf990' => array('weeks' => array(), 'summary' => array()),
				'perfFree' => array('weeks' => array(), 'summary' => array()),
				'status' => array('dates' => array(), 'rows' => array()),
			);
		}
		$saved = self::savedSpend($db);
		return self::compose($parsed, $saved);
	}

	public static function saveSpend($date, $amount, $userId, $db = null) {
		if ($db === null) {
			$db = PearDatabase::getInstance();
		}
		self::ensureTable($db);
		$date = self::isoDate($date);
		if ($date === '') {
			throw new Exception('Ngày không hợp lệ.');
		}
		$amount = round((float) $amount, 2);
		if ($amount < 0) {
			$amount = 0;
		}
		$parsed = self::parsedSheet();
		$campaign = self::campaignFor($parsed, $date);
		$userId = (int) $userId;
		$db->pquery(
			'INSERT INTO vtiger_mk_ad_spend (spend_date, campaign, amount, userid, modified_at)
			 VALUES (?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE campaign = VALUES(campaign), amount = VALUES(amount), userid = VALUES(userid), modified_at = VALUES(modified_at)',
			array($date, $campaign, $amount, $userId, date('Y-m-d H:i:s'))
		);
	}

	public static function parseMoneyInput($raw) {
		$raw = trim(str_replace(array(' ', 'đ', '₫'), '', (string) $raw));
		if ($raw === '') {
			return 0;
		}
		if (strpos($raw, ',') !== false) {
			$raw = str_replace('.', '', $raw);
			$raw = str_replace(',', '.', $raw);
		} elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
			$raw = str_replace('.', '', $raw);
		}
		return round((float) $raw, 2);
	}

	protected static function ensureTable($db) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$db->pquery(
			'CREATE TABLE IF NOT EXISTS vtiger_mk_ad_spend (
				spend_date DATE NOT NULL,
				campaign VARCHAR(8) NOT NULL DEFAULT \'990k\',
				amount DECIMAL(18,2) NOT NULL DEFAULT 0,
				userid INT NOT NULL DEFAULT 0,
				modified_at DATETIME NULL,
				PRIMARY KEY (spend_date)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	protected static function savedSpend($db) {
		$out = array();
		$res = $db->pquery('SELECT spend_date, campaign, amount FROM vtiger_mk_ad_spend', array());
		if (!$res) {
			return $out;
		}
		while ($row = $db->fetch_array($res)) {
			$out[$row['spend_date']] = array(
				'campaign' => (string) $row['campaign'],
				'amount' => (float) $row['amount'],
			);
		}
		return $out;
	}

	protected static function compose(array $parsed, array $saved) {
		$daySpend = array();
		foreach ($parsed['days'] as $day) {
			$date = $day['date'];
			$daySpend[$date] = self::spendOn($parsed, $saved, $date);
		}
		$months = array();
		foreach ($parsed['days'] as $day) {
			$key = substr($day['date'], 0, 7);
			if (!isset($months[$key])) {
				$ts = strtotime($day['date'] . ' 12:00:00');
				$months[$key] = array(
					'key' => $key,
					'label' => 'Tháng ' . date('n', $ts) . '/' . date('Y', $ts),
					'days' => array(),
				);
			}
			$spend = $daySpend[$day['date']];
			$total = (float) $day['total'];
			$months[$key]['days'][] = array(
				'date' => $day['date'],
				'label' => date('d/m/Y', strtotime($day['date'] . ' 12:00:00')),
				'total' => $total,
				'nkhoa' => $day['nkhoa'],
				'tiktok' => $day['tiktok'],
				'kv1' => $day['kv1'],
				'kv2' => $day['kv2'],
				'kv3' => $day['kv3'],
				'unknown' => $day['unknown'],
				'consulting' => $day['consulting'],
				'unreachable' => $day['unreachable'],
				'khong_hoc' => $day['khong_hoc'],
				'online' => $day['online'],
				'spend' => $spend['amount'],
				'edited' => $spend['edited'],
				'cost_data' => $total > 0 ? round($spend['amount'] / $total, 2) : null,
			);
		}
		$weeks = array();
		$sumBudget = 0;
		$sumData = 0;
		$sumQualified = 0;
		$sumPaid = 0;
		foreach ($parsed['weeks990'] as $week) {
			$budget = 0;
			$cursor = strtotime($week['from'] . ' 12:00:00');
			$end = strtotime($week['to'] . ' 12:00:00');
			while ($cursor !== false && $cursor <= $end) {
				$iso = date('Y-m-d', $cursor);
				$budget += self::campaignAmount($parsed, $saved, $iso, '990k');
				$cursor = strtotime('+1 day', $cursor);
			}
			$sumBudget += $budget;
			$sumData += $week['data'];
			$sumQualified += $week['qualified'];
			$sumPaid += $week['paid'];
			$weeks[] = array(
				'label' => $week['label'],
				'range' => date('d/m', strtotime($week['from'])) . ' - ' . date('d/m', strtotime($week['to'])),
				'budget' => round($budget, 2),
				'data' => $week['data'],
				'cost_data' => $week['data'] > 0 ? round($budget / $week['data'], 2) : null,
				'qualified' => $week['qualified'],
				'cost_qualified' => $week['qualified'] > 0 ? round($budget / $week['qualified'], 2) : null,
				'paid' => $week['paid'],
				'paid_rate' => $week['qualified'] > 0 ? $week['paid'] / $week['qualified'] : null,
				'kv1' => $week['kv1'],
				'kv2' => $week['kv2'],
				'kv3' => $week['kv3'],
				'unknown' => $week['unknown'],
			);
		}
		$budgetVat = $sumBudget * self::VAT;
		$freeBudget = 0;
		foreach ($parsed['freeDates'] as $iso => $unused) {
			$freeBudget += self::campaignAmount($parsed, $saved, $iso, 'free');
		}
		$freeBudgetVat = $freeBudget * self::VAT;
		$freeShow = 0;
		$freeAppt = 0;
		$freeClose = 0;
		$freeWeeks = array();
		foreach ($parsed['freeWeeks'] as $week) {
			$freeShow += $week['show'];
			$freeAppt += $week['appointments'];
			$freeClose += $week['close'];
			$freeWeeks[] = $week;
		}
		$denom = (float) $parsed['freeDenom'];
		return array(
			'error' => '',
			'months' => array_values($months),
			'classes' => $parsed['classes'],
			'perf990' => array(
				'weeks' => $weeks,
				'summary' => array(
					'budget' => round($budgetVat, 2),
					'data' => $sumData,
					'cost_data' => $sumData > 0 ? round($budgetVat / $sumData, 2) : null,
					'qualified' => $sumQualified,
					'cost_qualified' => $sumQualified > 0 ? round($budgetVat / $sumQualified, 2) : null,
					'paid' => $sumPaid,
					'paid_rate' => $sumQualified > 0 ? $sumPaid / $sumQualified : null,
					'cost_per_paid' => $sumPaid > 0 ? round($budgetVat / $sumPaid, 2) : null,
					'revenue' => round($sumPaid * 990000, 2),
					'target_qualified' => 350000,
				),
			),
			'perfFree' => array(
				'weeks' => $freeWeeks,
				'summary' => array(
					'budget' => round($freeBudgetVat, 2),
					'appointments' => $freeAppt,
					'show' => $freeShow,
					'close' => $freeClose,
					'data' => (float) $parsed['freeData'],
					'cost_qualified' => $denom > 0 ? round($freeBudgetVat / $denom, 2) : null,
					'cost_show' => $freeShow > 0 ? round($freeBudgetVat / $freeShow, 2) : null,
					'target_qualified' => 200000,
					'target_show' => $denom > 0 ? round(($freeBudgetVat / $denom) * 3.5, 2) : null,
				),
			),
			'status' => $parsed['status'],
		);
	}

	protected static function spendOn(array $parsed, array $saved, $date) {
		if (isset($saved[$date])) {
			return array('amount' => (float) $saved[$date]['amount'], 'edited' => true);
		}
		$amount = 0;
		if (!empty($parsed['base990'][$date])) {
			$amount += (float) $parsed['base990'][$date];
		}
		if (!empty($parsed['baseFree'][$date])) {
			$amount += (float) $parsed['baseFree'][$date];
		}
		return array('amount' => $amount, 'edited' => false);
	}

	protected static function campaignAmount(array $parsed, array $saved, $date, $campaign) {
		$baseKey = $campaign === 'free' ? 'baseFree' : 'base990';
		$base = !empty($parsed[$baseKey][$date]) ? (float) $parsed[$baseKey][$date] : 0;
		if (isset($saved[$date]) && $saved[$date]['campaign'] === $campaign) {
			return (float) $saved[$date]['amount'];
		}
		return $base;
	}

	protected static function campaignFor(array $parsed, $date) {
		if (!empty($parsed['error'])) {
			return '990k';
		}
		if (!empty($parsed['base990'][$date])) {
			return '990k';
		}
		if (!empty($parsed['baseFree'][$date])) {
			return 'free';
		}
		foreach ($parsed['weeks990'] as $week) {
			if ($date >= $week['from'] && $date <= $week['to']) {
				return '990k';
			}
		}
		if (!empty($parsed['freeDates'][$date])) {
			return 'free';
		}
		return '990k';
	}

	protected static function parsedSheet() {
		$path = self::CACHE_FILE;
		if (is_file($path) && (time() - filemtime($path)) < self::CACHE_SECONDS) {
			$cached = json_decode((string) file_get_contents($path), true);
			if (is_array($cached) && empty($cached['error'])) {
				return $cached;
			}
		}
		try {
			$parsed = self::downloadAndParse();
		} catch (Exception $e) {
			if (is_file($path)) {
				$cached = json_decode((string) file_get_contents($path), true);
				if (is_array($cached)) {
					return $cached;
				}
			}
			return array('error' => 'Chưa tải được sheet Tổng Hợp Data.');
		}
		$dir = dirname($path);
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		@file_put_contents($path, json_encode($parsed));
		return $parsed;
	}

	protected static function downloadAndParse() {
		$url = 'https://docs.google.com/spreadsheets/d/' . self::SHEET_ID . '/export?format=xlsx';
		$raw = self::httpGet($url);
		if (strlen($raw) < 1000 || substr($raw, 0, 2) !== 'PK') {
			throw new Exception('Sheet không tải được.');
		}
		$tmp = tempnam(sys_get_temp_dir(), 'mksheet');
		file_put_contents($tmp, $raw);
		$zip = new ZipArchive();
		if ($zip->open($tmp) !== true) {
			@unlink($tmp);
			throw new Exception('Không đọc được file sheet.');
		}
		$strings = self::sharedStrings($zip);
		$names = self::sheetPaths($zip);
		$summary = self::readRows($zip, isset($names['Tổng Hợp Data']) ? $names['Tổng Hợp Data'] : 'xl/worksheets/sheet1.xml', $strings);
		$paid = self::readRows($zip, isset($names['TKQC lớp 990K']) ? $names['TKQC lớp 990K'] : 'xl/worksheets/sheet9.xml', $strings);
		$free = self::readRows($zip, isset($names['TKQC lớp Free']) ? $names['TKQC lớp Free'] : 'xl/worksheets/sheet10.xml', $strings);
		$zip->close();
		@unlink($tmp);
		return self::interpret($summary, $paid, $free);
	}

	protected static function httpGet($url) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 40);
			curl_setopt($ch, CURLOPT_USERAGENT, 'NguyenKhoaCRM');
			$body = curl_exec($ch);
			curl_close($ch);
			return $body === false ? '' : $body;
		}
		$ctx = stream_context_create(array('http' => array('timeout' => 40)));
		$body = @file_get_contents($url, false, $ctx);
		return $body === false ? '' : $body;
	}

	protected static function sharedStrings(ZipArchive $zip) {
		$xml = $zip->getFromName('xl/sharedStrings.xml');
		$out = array();
		if ($xml === false) {
			return $out;
		}
		$root = simplexml_load_string($xml);
		if (!$root) {
			return $out;
		}
		$ns = $root->getNamespaces(true);
		$uri = isset($ns['']) ? $ns[''] : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		foreach ($root->children($uri)->si as $si) {
			$text = '';
			foreach ($si->children($uri)->t as $t) {
				$text .= (string) $t;
			}
			if ($text === '') {
				foreach ($si->children($uri)->r as $run) {
					$text .= (string) $run->children($uri)->t;
				}
			}
			$out[] = $text;
		}
		return $out;
	}

	protected static function sheetPaths(ZipArchive $zip) {
		$book = $zip->getFromName('xl/workbook.xml');
		$rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
		$map = array();
		if ($book === false || $rels === false) {
			return $map;
		}
		$relRoot = simplexml_load_string($rels);
		$ridTo = array();
		foreach ($relRoot->Relationship as $rel) {
			$ridTo[(string) $rel['Id']] = (string) $rel['Target'];
		}
		if (!preg_match_all('/<sheet\b[^>]*\bname="([^"]+)"[^>]*\br:id="([^"]+)"/', $book, $found, PREG_SET_ORDER)) {
			return $map;
		}
		foreach ($found as $sheet) {
			$rid = $sheet[2];
			$target = isset($ridTo[$rid]) ? $ridTo[$rid] : '';
			if ($target !== '' && strpos($target, 'xl/') !== 0) {
				$target = 'xl/' . ltrim($target, '/');
			}
			$map[html_entity_decode($sheet[1], ENT_QUOTES, 'UTF-8')] = $target;
		}
		return $map;
	}

	protected static function readRows(ZipArchive $zip, $path, array $strings) {
		$xml = $zip->getFromName($path);
		$rows = array();
		if ($xml === false || $xml === '') {
			return $rows;
		}
		$offset = 0;
		while (($pos = strpos($xml, '<c r="', $offset)) !== false) {
			$gt = strpos($xml, '>', $pos);
			if ($gt === false) {
				break;
			}
			$head = substr($xml, $pos, $gt - $pos + 1);
			if (substr($head, -2) === '/>') {
				$offset = $gt + 1;
				continue;
			}
			$close = strpos($xml, '</c>', $gt);
			if ($close === false) {
				break;
			}
			$body = substr($xml, $gt + 1, $close - $gt - 1);
			$offset = $close + 4;
			if (!preg_match('/^<c r="([A-Z]+)(\d+)"/', $head, $ref)) {
				continue;
			}
			$vpos = strpos($body, '<v>');
			if ($vpos === false) {
				$vpos = strpos($body, '<v ');
			}
			if ($vpos === false) {
				continue;
			}
			$vstart = strpos($body, '>', $vpos);
			$vend = strpos($body, '</v>', $vstart);
			if ($vstart === false || $vend === false) {
				continue;
			}
			$text = substr($body, $vstart + 1, $vend - $vstart - 1);
			if (strpos($head, 't="s"') !== false) {
				$idx = (int) $text;
				$text = isset($strings[$idx]) ? $strings[$idx] : '';
			}
			$rows[(int) $ref[2]][self::colIndex($ref[1])] = $text;
		}
		return $rows;
	}

	protected static function interpret(array $summary, array $paid, array $free) {
		$perfRow = 0;
		$freeRow = 0;
		$statusRow = 0;
		foreach ($summary as $r => $cols) {
			$label = isset($cols[20]) ? (string) $cols[20] : '';
			if ($perfRow === 0 && strpos($label, 'LỚP HỌC 990') !== false) {
				$perfRow = $r;
			}
			if ($freeRow === 0 && strpos($label, 'MIỄN PHÍ') !== false) {
				$freeRow = $r;
			}
			if ($label === 'TÌNH TRẠNG') {
				$statusRow = $r;
			}
		}
		$days = array();
		foreach ($summary as $cols) {
			$date = self::asDate(isset($cols[1]) ? $cols[1] : '');
			if ($date === '') {
				continue;
			}
			$days[] = array(
				'date' => $date,
				'total' => self::num(isset($cols[2]) ? $cols[2] : 0),
				'nkhoa' => self::num(isset($cols[3]) ? $cols[3] : 0),
				'tiktok' => self::num(isset($cols[4]) ? $cols[4] : 0),
				'kv1' => self::num(isset($cols[5]) ? $cols[5] : 0),
				'kv2' => self::num(isset($cols[6]) ? $cols[6] : 0),
				'kv3' => self::num(isset($cols[7]) ? $cols[7] : 0),
				'unknown' => self::num(isset($cols[8]) ? $cols[8] : 0),
				'consulting' => self::num(isset($cols[9]) ? $cols[9] : 0),
				'unreachable' => self::num(isset($cols[10]) ? $cols[10] : 0),
				'khong_hoc' => self::num(isset($cols[11]) ? $cols[11] : 0),
				'online' => self::num(isset($cols[12]) ? $cols[12] : 0),
			);
		}
		$classes = array();
		$limit = $perfRow > 0 ? $perfRow : 9999;
		foreach ($summary as $r => $cols) {
			if ($r >= $limit) {
				break;
			}
			$date = self::asDate(isset($cols[21]) ? $cols[21] : '');
			if ($date === '') {
				continue;
			}
			$month = isset($cols[19]) ? trim((string) $cols[19]) : '';
			if ($month !== '' && strpos($month, 'Tháng') !== 0 && strpos($month, 'THÁNG') !== 0) {
				continue;
			}
			$classes[] = array(
				'month' => $month,
				'weekday' => isset($cols[20]) ? (string) $cols[20] : '',
				'date' => date('d/m/Y', strtotime($date . ' 12:00:00')),
				'total' => self::num(isset($cols[22]) ? $cols[22] : 0),
				'appointments' => self::num(isset($cols[23]) ? $cols[23] : 0),
				'reschedule' => self::num(isset($cols[24]) ? $cols[24] : 0),
				'khong_hoc' => self::num(isset($cols[25]) ? $cols[25] : 0),
				'confirmed' => self::num(isset($cols[26]) ? $cols[26] : 0),
				'show' => self::num(isset($cols[27]) ? $cols[27] : 0),
				'pct_potential' => self::fraction(isset($cols[28]) ? $cols[28] : ''),
				'pct_show' => self::fraction(isset($cols[29]) ? $cols[29] : ''),
				'pct_confirm' => self::fraction(isset($cols[30]) ? $cols[30] : ''),
				'pct_actual' => self::fraction(isset($cols[31]) ? $cols[31] : ''),
				'pcth' => self::num(isset($cols[32]) ? $cols[32] : 0),
				'pcth_mq' => self::num(isset($cols[33]) ? $cols[33] : 0),
				'mq' => self::num(isset($cols[34]) ? $cols[34] : 0),
				'pct_close' => self::fraction(isset($cols[35]) ? $cols[35] : ''),
				'knm' => self::num(isset($cols[37]) ? $cols[37] : 0),
				'potential' => self::num(isset($cols[38]) ? $cols[38] : 0),
				'unsure' => self::num(isset($cols[39]) ? $cols[39] : 0),
				'invite' => self::num(isset($cols[40]) ? $cols[40] : 0),
				'stop' => self::num(isset($cols[41]) ? $cols[41] : 0),
				'unknown' => self::num(isset($cols[42]) ? $cols[42] : 0),
				'other' => self::num(isset($cols[43]) ? $cols[43] : 0),
				'franchise' => self::num(isset($cols[44]) ? $cols[44] : 0),
				'material' => self::num(isset($cols[45]) ? $cols[45] : 0),
			);
		}
		$weeks990 = array();
		$rowNums = array_keys($summary);
		sort($rowNums);
		$year = 2026;
		if ($days) {
			$year = (int) substr($days[count($days) - 1]['date'], 0, 4);
		}
		foreach ($rowNums as $r) {
			if ($freeRow > 0 && $r >= $freeRow) {
				break;
			}
			if ($perfRow > 0 && $r < $perfRow) {
				continue;
			}
			$cols = $summary[$r];
			$label = isset($cols[20]) ? trim((string) $cols[20]) : '';
			if (strpos($label, 'Tuần') !== 0) {
				continue;
			}
			$next = isset($summary[$r + 1]) ? $summary[$r + 1] : array();
			$range = isset($next[21]) ? (string) $next[21] : '';
			$span = self::rangeDates($range, $year);
			if ($span === null) {
				continue;
			}
			$weeks990[] = array(
				'label' => $label,
				'from' => $span[0],
				'to' => $span[1],
				'data' => self::num(isset($cols[23]) ? $cols[23] : 0),
				'qualified' => self::num(isset($cols[25]) ? $cols[25] : 0),
				'paid' => self::num(isset($cols[27]) ? $cols[27] : 0),
				'kv1' => self::num(isset($cols[29]) ? $cols[29] : 0),
				'kv2' => self::num(isset($cols[30]) ? $cols[30] : 0),
				'kv3' => self::num(isset($cols[31]) ? $cols[31] : 0),
				'unknown' => self::num(isset($cols[32]) ? $cols[32] : 0),
			);
		}
		$freeWeeks = array();
		$freeDenom = 0;
		$freeData = 0;
		foreach ($rowNums as $r) {
			if ($freeRow === 0 || $r < $freeRow) {
				continue;
			}
			if ($statusRow > 0 && $r >= $statusRow) {
				break;
			}
			$cols = $summary[$r];
			$label = isset($cols[20]) ? trim((string) $cols[20]) : '';
			if (strpos($label, 'Tuần') !== 0) {
				continue;
			}
			$when = self::asDate(isset($cols[22]) ? $cols[22] : '');
			if ($when === '') {
				if ($label === 'Tuần 1') {
					$freeDenom = self::num(isset($cols[29]) ? $cols[29] : 0) + self::num(isset($cols[30]) ? $cols[30] : 0) * 0.347;
				}
				continue;
			}
			$freeWeeks[] = array(
				'label' => $label,
				'date' => date('d/m/Y', strtotime($when . ' 12:00:00')),
				'appointments' => self::num(isset($cols[23]) ? $cols[23] : 0),
				'show' => self::num(isset($cols[25]) ? $cols[25] : 0),
				'close' => self::num(isset($cols[27]) ? $cols[27] : 0),
			);
		}
		foreach ($summary as $r => $cols) {
			if ($freeRow === 0 || $r < $freeRow || ($statusRow > 0 && $r >= $statusRow)) {
				continue;
			}
			$label = isset($cols[20]) ? (string) $cols[20] : '';
			if (strpos($label, 'Ngân Sách QC') !== false) {
				$below = isset($summary[$r + 1]) ? $summary[$r + 1] : array();
				if (isset($below[29])) {
					$freeData = self::num($below[29]);
				}
			}
		}
		$status = array('dates' => array(), 'rows' => array());
		if ($statusRow > 0 && isset($summary[$statusRow])) {
			$header = $summary[$statusRow];
			$dateCols = array();
			foreach ($header as $col => $value) {
				if ($col < 21) {
					continue;
				}
				$date = self::asDate($value);
				if ($date === '') {
					continue;
				}
				$dateCols[] = $col;
				$status['dates'][] = date('d/m/Y', strtotime($date . ' 12:00:00'));
			}
			foreach ($rowNums as $r) {
				if ($r <= $statusRow) {
					continue;
				}
				$cols = $summary[$r];
				$label = isset($cols[20]) ? trim((string) $cols[20]) : '';
				if ($label === '') {
					if ($r > $statusRow + 3) {
						break;
					}
					continue;
				}
				$cells = array();
				foreach ($dateCols as $col) {
					$cells[] = self::num(isset($cols[$col]) ? $cols[$col] : 0);
				}
				$status['rows'][] = array('label' => $label, 'cells' => $cells);
			}
		}
		$base990 = self::moneyByDate($paid, 7);
		$baseFree = self::moneyByDate($free, 12);
		$freeDates = array();
		foreach ($free as $r => $cols) {
			if ($r < 192 || $r > 221) {
				continue;
			}
			$date = self::asDate(isset($cols[1]) ? $cols[1] : '');
			if ($date !== '') {
				$freeDates[$date] = 1;
			}
		}
		return array(
			'error' => '',
			'days' => $days,
			'classes' => $classes,
			'weeks990' => $weeks990,
			'freeWeeks' => $freeWeeks,
			'freeDenom' => $freeDenom,
			'freeData' => $freeData,
			'freeDates' => $freeDates,
			'status' => $status,
			'base990' => $base990,
			'baseFree' => $baseFree,
		);
	}

	protected static function moneyByDate(array $rows, $moneyCol) {
		$out = array();
		foreach ($rows as $cols) {
			$date = self::asDate(isset($cols[1]) ? $cols[1] : '');
			if ($date === '') {
				continue;
			}
			$out[$date] = self::num(isset($cols[$moneyCol]) ? $cols[$moneyCol] : 0);
		}
		return $out;
	}

	protected static function rangeDates($label, $year) {
		if (!preg_match('/(\d{1,2})\/(\d{1,2})\s*-\s*(\d{1,2})\/(\d{1,2})/', (string) $label, $m)) {
			return null;
		}
		$start = sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
		$endYear = $year;
		if ((int) $m[4] < (int) $m[2]) {
			$endYear++;
		}
		$end = sprintf('%04d-%02d-%02d', $endYear, (int) $m[4], (int) $m[3]);
		return array($start, $end);
	}

	protected static function asDate($value) {
		if (!is_numeric($value)) {
			return '';
		}
		$n = (float) $value;
		if ($n < 20000 || $n > 80000) {
			return '';
		}
		$dt = new DateTime('1899-12-30');
		$dt->modify('+' . (int) round($n) . ' days');
		return $dt->format('Y-m-d');
	}

	protected static function isoDate($value) {
		$value = trim((string) $value);
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
			return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
		}
		if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
			return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
		}
		return '';
	}

	protected static function num($value) {
		if (is_numeric($value)) {
			return (float) $value;
		}
		return 0;
	}

	protected static function fraction($value) {
		if (!is_numeric($value)) {
			return null;
		}
		return (float) $value;
	}

	protected static function colIndex($letters) {
		$n = 0;
		$letters = strtoupper($letters);
		$len = strlen($letters);
		for ($i = 0; $i < $len; $i++) {
			$n = $n * 26 + (ord($letters[$i]) - 64);
		}
		return $n;
	}
}
