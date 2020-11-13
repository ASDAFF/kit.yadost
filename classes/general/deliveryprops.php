<?php
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

IncludeModuleLangFile(__FILE__);
class deliveryPropsFixer
{
	static $oldTemplate = true;
	// запускает процесс правки свойств
	public static function Start($data)
	{
		if (!CDeliveryYaHelper::isAdmin())
			self::throwException("Access denied");
		
		if (!CModule::IncludeModule("sale"))
			CDeliveryYaHelper::throwException("Module sale not found");
		
		if (!isset($data['personeTypeId'])) {
			$result = array_values(deliveryPropsFixer::getPersonTypes());
		} else {
			if (!isset($data['step'])) {
				CDeliveryYaHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_stepNotFound"));
			}
			
			self::$oldTemplate = COption::GetOptionString(CDeliveryYaDriver::$MODULE_ID, "oldTemplate", "Y")=="Y"?true:false;
			$personeTypeId = $data['personeTypeId'];
			$method = 'fixProp'. ucfirst($data['step']);

			if (!method_exists('deliveryPropsFixer', $method)) {
				CDeliveryYaHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_stepNotFound"));
			}

			$result = call_user_func(array('deliveryPropsFixer', $method), $personeTypeId);
		}

		// die(print_r(array($_GET, $method, $result), true));
		return $result;
	}
	
	public static $PROP_NAME_PREFIX = '';

	/**
	 * Возвращает ID службы доставки МКурьер
	 */
	public static function DELIVERY_COURIER_ID()
	{
		$arDelivery = CDeliveryYaHelper::getDeliveryIDs();
		return $arDelivery["yandexDelivery:courier"];
	}

	/**
	 * Возвращает ID службы доставки МПочта
	 */
	public static function DELIVERY_POST_ID()
	{
		$arDelivery = CDeliveryYaHelper::getDeliveryIDs();
		return $arDelivery["yandexDelivery:post"];
	}

	/**
	 * Возвращает ID службы доставки МСамовывоз
	 */
	public static function DELIVERY_PICKUP_ID()
	{
		$arDelivery = CDeliveryYaHelper::getDeliveryIDs();
		return $arDelivery["yandexDelivery:pickup"];
	}

	/**
	 * Получает список типов платильщиков
	 */
	public static function getPersonTypes()
	{
		static $arResult = false;

		if ($arResult === false) {
			$rsItems = CSalePersonType::GetList(
				$arOrder = array('SORT' => 'ASC'),
				$arFilter = array('ACTIVE' => 'Y'),
				$arGroupBy = false,
				$arNavParams = array(),
				$arSelect = array('ID', 'NAME', 'LID')
			);

			$arResult = array();
			while($arItem = $rsItems->GetNext()) {
				$arResult[$arItem['ID']] = $arItem;
			}
		}

		return $arResult;
	}

	/**
	 * Возвращает список свойст платильщика
	 * 
	 * @param  [type] $personeTypeId id типа платильщика
	 */
	public static function getOrderProps($personeTypeId)
	{
		static $arResult = false;

		if ($arResult !== false) {
			return $arResult;
		}

		if (!array_key_exists($personeTypeId, self::getPersonTypes())) {
			CDeliveryYaHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_persTypeNotFound"));
		}

		$rsItems = CSaleOrderProps::GetList(
			$arOrder = array('SORT' => 'ASC'),
			$arFilter = array('PERSON_TYPE_ID' => $personeTypeId),
			$arGroupBy = false,
			$arNavStartParams = false,
			$arSelect = array()
		);

		$arResult = array();
		while($arItem = $rsItems->Fetch()) {
			$key = $arItem['CODE'] ? $arItem['CODE'] : $arItem['ID'];
			$arResult[$key] = $arItem;
		}

		return $arResult;
	}

	/**
	 * Возвращает код св-ва с префиксом
	 * 
	 * @param  string $propName
	 * @return string
	 */
	public static function getPropName($propName)
	{
		return self::$PROP_NAME_PREFIX . $propName;
	}

	/**
	 * Исправляет поле "CONTACT_PERSON" или "FIO"
	 * 
	 * @param  int $personeTypeId id типа платильщика
	 * @return  bool|array Результат работы, если метод добавили/изменил поля возвращается массив с кодами этих полей
	 */
	public static function fixPropPersone($personeTypeId)
	{
		$arProps  = self::getOrderProps($personeTypeId);
		$arAddedFields = array();
		
		
		if (isset($arProps['CONTACT_PERSON'])) {
			$findProp = $arProps['CONTACT_PERSON'];
		} elseif ($arProps['FIO']) {
			$findProp = $arProps['FIO'];
		} else {
			// $arPersones = self::getPersonTypes();
			// deliveryHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_ERR_FIO",
				// array("PERSON_TYPE" => $arPersones[$personeTypeId]['NAME'])
			// ));
			
			$propName = self::getPropName('FIO');
			$propsGroups = self::getPropsGroups();
			
			$findProp = array(
				'PERSON_TYPE_ID' => $personeTypeId,
				'NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_FIO"),
				'TYPE' => 'TEXT',
				'REQUIED' => 'Y',
				'USER_PROPS' => 'Y',
				'PROPS_GROUP_ID' => $propsGroups[$personeTypeId][0],
				'CODE' => $propName,
				'SORT' => 240,
				'ACTIVE' => 'Y',
			);
			
			self::updateOrderProp($findProp);
			$arAddedFields[] = $propName;
		}

		if ($findProp['NAME'] != GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_FIO")) {
			self::updateOrderProp(array_merge($findProp, array('NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_FIO"), "CODE" => "FIO")));
		}
		
		COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, "fname", "FIO");

		$propName = self::getPropName('LAST_NAME');
		if (!isset($arProps[$propName])) {
			self::updateOrderProp(array(
				'PERSON_TYPE_ID' => $personeTypeId,
				'NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_LAST_NAME"),
				'TYPE' => 'TEXT',
				'REQUIED' => 'Y',
				'USER_PROPS' => 'Y',
				'PROPS_GROUP_ID' => $findProp['PROPS_GROUP_ID'],
				'CODE' => $propName,
				'SORT' => $findProp['SORT'] + 1,
				'ACTIVE' => 'Y',
			));
			
			$arAddedFields[] = $propName;
		}
		
		COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, "lname", $propName);

		$propName = self::getPropName('SECOND_NAME');
		if (!isset($arProps[$propName])) {
			self::updateOrderProp(array(
				'PERSON_TYPE_ID' => $personeTypeId,
				'NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_SECOND_NAME"),
				'TYPE' => 'TEXT',
				'REQUIED' => 'N',
				'USER_PROPS' => 'Y',
				'PROPS_GROUP_ID' => $findProp['PROPS_GROUP_ID'],
				'CODE' => $propName,
				'SORT' => $findProp['SORT'] + 2,
				'ACTIVE' => 'Y',
			));
			
			$arAddedFields[] = $propName;
		}
		
		COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, "mname", $propName);

		// return array(self::getPropName('LAST_NAME'), self::getPropName('SECOND_NAME'));
		return $arAddedFields;
	}

	/**
	 * Исправляет поле местоположения
	 * 
	 * @param int $personeTypeId
	 */
	public static function fixPropLocation($personeTypeId)
	{
		$arProps = self::getOrderProps($personeTypeId);

		$find = false;
		foreach($arProps as $code => $arProp) {
			if ($arProp['IS_LOCATION'] == 'Y') {
				return true;
			}
		}

		$propName = self::getPropName('LOCATION');
		$propsGroups = self::getPropsGroups();
		
		$findProp = array(
			'PERSON_TYPE_ID' => $personeTypeId,
			'NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_LOCATION"),
			'TYPE' => 'LOCATION',
			'REQUIED' => 'Y',
			'USER_PROPS' => 'Y',
			'PROPS_GROUP_ID' => $propsGroups[$personeTypeId][0],
			'CODE' => $propName,
			'SORT' => 140,
			'ACTIVE' => 'Y',
		);
		
		self::updateOrderProp($findProp);
		
		return array($propName);

		// $arPersones = self::getPersonTypes();
		// deliveryHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_ERR_LOCATION",
			// array("PERSON_TYPE" => $arPersones[$personeTypeId]['NAME'])
		// ));
	}

	/**
	 * Исправляет поле ADDRESS
	 * 
	 * @param  int $personeTypeId
	 */
	public static function fixPropAddress($personeTypeId)
	{
		$arProps = self::getOrderProps($personeTypeId);
		if (!isset($arProps['ADDRESS'])) {
			// $arPersones = self::getPersonTypes();
			// deliveryHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_ERR_ADDRESS",
				// array("PERSON_TYPE" => $arPersones[$personeTypeId]['NAME'])
			// ));
			$propName = self::getPropName('ADDRESS');
			$propsGroups = self::getPropsGroups();
			
			$arProps['ADDRESS'] = array(
				'PERSON_TYPE_ID' => $personeTypeId,
				'NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_ADDRESS"),
				'TYPE' => 'TEXTAREA',
				'REQUIED' => 'N',
				'USER_PROPS' => 'Y',
				'PROPS_GROUP_ID' => $propsGroups[$personeTypeId][0],
				'CODE' => $propName,
				'SORT' => 150,
				'ACTIVE' => 'N',
			);
		}
		else
		{
			if ($arProps['ADDRESS']['ACTIVE'] != 'N') {
				self::updateOrderProp(array_merge($arProps['ADDRESS'], array(
					'ACTIVE' => 'N',
				)));
			}
		}
		
		COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, "address", "");

		$propsToCreate = array(
			self::getPropName('STREET')     => array('NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_STREET"), 'REQUIED' => self::$oldTemplate?'N':'Y'),
			self::getPropName('HOUSE')      => array('NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_HOUSE"), 'REQUIED' => self::$oldTemplate?'N':'Y'),
			self::getPropName('BUILD')      => array('NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_BUILD"), 'REQUIED' => 'N'),
			self::getPropName('APARTAMENT') => array('NAME' => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_APARTAMENT"), 'REQUIED' => self::$oldTemplate?'N':'Y'),
		);
		
		$propsSuggest = array(
			self::getPropName('STREET') => "street",
			self::getPropName('HOUSE') => "house",
			self::getPropName('BUILD') => "build",
			self::getPropName('APARTAMENT') => "flat",
		);

		$sort = $arProps['ADDRESS']['SORT'];
		
		foreach($propsToCreate as $code => $parms) {
			if (isset($arProps[$code])) {
				$arProp = $arProps[$code];
			} else {
				$arProp = array_merge(array(
					'CODE'           => $code,
					'PERSON_TYPE_ID' => $personeTypeId,
					'PROPS_GROUP_ID' => $arProps['ADDRESS']['PROPS_GROUP_ID'],
					'TYPE'           => 'TEXT',
					'USER_PROPS'     => 'Y',
					'SORT'           => ++$sort,
				), $parms);

				// $arProp['ID'] = self::updateOrderProp($arProp);
			}
			$arProp['ID'] = self::updateOrderProp($arProp);
			
			COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, $propsSuggest[$code], $code);

			self::fixDeliveryTies($arProps['ADDRESS']['ID'], $arProp['ID']);
		}
		
		COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, "addressMode", "sep");

		return array_keys($propsToCreate);
	}

	/**
	 * Исправляет поле ZIP
	 * @param  [type] $personeTypeId [description]
	 * @return [type]                [description]
	 */
	public static function fixPropZip($personeTypeId)
	{
		$arProps = self::getOrderProps($personeTypeId);
		if (!isset($arProps['ZIP'])) {
			$propId = self::updateOrderProp(array(
				'CODE'           => 'ZIP',
				'NAME'           => GetMessage("TRADE_YANDEX_DELIVERY_propfix_PROP_ZIP"),
				'PERSON_TYPE_ID' => $personeTypeId,
				'PROPS_GROUP_ID' => $arProps['ADDRESS']['PROPS_GROUP_ID'],
				'TYPE'           => 'TEXT',
				'USER_PROPS'     => 'Y',
				'SORT'           => $arProps['ADDRESS']['SORT'] - 1,
			));
		} else {
			$propId = $arProps['ZIP']['ID'];
		}
		
		COption::SetOptionString(CDeliveryYaDriver::$MODULE_ID, "index", "ZIP");

		self::fixDeliveryTies($propId, $propId);

		return array('ZIP');
	}

	/**
	 * Изменяет привязку служб доставки у свойства
	 * 
	 * @param  [type] $fromPropId id свойства с которого будут скопированны тек. службы
	 * @param  [type] $toPropId   id свойства которому нужно проставить
	 * @return bool
	 */
	public static function fixDeliveryTies($fromPropId, $toPropId)
	{
		if (!CDeliveryYaHelper::isConverted() || self::$oldTemplate)
			return;
		
		$rsTies = CSaleOrderProps::GetOrderPropsRelations(array('PROPERTY_ID' => $fromPropId));
		$curDeliveryTies = array();
		while($arTie = $rsTies->GetNext()) {
			if ($arTie['ENTITY_TYPE'] != 'D') {
				continue;
			}

			$curDeliveryTies[] = $arTie['ENTITY_ID'];
		}

		if ($curDeliveryTies) {
			$newDeliveryTies = array_diff(array(
				self::DELIVERY_COURIER_ID(),
				self::DELIVERY_POST_ID()
			), $curDeliveryTies);
		} else {
			$rsDeliveries = CSaleDelivery::GetList(
				$arOrder = array('SORT' => 'ASC'),
				$arFilter = array(),
				$arGroupBy = false,
				$arNavParams = array(),
				$arSelect = array('ID')
			);

			$arDeliveries = array();
			while($arDelivery = $rsDeliveries->GetNext()) {
				if ($arDelivery['ID'] == self::DELIVERY_PICKUP_ID()) {
					continue;
				}

				$arDeliveries[] = $arDelivery['ID'];
			}

			$newDeliveryTies = array_unique(array_merge($arDeliveries, array(
				self::DELIVERY_COURIER_ID(),
				self::DELIVERY_POST_ID()
			)));
		}

		if (!$newDeliveryTies) {
			return true;
		}

		$curDeliveryTies = array_merge($curDeliveryTies, $newDeliveryTies);
		$ret = CSaleOrderProps::UpdateOrderPropsRelations($toPropId, $curDeliveryTies, 'D');
		
		if (!$ret) {
			CDeliveryYaHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_ERR_PROP"). $toPropId);
		}
	}

	private static function updateOrderProp($arFields)
	{
		if (!isset($arFields["DEFAULT_VALUE"]))
			$arFields["DEFAULT_VALUE"] = "";
		
		if ($arFields['ID']) {
			$ret = CSaleOrderProps::Update($arFields['ID'], $arFields);
		} else {
			$ret = CSaleOrderProps::Add($arFields);
		}

		if (!$ret) {
			$operation = $arFields['ID'] ? GetMessage("TRADE_YANDEX_DELIVERY_propfix_UPDATE"):GetMessage("TRADE_YANDEX_DELIVERY_propfix_ADDING");
			
			if($ex = $GLOBALS["APPLICATION"]->GetException())
				$strError = $ex->GetString();
			
			CDeliveryYaHelper::throwException(GetMessage("TRADE_YANDEX_DELIVERY_propfix_ERR_UPDATE",
				array("OPERATION" => $operation, "PROP_CODE" => $arFields['CODE'])
			)." ".$strError);
		}

		return $arFields['ID'] ? $arFields['ID'] : $ret;
	}
	
	private static function getPropsGroups()
	{
		$dbItems = CSaleOrderPropsGroup::GetList(
			$arOrder = array('SORT' => 'ASC'),
			$arFilter = array('ACTIVE' => 'Y')
		);
		
		$arResult = array();
		
		while($arItem = $dbItems->Fetch())
			$arResult[$arItem["PERSON_TYPE_ID"]][] = $arItem;
		
		return $arResult;
	}
}