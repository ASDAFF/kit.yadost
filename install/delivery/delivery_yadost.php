<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id="kit.yadost";
CModule::IncludeModule($module_id);

// установим метод CKITYadost::Init в качестве обработчика события
if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/classes/general/CKITYadost.php'))
	AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CKITYadost', 'Init'));