<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("IPOLyadost_COMP_NAME"),
	"DESCRIPTION" => GetMessage("IPOLyadost_COMP_DESCR"),
	"CACHE_PATH" => "Y",
	"SORT" => 40,
	"PATH" => array(
		"ID" => "e-store",
		"CHILD" => array(
			"ID" => "ipol",
			"NAME" => GetMessage("IPOLyadost_GROUP"),
			"SORT" => 30,
			"CHILD" => array(
				"ID" => "ipol_yadostPickup",
			),
		),
	),
);