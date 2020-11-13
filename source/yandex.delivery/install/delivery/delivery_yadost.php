<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id="yandex.delivery";
CModule::IncludeModule($module_id);

// установим метод CDeliveryYandex::Init в качестве обработчика события
if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/classes/general/CDeliveryYandex.php'))
	AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryYandex', 'Init'));