<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("TRADE_YANDEX_DELIVERY_COMP_NAME"),
	"DESCRIPTION" => GetMessage("TRADE_YANDEX_DELIVERY_COMP_DESCR"),
	"CACHE_PATH" => "Y",
	"SORT" => 40,
	"PATH" => array(
		"ID" => "e-store",
		"CHILD" => array(
			"ID" => "trade",
			"NAME" => GetMessage("TRADE_YANDEX_DELIVERY_GROUP"),
			"SORT" => 30,
			"CHILD" => array(
				"ID" => "trade_deliveryPickup",
			),
		),
	),
);
?>