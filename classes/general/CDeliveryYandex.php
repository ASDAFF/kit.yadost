<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

cmodule::includeModule('sale');
IncludeModuleLangFile(__FILE__);

class CDeliveryYa{
	static $MODULE_ID = "yandex.delivery";
	static $locationTo;
	static $personType;
	static $deliveryID;
	
	static public function Init(){
		// получаем поставщиков услуг и делаем из них профили
		$arProfiles = array(
			"courier" => array(
				"TITLE" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_NAME_COURIER"),
				"DESCRIPTION" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_DESCR_COURIER"),
				"RESTRICTIONS_WEIGHT" => array(0,75000),
				"RESTRICTIONS_SUM" => array(0),
				"RESTRICTIONS_MAX_SIZE" => "0",
				"RESTRICTIONS_DIMENSIONS_SUM" => "0"
			),
			"pickup" => array(
				"TITLE" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_NAME_PICKUP"),
				"DESCRIPTION" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_DESCR_PICKUP"),
				"RESTRICTIONS_WEIGHT" => array(0,75000),
				"RESTRICTIONS_SUM" => array(0),
				"RESTRICTIONS_MAX_SIZE" => "0",
				"RESTRICTIONS_DIMENSIONS_SUM" => "0"
			),
			"post" => array(
				"TITLE" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_NAME_POST"),
				"DESCRIPTION" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_DESCR_POST"),
				"RESTRICTIONS_WEIGHT" => array(0,75000),
				"RESTRICTIONS_SUM" => array(0),
				"RESTRICTIONS_MAX_SIZE" => "0",
				"RESTRICTIONS_DIMENSIONS_SUM" => "0"
			)
		);
		
		return array(
			/* Basic description */
			"SID" => "tradeDeliveryYa",
			"NAME" => GetMessage("TRADE_YANDEX_DELIVERY_DELIV_NAME"),
			"DESCRIPTION" => GetMessage('TRADE_YANDEX_DELIVERY_DELIV_DESCR'),
			"DESCRIPTION_INNER" => GetMessage('TRADE_YANDEX_DELIVERY_DESCRIPTION_INNER'),
			"BASE_CURRENCY" => COption::GetOptionString("sale", "default_currency", "RUB"),
			"HANDLER" => __FILE__,

			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliveryYa", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliveryYa", "SetSettings"),

			"COMPABILITY" => array("CDeliveryYa", "Compability"),
			"CALCULATOR" => array("CDeliveryYa", "Calculate"),

			/* List of delivery profiles */
			"PROFILES" => $arProfiles,
		);
	}
	
	static public function SetSettings($arSettings){
		return serialize($arSettings);
	}
	
	static public function GetSettings($strSettings){
		return unserialize($strSettings);
	}
	
	static function getProfilesConversion()
	{
		return array(
			"PICKUP" => "pickup",
			"TODOOR" => "courier",
			"POST" => "post"
		);
	}
	
	static $cityFrom;
	static $cityTo;
	static $compabilityPerform = false;
	static $compabilityProfiles = array();
	static $calculateProfiles = array();
	static $calculateRequestResult = false;
	static $clearOrderData = true;
	
	static public function getDeliveryProfiles($arOrder, $arConfig)
	{
		if (self::$compabilityPerform)
			return self::$compabilityProfiles;
		
		self::$compabilityPerform = true;
		
		if (self::$clearOrderData)
		{
			self::$cityTo = null;
			CDeliveryYaDriver::clearOrderData();
		}
		
		if (empty(self::$cityTo))
		{
			$tmpCity = CDeliveryYaHelper::getCityNameByID($arOrder["LOCATION_TO"]);
			self::$cityTo = $tmpCity["NAME"];
		}
		
		$basketFilter = array();
		if ((self::isOrderDetailPage() || self::isOrderEditPage()) && $_GET["ID"])
			$basketFilter = array("ORDER_ID" => $_GET["ID"]);
			
		$orderItems = null;
		if (isset($arOrder["ITEMS"]) && $arOrder["ITEMS"])
			$orderItems = $arOrder["ITEMS"];
		
		CDeliveryYaDriver::getOrderBasket($basketFilter, $orderItems);
		CDeliveryYaDriver::getModuleSetups();
		
		$arCityFrom = CDeliveryYaHelper::getCityFromNames();
		$cityFrom = CDeliveryYaDriver::$options["cityFrom"];
		self::$cityFrom = $arCityFrom[$cityFrom];
		
		$obCache = new CPHPCache();
		$cachename = 
			"trade_yandex_delivery|".
			self::$cityFrom."|".
			self::$cityTo."|".
			CDeliveryYaDriver::$options["to_yd_warehouse"];
			
		$arNeedDims = array(
			"WEIGHT",
			"LENGTH",
			"WIDTH",
			"HEIGHT",
			"PRICE"
		);
		foreach ($arNeedDims as $dim)
			$cachename .= "|".CDeliveryYaDriver::$tmpOrderDimension[$dim];
		
		// оценочная стоимость
		if (self::$assessedCostPercent === null)
			self::$assessedCostPercent = FloatVal(COption::GetOptionString(CDeliveryYaDriver::$MODULE_ID, 'assessedCostPercent', '100'));
		
		$cachename .= "|". self::$assessedCostPercent;
		
		if($obCache->InitCache(defined("TRADE_YANDEX_DELIVERY_CACHE_TIME")?TRADE_YANDEX_DELIVERY_CACHE_TIME:86400,$cachename,"/trade_yandex_delivery/") && !defined("TRADE_YANDEX_DELIVERY_NOCACHE"))
		{
			$res = $obCache->GetVars();
		}
		else
		{
			$arSend = array(
				"city_from" => self::$cityFrom,
				"city_to" => self::$cityTo,
				
				"weight" => CDeliveryYaDriver::$tmpOrderDimension["WEIGHT"],
				"height" => CDeliveryYaDriver::$tmpOrderDimension["HEIGHT"],
				"width" => CDeliveryYaDriver::$tmpOrderDimension["WIDTH"],
				"length" => CDeliveryYaDriver::$tmpOrderDimension["LENGTH"],
				
				"total_cost" => CDeliveryYaDriver::$tmpOrderDimension["PRICE"],
				"order_cost" => CDeliveryYaDriver::$tmpOrderDimension["PRICE"],
				"assessed_value" => CDeliveryYaDriver::$tmpOrderDimension["PRICE"] * (self::$assessedCostPercent/100),
				
				"to_yd_warehouse" => CDeliveryYaDriver::$options["to_yd_warehouse"]=="Y"?1:0
			);
			
			$method = "searchDeliveryList";
			
			$res = CDeliveryYaHelper::convertFromUTF(CDeliveryYaDriver::MakeRequest($method, $arSend));
			
			if ($res["status"] == "ok")
			{
				$obCache->StartDataCache();
				$obCache->EndDataCache($res);
			}
		}
		
		self::$calculateRequestResult = false;
		
		if ($res["status"] == "ok")
		{
			self::$calculateRequestResult = (array) $res["data"];
			$arProfiles = array();

			$arProfilesConversion = self::getProfilesConversion();// для конвертации названий тарифов из ЯД в битриксовые профили
			foreach ($res["data"] as $tariff)
			{
				$profileType = $arProfilesConversion[$tariff["type"]];
				if (
					empty($arProfiles[$profileType]) || 
					($tariff["costWithRules"] < $arProfiles[$profileType]["price"]) 
				)
				{
					$addTariff = true;
					
					if ($profileType == "pickup")
						if (empty($tariff["pickupPoints"]))
							$addTariff = false;
							
					
					if ($addTariff)
						$arProfiles[$profileType] = array(
							"price" => $tariff["costWithRules"],
							"term" => $tariff["days"],
	                        "daysMin" => $tariff["minDays"],
	                        "daysMax" => $tariff["maxDays"],
						);
				}
			}

			// если заказ уже создан и редактируется, то надо подсунуть рассчитанную уже стоимость доставки
			$orderID = false;
			if ($_REQUEST["order_id"])
				$orderID = $_REQUEST["order_id"];
			if ($_REQUEST["formData"]["order_id"])
				$orderID = $_REQUEST["formData"]["order_id"];
			
			if ($orderID)
			{
				$orderInfo = CDeliveryYaSqlOrders::getList(array(
					"filter" => array("ORDER_ID" => $orderID)
				))->Fetch();
				
				$orderInfo = json_decode(CDeliveryYaHelper::convertToUTF($orderInfo["PARAMS"]), true);
				
				$profileType = $arProfilesConversion[$orderInfo["type"]];
				$arProfiles[$profileType] = array(
					"price" => $orderInfo["costWithRules"],
					"term" => $orderInfo["days"],
					"daysMin" => $orderInfo["minDays"],
					"daysMax" => $orderInfo["maxDays"],
				);
			}
			
			self::$compabilityProfiles = array_keys($arProfiles);
			self::$calculateProfiles = $arProfiles;
		}
		
		return self::$compabilityProfiles;
	}
	
	// получаем пересчитанные данные заказа по AJAX
	static $assessedCostPercent = null;
	static public function calculateOrder($params)
	{
		if (!CDeliveryYaHelper::isAdmin("R"))
			CDeliveryYaHelper::throwException("Access denied");
		
		if (empty($params["ORDER_ID"]))
			CDeliveryYaHelper::throwException("Order not found", $params);
		
		$orderID = $params["ORDER_ID"];
		
		// получаем тип плательщика
		$orderData = CDeliveryYaDriver::getOrder($orderID);
		
		// вытаскиваем местоположение
		$arLocationProp = CSaleOrderProps::GetList(
			array(),
			array("TYPE" => "LOCATION", "PERSON_TYPE_ID" => $orderData["PERSON_TYPE_ID"])
		)->Fetch();
		
		$locationValue = CSaleOrderPropsValue::GetList(
			array(),
			array("ORDER_PROPS_ID" => $arLocationProp["ID"], "ORDER_ID" => $orderID)
		)->Fetch();
		
		$calculateResult = array();
		if ($locationValue["VALUE"] && $orderData["PERSON_TYPE_ID"])
		{
			if (isset($params["data"]["formData"]["assessedCostPercent"]))
				self::$assessedCostPercent = FloatVal($params["data"]["formData"]["assessedCostPercent"]);
			
			$arOrderCompabilityData = array(
				"LOCATION_TO" => $locationValue["VALUE"],
				"PERSON_TYPE_ID" => $orderData["PERSON_TYPE_ID"],
			);
			
			// подставляем габариты с формы отправки заявки
			if ($params["data"]["formData"])
			{
				CDeliveryYaDriver::getOrderBasket(array("ORDER_ID" => $orderID));
				CDeliveryYaDriver::$tmpOrderDimension["WEIGHT"] = $params["data"]["formData"]["WEIGHT"];
				CDeliveryYaDriver::$tmpOrderDimension["LENGTH"] = $params["data"]["formData"]["LENGTH"];
				CDeliveryYaDriver::$tmpOrderDimension["WIDTH"] = $params["data"]["formData"]["WIDTH"];
				CDeliveryYaDriver::$tmpOrderDimension["HEIGHT"] = $params["data"]["formData"]["HEIGHT"];
				
				// не чистим данные до запроса на расчет доставки
				self::$clearOrderData = false;
			}
			
			self::Compability($arOrderCompabilityData, false);
			$calculateResult = self::$calculateRequestResult;
			
			if ($calculateResult && is_array($calculateResult))
			{
				$deliveryType = $params["data"]["widgetData"]["type"];
				$tariffID = $params["data"]["widgetData"]["tariffId"];
				
				$arResult = false;
				foreach ($calculateResult as $res)
				{
					if ($res["type"] == $deliveryType && IntVal($res["tariffId"]) == IntVal($tariffID))
					{
						// собираем для товаров заказа данные для открытия их по ссылке
						if (!CModule::IncludeModule("iblock"))
							CDeliveryYaHelper::throwException("Module iblock not found");
						
						$arGoodsIDs = array_merge(CDeliveryYaDriver::$zeroGabsGoods, CDeliveryYaDriver::$zeroWeightGoods);
						$zeroGabs = array();
						$zeroWeight = array();
						foreach ($arGoodsIDs as $elemID)
						{
							$dbRes = CIBlockElement::GetList(
								array(),
								array("ID" => $elemID)
							)->Fetch();
							
							$tmpEl = array(
								"ID" => $elemID,
								"IBLOCK_TYPE" => $dbRes["IBLOCK_TYPE_ID"],
								"IBLOCK_ID" => $dbRes["IBLOCK_ID"]
							);
							// $tmpEl = $dbRes;
							
							if (CDeliveryYaDriver::$zeroGabsGoods[$elemID])
								$zeroGabs[$elemID] = $tmpEl;
							
							if (CDeliveryYaDriver::$zeroWeightGoods[$elemID])
								$zeroWeight[$elemID] = $tmpEl;
						}
						
						$arResult = array(
							"minDays" => $res["minDays"],
							"maxDays" => $res["maxDays"],
							"costWithRules" => $res["costWithRules"],
							
							"is_ds_import_available" => $res["delivery"]["is_ds_import_available"],// само склад СД
							"is_ds_withdraw_available" => $res["delivery"]["is_ds_withdraw_available"],// забор склад СД
							"is_ff_import_available" => $res["delivery"]["is_ff_import_available"],// само ед склад
							"is_ff_withdraw_available" => $res["delivery"]["is_ff_withdraw_available"],// забор ед склад
							
							"date_limits" => array(
								"import" => array(
									"ds" => CDeliveryYaDriver::convertDataToAdmin($res["date_limits"]["import"]["min"]),
									"ff" => CDeliveryYaDriver::convertDataToAdmin($res["date_limits"]["import_sort"]["min"])
								),
								"withdraw" => array(
									"ds" => CDeliveryYaDriver::convertDataToAdmin($res["date_limits"]["withdraw"]["min"]),
									"ff" => CDeliveryYaDriver::convertDataToAdmin($res["date_limits"]["withdraw_sort"]["min"])
								)
							),
							
							"zeroGabs" => $zeroGabs,
							"zeroWeight" => $zeroWeight,
							"totalWeightMoreDefault" => CDeliveryYaDriver::$totalWeightMoreDefault,
							"isZeroGabsWeight" => !(empty($zeroGabs) && empty($zeroWeight))
						);
					}
				}
				
				if (empty($arResult))
					return false;
				else
					return array_merge($arResult, array("debug" => CDeliveryYaDriver::$debug));
			}
			else
				CDeliveryYaHelper::throwException("No delivery calculated", array($params, $calculateResult));
		}
		else
			CDeliveryYaHelper::throwException("Cant find LOCATION or PERSON_TYPE", array($params, $orderData, $arLocationProp, $locationValue, $calculateResult));
	
		return true;
	}
	
	static public function Compability($arOrder, $arConfig)
	{
		self::$locationTo = $arOrder["LOCATION_TO"];
		self::$personType = $arOrder["PERSON_TYPE_ID"];
		
		$ifPrevent = true;
		foreach(GetModuleEvents(self::$MODULE_ID, "onCompabilityBefore", true) as $arEvent)
		{
			$eventRet = ExecuteModuleEventEx($arEvent,Array($arOrder, $arConfig));
			if(!$eventRet)
				$ifPrevent = false;
		}
		
		if (!$ifPrevent)
			return array();
		
		// получаем профили доставки
		$arDeliveryProfiles = self::getDeliveryProfiles($arOrder, $arConfig);
		
		foreach(GetModuleEvents(self::$MODULE_ID, "onCompability", true) as $arEvent)
			ExecuteModuleEventEx($arEvent,Array($arOrder, $arConfig, &$arDeliveryProfiles));
		
		return $arDeliveryProfiles;
	}
	
	static public function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)//расчет стоимости
	{
		// если выключено Считать стоимость доставки сразу
		if (!self::$compabilityPerform)
			self::Compability($arOrder, $arConfig);
		
		$deliveryPrice = self::$calculateProfiles[$profile]["price"];
		$term = self::$calculateProfiles[$profile]["term"];
		$daysMin = self::$calculateProfiles[$profile]["daysMin"];
		$daysMax = self::$calculateProfiles[$profile]["daysMax"];
		
		$requestProfilePrices = "{}";
		if ($_REQUEST["yd_ajaxDeliveryPrice"])
			$requestProfilePrices = $_REQUEST["yd_ajaxDeliveryPrice"];
		if ($_REQUEST["order"]["yd_ajaxDeliveryPrice"])
			$requestProfilePrices = $_REQUEST["order"]["yd_ajaxDeliveryPrice"];
		
		if (!empty($requestProfilePrices))
		{
			if (!$tmpProfilePrices = json_decode($requestProfilePrices, true))
				$tmpProfilePrices = json_decode(CDeliveryYaHelper::convertToUTF($requestProfilePrices), true);
			
			$requestProfilePrices = $tmpProfilePrices;
		}
		else
			$requestProfilePrices = array();
		
		$deliveryProvider = "";
		if ($requestProfilePrices[$profile])
		{
			$deliveryPrice = $requestProfilePrices[$profile]["price"];
			$term = $requestProfilePrices[$profile]["term"];
			$deliveryProvider = CDeliveryYaHelper::convertFromUTF($requestProfilePrices[$profile]["provider"]) . "<br/>";
		}
		
        $arReturn = array(
            "RESULT" => "OK",
            "PERIOD_FROM" => $daysMin,
            "PERIOD_TO" => $daysMax,
            "VALUE" => $deliveryPrice,
            "TRANSIT" => $term . getMessage("TRADE_YANDEX_DELIVERY_JS_DAY") . "<br/>" . $deliveryProvider . "<a id = 'yandex_delivery_inject_".$profile."'></a>"
        );
		
		foreach(GetModuleEvents(self::$MODULE_ID, "onCalculate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent,Array(&$arReturn,$profile,$arConfig,$arOrder));
		
		return $arReturn;
	}
	
	static $selectedDelivery = "";
	static public function pickupLoader($arResult, $arUserResult)
	{
		if(!CDeliveryYaHelper::isActive())
			return;
		
		self::$selectedDelivery = $arUserResult['DELIVERY_ID'];
		self::$personType = $arUserResult["PERSON_TYPE_ID"];
		self::$locationTo = $arUserResult["DELIVERY_LOCATION"];
	}
	
	static public function setLocationFromCookie(&$arResult, &$arUserResult, &$arParams)
	{
		$cityGeo = CDeliveryYaHelper::convertFromUTF($_COOKIE["city_to"]);// город из геовиджета
	
		if (!empty($cityGeo))
		{
			$code = CDeliveryYaHelper::getCityCodeByName($cityGeo);
			
			if ($code)
			{
				$db_props = CSaleOrderProps::GetList(
					array("SORT" => "ASC"),
					array("TYPE" => "LOCATION")
				);
				
				if ($props = $db_props->Fetch())
				{	
					if($props['DEFAULT_VALUE'] == $code)
						return true;
					
					$arFields = array(
					   "DEFAULT_VALUE" => $code,
					);
					
					CSaleOrderProps::Update($props["ID"], $arFields);
				}
			}
		}
		
		return true;
	}
	
	static public function loadComponent(&$arResult, &$arUserResult) // подключает компонент
	{
		self::$personType = $arUserResult["PERSON_TYPE_ID"];
		self::$locationTo = $arUserResult["DELIVERY_LOCATION"];
		self::$selectedDelivery = $arUserResult['DELIVERY_ID'];
		
		if(CDeliveryYaHelper::isActive() && $_REQUEST['is_ajax_post'] != 'Y' && $_REQUEST["AJAX_CALL"] != 'Y' && !$_REQUEST["ORDER_AJAX"])
		{
			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				"trade:yandex.deliveryPickup",
				"order",
				array(
					"WIDGET_CODE" => COption::GetOptionString(self::$MODULE_ID, "basketWidget"),
					"CITY_ID" => self::$locationTo,
					"USE_BASKET" => "Y",
					"PVZ_ID" => COption::GetOptionString(self::$MODULE_ID,"idOfPVZ", ""),
					"PERSON_TYPE" => self::$personType,
					"TO_DELIVERY_YANDEX_WAREHOUSE" => ("Y" == COption::GetOptionString(self::$MODULE_ID,"to_yd_warehouse", ""))?1:0
				),
				false,
				array("HIDE_ICONS" => "Y")
			);
		}
	}
	
	static public function onEpilog(){//отображение формы отправки заявки
		CDeliveryYaHelper::checkLocationChange();
	
		// проверяем надо ли отображать сообщения об изменениях заказа и отображаем их
		if (defined('ADMIN_SECTION') && /*$GLOBALS["USER"]->IsAdmin()*/CDeliveryYaHelper::isAdmin("R"))
			CDeliveryYaHelper::showMessageNotice();
		
		if(
			!self::isOrderDetailPage() || 
			!cmodule::includeModule('sale') ||
			!CDeliveryYaHelper::isAdmin("R")
		)
			return false;
		
		include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/js/".self::$MODULE_ID."/orderDetail.php");
		
		return true;
	}
	
	// проверяет не в карточке ли заказа мы находимся
	static public function isOrderDetailPage()
	{
		if (
			preg_match("/\/bitrix\/admin\/sale_order_detail.php/", $_SERVER['PHP_SELF']) ||
			preg_match("/\/bitrix\/admin\/sale_order_view.php/", $_SERVER['PHP_SELF'])
		)
			return true;
		else
			return false;
	}
	
	// проверяет не в редактировании заказа мы находимся
	static public function isOrderEditPage()
	{
		if (
			preg_match("/\/bitrix\/admin\/sale_order_new.php/", $_SERVER['PHP_SELF']) ||
			preg_match("/\/bitrix\/admin\/sale_order_edit.php/", $_SERVER['PHP_SELF'])
		)
			return true;
		else
			return false;
	}
	
	static public function onBufferContent(&$content) {
		// Админка нам не нужна тут
		if ((defined("ADMIN_SECTION") && ADMIN_SECTION===true) || strpos($_SERVER['PHP_SELF'], "/bitrix/admin") ===
            true) return;
		
		if (CDeliveryYaHelper::isActive() && self::$personType && self::$selectedDelivery)
		{
			$noJson = self::no_json($content);
			$arCity = CDeliveryYaHelper::getCityNameByID(self::$locationTo);
			
			// Таким вот странным способом мы передаем наши данные из PHP в js в момент Ajax-запроса.
			if(($_REQUEST['is_ajax_post'] == 'Y' || $_REQUEST["AJAX_CALL"] == 'Y') && self::$locationTo && ($_REQUEST["confirmorder"] != "Y") && $noJson) 
			{
				$content .= '<input type="hidden" id="yd_ajaxLocation" name="yd_ajaxLocation" value=\''.$arCity["REGION"] . " " . $arCity["NAME"].'\' />';
				$content .= '<input type="hidden" id="yd_ajaxDeliveryID" name="yd_ajaxDeliveryID" value=\''.self::$selectedDelivery.'\' />';
				$content .= '<input type="hidden" id="yd_ajaxPersonType" name="yd_ajaxPersonType" value=\''.self::$personType.'\' />';
			}
			elseif(($_REQUEST['action'] == 'refreshOrderAjax' || $_REQUEST['soa-action'] == 'refreshOrderAjax') && !$noJson)
			{
				$content = substr($content,0,strlen($content)-1).',"trade_yandex_delivery":{"yd_ajaxPersonType":"'.self::$personType.'","yd_ajaxDeliveryID":"'.self::$selectedDelivery.'", "yd_ajaxLocation":"'.CDeliveryYaHelper::convertToUTF($arCity["REGION"] . " " . $arCity["NAME"]).'"}}';
			}
		}
	}
	
	static public function no_json(&$wat){
		return is_null(json_decode(CDeliveryYaHelper::convertToUTF($wat),true));
	}
	
	static public function getDeliveryTerm($min, $max)
	{
		if (empty($min) && empty($max))
			return "";
		
		if (empty($min))
			return $max;
		
		if (empty($max))
			return $min;
		
		if ($min == $max)
			return $min;
		
		return $min ." - ". $max;
	}
	
	static public function orderCreate($orderID, $orderFields)
	{
		if ($_REQUEST["yd_is_select"] == "tradeDeliveryYa")
		{
			if (!cmodule::includemodule('sale'))
				return true;
			
			// запоминаем данные заказа, если доставка - яндекс
			$Data = array(
				"ORDER_ID" => $orderID,
				"PARAMS" => $_REQUEST["yd_deliveryData"]
			);
			
			CDeliveryYaSqlOrders::Add($Data);
			
			if (CDeliveryYaHelper::controlProps())
			{
				$orderPropValue = CDeliveryYaHelper::getOrderPropsCodeFormID();
				
				$arOrderPropsCode = CDeliveryYaHelper::getOrderPropsCode();
				
				foreach ($arOrderPropsCode as $propCode)
				{
					if (!empty($_REQUEST[$orderPropValue[$propCode]]))
					{
						$op = CSaleOrderProps::GetList(array(), array("PERSON_TYPE_ID" => $orderFields['PERSON_TYPE_ID'], "CODE" => $propCode))->Fetch();
						
						if ($op)
						{
							$arFields = array(
								"ORDER_ID" => $orderID,
								"ORDER_PROPS_ID" => $op['ID'],
								"NAME" => GetMessage("TRADE_YANDEX_DELIVERY_prop_name_" . $propCode),
								"CODE" => $propCode,
								"VALUE" => preg_replace("/\"/", "", $_REQUEST[$orderPropValue[$propCode]])
							);
							
							$dbOrderProp = CSaleOrderPropsValue::GetList(
								array(),
								array(
									"ORDER_PROPS_ID" => $op['ID'],
									"CODE" => $propCode,
									"ORDER_ID" => $orderID
								)
							);
							
							if ($existProp = $dbOrderProp->Fetch())
								CSaleOrderPropsValue::Update($existProp["ID"], $arFields);
							else
								CSaleOrderPropsValue::Add($arFields);
						}
					}
				}
			}
		}
		
		return true;
	}
}