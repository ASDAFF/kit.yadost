<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("KITyadost_COMP_NAME"),
	"DESCRIPTION" => GetMessage("KITyadost_COMP_DESCR"),
	"CACHE_PATH" => "Y",
	"SORT" => 40,
	"PATH" => array(
		"ID" => "e-store",
		"CHILD" => array(
			"ID" => "kit",
			"NAME" => GetMessage("KITyadost_GROUP"),
			"SORT" => 30,
			"CHILD" => array(
				"ID" => "kit_yadostPickup",
			),
		),
	),
);