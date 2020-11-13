<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

IncludeModuleLangFile(__FILE__);

class CDeliveryYandexDriver
{
	const CACHE_TIME = 86400;// сутки храним кеш
	private static $agentCall = false;
	
	/////////////////////////////////////////////////////////////////////////////
	// Агент обновления статусов
	/////////////////////////////////////////////////////////////////////////////
	static $MODULE_ID = 'yandex.delivery';
	static $tmpDeliveryStatus;// признак вызова из агента
	static $formData;
	
	// обновляет статусы заказов
	// $arStatus = array(bitrixOrderID => ydStatus);
	static $assessedCostPercent = null;
	
	// статусы завершенных заказов
	static $tmpOrder;
	
	// статусы заказов с ошибкой
	static $tmpOrderProps;
	
	// статусы заказов, которые нельзя редактировать
	static $tmpOrderConfirm;
	
	/////////////////////////////////////////////////////////////////////////////
	// Логика действий по ajax запросам модуля
	/////////////////////////////////////////////////////////////////////////////
	
	// отправка заявки с заказом
	/*
	$params = array(
			ORDER_ID
			perform_actions = "confirm" подтвердить заказ, false - сделать черновик
			delivery_type = import - самопривоз заказов , withdraw - забор
			import_type = courier — пеший курьер; , car — автомобильный курьер.
		)
	*/
	static $tmpOrderBasket;
	static $tmpOrderDimension;
	static $tmpOrderID = null;
	static $zeroWeightGoods = array();
	
	// получение 
	static $zeroGabsGoods = array();
	
	// получение документов и ярлыков заказа
	static $totalWeightMoreDefault;
	
	// удаляем старые записи
	static $options;
	
	/////////////////////////////////////////////////////////////////////////////
	// Выполнение запросов
	/////////////////////////////////////////////////////////////////////////////
	
	// получение документов заказа
	static $requestSend = false;
	
	// получение ярлыков заказа
	static $requestConfig = false;
	
	/*
	указать надо какой-либо из ид
		$input = array(
			"bitrix_ID" - ид заказа в битрикс
			"delivery_ID" - ид заказа в яндекс
		)
	*/
	// получение статуса заказа
	static $debug = array();
	
	// вспомогательный, проверяет есть ли данные с формы и ставит их, если нет, берет из свойств заказа
	static $configFileName = false;
	
	// отправка заказа в черновики яндекс доставки
	
	static public function agentOrderStates()
	{
		$returnVal = 'CDeliveryYandexDriver::agentOrderStates();';
		
		if (!CModule::IncludeModule("sale"))
			return $returnVal;
		
		// статусы завершенных заказов
		$arEndStatus = self::getEndStatus();
		
		// забираем выгруженные заказы не в финальном статусе за последние 2 месяца
		$dbOrders = CDeliveryYandexSqlOrders::getList(array(
			"filter" => array(
				"!STATUS" => $arEndStatus,
				"!delivery_ID" => false
			)
		));
		
		// собираем заказы не в финальных статусах
		$deliveryIDs = array();// ид заказов в яндекс
		$arOrders = array();// ид заказов в админке
		$deliveryStatus = array();// статусы заказов в ЯД
		
		while ($arOrder = $dbOrders->Fetch())
		{
			$deliveryStatus[$arOrder["ORDER_ID"]] = $arOrder["STATUS"];
			$arOrders[$arOrder["ORDER_ID"]] = $arOrder["ORDER_ID"];
			$deliveryIDs[$arOrder["delivery_ID"]] = $arOrder["ORDER_ID"];
            $paramsOrder = json_decode($arOrder['PARAMS'], true);
            $arDeliveryType[$arOrder["ORDER_ID"]] = $paramsOrder['type'];
		}
		
		self::$tmpDeliveryStatus = $deliveryStatus;
		
		//== тут бы запросить оптом статусы из яндекс, но пока нельзя, будем по одному запрашивать
		$arStatus = array();
		$arTracks = array();// трек номера заказов
		self::$agentCall = true;
		foreach ($deliveryIDs as $deliveryID => $bitrixID)
		{
			$status = self::getOrderStatus(array("delivery_ID" => $deliveryID));
			
			if (is_array($status))// значит ошибка пришла, не надо запрашивать этот заказ из БД
				unset($arOrders[$bitrixID]);
			else
			{
				$arStatus[$bitrixID] = $status;
                if ($arDeliveryType[$bitrixID] == "POST") {
                    $trek_numpostruss = self::getOrderInfo($bitrixID);
                    $arTracks[$bitrixID] = $trek_numpostruss['OrderInfo']['data']['delivery_num'];
                } else {
                    $arTracks[$bitrixID] = $bitrixID . "-YD" . $deliveryID;
                }

			}
		}
		self::$agentCall = false;
		
		// непосредственное обновление статусов
		self::updateOrderStatus($arStatus);
		// непосредственное обновление трек номеров
		self::updateTrackNumbers($arTracks);
		
		return $returnVal;
	}
	
	// получение данных заказа, используется для отладки
	
	static public function updateOrderStatus($arStatus)
	{
		if (!CModule::IncludeModule("sale"))
			return false;
		
		// получаем статусы отобранных заказов в битрикс
		$arOrders = array_keys($arStatus);
		self::getModuleSetups();
		
		$dbOrders = CSaleOrder::GetList(
			array(),
			array("ID" => $arOrders),
			false,
			false,
			array("ID", "STATUS_ID")
		);
		
		$statusOrders = array();
		while ($arOrder = $dbOrders->Fetch())
			$statusOrders[$arOrder["ID"]] = $arOrder["STATUS_ID"];
		
		global $USER;
		$userCreated = false;
		if (!is_object($USER))
		{
			$userCreated = true;
			$USER = new CUser();
		}
		
		self::getModuleSetups();
		
		// обновляем статусы для полученых заказов
		foreach ($arStatus as $orderID => $ydStatus)
		{
			// обновляем статус заказа в таблице
			$updateSqlStatus = false;
			if (self::$tmpDeliveryStatus[$orderID])
			{
				if (self::$tmpDeliveryStatus[$orderID] != $ydStatus)
					$updateSqlStatus = true;
			}
			else
				$updateSqlStatus = true;
			
			if ($updateSqlStatus)
				CDeliveryYandexSqlOrders::updateCustom(
					array("ORDER_ID" => $orderID),
					array("STATUS" => $ydStatus)
				);
			
			// если пришел статус с ошибкой, то надо показать сообщение админу
			$errorStatus = self::getErrorStatus();
			
			if (in_array($ydStatus, $errorStatus))
			{
				$change = array(
					"event" => "ERROR_STATUS_ORDER",
					"orderID" => $orderID
				);
				
				CDeliveryYandexHelper::updateNoticeFileData($change);
			}
			
			
			if (!empty(self::$options["STATUS"]))
			{
				// если текущий статус отличается от соответсвия, то ставим его
				if ($ydStatus == "NEW" || self::$options["STATUS"][$ydStatus])
					if ($statusOrders[$orderID] != self::$options["STATUS"][$ydStatus])
						CSaleOrder::StatusOrder($orderID, self::$options["STATUS"][$ydStatus]);
			}
		}
		
		if ($userCreated)
			unset($USER);
		
		return true;
	}
	
	static public function updateTrackNumbers($arTracks)
	{
		if (!CModule::IncludeModule("sale"))
			return false;
		
		global $USER;
		$userCreated = false;
		if (!is_object($USER))
		{
			$userCreated = true;
			$USER = new CUser();
		}
		
		foreach ($arTracks as $orderID => $trackNumber)
		{
			$arOrder = CSaleOrder::getByID($orderID);
			
			if (empty($arOrder["TRACKING_NUMBER"]))
				CSaleOrder::update($orderID, array(
					"TRACKING_NUMBER" => $trackNumber
				));
		}
		
		if ($userCreated)
			unset($USER);
		
		return true;
	}
	
	// получение данных магазина, используется для отладки
	
	static public function getEndStatus()
	{
		return array(
			"CANCELED", // отменен
			"RETURN_RETURNED", // Возвращён в магазин
			"DELIVERY_DELIVERED", // Доставлен
			// "ERROR", // Ошибка
		);
	}
	
	// получение данных отправителя, используется для отладки
	
	static public function getErrorStatus()
	{
		return array(
			"ERROR", // Ошибка
		);
	}
	
	// подтверждение заказа и перевод его из черновика
	
	static public function getNotEditableStatus()
	{
		return array(
			"CREATED", "SENDER_SENT", "DELIVERY_LOADED", "FULFILMENT_LOADED"
		);
	}
	
	// создание отгрузки заказа в яндекс
	
	static public function saveFormData(&$params)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		$orderID = $params["ORDER_ID"];
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Order ID empty", $params);
		
		// сохраняем данные формы, виджета
		if (!empty($params["data"]["widgetDataJSON"]) && !empty($params["data"]["formDataJSON"]))
		{
			$dbRes = CDeliveryYandexSqlOrders::updateCustom(
				array(
					"ORDER_ID" => $orderID
				),
				array(
					"PARAMS" => CDeliveryYandexHelper::convertFromUTF($params["data"]["widgetDataJSON"]),
					"MESSAGE" => CDeliveryYandexHelper::convertFromUTF($params["data"]["formDataJSON"])
				)
			);
			
			if (!$dbRes)
				CDeliveryYandexHelper::throwException("Cant update DB", array(CDeliveryYandexSqlOrders::getErrorMessagesCustom(), $params));
		}
		
		// если отмечена опция обновления стоимости доставки в заказе
		if ("Y" == $params["data"]["formData"]["change_delivery_price"])
		{
			// пересчитываем стоимость
			self::getOrder($orderID);
			$shift = floatVal($params["data"]["formData"]["delivery_price"]) - floatVal(self::$tmpOrder["PRICE_DELIVERY"]);
			
			self::$tmpOrder["PRICE_DELIVERY"] = floatVal(self::$tmpOrder["PRICE_DELIVERY"]) + $shift;
			self::$tmpOrder["PRICE"] = floatVal(self::$tmpOrder["PRICE"]) + $shift;
			
			if (CModule::IncludeModule("sale"))
			{
				$tmpOrderData = self::$tmpOrder;
				$tmpOrderProps = self::$tmpOrderProps;
				
				if (CDeliveryYandexHelper::isConverted())
					$arFields = array(
						"PRICE_DELIVERY" => self::$tmpOrder["PRICE_DELIVERY"]
					);
				else
					$arFields = array(
						"PRICE_DELIVERY" => self::$tmpOrder["PRICE_DELIVERY"],
						"PRICE" => self::$tmpOrder["PRICE"]
					);
				
				CSaleOrder::Update($orderID, $arFields);
				
				self::$tmpOrder = $tmpOrderData;
				self::$tmpOrderProps = $tmpOrderProps;
			}
		}
		
		// если самовывоз, пересохраняем в свойство полный адрес доставки
		if ("PICKUP" == $params["data"]["formData"]["profile_name"])
		{
			CDeliveryYandexHelper::updateAddressProp($orderID, CDeliveryYandexHelper::convertFromUTF($params["data"]["formData"]["address"]));
		}
		
		return array("saveFormData" => true);
	}
	
	// выгрузка в СД
	
	static public function sendOrder($params)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		$orderID = $params["ORDER_ID"];
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Order ID empty", $params);
		
		$arDeliveryTypes = array("import", "withdraw");
		if (!in_array($params["data"]["formData"]["delivery_type"], $arDeliveryTypes))
			CDeliveryYandexHelper::throwException("Invalid delivery_type", $params);
		
		self::$formData = CDeliveryYandexHelper::convertFromUTF($params["data"]["formData"]);
		self::$tmpOrderConfirm = CDeliveryYandexHelper::convertFromUTF($params["data"]);
		
		self::saveFormData($params);
		
		unset($params["data"]["widgetDataJSON"]);
		unset($params["data"]["formDataJSON"]);
		
		$arResult = array();
		
		// отправляем черновик это делаем в любом случае, так как заказ мог быть изменен
		self::getModuleSetups();
		self::$options["to_yd_warehouse"] = $params["data"]["formData"]["to_yd_warehouse"];
		
		if (isset($params["data"]["formData"]["assessedCostPercent"]))
			self::$assessedCostPercent = FloatVal($params["data"]["formData"]["assessedCostPercent"]);
		if (self::$assessedCostPercent === null)
			self::$assessedCostPercent = FloatVal(COption::GetOptionString(CDeliveryYandexDriver::$MODULE_ID, 'assessedCostPercent', '100'));
		
		$arResult["sendDraft"] = self::sendOrderDraft($orderID);
		
		if ($arResult["sendDraft"]["status"] == "ok")
		{
			self::$tmpOrderConfirm["savedParams"]["delivery_ID"] = $arResult["sendDraft"]["data"]["order"]["id"];
			self::updateOrderStatus(array($orderID => "DRAFT"));
		}
		
		// если необходимо, то отправляем заказ в СД
		if ($params["perform_actions"] == "confirm")
		{
			if (empty(self::$tmpOrderConfirm["savedParams"]["parcel_ID"]))
			{
				// self::$tmpOrderConfirm = false;
				// преобразуем дату в нужный формате
				$shipmentDate = self::convertDataFromAdmin($params["data"]["formData"]["shipment_date"]);
				
				$arResult["confirmOrder"] = self::confirmOrder($orderID, $params["data"]["formData"]["delivery_type"], $shipmentDate);// подтверждаем заказ
			}
		}
		
		// обновляем статус заказа после всех действий
		$deliveryID = self::$tmpOrderConfirm["savedParams"]["delivery_ID"];
		if ($arResult["sendDraft"])
			$deliveryID = $arResult["sendDraft"]["data"]["order"]["id"];
		
		$arResult["STATUS"] = self::getOrderStatus(array("delivery_ID" => $deliveryID));
		
		self::updateOrderStatus(array($orderID => $arResult["STATUS"]));
		
		return $arResult;
	}
	
	// отдаем в аякс интервалы и склады
	
	static public function getOrderDocuments($params)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		self::delOrderDocuments(); //удаляем старые файлы
		
		$orderID = $params["ORDER_ID"];
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Order ID empty", $params);
		
		$filePath = $_SERVER['DOCUMENT_ROOT'] . "/upload/" . self::$MODULE_ID;
		
		$fileNames = array(
			"docs" => $filePath . "/" . self::getOrderDocsNumber("docs", $orderID) . ".pdf",
			"labels" => $filePath . "/" . self::getOrderDocsNumber("labels", $orderID) . ".pdf"
		);
		
		$getRequestDocs = false;
		foreach ($fileNames as $filename)
			if (!file_exists($filename))
				$getRequestDocs = true;
		
		if ($getRequestDocs)
		{
			$docsRes = self::getOrderDocs($orderID);
			$labelRes = self::getOrderLabels($orderID);
			
			$arDocs = array();
			$errors = array();
			if (!empty($docsRes["data"]["errors"]))
				$errors[] = $docsRes["data"]["errors"]["parcel_id"];
			else
				$arDocs["docs"] = base64_decode($docsRes["data"]);
			
			if (!empty($labelRes["data"]["errors"]))
				$errors[] = $labelRes["data"]["errors"]["order_id"];
			else
				$arDocs["labels"] = base64_decode($labelRes["data"]);
			
			if (empty($arDocs))
				CDeliveryYandexHelper::throwException("error", $errors);
			
			
			if (!file_exists($filePath))
				mkdir($filePath);
			
			$arReturn = array();
			foreach ($arDocs as $docType => $docVal)
				if (false === file_put_contents($fileNames[$docType], $arDocs[$docType]))
					CDeliveryYandexHelper::throwException("Can't write file", array("filePath" => $fileNames[$docType]));
				else
					$arReturn[$docType] = "/upload/" . self::$MODULE_ID . "/" . self::getOrderDocsNumber($docType, $orderID) . ".pdf";
			
			return $arReturn;
		}
		
		return array(
			"docs" => "/upload/" . self::$MODULE_ID . "/" . self::getOrderDocsNumber("docs", $orderID) . ".pdf",
			"labels" => "/upload/" . self::$MODULE_ID . "/" . self::getOrderDocsNumber("labels", $orderID) . ".pdf"
		);
	}
	
	// получение временных интервалов привоза посылок на склад
	
	static public function delOrderDocuments()
	{
		$dirPath = $_SERVER['DOCUMENT_ROOT'] . "/upload/" . self::$MODULE_ID . "/";
		$dirContain = scandir($dirPath);
		foreach ($dirContain as $contain)
		{
			if (strpos($contain, '.pdf') !== false && (time() - (int)filemtime($dirPath . $contain)) > 1300)
				unlink($dirPath . $contain);
		}
	}
	
	// получение доступных вариантов доставки и складов сортировки
	
	static public function getOrderDocs($orderID)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		self::getOrderConfirm($orderID);
		
		if (empty(self::$tmpOrderConfirm["savedParams"]["parcel_ID"]))
			CDeliveryYandexHelper::throwException("Order not confirm in yandex");
		
		$arSend = array(
			"parcel_id" => self::$tmpOrderConfirm["savedParams"]["parcel_ID"],
		);
		
		$method = "getSenderParcelDocs";
		$res = self::MakeRequest($method, $arSend);
		
		return $res;
	}
	
	// отмена заказа
	static public function getOrderLabels($orderID)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		self::getOrderConfirm($orderID);
		
		if (empty(self::$tmpOrderConfirm["savedParams"]["delivery_ID"]))
			CDeliveryYandexHelper::throwException("Order not found in yandex");
		
		$arSend = array(
			"order_id" => self::$tmpOrderConfirm["savedParams"]["delivery_ID"],
			"is_raw" => 0, //0 — ярлык в формате PDF; , 1 — ярлык в формате HTML
		);
		
		$method = "getSenderOrderLabel";
		$res = self::MakeRequest($method, $arSend);
		
		return $res;
	}
	
	// отправка статистики в Яндекс
	
	static public function getOrderStatus($input)
	{
		$error = array("error" => true);
		
		if (!self::$agentCall)
			if (!CDeliveryYandexHelper::isAdmin("R"))
			{
				$error["msg"] = "Access denied";
				
				return $error;
			}
		
		if (empty($input["bitrix_ID"]) && empty($input["delivery_ID"]))
		{
			$error["msg"] = "Empty order ID";
			
			return $error;
		}
		
		if ($input["bitrix_ID"])
		{
			self::getOrderConfirm($input["bitrix_ID"]);
			
			if (empty(self::$tmpOrderConfirm["savedParams"]["delivery_ID"]))
			{
				$error["msg"] = "Order not found in yandex";
				
				return $error;
			}
			
			$arSend = array(
				"order_id" => self::$tmpOrderConfirm["savedParams"]["delivery_ID"]
			);
		}
		else
			$arSend = array(
				"order_id" => $input["delivery_ID"]
			);
		
		$method = "getSenderOrderStatus";
		$res = self::MakeRequest($method, $arSend);
		
		return $res["data"];
	}
	/////////////////////////////////////////////////////////////////////////////
	// Вспомогательные методы для получения данных
	/////////////////////////////////////////////////////////////////////////////
	
	static public function sendOrderDraft($orderID)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		// получаем данные о заказе
		self::fillOrderData($orderID);
		
		// заполняем данные заказа
		$arSend["recipient_first_name"] = self::getRecipientField("fname");
		$arSend["recipient_middle_name"] = self::getRecipientField("mname");
		$arSend["recipient_last_name"] = self::getRecipientField("lname");
		
		$arSend["recipient_phone"] = self::getRecipientField("phone");
		$arSend["recipient_email"] = self::getRecipientField("email");
		$arSend["order_comment"] = self::$tmpOrder["USER_DESCRIPTION"];
		
		$arSend["order_num"] = self::getOrderNum($orderID);
		
		$arSend["order_requisite"] = self::$requestConfig["requisite_id"][0];
		// $arSend["order_warehouse"] = self::$requestConfig["warehouse_id"][0];
		$arSend["order_warehouse"] = self::$requestConfig["warehouse_id"][self::$tmpOrderConfirm["formData"]["warehouseConfigNum"]];
		
		
		// оценочная стоимость
		$orderPrice = floatVal(self::$tmpOrder["PRICE"]) - floatval(self::$tmpOrder["PRICE_DELIVERY"]);
		$assessedCost = $orderPrice * (self::$assessedCostPercent / 100);
		
		$arSend["order_assessed_value"] = $assessedCost;
		
		// величина предоплаты
		if ("Y" == self::$tmpOrder["PAYED"])
			$arSend["order_amount_prepaid"] = self::$tmpOrder["PRICE"];
		else
			$arSend["order_amount_prepaid"] = 0;
		
		// стоимость доставки
		$arSend["order_delivery_cost"] = self::$tmpOrder["PRICE_DELIVERY"];
		
		$arSend["is_manual_delivery_cost"] = 1;//== костыль, вырезать. Так потому, что на оф заказа стоимость считается виджетом, в ЛК стоимость может быть другой или менеджер ее поменял руками. Пока ставим, что выставили руками, чтобы стоимость доставки в Битрикс и ЛК яндекс не отличались.
		
		$arSend["deliverypoint_city"] = self::$tmpOrderConfirm["widgetData"]["yandexDeliveryCity"];
		
		// адрес доставки
		if (self::$tmpOrderConfirm["widgetData"]["type"] != "PICKUP")
		{
			$arSend["deliverypoint_street"] = self::getRecipientField("street");
			$arSend["deliverypoint_house"] = self::getRecipientField("house");
			$arSend["deliverypoint_housing"] = self::getRecipientField("housing");
			$arSend["deliverypoint_build"] = self::getRecipientField("build");
			$arSend["deliverypoint_flat"] = self::getRecipientField("flat");
			$arSend["deliverypoint_index"] = self::getRecipientField("index");
		}
		
		if (self::$tmpOrderConfirm["widgetData"]["type"] != "TODOOR")
			$arSend["delivery_pickuppoint"] = self::$tmpOrderConfirm["widgetData"]["pickuppointId"];
		
		if (!preg_match("/^[\d]{6,6}$/", $arSend["deliverypoint_index"]))//== костыль
			$arSend["deliverypoint_index"] = "";
		
		// данные по тарифу доставки
		$arSend["delivery_delivery"] = self::$tmpOrderConfirm["widgetData"]["delivery"]["id"];
		$arSend["delivery_direction"] = self::$tmpOrderConfirm["widgetData"]["direction"];
		$arSend["delivery_tariff"] = self::$tmpOrderConfirm["widgetData"]["tariffId"];
		
		// интервал доставки
		// $arSend["delivery_interval"] = self::$tmpOrderConfirm["widgetData"]["deliveryIntervals"][0]["id"];
		$arSend["delivery_interval"] = self::$tmpOrderConfirm["widgetData"]["deliveryIntervalId"];
		
		
		// использовать ли склад для забора товара
		$arSend["delivery_to_yd_warehouse"] = ("Y" == self::$options["to_yd_warehouse"]) ? 1 : 0;
		
		// корзина заказа
		$arSend["order_items"] = array();
		foreach (self::$tmpOrderBasket as $arBasket)
		{
			$arSend["order_items"][] = array(
				"orderitem_article" => $arBasket["artnumber"],
				"orderitem_name" => $arBasket["NAME"],
				"orderitem_quantity" => ceil($arBasket["QUANTITY"]),
				"orderitem_cost" => ceil($arBasket["PRICE"]),
				"orderitem_vat_value" => $arBasket["VAT_YD_ID"]
			);
		}
		
		// габариты заказа
		$arGabs = array(
			"weight",
			"length",
			"width",
			"height"
		);
		
		foreach ($arGabs as $gab)
		{
			$tmpGab = self::$tmpOrderDimension[CDeliveryYandexHelper::toUpper($gab)];
			
			if (self::$formData[CDeliveryYandexHelper::toUpper($gab)])
				$tmpGab = self::$formData[CDeliveryYandexHelper::toUpper($gab)];
			
			$arSend["order_" . $gab] = $tmpGab;
		}
		// $arSend["order_weight"] = self::$tmpOrderDimension["WEIGHT"];
		// $arSend["order_length"] = self::$tmpOrderDimension["LENGTH"];
		// $arSend["order_width"] = self::$tmpOrderDimension["WIDTH"];
		// $arSend["order_height"] = self::$tmpOrderDimension["HEIGHT"];
		
		
		// выполняем запрос
		if (self::$tmpOrderConfirm["savedParams"]["delivery_ID"])
		{
			$arSend["order_id"] = self::$tmpOrderConfirm["savedParams"]["delivery_ID"];
			$method = "updateOrder";
		}
		else
			$method = "createOrder";
		
		$arSend["order_shipment_date"] = self::convertDataFromAdmin(self::$formData["shipment_date"]);
		
		//		die(print_r(array($arSend, self::$tmpOrderConfirm), true));
		
		$res = self::MakeRequest($method, $arSend);
		
		// надо запомнить номер заказа в яндекс
		if ($res["status"] == "ok" && $res["data"]["order"]["id"])
		{
			$dbRes = CDeliveryYandexSqlOrders::updateCustom(
				array("ORDER_ID" => $orderID),
				array("delivery_ID" => $res["data"]["order"]["id"])
			);
			if (!$dbRes)
				CDeliveryYandexHelper::throwException(CDeliveryYandexSqlOrders::getErrorMessagesCustom(), array("method" => $method, "request" => $arSend, "result" => $res));
		}
		else
			CDeliveryYandexHelper::throwException("Draft order error", array("method" => $method, "request" => $arSend, "result" => $res));
		
		return $res;
	}
	
	static public function getOrderInfo($orderID)
	{
		//		if (!CDeliveryYandexHelper::isAdmin())
		//			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		self::getOrderConfirm($orderID);
		
		if (empty(self::$tmpOrderConfirm["savedParams"]["delivery_ID"]))
			CDeliveryYandexHelper::throwException("Order not found in yandex");
		
		$arSend = array(
			"order_id" => self::$tmpOrderConfirm["savedParams"]["delivery_ID"]
		);
		
		$method = "getOrderInfo";
		$res = self::MakeRequest($method, $arSend);
		
		return array("OrderInfo" => $res, "saveParams" => self::$tmpOrderConfirm);
	}
	
	static public function getWarehouseInfo($warehouseID)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($warehouseID))
			CDeliveryYandexHelper::throwException("Empty warehouseID");
		
		$arSend = array(
			"warehouse_id" => $warehouseID
		);
		
		$method = "getWarehouseInfo";
		$res = self::MakeRequest($method, $arSend);
		
		return array("warehouseInfo" => $res);
	}// данные виджета со страницы оф заказа
	
	static public function getSenderInfo($senderID)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($senderID))
			CDeliveryYandexHelper::throwException("Empty senderID");
		
		$arSend = array();
		
		// подменяем сендера, чтобы получить его данные
		self::getRequestConfig();
		self::$requestConfig["sender_id"][COption::GetOptionString(self::$MODULE_ID, 'defaultSender', '0')] = $senderID;
		
		$method = "getSenderInfo";
		$res = self::MakeRequest($method, $arSend);
		
		return array("clientInfo" => $res);
	}
	
	public static function getRequisiteInfo()
	{
		//		if (!CDeliveryYandexHelper::isAdmin("R"))
		//			CDeliveryYandexHelper::throwException("Access denied");
		
		self::getRequestConfig();
		$requisiteID = self::$requestConfig["requisite_id"][0];
		
		if ($requisiteID)
		{
			$arSend = array(
				"requisite_id" => $requisiteID
			);
			
			$method = "getRequisiteInfo";
			$res = self::MakeRequest($method, $arSend);
		}
		else
			$res = null;
		
		
		return array("requisiteInfo" => $res);
	}
	
	static public function confirmOrder($orderID, $deliveryType, $shipmentDate = false)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		if (empty($deliveryType))
			CDeliveryYandexHelper::throwException("Empty deliveryType");
		
		self::getOrderConfirm($orderID);
		
		if (!$shipmentDate)
			$shipmentDate = self::getShipmentDate();//== завтра\
		
		if (empty(self::$tmpOrderConfirm["savedParams"]["delivery_ID"]))
			CDeliveryYandexHelper::throwException("Order not found in yandex", array("data" => self::$tmpOrderConfirm));
		
		$arSend = array(
			"order_ids" => self::$tmpOrderConfirm["savedParams"]["delivery_ID"],
			"shipment_date" => $shipmentDate,
			"type" => $deliveryType // import - самопривоз заказов , withdraw - забор
		);
		
		$method = "confirmSenderOrders";
		$res = self::MakeRequest($method, $arSend);
		
		// надо запомнить номер отгрузки
		if ($res["status"] == "ok" && empty($res["data"]["result"]["error"]))
		{
			foreach ($res["data"]["result"]["success"] as $parcel)
				if (!empty($parcel["parcel_id"]) && !empty($parcel["orders"]))
				{
					$dbRes = CDeliveryYandexSqlOrders::updateCustom(
						array(
							"ORDER_ID" => $orderID
						),
						array(
							"parcel_ID" => $parcel["parcel_id"]
						)
					);
					
					if (!$dbRes)
						CDeliveryYandexHelper::throwException(CDeliveryYandexSqlOrders::getErrorMessagesCustom(), array("method" => $method, "request" => $arSend, "result" => $res));
				}
		}
		else
			CDeliveryYandexHelper::throwException("Confirm order error", array("method" => $method, "request" => $arSend, "result" => $res));
		
		return $res;
	}
	
	// заполняет все переменные класса с данными заказа
	
	static public function createDeliveryOrder($orderID, $deliveryType, $importType)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($orderID))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		if (empty($deliveryType))
			CDeliveryYandexHelper::throwException("Empty deliveryType");
		
		if (empty($importType))
			CDeliveryYandexHelper::throwException("Empty importType");
		
		self::getOrderConfirm($orderID);
		self::getModuleSetups();
		self::getRequestConfig();
		self::getOrderBasket(array("ORDER_ID" => $orderID));
		
		if (empty(self::$tmpOrderConfirm["savedParams"]["delivery_ID"]))
			CDeliveryYandexHelper::throwException("Order not found in yandex");
		
		// объем посылки, габариты в сантиметрах(уже переведены), объем в куб.м
		$volume = self::$tmpOrderDimension["LENGTH"] * self::$tmpOrderDimension["LENGTH"] * self::$tmpOrderDimension["LENGTH"] / 1000000;
		
		$method = false;// метод, которым отправляется заказ
		
		// $intervals = self::getInterval(self::$tmpOrderConfirm["widgetData"]["delivery"]["unique_name"], $deliveryType);
		
		$arSend = array(
			"shipment_date" => self::getShipmentDate(),
			"interval" => self::$tmpOrderConfirm["formData"]["interval"],
			"delivery_name" => self::$tmpOrderConfirm["widgetData"]["delivery"]["unique_name"],
			"warehouse_from_id" => self::$requestConfig["warehouse_id"][self::$tmpOrderConfirm["formData"]["warehouseConfigNum"]],
			"warehouse_to_id" => self::$tmpOrderConfirm["formData"]["deliveries"],///////== !!!!!!!!!!!!
			// "warehouse_to_id" => 1081,///////== !!!!!!!!!!!!
			"requisite_id" => self::$requestConfig["requisite_id"][0],
			"weight" => self::$tmpOrderDimension["WEIGHT"],
			"volume" => $volume,
			"type" => $deliveryType,
			"sort" => 0 // использовать сортировку на едином складе
		);
		
		// if (self::$options["to_yd_warehouse"])
		// $arSend["delivery_name"] = "fulfillment_".$arSend["delivery_name"];
		// else
		// $arSend["delivery_name"] = "delivery_".$arSend["delivery_name"];
		
		if ($deliveryType == "withdraw")// забор курьером
			$method = "createWithdraw";
		elseif ($deliveryType == "import")// самопривоз
		{
			$method = "createImport";
			
			$arSend["name"] = array(self::$options["COURIER"]["courier_name"]);
			$arSend["import_type"] = $importType;//courier — пеший курьер; , car — автомобильный курьер.
			
			if ($importType == "car")
			{
				$arSend["car_number"] = self::$options["COURIER"]["car_number"];
				$arSend["car_model"] = self::$options["COURIER"]["car_model"];
			}
		}
		
		if (!$method)
			CDeliveryYandexHelper::throwException("Cant't detect delivery method createWithdraw or createImport");
		
		// $arSend["order_ids"] = array(self::$tmpOrderConfirm["savedParams"]["delivery_ID"]);
		$arSend["order_ids"] = self::$tmpOrderConfirm["savedParams"]["delivery_ID"];
		
		$res = self::MakeRequest($method, $arSend);
		
		if ($res["status"] == "ok")
			return $res;
		else
			CDeliveryYandexHelper::throwException("createDeliveryOrder error", array("method" => $method, "request" => $arSend, "result" => $res));
		
		return false;
	}
	
	// очищает все временные переменные класса с данными заказа
	
	static public function confirmParcel($parcel_id)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($parcel_id))
			CDeliveryYandexHelper::throwException("confirmParcel error parcel_id empty");
		
		$arSend = array(
			"parcel_ids" => $parcel_id
		);
		
		$method = "confirmSenderParcels";
		$res = self::MakeRequest($method, $arSend);
		
		if ($res["status"] == "ok" && empty($res["data"]["result"]["error"]))
			return $res["data"]["result"];
		else
			CDeliveryYandexHelper::throwException("confirmSenderParcels error", array("method" => $method, "request" => $arSend, "result" => $res));
		
		return false;
	}
	
	// данные заказа
	
	static public function getFormIntervalWarehouse($params)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($params))
			CDeliveryYandexHelper::throwException("Empty params");
		
		$arResult = array();
		
		$arResult["intervals"] = self::getInterval($params["deliveryName"], $params["deliveryType"]);
		$arResult["deliveries"] = self::getDeliveries();
		
		return $arResult;
	}
	
	// свойства заказа
	
	static public function getInterval($deliveryName, $deliveryType)
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($deliveryName))
			CDeliveryYandexHelper::throwException("Empty deliveryName");
		
		if (empty($deliveryType))
			CDeliveryYandexHelper::throwException("Empty deliveryType");
		
		$obCache = new CPHPCache();
		
		$cachename = "trade_yandex_deliveryIntervals|" . $deliveryName . "|" . $deliveryType;
		
		if ($obCache->InitCache(self::CACHE_TIME, $cachename, "/trade_yandex_delivery/"))
			return $obCache->GetVars();
		else
		{
			$arSend = array(
				"shipment_date" => self::getShipmentDate(), //== завтра\
				"delivery_name" => $deliveryName,
				"shipment_type" => $deliveryType// import - самопривоз заказов , withdraw - забор
			);
			
			$method = "getIntervals";
			$res = self::MakeRequest($method, $arSend);
			
			// надо запомнить номер отгрузки
			if ($res["status"] == "ok" && !empty($res["data"]["schedules"][0]))
			{
				$arReturn = CDeliveryYandexHelper::convertFromUTF($res["data"]["schedules"]);
				$obCache->StartDataCache();
				$obCache->EndDataCache($arReturn);
				
				return $arReturn;
			}
			else
				CDeliveryYandexHelper::throwException("getInterval error", array("method" => $method, "request" => $arSend, "result" => $res));
		}
		
		return false;
	}
	
	// данные виджета со страницы оформления заказа
	
	static public function getDeliveries()
	{
		if (!CDeliveryYandexHelper::isAdmin("R"))
			CDeliveryYandexHelper::throwException("Access denied");
		
		$obCache = new CPHPCache();
		$cachename = "trade_yandex_delivery";
		
		if ($obCache->InitCache(self::CACHE_TIME, $cachename, "/trade_yandex_delivery/"))
			return $obCache->GetVars();
		else
		{
			$arSend = array();
			
			$method = "getDeliveries";
			$res = self::MakeRequest($method, $arSend);
			
			// надо запомнить номер отгрузки
			if ($res["status"] == "ok" && !empty($res["data"]["deliveries"]))
			{
				$arReturn = CDeliveryYandexHelper::convertFromUTF($res["data"]["deliveries"]);
				
				$arReturn["selectedDeliveries"] = COption::GetOptionString(self::$MODULE_ID, "deliveries", "");
				
				$obCache->StartDataCache();
				$obCache->EndDataCache($arReturn);
				
				return $arReturn;
			}
			else
				CDeliveryYandexHelper::throwException("getDeliveries error", array("method" => $method, "request" => $arSend, "result" => $res));
		}
		
		return false;
	}
	
	// получаем номер заказа в Битрикс, он может не совпадать с ID
	
	static public function cancelOrder($params)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		if (empty($params["ORDER_ID"]))
			CDeliveryYandexHelper::throwException("Empty order ID");
		
		$orderID = $params["ORDER_ID"];
		
		$orderInfo = CDeliveryYandexSqlOrders::getList(array(
			"filter" => array("ORDER_ID" => $orderID)
		))->Fetch();
		
		if ($orderInfo["delivery_ID"])
		{
			$method = "deleteOrder";
			$arSend = array(
				"order_id" => $orderInfo["delivery_ID"]
			);
			
			$res = self::MakeRequest($method, $arSend);
			
			$status = self::getOrderStatus(array("delivery_ID" => $orderInfo["delivery_ID"]));
			
			if ($status == "CANCELED")
			{
				$dbRes = CDeliveryYandexSqlOrders::updateCustom(
					array(
						"ORDER_ID" => $orderID
					),
					array(
						"delivery_ID" => null,
						"parcel_ID" => null
					)
				);
				
				if (!$dbRes)
					CDeliveryYandexHelper::throwException(CDeliveryYandexSqlOrders::getErrorMessagesCustom(), array("method" => $method, "request" => $arSend, "result" => $res));
				
				self::updateOrderStatus(array($orderID => "NEW"));
			}
			
			return array("result" => $res["data"], "STATUS" => "NEW");
		}
		
		self::updateOrderStatus(array($orderID => "NEW"));
		
		return array("STATUS" => "NEW");
	}
	
	// получение корзины заказа с габаритами
	/*
	$params = array(
		"ORDER_ID" => 1 - ид заказа
		"PRODUCT_ID" => 1 - ид товара
		пустой массив - берется текущая корзина юзера
		"PRODUCT_QUANTITY" => 1 количество товара
	);
	*/
	
	static public function sendStatistic($params)
	{
		return false;
		
		// допустимые действия
		$allowTypes = array(
			"install",// - Установка модуля в CMS
			"activate",// - Активация службы доставки Яндекс.Доставка
			"deactivate",// - Деактивация службы доставки Яндекс.Доставка
			"update",// - Обновление модуля
			"settings",// Изменение настроек модуля пользователем
			"remove",// Удаление модуля
		);
		
		self::getModuleSetups();
		
		$type = $params["type"];
		if (!in_array($type, $allowTypes))
			return false;
		
		$info = CModule::CreateModuleObject('main');
		$mainVersion = $info->MODULE_VERSION;
		
		$info = CModule::CreateModuleObject('yandex.delivery');
		$moduleVersion = $info->MODULE_VERSION;
		
		$arSend = array(
			"type" => $type,
			"cms" => array(
				"name" => "Bitrix",
				"version" => $mainVersion,
				"module_version" => $moduleVersion
			),
			// "time" => date(DateTime::ISO8601),
			"time" => date("Y-m-d\TH:i:sP"),
			"domain" => $_SERVER["HTTP_HOST"],
			"settings" => array_merge(self::$options, array("DELIVERY_ACTIVE" => CDeliveryYandexHelper::isActive())),
			"unique_key" => COption::GetOptionString(self::$MODULE_ID, "unique_num")
		);
		
		$method = "sendModuleEvent";
		try
		{
			self::MakeRequest($method, $arSend);
			
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	static public function fillOrderData($orderID)
	{
		self::getOrder($orderID);
		self::getOrderProps($orderID);
		self::getOrderConfirm($orderID);
		self::getRequestConfig();
		self::getOrderBasket(array("ORDER_ID" => $orderID));
	}
	
	// убираем родителей комплектов из корзины, они - фиктивные товары
	
	static public function clearOrderData()
	{
		self::$tmpOrder = false;
		self::$tmpOrderProps = false;
		self::$tmpOrderConfirm = false;
		self::$tmpOrderBasket = false;
		self::$tmpOrderDimension = false;
	}
	
	// получение габаритов заказа и веса
	
	static public function getOrder($orderID)
	{
		self::getModuleSetups();
		if (empty(self::$tmpOrder))
		{
			if (!CModule::IncludeModule("sale"))
				CDeliveryYandexHelper::throwException("Module sale not found");
			
			$arOrder = CSaleOrder::GetList(
				array(),
				array("ID" => $orderID)
			)->Fetch();
			
			if (empty($arOrder))
				CDeliveryYandexHelper::throwException("Order not found", array("ORDER_ID" => $orderID));
			
			self::$tmpOrder = $arOrder;
			
			foreach (GetModuleEvents(self::$MODULE_ID, "onGetOrderData", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array(self::$tmpOrder));
		}
		
		return self::$tmpOrder;
	}
	
	static public function getOrderProps($orderID)
	{
		self::getModuleSetups();
		if (empty(self::$tmpOrderProps))
		{
			if (!CModule::IncludeModule("sale"))
				CDeliveryYandexHelper::throwException("Module sale not found");
			
			$dbOrderProps = CSaleOrderPropsValue::GetList(
				array(),
				array("ORDER_ID" => $orderID)
			);
			
			$arNeedUserPropsCode = self::$options["ADDRESS"];
			
			
			$userProps = array();
			while ($arProps = $dbOrderProps->Fetch())
			{
				$allProps[] = $arProps;
				foreach ($arNeedUserPropsCode as $key => $code)
					if ($arProps["CODE"] == $code)
						$userProps[$key] = $arProps["VALUE"];
			}
			
			if ($userProps["fname"] == $userProps["mname"] && $userProps["fname"] == $userProps["lname"])
			{
				$arName = explode(" ", $userProps["fname"]);

                AddMessage2Log($arName);

                $userProps["lname"] = $arName[0];
				
				if (!empty($arName[1]))
					$userProps["fname"] = $arName[1];
				else {
                    $userProps["fname"] = $arName[0];
                    //$userProps["lname"] = " ";
                }

				if (!empty($arName[2]))
					$userProps["mname"] = $arName[2];
				else
					$userProps["mname"] = " ";
			}
			
			if (empty($userProps["mname"]))
				$userProps["mname"] = "";
			if (empty($userProps["lname"]))
				$userProps["lname"] = "";
			
			if (!preg_match("/^[\d]{6,6}$/", $userProps["index"]))
				$userProps["index"] = "";
			
			self::$tmpOrderProps = $userProps;
			
			foreach (GetModuleEvents(self::$MODULE_ID, "onGetOrderProps", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array(self::$tmpOrderProps));
		}
		
		return self::$tmpOrderProps;
	}
	
	static public function getOrderConfirm($orderID)
	{
		self::getModuleSetups();
		if (empty(self::$tmpOrderConfirm))
		{
			$sqlOrder = CDeliveryYandexSqlOrders::getList(array(
				"filter" => array("ORDER_ID" => $orderID)
			))->Fetch();
			
			if (!$sqlOrder)
				return false;
			
			self::$tmpOrderConfirm["widgetData"] = CDeliveryYandexHelper::convertFromUTF(json_decode(CDeliveryYandexHelper::convertToUTF($sqlOrder["PARAMS"]), true));
			
			self::$tmpOrderConfirm["formData"] = CDeliveryYandexHelper::convertFromUTF(json_decode(CDeliveryYandexHelper::convertToUTF($sqlOrder["MESSAGE"]), true));
			
			unset($sqlOrder["PARAMS"]);
			unset($sqlOrder["MESSAGE"]);
			
			// заполняем остальные сохраненные параметры
			foreach ($sqlOrder as $key => $param)
				self::$tmpOrderConfirm["savedParams"][$key] = $param;
			
			foreach (GetModuleEvents(self::$MODULE_ID, "onGetWidgetData", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array(self::$tmpOrderConfirm));
		}
		
		return self::$tmpOrderConfirm;
	}
	
	static public function getOrderNum($orderID)
	{
		return $orderID;
		
		//		self::getModuleSetups();
		//		self::getOrder($orderID);
		//
		//		if (self::$tmpOrder["ACCOUNT_NUMBER"])
		//			$accountNumber = self::$tmpOrder["ACCOUNT_NUMBER"];
		//		else
		//			$accountNumber = self::$tmpOrder["ID"];
		//
		//		return $accountNumber;
	}
	
	// суммируем размеры одного товара
	
	static public function getOrderBasket($params, $arOrderItems = null)
	{
		self::getModuleSetups();
		if (empty(self::$tmpOrderBasket))
		{
			self::$tmpOrderID = null;
			
			if (!CModule::IncludeModule("sale"))
				CDeliveryYandexHelper::throwException("Module sale not found");
			
			if ($params["PRODUCT_ID"])
			{
				if (!CModule::IncludeModule("catalog"))
					CDeliveryYandexHelper::throwException("Module catalog not found");
				
				$arProduct = CCatalogProduct::GetList(
					array(),
					array("ID" => $params["PRODUCT_ID"]),
					false,
					array("nTopCount" => 1)
				)->Fetch();
				
				$orderBasket = array(
					$params["PRODUCT_ID"] => array(
						"PRODUCT_ID" => $arProduct["ID"],
						"NAME" => $arProduct["ELEMENT_NAME"],
						"VAT_INCLUDED" => $arProduct["VAT_INCLUDED"],
						"WEIGHT" => $arProduct["WEIGHT"],
						"QUANTITY" => ($params["PRODUCT_QUANTITY"]) ? $params["PRODUCT_QUANTITY"] : 1,
						"DIMENSIONS" => Array
						(
							"WIDTH" => $arProduct["WIDTH"],
							"HEIGHT" => $arProduct["HEIGHT"],
							"LENGTH" => $arProduct["LENGTH"]
						),
						"SET_PARENT_ID" => $arProduct["SET_PARENT_ID"]
					)
				);
				
				$dbPrice = CPrice::GetList(
					array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC", "SORT" => "ASC"),
					array("PRODUCT_ID" => $params["PRODUCT_ID"]),
					false,
					false,
					array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO")
				);
				
				while ($arPrice = $dbPrice->Fetch())
				{
					$orderBasket[$params["PRODUCT_ID"]]["BASE_PRICE"] = $arPrice["PRICE"];
					$orderBasket[$params["PRODUCT_ID"]]["CURRENCY"] = $arPrice["CURRENCY"];
					
					$arDiscounts = CCatalogDiscount::GetDiscountByPrice(
						$arPrice["ID"]
					);
					
					$orderBasket[$params["PRODUCT_ID"]]["PRICE"] = CCatalogProduct::CountPriceWithDiscount(
						$arPrice["PRICE"],
						$arPrice["CURRENCY"],
						$arDiscounts
					);
				}
				
				$vatID = CDeliveryYandexHelper::getVatIDDefault();
				if ($arProduct["VAT_ID"])
				{
					$arVat = CCatalogVat::getListEx(
						array($by => "ID"),
						array(
							"ID" => $arProduct["VAT_ID"]
						),
						false,
						false,
						array()
					)->Fetch();
					
					if ($arVat)
					{
						$vatID = CDeliveryYandexHelper::getVatID((int) $arVat["RATE"]);
					}
				}
				
				$orderBasket["VAT_YD_ID"] = $vatID;
			}
			else
			{
				$arFilter = array("ORDER_ID" => "0");
				
				// формируем фильтр от параметров
				if ($params["ORDER_ID"])
				{
					self::$tmpOrderID = $params["ORDER_ID"];
					
					$arFilter = array(
						"ORDER_ID" => $params["ORDER_ID"]
					);
				}
				elseif (empty($params))
					$arFilter = array(
						"FUSER_ID" => CSaleBasket::GetBasketUserID(),
						"LID" => SITE_ID,
						"ORDER_ID" => "NULL",
						"CAN_BUY" => "Y",
						"DELAY" => "N"
					);
				
				
				$dbBasket = CSaleBasket::GetList(
					array(),
					$arFilter
				);
				
				$orderBasket = array();
				while ($arBasket = $dbBasket->Fetch())
				{
					$arBasket["DIMENSIONS"] = unserialize($arBasket["DIMENSIONS"]);
					$orderBasket[$arBasket["PRODUCT_ID"]] = $arBasket;
					
					$orderBasket[$arBasket["PRODUCT_ID"]]["VAT_YD_ID"] = CDeliveryYandexHelper::getVatID($arBasket["VAT_RATE"]);
				}
				
				if (!is_null($arOrderItems))
					foreach ($orderBasket as $productID => $arBasket)
					{
						foreach ($arOrderItems as $item)
							if ($item["PRODUCT_ID"] == $productID)
								$orderBasket[$productID]["PRICE"] = $item["PRICE"];
					}
			}
			
			$handleDimensions = true;
			// если корзина получилась пустой, то берем ее как один товар с дефолтными габаритами
			if (empty($orderBasket))
			{
				if (!is_null($arOrderItems))
				{
					foreach ($arOrderItems as $item)
						$orderBasket[$item["PRODUCT_ID"]] = array(
							"ID" => 0,
							"WEIGHT" => $item["WEIGHT"],
							"PRODUCT_ID" => $item["PRODUCT_ID"],
							"DIMENSIONS" => array(
								"WIDTH" => $item["WIDTH"],
								"HEIGHT" => $item["HEIGHT"],
								"LENGTH" => $item["LENGTH"]
							),
							"QUANTITY" => $item["QUANTITY"],
							"PRICE" => $item["PRICE"],
							"VAT_YD_ID" => CDeliveryYandexHelper::getVatIDDefault()
						);
				}
				else
				{
					$handleDimensions = false;
					
					$orderBasket = array(
						array(
							"ID" => 0,
							"WEIGHT" => self::$options["weightD"],
							"DIMENSIONS" => array(
								"WIDTH" => self::$options["widthD"],
								"HEIGHT" => self::$options["heightD"],
								"LENGTH" => self::$options["lengthD"]
							),
							"QUANTITY" => 1,
							"PRICE" => 1000,
							"VAT_YD_ID" => CDeliveryYandexHelper::getVatIDDefault()
						)
					);
				}
			}
			
			if ($handleDimensions)
			{
				// обрабатываем комплекты
				$orderBasket = self::handleBitrixComplects($orderBasket);
				
				// если указано свойство артикул, то вытаскиваем его
				$artnumberCode = self::$options["artnumber"];
				
				// если габариты в свойстве, то вытаскиваем их
				$sideMode = self::$options["sideMode"];
				
				// если габариты в свойстве, то вытаскиваем их
				$weightMode = self::$options["weightPr"];
				
				if ($artnumberCode || $sideMode != "def" || $weightMode != "CATALOG_WEIGHT")
				{
					if (!CModule::IncludeModule("iblock"))
						CDeliveryYandexHelper::throwException("Module iblock not found");
					
					$productIDs = array();
					// собираем id товаров в корзине
					foreach ($orderBasket as $arBasket)
						$productIDs[] = $arBasket["PRODUCT_ID"];
					
					// формируем, какие свойства вытащить из запроса
					$arSelect = array("ID", "IBLOCK_ID");
					
					// артикул
					if ($artnumberCode)
						if ($artnumberCode != "ID")
							$arSelect[] = "PROPERTY_" . $artnumberCode;
					
					// габариты
					if ($sideMode == "unit")
						$arSelect[] = "PROPERTY_" . self::$options["sidesUnit"];
					elseif ($sideMode == "sep")
					{
						$arSelect[] = "PROPERTY_" . self::$options["sidesSep"]["L"];
						$arSelect[] = "PROPERTY_" . self::$options["sidesSep"]["W"];
						$arSelect[] = "PROPERTY_" . self::$options["sidesSep"]["H"];
					}
					
					// вес
					if ($weightMode != "CATALOG_WEIGHT")
						$arSelect[] = "PROPERTY_" . self::$options["weightPr"];
					
					// делаем запрос в БД
					if (!empty($productIDs))
					{
						$dbElements = CIBlockElement::GetList(
							array(),
							array("ID" => $productIDs),
							false,
							false,
							$arSelect
						);
						
						// добавляем в товары данные из свойств
						while ($arElem = $dbElements->Fetch())
						{
							// артикул
							if ($artnumberCode)
								if ($artnumberCode == "ID")
									$orderBasket[$arElem["ID"]]["artnumber"] = $arElem["ID"];
								else
									$orderBasket[$arElem["ID"]]["artnumber"] = $arElem["PROPERTY_" . CDeliveryYandexHelper::toUpper($artnumberCode) . "_VALUE"];
							
							// габариты
							if ($sideMode == "unit")
							{
								$arDims = explode(self::$options["sidesUnitSprtr"], $arElem['PROPERTY_' . CDeliveryYandexHelper::toUpper(self::$options["sidesUnit"]) . '_VALUE']);
								$orderBasket[$arElem["ID"]]["DIMENSIONS"] = array(
									"WIDTH" => $arDims[0],
									"HEIGHT" => $arDims[1],
									"LENGTH" => $arDims[2]
								);
							}
							elseif ($sideMode == "sep")
								$orderBasket[$arElem["ID"]]["DIMENSIONS"] = array(
									"WIDTH" => $arElem['PROPERTY_' . CDeliveryYandexHelper::toUpper(self::$options["sidesSep"]['W']) . '_VALUE'],
									"HEIGHT" => $arElem['PROPERTY_' . CDeliveryYandexHelper::toUpper(self::$options["sidesSep"]['H']) . '_VALUE'],
									"LENGTH" => $arElem['PROPERTY_' . CDeliveryYandexHelper::toUpper(self::$options["sidesSep"]['L']) . '_VALUE']
								);
							
							// вес
							if ($weightMode != "CATALOG_WEIGHT")
								$orderBasket[$arElem["ID"]]["WEIGHT"] = $arElem["PROPERTY_" . CDeliveryYandexHelper::toUpper(self::$options["weightPr"]) . "_VALUE"];
						}
					}
				}
			}
			
			self::$tmpOrderBasket = $orderBasket;
			
			// получаем суммарные габариты заказа, вес товаров могут тут измениться
			self::getOrderDimension();
			
			foreach (GetModuleEvents(self::$MODULE_ID, "onGetBasketData", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array(self::$tmpOrderBasket));
		}
		
		return self::$tmpOrderBasket;
	}
	
	// Суммируем размеры грузов
	
	static public function handleBitrixComplects($goods)
	{
		$arComplects = array();
		
		foreach ($goods as $good)
			if (
				array_key_exists('SET_PARENT_ID', $good) &&
				$good['SET_PARENT_ID'] &&
				$good['SET_PARENT_ID'] != $good['ID']
			)
				$arComplects[$good['SET_PARENT_ID']] = true;
		
		foreach ($goods as $key => $good)
			if (array_key_exists($good['ID'], $arComplects))
				unset($goods[$key]);
		
		return $goods;
	}
	
	// приводит стороны к сантиметрам
	
	static public function sumSizeOneGoods($xi, $yi, $zi, $qty)
	{
		// отсортировать грузы по возрастанию
		$ar = array($xi, $yi, $zi);
		sort($ar);
		if ($qty <= 1)
			return (array('X' => $ar[0], 'Y' => $ar[1], 'Z' => $ar[2]));
		
		$x1 = 0;
		$y1 = 0;
		$z1 = 0;
		$l = 0;
		
		$max1 = floor(Sqrt($qty));
		for ($y = 1; $y <= $max1; $y++)
		{
			$i = ceil($qty / $y);
			$max2 = floor(Sqrt($i));
			for ($z = 1; $z <= $max2; $z++)
			{
				$x = ceil($i / $z);
				$l2 = $x * $ar[0] + $y * $ar[1] + $z * $ar[2];
				if (($l == 0) || ($l2 < $l))
				{
					$l = $l2;
					$x1 = $x;
					$y1 = $y;
					$z1 = $z;
				}
			}
		}
		
		return (array('X' => $x1 * $ar[0], 'Y' => $y1 * $ar[1], 'Z' => $z1 * $ar[2]));
	}
	
	// приводит вес к килограммам
	
	static public function sumSize($a)
	{
		$n = count($a);
		if (!($n > 0))
			return (array('length' => '0', 'width' => '0', 'height' => '0'));
		for ($i3 = 1; $i3 < $n; $i3++)
		{
			// отсортировать размеры по убыванию
			for ($i2 = $i3 - 1; $i2 < $n; $i2++)
			{
				for ($i = 0; $i <= 1; $i++)
				{
					if ($a[$i2]['X'] < $a[$i2]['Y'])
					{
						$a1 = $a[$i2]['X'];
						$a[$i2]['X'] = $a[$i2]['Y'];
						$a[$i2]['Y'] = $a1;
					};
					if (($i == 0) && ($a[$i2]['Y'] < $a[$i2]['Z']))
					{
						$a1 = $a[$i2]['Y'];
						$a[$i2]['Y'] = $a[$i2]['Z'];
						$a[$i2]['Z'] = $a1;
					}
				}
				$a[$i2]['Sum'] = $a[$i2]['X'] + $a[$i2]['Y'] + $a[$i2]['Z']; // сумма сторон
			}
			// отсортировать грузы по возрастанию
			for ($i2 = $i3; $i2 < $n; $i2++)
				for ($i = $i3; $i < $n; $i++)
					if ($a[$i - 1]['Sum'] > $a[$i]['Sum'])
					{
						$a2 = $a[$i];
						$a[$i] = $a[$i - 1];
						$a[$i - 1] = $a2;
					}
			// расчитать сумму габаритов двух самых маленьких грузов
			if ($a[$i3 - 1]['X'] > $a[$i3]['X'])
				$a[$i3]['X'] = $a[$i3 - 1]['X'];
			if ($a[$i3 - 1]['Y'] > $a[$i3]['Y'])
				$a[$i3]['Y'] = $a[$i3 - 1]['Y'];
			$a[$i3]['Z'] = $a[$i3]['Z'] + $a[$i3 - 1]['Z'];
			$a[$i3]['Sum'] = $a[$i3]['X'] + $a[$i3]['Y'] + $a[$i3]['Z']; // сумма сторон
		}
		
		return (array(
			'L' => Round($a[$n - 1]['X'], 2),
			'W' => Round($a[$n - 1]['Y'], 2),
			'H' => Round($a[$n - 1]['Z'], 2))
		);
	}
	
	// определение даты доставки на склад
	
	static public function standartSides($side)
	{
		self::getModuleSetups();
		$side = floatVal($side);
		
		switch (self::$options["sidesMeas"])
		{
			case 'mm':
				return $side * 0.1;
			case 'dm':
				return $side * 10;
			case 'm':
				return $side * 100;
			default:
				return $side;
		}
	}
	
	static public function standartWeight($weight)
	{
		self::getModuleSetups();
		$weight = floatVal($weight);
		
		switch (self::$options["weightMeas"])
		{
			case 'g':
				$res = $weight * 0.001;
				break;
			case 't':
				$res = $weight * 1000;
				break;
			default:
				$res = $weight;
		}
		
		return $res;
	}
	
	static public function getShipmentDate($template = false)
	{
		if ($template)
			return date($template, time()/* + 24*60*60*/);
		
		return date("Y-m-d", time()/* + 24*60*60*/);
	}
	
	/////////////////////////////////////////////////////////////////////////////
	// Запросы, настройки
	/////////////////////////////////////////////////////////////////////////////
	
	static public function convertDataFromAdmin($str)
	{
		$arTime = explode(".", $str);
		
		return date("Y-m-d", mktime(0, 0, 0, $arTime[1], $arTime[0], $arTime[2]));
	}
	
	static public function convertDataToAdmin($str)
	{
		$arTime = explode("-", $str);
		
		return date("d.m.Y", mktime(0, 0, 0, $arTime[1], $arTime[2], $arTime[0]));
	}
	
	static public function getModuleSetups()
	{
		if (empty(self::$options))
		{
			self::$options = array(
				// "assessedCost" => COption::GetOptionString(self::$MODULE_ID, "assessedCost", 0),
				"assessedCostPercent" => FloatVal(COption::GetOptionString(CDeliveryYandexDriver::$MODULE_ID, 'assessedCostPercent', '100')),
				"artnumber" => COption::GetOptionString(self::$MODULE_ID, "artnumber", ""),
				"cityFrom" => COption::GetOptionString(self::$MODULE_ID, "cityFrom", "MOSCOW"),
				"to_yd_warehouse" => COption::GetOptionString(self::$MODULE_ID, "to_yd_warehouse", ""),
				"defaultWarehouse" => COption::GetOptionString(CDeliveryYandexDriver::$MODULE_ID, 'defaultWarehouse', '0'),
				
				"ADDRESS" => array(
					"fname" => COption::GetOptionString(self::$MODULE_ID, "fname", "FIO"),
					"lname" => COption::GetOptionString(self::$MODULE_ID, "lname", "FIO"),
					"mname" => COption::GetOptionString(self::$MODULE_ID, "mname", "FIO"),
					"email" => COption::GetOptionString(self::$MODULE_ID, "email", "EMAIL"),
					"phone" => COption::GetOptionString(self::$MODULE_ID, "phone", "PHONE"),
					
					"index" => COption::GetOptionString(self::$MODULE_ID, "index", "ZIP"),
					"address" => COption::GetOptionString(self::$MODULE_ID, "address", "ADDRESS"),
					"street" => COption::GetOptionString(self::$MODULE_ID, "street", "STREET"),
					"house" => COption::GetOptionString(self::$MODULE_ID, "house", "HOUSE"),
					"build" => COption::GetOptionString(self::$MODULE_ID, "build", "BUILD"),
					"flat" => COption::GetOptionString(self::$MODULE_ID, "flat", "FLAT"),
				),
				
				"sidesMeas" => COption::GetOptionString(self::$MODULE_ID, "sidesMeas", "mm"),
				"sideMode" => COption::GetOptionString(self::$MODULE_ID, "sideMode", "def"),
				
				"sidesSep" => unserialize(COption::GetOptionString(self::$MODULE_ID, "sidesSep", 'a:3:{s:1:"L";s:6:"LENGTH";s:1:"W";s:5:"WIDTH";s:1:"H";s:6:"HEIGHT";}')),
				"sidesUnit" => COption::GetOptionString(self::$MODULE_ID, "sidesUnit", "DIMESIONS"),
				"sidesUnitSprtr" => COption::GetOptionString(self::$MODULE_ID, "sidesUnitSprtr", "x"),
				"weightPr" => COption::GetOptionString(self::$MODULE_ID, "weightPr", "CATALOG_WEIGHT"),
				"weightMeas" => COption::GetOptionString(self::$MODULE_ID, "weightMeas", "kg"),
				
				"weightD" => COption::GetOptionString(self::$MODULE_ID, "weightD", "1"),
				"heightD" => COption::GetOptionString(self::$MODULE_ID, "heightD", "20"),
				"widthD" => COption::GetOptionString(self::$MODULE_ID, "widthD", "30"),
				"lengthD" => COption::GetOptionString(self::$MODULE_ID, "lengthD", "40"),
				
				"COURIER" => array(
					// "import_type" => COption::GetOptionString(self::$MODULE_ID, "import_type", "courier"),
					"courier_name" => COption::GetOptionString(self::$MODULE_ID, "courier_name", ""),
					"car_number" => COption::GetOptionString(self::$MODULE_ID, "car_number", "XX100X199"),
					"car_model" => COption::GetOptionString(self::$MODULE_ID, "car_model", "Ford"),
				)
			);
			
			$arStatuses = CDeliveryYandexHelper::getDeliveryStatuses();
			
			foreach ($arStatuses as $status => $descr)
			{
				$option = COption::GetOptionString(self::$MODULE_ID, $status, "");
				if ($option)
					self::$options["STATUS"][$status] = $option;
			}
		}
		
		return true;
	}
	
	static public function sign($method)
	{
		self::getRequestConfig();
		
		$hash = '';
		
		// Добавляем к запросу внутренные параматры для правильного формирования подписи
		self::$requestSend['client_id'] = self::$requestConfig["client_id"];
		self::$requestSend['sender_id'] = IntVal(self::$requestConfig["sender_id"][COption::GetOptionString(self::$MODULE_ID, 'defaultSender', '0')]);
		
		// Сортируем  параметры запроса по ключам в алфавитном порядке для правильного формирования подписи
		$keys = array_keys(self::$requestSend);
		sort($keys);
		
		
		// Собираем все параметры запроса до 3 уровня вложенности и генерируем подпись
		foreach ($keys as $key)
		{
			if (!is_array(self::$requestSend[$key]))
				$hash .= self::$requestSend[$key];
			else
			{
				$subKeys = array_keys(self::$requestSend[$key]);
				sort($subKeys);
				foreach ($subKeys as $subKey)
				{
					
					if (!is_array(self::$requestSend[$key][$subKey]))
						$hash .= self::$requestSend[$key][$subKey];
					else
					{
						$subSubKeys = array_keys(self::$requestSend[$key][$subKey]);
						sort($subSubKeys);
						foreach ($subSubKeys as $subSubKey)
						{
							if (!is_array(self::$requestSend[$key][$subKey][$subSubKey]))
							{
								$hash .= self::$requestSend[$key][$subKey][$subSubKey];
							}
						}
					}
				}
			}
		}
		
		$hash .= self::$requestConfig["keys"][$method];
		$hash = md5($hash);
		
		// Подписываем запрос
		self::$requestSend['secret_key'] = $hash;
	}
	
	static public function getConfigFileName()
	{
		if (!self::$configFileName)
		{
			$configFilePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/js/" . self::$MODULE_ID . "/private/";
			
			// время последнего изменения файла
			$lastConfigFileTime = COption::GetOptionString(CDeliveryYandexDriver::$MODULE_ID, "lastConfigFileTime", 0);
			$fileName = md5(CMain::GetServerUniqID() . $lastConfigFileTime);
			if (!file_exists($configFilePath . $fileName . ".conf"))
				$fileName = md5($lastConfigFileTime);
			
			global $USER;
			if (!is_object($USER))
				$USER = new CUser();
			
			// если это хит админа, переназначаем имя файла после суток
			if ($GLOBALS["USER"]->isAdmin() && file_exists($configFilePath . $fileName . ".conf"))
			{
				$curTime = time();
				// если файлу более суток, генерим новое имя файла
				if ($curTime - $lastConfigFileTime > 86400)
				{
					// берем старый конфиг и перезаливаем в файл с новым именем
					$fileContent = file_get_contents($configFilePath . $fileName . ".conf");
					
					$newFileName = md5(CMain::GetServerUniqID() . $curTime);
					
					if (file_put_contents($configFilePath . $newFileName . ".conf", $fileContent))
						if (COption::SetOptionString(CDeliveryYandexDriver::$MODULE_ID, "lastConfigFileTime", $curTime))
						{
							unlink($configFilePath . $fileName . ".conf");
							$fileName = $newFileName;
						}
				}
			}
			
			// пытаемся найти конфиг файл в папке
			if (!file_exists($configFilePath . $fileName . ".conf"))
			{
				$arFiles = glob($configFilePath . "*.conf");
				
				$filesCount = count($arFiles);
				if ($filesCount >= 2)
					return false;
				elseif ($filesCount == 0)
					self::$configFileName = $configFilePath . $fileName . ".conf";
				else
					self::$configFileName = $arFiles[0];
			}
			else
				self::$configFileName = $configFilePath . $fileName . ".conf";
			
			self::checkOldConfig();
		}
		
		return true;
	}
	
	static public function getRequestConfig()
	{
		if (empty(self::$requestConfig))
		{
			$configFound = false;
			$iterator = 0;
			
			// пробуем прочитать конфиг файл, он может перезаписываться в данный момент и еще не быть доступным
			while ($iterator < 10 && !$configFound)
			{
				self::getConfigFileName();
				
				$arConfig = array();
				if (file_exists(self::$configFileName))
					$arConfig = file_get_contents(self::$configFileName);
				
				$arConfig = json_decode($arConfig, true);
				
				$iterator++;
				
				if (empty($arConfig))
				{
					self::$configFileName = false;
					usleep(300000);// выполняем слудющую итерацию через 0,3 cекунды, когда конфиг запишется
				}
				else
					$configFound = true;
			}
			
			if (!$configFound)
				return false;
			
			// добавляем ключ для отправки статистики
			if (isset($arConfig["keys"]["getDeliveries"]))
				$arConfig["keys"]["sendModuleEvent"] = $arConfig["keys"]["getDeliveries"];
			
			self::$requestConfig = $arConfig;
		}
		
		return true;
	}
	
	// подпись запроса
	
	public static function MakeRequest($method, $arSend)
	{
		if (!function_exists('curl_init'))
			CDeliveryYandexHelper::throwException("curl not found");
		
		self::$requestSend = $arSend;
		
		// Подписываем запрос
		self::$requestSend = CDeliveryYandexHelper::convertToUTF(self::$requestSend);
		self::sign($method);
		
		$request = http_build_query(self::$requestSend);
		
		$curl_url = 'https://delivery.yandex.ru/api/last/';
		// $curl_url = 'https://delivery.yandex.ru/api/1.0/';
		
		// Отправляем запрос на обработку в delivery API и сохраняем ответ
		$curl_handle = curl_init();
		
		$headers = array("Content-Type: application/x-www-form-urlencoded", "Accept: application/json");
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
		
		curl_setopt($curl_handle, CURLOPT_URL, $curl_url . $method);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $request);
		
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
		
		curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, 1);
		
		$curl_answer = curl_exec($curl_handle);
		$code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		
		curl_close($curl_handle);
		
		$arResult = json_decode($curl_answer, true);
		
		$debugInfo = array(
			"code" => $code,
			"method" => $method,
			"arSend" => self::$requestSend,
			"res" => $arResult,
			"req_url" => $curl_url . $method . "?" . $request,
			"json_request" => json_encode(self::$requestSend),
			"json_return" => $curl_answer
		);
		
		self::$debug[] = $debugInfo;
		
//		$logRes = array();
//		foreach($arResult["data"] as $tariff)
//			$logRes[] = array(
//				"tariffName" => $tariff["tariffName"],
//				"tariffId" => $tariff["tariffId"],
//				"type" => $tariff["type"],
//				"cost" => $tariff["cost"],
//				"costWithRules" => $tariff["costWithRules"],
//				"is_pickup_point" => $tariff["is_pickup_point"],
//				"is_post" => $tariff["is_post"],
//				"minDays" => $tariff["minDays"],
//				"maxDays" => $tariff["maxDays"],
//				"days" => $tariff["days"],
//			);
//
//		prefile(array(
//			"code" => $code,
//			"method" => $method,
//			"arSend" => self::$requestSend,
//			"res" => $logRes
//		), "calculate");
		
		CDeliveryYandexHelper::errorLog(self::$debug);
		
		if ($code != 200)
		{
			return "request error";
		}
		//			CDeliveryYandexHelper::throwException("request error");
		
		return $arResult;
	}
	
	// получение пути конфиг файла
	
	public static function setConfig($params)
	{
		if (!CDeliveryYandexHelper::isAdmin())
			CDeliveryYandexHelper::throwException("Access denied");
		
		$oldFormat = false;
		
		$clientParams = $params["config2"];
		$clientParams = json_decode($clientParams, true);
		if (empty($clientParams))
		{
			$oldFormat = true;
			$clientParams = $params["config2"];
			$clientParams = preg_replace("/^( )?\[/", "{", $clientParams);
			$clientParams = preg_replace("/\]( )?$/", "}", $clientParams);
			$clientParams = preg_replace("/\/\*.*?\*\//", "", $clientParams);
			$clientParams = preg_replace("/ /", "", $clientParams);
			
			$clientParams = json_decode($clientParams, true);
		}
		
		if (empty($clientParams))
			CDeliveryYandexHelper::throwException("Client params error", array("decode_error" => self::json_last_error_msg(), "config2" => $params["config2"]));
		
		$keys = $params["config1"];
		$keys = json_decode($keys, true);
		if (empty($keys))
		{
			$keys = $params["config1"];
			$keys = preg_replace("/\[\n/", "", $keys);
			$keys = preg_replace("/\n\]/", "", $keys);
			$keys = preg_replace("/: /", "\": \"", $keys);
			$keys = preg_replace("/\n/", "\",\n\"", $keys);
			$keys = preg_replace("/ /", "", $keys);
			$keys = preg_replace("/\n/", "", $keys);
			$keys = "{\"" . $keys . "\"}";
			
			$keys = json_decode($keys, true);
		}
		
		if (empty($keys))
			CDeliveryYandexHelper::throwException("API-keys error", array("decode_error" => self::json_last_error_msg(), "config1" => $params["config1"]));
		
		$arReplacer = array(
			"sender_ids" => "sender_id",
			"warehouse_ids" => "warehouse_id",
			"requisite_ids" => "requisite_id",
			
			//новый конфиг
			"client" => "client_id",
			"senders" => "sender_id",
			"warehouses" => "warehouse_id",
			"requisites" => "requisite_id",
		);
		
		foreach ($arReplacer as $key => $val)
		{
			if ($clientParams[$key])
			{
				$clientParams[$val] = $clientParams[$key];
				unset($clientParams[$key]);
			}
		}
		
		if (!$oldFormat)
			foreach ($clientParams as $key => $val)
			{
				$tmpArr = array();
				
				if (is_array($val))
					if ($val["id"])
						$clientParams[$key] = $val["id"];
					else
						foreach ($val as $value)
							if ($value["id"])
								$tmpArr[] = $value["id"];
				
				if (!empty($tmpArr))
					$clientParams[$key] = $tmpArr;
			}
		
		$arConfig = array_merge($clientParams, array("keys" => $keys));
		
		self::getConfigFileName();
		
		$configFileExist = false;
		if (file_exists(self::$configFileName))
			$configFileExist = true;
		
		file_put_contents(self::$configFileName, json_encode($arConfig, true));
		
		// если конфига не было, значит это установка модуля, отправляем статистику
		if (!$configFileExist)
			self::sendStatistic(array("type" => "install"));
		
		return true;
	}
	
	// получение конфига Яндекс для выполнения запросов к апи
	
	static private function getOrderDocsNumber($type, $orderID)
	{
		return md5($type . $orderID);
	}
	
	// выполнение запроса
	
	static private function getRecipientField($code)
	{
		if (!empty(self::$formData[$code]))
			return self::$formData[$code];
		
		if (!empty(self::$tmpOrderConfirm["formData"][$code]))
			return self::$tmpOrderConfirm["formData"][$code];
		else
			return self::$tmpOrderProps[$code];
	}
	
	// установка конфиг файла из ЛК яндекс
	
	static private function getOrderDimension()
	{
		self::getModuleSetups();
		
		if (empty(self::$tmpOrderDimension))
		{
			// если есть номер заказа, надо проверить не меняли ли на форме габариты и вернуть их
			$returnFormData = false;
			$arSavedFormParams = array();
			
			if (self::$tmpOrderID)
			{
				$dbOrders = CDeliveryYandexSqlOrders::getList(array(
					"filter" => array("ORDER_ID" => self::$tmpOrderID)
				))->Fetch();
				
				if ($dbOrders)
				{
					$arDimsCheck = array(
						"WEIGHT",
						"WIDTH",
						"HEIGHT",
						"LENGTH"
					);
					
					$formData = CDeliveryYandexHelper::convertFromUTF(json_decode(CDeliveryYandexHelper::convertToUTF($dbOrders["MESSAGE"]), true));
					
					// проверяем наличие сохраненных габаритов
					$returnFormData = true;
					foreach ($arDimsCheck as $oneDim)
						if (!isset($formData[$oneDim]))
							$returnFormData = false;
					
					if ($returnFormData)
						foreach ($arDimsCheck as $oneDim)
							$arSavedFormParams[$oneDim] = $formData[$oneDim];
				}
			}
			
			self::$zeroWeightGoods = array();// id товаров с 0 весом
			self::$zeroGabsGoods = array();// id товаров с 0 габаритами
			$totalWeight = 0;
			$oneGoodDims = array();
			$noWeightCount = 0; // количество товаров с 0 весом
			$arDefSetups = array(
				"WEIGHT" => self::standartWeight(self::$options["weightD"]),
				"LENGTH" => self::standartSides(self::$options["lengthD"]),
				"WIDTH" => self::standartSides(self::$options["widthD"]),
				"HEIGHT" => self::standartSides(self::$options["heightD"])
			);// габариты заказа по умолчанию
			$totalPrice = 0;
			$totalQuantity = 0;
			
			foreach (self::$tmpOrderBasket as $prodID => $arItem)
			{
				$isZeroGab = false;
				foreach (self::$tmpOrderBasket[$prodID]["DIMENSIONS"] as $val)
					if (empty($val))
						$isZeroGab = true;
				
				if ($isZeroGab)
					self::$zeroGabsGoods[$prodID] = $prodID;
				
				// приводим размерности
				self::$tmpOrderBasket[$prodID]["DIMENSIONS"] = array(
					"WIDTH" => self::standartSides(self::$tmpOrderBasket[$prodID]["DIMENSIONS"]["WIDTH"]),
					"HEIGHT" => self::standartSides(self::$tmpOrderBasket[$prodID]["DIMENSIONS"]["HEIGHT"]),
					"LENGTH" => self::standartSides(self::$tmpOrderBasket[$prodID]["DIMENSIONS"]["LENGTH"]),
				);
				self::$tmpOrderBasket[$prodID]["WEIGHT"] = self::standartWeight(self::$tmpOrderBasket[$prodID]["WEIGHT"]);
				
				// анализируем вес
				if (floatval(self::$tmpOrderBasket[$prodID]["WEIGHT"]) == 0)
				{
					self::$zeroWeightGoods[$prodID] = $prodID;
					$noWeightCount += (int)self::$tmpOrderBasket[$prodID]["QUANTITY"];
				}
				
				$totalWeight += self::$tmpOrderBasket[$prodID]["WEIGHT"] * self::$tmpOrderBasket[$prodID]["QUANTITY"];
				
				// собираем массив габаритов, количества для расчета габаритов посылки
				$oneGoodDims[] = self::sumSizeOneGoods(
					self::$tmpOrderBasket[$prodID]["DIMENSIONS"]["WIDTH"],
					self::$tmpOrderBasket[$prodID]["DIMENSIONS"]["HEIGHT"],
					self::$tmpOrderBasket[$prodID]["DIMENSIONS"]["LENGTH"],
					self::$tmpOrderBasket[$prodID]["QUANTITY"]
				);
				
				$totalQuantity += floatVal(self::$tmpOrderBasket[$prodID]["QUANTITY"]);
				$totalPrice += floatVal(self::$tmpOrderBasket[$prodID]["QUANTITY"]) * floatVal(self::$tmpOrderBasket[$prodID]["PRICE"]);
			}
			
			// считаем габариты посылки
			$resultDims = self::sumSize($oneGoodDims);
			
			// определяемся с товарами с 0 весом
			if ($noWeightCount > 0)
			{
				// сколько ставить веса товарам с 0 весом
				if ($totalWeight >= $arDefSetups['WEIGHT'])
				{
					self::$totalWeightMoreDefault = true;
					$setZeroWeight = 10 * 0.001;// считаем вес товаров без веса как 10грамм
				}
				else
				{
					self::$totalWeightMoreDefault = false;
					$setZeroWeight = ceil(1000 * ($arDefSetups['WEIGHT'] - $totalWeight) / $noWeightCount) * 0.001;
				}
				
				// выставляем вес и считаем общий заново
				$totalWeight = 0;
				foreach (self::$tmpOrderBasket as $prodID => $arItem)
				{
					if ($setZeroWeight && floatval(self::$tmpOrderBasket[$prodID]["WEIGHT"]) == 0)
						self::$tmpOrderBasket[$prodID]["WEIGHT"] = $setZeroWeight;
					
					$totalWeight += self::$tmpOrderBasket[$prodID]["WEIGHT"] * self::$tmpOrderBasket[$prodID]["QUANTITY"];
				}
			}
			
			// отдаем габариты
			self::$tmpOrderDimension = array(
				"WEIGHT" => $totalWeight,
				"LENGTH" => (floatVal($resultDims["L"]) != 0) ? $resultDims["L"] : $arDefSetups["LENGTH"],
				"WIDTH" => (floatVal($resultDims["W"]) != 0) ? $resultDims["W"] : $arDefSetups["WIDTH"],
				"HEIGHT" => (floatVal($resultDims["H"]) != 0) ? $resultDims["H"] : $arDefSetups["HEIGHT"],
				"PRICE" => $totalPrice,
				"QUANTITY" => $totalQuantity
			);
			
			// заменяем габариты на сохраненные в форме
			if ($returnFormData && is_set($arDimsCheck) && is_array($arDimsCheck))
				foreach ($arDimsCheck as $oneDim)
					self::$tmpOrderDimension[$oneDim] = $arSavedFormParams[$oneDim];
		}
		
		return self::$tmpOrderDimension;
	}
	
	// проверяем наличие конфига, соданного старым способом, переносим в новый файл конфига
	
	private static function checkOldConfig()
	{
		$arOldConfigFiles = array(
			$_SERVER['DOCUMENT_ROOT'] . "/bitrix/js/" . self::$MODULE_ID . "/config.php",
			$_SERVER['DOCUMENT_ROOT'] . "/bitrix/js/" . self::$MODULE_ID . "/private/config.txt"
		);
		
		foreach ($arOldConfigFiles as $oldConfigFileName)
			if (file_exists($oldConfigFileName))
			{
				$arConfig = array();
				
				if (preg_match("/\.php$/", $oldConfigFileName))
					require_once($oldConfigFileName);
				else
					$arConfig = file_get_contents($oldConfigFileName);
				
				$arConfig = json_decode($arConfig, true);
				
				// переносим старый конфиг в новое расположение
				if (!empty($arConfig))
				{
					if (empty(self::$requestConfig))
						self::getConfigFileName();
					
					file_put_contents(self::$configFileName, json_encode($arConfig, true));
				}
				
				unlink($oldConfigFileName);
			}
	}
	
	// получение текста ошибки json_decode, json_encode
	private static function json_last_error_msg()
	{
		static $ERRORS = array(
			JSON_ERROR_NONE => 'No error',
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
			JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		);
		
		$error = json_last_error();
		
		return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
	}
}