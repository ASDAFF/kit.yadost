<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
$orderID = $_REQUEST['ID'];

CDeliveryYaDriver::getOrder($orderID);
CDeliveryYaDriver::getOrderProps($orderID);
CDeliveryYaDriver::getOrderConfirm($orderID);
CDeliveryYaDriver::getOrderBasket(array("ORDER_ID" => $orderID));
$arEndStatus = CDeliveryYaDriver::getEndStatus();
$arErrorStatus = CDeliveryYaDriver::getErrorStatus();
$arNotEditStatus = CDeliveryYaDriver::getNotEditableStatus();

// проверка обновлений модуля
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/update_client_partner.php");
$stableVersionsOnly = COption::GetOptionString("main", "stable_versions_only", "Y");
$arRequestedModules = array(CDeliveryYaDriver::$MODULE_ID);
$lastVersion = false;
if ($arUpdateList = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, $stableVersionsOnly, $arRequestedModules))
{
	$arUpdateList = $arUpdateList["MODULE"];
	$thisModule = false;
	foreach ($arUpdateList as $key => $module)
		if ($module["@"]["ID"] == CDeliveryYaDriver::$MODULE_ID)
			$thisModule = $module;
	
	if ($thisModule)
		foreach ($thisModule["#"]["VERSION"] as $val)
			$lastVersion = $val["@"]["ID"];
}

// текст сообщений - предупреждений
$arWarnings = array(
	"delivery_type_withdraw", // заявка вызов курьера на забор
	"orderPayed", // заказ оплачен
	"orderCancel", // заказ отменен
	"orderChange", // заказ изменен
	"orderError", // заказ со статусом ERROR в ЯД
	"orderSendChange", // изменили отправленный заказ
	"orderSendCancel", // отменили отправленный заказ
	
	"confirmCancel",
	"confirmSendCancel",
	"confirmSendCancelNegative",
	"requestError",
	"calculateError",
	
	"zeroGabs",
	"zeroWeight",
	"zeroWeightGabsMoreDefault",
	"zeroWeightGabsLessDefault",
	"changeWeightGabsAffect",
	
	"warningChangeDelivery",
);

$arLangsWarning = array();
foreach ($arWarnings as $warningCode)
	$arLangsWarning[$warningCode] = GetMessage("TRADE_YANDEX_DELIVERY_WARNING_" . $warningCode);

if ($lastVersion)
	$arLangsWarning["newModuleVersionDetected"] = GetMessage("TRADE_YANDEX_DELIVERY_WARNING_newModuleVersionDetected", array(
		"#MODULE_UPDATE_VERSION#" => $lastVersion,
		"#MODULE_ID#" => CDeliveryYaDriver::$MODULE_ID
	));

// флаги отмены, оплаты, изменения заказа
$isOrderPayed = ("Y" == CDeliveryYaDriver::$tmpOrder["PAYED"]) ? true : false;
$isOrderCancel = ("Y" == CDeliveryYaDriver::$tmpOrder["CANCELED"]) ? true : false;
$isOrderChange = CDeliveryYaHelper::isOrderChanged($orderID);

CJSCore::Init(array("jquery"));

$formElements = array();// массив элементов формы
$arLangs = array();// массив языковых констант

// группы настроек
$arOptionsGroupsSort = array(
	"COMMON" => 100, // общие информационные
	"RECIPIENT" => 200, // данные получателя
	"DELIVERY" => 300, // данные выбранной доставки
	"OPTIONAL" => 400, // опции выбираемые в форме
	"GABS" => 500, // опции веса, габаритов
	"WARNINGS" => 600, // блок сообщений
);

$arOptionsGroupsName = array();
foreach ($arOptionsGroupsSort as $group => $sort)
	$arOptionsGroupsName[$group] = GetMessage("TRADE_YANDEX_DELIVERY_GROUP_" . $group);

// фиктивная невидимая настройка для отображения блока сообщений внизу формы
$formElements["warning_fictive"] = array(
	"type" => "label",
	"name" => "",
	"value" => "",
	"sended" => false,
	"group" => "WARNINGS",
	"visible" => false
);

// лэйблы информационные
$arLabels = array("ORDER_ID", "delivery_ID", "parcel_ID", "STATUS");
foreach ($arLabels as $label)
{
	$value = CDeliveryYaDriver::$tmpOrderConfirm["savedParams"][$label];
	
	if (empty($value) && $label == "STATUS")
		$value = "NEW";
	
	if ($label == "ORDER_ID")
		$value = $orderID;
	
	if (empty($value))
		$value = "";
	
	$formElements[$label] = array(
		"type" => "label",
		"name" => GetMessage("TRADE_YANDEX_DELIVERY_LABELS_" . $label),
		"value" => $value,
		"sended" => false,
		"group" => "COMMON"
	);
	
	if ($label == "delivery_ID")
		$formElements[$label]["href"] = array(
			//"value" => "https://delivery.yandex.ru/order/create?id=#REPLACER_0#",
			"value" => "https://delivery.yandex.ru/order/create?id=".$formElements[$label]["value"],
			"replaces" => array("value")
		);
	
	if ($label == "parcel_ID")
		$formElements[$label]["href"] = array(
			"value" => "https://delivery.yandex.ru/order"
		);
}

// статус запрашиваем, если заказ уже отправлен
$status = null;
if (CDeliveryYaDriver::$tmpOrderConfirm["savedParams"]["delivery_ID"])
{
	$status = CDeliveryYaDriver::getOrderStatus(array("delivery_ID" => CDeliveryYaDriver::$tmpOrderConfirm["savedParams"]["delivery_ID"]));
	
	if ($status)
	{
		$formElements["STATUS"]["value"] = $status;
		CDeliveryYaDriver::updateOrderStatus(array($orderID => $status));
	}
}

$statusNames = CDeliveryYaHelper::getDeliveryStatuses();
$statusNames["NEW"] = GetMessage("TRADE_YANDEX_DELIVERY_YD_STATUS_NEW");

foreach ($statusNames as $code => $value)
	$arLangs["status_name"][$code] = GetMessage("TRADE_YANDEX_DELIVERY_YD_STATUS_" . $code);

$formElements["status_info"] = array(
	"type" => "label",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_status_info_NAME"),
	"value" => $statusNames[$status],
	"group" => "COMMON"
);

// способ доставки на склад ЯД
$formElements["is_payed"] = array(
	"type" => "checkbox",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_is_payed_NAME"),
	"value" => ("Y" == CDeliveryYaDriver::$tmpOrder["PAYED"]) ? "Y" : "N",
	"sended" => false,
	"group" => "COMMON",
	"disabled" => true
);

// если статус не из категории запрещенных для редактирования, подключаем виджет
// if (!in_array($statusNames[CDeliveryYaDriver::$tmpOrderConfirm["savedParams"]["STATUS"]], $arNotEditStatus))
$GLOBALS['APPLICATION']->AddHeadString(COption::GetOptionString("yandex.delivery", "basketWidget"));

// название доставки
$formElements["delivery_name"] = array(
	"type" => "label",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_name_NAME"),
	"value" => CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["delivery"]["name"],
	"data" => CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["delivery"]["unique_name"],
	"sended" => true,
	"group" => "DELIVERY"
);

// тип доставки курьер, самовывоз, почта
$arTariffNames = array(
	"TODOOR" => "",
	"POST" => "",
	"PICKUP" => ""
);

foreach ($arTariffNames as $code => $value)
	$arLangs["profile_name"][$code] = GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_profile_name_" . $code);

$formElements["profile_name"] = array(
	"type" => "label",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_profile_name_NAME"),
	"value" => $arLangs["profile_name"][CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["type"]],
	"data" => CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["type"],
	"sended" => true,
	"group" => "DELIVERY"
);

CDeliveryYaDriver::getModuleSetups();
CDeliveryYaDriver::getOrderProps($orderID);

// город доставки
// смотрим не изменился ли город
$locationValue = CDeliveryYaHelper::getOrderLocationValue($orderID, CDeliveryYaDriver::$tmpOrder["PERSON_TYPE_ID"]);

$city = null;
if ($locationValue)
	$city = CDeliveryYaHelper::getCityNameByID($locationValue);

$cityName = $city["NAME"] ? $city["NAME"] : CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["deliveryCity"];

if ($city["REGION"])
    $cityName = $city["REGION"] . " " . $cityName;

$formElements["city"] = array(
	"type" => "label",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_deliveryCity_NAME"),
	// "value" => CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["deliveryCity"],
	"value" => $cityName,
	"sended" => true,// признак, что это поле читается с формы и отправляется в аякс
	"group" => "DELIVERY"
);

// поля адреса доставки
foreach (CDeliveryYaDriver::$options["ADDRESS"] as $name => $value)
{
	// $propValue = !empty(CDeliveryYaDriver::$tmpOrderConfirm["formData"][$name])?
	// CDeliveryYaDriver::$tmpOrderConfirm["formData"][$name]:
	// CDeliveryYaDriver::$tmpOrderProps[$name];
	$propValue = CDeliveryYaDriver::$tmpOrderProps[$name];
	
	// если адрес пустой, пробуем взять его из свойства адрес ПВЗ
    if ($name == "address" && empty($propValue))
	{
		$propAddress = CSaleOrderPropsValue::GetList(
			array(),
			array(
				"ORDER_ID" => $orderID,
				"CODE" => "yandex_delivery_PVZ_ADDRESS"
			)
		);
		
		while ($address = $propAddress->Fetch())
			if (!empty($address["VALUE"]))
				$propValue = $address["VALUE"];
	}
	
	$disabled = false;
	if (!empty($value))
		$disabled = true;
	
	if (empty($propValue) || $propValue == " ")
		$propValue = "";
	
	$formElements[$name] = array(
		"type" => "input",
		"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_" . $name . "_NAME"),
		"value" => $propValue,
		"sended" => true,// признак, что это поле читается с формы и отправляется в аякс
		"group" => "RECIPIENT",
		"setInOptions" => !$disabled,// признак, что выставили в настройках модуля, значит на форме не давать редактировать
		"disabled" => $disabled
	);
}

$formElements["address"]["type"] = "textarea";
$formElements["address"]["disabled"] = true;

// это доставка на склад ЯД
$formElements["delivery_type"] = array(
	"type" => "select",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_NAME"),
	"value" => array(
		"import" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_import"),
		"withdraw" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_withdraw"),
	),
	"empty" => false, // признак, может ли быть пустым,
	"sended" => true,
	"selected" => (!empty(CDeliveryYaDriver::$tmpOrderConfirm["formData"]["delivery_type"])) ?
		CDeliveryYaDriver::$tmpOrderConfirm["formData"]["delivery_type"] :
		// "import",
		COption::GetOptionString(CDeliveryYaDriver::$MODULE_ID, "delivery_type_import_widthdraw", "import"),
	"events" => array(
		"onChange" => "deliverySender.deliveryTypeChange();"
	),
	"group" => "OPTIONAL"
);


// стоимость доставки
$deliveryPrice = CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["costWithRules"];
if (CDeliveryYaDriver::$tmpOrderConfirm["formData"]["delivery_price"])
	$deliveryPrice = CDeliveryYaDriver::$tmpOrderConfirm["formData"]["delivery_price"];

$formElements["delivery_price"] = array(
	"type" => "label",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_price_NAME"),
	"value" => $deliveryPrice,
	"sended" => true,// признак, что это поле читается с формы и отправляется в аякс
	"group" => "DELIVERY"
);


$deliveryTerms = CDeliveryYa::getDeliveryTerm(
	CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["minDays"],
	CDeliveryYaDriver::$tmpOrderConfirm["widgetData"]["maxDays"]
);

// время доставки
$formElements["delivery_terms"] = array(
	"type" => "label",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_terms_NAME"),
	"value" => $deliveryTerms,
	"sended" => true,// признак, что это поле читается с формы и отправляется в аякс
	"group" => "DELIVERY"
);

// изменить стоимость доставки в заказе
$changeDeliveryPrice = CDeliveryYaDriver::$tmpOrderConfirm["formData"]["change_delivery_price"];
if (empty($changeDeliveryPrice))
	$changeDeliveryPrice = "Y";

$formElements["change_delivery_price"] = array(
	"type" => "checkbox",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_change_delivery_price_NAME"),
	"value" => $changeDeliveryPrice,
	"sended" => true,
	"group" => "DELIVERY"
);
/*
// способ доставки на склад ЯД
$formElements["import_type"] = array(
	"type" => "select",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_import_type_NAME"),
	"value" => array(
		"courier" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_import_type_courier"),
		"car" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_import_type_car"),
	),
	"empty" => false, // признак, может ли быть пустым
	"sended" => true,
	"selected" => (CDeliveryYaDriver::$tmpOrderConfirm["formData"]["import_type"])?
		CDeliveryYaDriver::$tmpOrderConfirm["formData"]["import_type"]:
		"courier",
	"group" => "OPTIONAL"
);*/

// дата отгрузки
$shipmentDate = CDeliveryYaDriver::$tmpOrderConfirm["formData"]["shipment_date"];
if (empty($shipmentDate))
	$shipmentDate = CDeliveryYaDriver::getShipmentDate("d.m.Y");

$formElements["shipment_date"] = array(
	"type" => "date",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_shipment_date_NAME"),
	"empty" => false, // признак, может ли быть пустым
	"sended" => true,
	"value" => $shipmentDate,
	"group" => "OPTIONAL",
	"events" => array(
		"onChange" => "deliverySender.shipmentDateChange();"
	),
);


// способ доставки на склад ЯД
$toYdWarehouse = CDeliveryYaDriver::$tmpOrderConfirm["formData"]["to_yd_warehouse"];
if (empty($toYdWarehouse))
	$toYdWarehouse = CDeliveryYaDriver::$options["to_yd_warehouse"];

$formElements["to_yd_warehouse"] = array(
	"type" => "checkbox",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_to_yd_warehouse_NAME"),
	"value" => $toYdWarehouse,
	"sended" => true,
	"group" => "OPTIONAL",
	"events" => array(
		"onChange" => "deliverySender.warehouseChange();"
	),
);

// ID склада отправителя
CDeliveryYaDriver::getRequestConfig();
$arRequestConfig = CDeliveryYaDriver::$requestConfig;

$arWarehouses = $arRequestConfig["warehouse_id"];

if (isset(CDeliveryYaDriver::$tmpOrderConfirm["formData"]["warehouseConfigNum"]))
	$warehouseConfigNum = CDeliveryYaDriver::$tmpOrderConfirm["formData"]["warehouseConfigNum"];
else
	$warehouseConfigNum = CDeliveryYaDriver::$options["defaultWarehouse"];

foreach ($arWarehouses as $num => $warehouse)
{
	if (!empty($warehouse))
	{
		$warehouseInfo = CDeliveryYaHelper::convertFromUTF(CDeliveryYaDriver::getWarehouseInfo($warehouse));
		if ($warehouseInfo["warehouseInfo"]["data"]["field_name"])
			$arWarehouses[$num] .= " " . $warehouseInfo["warehouseInfo"]["data"]["field_name"];
	}
}

$formElements["warehouseConfigNum"] = array(
	"type" => "select",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_warehouse_ID_NAME"),
	"value" => $arWarehouses,
	"empty" => false, // признак, может ли быть пустым,
	"sended" => true,
	"selected" => $warehouseConfigNum,
	"group" => "OPTIONAL"
);

$assessedCostPercent = CDeliveryYaDriver::$tmpOrderConfirm["formData"]["assessedCostPercent"];
if (empty($assessedCostPercent))
	$assessedCostPercent = CDeliveryYaDriver::$options["assessedCostPercent"];
$formElements["assessedCostPercent"] = array(
	"type" => "input",
	"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_assessedCostPercent_NAME"),
	"value" => $assessedCostPercent,
	"sended" => true,// признак, что это поле читается с формы и отправляется в аякс
	"group" => "OPTIONAL",
	"events" => array(
		"onChange" => "deliverySender.assessedCostChange();"
	),
);

// габариты и вес
$arGabsValues = array(
	"LENGTH",
	"WIDTH",
	"HEIGHT",
	"WEIGHT",
);

foreach ($arGabsValues as $code)
{
	$val = CDeliveryYaDriver::$tmpOrderConfirm["formData"][$code];
	if (!isset(CDeliveryYaDriver::$tmpOrderConfirm["formData"][$code]))
		$val = CDeliveryYaDriver::$tmpOrderDimension[$code];
	
	$formElements[$code] = array(
		"type" => "input",
		"name" => GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_" . $code . "_NAME"),
		"value" => $val,
		"sended" => true,// признак, что это поле читается с формы и отправляется в аякс
		"group" => "GABS",
		"events" => array(
			"onChange" => "deliverySender.dimensionsChange();"
		),
	);
}
?>
<!--suppress ALL, JSUnresolvedFunction -->

<style>
    table.TRADE_YANDEX_DELIVERY_table_form {
        width: 100%;
    }

    table.TRADE_YANDEX_DELIVERY_table_form td {
        /*border: 1px solid red;*/
    }

    table.TRADE_YANDEX_DELIVERY_table_form input[type="text"] {
        width: 130px;
    }

    table.TRADE_YANDEX_DELIVERY_table_form textarea {
        width: 98.3%;
    }

    table.TRADE_YANDEX_DELIVERY_table_form .yd_table_form_block_title {
        padding-top: 20px;
        text-transform: uppercase;
    }

    table.TRADE_YANDEX_DELIVERY_table_form tr:first-child .yd_table_form_block_title {
        padding-top: 5px;
    }

    table.TRADE_YANDEX_DELIVERY_table_form .yd_table_form_block_warning div {
        color: red;
        padding: 5px 0 10px;
    }
</style>

<?
global $USER;
$rsUser = CUser::GetByID($USER->GetID());
$arUser = $rsUser->Fetch();
$site_id = $arUser["LID"];

$rsSites = CSite::GetList($by="sort", $order="desc", Array("ID" => $arUser["LID"]));
while ($arSite = $rsSites->Fetch()) {
    $siteDomain = $arSite["SERVER_NAME"];
}
?>

<script>
    $(document).ready(function ()
    {
        var siteIDs = <?=CUtil::PHPtoJSObject(CDeliveryYaHelper::selectSite())?>;
        var siteID = <?=CUtil::PHPtoJSObject($siteDomain)?>;

        if (siteIDs.indexOf(siteID) != -1 || siteIDs == 0) {
            if(!$('#yandexDelivery_admin_dialog_button').hasClass('adm-btn')){
                $('.adm-detail-toolbar').find('.adm-detail-toolbar-right').prepend("<a href='javascript:void(0)' onclick='deliverySender.ShowDialog();' class='adm-btn' id = 'yandexDelivery_admin_dialog_button'><?=GetMessage('TRADE_YANDEX_DELIVERY_JSC_SOD_BTNAME')?></a>");
            }
            deliverySender.handleDialogButton();// управление цветом текста кнопки
        }
    });
</script>

<script>
    if (typeof deliverySender === "undefined")
        var deliverySender = {

            endStatus: <?=CUtil::PHPtoJSObject($arEndStatus)?>,
            errorStatus: <?=CUtil::PHPtoJSObject($arErrorStatus)?>,
            notEditableStatus: <?=CUtil::PHPtoJSObject($arNotEditStatus)?>,
            tmpOrderConfirm: <?=CUtil::PHPtoJSObject(CDeliveryYaDriver::$tmpOrderConfirm)?>,
            formElements: <?=CUtil::PHPtoJSObject($formElements)?>,
            formElementsGroups: <?=CUtil::PHPtoJSObject($arOptionsGroupsName)?>,
            formElementsGroupsSort: <?=CUtil::PHPtoJSObject($arOptionsGroupsSort)?>,
            arLangs: <?=CUtil::PHPtoJSObject($arLangs)?>,
            arLangsWarning: <?=CUtil::PHPtoJSObject($arLangsWarning)?>,
            isOrderPayed: <?=CUtil::PHPtoJSObject($isOrderPayed)?>,
            isOrderCancel: <?=CUtil::PHPtoJSObject($isOrderCancel)?>,
            isOrderChange: <?=CUtil::PHPtoJSObject($isOrderChange)?>,

            lastVersion: <?=CUtil::PHPtoJSObject($lastVersion)?>,
            tmpOrderDimension: <?=CUtil::PHPtoJSObject(CDeliveryYaDriver::$tmpOrderDimension)?>,

            isAdmin: <?=CUtil::PHPtoJSObject(CDeliveryYaHelper::isAdmin())?>,

            formOpened: false,// признак, что форма открывалась
            recalculate: true,// признак необходимости перерасчета стоиомости доставки

            onLoad: function ()
            {
                if (typeof deliverySender.tmpOrderConfirm != "object")
                    deliverySender.tmpOrderConfirm = {};

                if (typeof deliverySender.tmpOrderConfirm.savedParams == "undefined")
                    deliverySender.tmpOrderConfirm.savedParams = {};

                deliverySender.dimensionsChanged = false;
            },

            // прводит соответсвие текущих настроек и параметров
            suggestDependens: function ()
            {
                var cancel = deliverySender.isOrderCancel,
                    change = deliverySender.isOrderChange,
                    status = deliverySender.getStatusGroup();

                // cancel = false;// признак отмены в Битрикс
                // change = true;// признак изменения в Битрикс
                // status = 3;// 0 - старт; 1 - notEdit; 2 - error; 3 - остальные

                // если статус ERROR, неважно какие остальные параметры
                if (status == 2)
                    return {
                        "edit": false,
                        "button": ["cancel"],
                        "color": "red",
                        "message": ["orderError"]
                    };

                var suggest = {
                    true: {
                        true: {
                            0: {
                                "edit": false,
                                "button": [],
                                "color": "gray",
                                "message": []
                            },
                            1: {
                                "edit": false,
                                "button": ["cancel"],
                                "color": "red",
                                "message": ["orderCancel"]
                            },
                            3: {
                                "edit": false,
                                "button": ["cancel"],
                                "color": "red",
                                "message": ["orderSendCancel"]
                            }
                        },
                        false: {
                            0: {
                                "edit": false,
                                "button": [],
                                "color": "gray",
                                "message": []
                            },
                            1: {
                                "edit": false,
                                "button": ["cancel"],
                                "color": "red",
                                "message": ["orderCancel"]
                            },
                            3: {
                                "edit": false,
                                "button": ["cancel"],
                                "color": "red",
                                "message": ["orderSendCancel"]
                            }
                        }
                    },
                    false: {
                        true: {
                            0: {
                                "edit": true,
                                "button": ["confirm", "save", "changeDelivery"],
                                "color": "red",
                                "message": ["orderChange"]
                            },
                            1: {
                                "edit": false,
                                "button": ["cancel", "print", "edit"],
                                "color": "red",
                                "message": ["orderChange"]
                            },
                            3: {
                                "edit": false,
                                "button": ["cancel", "print", "edit"],
                                "color": "red",
                                "message": ["orderSendChange"]
                            }
                        },
                        false: {
                            0: {
                                "edit": true,
                                "button": ["confirm", "save", "changeDelivery"],
                                "color": "yellow",
                                "message": []
                            },
                            1: {
                                "edit": false,
                                "button": ["cancel", "print", "edit"],
                                "color": "yellow",
                                "message": []
                            },
                            3: {
                                "edit": false,
                                "button": ["cancel", "print", "edit"],
                                "color": "yellow",
                                "message": []
                            }
                        }
                    }
                };

                var result = suggest[cancel][change][status];

                // если изменили габариты на форме, удаляем кнопку перевыбора способа доставки
                deliverySender.showWarningchangeDelivery = false;
                if (deliverySender.dimensionsChanged)
                    if (result["button"].length > 0)
                        for (var i in result["button"])
                            if (result["button"][i] == "changeDelivery")
                            {
                                result["button"].splice(i, 1);
                                deliverySender.showWarningChangeDelivery = true;
                            }
                return result;
            },

            getStatusGroup: function ()
            {
                var status = deliverySender.getCurValue("STATUS"),
                    startStatus = ["NEW", "DRAFT"];

                // начальные статусы
                if (deliverySender.inStatus(status, startStatus))
                    return 0;

                // возможные для редактирования статусы
                if (deliverySender.inStatus(status, deliverySender.notEditableStatus))
                    return 1;

                // ошибка
                if (deliverySender.inStatus(status, deliverySender.errorStatus))
                    return 2;

                // все остальные
                return 3;
            },

            // управление цветом кнопки
            handleDialogButton: function (dependens)
            {
                if (typeof dependens == "undefined")
                {
                    dependens = deliverySender.suggestDependens();
                    dependens = dependens["color"];
                }

                var button = $('#yandexDelivery_admin_dialog_button'),
                    colors = {
                        "yellow": "#d4af1e",
                        "gray": "#3f4b54",
                        "red": "#F13939",
                        "green": "#3A9640"
                    };

                button.css('color', colors[dependens]);
            },

            // получение текущего значения из объекта
            getCurValue: function (code)
            {
                if (typeof deliverySender.formElements != "undefined")
                    if (typeof deliverySender.formElements[code] != "undefined")
                        return deliverySender.formElements[code].value;

                return null;
            },

            // получение текущего значения из формы
            getCurFormValue: function (code)
            {
                var obj = yd$("#delivery_input_" + code);

                if (typeof deliverySender.formElements != "undefined")
                    if (typeof deliverySender.formElements[code] != "undefined")
                    {
                        if (deliverySender.formElements[code].type == "select")
                            return obj.find("option:selected").val();
                        else
                            return obj.val();

                    }
                return null;
            },

            dialogButtons: {
                "save": {
                    "action": "saveFormData",
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_SAVE_FORM_DATA')?>",
                    "onclick": "deliverySender.saveFormData();"
                },
                "send": {
                    "action": "sendOrder",
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_SEND_DRAFT')?>",
                    "onclick": "deliverySender.sendOrder();"
                },
                "confirm": {
                    "perform_actions": "confirm",
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_SEND_CONFIRM')?>",
                    "onclick": "deliverySender.sendOrder('confirm');"
                },
                "print": {
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_DOCS')?>",
                    "onclick": "deliverySender.printDocs();"
                },
                "changeDelivery": {
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_CHANGE_DELIVERY')?>",
                    "onclick": "deliverySender.initWidget();",
                    "data": {
                        "ydwidget-open": null
                    }
                },
                "cancel": {
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_CANCEL_ORDER')?>",
                    "onclick": "deliverySender.cancelOrder();"
                },
                "edit": {
                    "value": "<?=GetMessage('TRADE_YANDEX_DELIVERY_EDIT_ORDER')?>",
                    "onclick": "deliverySender.editOrder();"
                },
            },

            // кнопки, доступные для прав НЕ ниже "Запись"
            adminButtons: {
                "save": true,
                "send": true,
                "confirm": true,
                "print": false,
                "changeDelivery": false,
                "cancel": true,
                "edit": true
            },

            // определяем видимость элементов формы
            checkVisbility: function ()
            {
                // свойства, которые надо скрывать для самовывоза
                var arOnlyPickup = {
                    "index": true,
                    "street": true,
                    "house": true,
                    "build": true,
                    "flat": true
                };

                // поля, на которые влияет тип отгрузки
                var arInputs = {
                    "import_type": true,
                    "interval": true,
                    "deliveries": true
                };

                for (var i in deliverySender.formElements)
                {
                    // для самовывоза
                    if (arOnlyPickup[i])
                        if (deliverySender.formElements["profile_name"]["data"] == "PICKUP")
                            deliverySender.formElements[i]["visible"] = false;
                        else
                            deliverySender.formElements[i]["visible"] = true;

                    // для типа отгрузки
                    if (arInputs[i])
                        if (deliverySender.formElements["delivery_type"]["selected"] == "withdraw")
                            deliverySender.formElements[i]["visible"] = false;
                        else
                            deliverySender.formElements[i]["visible"] = true;
                }

            },

            // изменение заказа
            editOrder: function ()
            {
                var dataObject = {};
                dataObject["action"] = "getOrderStatus";
                dataObject["bitrix_ID"] = "<?=$orderID?>";

                deliverySender.doAjax(dataObject, function (data)
                {
                    if (typeof data.data.error == "undefined")
                    {
                        deliverySender.tmpOrderConfirm.savedParams["STATUS"] = data.data;
                        deliverySender.formElements.STATUS.value = data.data;

                        var dependens = deliverySender.suggestDependens(),
                            confirmMessages = {
                                1: "confirmCancel",
                                2: "confirmSendCancel",
                                3: "confirmSendCancel"
                            },
                            group = deliverySender.getStatusGroup();

                        if (group == 1)
                        {
                            confirmResult = confirm(deliverySender.getWarningText(confirmMessages[group]));

                            if (confirmResult)
                            {
                                var canCancel = false;
                                for (var i in dependens["button"])
                                    if ("cancel" == dependens["button"][i])
                                        deliverySender.cancelOrder();
                            }
                            else
                            {
                                // deliverySender.addWarning("COMMON", "confirmSendCancelNegative");
                            }
                        }
                        else
                            alert(deliverySender.getWarningText(confirmMessages[group]));
                    }
                    else
                    {
                        alert("<?=GetMessage("TRADE_YANDEX_DELIVERY_WARNING_requestError")?>");
                    }
                });
            },

            // при смене даты отгрузки
            shipmentDateChange: function ()
            {
                deliverySender.formElements.shipment_date.value = deliverySender.getCurFormValue("shipment_date");

                deliverySender.checkDeliveryTimeLimits();

                // перерисовываем форму без пересчета
                deliverySender.recalculate = false;
                deliverySender.drawForm();
                deliverySender.recalculate = true;
            },

            // изменение оценочной стоимости
            assessedCostChange: function ()
            {
                deliverySender.formElements.assessedCostPercent.value = deliverySender.getCurFormValue("assessedCostPercent");
                deliverySender.dimensionsChanged = true;
                // перерисовываем форму c пересчетом
                deliverySender.drawForm();
            },

            // изменение настройки использовать скляд Яндекс.Доставки
            warehouseChange: function ()
            {
                if (deliverySender.getCurFormValue("to_yd_warehouse") == "Y")
                    deliverySender.formElements.to_yd_warehouse.value = "N";
                else
                    deliverySender.formElements.to_yd_warehouse.value = "Y";

                deliverySender.checkDeliveryTimeLimits();

                // перерисовываем форму без пересчета
                deliverySender.recalculate = false;
                deliverySender.drawForm();
                deliverySender.recalculate = true;
            },

            // обработчик переключателя Тип отгрузки
            deliveryTypeChange: function ()
            {
                // определяемся с настройкой Использовать склад Яндекс.Доставки
                deliverySender.formElements.delivery_type.selected = deliverySender.getCurFormValue("delivery_type");
                deliverySender.checkDeliveryTimeLimits();

                // перерисовываем форму без пересчета
                deliverySender.recalculate = false;
                deliverySender.drawForm();
                deliverySender.recalculate = true;


                // var inputName = "delivery_type",
                // obj = $("#delivery_input_"+inputName),
                // value = obj.find(":checked").val(),
                // visible = false,
                // display = "none";

                // поля, на которые влияет тип отгрузки
                // var arInputs = [/*"import_type", "interval", "deliveries"*/];

                // if (value == "import")
                // {
                // visible = true;
                // display = "";
                // deliverySender.addWarning("OPTIONAL", false);
                // }
                // else
                // deliverySender.addWarning("OPTIONAL", "delivery_type_withdraw");

                // for (var i in arInputs)
                // {
                // deliverySender.formElements[arInputs[i]].visible = visible;
                // $("#delivery_input_" + arInputs[i]).css("display", display);
                // }
            },

            // отображает сообщение для блока
            addWarning: function (groupCode, warningCode, additionalText)
            {
                if (typeof warningCode == "undefined")
                    warningCode = false;

                var obj = $("[data=yd_table_form_block_warning_" + groupCode + "]");

                if (obj.length > 0)
                    if (warningCode)
                    {
                        var warningText = deliverySender.getWarningText(warningCode);
                        if (additionalText)
                            warningText += additionalText;

                        var warnindDiv = obj.children("div");
                        if (warnindDiv.length > 0)
                            warnindDiv.append("<br>" + warningText);
                        else
                            obj.html("<div>" + warningText + "</div>");
                    }
                    else
                        obj.html("");
            },

            getWarningText: function (warningCode)
            {
                return deliverySender.arLangsWarning[warningCode];
            },

            // формируем html полей формы
            putFormInputs: function ()
            {
                var formInputs = deliverySender.formElements,
                    html = {},
                    iterator = {},
                    strElemCount = 3;// количество элементов в строке

                for (var i in formInputs)
                {
                    var group = formInputs[i].group,
                        sort = deliverySender.formElementsGroupsSort[group];

                    // заголовок блока
                    if (typeof html[sort] == "undefined")
                    {
                        var combineTableCols = strElemCount * 2;
                        html[sort] = "<tr><td class = 'yd_table_form_block_title' colspan = '" + combineTableCols + "'>";

                        if (typeof deliverySender.formElementsGroups[group] != "undefined")
                            html[sort] += "<b>" + deliverySender.formElementsGroups[group] + "</b>";

                        html[sort] += "</td></tr>";

                        // для уведомлений
                        html[sort] += "<tr><td data = 'yd_table_form_block_warning_" + group + "' class = 'yd_table_form_block_warning' colspan = '" + combineTableCols + "'></td></tr>";
                    }

                    if (typeof iterator[sort] == "undefined")
                        iterator[sort] = 0;

                    // для textarea выводим во всю ширину формы
                    var combine = "";
                    if (formInputs[i].type == "textarea")
                        combine = "colspan = " + (strElemCount * 2 - 1);

                    if (iterator[sort] >= strElemCount || combine != "")
                    {
                        if (combine != "" && !iterator[sort])
                            html[sort] += "</tr>";

                        html[sort] += "<tr>";
                    }

                    // оболочка <td></td><td></td> с учетом видимости
                    html[sort] += "<td style='text-align: right; font-weight: bold;";

                    if (formInputs[i]["visible"] == false)
                        html[sort] += "display: none;";

                    html[sort] += "'>" + formInputs[i].name + ": </td><td style = '";

                    if (formInputs[i]["visible"] == false)
                        html[sort] += "display: none;";

                    html[sort] += "' " + combine + ">";

                    // содержимое в зависимости от типа
                    switch (formInputs[i].type)
                    {
                        case "date":
                            html[sort] += "<div style = 'position: relative;'>";
                            html[sort] += "<input type='text' class='adm-calendar-from' ";
                            html[sort] += "id = 'delivery_input_" + i + "' ";

                            if (formInputs[i].disabled)
                                html[sort] += "disabled ";

                            if (formInputs[i].events)
                                for (var n in formInputs[i].events)
                                    html[sort] += n + " = '" + formInputs[i].events[n] + "' ";

                            html[sort] += "value = '" + formInputs[i].value + "'>";

                            if (!(typeof formInputs[i].disabled != "undefined" && formInputs[i].disabled == true))
                                html[sort] += "<span class='adm-calendar-icon' onclick=\"BX.calendar({node:this, field:'delivery_input_" + i + "', form: '', bTime: false, bHideTime: false});\"></span>";

                            html[sort] += "</div>";
                            break;

                        case "select":
                            html[sort] += "<select ";

                            if (formInputs[i].disabled)
                                html[sort] += "disabled ";

                            if (formInputs[i].events)
                                for (var n in formInputs[i].events)
                                    html[sort] += n + " = '" + formInputs[i].events[n] + "' ";

                            html[sort] += "id = 'delivery_input_" + i + "'>";

                            if (formInputs[i].empty)
                            {
                                html[sort] += "<option ";

                                if (formInputs[i].selected == "")
                                    html[sort] += "selected";

                                html[sort] += "></option>";
                            }

                            for (var k in formInputs[i].value)
                            {
                                html[sort] += "<option value = '" + k + "' ";

                                if (k == formInputs[i].selected)
                                    html[sort] += "selected";

                                html[sort] += ">" + formInputs[i].value[k] + "</option>";
                            }

                            html[sort] += "</select>";
                            break;

                        case "label":
                            html[sort] += "<span ";

                            if (typeof formInputs[i].data != "undefined")
                                html[sort] += "data-trade_yandex_delivery_data = '" + formInputs[i].data + "'";

                            html[sort] += "id = 'delivery_input_" + i + "'>";

                            if (typeof formInputs[i].href != "undefined")
                            {
                                var href = formInputs[i].href,
                                    readyHref = null;

                                if (typeof href.replacers != "undefined")
                                    for (var k in href.replacers)
                                    {
                                        var regex = new RegExp("#REPLACER_" + k + "#");
                                        readyHref = href.value.replace(regex, formInputs[i][href.replacers[k]]);
                                    }

                                if (readyHref == null)
                                    readyHref = formInputs[i].href.value;

                                html[sort] += "<a target = '_blank' href = '" + readyHref + "'>" + formInputs[i].value + "</a>";
                            }
                            else
                                html[sort] += formInputs[i].value;

                            html[sort] += "</span>";
                            break;

                        case "input":
                            html[sort] += "<input ";
                            html[sort] += "type = 'text' ";

                            if (formInputs[i].disabled)
                                html[sort] += "readonly disabled ";

                            if (formInputs[i].events)
                                for (var n in formInputs[i].events)
                                    html[sort] += n + " = '" + formInputs[i].events[n] + "' ";

                            html[sort] += "id = 'delivery_input_" + i + "' ";

                            html[sort] += "value = '" + formInputs[i].value + "'";
                            html[sort] += ">";
                            break;

                        case "checkbox":
                            html[sort] += "<input ";
                            html[sort] += "type = 'checkbox' ";

                            if (formInputs[i].disabled)
                                html[sort] += "readonly disabled ";

                            if (formInputs[i].events)
                                for (var n in formInputs[i].events)
                                    html[sort] += n + " = '" + formInputs[i].events[n] + "' ";

                            html[sort] += "id = 'delivery_input_" + i + "' ";

                            html[sort] += "value = '" + formInputs[i].value + "' ";

                            if ("Y" == formInputs[i].value)
                                html[sort] += "checked = 'checked'";

                            html[sort] += ">";
                            break;

                        case "textarea":
                            html[sort] += "<textarea rows = '2'";

                            if (formInputs[i].sended)
                                html[sort] += "data-trade_yandex_deliverySended = 'true' ";

                            if (formInputs[i].disabled)
                                html[sort] += "readonly disabled ";

                            html[sort] += "id = 'delivery_input_" + i + "'>";
                            html[sort] += formInputs[i].value;
                            html[sort] += "</textarea>";
                            break;
                    }

                    html[sort] += "</td>";

                    iterator[sort]++;
                    if (iterator[sort] >= strElemCount || combine != "")
                    {
                        html[sort] += "</tr>";
                        iterator[sort] = 0;
                    }
                }

                var returnHTML = "";
                for (var group in html)
                    returnHTML += html[group];

                return returnHTML;
            },

            // отрисовка формы и постобработка
            drawForm: function ()
            {
                // сначала рисуем форму как есть
                // проверка по флагам доступных кнопок, полей и т.д.
                var dependens = deliverySender.suggestDependens();

                // возможность редактирования полей формы
                deliverySender.checkEditable(dependens["edit"]);

                // ставим из языковых констант значения
                deliverySender.setLangValues();

                $("#TRADE_YANDEX_DELIVERY_table_form").html(deliverySender.getFormHTML());
                deliverySender.formOpened = true;

                // задаем видимость кнопок, скрываем/отображаем
                deliverySender.checkButtons(dependens["button"]);

                // задаем цвет кнопки открытия формы
                deliverySender.handleDialogButton(dependens["color"]);

                // даем сообщение об оплате
                if (deliverySender.isOrderPayed)
                    deliverySender.addWarning("DELIVERY", "orderPayed");

                // выводим сообщение о доступности обновления модуля
                if (deliverySender.lastVersion != false)
                    deliverySender.addWarning("COMMON", "newModuleVersionDetected");

                if (deliverySender.calculateError)
                    deliverySender.addWarning("GABS", "calculateError");

                // сообщения о товарах с 0 весом или габаритами
                if (deliverySender.isZeroGabsWeight)
                {
                    if (deliverySender.totalWeightMoreDefault)
                        deliverySender.addWarning("WARNINGS", "zeroWeightGabsMoreDefault");
                    else
                        deliverySender.addWarning("WARNINGS", "zeroWeightGabsLessDefault");

                    deliverySender.addWarning("WARNINGS", "changeWeightGabsAffect");
                }

                deliverySender.addGabsWarning(deliverySender.zeroGabs, "zeroGabs");
                deliverySender.addGabsWarning(deliverySender.zeroWeight, "zeroWeight");

                // даем сообщение от зависимостей
                for (var i in dependens["message"])
                    deliverySender.addWarning("COMMON", dependens["message"][i]);

                // сообщение о том, как изменить вариант доставки
                if (deliverySender.showWarningChangeDelivery)
                    deliverySender.addWarning("GABS", "warningChangeDelivery");

                // далее пересчитываем дсотавку и рисуем форму с обновленными данными
                // проверяем доступные типы отгрузки, стоимость дост при смене габаритов, доступность доставки, ограничения на оценочную стоимость
                if (deliverySender.recalculate && !deliverySender.isOrderChange)
                {
                    deliverySender.getOrderCalculate(function ()
                    {
                        deliverySender.recalculate = false;
                        deliverySender.drawForm();
                        deliverySender.recalculate = true;
                    });
                }
            },

            getOrderCalculate: function (afterCalculateHandler)
            {
                var skipRecalculate = false;
                // не пересчитываем отправленные заказы
                if (typeof deliverySender.formElements.parcel_ID != "undefined")
                    if (typeof deliverySender.formElements.parcel_ID.value != "undefined")
                        if (deliverySender.formElements.parcel_ID.value && deliverySender.formElements.parcel_ID.value != "")
                            if (typeof afterCalculateHandler == "function")
                                skipRecalculate = true;

                // не пересчитываем заказы с неуказанной стоимостью доставки
                if (typeof deliverySender.formElements.yandexDelivery_price == "undefined")
                    skipRecalculate = true;
                else if (deliverySender.formElements.yandexDelivery_price.value == "")
                    skipRecalculate = true;

                if (skipRecalculate)
                {
                    afterCalculateHandler();
                    return;
                }

                var dataObject = deliverySender.prepareSaveSendData();

                dataObject["action"] = "calculateOrder";

                deliverySender.calculateError = false;

                deliverySender.doAjax(dataObject, function (data)
                {
                    console.log(data);

                    if (data.success)
                    {
                        data = data.data;

                        // определение доступных типов забора, возможности использования единого склада
                        deliverySender.formElements.yandexDelivery_type.value = {};
                        deliverySender.warehouseAvailable = null;

                        var deliveryTypeName = {
                            "import": "<?=GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_import")?>",
                            "withdraw": "<?=GetMessage("TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_withdraw")?>",
                        };

                        for (var i in deliveryTypeName)
                        {
                            if (data["is_ds_" + i + "_available"] || data["is_ff_" + i + "_available"])
                            {
                                deliverySender.formElements.yandexDelivery_type.value[i] = deliveryTypeName[i];

                                if (typeof deliverySender.warehouseAvailable == "undefined" || deliverySender.warehouseAvailable == null)
                                    deliverySender.warehouseAvailable = {};

                                if (typeof deliverySender.warehouseAvailable[i] == "undefined")
                                    deliverySender.warehouseAvailable[i] = [];

                                if (parseInt(data["is_ff_" + i + "_available"]))
                                    deliverySender.warehouseAvailable[i].push("YD");

                                if (parseInt(data["is_ds_" + i + "_available"]))
                                    deliverySender.warehouseAvailable[i].push("DS");
                            }
                        }

                        // проверяем выбран ли сейчас доступный вариант отгрузки
                        var lastAllowDeliveryType,
                            deliveryTypeFinded = false;
                        for (var i in deliverySender.formElements.yandexDelivery_type.value)
                        {
                            lastAllowDeliveryType = i;
                            if (deliverySender.formElements.yandexDelivery_type.selected == i)
                                deliveryTypeFinded = true;
                        }

                        if (!deliveryTypeFinded)
                            deliverySender.formElements.yandexDelivery_type.selected = lastAllowDeliveryType;

                        // определяемся с настройкой Использовать склад Яндекс.Доставки
                        deliverySender.checkWarehouses();

                        // ставим стоимость доставки
                        deliverySender.formElements.yandexDelivery_price.value = data.costWithRules;

                        // ставим дату отгрузки на самую раннюю, если она старше текущей установленной
                        deliverySender.yandexDeliveryTimeLimits = data.date_limits;
                        deliverySender.checkDeliveryTimeLimits();

                        // сохраняем товары с 0 весом и габаритами
                        deliverySender.zeroGabs = data.zeroGabs;
                        deliverySender.zeroWeight = data.zeroWeight;
                        deliverySender.totalWeightMoreDefault = data.totalWeightMoreDefault;
                        deliverySender.isZeroGabsWeight = data.isZeroGabsWeight;
                    }
                    else
                    {
                        console.log(data);
                        deliverySender.calculateError = true;
                    }

                    if (typeof afterCalculateHandler == "function")
                        afterCalculateHandler();
                });
            },

            addGabsWarning: function (input, messCode)
            {
                var isWarning = false,
                    // additionsText = "<div>";
                    additionsText = "";

                for (var i in input)
                {
                    isWarning = true;
                    additionsText += "<a class = 'adm-btn' target = '_blank' href = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" + input[i]["IBLOCK_ID"] + "&type=" + input[i]["IBLOCK_TYPE"] + "&ID=" + input[i]["ID"] + "'>" + input[i]["ID"] + "</a>";
                }

                if (isWarning)
                    deliverySender.addWarning("WARNINGS", messCode, "<div>" + additionsText + "</div>");
            },

            checkDeliveryTimeLimits: function ()
            {
                if (typeof deliverySender.yandexDeliveryTimeLimits == "undefined")
                    return;

                var curDeliveryDateVal = deliverySender.getCurFormValue("shipment_date");
                arCurDate = curDeliveryDateVal.split(".");
                to_yd_warehouse = (deliverySender.formElements.to_yd_warehouse.value == "Y") ? "ff" : "ds",
                    strMinDate = deliverySender.yandexDeliveryTimeLimits[deliverySender.formElements.yandexDelivery_type.selected][to_yd_warehouse],
                    arMinDate = strMinDate.split("."),
                    curDate = new Date(arCurDate[2], arCurDate[1], arCurDate[0]),
                    minDate = new Date(arMinDate[2], arMinDate[1], arMinDate[0]);

                if (curDate < minDate)
                    deliverySender.formElements.shipment_date.value = strMinDate;
            },

            // определяемся с настройкой Использовать склад Яндекс.Доставки
            checkWarehouses: function ()
            {
                // deliverySender.warehouseAvailable = {
                // "import": ["YD"],
                // "withdraw": ["DS"],
                // };
                if (typeof deliverySender.warehouseAvailable == "undefined" || deliverySender.warehouseAvailable == null)
                {
                    deliverySender.calculateError = true;
                    return;
                }

                var selectedDeliveryType = deliverySender.formElements.yandexDelivery_type.selected;
                if (deliverySender.warehouseAvailable[selectedDeliveryType].length >= 2)
                    deliverySender.formElements.to_yd_warehouse.disabled = false;
                else
                {
                    if (deliverySender.warehouseAvailable[selectedDeliveryType].length <= 0)
                        deliverySender.calculateError = true;
                    else
                    {
                        deliverySender.formElements.to_yd_warehouse.disabled = true;

                        for (var i in deliverySender.warehouseAvailable[selectedDeliveryType])
                        {
                            if (deliverySender.warehouseAvailable[selectedDeliveryType][i] == "YD")
                                deliverySender.formElements.to_yd_warehouse.value = "Y";

                            if (deliverySender.warehouseAvailable[selectedDeliveryType][i] == "DS")
                                deliverySender.formElements.to_yd_warehouse.value = "N";
                        }
                    }
                }

                var status = deliverySender.getCurValue("STATUS");

                if (!deliverySender.inStatus(status, ["NEW"]))// статус не NEW, единый склад надо блочить
                    deliverySender.formElements.to_yd_warehouse.disabled = true;
            },

            setLangValues: function ()
            {

                deliverySender.formElements.status_info.value = deliverySender.arLangs["status_name"][deliverySender.getCurValue("STATUS")];
            },

            // возвращает содержимое формы
            getFormHTML: function ()
            {
                // устанавливаем visible
                deliverySender.checkVisbility();

                // экранируем слеши в данных виджета
                try
                {
                    if (deliverySender.tmpOrderConfirm.widgetData.address)
                        if (deliverySender.tmpOrderConfirm.widgetData.address.comment != null)
                            deliverySender.tmpOrderConfirm.widgetData.address.comment = deliverySender.tmpOrderConfirm.widgetData.address.comment.replace(/\\?("|')/g, '\\$1');
                } catch (err)
                {
                }

                return deliverySender.putFormInputs();
            },

            Dialog: false,
            ShowDialog: function ()
            {
                if (!deliverySender.Dialog)
                {
                    var html = $('#TRADE_YANDEX_DELIVERY_wndOrder').parent().html();
                    $('#TRADE_YANDEX_DELIVERY_wndOrder').parent().remove();

                    // формируем кнопки
                    var buttons = [];
                    for (var i in deliverySender.dialogButtons)
                    {
                        deliverySender.dialogButtons[i].visible = true;

                        var but = "";
                        but += '<input data-buttonType = "' + i + '" type=\"button\" ';

                        if (typeof deliverySender.dialogButtons[i].data == "object")
                            for (var k in deliverySender.dialogButtons[i].data)
                            {
                                but += "data-" + k;
                                if (typeof deliverySender.dialogButtons[i].data[k] != "undefined" &&
                                    deliverySender.dialogButtons[i].data[k] != null
                                )
                                    but += "='" + deliverySender.dialogButtons[i].data[k] + "'";

                                but += " ";
                            }

                        but += 'value=\"' + deliverySender.dialogButtons[i].value + '\"';

                        if (typeof deliverySender.dialogButtons[i].onclick != "undefined")
                            but += 'onclick=\"' + deliverySender.dialogButtons[i].onclick + '"';

                        but += '/>';

                        buttons.push(but);
                    }

                    // содержимое окна
                    var html = "";
                    html += "<table class = 'TRADE_YANDEX_DELIVERY_table_form' id = 'TRADE_YANDEX_DELIVERY_table_form'>";
                    html += "</table>";

                    deliverySender.Dialog = new BX.CDialog({
                        title: "<?=GetMessage('TRADE_YANDEX_DELIVERY_JSC_SOD_WNDTITLE')?>",
                        content: html,
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '800',
                        width: '800',
                        buttons: buttons
                    });

                    deliverySender.Dialog.Show();

                    if (!deliverySender.isAdmin)
                        $(".bx-core-adm-dialog-buttons").prepend("<div><?=GetMessage("TRADE_YANDEX_DELIVERY_JSC_SOD_RIGHT_NOT_ALLOW")?></div>");
                }
                else
                    deliverySender.Dialog.Show();

                deliverySender.drawForm();
            },

            // проверка есть ли статус в массиве
            inStatus: function (status, arStatuses)
            {
                for (var i in arStatuses)
                    if (status == arStatuses[i])
                        return true;

                return false;
            },

            // определяем видимость кнопок формы и возможность редактирования элементов формы по статусу
            checkButtons: function (showButton)
            {
                // скрываем/отображаем кнопки
                for (var i in deliverySender.dialogButtons)
                {
                    deliverySender.dialogButtons[i].visible = false;
                    for (var k in showButton)
                        if (i == showButton[k])
                            deliverySender.dialogButtons[i].visible = true;

                    // если ошибка расчета, скрываем кнопки работы с заказом
                    if (deliverySender.calculateError)
                        deliverySender.dialogButtons[i].visible = false;

                    var tmpButton = $("[data-buttonType='" + i + "']");
                    if (deliverySender.dialogButtons[i].visible)
                        tmpButton.show();
                    else
                        tmpButton.hide();

                    // деактивируем кнопку для прав ниже "Запись"
                    if (!deliverySender.isAdmin)
                        if (deliverySender.adminButtons[i])
                            tmpButton.attr("disabled", true);
                        else
                            tmpButton.attr("disabled", false);
                }
            },

            // возможность редактирования полей формы
            checkEditable: function (editable)
            {
                if (!editable)
                    for (var i in deliverySender.formElements)
                        deliverySender.formElements[i].disabled = true;
                else
                    for (var i in deliverySender.formElements)
                        if (
                            (
                                deliverySender.formElements[i].type != "textarea" &&
                                i != "is_payed"
                            )
                            &&
                            (
                                (typeof deliverySender.formElements[i].setInOptions != "undefined" &&
                                    deliverySender.formElements[i].setInOptions == true) ||
                                typeof deliverySender.formElements[i].setInOptions == "undefined"
                            )
                        )
                            deliverySender.formElements[i].disabled = false;

                // проверка на возможность редактирования привоза на единый склад
                if (typeof deliverySender.warehouseAvailable != "undefined")
                    deliverySender.checkWarehouses();
            },

            // собираем данные на форме и обновляем значения полей в объекте, если на входе объект, то выставляем данные из него
            getFormData: function (changeVals)
            {
                var formData = {},
                    formInputs = deliverySender.formElements;

                for (var i in formInputs)
                {
                    var curVal, curObj;

                    switch (formInputs[i].type)
                    {
                        case "select":
                            curObj = $("#delivery_input_" + i).find(":checked");
                            curVal = curObj.val();

                            if (typeof changeVals != "undefined" && typeof changeVals[i] != "undefined")
                            {
                                curVal = changeVals[i];
                                curObj.val(curVal);
                            }

                            deliverySender.formElements[i].selected = curVal;
                            break;

                        case "checkbox":
                            curObj = $("#delivery_input_" + i);

                            var checked = curObj.prop("checked");

                            if (checked)
                                curVal = "Y";
                            else
                                curVal = "N";

                            if (typeof changeVals != "undefined" && typeof changeVals[i] != "undefined")
                                if (changeVals[i] == false || changeVals[i] == null || changeVals[i] == "N")
                                    curVal = "N";
                                else
                                    curVal = "Y";

                            deliverySender.formElements[i].value = curVal;

                            curObj.val(curVal);
                            if ("Y" == curVal)
                                curObj.prop("checked", true);
                            else
                                curObj.prop("checked", false);
                            break;

                        default:
                            curObj = $("#delivery_input_" + i);

                            var data = curObj.attr("data-trade_yandex_delivery_data");

                            if (typeof data != "undefined")
                            {
                                curVal = data;

                                var tmpValue;

                                if (typeof changeVals != "undefined" && typeof changeVals[i] != "undefined")
                                {
                                    curVal = changeVals[i];
                                    curObj.attr("data-trade_yandex_delivery_data", curVal);

                                    if (typeof deliverySender.arLangs[i] != "undefined" && typeof deliverySender.arLangs[i][curVal] != "undefined")
                                        curObj.html(deliverySender.arLangs[i][curVal]);
                                    else
                                        curObj.html(curVal);
                                }

                                deliverySender.formElements[i].data = curVal;
                                if (typeof deliverySender.arLangs[i] != "undefined" && typeof deliverySender.arLangs[i][curVal] != "undefined")
                                    deliverySender.formElements[i].value = deliverySender.arLangs[i][curVal];
                                else
                                    deliverySender.formElements[i].value = curVal;
                            }
                            else
                            {
                                if (formInputs[i].type == "label")
                                    curVal = curObj.html();
                                else
                                    curVal = curObj.val();

                                if (typeof formInputs[i].href != "undefined" && formInputs[i].type == "label")
                                {
                                    curVal = curVal.match(/>(.*)?</);
                                    curVal = curVal[1];

                                    if (typeof curVal == "undefined")
                                        curVal = "";
                                }

                                if (typeof changeVals != "undefined" && typeof changeVals[i] != "undefined")
                                    curVal = changeVals[i];

                                deliverySender.formElements[i].value = curVal;

                                if (typeof changeVals != "undefined" && typeof changeVals[i] != "undefined")
                                    if (formInputs[i].type == "label")
                                        curObj.html(curVal);
                                    else
                                        curObj.val(curVal);
                            }

                            break;
                    }

                    // значение отправляется в запросе, добавляем в ответ
                    if (formInputs[i].sended && formInputs[i].visible != false)
                        formData[i] = curVal;
                }

                return formData;
            },

            checkRequireFields: function (formData)
            {
                var reqFields = ["fname", "lname", "street", "house", "phone", "delivery_name", "profile_name", "warehouseConfigNum"],
                    needToFill = "";

                if (deliverySender.formElements.profile_name.data == "POST")
                {
                    reqFields.push("index", "mname");
                }

                for (var i in reqFields)
                    if (typeof formData[reqFields[i]] != "undefined" && formData[reqFields[i]] == "")
                    {
                        if (needToFill.length > 0)
                            needToFill += ", ";

                        needToFill += deliverySender.formElements[reqFields[i]].name;
                    }

                if (needToFill.length > 0)
                {
                    confirm("<?=GetMessage("TRADE_YANDEX_DELIVERY_FILL_REQ")?>" + needToFill);
                    return false;
                }
                else
                    return true;
            },

            // готовим данные формы для сохранения для отправке или сохранения
            prepareSaveSendData: function ()
            {
                var dataObject = {};
                dataObject["data"] = deliverySender.tmpOrderConfirm;

                if (deliverySender.formOpened)
                    var formData = deliverySender.getFormData();

                dataObject["data"]["formData"] = formData;
                dataObject["data"]["formDataJSON"] = JSON.stringify(dataObject["data"]["formData"]);
                dataObject["data"]["widgetDataJSON"] = JSON.stringify(dataObject["data"]["widgetData"]);

                return dataObject;
            },

            // сохранение данных формы
            saveFormData: function ()
            {
                var dataObject = deliverySender.prepareSaveSendData();
                if (dataObject == false)
                    return false;

                // если пересчитывали стоимость доставки, то надо снять флаг изменения заказа
                deliverySender.dropOrderChange();

                dataObject["action"] = "saveFormData";

                deliverySender.doAjax(dataObject, function (data)
                {
                    // console.log(data);
                    if (data.success)
                    {
                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_SAVE_FORM_DATA_SUCCESS')?>");
                    }
                    else
                    {
                        console.log(data);
                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_SAVE_FORM_DATA_ERROR')?>\n\n" + data.data.code);
                    }
                });
            },

            // отправка заявки
            sendOrder: function (addAction)
            {
                var dataObject = deliverySender.prepareSaveSendData();
                if (dataObject == false)
                    return false;

                // если пересчитывали стоимость доставки, то надо снять флаг изменения заказа
                deliverySender.dropOrderChange();

                if (!deliverySender.checkRequireFields(dataObject["data"]["formData"]))
                    return false;

                dataObject["action"] = "sendOrder";
                if (addAction)
                    dataObject["perform_actions"] = addAction;

                deliverySender.doAjax(dataObject, function (data)
                {
                    if (data.success)
                    {
                        if (typeof data.data.sendDraft != "undefined")
                        {
                            var sendDraft = data.data.sendDraft.data;
                            deliverySender.formElements.delivery_ID.value = sendDraft.order.id;
                        }

                        deliverySender.tmpOrderConfirm.savedParams["STATUS"] = data.data.STATUS;
                        deliverySender.formElements.STATUS.value = data.data.STATUS;

                        if (typeof data.data.confirmOrder != "undefined")
                        {
                            var confirmOrder = data.data.confirmOrder.data.result.success[0];

                            deliverySender.formElements.parcel_ID.value = confirmOrder.parcel_id;
                        }

                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_SEND_SUCCESS')?>");

                        // обновляем форму после отправки
                        deliverySender.drawForm();
                    }
                    else
                    {
                        console.log(data);
                        var str = "";

                        try
                        {
                            for (var i in data.data.data.result.data.errors)
                                str += i + ": " + data.data.data.result.data.errors[i] + "\n";
                        } catch (err)
                        {
                            // console.log(err);
                            // console.log("Error in data.data.data.result.data.errors not found");
                        }

                        try
                        {
                            for (var i in data.data.data.result.data.result.error)
                                for (var k in data.data.data.result.data.result.error[i])
                                    str += k + ": " + data.data.data.result.data.result.error[i][k] + "\n";
                        } catch (err)
                        {
                            // console.log(err);
                            // console.log("Error in data.data.data.result.data.result.error not found");
                        }

                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_SEND_ERROR')?>\n\n" + str);
                    }
                });
            },

            // отмена заказа
            cancelOrder: function ()
            {
                if (!confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_CANCEL_CONFIRM')?>"))
                    return false;

                var status = deliverySender.getStatusGroup();
				<?/*if (status != 1)
                {
                    confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_CANCEL_CANT_PERFORM')?>");
                    return false;
                }*/?>

                var dataObject = {};
                dataObject["action"] = "cancelOrder";

                deliverySender.doAjax(dataObject, function (data)
                {
                    if (data.success)
                    {
                        deliverySender.tmpOrderConfirm.savedParams["STATUS"] = data.data.STATUS;
                        deliverySender.formElements.STATUS.value = data.data.STATUS;
                        deliverySender.formElements.delivery_ID.value = "";
                        deliverySender.tmpOrderConfirm.savedParams["delivery_ID"] = "";
                        deliverySender.formElements.parcel_ID.value = "";
                        deliverySender.tmpOrderConfirm.savedParams["parcel_ID"] = "";

                        // deliverySender.isOrderChange = false;

                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_CANCEL_SUCCESS')?>");

                        deliverySender.drawForm();
                    }
                    else
                    {
                        console.log(data);
                        var str = "";

                        str += data.data.data.order_id;
                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_SEND_ERROR')?>\n\n" + str);
                    }
                });
            },

            // печать документов о заказе
            printDocs: function ()
            {
                var dataObject = {};
                dataObject["action"] = "getOrderDocuments";

                deliverySender.doAjax(dataObject, function (data)
                {
                    if (data.success)
                    {
                        for (var i in data.data)
                            window.open(data.data[i]);
                    }
                    else
                    {
                        // console.log(data);
                        // var str = "";

                        // for (var i in data.data.data)
                        // str += data.data.data[i] + "\n";

                        // if (str != "")
                        // confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_SEND_ERROR')?>\n\n" + str);
                        // else
                        confirm("<?=GetMessage('TRADE_YANDEX_DELIVERY_DOCUMENT_NOT_READY_YET')?>");
                    }
                });
            },

            doAjax: function (ajaxData, callback)
            {
                ajaxData["ORDER_ID"] = deliverySender.getCurValue("ORDER_ID");
                ajaxData["sessid"] = BX.bitrix_sessid();

                $.ajax({
                    url: "/bitrix/js/<?=CDeliveryYaDriver::$MODULE_ID?>/ajax.php",
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
                        // console.log(data);
                        if (typeof callback == "function")
                            callback(data);

                        return;
                    },
                });

                return;
            },

            // снимает флаг изменения заказа
            dropOrderChange: function ()
            {
                deliverySender.isOrderChange = false;

                deliverySender.doAjax({"action": "deleteOrderFromChange"}, function (data)
                {
                    // console.log(data);
                });
            },

            widgetReady: false,
            initWidget: function ()
            {
                deliverySender.getFormData();
                if (deliverySender.widgetReady)
                    ydwidget.cartWidget.open();
                else
                    $(document).on("ydwidget_cartWidget_onLoad", function ()
                    {
                        setTimeout(ydwidget.cartWidget.open(), 1000);
                    });
            },

            createAddress: function (delivery)
            {
                var address = '';

                if (delivery.type == "PICKUP")
                {
                    // адрес для самовывоза
                    address = '<?=GetMessage('TRADE_YANDEX_DELIVERY_JS_PICKUP')?>: ';
                    address += delivery.full_address + ' | ';
                    address += delivery.days + ' <?=GetMessage('TRADE_YANDEX_DELIVERY_JS_DAY')?> | ';
                    address += delivery.costWithRules + ' <?=GetMessage('TRADE_YANDEX_DELIVERY_JS_RUB')?>';
                    address += ' #' + delivery.pickuppointId;
                }

                // console.log(delivery);
                // Убираем этот код, дабы не было подмены адреса при выборе почты
                /*if (delivery.type == "POST")
                 {
                 address = {};
                 var autoComplitAddr = ydwidget.cartWidget.getAddress();
                 // console.log({"ydwidget.cartWidget.getAddress": autoComplitAddr});

                 if (typeof autoComplitAddr != "undefined" && autoComplitAddr != null)
                 {
                 var addr = autoComplitAddr["index"];
                 addr += ", " + autoComplitAddr["city"];
                 addr += ", " + autoComplitAddr["street"];
                 addr += ", " + autoComplitAddr["house"];

                 if (typeof autoComplitAddr["building"] != "undefined" && autoComplitAddr["building"] != null)
                 addr += ", " + autoComplitAddr["building"];

                 address["address"] = addr;

                 address["index"] = autoComplitAddr["index"];
                 address["street"] = autoComplitAddr["street"];
                 address["house"] = autoComplitAddr["house"];
                 if (typeof autoComplitAddr["building"] != "undefined" && autoComplitAddr["building"] != null)
                 address["build"] = autoComplitAddr["building"];
                 if (typeof autoComplitAddr["apartment"] != "undefined" && autoComplitAddr["apartment"] != null)
                 address["flat"] = autoComplitAddr["apartment"];
                 }
                 }*/


                return address;
            },

            getDeliveryTerm: function (min, max)
            {
                if (typeof (min) == "undefined" && typeof(max) == "undefined")
                    return "";

                if (typeof(min) == "undefined")
                    return max;

                if (typeof(max) == "undefined")
                    return min;

                if (min == max)
                    return min;

                return min + " - " + max;
            },

            dimensionsChange: function ()
            {
                deliverySender.dimensionsChanged = true;
                ydwidget.cartWidget.setDeliveryVariant(null);
                deliverySender.getFormData();
                deliverySender.drawForm();
            },

            getWeight: function ()
            {
                return deliverySender.getCurValue("WEIGHT");
            },

            getDimensions: function ()
            {
                return [[
                    deliverySender.getCurValue("WIDTH"),
                    deliverySender.getCurValue("LENGTH"),
                    deliverySender.getCurValue("HEIGHT"),
                    1
                ]];
            }
        };

    deliverySender.onLoad();

    $(document).ready(function ()
    {
        ydwidget.ready(function ()
        {
            yd$('body').prepend('<div id="ydwidget" class="yd-widget-modal"></div>');

            ydwidget.initCartWidget({
                //получить указанный пользователем город
                'getCity': function ()
                {
                    var city = deliverySender.formElements.city.value;

                    if (ydwidget.currentCity)
                        city = ydwidget.currentCity;

                    if (city)
                        return {value: city};
                    else
                        return false;
                },

                //id элемента-контейнера
                'el': 'ydwidget',

                'itemsDimensions': function ()
                {
                    return deliverySender.getDimensions();
                },

                //общий вес товаров в корзине
                'weight': function ()
                {
                    return deliverySender.getWeight();
                },

                //общая стоимость товаров в корзине
                'cost': function ()
                {
                    return <?=CDeliveryYaDriver::$tmpOrderDimension["PRICE"]?>;
                },

                //общее количество товаров в корзине
                'totalItemsQuantity': function ()
                {
                    return 1;
                },

                'assessed_value': <?=CDeliveryYaDriver::$tmpOrderDimension["PRICE"]?>,

                'order': {
                    //имя, фамилия, телефон, улица, дом, индекс
                    'recipient_first_name': function ()
                    {
                        return yd$('#yd_fname').val()
                    },
                    'recipient_last_name': function ()
                    {
                        return yd$('#yd_lname').val()
                    },
                    'recipient_phone': function ()
                    {
                        return yd$('#yd_phone').val()
                    },
                    'deliverypoint_street': function ()
                    {
                        return yd$('#yd_street').val()
                    },
                    'deliverypoint_house': function ()
                    {
                        return yd$('#yd_house').val()
                    },
                    'deliverypoint_index': function ()
                    {
                        return yd$('#yd_index').val()
                    },

                    //объявленная ценность заказа
                    'order_assessed_value': <?=CDeliveryYaDriver::$tmpOrderDimension["PRICE"]?>,
                    //флаг отправки заказа через единый склад.
                    'delivery_to_yd_warehouse': (deliverySender.formElements.to_yd_warehouse.value == "Y") ? 1 : 0,
                    //товарные позиции в заказе
                },

                'onLoad': function ()
                {
                    deliverySender.widgetReady = true;
                    $(document).trigger("ydwidget_cartWidget_onLoad");
                    return false;
                },

                'onDeliveryChange': function (delivery)
                {
                    if (delivery == null)
                        return;

//                     console.log(delivery);
                    // скидываем флаг изменения заказа, но сохранится это только после сохранения/отправки формы
                    deliverySender.isOrderChange = false;

                    if (delivery.type != "POST")
                        delivery.yandexDeliveryCity = ydwidget.city.value;

                    var arChangeDelivery = {
                        "delivery_name": delivery.delivery.unique_name,
                        "profile_name": delivery.type,
                        "delivery_price": delivery.costWithRules,
                        "delivery_terms": deliverySender.getDeliveryTerm(delivery.minDays, delivery.maxDays)
                    };

                    if (delivery.type == "PICKUP")
                    {
                        // заменяем в комментарии кавычки иначе не переводятся данные нормально в json
                        if (typeof delivery.address != "undefined")
                            if (typeof delivery.address.comment != "undefined" && delivery.address.comment != null)
                                delivery.address.comment = delivery.address.comment.replace(/\\?("|')/g, '\\$1');

                        arChangeDelivery["address"] = deliverySender.createAddress(delivery);
                    }
                    else if (delivery.type == "POST")
                    {
                        var postChange = deliverySender.createAddress({
                            "type": delivery.type
                        });

                        for (var i in postChange)
                            arChangeDelivery[i] = postChange[i];

                    }

                    // сохраняем в объекте и на форме данные, заменяем из ответа виджета нужные
                    deliverySender.getFormData(arChangeDelivery);

                    // сохраняем данные виджета
                    deliverySender.tmpOrderConfirm.widgetData = delivery;

                    // перерисовываем форму
                    deliverySender.drawForm();

                    // закрыли виджет
                    ydwidget.cartWidget.close();

                    return false;
                },

                //создавать заказ в cookie для его последующего создания в Яндекс.Доставке только если выбрана доставка Яндекса
                'createOrderFlag': function ()
                {
                    return false;
                },

                //запустить сабмит формы, когда валидация успешно прошла и заказ создан в cookie,
                //либо если createOrderFlag вернул false
                'runOrderCreation': function ()
                {
                    return false;
                },

                'onlyDeliveryTypes': function ()
                {
                    return ["pickup", "post", "todoor"];
                }
            });
			
			<?if ($_GET["yandexDeliveryOpenSendForm"] == "Y"){?>
            deliverySender.ShowDialog();
			<?}?>
        });
    });
</script>