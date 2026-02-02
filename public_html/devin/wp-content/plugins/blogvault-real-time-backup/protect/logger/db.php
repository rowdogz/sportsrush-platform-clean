<?php
if (!defined('ABSPATH') && !defined('MCDATAPATH')) exit;

if (!class_exists('BVProtectLoggerDB_V636')) :
class BVProtectLoggerDB_V636 {
	private $tablename;
	private $bv_tablename;

	const MAXROWCOUNT = 100000;

	function __construct($tablename) {
		$this->tablename = $tablename;
		$this->bv_tablename = BVProtect_V636::$db->getBVTable($tablename);
	}

	public function log($data) {
		if (is_array($data)) {
			if (BVProtect_V636::$db->rowsCount($this->bv_tablename) > BVProtectLoggerDB_V636::MAXROWCOUNT) {
				BVProtect_V636::$db->deleteRowsFromtable($this->tablename, 1);
			}

			BVProtect_V636::$db->replaceIntoBVTable($this->tablename, $data);
		}
	}
}
endif;