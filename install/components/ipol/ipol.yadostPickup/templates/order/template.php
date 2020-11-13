<?
/**
 * Copyright (c) 24/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

if (empty($arResult["ERRORS"]))
{
	$htmlIDs = array();
	$deliveryIDs = array();

	if (CIPOLYadostHelper::isConverted())
	{
		$dTS = Bitrix\Sale\Delivery\Services\Table::getList(array(
			'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
			'filter' => array('CODE' => 'ipolYadost:%')
		));

		while ($dataShip = $dTS->Fetch())
		{
			$profileName = preg_replace("/ipolYadost:/", "", $dataShip["CODE"]);
			$htmlIDs[$profileName] = 'ID_DELIVERY_ID_' . $dataShip['ID'];
			$deliveryIDs[$profileName] = $dataShip['ID'];
		}
	}
	else
	{
		$htmlIDs = array(
			"pickup" => 'ID_DELIVERY_ipolYadost_pickup',
			"courier" => 'ID_DELIVERY_ipolYadost_courier',
			"post" => 'ID_DELIVERY_ipolYadost_post'
		);
		$deliveryIDs = array(
			"pickup" => "ipolYadost:pickup",
			"courier" => "ipolYadost:courier",
			"post" => "ipolYadost:post"
		);
	}

	$dimensionStr = "[";

	$dimensionStr .= $arResult["ORDER_DIMENSIONS"]["WIDTH"] . ", ";
	$dimensionStr .= $arResult["ORDER_DIMENSIONS"]["LENGTH"] . ", ";
	$dimensionStr .= $arResult["ORDER_DIMENSIONS"]["HEIGHT"] . ", ";
	$dimensionStr .= 1;

	$dimensionStr .= "]";// передаем в виджет один товар с итоговыми габаритами посылки

	$arAddressInputs = array();
	foreach ($arResult["ADDRESS_FIELDS"] as $personType => $arAdr)
		foreach ($arAdr as $propName => $propID)
			$arAddressInputs[$personType][$propName] = $propID;

	$widgetCode = COption::GetOptionString("ipol.yadost", "basketWidget");

	$GLOBALS['APPLICATION']->AddHeadString(COption::GetOptionString("ipol.yadost", "basketWidget"));
	$showWidgetOnProfileClick = ("Y" == COption::GetOptionString("ipol.yadost", "showWidgetOnProfile", "N")) ? true : false;
	?>

    <!--suppress ALL, JSUnresolvedVariable, JSUnresolvedVariable -->
    <script type="text/javascript">
        if (typeof ydwidget !== "undefined")
        {
            ydwidget.ready(function ()
            {
                yd$('body').prepend('<div id="ydwidget" class="yd-widget-modal"></div>');

                // выполняется при первой загрузке виджета
                ydwidget.ipol_onLoad = function ()
                {
                    if (typeof BX.Sale.OrderAjaxComponent !== "undefined") {
                        BX.Sale.OrderAjaxComponent.sendRequest('refreshOrderAjax');
                    }
                    ydwidget.ipol_pvzAddressFull = "";
                    ydwidget.ipol_orderForm = "#ORDER_FORM";
                    ydwidget.ipol_oldTemplate = false;
                    ydwidget.ipol_startHTML = false;


                    ydwidget.ipol_addressInputs = <?=CUtil::PHPToJSObject($arAddressInputs)?>;

                    ydwidget.ipol_showWidgetOnClick = <?=CUtil::PHPToJSObject($showWidgetOnProfileClick)?>;

                    ydwidget.ipol_htmlIDs = <?=CUtil::PHPToJSObject($htmlIDs)?>;
                    ydwidget.ipol_deliveryIDs = <?=CUtil::PHPToJSObject($deliveryIDs)?>;
                    ydwidget.ipol_deliveryPrice = {};

                    ydwidget.ipol_openWidgetTitles = {
                        "courier": "<?=GetMessage('IPOLyadost_JS_select_courier')?>",
                        "post": "<?=GetMessage('IPOLyadost_JS_select_post')?>",
                        "pickup": "<?=GetMessage('IPOLyadost_JS_select_pickup')?>",
                    };

                    if (yd$(ydwidget.ipol_orderForm).length > 0)
                        ydwidget.ipol_oldTemplate = true;
                    else
                        ydwidget.ipol_orderForm = "#bx-soa-order-form";


                    if (typeof ydwidget.ipol_currentCity == "undefined")
                        ydwidget.ipol_currentCity = '<?=$arResult["CITY_NAME"]?>';

                    ydwidget.ipol_selectPickupBtn = {};
                    ydwidget.ipol_deliveryDataSaved = {};

                    // определяем выбрана ли сейчас delivery и заодно формируем кнопки выбрать ПВЗ для каждого профиля
                    for (var key in ydwidget.ipol_htmlIDs)
                    {
                        var profileKey = ydwidget.ipol_getTariffAccordingKey(key);

                        ydwidget.ipol_selectPickupBtn[key] = '<a href="javascript:void(0);" data-ydwidget-open data-ydwidget-profile = "' + key + '" onclick="ydwidget.ipol_openWidget(\'' + profileKey + '\');">' + ydwidget.ipol_openWidgetTitles[key] + '</a>';

                        var deliveryRadio = yd$("#" + ydwidget.ipol_htmlIDs[key]);
                        if (typeof deliveryRadio != "undefined")
                            if (deliveryRadio.length > 0)
                                if (ydwidget.ipol_oldTemplate)
                                {
                                    if (deliveryRadio.attr("checked") == "checked")
                                        ydwidget.ipol_currentdelivery = ydwidget.ipol_deliveryIDs[key];
                                }
                                else
                                {
                                    if (deliveryRadio.prop("checked"))
                                        ydwidget.ipol_currentdelivery = ydwidget.ipol_deliveryIDs[key];
                                }
                    }
                    ;

                    // навешиваем обработчик на отправку формы
                    ydwidget.ipol_onSubmitForm();

                    // переопределяем функцию обновления формы, чтобы сохранять адрес перед отправкой и запретить оф заказа, если не указан для профиля вариант в виджете
                    if (!ydwidget.ipol_oldTemplate)
                    {
                        BX.Sale.OrderAjaxComponent.ipol_oldSendRequest = BX.Sale.OrderAjaxComponent.sendRequest;

                        BX.Sale.OrderAjaxComponent.sendRequest = function (action, actionData)
                        {
                            if (!ydwidget.cartWidget.isOpened)
                                ydwidget.ipol_beforeSubmitAddress = ydwidget.ipol_getAddressInput();

                            if (action == "saveOrderAjax" && !ydwidget.ipol_checkOrderCreate())
                                ydwidget.ipol_denieOrderCreate();
                            else
                                BX.Sale.OrderAjaxComponent.ipol_oldSendRequest(action, actionData);
                        }
                    }

                    // навешиваем обработчики на открытие блока доставок
                    if (!ydwidget.ipol_oldTemplate)
                    {
                        yd$('#bx-soa-delivery .bx-soa-section-title-container').on('click', function ()
                        {
                            ydwidget.ipol_initJS();
                        });
                        yd$('#bx-soa-delivery .bx-soa-section-title-container a').on('click', function ()
                        {
                            ydwidget.ipol_initJS();
                        });
                    }

                    // запускаем скрипты обновления формы
                    ydwidget.ipol_initJS();

                    // ==== подписываемся на перезагрузку формы
                    if (typeof(BX) && BX.addCustomEvent)
                        BX.addCustomEvent('onAjaxSuccess', ydwidget.ipol_initJS);

                    // Для старого JS-ядра
                    if (window.jsAjaxUtil) // Переопределение Ajax-завершающей функции для навешивания js-событий новым эл-там
                    {
                        jsAjaxUtil._CloseLocalWaitWindow = jsAjaxUtil.CloseLocalWaitWindow;
                        jsAjaxUtil.CloseLocalWaitWindow = function (TID, cont)
                        {
                            jsAjaxUtil._CloseLocalWaitWindow(TID, cont);
                            ydwidget.ipol_initJS();
                        }
                    }
                };

                // открывает виджет с нужным профилем
                ydwidget.ipol_openWidget = function (profile)
                {
                    ydwidget.ipol_saveAddressData(profile);
                    ydwidget.ipol_onlyDeliveryTypes = [profile];
                    ydwidget.cartWidget.changeDeliveryTypes();
					
					setTimeout(function()
                    {
                        var $toggleBlock = yd$(".cw-variants-container"),
                            $clickBlock = yd$("#cw_variants_header");

                        $clickBlock.click(function (event)
                        {
                            console.log("click");
                            $toggleBlock.toggleClass('postActive');
                        });
                        
                        if (ydwidget.ipol_chosenDeliveryType == "courier" || ydwidget.ipol_chosenDeliveryType == "post")
                            $toggleBlock.addClass("postCourier");
                        else
                            $toggleBlock.removeClass("postCourier");
					}, 4000);
					   
                    return false;
                }

                // сохраняем адрес указанный, чтобы его вернуть, если выбрали иной способ доставки
                ydwidget.ipol_saveAddressData = function (profile)
                {
                    if (typeof profile == "undefined")
                        profile = false;

                    var addressValue = ydwidget.ipol_getAddressInput();

                    if (!ydwidget.ipol_savedAddress)
                        ydwidget.ipol_savedAddress = addressValue;

                    // тут тонкий момент, либо в виджете открывают самовывоз, либо был не самовывоз в форме и она обновилась UPDATE_STATE, тогда сохраняем адрес
                    if (typeof ydwidget.ipol_chosenDeliveryType != "undefined")
                        if (ydwidget.ipol_chosenDeliveryType != "pickup" && profile == "pickup")
                            ydwidget.ipol_savedAddress = addressValue;
                        else if (profile == false && ydwidget.ipol_chosenDeliveryType != "pickup" && !ydwidget.cartWidget.isOpened)
                        {
                            if (ydwidget.ipol_oldTemplate)
                                ydwidget.ipol_savedAddress = ydwidget.ipol_beforeSubmitAddress;
                        }
                }

                // получение соответсвий по названиям тарифов
                ydwidget.ipol_getTariffAccording = function ()
                {
                    return {
                        "TODOOR": "courier",
                        "POST": "post",
                        "PICKUP": "pickup"
                    };
                }

                // получение соответсвий по названиям блока адреса
                ydwidget.ipol_getAddressAccording = function ()
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
                ydwidget.ipol_getTariffAccordingKey = function (key)
                {
                    var according = ydwidget.ipol_getTariffAccording();

                    for (var i in according)
                        if (according[i] == key)
                            return i.toLowerCase();

                    return false;
                }

                // проверка выбран ли профиль ЯД
                ydwidget.ipol_checkCurrentDelivery = function ()
                {
                    for (var key in ydwidget.ipol_htmlIDs)
                        if (ydwidget.ipol_currentdelivery == ydwidget.ipol_deliveryIDs[key])
                            return key;

                    return false;
                }

                // получение значения поля на форме при аякс обновлении
                ydwidget.ipol_getDataFromAjax = function (inputName, returnType)
                {
                    var input = false,
                        tmpInput = false;

                    tmpInput = yd$('#' + inputName);
                    if (tmpInput.length > 0)
                        input = tmpInput;

                    tmpInput = yd$('[name=' + inputName + ']');
                    if (tmpInput.length > 0)
                        input = tmpInput;

                    if (input)
                        if (returnType == "value")
                            return input.val();
                        else
                            return input;

                    return false;
                }

                // ставим подпись для открытия виджета и адрес выбранный
                ydwidget.ipol_setTariffInfo = function ()
                {
                    var addrHTML = "";

                    if (ydwidget.ipol_oldTemplate)
                    {
                        for (var key in ydwidget.ipol_selectPickupTag)
                        {
                            if (ydwidget.ipol_selectPickupTag[key])
                            {
                                if (typeof ydwidget.ipol_pvzAddress[key] != "undefined" && ydwidget.ipol_pvzAddress[key] != "undefined")
                                    addrHTML += ydwidget.ipol_pvzAddress[key];

                                if (ydwidget.ipol_selectPickupTag[key])
                                    if (!ydwidget.ipol_selectPickupTag[key].data("ydButtonSet"))
                                    {
                                        if (yd$("#ipol_pvz_address_block").length <= 0)
                                        {
                                            addrHTML = "<span id = 'ipol_pvz_address_block'>" + addrHTML + "</span>";
                                            ydwidget.ipol_selectPickupTag[key].before(addrHTML);
                                        }
                                        else
                                            yd$("#ipol_pvz_address_block").html(addrHTML);

                                        ydwidget.ipol_selectPickupTag[key].append(ydwidget.ipol_selectPickupBtn[key]);
                                        ydwidget.ipol_selectPickupTag[key].data("ydButtonSet", true);
                                    }
                            }
                        }
                    }
                    else
                    {
                        var key = ydwidget.ipol_checkCurrentDelivery();

                        if (typeof ydwidget.ipol_pvzAddress[key] != "undefined" && ydwidget.ipol_pvzAddress[key] != "undefined")
                            addrHTML = ydwidget.ipol_pvzAddress[key];

                        if (ydwidget.ipol_selectPickupTag[key])
                        {
                            if (yd$("#ipol_pvz_address_block").length <= 0)
                            {
                                addrHTML = "<span id = 'ipol_pvz_address_block'>" + addrHTML + "</span>";
                                ydwidget.ipol_selectPickupTag[key].before(addrHTML);
                            }
                            else
                                yd$("#ipol_pvz_address_block").html(addrHTML);

                            ydwidget.ipol_selectPickupTag[key].html(ydwidget.ipol_selectPickupBtn[key]);
                        }
                    }
                };

                // подписка на отправку формы, не даем оф заказ с профилем яд и не указанным вариантом в виджете
                ydwidget.ipol_onSubmitForm = function ()
                {
                    // не даем отправить форму, если выбрана ddelivery и не заполнены данные dataSave
                    yd$(ydwidget.ipol_orderForm).on("submit", function (e)
                    {
                        // сохраняем адрес перед отправкой формы
                        if (!ydwidget.cartWidget.isOpened)
                            ydwidget.ipol_beforeSubmitAddress = ydwidget.ipol_getAddressInput();

                        if (!ydwidget.ipol_checkOrderCreate())
                            ydwidget.ipol_denieOrderCreate(e);

                        return true;
                    });
                }

                // проверка на возможность создания заказа
                ydwidget.ipol_checkOrderCreate = function ()
                {
                    if (ydwidget.ipol_checkCurrentDelivery())
                    {
                        var dataSave = yd$("#yd_deliveryData").val(),
                            confirmorder = yd$("#confirmorder").val();

                        if (ydwidget.ipol_oldTemplate)
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
                ydwidget.ipol_denieOrderCreate = function (e)
                {
                    // добавляем кнопку для открытия виджета на форму, иначе при неоткрытом блоке доставок, нажатие на оформить заказа ничего не делает
                    ydwidget.ipol_addInvisibleButton();

                    if (ydwidget.ipol_oldTemplate)
                    {
                        yd$("[data-ydwidget-profile=" + ydwidget.ipol_checkCurrentDelivery() + "]").click();

                        yd$("#confirmorder").val("N");
                    }
                    else
                    {
                        if (typeof e != "undefined")
                            e.preventDefault();
                        setTimeout(function ()
                        {
                            BX.Sale.OrderAjaxComponent.endLoader()
                        }, 300);

                        yd$("[data-ydwidget-profile=" + ydwidget.ipol_checkCurrentDelivery() + "]").click();
                    }

                    ydwidget.ipol_delInvisibleButton();
                }

                // добавление невидимой кнопки для открытия виджета
                ydwidget.ipol_addInvisibleButton = function ()
                {
                    var buttonObj = yd$("[data-ydwidget-profile=" + ydwidget.ipol_checkCurrentDelivery() + "]");
                    if (buttonObj.length <= 0)
                        yd$(ydwidget.ipol_orderForm).append("<div id = 'ydmlab_fake_widget_link' style='display:none'>" + ydwidget.ipol_selectPickupBtn[ydwidget.ipol_checkCurrentDelivery()] + "</div>");
                }

                // удаление невидимой кнопки для открытия виджета
                ydwidget.ipol_delInvisibleButton = function ()
                {
                    yd$("#ydmlab_fake_widget_link").remove();
                }

                // Инициализация JS - замена кнопки "Расчитать..." на выбор ПВЗ, проставление нужного ПВЗ и получение нужных параметров после обновления страницы по Ajax
                ydwidget.ipol_initJS = function (ajaxAns)
                {
                    var newTemplateAjax = (typeof(ajaxAns) != 'undefined' && ajaxAns !== null && typeof(ajaxAns.IPOLyadost) == 'object') ? true : false;

                    // выбранная доставка
                    if (ydwidget.ipol_oldTemplate)
                    {
                        if (tmpVal = ydwidget.ipol_getDataFromAjax("yd_ajaxDeliveryID", "value"))
                            ydwidget.ipol_currentdelivery = tmpVal;
                    }
                    else if (newTemplateAjax)
                        ydwidget.ipol_currentdelivery = ajaxAns.IPOLyadost.yd_ajaxDeliveryID;

                    var curCheckedProfile = ydwidget.ipol_checkCurrentDelivery();

                    // проверяем по ответу это не UPDATE_STATE, нет - обновляем сохраненный адрес
                    if (typeof ajaxAns == "undefined" || !(typeof ajaxAns == "object" && typeof ajaxAns["MESSAGE"] == "object" && ajaxAns["ERROR"] == ""))
                        ydwidget.ipol_saveAddressData();

                    // если сменили профиль на Яндекс.Доставку и надо сразу открыть виджет
                    var openWidget = false;
                    if (
                        ydwidget.ipol_showWidgetOnClick &&
                        curCheckedProfile &&
                        typeof ydwidget.ipol_chosenDeliveryType != "undefined" &&
                        ydwidget.ipol_chosenDeliveryType != curCheckedProfile
                    )
                        openWidget = true;

                    ydwidget.ipol_chosenDeliveryType = curCheckedProfile;

                    // при смене профиля стираем данные о выбранной доставке из тега, либо выставляем его
                    if (
                        curCheckedProfile &&
                        typeof ydwidget.ipol_deliveryDataSaved[curCheckedProfile] != "undefined"
                    )
                        ydwidget.ipol_putDataToForm(ydwidget.ipol_deliveryDataSaved[curCheckedProfile], "yd_deliveryData");
                    else
                        ydwidget.ipol_putDataToForm(false, "yd_deliveryData");

                    // при смене профиля ставим на форму адрес пвз полный, если выбран самовывоз
                    if (curCheckedProfile == "pickup" && ydwidget.ipol_pvzAddressFull)
                        ydwidget.ipol_putDataToForm(ydwidget.ipol_pvzAddressFull, "yd_pvzAddressValue");
                    else
                        ydwidget.ipol_putDataToForm(false, "yd_pvzAddressValue");


                    // ставим на форму признак, что выбрана Яндекс доставка
                    var tmpDelivSelect = ydwidget.ipol_getDataFromAjax("yd_is_select", "object");
                    if (!tmpDelivSelect)
                    {
                        yd$(ydwidget.ipol_orderForm).append("<input type = 'hidden' value = '' name = 'yd_is_select'>");
                        tmpDelivSelect = ydwidget.ipol_getDataFromAjax("yd_is_select", "object");
                    }

                    // впиливаем стоимость доставки
                    if (curCheckedProfile)
                        ydwidget.ipol_putDataToForm(ydwidget.ipol_deliveryPrice, "yd_ajaxDeliveryPrice");

                    if (ydwidget.ipol_checkCurrentDelivery())
                        tmpDelivSelect.val("ipolYadost");
                    else
                        tmpDelivSelect.val("false");

                    // цепляем значения с формы при обновлении по ajax
                    var tmpVal = false;
                    // тип плательщика
                    ydwidget.ipol_personType = "<?=$arResult["PERSON_TYPE"]?>";
                    if (ydwidget.ipol_oldTemplate)
                    {
                        if (tmpVal = ydwidget.ipol_getDataFromAjax("yd_ajaxPersonType", "value"))
                            ydwidget.ipol_personType = tmpVal;
                    }
                    else if (newTemplateAjax)
                        ydwidget.ipol_personType = ajaxAns.IPOLyadost.yd_ajaxPersonType;

                    // инпут адреса
                    if (typeof ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["address"] != "undefined")
                        ydwidget.ipol_addrInp = ydwidget.ipol_getDataFromAjax("ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["address"], "object");

                    // город
                    if (ydwidget.ipol_oldTemplate)
                    {
                        if (tmpVal = ydwidget.ipol_getDataFromAjax("yd_ajaxLocation", "value"))
                            ydwidget.ipol_currentCity = tmpVal;
                    }
                    else if (newTemplateAjax)
                        ydwidget.ipol_currentCity = ajaxAns.IPOLyadost.yd_ajaxLocation;

                    // место, где будет кнопка "выбрать ПВЗ"
                    ydwidget.ipol_selectPickupTag = {
                        "pickup": yd$('#ipol_yadost_inject_pickup'),
                        "post": yd$('#ipol_yadost_inject_post'),
                        "courier": yd$('#ipol_yadost_inject_courier')
                    };

                    var addressValue = ydwidget.ipol_getAddressInput();

                    if (addressValue && ydwidget.ipol_pvzAddressFull != "") // Если у нас есть адрес выбранного ПВЗ....
                    {
                        //...и он соответствует вдресу в инпуте адреса, то надо заблокировать инпут выбора адреса
                        if (ydwidget.ipol_pvzAddressFull && ydwidget.ipol_chosenDeliveryType == "pickup")
                        {
                            ydwidget.ipol_setAddressInput(ydwidget.ipol_pvzAddressFull);
                            ydwidget.ipol_blockAddressInput(true);
                        }
                        else
                        {
                            // выставляем адрес сохраненный до пвз, разблокируем поле
                            ydwidget.ipol_blockAddressInput(false);
                            if (ydwidget.ipol_savedAddress)
                                ydwidget.ipol_setAddressInput(ydwidget.ipol_savedAddress);
                        }
                    }


                    if (!ydwidget.ipol_pvzAddress)
                        ydwidget.ipol_pvzAddress = {}; // Если ПВЗ не выбран.

                    // Тут ставим кнопку "выбрать ПВЗ"
                    ydwidget.ipol_setTariffInfo();

                    // открываем виджет, если необходимо
                    if (openWidget)
                    {
                        ydwidget.ipol_addInvisibleButton();
                        yd$("[data-ydwidget-profile=" + ydwidget.ipol_checkCurrentDelivery() + "]").click();
                        ydwidget.ipol_delInvisibleButton();
                    }
                };

                // вписывает на форму данные в тег
                ydwidget.ipol_putDataToForm = function (data, tagID)
                {
                    var tmpInput = ydwidget.ipol_getDataFromAjax(tagID, "object");

                    // удаляем тег, если данные пустые
                    if (!data && tmpInput)
                    {
                        tmpInput.remove();
                        return;
                    }

                    if (tmpInput)
                        tmpInput.val(JSON.stringify(data));
                    else
                        yd$(ydwidget.ipol_orderForm).append("<input type = 'hidden' value = '" + JSON.stringify(data) + "' name = '" + tagID + "' id = '" + tagID + "'>");
                }

                // в виджете выбрали вариант доставки, обрабатываем
                ydwidget.ipol_onDeliveryChange = function (delivery, isAjax)
                {
                    console.log({"delivery": delivery});

                    if (!delivery)
                    {
                        ydwidget.ipol_pvzAddressFull = '';
                        ydwidget.ipol_pvzId = '';
                        ydwidget.ipol_pvzAddress = {},
                            yd$("#yd_deliveryData").remove();
                        ydwidget.ipol_setTariffInfo();
                        return;
                    }

                    // заменяем в комментарии кавычки иначе не переводятся данные нормально в json
                    if (typeof delivery.address != "undefined")
                        if (typeof delivery.address.comment != "undefined")
                            if (delivery.address.comment != null)
                                delivery.address.comment = delivery.address.comment.replace(/\\?("|')/g, '\\$1');

                    var deliveryTypesSeq = ydwidget.ipol_getTariffAccording(),
                        deliveryKey = deliveryTypesSeq[delivery.type];

                    // запоминаем новую стоимость доставки
                    ydwidget.ipol_deliveryPrice[deliveryKey] = {
                        "price": delivery.costWithRules,
                        "term": delivery.days,
                        "provider": delivery.delivery.name
                    };

                    // вставляем на форму в тег данные выбранного варианта доставки
                    delivery.yadostCity = ydwidget.ipol_currentCity;
                    ydwidget.ipol_deliveryDataSaved[deliveryKey] = delivery;
                    ydwidget.ipol_putDataToForm(ydwidget.ipol_deliveryDataSaved[deliveryKey], "yd_deliveryData");

                    // впиливаем стоимость доставки
                    ydwidget.ipol_putDataToForm(ydwidget.ipol_deliveryPrice, "yd_ajaxDeliveryPrice");

                    // запомнили текущий профиль доставки
                    ydwidget.ipol_chosenDeliveryType = deliveryKey;

                    // формируем адрес ПВЗ
                    ydwidget.ipol_createAddress(delivery);

                    if (deliveryKey == "pickup")
                    {
                        // Сохраняем параметры выбранного ПВЗ, чтобы после Ajax-перезагрузки формы знать какой ПВЗ был выбран.
                        ydwidget.ipol_pvzId = delivery.pickuppointId;

                        ydwidget.ipol_setAddressInput(ydwidget.ipol_pvzAddressFull);

                        if (typeof isAjax == 'undefined')// Блокируем поле адреса.
                            ydwidget.ipol_blockAddressInput(true);
                    }
                    else
                    {
                        if (typeof isAjax == 'undefined')// Разблокируем поле адреса.
                            ydwidget.ipol_blockAddressInput(false);

                        // выставляем адрес, который сохранили ранее, когда был выбран ПВЗ
                        ydwidget.ipol_setAddressInput(ydwidget.ipol_savedAddress);

                        if (deliveryKey == "post")
                        {
                            var addressAccording = ydwidget.ipol_getAddressAccording(),
                                autoComplitAddr = ydwidget.cartWidget.getAddress();
                             console.log({"ydwidget.cartWidget.getAddress": autoComplitAddr});

                            if (typeof autoComplitAddr != "undefined" && autoComplitAddr != null)
                                if (typeof ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["address"] != "undefined")
                                {
                                    var addr = autoComplitAddr["index"];
                                    addr += ", " + autoComplitAddr["city"];
                                    addr += ", " + autoComplitAddr["street"];
                                    addr += ", " + autoComplitAddr["house"];

                                    if (typeof autoComplitAddr["building"] != "undefined" && autoComplitAddr["building"] != null)
                                        addr += ", " + autoComplitAddr["building"];

                                    ydwidget.ipol_setAddressInput(addr);

                                    // выставляем индекс
                                    if (typeof ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["index"] != "undefined")
                                    {
                                        var selector = "[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["index"] + "]";
                                        yd$(selector).val(autoComplitAddr["index"]);
                                        yd$(selector).html(autoComplitAddr["index"]);
                                    }
                                }
                                else
                                    for (var i in autoComplitAddr)
                                    {
                                        if (typeof ydwidget.ipol_addressInputs[ydwidget.ipol_personType][addressAccording[i]] != "undefined")
                                        {
                                            var selector = "[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType][addressAccording[i]] + "]";
                                            yd$(selector).val(autoComplitAddr[i]);
                                            yd$(selector).html(autoComplitAddr[i]);
                                        }
                                    }
                        }
                    }

                    // выставляем улицу, дом, квартиру, офис, если указаны коды в настройках
                    ydwidget.ipol_setAddressInputs(delivery.address, delivery.type);

                    //Выводим подпись о выбранном ПВЗ рядом с кнопкой "Выбрать ПВЗ"
                    ydwidget.ipol_setTariffInfo();

                    // закрыли виджет
                    ydwidget.cartWidget.close();

                    // Перезагружаем форму (с применением новой стоимости доставки)
                    if (ydwidget.ipol_oldTemplate)
                    {
                        if (typeof isAjax == 'undefined')
                        {
                            var clickObj = yd$('#' + ydwidget.ipol_htmlIDs[ydwidget.ipol_chosenDeliveryType]);
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

                // выставляет поля улица, дом, квартира
                ydwidget.ipol_setAddressInputs = function (addressObj, deliveryType)
                {
                    if (!addressObj ||  deliveryType == 'POST')
                        return false;

                    var addressAccording = ydwidget.ipol_getAddressAccording();

                    for (var i in ydwidget.ipol_addressInputs[ydwidget.ipol_personType])
                    {
                        var inputID = ydwidget.ipol_addressInputs[ydwidget.ipol_personType][i],
                            adressObjectKey = addressAccording[i];

                        if (!!inputID)
                        {
                            var $inputObj = yd$("[name=ORDER_PROP_"+ inputID +"]");
                            if (!!addressObj[adressObjectKey] && !!$inputObj)
                            {
                                $inputObj.val(addressObj[adressObjectKey]);
                            }
                        }
                    }
                }

                // устанавливает инпут адреса
                ydwidget.ipol_setAddressInput = function (value)
                {
                    if (ydwidget.ipol_addrInp)
                    {
                        if (ydwidget.ipol_oldTemplate)
                        {
                            ydwidget.ipol_addrInp.val(value);
                            ydwidget.ipol_addrInp.html(value);
                        }
                        else
                        {
                            ydwidget.ipol_addrInp.html(value);
                            ydwidget.ipol_addrInp.val(value);
                        }
                    }
                }

                // получает текущее значение в поле адреса
                ydwidget.ipol_getAddressInput = function ()
                {
                    var addressValue = false;

                    if (ydwidget.ipol_addrInp)
                        if (ydwidget.ipol_oldTemplate)
                            addressValue = ydwidget.ipol_addrInp.val();
                        else
                            addressValue = ydwidget.ipol_addrInp.val();

                    return addressValue;
                }

                // блокирует поле адреса
                ydwidget.ipol_blockAddressInput = function (block)
                {
                    if (typeof block == "undefined")
                        block = true;

                    // console.log({"block": block});
                    if (ydwidget.ipol_addrInp)
                    {
                        if (block)
                        {
                            ydwidget.ipol_addrInp
                            // .css('background-color', '#eee')
                                .addClass('yd_disabled')
                                .bind("change", ydwidget.ipol_blockChangeAddr)
                                .bind("keyup", ydwidget.ipol_blockChangeAddr);

                            // ydwidget.ipol_pvzAddressBlocked = true;
                        }
                        else
                        {
                            ydwidget.ipol_addrInp
                            // .css('background-color', '#eee')
                                .removeClass('yd_disabled')
                                .unbind("change", ydwidget.ipol_blockChangeAddr)
                                .unbind("keyup", ydwidget.ipol_blockChangeAddr);

                            // ydwidget.ipol_pvzAddressBlocked = false;
                        }
                    }
                }

                // формируем адрес и подпись для профиля яд
                ydwidget.ipol_createAddress = function (delivery)
                {
                    var address = '<span style="font-size:11px">';

                    if (ydwidget.ipol_chosenDeliveryType == "pickup")
                    {
                        // адрес для самовывоза
                        ydwidget.ipol_pvzAddressFull = '<?=GetMessage('IPOLyadost_JS_PICKUP')?>: ';
                        ydwidget.ipol_pvzAddressFull += delivery.full_address + ' | ';
                        ydwidget.ipol_pvzAddressFull += delivery.days + ' <?=GetMessage('IPOLyadost_JS_DAY')?> | ';
                        ydwidget.ipol_pvzAddressFull += ' #' + delivery.pickuppointId;

                        address += delivery.address.street + '<br>';
                    }

                    address += '</span><br>';

                    ydwidget.ipol_pvzAddress[ydwidget.ipol_chosenDeliveryType] = address;
                }

                // Ф-ция которая не дает поменять адрес доставки при выбранном ПВЗ
                ydwidget.ipol_blockChangeAddr = function ()
                {
                    if (ydwidget.ipol_oldTemplate)
                    {
                        yd$(this).html(ydwidget.ipol_pvzAddressFull);
                        yd$(this).val(ydwidget.ipol_pvzAddressFull);
                    }
                    else
                    {
                        yd$(this).val(ydwidget.ipol_pvzAddressFull);
                        yd$(this).html(ydwidget.ipol_pvzAddressFull);
                    }
                }

                // устанавливаем настройки виджета
                ydwidget.initCartWidget({
                    //получить указанный пользователем город
                    'getCity': function ()
                    {
                        var city = '<?=$arResult["CITY_NAME"]?>';

                        if (ydwidget.ipol_currentCity)
                            city = ydwidget.ipol_currentCity;

                        if (city)
                            return {value: city};
                        else
                            return false;
                    },

                    //id элемента-контейнера
                    'el': 'ydwidget',

                    'itemsDimensions': function ()
                    {
                        return [
							<?=$dimensionStr?>
                        ];
                    },

                    //общий вес товаров в корзине
                    'weight': function ()
                    {
                        return <?=number_format($arResult["TOTAL_WEIGHT"], 2)?>;
                    },

                    //общая стоимость товаров в корзине
                    'cost': function ()
                    {
                        return <?=$arResult["TOTAL_PRICE"]?>;
                    },

                    //общее количество товаров в корзине
                    'totalItemsQuantity': function ()
                    {
                        return 1;
                    },

                    'assessed_value': <?=$arResult["TOTAL_PRICE"]?>,
	
	                <?
                    // селектор на поле Индекс, его изменение заставит виджет выбрать другое почтовое отделение, соотв индексу новому
                    
                    
                    // убрано, так как ввод невалидного индекса из справочника местоположений Битрикс выводит на карте, что почтового отделения нет
                    /*'indexEl': "[name=ORDER_PROP_<?=$arAddressInputs[$arResult["PERSON_TYPE"]]["index"]?>]",*/?>

                    // 'cityEl':

                    'order': {
                        //имя, фамилия, телефон, улица, дом, индекс
                        'recipient_first_name': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["fname"] + "]").val()
                        },
                        'recipient_last_name': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["lname"] + "]").val()
                        },
                        'recipient_phone': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["phone"] + "]").val()
                        },
                        'deliverypoint_street': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["street"] + "]")
                        },
                        'deliverypoint_house': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["house"] + "]").val()
                        },
                        'deliverypoint_index': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.ipol_addressInputs[ydwidget.ipol_personType]["index"] + "]").val()
                        },

                        //объявленная ценность заказа
                        'order_assessed_value': <?=$arResult["TOTAL_PRICE"]?>,
                        //флаг отправки заказа через единый склад.
                        'delivery_to_yd_warehouse': <?=$arParams["TO_YADOST_WAREHOUSE"]?>,
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

                    'onLoad': function ()
                    {
                        ydwidget.ipol_onLoad();
                    },

                    'onDeliveryChange': function (delivery)
                    {
                        ydwidget.ipol_onDeliveryChange(delivery);
                    },

                    // 'unSelectMsVariant': function () { yd$('#ms_delivery').prop('checked', false) },
                    // 'selectMsVariant': function () { yd$('#ms_delivery').prop('checked', true) },

                    //создавать заказ в cookie для его последующего создания в Яндекс.Доставке только если выбрана доставка Яндекса
                    'createOrderFlag': function ()
                    {
                        return ydwidget.ipol_checkCurrentDelivery() ? true : false;
                    },

                    //запустить сабмит формы, когда валидация успешно прошла и заказ создан в cookie,
                    //либо если createOrderFlag вернул false
                    'runOrderCreation': function ()
                    {
                        return false;
                    },

                    'onlyDeliveryTypes': function ()
                    {
                        return ydwidget.ipol_onlyDeliveryTypes;
                    }
                });

            });
        }
    </script>
	<?
}
else
{
	?>
    <script>
        console.log(<?=CUtil::PHPToJSObject($arResult)?>);
    </script>
	<?
} ?>
