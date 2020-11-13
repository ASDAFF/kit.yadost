<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (empty($arResult["ERRORS"]))
{
if(CDeliveryYaHelper::isConverted())
{
	$dTS = Bitrix\Sale\Delivery\Services\Table::getList(array(
		 'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC'),
		 'filter' => array('CODE' => 'yandexDelivery:%')
	));
	
	while($dataShip = $dTS->Fetch())
	{
		$profileName = preg_replace("/yandexDelivery:/", "", $dataShip["CODE"]);
		$htmlIDs[$profileName] = 'ID_DELIVERY_ID_'.$dataShip['ID'];
		$deliveryIDs[$profileName] = $dataShip['ID'];
	}
}
else
{
	$htmlIDs = array(
		"pickup" => 'ID_DELIVERY_yandexDelivery_pickup',
		"courier" => 'ID_DELIVERY_yandexDelivery_courier',
		"post" => 'ID_DELIVERY_yandexDelivery_post'
	);
	$deliveryIDs = array(
		"pickup" => "yandexDelivery:pickup",
		"courier" => "yandexDelivery:courier",
		"post" => "yandexDelivery:post"
	);
}


	
$dimensionStr = "[";

$dimensionStr .= $arResult["ORDER_DIMENSIONS"]["WIDTH"].", ";
$dimensionStr .= $arResult["ORDER_DIMENSIONS"]["LENGTH"].", ";
$dimensionStr .= $arResult["ORDER_DIMENSIONS"]["HEIGHT"].", ";
$dimensionStr .= 1;

$dimensionStr .= "]";// передаем в виджет один товар с итоговыми габаритами посылки

$arAddrInputs = array();
foreach ($arResult["ADDRESS_FIELDS"] as $personType => $arAdr)
	foreach ($arAdr as $propName => $propID)
		$arAddrInputs[$personType][$propName] = $propID;

$GLOBALS['APPLICATION']->AddHeadString(COption::GetOptionString("yandex.delivery", "basWidjet"));
$showWidgetOnProfileClick = ("Y" == COption::GetOptionString("yandex.delivery", "showWidgetOnProfile", "N"))?true:false;
?>

<script type="text/javascript">
ydwidget.ready(function () {
	yd$('body').prepend('<div id="ydwidget" class="yd-widget-modal"></div>');
	
	// выполняется при первой загрузке виджета
	ydwidget.trade_onLoad = function()
	{
		ydwidget.trade_pvzAddressFull = "";
		// ydwidget.trade_pvzAddressBlocked = false;
		ydwidget.trade_orderForm = "#ORDER_FORM";
		ydwidget.trade_oldTemplate = false;
		ydwidget.trade_startHTML = false;
		
		
		ydwidget.trade_addressInputs = <?=CUtil::PHPToJSObject($arAddrInputs)?>;
		
		ydwidget.trade_showWidgetOnClick = <?=CUtil::PHPToJSObject($showWidgetOnProfileClick)?>;
		
		ydwidget.trade_htmlIDs = <?=CUtil::PHPToJSObject($htmlIDs)?>;
		ydwidget.trade_deliveryIDs = <?=CUtil::PHPToJSObject($deliveryIDs)?>;
		ydwidget.trade_deliveryPrice = {};
		
		ydwidget.trade_openWidgetTitles = {
			"courier": "<?=GetMessage('delivery_JS_select_courier')?>",
			"post": "<?=GetMessage('delivery_JS_select_post')?>",
			"pickup": "<?=GetMessage('delivery_JS_select_pickup')?>",
		};
		
		if (yd$(ydwidget.trade_orderForm).length > 0)
			ydwidget.trade_oldTemplate = true;
		else
			ydwidget.trade_orderForm = "#bx-soa-order-form";
		
		
		if (typeof ydwidget.trade_currentCity == "undefined")
			ydwidget.trade_currentCity = '<?=$arResult["CITY_NAME"]?>';
		
		ydwidget.trade_selectPickupBtn = {};
		ydwidget.trade_deliveryDataSaved = {};
		
		// определяем выбрана ли сейчас delivery и заодно формируем кнопки выбрать ПВЗ для каждого профиля
		for (var key in ydwidget.trade_htmlIDs)
		{
			var profileKey = ydwidget.trade_getTariffAccordingKey(key);
			ydwidget.trade_selectPickupBtn[key] = '<a href="javascript:void(0);" data-ydwidget-open data-ydwidget-profile = "'+ key +'" onclick="ydwidget.trade_openWidget(\''+ profileKey +'\');">'+ydwidget.trade_openWidgetTitles[key]+'</a>';
			
			var deliveryRadio =	yd$("#"+ydwidget.trade_htmlIDs[key]);
			if (typeof deliveryRadio != "undefined")
				if (deliveryRadio.length > 0)
					if (ydwidget.trade_oldTemplate)
					{
						if (deliveryRadio.attr("checked") == "checked")
							ydwidget.trade_currentdelivery = ydwidget.trade_deliveryIDs[key];
					}
					else
					{
						if (deliveryRadio.prop("checked"))
							ydwidget.trade_currentdelivery = ydwidget.trade_deliveryIDs[key];
					}
		};
		
		// навешиваем обработчик на отправку формы
		ydwidget.trade_onSubmitForm();
		
		// переопределяем функцию обновления формы, чтобы сохранять адрес перед отправкой и запретить оф заказа, если не указан для профиля вариант в виджете
		if (!ydwidget.trade_oldTemplate)
		{
			BX.Sale.OrderAjaxComponent.trade_oldSendRequest = BX.Sale.OrderAjaxComponent.sendRequest;
			
			BX.Sale.OrderAjaxComponent.sendRequest = function (action, actionData)
			{
				if (!ydwidget.cartWidget.isOpened)
					ydwidget.trade_beforeSubmitAddress = ydwidget.trade_getAddressInput();
				
				if (action == "saveOrderAjax" && !ydwidget.trade_checkOrderCreate())
					ydwidget.trade_denieOrderCreate();
				else
					BX.Sale.OrderAjaxComponent.trade_oldSendRequest(action, actionData);
			}
		}
		
		// навешиваем обработчики на открытие блока доставок
		if (!ydwidget.trade_oldTemplate)
		{
			yd$('#bx-soa-delivery .bx-soa-section-title-container').on('click',function(){ydwidget.trade_initJS();});
			yd$('#bx-soa-delivery .bx-soa-section-title-container a').on('click',function(){ydwidget.trade_initJS();});
		}
		
		// запускаем скрипты обновления формы
		ydwidget.trade_initJS();
		
		// ==== подписываемся на перезагрузку формы
		if(typeof(BX) && BX.addCustomEvent)
			BX.addCustomEvent('onAjaxSuccess', ydwidget.trade_initJS);
		
		// Для старого JS-ядра
		if (window.jsAjaxUtil) // Переопределение Ajax-завершающей функции для навешивания js-событий новым эл-там
		{
			jsAjaxUtil._CloseLocalWaitWindow = jsAjaxUtil.CloseLocalWaitWindow;
			jsAjaxUtil.CloseLocalWaitWindow = function (TID, cont)
			{
				jsAjaxUtil._CloseLocalWaitWindow(TID, cont);
				ydwidget.trade_initJS();
			}
		}
	};
	
	// открывает виджет с нужным профилем
	ydwidget.trade_openWidget = function(profile)
	{
		// удаляем текущий выбранный вариант доставки в виджете и данные с формы, если это почта
		// if (profile == "post")
		// {
			// ydwidget.cartWidget.setDeliveryVariant(null);
			// yd$("#yd_deliveryData").val("");
		// }
		
		ydwidget.trade_saveAddressData(profile);
		//yd$(this).data("data-ydwidget-open", true);
		ydwidget.trade_onlyDeliveryTypes = [profile];
		ydwidget.cartWidget.changeDeliveryTypes();
		// ydwidget.cartWidget.open();
		return false;
	}
	
	// сохраняем адрес указанный, чтобы его вернуть, если выбрали иной способ доставки
	ydwidget.trade_saveAddressData = function(profile)
	{
		if (typeof profile == "undefined")
			profile = false;
		
		var addressValue = ydwidget.trade_getAddressInput();
		
		if (!ydwidget.trade_savedAddress)
			ydwidget.trade_savedAddress = addressValue;
		
		// тут тонкий момент, либо в виджете открывают самовывоз, либо был не самовывоз в форме и она обновилась UPDATE_STATE, тогда сохраняем адрес
		if (typeof ydwidget.trade_chosenDeliveryType != "undefined")
			if (ydwidget.trade_chosenDeliveryType != "pickup" && profile == "pickup")
				ydwidget.trade_savedAddress = addressValue;
			else if (profile == false && ydwidget.trade_chosenDeliveryType != "pickup" && !ydwidget.cartWidget.isOpened)
			{
				if (ydwidget.trade_oldTemplate)
					ydwidget.trade_savedAddress = ydwidget.trade_beforeSubmitAddress;
			}
	}
	
	// получение соответсвий по названиям тарифов
	ydwidget.trade_getTariffAccording = function()
	{
		return {
			"TODOOR": "courier",
			"POST": "post",
			"PICKUP": "pickup"
		};
	}
	
	// получение соответсвий по названиям блока адреса
	ydwidget.trade_getAddressAccording = function()
	{
		return {
			"index": "index",
			"street": "street",
			"house": "house",
			"building": "build",
			"apartment": "flat"
		};
	}
	
	// отдает по профилю битрикс название профиля для ЯД
	ydwidget.trade_getTariffAccordingKey = function(key)
	{
		var according = ydwidget.trade_getTariffAccording();
		
		for(var i in according)
			if (according[i] == key)
				return i.toLowerCase();
			
		return false;
	}
	
	// проверка выбран ли профиль ЯД
	ydwidget.trade_checkCurrentDelivery = function()
	{
		for (var key in ydwidget.trade_htmlIDs)
			if (ydwidget.trade_currentdelivery == ydwidget.trade_deliveryIDs[key])
				return key;
		
		return false;
	}
	
	// получение значения поля на форме при аякс обновлении
	ydwidget.trade_getDataFromAjax = function(inputName, returnType)
	{
		var input = false,
			tmpInput = false;
			
		tmpInput = yd$('#'+inputName);
		if(tmpInput.length > 0 )
			input = tmpInput;
		
		tmpInput = yd$('[name='+inputName+']');
		if(tmpInput.length > 0 )
			input = tmpInput;
		
		if (input)
			if (returnType == "value")
				return input.val();
			else
				return input;
		
		return false;
	}
	
	// ставим подпись для открытия виджета и адрес выбранный
	ydwidget.trade_setTariffInfo = function()
	{
		var addrHTML = "";
		
		if (ydwidget.trade_oldTemplate)
		{
			for (var key in ydwidget.trade_selectPickupTag)
			{
				if (ydwidget.trade_selectPickupTag[key])
				{
					if (typeof ydwidget.trade_pvzAddress[key] != "undefined" && ydwidget.trade_pvzAddress[key] != "undefined")
						addrHTML += ydwidget.trade_pvzAddress[key];
					
					if (ydwidget.trade_selectPickupTag[key])
						if (!ydwidget.trade_selectPickupTag[key].data("ydButtonSet"))
						{
							if (yd$("#trade_pvz_address_block").length <= 0)
							{
								addrHTML = "<span id = 'trade_pvz_address_block'>" + addrHTML + "</span>";
								ydwidget.trade_selectPickupTag[key].before(addrHTML);
							}
							else
								yd$("#trade_pvz_address_block").html(addrHTML);
							
							ydwidget.trade_selectPickupTag[key].append(ydwidget.trade_selectPickupBtn[key]);
							ydwidget.trade_selectPickupTag[key].data("ydButtonSet", true);
						}
				}
			}
		}
		else
		{
			var key = ydwidget.trade_checkCurrentDelivery();
				
			if (typeof ydwidget.trade_pvzAddress[key] != "undefined" && ydwidget.trade_pvzAddress[key] != "undefined")
				addrHTML = ydwidget.trade_pvzAddress[key];
			
			if (ydwidget.trade_selectPickupTag[key])
			{	
				if (yd$("#trade_pvz_address_block").length <= 0)
				{
					addrHTML = "<span id = 'trade_pvz_address_block'>" + addrHTML + "</span>";
					ydwidget.trade_selectPickupTag[key].before(addrHTML);
				}
				else
					yd$("#trade_pvz_address_block").html(addrHTML);
				
				ydwidget.trade_selectPickupTag[key].html(ydwidget.trade_selectPickupBtn[key]);
			}
		}
	};
	
	// подписка на отправку формы, не даем оф заказ с профилем яд и не указанным вариантом в виджете
	ydwidget.trade_onSubmitForm = function()
	{
		// не даем отправить форму, если выбрана ddelivery и не заполнены данные dataSave
		yd$(ydwidget.trade_orderForm).on("submit", function(e){
			// сохраняем адрес перед отправкой формы
			if (!ydwidget.cartWidget.isOpened)
				ydwidget.trade_beforeSubmitAddress = ydwidget.trade_getAddressInput();
			
			// console.log({
				// "checkDel": ydwidget.trade_checkCurrentDelivery(),
				// "dataSave": yd$("#yd_deliveryData").val(),
				// "oldTemplate": ydwidget.trade_oldTemplate,
				
			// });
			
			if (!ydwidget.trade_checkOrderCreate())
				ydwidget.trade_denieOrderCreate(e);
			
			return true;
		});
	}
	
	// проверка на возможность создания заказа
	ydwidget.trade_checkOrderCreate = function()
	{
		if (ydwidget.trade_checkCurrentDelivery())
		{
			var dataSave = yd$("#yd_deliveryData").val(),
				confirmorder = yd$("#confirmorder").val();
			
			if (ydwidget.trade_oldTemplate)
			{
				if ((typeof dataSave == "undefined" || dataSave == "false") && confirmorder == "Y")
					return false;
			}
			else
			{
				if (typeof dataSave == "undefined" || dataSave == "false")
					return false;
			}
		}
		
		return true;
	}
	
	// отмена создания заказа
	ydwidget.trade_denieOrderCreate = function(e)
	{
		// добавляем кнопку для открытия виджета на форму, иначе при неоткрытом блоке доставок, нажатие на оформить заказа ничего не делает
		ydwidget.trade_addInvisibleButton();
		
		if (ydwidget.trade_oldTemplate)
		{
			yd$("[data-ydwidget-profile="+ydwidget.trade_checkCurrentDelivery()+"]").click();
			
			yd$("#confirmorder").val("N");
		}
		else
		{
			if (typeof e != "undefined")
				e.preventDefault();
			setTimeout(function(){BX.Sale.OrderAjaxComponent.endLoader()}, 300);
			
			yd$("[data-ydwidget-profile="+ydwidget.trade_checkCurrentDelivery()+"]").click();
		}
		
		ydwidget.trade_delInvisibleButton();
	}
	
	// добавление невидимой кнопки для открытия виджета
	ydwidget.trade_addInvisibleButton = function()
	{
		var buttonObj = yd$("[data-ydwidget-profile="+ydwidget.trade_checkCurrentDelivery()+"]");
		if (buttonObj.length <= 0)
			yd$(ydwidget.trade_orderForm).append("<div id = 'ydmlab_fake_widget_link' style='display:none'>" + ydwidget.trade_selectPickupBtn[ydwidget.trade_checkCurrentDelivery()] + "</div>");
	}
	
	// удаление невидимой кнопки для открытия виджета
	ydwidget.trade_delInvisibleButton = function()
	{
		yd$("#ydmlab_fake_widget_link").remove();
	}
	
	// Инициализация JS - замена кнопки "Расчитать..." на выбор ПВЗ, проставление нужного ПВЗ и получение нужных параметров после обновления страницы по Ajax
	ydwidget.trade_initJS = function(ajaxAns)
	{
		var newTemplateAjax = (typeof(ajaxAns) != 'undefined' && ajaxAns !== null && typeof(ajaxAns.yandexDelivery) == 'object') ? true : false;
		
		// выбранная доставка
		if (ydwidget.trade_oldTemplate)
		{
			if (tmpVal = ydwidget.trade_getDataFromAjax("yd_ajaxdelveryid", "value"))
				ydwidget.trade_currentdelivery = tmpVal;
		}
		else
			if (newTemplateAjax)
				ydwidget.trade_currentdelivery = ajaxAns.yandexDelivery.yd_ajaxdelveryid;
		
		var curCheckedProfile = ydwidget.trade_checkCurrentDelivery();
		
		// проверяем по ответу это не UPDATE_STATE, нет - обновляем сохраненный адрес
		if (typeof ajaxAns == "undefined" || !(typeof ajaxAns == "object" && typeof ajaxAns["MESSAGE"] == "object" && ajaxAns["ERROR"] == ""))
			ydwidget.trade_saveAddressData();
		
		// если сменили профиль на Яндекс.Доставку и надо сразу открыть виджет
		// console.log({
			// "trade_showWidgetOnClick": ydwidget.trade_showWidgetOnClick,
			// "curCheckedProfile": curCheckedProfile,
			// "trade_chosenDeliveryType": ydwidget.trade_chosenDeliveryType
		// });
		
		var openWidget = false;
		if (
			ydwidget.trade_showWidgetOnClick &&
			curCheckedProfile && 
			typeof ydwidget.trade_chosenDeliveryType != "undefined" &&
			ydwidget.trade_chosenDeliveryType != curCheckedProfile
			)
			openWidget = true;
		
		ydwidget.trade_chosenDeliveryType = curCheckedProfile;
		
		// при смене профиля стираем данные о выбранной доставке из тега, либо выставляем его
		if (
			curCheckedProfile && 
			// typeof ydwidget.trade_deliveryDataSaved != "undefined" &&
			typeof ydwidget.trade_deliveryDataSaved[curCheckedProfile] != "undefined"
		)
			ydwidget.trade_putDataToForm(ydwidget.trade_deliveryDataSaved[curCheckedProfile], "yd_deliveryData");
		else
			ydwidget.trade_putDataToForm(false, "yd_deliveryData");
		
		// при смене профиля ставим на форму адрес пвз полный, если выбран самовывоз
		if (curCheckedProfile == "pickup" && ydwidget.trade_pvzAddressFull)
			ydwidget.trade_putDataToForm(ydwidget.trade_pvzAddressFull, "yd_pvzAdressValue");
		else
			ydwidget.trade_putDataToForm(false, "yd_pvzAdressValue");
			
		
		// ставим на форму признак, что выбрана Яндекс доставка
		var tmpDelivSelect = ydwidget.trade_getDataFromAjax("yd_is_select", "object");
		if (!tmpDelivSelect)
		{
			yd$(ydwidget.trade_orderForm).append("<input type = 'hidden' value = '' name = 'yd_is_select'>");
			tmpDelivSelect = ydwidget.trade_getDataFromAjax("yd_is_select", "object");
		}
		
		if (ydwidget.trade_checkCurrentDelivery())
			tmpDelivSelect.val("yandexDelivery");
		else
			tmpDelivSelect.val("false");
		
		// цепляем значения с формы при обновлении по ajax
		var tmpVal = false;
		// тип плательщика
		ydwidget.trade_personType = "<?=$arResult["PERSON_TYPE"]?>";
		if (ydwidget.trade_oldTemplate)
		{
			if (tmpVal = ydwidget.trade_getDataFromAjax("yd_ajaxpersontype", "value"))
				ydwidget.trade_personType = tmpVal;
		}
		else
			if (newTemplateAjax)
				ydwidget.trade_personType = ajaxAns.yandexDelivery.yd_ajaxpersontype;
			
		// инпут адреса
		if (typeof ydwidget.trade_addressInputs[ydwidget.trade_personType]["address"] != "undefined")
			ydwidget.trade_addrInp = ydwidget.trade_getDataFromAjax("ORDER_PROP_" + ydwidget.trade_addressInputs[ydwidget.trade_personType]["address"], "object");
		
		// город
		if (ydwidget.trade_oldTemplate)
		{
			if (tmpVal = ydwidget.trade_getDataFromAjax("yd_ajaxlocation", "value"))
				ydwidget.trade_currentCity = tmpVal;
		}
		else
			if (newTemplateAjax)
				ydwidget.trade_currentCity = ajaxAns.yandexDelivery.yd_ajaxlocation;
		
		// var toConsole = {
			// "ajaxAns": ajaxAns,
			// "newTemplateAjax": newTemplateAjax,
			// "curDeliv": ydwidget.trade_currentdelivery,
			// "oldTemplate": ydwidget.trade_oldTemplate
		// };
		
		// if (typeof(ajaxAns) != 'undefined')
			// toConsole["check"] = {
				// "1": typeof(ajaxAns) != 'undefined',
				// "2": ajaxAns !== null,
				// "3": typeof(ajaxAns.yandexDelivery) == 'object'
			// };
			
		// console.log(toConsole);
		
		// место, где будет кнопка "выбрать ПВЗ"
		// ydwidget.trade_selectPickupTag = {};
		ydwidget.trade_selectPickupTag = {
			"pickup": yd$('#trade_delivery_inject_pickup'),
			"post": yd$('#trade_delivery_inject_post'),
			"courier": yd$('#trade_delivery_inject_courier')
		};
		
		var addressValue = ydwidget.trade_getAddressInput();
		
		if(addressValue && ydwidget.trade_pvzAddressFull != "") // Если у нас есть адрес выбранного ПВЗ....
		{
			//...и он соответствует вдресу в инпуте адреса, то надо заблокировать инпут выбора адреса
			if(ydwidget.trade_pvzAddressFull && ydwidget.trade_chosenDeliveryType == "pickup")
			{
				ydwidget.trade_setAddressInput(ydwidget.trade_pvzAddressFull);
				ydwidget.trade_blockAddressInput(true);
			}
			else
			{
				// выставляем адрес сохраненный до пвз, разблокируем поле
				ydwidget.trade_blockAddressInput(false);
				if (ydwidget.trade_savedAddress)
					ydwidget.trade_setAddressInput(ydwidget.trade_savedAddress);
			}
		}
		
		
		if(!ydwidget.trade_pvzAddress)
			ydwidget.trade_pvzAddress = {}; // Если ПВЗ не выбран.
		
		// Тут ставим кнопку "выбрать ПВЗ"
		ydwidget.trade_setTariffInfo();
		
		// открываем виджет, если необходимо
		if (openWidget)
		{
			ydwidget.trade_addInvisibleButton();
			yd$("[data-ydwidget-profile="+ydwidget.trade_checkCurrentDelivery()+"]").click();
			ydwidget.trade_delInvisibleButton();
		}
	};
	
	// вписывает на форму данные в тег
	ydwidget.trade_putDataToForm = function (data, tagID)
	{
		var tmpInput = ydwidget.trade_getDataFromAjax(tagID, "object");
		
		// удаляем тег, если данные пустые
		if (!data && tmpInput)
		{
			tmpInput.remove();
			return;
		}
		
		if (tmpInput)
			tmpInput.val(JSON.stringify(data));
		else
			yd$(ydwidget.trade_orderForm).append("<input type = 'hidden' value = '"+ JSON.stringify(data) +"' name = '"+ tagID +"' id = '"+ tagID +"'>");
	}
	
	// в виджете выбрали вариант доставки, обрабатываем
	ydwidget.trade_onDeliveryChange = function (delivery, isAjax)
	{
		console.log({"delivery": delivery});
		
		if(!delivery) {
			ydwidget.trade_pvzAddressFull = '';
			ydwidget.trade_pvzId = '';
			ydwidget.trade_pvzAddress = {},
			yd$("#yd_deliveryData").remove();
			ydwidget.trade_setTariffInfo();
			return;
		}
		
		// заменяем в комментарии кавычки иначе не переводятся данные нормально в json
		if (typeof delivery.address != "undefined")
			if (typeof delivery.address.comment != "undefined")
				if (delivery.address.comment != null)
					delivery.address.comment = delivery.address.comment.replace(/\\?("|')/g, '\\$1');
		
		var deliveryTypesSeq = ydwidget.trade_getTariffAccording(),
			deliveryKey = deliveryTypesSeq[delivery.type];
		
		// запоминаем новую стоимость доставки
		ydwidget.trade_deliveryPrice[deliveryKey] = {
			"price": delivery.costWithRules,
			"term": delivery.days
		};
		
		// вставляем на форму в тег данные выбранного варианта доставки
		delivery.yandexDeliveryCity = ydwidget.trade_currentCity;
		ydwidget.trade_deliveryDataSaved[deliveryKey] = delivery;
		ydwidget.trade_putDataToForm(ydwidget.trade_deliveryDataSaved[deliveryKey], "yd_deliveryData");
		
		// впиливаем стоимость доставки
		ydwidget.trade_putDataToForm(ydwidget.trade_deliveryPrice, "yd_ajaxDeliveryPrice");
		
		// запомнили текущий профиль доставки
		ydwidget.trade_chosenDeliveryType = deliveryKey;
		
		// формируем адрес ПВЗ
		ydwidget.trade_createAddress(delivery);
		
		if (deliveryKey == "pickup")
		{
			// Сохраняем параметры выбранного ПВЗ, чтобы после Ajax-перезагрузки формы знать какой ПВЗ был выбран.
			ydwidget.trade_pvzId = delivery.pickuppointId;

			ydwidget.trade_setAddressInput(ydwidget.trade_pvzAddressFull);
			
			if(typeof isAjax == 'undefined')// Блокируем поле адреса.
				ydwidget.trade_blockAddressInput(true);
		}
		else
		{
			if(typeof isAjax == 'undefined')// Разблокируем поле адреса.
				ydwidget.trade_blockAddressInput(false);
				
			// выставляем адрес, который сохранили ранее, когда был выбран ПВЗ
			ydwidget.trade_setAddressInput(ydwidget.trade_savedAddress);
			
			if (deliveryKey == "post")
			{
				var addressAccording = ydwidget.trade_getAddressAccording(),
					autoComplitAddr = ydwidget.cartWidget.getAddress();
				// console.log({"ydwidget.cartWidget.getAddress": autoComplitAddr});
				
				if (typeof autoComplitAddr != "undefined" && autoComplitAddr != null)
					if (typeof ydwidget.trade_addressInputs[ydwidget.trade_personType]["address"] != "undefined")
					{
						var addr = autoComplitAddr["index"];
						addr += ", " + autoComplitAddr["city"];
						addr += ", " + autoComplitAddr["street"];
						addr += ", " + autoComplitAddr["house"];
						
						if (typeof autoComplitAddr["building"] != "undefined" && autoComplitAddr["building"] != null)
							addr += ", " + autoComplitAddr["building"];
						
						ydwidget.trade_setAddressInput(addr);
						
						// выставляем индекс
						if (typeof ydwidget.trade_addressInputs[ydwidget.trade_personType]["index"] != "undefined")
						{
							var selector = "[name=ORDER_PROP_" + ydwidget.trade_addressInputs[ydwidget.trade_personType]["index"] + "]";
							yd$(selector).val(autoComplitAddr["index"]);
							yd$(selector).html(autoComplitAddr["index"]);
						}
					}
					else
						for (var i in autoComplitAddr)
						{
							if (typeof ydwidget.trade_addressInputs[ydwidget.trade_personType][addressAccording[i]] != "undefined")
							{
								var selector = "[name=ORDER_PROP_" + ydwidget.trade_addressInputs[ydwidget.trade_personType][addressAccording[i]] + "]";
								yd$(selector).val(autoComplitAddr[i]);
								yd$(selector).html(autoComplitAddr[i]);
							}
						}
			}
		}
		
		//Выводим подпись о выбранном ПВЗ рядом с кнопкой "Выбрать ПВЗ"
		ydwidget.trade_setTariffInfo();
		
		// закрыли виджет
		ydwidget.cartWidget.close();
		
		// Перезагружаем форму (с применением новой стоимости доставки)
		if (ydwidget.trade_oldTemplate)
		{
			if(typeof isAjax == 'undefined')
			{
				var clickObj = yd$('#'+ydwidget.trade_htmlIDs[ydwidget.trade_chosenDeliveryType]);
				if (clickObj.prop("checked"))
				{
					if (typeof submitForm == "function")
						submitForm();
				}
				else
					clickObj.click();
			}
		}
		else
			BX.Sale.OrderAjaxComponent.sendRequest();
	}
	
	// устанавливает инпут адреса
	ydwidget.trade_setAddressInput = function(value)
	{
		if (ydwidget.trade_addrInp)
		{
			if (ydwidget.trade_oldTemplate)
			{
				ydwidget.trade_addrInp.val(value);
				ydwidget.trade_addrInp.html(value);
			}
			else
			{
				ydwidget.trade_addrInp.html(value);
				ydwidget.trade_addrInp.val(value);
			}
		}
	}
	
	// получает текущее значение в поле адреса
	ydwidget.trade_getAddressInput = function()
	{
		var addressValue = false;
		
		if (ydwidget.trade_addrInp)
			if (ydwidget.trade_oldTemplate)
				addressValue = ydwidget.trade_addrInp.val();
			else
				addressValue = ydwidget.trade_addrInp.val();
			
		return addressValue;
	}
	
	// блокирует поле адреса
	ydwidget.trade_blockAddressInput = function(block)
	{
		if (typeof block == "undefined")
			block = true;
		
		// console.log({"block": block});
		if (ydwidget.trade_addrInp)
		{
			if (block)
			{
				ydwidget.trade_addrInp
				// .css('background-color', '#eee')
				.addClass('yd_disabled')
				.bind("change", ydwidget.trade_blockChangeAddr)
				.bind("keyup", ydwidget.trade_blockChangeAddr);
				
				// ydwidget.trade_pvzAddressBlocked = true;
			}
			else
			{
				ydwidget.trade_addrInp
				// .css('background-color', '#eee')
				.removeClass('yd_disabled')
				.unbind("change", ydwidget.trade_blockChangeAddr)
				.unbind("keyup", ydwidget.trade_blockChangeAddr);
				
				// ydwidget.trade_pvzAddressBlocked = false;
			}
		}	
	}
	
	// формируем адрес и подпись для профиля яд
	ydwidget.trade_createAddress = function(delivery)
	{
		var address = '<span style="font-size:11px">';
		
		if (ydwidget.trade_chosenDeliveryType == "pickup")
		{
			// адрес для самовывоза
			ydwidget.trade_pvzAddressFull = '<?=GetMessage('delivery_JS_PICKUP')?>: ';
			ydwidget.trade_pvzAddressFull += delivery.full_address + ' | ';
			ydwidget.trade_pvzAddressFull += delivery.days + ' <?=GetMessage('delivery_JS_DAY')?> | ';
			// ydwidget.trade_pvzAddressFull += delivery.costWithRules + ' <?=GetMessage('delivery_JS_RUB')?>';
			ydwidget.trade_pvzAddressFull += ' #' + delivery.pickuppointId;
			
			address += delivery.address.street + '<br>';
		}
		
		// общее для всех профилей
		// if (!ydwidget.trade_oldTemplate)
			// address += '<?=GetMessage('delivery_JS_COST')?>: <b>'+delivery.costWithRules+' <?=GetMessage('delivery_JS_RUB')?></b><br>';
		
		// address += '<?=GetMessage('delivery_JS_TERM')?>: <b>' + delivery.days + ' <?=GetMessage('delivery_JS_DAY')?></b>';
		address += '</span><br>';
		
		ydwidget.trade_pvzAddress[ydwidget.trade_chosenDeliveryType] = address;
	}
	
	// Ф-ция которая не дает поменять адрес доставки при выбранном ПВЗ
	ydwidget.trade_blockChangeAddr = function()
	{
		if (ydwidget.trade_oldTemplate)
		{
			yd$(this).html(ydwidget.trade_pvzAddressFull);
			yd$(this).val(ydwidget.trade_pvzAddressFull);
		}
		else
		{
			yd$(this).val(ydwidget.trade_pvzAddressFull);
			yd$(this).html(ydwidget.trade_pvzAddressFull);
		}
	}
	
	// устанавливаем настройки виджета
	ydwidget.initCartWidget({
		//получить указанный пользователем город
		'getCity': function () {
			var city = '<?=$arResult["CITY_NAME"]?>';
			
			if(ydwidget.trade_currentCity)
				city = ydwidget.trade_currentCity;
			
			if (city)
				return {value: city};
			else
				return false;
		},

		//id элемента-контейнера
		'el': 'ydwidget',

		'itemsDimensions': function () {
			return [
				<?=$dimensionStr?>
			];
		},

		//общий вес товаров в корзине
		'weight': function () {
			return <?=number_format($arResult["TOTAL_WEIGHT"], 2)?>;
		},

		//общая стоимость товаров в корзине
		'cost': function () {
			return <?=$arResult["TOTAL_PRICE"]?>;
		},
		
		//общее количество товаров в корзине
		'totalItemsQuantity': function () { return 1; },
		
		'assessed_value': <?=$arResult["TOTAL_PRICE"]?>,
		
		// селектор на поле Индекс, его изменение заставит виджет выбрать другое почтовое отделение, соотв индексу новому
		'indexEl': "[name=ORDER_PROP_<?=$arAddrInputs[$arResult["PERSON_TYPE"]]["index"]?>]",
		
		// 'cityEl': 
		
		'order': {
		  //имя, фамилия, телефон, улица, дом, индекс
		  'recipient_first_name': function () { return yd$("[name=ORDER_PROP_"+ydwidget.trade_addressInputs[ydwidget.trade_personType]["fname"] + "]").val() },
		  'recipient_last_name': function () { return yd$("[name=ORDER_PROP_"+ydwidget.trade_addressInputs[ydwidget.trade_personType]["lname"] + "]").val() },
		  'recipient_phone': function () { return yd$("[name=ORDER_PROP_"+ydwidget.trade_addressInputs[ydwidget.trade_personType]["phone"] + "]").val() },
		  'deliverypoint_street': function () { return yd$("[name=ORDER_PROP_"+ydwidget.trade_addressInputs[ydwidget.trade_personType]["street"] + "]") },
		  'deliverypoint_house': function () { return yd$("[name=ORDER_PROP_"+ydwidget.trade_addressInputs[ydwidget.trade_personType]["house"] + "]").val() },
		  'deliverypoint_index': function () { return yd$("[name=ORDER_PROP_"+ydwidget.trade_addressInputs[ydwidget.trade_personType]["index"] + "]").val() },
		  
		  //объявленная ценность заказа
		  'order_assessed_value': <?=$arResult["TOTAL_PRICE"]?>,
		  //флаг отправки заказа через единый склад.
		  'delivery_to_yd_warehouse': <?=$arParams["TO_delivery_WAREHOUSE"]?>,
		  //товарные позиции в заказе
		  
		  //возможно указывать и другие поля, см. объект OrderItem в документации
		  // 'order_items': function () {
			// var items = [];
			// items.push({
			  // 'orderitem_name': 'Товар 1',
			  // 'orderitem_quantity': 2,
			  // 'orderitem_cost': 100
			// });
			// items.push({
			  // 'orderitem_name': 'Товар 2',
			  // 'orderitem_quantity': 1,
			  // 'orderitem_cost': 200
			// });
			// return items;
		  // }
		},
		
		'onLoad': function () {
			ydwidget.trade_onLoad();
		},

		'onDeliveryChange': function (delivery) {
			ydwidget.trade_onDeliveryChange(delivery);
		},
		
		// 'unSelectMsVariant': function () { yd$('#ms_delivery').prop('checked', false) },
        // 'selectMsVariant': function () { yd$('#ms_delivery').prop('checked', true) },
		
		//создавать заказ в cookie для его последующего создания в Яндекс.Доставке только если выбрана доставка Яндекса
		'createOrderFlag': function () 
		{ 
			// console.log({"check":(ydwidget.trade_currentdelivery == "<?=$deliveryID?>")});
			return ydwidget.trade_checkCurrentDelivery()?true:false;
			//(ydwidget.trade_currentdelivery == "<?=$deliveryID?>");//yd$('#yd_delivery').is(':checked')
		},

		//запустить сабмит формы, когда валидация успешно прошла и заказ создан в cookie,
		//либо если createOrderFlag вернул false
		'runOrderCreation': function () { 
			return false;
		},
		
		'onlyDeliveryTypes': function()
		{
			// return ["pickup", "post", "todoor"];
			return ydwidget.trade_onlyDeliveryTypes;
		}
	});
	
});
</script>

<?}
else
{
	?>
	<script>
	console.log(<?=CUtil::PHPToJSObject($arResult)?>);
	</script>
	<?
}?>