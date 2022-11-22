<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       simplestats/simplestatsindex.php
 *	\ingroup    simplestats
 *	\brief      Home page of simplestats top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $conf, $db, $langs, $user;

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/custom/bthcommon/class/bthcommon.class.php');

// Load translation files required by the page
$langs->loadLangs(array("simplestats@simplestats"));

$action = GETPOST('action', 'aZ09');


// Security check
//if (! $user->rights->simplestats->myobject->read) accessforbidden();
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */
$object = new bthcommon($db);
$form = new Form($db);
$formfile = new FormFile($db);

//Define module parameters

llxHeader("", $langs->trans("Test"));

print load_fiche_titre($langs->trans("Test"), '', '');

$xml = file_get_contents('https://bnb.bg/Statistics/StExternalSector/StExchangeRates/StERForeignCurrencies/index.htm?download=xml&search=&lang=BG');
$path = DOL_DOCUMENT_ROOT . '/custom/bthcommon/temp';
$name = 'rates.xml';
file_put_contents($path .'/'. $name, $xml);

$file = simpleXML_load_file($path .'/'. $name,"SimpleXMLElement",LIBXML_NOCDATA);

$obj = [];

$sql = 'SELECT rowid as id, code FROM '.MAIN_DB_PREFIX.'multicurrency WHERE code != "EUR"';
$resql = $db->query($sql);

if ($resql) {
	while ($row = $db->fetch_object($resql)) {
		$obj[] = $row;
	}
}

foreach ($obj as $currency) {
	foreach ($file as $rate) {
		if ($rate->CODE == $currency->code && $rate->CODE != "EUR") {
			dol_include_once('/multicurrency/class/multicurrency.class.php');
			$currency_rate = new CurrencyRate($db);
			$currency_rate->date_sync = date('Y-m-d', strtotime(strval($rate->CURR_DATE)));
			$currency_rate->rate = $rate->REVERSERATE;
			$currency_rate->fk_multicurrency = $currency->id;
			$currency_rate->entity = 1;
			$result = $currency_rate->create($currency->id);

		}
	}
}


// End of page
llxFooter();
$db->close();
