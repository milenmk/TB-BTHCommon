<?php

declare(strict_types=1);

/**
 * \file        class/bthcommon.class.php
 * \ingroup     bthcommon
 * \brief       This file is a CRUD class file for bthcommon (Create/Read/Update/Delete)
 */
// Put here all includes required by your class file
dol_include_once('/core/class/commonobject.class.php');

/**
 * Class for bthcommon
 */
class bthcommon extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'bthcommon';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'bthcommon';

	/**
	 * @var string String with name of icon for bthcommon. Must be the part after the 'object_' into
	 *      object_bthcommon.png
	 */
	public $picto = 'other';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
	}

	/**
	 * Function to update currency rates from BNB official site
	 *
	 * @return string
	 */
	function update_currency_rate_from_url($url){

		global $user;

		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $url);
		$contents = curl_exec($c);
		curl_close($c);

		if ($contents) {
			$path = DOL_DOCUMENT_ROOT . '/custom/bthcommon/temp';
			$name = 'rates.xml';
			file_put_contents($path .'/'. $name, $contents);

			$file = simpleXML_load_file($path .'/'. $name,"SimpleXMLElement",LIBXML_NOCDATA);

			$obj = [];

			$sql = 'SELECT rowid as id, code FROM '.MAIN_DB_PREFIX.'multicurrency WHERE code != "EUR"';
			$resql = $this->db->query($sql);

			if ($resql) {
				while ($row = $this->db->fetch_object($resql)) {
					$obj[] = $row;
				}
			}

			$end_normal = 0;
			$end_error = 0;

			foreach ($obj as $currency) {
				foreach ($file as $rate) {
					if ($rate->CODE == $currency->code && $rate->CODE != "EUR") {
						dol_include_once('/multicurrency/class/multicurrency.class.php');
						$currency_rate = new CurrencyRate($this->db);
						$currency_rate->date_sync = dol_now();
						$currency_rate->rate = $rate->REVERSERATE;
						$currency_rate->entity = 1;
						//$result = $currency_rate->create($currency->id);
						if ($currency_rate->create($currency->id) > 0) {
							$end_normal += 1;
						} else {
							$end_error += 1;
						}
					}
				}
			}

			return $end_normal . ' - ' . $end_error;
		}
	}

	/**
	 * @return int|void
	 */
	public function getCustomerCategories()
	{

		$sql = "SELECT DISTINCT c.rowid, c.label, c.ref_ext, c.description, c.color, c.fk_parent, c.visible"; // Distinct reduce pb with old tables with duplicates
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c";
		$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
		$sql .= " AND c.type = 2";
		$sql .= " AND c.rowid != 83";
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			while ($obj = $this->db->fetch_object($resql)) {
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['label'] = !empty($obj->label_trans) ? $obj->label_trans : $obj->label;
				$i++;
			}

			return $this->cats;
		} else {
			dol_print_error($this->db);

			return -1;
		}
	}

}
