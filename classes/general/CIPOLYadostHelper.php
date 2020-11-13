<?
/**
 * Copyright (c) 24/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

IncludeModuleLangFile(__FILE__);

class CIPOLYadostHelper
{
	static $exceptionData = array();
	
	// чистка кеша
	static $isActive = null;
	
	// проверка прав доступа на модуль
	static $noticeFileName = "";
	static $changeFileName = "";
	static $orderBeforeUpdate;
	
	// Проверка активности СД
	static $isOrderAdd = false;
	static $orderBasketBeforeUpdate;
	static $deliveryIDs = null;
	static $chosenSender = null;
	
	// добавление свойства
	
	public static function clearCache()
	{
		if (!CIPOLYadostHelper::isAdmin())
			CIPOLYadostHelper::throwException("Access denied");
		
		$obCache = new CPHPCache();
		$obCache->CleanDir('/IPOLyadost/');
		
		return true;
	}
	
	public static function isAdmin($right = "W")
	{
		$userRight = CMain::GetUserRight(CIPOLYadostDriver::$MODULE_ID);
		
		$DEPTH = array(
			'D' => 1,
			'R' => 2,
			'W' => 3
		);
		
		return ($DEPTH[$right] <= $DEPTH[$userRight]);
	}
	
	//кодировки
	
	public static function throwException($code, $data = null)
	{
		self::$exceptionData = array("code" => self::convertToUTF($code), "data" => $data, "debug" => CIPOLYadostDriver::$debug);
		
		$dataToCode = print_r($data, true);
		$dataToCode .= print_r(array("debug" => CIPOLYadostDriver::$debug), true);
		
		self::errorLog(CIPOLYadostDriver::$debug);
		
		throw new Exception($code . "\n" . $dataToCode);
	}
	
	static public function getDelivery()
	{
		if (!cmodule::includeModule("sale"))
			return false;
		
		if (self::isConverted())
		{
			$dS = Bitrix\Sale\Delivery\Services\Table::getList(array(
				'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
				'filter' => array('CODE' => 'ipolYadost')
			))->Fetch();
		}
		else
			$dS = CSaleDeliveryHandler::GetBySID('ipolYadost')->Fetch();
		
		return $dS;
	}
	
	static public function isActive()
	{
		if (is_null(self::$isActive))
		{
			$dS = self::getDelivery();
			
			self::$isActive = ($dS && $dS['ACTIVE'] == 'Y');
		}
		
		return self::$isActive;
	}
	
	static public function getOrderPropsCode()
	{
		return array_keys(self::getOrderPropsCodeFormID());
	}
	
	static public function getOrderPropsCodeFormID()
	{
		return array(
			"ipol_yadost_PVZ_ADDRESS" => "yd_pvzAddressValue"
		);
	}
	
	static public function controlProps()
	{
		if (!CModule::IncludeModule("sale"))
			return false;
		
		$arPropertyCodes = self::getOrderPropsCode();
		
		$tmpGet = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			array("CODE" => $arPropertyCodes)
		);
		
		$existedProps = array();
		while ($tmpElement = $tmpGet->Fetch())
			$existedProps[$tmpElement["CODE"]][$tmpElement['PERSON_TYPE_ID']] = $tmpElement["ID"];
		
		$tmpGet = CSalePersonType::GetList(
			Array("SORT" => "ASC"),
			Array("ACTIVE" => "Y")
		);
		
		$allPayers = array();
		while ($tmpElement = $tmpGet->Fetch())
			$allPayers[] = $tmpElement['ID'];
		
		$return = true;
		// тут проверяем созданы ли все свойства
		foreach ($arPropertyCodes as $needCode)
			foreach ($allPayers as $payer)
				if (empty($existedProps[$needCode][$payer]))
				{
					$return = false;
					// записываем поля, которых нет
					$existedProps[$needCode][$payer] = 1;
				}
				else
					unset($existedProps[$needCode][$payer]);
		
		if ($return)
			return $return;
		
		// создаем свойства, каких нет
		$return = true;
		
		$PropsGroup = array();
		$tmpGet = CSaleOrderPropsGroup::GetList(
			array("SORT" => "ASC"),
			array(),
			false
		// array('nTopCount' => '1')
		);
		
		while ($tmpElement = $tmpGet->Fetch())
			$PropsGroup[$tmpElement["PERSON_TYPE_ID"]] = $tmpElement['ID'];
		
		foreach ($existedProps as $propCode => $prop)
			foreach ($prop as $payer => $val)
			{
				$arFields = array(
					"PERSON_TYPE_ID" => $payer,
					"NAME" => GetMessage('IPOLyadost_prop_name_' . $propCode),
					"TYPE" => "TEXT",
					"REQUIED" => "N",
					"DEFAULT_VALUE" => "",
					"SORT" => 100,
					"CODE" => $propCode,
					"USER_PROPS" => "Y",
					"IS_LOCATION" => "N",
					"IS_LOCATION4TAX" => "N",
					"PROPS_GROUP_ID" => $PropsGroup[$payer],
					"SIZE1" => 10,
					"SIZE2" => 1,
					"DESCRIPTION" => GetMessage('IPOLyadost_prop_descr_' . $propCode),
					"IS_EMAIL" => "N",
					"IS_PROFILE_NAME" => "N",
					"IS_PAYER" => "N",
					"IS_FILTERED" => "Y",
					"IS_ZIP" => "N",
					"UTIL" => "Y"
				);
				
				if (!CSaleOrderProps::Add($arFields))
					$return = false;
				
			}
		
		return $return;
	}
	
	static public function toUpper($str)
	{
//		$str = str_replace( //H8 ANSI
//			array(
//				GetMessage('IPOLyadost_LANG_YO_S'),
//				GetMessage('IPOLyadost_LANG_CH_S'),
//				GetMessage('IPOLyadost_LANG_YA_S')
//			),
//			array(
//				GetMessage('IPOLyadost_LANG_YO_B'),
//				GetMessage('IPOLyadost_LANG_CH_B'),
//				GetMessage('IPOLyadost_LANG_YA_B')
//			),
//			$str
//		);
//		if (function_exists('mb_strtoupper'))
//			return mb_strtoupper($str, LANG_CHARSET);
//		else
			return strtoupper($str);
	}
	
	// получаем города отправления
	
	static public function convertToUTF($handle)
	{
		if (LANG_CHARSET !== 'UTF-8')
		{
			if (is_array($handle))
				foreach ($handle as $key => $val)
				{
					unset($handle[$key]);
					$key = self::convertToUTF($key);
					$handle[$key] = self::convertToUTF($val);
				}
			else
				$handle = $GLOBALS['APPLICATION']->ConvertCharset($handle, LANG_CHARSET, 'UTF-8');
		}
		
		return $handle;
	}
	
	static public function convertFromUTF($handle)
	{
		if (LANG_CHARSET !== 'UTF-8')
		{
			if (is_array($handle))
				foreach ($handle as $key => $val)
				{
					unset($handle[$key]);
					$key = self::convertFromUTF($key);
					$handle[$key] = self::convertFromUTF($val);
				}
			else
				$handle = $GLOBALS['APPLICATION']->ConvertCharset($handle, 'UTF-8', LANG_CHARSET);
		}
		
		return $handle;
	}
	
	static public function isConverted()
	{
		return (COption::GetOptionString("main", "~sale_converted_15", 'N') == 'Y');
	}
	
	static public function getOrderLocationValue($orderID, $personType)
	{
		$dbProps = CSaleOrderProps::GetList(
			array(),
			array(
				"PERSON_TYPE_ID" => CIPOLYadostDriver::$tmpOrder["PERSON_TYPE_ID"],
				"TYPE" => "LOCATION",
				"ACTIVE" => "Y"
			)
		)->Fetch();
		
		$dbOrderProps = CSaleOrderPropsValue::GetList(
			array(),
			array("ORDER_ID" => $orderID, "ORDER_PROPS_ID" => $dbProps["ID"])
		)->Fetch();
		
		if ($dbOrderProps["VALUE"])
			return $dbOrderProps["VALUE"];
		else
			return null;
	}
	
	static public function getCityNameByID($locationID)
	{
		if (method_exists("CSaleLocation", "isLocationProMigrated") && CSaleLocation::isLocationProMigrated())
		{
			if (strlen($locationID) > 8)
				$cityID = CSaleLocation::getLocationIDbyCODE($locationID);
			else
				$cityID = $locationID;
			
			
			$locFilter = array(
				'=ID' => $cityID,
				'=NAME.LANGUAGE_ID' => LANGUAGE_ID
			);
			
			$locValue = \Bitrix\Sale\Location\LocationTable::getList(array(
				'filter' => $locFilter,
				'select' => array(
					'*',
					'NAME_RU' => 'NAME.NAME',
					''
				)
			))->Fetch();
			
			$arLocs = CSaleLocation::GetByID($cityID, LANGUAGE_ID);
			
			$arCity = array(
				"BITRIX_ID" => $arLocs["ID"],
				"CITY_ID" => $arLocs["CODE"],
				"CITY_NAME" => $arLocs["CITY_NAME"]
			);
					
			// $arCity = array(
				// "BITRIX_ID" => $locValue["ID"],
				// "CITY_ID" => $locValue["CODE"],
				// "CITY_NAME" => $locValue["NAME_RU"] 
			// );
			
			if ($arLocs["REGION_ID"])
			{
				$regionValue = \Bitrix\Sale\Location\LocationTable::getList(array(
					'filter' => [
						'=ID' => $locValue["REGION_ID"],
						'=NAME.LANGUAGE_ID' => LANGUAGE_ID
					],
					'select' => array(
						'*',
						'NAME_RU' => 'NAME.NAME',
						''
					)
				))->fetch();

				 //$arCity["REGION_NAME"] = $regionValue["NAME_RU"];
				$arCity["REGION_NAME"] = $arLocs["REGION_NAME"];

				//Заглушка для г. Москва и г. Севастополь
                if ($arCity["CITY_ID"] == "0000073738" || $arCity["CITY_ID"] == "0001092542") {
                    $arCity["REGION_NAME"] = null;
                }
			}
			else {
				if ($locValue["PARENT_ID"]) {
					$arCity = array(
					 "BITRIX_ID" => $locValue["ID"],
					 "CITY_ID" => $locValue["CODE"],
					 "CITY_NAME" => $locValue["NAME_RU"]
					);
				}
			}
		}
		else
			$arCity = CSaleLocation::GetByID($locationID);
		
		if ($arCity)
			return array(
				"BITRIX_ID" => $arCity["ID"],
				"CITY_ID" => $arCity["CITY_ID"],
				"NAME" => $arCity["CITY_NAME"],
				"REGION" => $arCity["REGION_NAME"]
			);

		return false;
	}
	
	// отображает окно с сообщением в админке об изменении заказа и т.д.
	
	static public function getCityCodeByName($cityName)
	{
		if (!CModule::IncludeModule("sale"))
			return false;
		
		$dbCity = CSaleLocation::GetList(
			array(),
			array(
				"CITY_NAME" => $cityName,
				"CITY_LID" => LANGUAGE_ID,
				"REGION_LID" => LANGUAGE_ID,
				"COUNTRY_LID" => LANGUAGE_ID
			)
		);
		
		$arCities = array();
		while ($arCity = $dbCity->Fetch())
			$arCities[] = $arCity;
		
		if (!empty($arCities[0]))
			$arCities = $arCities[0];
		else
			return false;
		
		return $arCities["ID"];
	}
	
	//////////////////////////////////// обработка старых событий /////////////////////////////////////////////////
	// до обновления заказа
	
	static public function getDeliveryStatuses()
	{
		$arStatus = array(
			"DRAFT",
			"CREATED",
			"SENDER_SENT",
			"DELIVERY_LOADED",
			"ERROR",
			"FULFILMENT_LOADED",
			"SENDER_WAIT_FULFILMENT",
			"SENDER_WAIT_DELIVERY",
			"FULFILMENT_ARRIVED",
			"FULFILMENT_PREPARED",
			"FULFILMENT_TRANSMITTED",
			"DELIVERY_AT_START",
			"DELIVERY_TRANSPORTATION",
			"DELIVERY_ARRIVED",
			"DELIVERY_TRANSPORTATION_RECIPIENT",
			"DELIVERY_ARRIVED_PICKUP_POINT",
			"DELIVERY_DELIVERED",
			"RETURN_PREPARING",
			"RETURN_ARRIVED_DELIVERY",
			"RETURN_ARRIVED_FULFILMENT",
			"RETURN_PREPARING_SENDER",
			"RETURN_RETURNED",
			"LOST",
			"UNEXPECTED",
			"CANCELED"
		);
		
		$arRetStatus = array();
		foreach ($arStatus as $status)
			$arRetStatus[$status] = GetMessage("IPOLyadost_YD_STATUS_" . $status);
		
		return $arRetStatus;
	}
	
	static public function getCityFromNames()
	{
		$arCities = array(
			"MOSCOW",
			"PITER"
		);
		
		$arResult = array();
		
		foreach ($arCities as $city)
			$arResult[$city] = GetMessage("IPOLyadost_CityFrom_" . $city);
		
		return $arResult;
	}
	
	static public function getNoticeFileName()
	{
		self::$noticeFileName = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . CIPOLYadostDriver::$MODULE_ID . "/private/notice.txt";
		self::$changeFileName = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . CIPOLYadostDriver::$MODULE_ID . "/private/change.txt";
		
		$oldNoticeFileName = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . CIPOLYadostDriver::$MODULE_ID . "/notice.txt";
		$oldChangeFileName = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . CIPOLYadostDriver::$MODULE_ID . "/change.txt";
		
		if (file_exists($oldNoticeFileName))
		{
			CopyDirFiles($oldNoticeFileName, self::$noticeFileName, true);
			unlink($oldNoticeFileName);
		}
		
		if (file_exists($oldChangeFileName))
		{
			CopyDirFiles($oldChangeFileName, self::$changeFileName, true);
			unlink($oldChangeFileName);
		}
	}
	
	
	// после обновления заказа
	
	static public function clearNoticeFile()
	{
		if (!CIPOLYadostHelper::isAdmin("R"))
			CIPOLYadostHelper::throwException("Access denied");
		
		self::getNoticeFileName();
		
		if (file_exists(self::$noticeFileName))
			unlink(self::$noticeFileName);
		
		return true;
	}
	
	static public function showMessageNotice()
	{
		/*
		self::getNoticeFileName();
		
		if (file_exists(self::$noticeFileName))
		{
			CJSCore::Init("jquery");
			
			$arNotice = file_get_contents(self::$noticeFileName);
			$arNotice = unserialize($arNotice);
			
			$noticeText = "";
			
			if (self::isConverted())
				$viewScript = "sale_order_view";
			else
				$viewScript = "sale_order_detail";
			
			foreach ($arNotice as $event => $values)
			{
				$noticeText .= "<div>";
				$noticeText .= "<p>" . GetMessage("IPOLyadost_NOTICE_WINDOW_MSG_" . $event) . "</p>";
				$noticeText .= "<div class = \"adm-workarea\">";
				
				foreach ($values as $orderID)
					$noticeText .= "<a class = \"adm-btn yadostWinNoticeButton\" href = \"/bitrix/admin/" . $viewScript . ".php?ID=" . $orderID . "&yadostOpenSendForm=Y\">" . $orderID . "</a> ";
				
				$noticeText .= "</div>";
				$noticeText .= "</div>";
			}
			
			ob_start();
			?>
			<style>
				#yadostWinNotice {
					position: fixed;
					z-index: 1000;
					top: 5px;
					font: 12px "Arial", "Times New Roman", serif;
					width: 400px;
					background: #434f5d;
					color: #FFFFFF;
				}

				#yadostWinNotice #yadostWinNoticeHeader {
					background-color: #37414B;
					padding: 1px;
					width: 100%;
					text-align: center;
					color: #FFFFFF;
				}

				#yadostWinNotice #yadostWinNoticeHeader a {
					color: #7ECEFD;
					text-decoration: none;
				}

				#yadostWinNotice #yadostWinNoticeHeader a:hover {
					text-decoration: underline;
				}

				#yadostWinNotice p {
					font-family: "PT Sans";
					font-size: 17.89px;
					margin: 1em;
				}

				#yadostWinNotice #yadostWinNoticeButton {

				}
			</style>
			<script>
				function yadostWinNoticeClose()
				{
					var ajaxData = {
						"action": "clearNoticeFile",
						"sessid": BX.bitrix_sessid()
					};

					$.ajax({
						url: "/bitrix/js/<?=CIPOLYadostDriver::$MODULE_ID?>/ajax.php",
						data: ajaxData,
						type: "POST",
						dataType: "json",
						error: function (XMLHttpRequest, textStatus)
						{
							console.log(XMLHttpRequest.responseText);
							console.log(textStatus);
						},
						success: function (data)
						{
							$("#yadostWinNotice").slideUp(500);
						}
					});
				}

				function CloseNotice()
				{
					document.getElementById("yadostWinNotice").style.display = "none";
					return false;
				}

				$(document).ready(function ()
				{
					var winNotice = '<div id="yadostWinNotice">';
					winNotice += '<table id="yadostWinNoticeHeader"><tr>';
					winNotice += '<td style="width:50px">&nbsp</td>';
					winNotice += '<td><?=GetMessage("IPOLyadost_NOTICE_WINDOW_HEADER");?></td>';
					winNotice += '<td><a href="javascript:void(0)" onclick="yadostWinNoticeClose()">X</a></td>';
					winNotice += '</tr></table>';
					winNotice += '<p id = \"yadostWinNoticeContent\">' + '<?=$noticeText?>' + '</p>';
					winNotice += '</div>';
					$("body").prepend(winNotice);

					var $windowNotive = $("#yadostWinNotice");
					$windowNotive.css({
						top: ($(window).height() - $windowNotive.height()) / 2,
						left: ($(window).width() - $windowNotive.width()) / 2,
					});
				});
			</script>
			<?
			$content = ob_get_contents();
			ob_end_clean();
			
			$GLOBALS["APPLICATION"]->AddHeadString($content);
		}
		*/
	}
	
	// добавление заказа
	
	static public function OnBeforeOrderUpdateHandler($orderID, $arFields)
	{
		if (self::$isOrderAdd || isset($arFields["LOCKED_BY"]))
			return true;
		
		CIPOLYadostDriver::$tmpOrder = false;
		self::$orderBeforeUpdate = CIPOLYadostDriver::getOrder($orderID);
		$needDeliveries = self::getDeliveryIDs();
		$curDeliveryID = self::$orderBeforeUpdate["DELIVERY_ID"];
		if (!in_array($curDeliveryID, $needDeliveries))
			return true;
		
		$_SESSION["ipol_orderIDBeforeUpdate"] = $orderID;
		$_SESSION["ipol_orderPropsBeforeUpdate"] = self::getOrderCheckedPropsValues($orderID);
		$_SESSION["ipol_orderLocationBeforeUpdate"] = self::getOrderLocationValue($orderID, self::$orderBeforeUpdate["PERSON_TYPE_ID"]);
		$_SESSION["ipol_orderCanceled"] = self::$orderBeforeUpdate["CANCELED"];
		
		return true;
	}
	
	static public function getOrderCheckedPropsValues($orderID)
	{
		// свойства заказа, которые необходимо проверить
		CIPOLYadostDriver::$tmpOrderProps = false;
		$orderProps = CIPOLYadostDriver::getOrderProps($orderID);
		$propsToCheck = self::getUpdatedEventProps();
		
		$saveProps = array();
		foreach ($propsToCheck as $prop)
			$saveProps[$prop] = $orderProps[$prop];
		
		return $saveProps;
	}
	
	// обновление корзины
	
	static public function OnOrderUpdateHandler($orderID, $arFields)
	{
		if (self::$isOrderAdd || isset($arFields["LOCKED_BY"]) || (!isset($arFields["DELIVERY_ID"]) && !isset($arFields["CANCELED"])))
			return true;
		
		$arChange = false;
		$needDeliveries = self::getDeliveryIDs();
		$curDeliveryID = self::$orderBeforeUpdate["DELIVERY_ID"];
		
		if (isset($arFields["DELIVERY_ID"]) && $arFields["DELIVERY_ID"] != self::$orderBeforeUpdate["DELIVERY_ID"])
		{
			$curDeliveryID = $arFields["DELIVERY_ID"];
			
			$arChange[] = array(
				"event" => "CHANGE_ORDER",
				"orderID" => $orderID
			);
		}
		
		CIPOLYadostDriver::getOrderConfirm($orderID);
		if (
			isset($arFields["CANCELED"]) &&
			$arFields["CANCELED"] != self::$orderBeforeUpdate["CANCELED"] &&
			self::$orderBeforeUpdate["CANCELED"] == "N" &&
			in_array($curDeliveryID, $needDeliveries) &&
			!empty(CIPOLYadostDriver::$tmpOrderConfirm["savedParams"]["delivery_ID"])
		)
			$arChange[] = array(
				"event" => "CANCEL_ORDER",
				"orderID" => $orderID
			);
		
		if ($arChange)
			foreach ($arChange as $change)
				self::updateNoticeFileData($change);
		
		return true;
	}
	
	static public function checkLocationChange()
	{
		if (!$_SESSION["ipol_orderIDBeforeUpdate"])
		{
			unset($_SESSION["ipol_orderIDBeforeUpdate"]);
			unset($_SESSION["ipol_orderLocationBeforeUpdate"]);
			unset($_SESSION["ipol_orderPropsBeforeUpdate"]);
			
			return false;
		}
		
		$orderID = $_SESSION["ipol_orderIDBeforeUpdate"];
		unset($_SESSION["ipol_orderIDBeforeUpdate"]);
		
		$orderLocationBeforeUpdate = $_SESSION["ipol_orderLocationBeforeUpdate"];
		unset($_SESSION["ipol_orderLocationBeforeUpdate"]);
		
		$orderPropsBeforeUpdate = $_SESSION["ipol_orderPropsBeforeUpdate"];
		unset($_SESSION["ipol_orderPropsBeforeUpdate"]);
		
		CIPOLYadostDriver::$tmpOrder = false;
		CIPOLYadostDriver::getOrder($orderID);
		$curLocation = self::getOrderLocationValue($orderID, CIPOLYadostDriver::$tmpOrder["PERSON_TYPE_ID"]);
		
		$locationChange = false;
		if ($orderLocationBeforeUpdate != $curLocation)
			$locationChange = true;
		
		$propsChanged = false;
		$curProps = self::getOrderCheckedPropsValues($orderID);
		foreach ($orderPropsBeforeUpdate as $key => $val)
			if ($orderPropsBeforeUpdate[$key] != $curProps[$key])
				$propsChanged = true;
		
		if ($locationChange || $propsChanged)
		{
			$arChange = array(
				"event" => "CHANGE_ORDER",
				"orderID" => $orderID
			);
			
			self::updateNoticeFileData($arChange);
		}
		
		return true;
	}
	
	static public function OnOrderAddHandler($orderID, $arFields)
	{
		self::$isOrderAdd = true;
		
		unset($_SESSION["ipol_orderLocationBeforeUpdate"]);
		unset($_SESSION["ipol_orderIDBeforeUpdate"]);
		
		return true;
	}
	
	// добавление товара в заказ
	
	static public function OnBeforeBasketUpdateHandler($basketID, &$arFields)
	{
		if (empty($arFields["ORDER_ID"]) || self::$isOrderAdd)
			return true;
		
		// собираем корзину заказа до изменений
		CIPOLYadostDriver::$tmpOrderBasket = false;
		self::$orderBasketBeforeUpdate = CIPOLYadostDriver::getOrderBasket(array("ORDER_ID" => $arFields["ORDER_ID"]));
		
		return true;
	}
	
	// удаление товара в заказ
	
	static public function OnBasketUpdateHandler($basketID, $arFields)
	{
		if (empty($arFields["ORDER_ID"]) || self::$isOrderAdd)
			return true;
		
		$orderID = $arFields["ORDER_ID"];
		$needDeliveries = self::getDeliveryIDs();
		$arOrder = CIPOLYadostDriver::getOrder($orderID);
		
		if (in_array($arOrder["DELIVERY_ID"], $needDeliveries))
		{
			// собираем текущую корзину заказа
			CIPOLYadostDriver::$tmpOrderBasket = false;
			CIPOLYadostDriver::getOrderBasket(array("ORDER_ID" => $orderID));
			
			// анализируем изменения
			$changeOrder = self::compareBasket(self::$orderBasketBeforeUpdate, CIPOLYadostDriver::$tmpOrderBasket);
			
			if ($changeOrder)
			{
				$arChange = array(
					"event" => "CHANGE_ORDER",
					"orderID" => $orderID
				);
				
				self::updateNoticeFileData($arChange);
			}
		}
		
		return true;
	}
	
	// сравнивает корзины заказов
	
	static public function OnBasketAddHandler($basketID, $arFields)
	{
		if (empty($arFields["ORDER_ID"]))
			return true;
		
		$orderID = $arFields["ORDER_ID"];
		
		$arChange = array(
			"event" => "CHANGE_ORDER",
			"orderID" => $orderID
		);
		
		self::updateNoticeFileData($arChange);
		
		return true;
	}
	
	//////////////////////////////////// события ядра D7 /////////////////////////////////////////////////
	// сменили город
	
	static public function OnBeforeBasketDeleteHandler($basketID)
	{
		$arBasket = CSaleBasket::GetList(
			array(),
			array("ID" => $basketID)
		)->Fetch();
		
		if (empty($arBasket["ORDER_ID"]))
			return true;
		
		$orderID = $arBasket["ORDER_ID"];
		
		$arChange = array(
			"event" => "CHANGE_ORDER",
			"orderID" => $orderID
		);
		
		self::updateNoticeFileData($arChange);
		
		return true;
	}
	
	static public function compareBasket($arr1, $arr2)
	{
		foreach ($arr1 as $goodID => $good)
		{
			if (empty($arr2[$goodID]))
				return true;
			
			if ($arr2[$goodID]["QUANTITY"] != $arr1[$goodID]["QUANTITY"])
				return true;
		}
		
		foreach ($arr2 as $goodID => $good)
		{
			if (empty($arr1[$goodID]))
				return true;
			
			if ($arr2[$goodID]["QUANTITY"] != $arr1[$goodID]["QUANTITY"])
				return true;
		}
		
		return false;
	}
	
	// отмена заказа
	
	/**
	 * сменили доставку
	 *
	 * @param \Bitrix\Sale\PropertyValue $entity
	 * @param string                     $name
	 * @param string                     $value
	 * @param string                     $old_value
	 *
	 * @return bool
	 */
	static public function OnSalePropertyValueSetFieldHandler($entity, $name, $value, $old_value)
	{
		$needDeliveries = self::getDeliveryIDs();
		$changedProp = $entity->getProperty();
		
		/** @var \Bitrix\Sale\Order $order */
		$order = $entity->getCollection()->getOrder();
		
		if (!is_null($order))
		{
			$deliveryID = $order->getField("DELIVERY_ID");
			$orderID = $order->getField("ID");
		}
		else
			return true;
		
		// свойства заказа, которые необходимо проверить
		$propsToCheck = self::getCheckedUpdatedOrderProps();
		
		if ($orderID &&
			($changedProp["TYPE"] == "LOCATION" || in_array($changedProp["CODE"], $propsToCheck)) &&
			$value != $old_value &&
			in_array($deliveryID, $needDeliveries)
		)
		{
			$arChange = array(
				"event" => "CHANGE_ORDER",
				"orderID" => $orderID
			);
			
			self::updateNoticeFileData($arChange);
		}
		
		return true;
	}
	
	// изменение корзины заказа
	
	/**
	 * @param \Bitrix\Sale\Shipment $entity
	 * @param string                $name
	 * @param string                $value
	 * @param string                $old_value
	 *
	 * @return bool
	 */
	static public function OnSaleShipmentSetFieldHandler($entity, $name, $value, $old_value)
	{
		$needDeliveries = self::getDeliveryIDs();
		
		/** @var \Bitrix\Sale\Order $order */
		$order = $entity->getCollection()->getOrder();
		
		if (!is_null($order))
			$orderID = $order->getField("ID");
		else
			return true;
		
		if ($orderID && in_array($value, $needDeliveries))
		{
			$arChange = array(
				"event" => "CHANGE_ORDER",
				"orderID" => $orderID
			);
			
			self::updateNoticeFileData($arChange);
		}
		
		return true;
	}
	
	// получение свойств, изменение которых приводит к выставлению флага изменения заказа
	
	/**
	 * при отмене заказа
	 *
	 * @param \Bitrix\Sale\Order $entity
	 *
	 * @return bool
	 */
	static public function OnSaleOrderCanceledHandler($entity)
	{
		$needDeliveries = self::getDeliveryIDs();
		$orderID = $entity->getField("ID");
		$changed = $entity->getFields()->getChangedValues();
		$deliveryID = $entity->getField("DELIVERY_ID");
		
		CIPOLYadostDriver::getOrderConfirm($orderID);
		if (
			$orderID &&
			in_array($deliveryID, $needDeliveries) &&
			$changed["CANCELED"] == "Y" &&
			!empty(CIPOLYadostDriver::$tmpOrderConfirm["savedParams"]["delivery_ID"])
		)
		{
			$arChange = array(
				"event" => "CANCEL_ORDER",
				"orderID" => $orderID
			);
			
			self::updateNoticeFileData($arChange);
		}
		
		return true;
	}
	
	// получение названий свойств в настройках модуля, по которым будет ставиться флаг оплаты
	
	/**
	 * @param \Bitrix\Sale\BasketBase $entity
	 * @param string                  $name
	 * @param string                  $value
	 * @param string                  $old_value
	 *
	 * @return bool
	 */
	static public function OnSaleBasketItemSetFieldHandler($entity, $name, $value, $old_value)
	{
		$needDeliveries = self::getDeliveryIDs();
		
		/** @var \Bitrix\Sale\Order $order */
		$order = $entity->getCollection()->getOrder();
		
		if (!is_null($order))
		{
			$deliveryID = $order->getField("DELIVERY_ID");
			$orderID = $order->getField("ID");
		}
		else
			return true;
		
		if ($orderID && $name == "QUANTITY" && in_array($deliveryID, $needDeliveries))
		{
			$arChange = array(
				"event" => "CHANGE_ORDER",
				"orderID" => $orderID
			);
			
			self::updateNoticeFileData($arChange);
		}
		
		return true;
	}
	
	// получение id профилей
	
	static public function getCheckedUpdatedOrderProps()
	{
		CIPOLYadostDriver::getModuleSetups();
		
		$needProps = self::getUpdatedEventProps();
		
		$arResult = array();
		foreach ($needProps as $prop)
			$arResult[$prop] = CIPOLYadostDriver::$options["ADDRESS"][$prop];
		
		return $arResult;
	}
	
	static public function getUpdatedEventProps()
	{
		$needProps = array(
			"address",
			"street",
			"house",
			"build"
		);
		
		CIPOLYadostDriver::getModuleSetups();
		foreach ($needProps as $key => $prop)
			if (empty(CIPOLYadostDriver::$options["ADDRESS"][$prop]))
				unset($needProps[$key]);
		
		return $needProps;
	}
	
	// соответсвие названий тарифов в апи и Битрикс

	static public function getDeliveryIDs()
	{
		if (self::$deliveryIDs == null)
		{
			if (self::isConverted())
			{
				$dTS = Bitrix\Sale\Delivery\Services\Table::getList(array(
					'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
					'filter' => array('CODE' => 'yadost:%')
				));
				
				self::$deliveryIDs = array();
				
				while ($dataShip = $dTS->Fetch())
					self::$deliveryIDs[$dataShip["CODE"]] = $dataShip['ID'];
			}
			else
			{
				self::$deliveryIDs = array(
					"yadost:pickup" => "yadost:pickup",
					"yadost:courier" => "yadost:courier",
					"yadost:post" => "yadost:post"
				);
			}
		}
		
		return self::$deliveryIDs;
	}
	
	public static function getDeliverySuggestions()
	{
		return array(
			"TODOOR" => "courier",
			"POST" => "post",
			"PICKUP" => "pickup"
		);
	}
	
	// удалЯем данные, что заказ был изменен

	public static function updateNoticeFileData($params)
	{
		if (empty($params["event"]) || empty($params["orderID"]))
			return false;
		
		self::getNoticeFileName();
		
		$files = array(
			"notice" => self::$noticeFileName,
			"change" => self::$changeFileName
		);
		
		foreach ($files as $file)
		{
			$arChange = array();
			if (file_exists($file))
				$arChange = unserialize(file_get_contents($file));
			
			$arChange[$params["event"]][$params["orderID"]] = $params["orderID"];
			
			file_put_contents($file, serialize($arChange));
		}
		
		return true;
	}
	
	static public function deleteOrderFromChange($params)
	{
		if (!CIPOLYadostHelper::isAdmin("R"))
			CIPOLYadostHelper::throwException("Access denied");
		
		self::getNoticeFileName();
		
		$needsOrderID = $params["ORDER_ID"];
		
		$file = self::$changeFileName;
		
		if (!file_exists($file))
			return true;
		
		$fileContent = unserialize(file_get_contents($file));
		foreach ($fileContent as $event => $arOrders)
			foreach ($arOrders as $key => $orderID)
				if ($orderID == $needsOrderID)
					unset($fileContent[$event][$key]);
		
		file_put_contents($file, serialize($fileContent));
		
		return true;
	}
	
	static public function isOrderChanged($needsOrderID)
	{
		self::getNoticeFileName();
		
		$file = self::$changeFileName;
		
		if (!file_exists($file))
			return false;
		
		$fileContent = unserialize(file_get_contents($file));
		
		foreach ($fileContent as $event => $arOrders)
			foreach ($arOrders as $key => $orderID)
				if ($orderID == $needsOrderID)
					return true;
		
		return false;
	}
	
	static public function checkLogParams($inputRequest)
	{
		if ($inputRequest["deliveryDebugToLog"] == "SWITCH_ON")
			$_SESSION["IPOLyadost_print_logfile"] = true;
		if ($inputRequest["deliveryDebugToLog"] == "SWITCH_OFF")
			unset($_SESSION["IPOLyadost_print_logfile"]);
		
		return;
	}
	
	static public function errorLog($val)
	{
		$fileName = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . CIPOLYadostDriver::$MODULE_ID . "/errLog.txt";
		
		self::checkLogParams($_REQUEST);
		
		if ($_SESSION["IPOLyadost_print_logfile"])
		{
			$file = fopen($fileName, "w");
			fwrite($file, "\n\n" . date("H:i:s d-m-Y") . "\n");
			fwrite($file, print_r($val, true));
			fclose($file);
		}
	}
	
	static public function OnModuleUpdateHandler($arModules)
	{
		$sendStat = false;
		
		if (is_array($arModules))
			if (in_array(CIPOLYadostDriver::$MODULE_ID, $arModules))
				$sendStat = true;
			elseif (CIPOLYadostDriver::$MODULE_ID == $arModules)
				$sendStat = true;
		
		if ($sendStat)
			CIPOLYadostDriver::sendStatistic(array("type" => "update"));
	}
	
	/**
	 * return chosen in options senderID
	 *
	 * @return int
	 */
	public static function getSenderID()
	{
		if (!is_null(self::$chosenSender))
			return self::$chosenSender;
		
		CIPOLYadostDriver::getRequestConfig();
		$chosenSender = CIPOLYadostDriver::$requestConfig["sender_id"][COption::GetOptionString(CIPOLYadostDriver::$MODULE_ID, 'defaultSender', '0')];
		
		self::$chosenSender = (int)$chosenSender;
		
		return self::$chosenSender;
	}
	
	/**
	 * get list of vat rates
	 *
	 * @return array|null
	 */
	public static function getVatRateList()
	{
		$chosenSender = self::getSenderID();
		
		if ($chosenSender)
		{
			$senderInfo = CIPOLYadostDriver::getSenderInfo($chosenSender);
			
			if ($senderInfo["clientInfo"]["status"] == "ok")
				return $senderInfo["clientInfo"]["data"]["vat_settings"];
		}
		
		return null;
	}
	
	/**
	 * retrun id of vat chosen in YD Account options
	 *
	 * @return int
	 */
	public static function getVatIDDefault()
	{
		$requisiteInfo = CIPOLYadostDriver::getRequisiteInfo();
		
		if ($requisiteInfo["requisiteInfo"]["status"] == "ok")
		{
			return (int)$requisiteInfo["requisiteInfo"]["data"]["tax_id"];
		}
		
		return 1;
	}
	
	/**
	 * return vatID by the percentage
	 *
	 * @param $vatValue
	 *
	 * @return int
	 */
	public static function getVatID($vatValue)
	{
		$vatPercent = (int)($vatValue * 100);
		
		//https://tech.yandex.ru/delivery/doc/dg/reference/create-order-docpage/
		// find orderitem_vat_value
		
		switch ($vatPercent)
		{
			case 0:
				return 6;
				break;
			
			case 10:
				return 2;
				break;
			
			case 18:
				return 1;
				break;
			
			default:
				return self::getVatIDDefault();
				break;
		}
	}
	
	static public function updateAddressProp($orderID, $propValue)
	{
		if (!CModule::includeModule("sale"))
			return false;
		
		if (CIPOLYadostHelper::controlProps())
		{
			$propCode = "ipol_yadost_PVZ_ADDRESS";
			
			$arOrder = CSaleOrder::getList(
				array(),
				array("ID" => $orderID)
			)->fetch();
			
			if (!empty($arOrder))
			{
				$op = CSaleOrderProps::GetList(
					array(),
					array(
						"PERSON_TYPE_ID" => $arOrder['PERSON_TYPE_ID'],
						"CODE" => $propCode)
				)->Fetch();
				
				if ($op)
				{
					$arFields = array(
						"ORDER_ID" => $orderID,
						"ORDER_PROPS_ID" => $op['ID'],
						"NAME" => GetMessage("IPOLyadost_prop_name_" . $propCode),
						"CODE" => $propCode,
						"VALUE" => preg_replace("/\"/", "", $propValue)
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
		
		return true;
	}
	
	static public function getDefaultCityFromModuleSale()
	{
		$saleLocation = COption::getOptionString('sale', 'location', false);
		
		if ($saleLocation)
			return $saleLocation;
		else
			return false;
	}
	
	static public function getSiteIds()
	{
		$rsSites = CSite::GetList($by = "sort", $order = "desc", Array());
		
		$siteIDs = array();
		$siteIDs[] = "";
		
		while ($arSite = $rsSites->Fetch()) {
		  $siteIDs[$arSite["SERVER_NAME"]] = $arSite["SERVER_NAME"];
		}
		
		return $siteIDs;
	}
	
	static public function selectSite()
	{
		$site = \Bitrix\Main\Config\Option::get(CIPOLYadostDriver::$MODULE_ID, "site_selection");
		
		return $site;
	}
}