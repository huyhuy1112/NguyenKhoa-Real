<?php
/*+***********************************************************************************
 * Lịch sử Khách đã trả của cùng khách trên các đơn hàng.
 *************************************************************************************/

class SalesOrder_PaymentHistory_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND'));
		}
		if (!Users_Privileges_Model::isPermitted('SalesOrder', 'DetailView', $recordId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$response = new Vtiger_Response();
		try {
			if (getSalesEntityType($recordId) !== 'SalesOrder') {
				throw new Exception('Bản ghi không phải đơn hàng.');
			}
			$db = PearDatabase::getInstance();
			$current = $db->pquery(
				'SELECT so.accountid, so.contactid, so.customerno,
				        acc.accountname,
				        TRIM(CONCAT(IFNULL(cd.firstname, ""), " ", IFNULL(cd.lastname, ""))) AS contactname
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 LEFT JOIN vtiger_account acc ON acc.accountid = so.accountid
				 LEFT JOIN vtiger_contactdetails cd ON cd.contactid = so.contactid
				 WHERE so.salesorderid = ?',
				array($recordId)
			);
			if (!$current || $db->num_rows($current) < 1) {
				throw new Exception('Không tìm thấy đơn hàng.');
			}
			$accountId = (int) $db->query_result($current, 0, 'accountid');
			$contactId = (int) $db->query_result($current, 0, 'contactid');
			$customerNo = trim(decode_html((string) $db->query_result($current, 0, 'customerno')));
			$customer = trim(decode_html((string) $db->query_result($current, 0, 'accountname')));
			if ($customer === '') {
				$customer = trim(decode_html((string) $db->query_result($current, 0, 'contactname')));
			}
			if ($customer === '') {
				$customer = $customerNo;
			}

			$where = 'so.salesorderid = ?';
			$bind = array($recordId);
			if ($accountId > 0) {
				$where = 'so.accountid = ?';
				$bind = array($accountId);
			} elseif ($contactId > 0) {
				$where = 'so.contactid = ?';
				$bind = array($contactId);
			} elseif ($customerNo !== '') {
				$where = 'so.customerno = ?';
				$bind = array($customerNo);
			}

			$rs = $db->pquery(
				'SELECT so.salesorderid, so.salesorder_no, so.total, so.received, ce.createdtime
				 FROM vtiger_salesorder so
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE ' . $where . '
				 ORDER BY ce.createdtime DESC
				 LIMIT 50',
				$bind
			);
			$rows = array();
			if ($rs) {
				$count = $db->num_rows($rs);
				for ($i = 0; $i < $count; $i++) {
					$id = (int) $db->query_result($rs, $i, 'salesorderid');
					$total = (float) $db->query_result($rs, $i, 'total');
					$paid = (float) $db->query_result($rs, $i, 'received');
					if ($paid < 0) {
						$paid = 0;
					}
					$remaining = $total - $paid;
					if ($remaining < 0) {
						$remaining = 0;
					}
					$created = (string) $db->query_result($rs, $i, 'createdtime');
					$when = $created;
					if ($created !== '') {
						$when = (new DateTimeField($created))->getDisplayDateTimeValue();
					}
					$rows[] = array(
						'id' => $id,
						'no' => decode_html((string) $db->query_result($rs, $i, 'salesorder_no')),
						'when' => $when,
						'total' => CurrencyField::convertToUserFormat($total, null, true),
						'paid' => CurrencyField::convertToUserFormat($paid, null, true),
						'remaining' => CurrencyField::convertToUserFormat($remaining, null, true),
						'current' => $id === $recordId,
					);
				}
			}
			$response->setResult(array(
				'success' => true,
				'customer' => $customer,
				'rows' => $rows,
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
}
