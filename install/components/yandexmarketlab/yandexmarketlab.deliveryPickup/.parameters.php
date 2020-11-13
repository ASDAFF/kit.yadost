<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	"PARAMETERS" => array(
		"WIDGET_CODE" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('KITyadost_WIDGET_CODE'),
			"TYPE"     => "TEXT",
			"SIZE"     => 9
		),
		"CITY_NAME" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('KITyadost_CITY_NAME'),
			"TYPE"     => "TEXT",
		),
		"TO_YD_WAREHOUSE" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('KITyadost_TO_YD_WAREHOUSE'),
			"TYPE"     => "CHECKBOX",
			"DEFAULT"  => ("Y" == COption::GetOptionString("kit.yadost", "to_yd_warehouse", ""))?"Y":"N",
		),
		"USE_ITEM" => array(
			"PARENT"   => "BASE",
			"NAME"     => GetMessage('KITyadost_USE_ITEM'),
			"TYPE"     => "CHECKBOX",
			"REFRESH"  => "Y"
		)
	),
);

if ("Y" == $arCurrentValues["USE_ITEM"])
{
	$arComponentParameters["PARAMETERS"]["ITEM_ID"] = array(
		"PARENT"   => "BASE",
		"NAME"     => GetMessage('KITyadost_ITEM_ID'),
		"TYPE"     => "TEXT",
	);
	
	$arComponentParameters["PARAMETERS"]["ITEM_QUANTITY"] = array(
		"PARENT"   => "BASE",
		"NAME"     => GetMessage('KITyadost_ITEM_QUANTITY'),
		"TYPE"     => "TEXT",
	);
	
	
}
?>