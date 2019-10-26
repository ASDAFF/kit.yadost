<?
/**
 * Copyright (c) 24/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id="ipol.yadost";
CModule::IncludeModule($module_id);

// установим метод CIPOLYadost::Init в качестве обработчика события
if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/classes/general/CIPOLYadost.php'))
	AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CIPOLYadost', 'Init'));