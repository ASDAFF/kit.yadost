<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	"PARAMETERS" => array(
		"WIDGET_CODE" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('TRADE_YANDEX_DELIVERY_WIDGET_CODE'),
			"TYPE"     => "TEXT",
			"SIZE"     => 9
		),
		"CITY_NAME" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('TRADE_YANDEX_DELIVERY_CITY_NAME'),
			"TYPE"     => "TEXT",
		),
		"TO_YD_WAREHOUSE" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('TRADE_YANDEX_DELIVERY_TO_YD_WAREHOUSE'),
			"TYPE"     => "CHECKBOX",
			"DEFAULT"  => ("Y" == COption::GetOptionString("yandex.delivery", "to_yd_warehouse", ""))?"Y":"N",
		),
		"USE_ITEM" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('TRADE_YANDEX_DELIVERY_USE_ITEM'),
			"TYPE"     => "CHECKBOX",
			"REFRESH"  => "Y"
		)
	),
);

if ("Y" == $arCurrentValues["USE_ITEM"])
{
	$arComponentParameters["PARAMETERS"]["ITEM_ID"] = array(
		"PARENT"   => "BASE",
		"NAME"     => GetMessage('TRADE_YANDEX_DELIVERY_ITEM_ID'),
		"TYPE"     => "TEXT",
	);
	
	$arComponentParameters["PARAMETERS"]["ITEM_QUANTITY"] = array(
		"PARENT"   => "BASE",
		"NAME"     => GetMessage('TRADE_YANDEX_DELIVERY_ITEM_QUANTITY'),
		"TYPE"     => "TEXT",
	);
	
	
}
?>