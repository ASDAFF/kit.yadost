<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var array $arParams
 */

if(!cmodule::includeModule('yandex.delivery'))
	return false;
if(!cmodule::includeModule('sale'))
	return false;

$arResult["ERRORS"] = array();

// PERSON_TYPE
if ($arParams["PERSON_TYPE"])
	$arResult["PERSON_TYPE"] = $arParams["PERSON_TYPE"];
else
{
	$personType = CSalePersonType::GetList(
		array("ID" => "asc"),
		array(),
		false,
		array("nTopCount" => 1)
	)->Fetch();
	
	if (empty($personType))
		$arResult["ERRORS"][] = array("CODE" => "No PERSON_TYPE found");
	else
		$arResult["PERSON_TYPE"] = $personType["ID"];
}

// ORDER_PROPS
CDeliveryYandexDriver::getModuleSetups();
$arAdrFields = CDeliveryYandexDriver::$options["ADDRESS"];

$arProps = CSaleOrderProps::GetList(
	array(),
	array()
);

while ($arProp = $arProps->Fetch())
{
	foreach ($arAdrFields as $code => $field)
		if ($arProp["CODE"] == $field)
			$arResult["ADDRESS_FIELDS"][$arProp["PERSON_TYPE_ID"]][$code] = $arProp["ID"];
		
	if ("Y" == $arProp["IS_LOCATION"])
		$arResult["LOCATION_FIELD"][$arProp["PERSON_TYPE_ID"]] = $arProp["ID"];
}

if (!empty($arParams["CITY_NAME"]))
	$arResult["CITY_NAME"] = ($arParams["~CITY_NAME"]);
else
{
	if (empty($arParams["CITY_ID"]))
		$arParams["CITY_ID"] = CDeliveryYandexHelper::getDefaultCityFromModuleSale();
	
	if ($arParams["CITY_ID"])
	{
		$arCity = CDeliveryYandexHelper::getCityNameByID($arParams["CITY_ID"]);
		$arResult["CITY_NAME"] = $arCity["NAME"];
		
		if ($arCity["REGION"])
			$arResult["CITY_NAME"] = $arCity["REGION"] . " " . $arCity["NAME"];
	}
	else
		$arResult["ERRORS"][] = array("CODE" => "No CITY_ID or CITY_NAME");
}

//$arResult["ITEMS_DIMENSIONS"]
$basketFilter = false;
if ("Y" == $arParams["USE_ITEM"])
	$basketFilter = array("PRODUCT_ID" => $arParams["ITEM_ID"], "PRODUCT_QUANTITY" => $arParams["ITEM_QUANTITY"]);

CDeliveryYandexDriver::getOrderBasket($basketFilter);

$arResult["ORDER_DIMENSIONS"] = array(
	"WIDTH" => CDeliveryYandexDriver::$tmpOrderDimension["WIDTH"],
	"HEIGHT" => CDeliveryYandexDriver::$tmpOrderDimension["HEIGHT"],
	"LENGTH" => CDeliveryYandexDriver::$tmpOrderDimension["LENGTH"]
);

$arResult["TOTAL_PRICE"] = CDeliveryYandexDriver::$tmpOrderDimension["PRICE"];
$arResult["TOTAL_WEIGHT"] = CDeliveryYandexDriver::$tmpOrderDimension["WEIGHT"];
$arResult["TOTAL_QUANTITY"] = CDeliveryYandexDriver::$tmpOrderDimension["QUANTITY"];

CDeliveryYandexDriver::clearOrderData();
$this->IncludeComponentTemplate();