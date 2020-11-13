<?
/**
 * Copyright (c) 24/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
// заменяем данные в виджете
$arReplace = array(
	"weight" => $arResult["TOTAL_WEIGHT"],
	"cost" => $arResult["TOTAL_PRICE"],
	"height" => $arResult["ORDER_DIMENSIONS"]["HEIGHT"],
	"length" => $arResult["ORDER_DIMENSIONS"]["LENGTH"],
	"width" => $arResult["ORDER_DIMENSIONS"]["WIDTH"]
);
$widgetCode = $arParams["~WIDGET_CODE"];

foreach ($arReplace as $data => $value)
{
	$pattern = '/data-'.$data.'=".*?"/i';
	$widgetCode = preg_replace($pattern, $data."=\"".$value."\" ", $widgetCode);
}

// вставляем город получения
$widgetCode = preg_replace('/<meta /', '<meta data-city_to = "'.$arResult["CITY_NAME"].'" ', $widgetCode);

echo $widgetCode;
?>