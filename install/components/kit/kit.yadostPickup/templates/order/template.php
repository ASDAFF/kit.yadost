<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
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

	if (CKITYadostHelper::isConverted())
	{
		$dTS = Bitrix\Sale\Delivery\Services\Table::getList(array(
			'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
			'filter' => array('CODE' => 'kitYadost:%')
		));

		while ($dataShip = $dTS->Fetch())
		{
			$profileName = preg_replace("/kitYadost:/", "", $dataShip["CODE"]);
			$htmlIDs[$profileName] = 'ID_DELIVERY_ID_' . $dataShip['ID'];
			$deliveryIDs[$profileName] = $dataShip['ID'];
		}
	}
	else
	{
		$htmlIDs = array(
			"pickup" => 'ID_DELIVERY_kitYadost_pickup',
			"courier" => 'ID_DELIVERY_kitYadost_courier',
			"post" => 'ID_DELIVERY_kitYadost_post'
		);
		$deliveryIDs = array(
			"pickup" => "kitYadost:pickup",
			"courier" => "kitYadost:courier",
			"post" => "kitYadost:post"
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

	$widgetCode = COption::GetOptionString("kit.yadost", "basketWidget");

	$GLOBALS['APPLICATION']->AddHeadString(COption::GetOptionString("kit.yadost", "basketWidget"));
	$showWidgetOnProfileClick = ("Y" == COption::GetOptionString("kit.yadost", "showWidgetOnProfile", "N")) ? true : false;
	?>

    <!--suppress ALL, JSUnresolvedVariable, JSUnresolvedVariable -->
    <script type="text/javascript">
        if (typeof ydwidget !== "undefined")
        {
            ydwidget.ready(function ()
            {
                yd$('body').prepend('<div id="ydwidget" class="yd-widget-modal"></div>');

                // выполняется при первой загрузке виджета
                ydwidget.kit_onLoad = function ()
                {
                    if (typeof BX.Sale.OrderAjaxComponent !== "undefined") {
                        BX.Sale.OrderAjaxComponent.sendRequest('refreshOrderAjax');
                    }
                    ydwidget.kit_pvzAddressFull = "";
                    ydwidget.kit_orderForm = "#ORDER_FORM";
                    ydwidget.kit_oldTemplate = false;
                    ydwidget.kit_startHTML = false;


                    ydwidget.kit_addressInputs = <?=CUtil::PHPToJSObject($arAddressInputs)?>;

                    ydwidget.kit_showWidgetOnClick = <?=CUtil::PHPToJSObject($showWidgetOnProfileClick)?>;

                    ydwidget.kit_htmlIDs = <?=CUtil::PHPToJSObject($htmlIDs)?>;
                    ydwidget.kit_deliveryIDs = <?=CUtil::PHPToJSObject($deliveryIDs)?>;
                    ydwidget.kit_deliveryPrice = {};

                    ydwidget.kit_openWidgetTitles = {
                        "courier": "<?=GetMessage('KITyadost_JS_select_courier')?>",
                        "post": "<?=GetMessage('KITyadost_JS_select_post')?>",
                        "pickup": "<?=GetMessage('KITyadost_JS_select_pickup')?>",
                    };

                    if (yd$(ydwidget.kit_orderForm).length > 0)
                        ydwidget.kit_oldTemplate = true;
                    else
                        ydwidget.kit_orderForm = "#bx-soa-order-form";


                    if (typeof ydwidget.kit_currentCity == "undefined")
                        ydwidget.kit_currentCity = '<?=$arResult["CITY_NAME"]?>';

                    ydwidget.kit_selectPickupBtn = {};
                    ydwidget.kit_deliveryDataSaved = {};

                    // определяем выбрана ли сейчас delivery и заодно формируем кнопки выбрать ПВЗ для каждого профиля
                    for (var key in ydwidget.kit_htmlIDs)
                    {
                        var profileKey = ydwidget.kit_getTariffAccordingKey(key);

                        ydwidget.kit_selectPickupBtn[key] = '<a href="javascript:void(0);" data-ydwidget-open data-ydwidget-profile = "' + key + '" onclick="ydwidget.kit_openWidget(\'' + profileKey + '\');">' + ydwidget.kit_openWidgetTitles[key] + '</a>';

                        var deliveryRadio = yd$("#" + ydwidget.kit_htmlIDs[key]);
                        if (typeof deliveryRadio != "undefined")
                            if (deliveryRadio.length > 0)
                                if (ydwidget.kit_oldTemplate)
                                {
                                    if (deliveryRadio.attr("checked") == "checked")
                                        ydwidget.kit_currentdelivery = ydwidget.kit_deliveryIDs[key];
                                }
                                else
                                {
                                    if (deliveryRadio.prop("checked"))
                                        ydwidget.kit_currentdelivery = ydwidget.kit_deliveryIDs[key];
                                }
                    }
                    ;

                    // навешиваем обработчик на отправку формы
                    ydwidget.kit_onSubmitForm();

                    // переопределяем функцию обновления формы, чтобы сохранять адрес перед отправкой и запретить оф заказа, если не указан для профиля вариант в виджете
                    if (!ydwidget.kit_oldTemplate)
                    {
                        BX.Sale.OrderAjaxComponent.kit_oldSendRequest = BX.Sale.OrderAjaxComponent.sendRequest;

                        BX.Sale.OrderAjaxComponent.sendRequest = function (action, actionData)
                        {
                            if (!ydwidget.cartWidget.isOpened)
                                ydwidget.kit_beforeSubmitAddress = ydwidget.kit_getAddressInput();

                            if (action == "saveOrderAjax" && !ydwidget.kit_checkOrderCreate())
                                ydwidget.kit_denieOrderCreate();
                            else
                                BX.Sale.OrderAjaxComponent.kit_oldSendRequest(action, actionData);
                        }
                    }

                    // навешиваем обработчики на открытие блока доставок
                    if (!ydwidget.kit_oldTemplate)
                    {
                        yd$('#bx-soa-delivery .bx-soa-section-title-container').on('click', function ()
                        {
                            ydwidget.kit_initJS();
                        });
                        yd$('#bx-soa-delivery .bx-soa-section-title-container a').on('click', function ()
                        {
                            ydwidget.kit_initJS();
                        });
                    }

                    // запускаем скрипты обновления формы
                    ydwidget.kit_initJS();

                    // ==== подписываемся на перезагрузку формы
                    if (typeof(BX) && BX.addCustomEvent)
                        BX.addCustomEvent('onAjaxSuccess', ydwidget.kit_initJS);

                    // Для старого JS-ядра
                    if (window.jsAjaxUtil) // Переопределение Ajax-завершающей функции для навешивания js-событий новым эл-там
                    {
                        jsAjaxUtil._CloseLocalWaitWindow = jsAjaxUtil.CloseLocalWaitWindow;
                        jsAjaxUtil.CloseLocalWaitWindow = function (TID, cont)
                        {
                            jsAjaxUtil._CloseLocalWaitWindow(TID, cont);
                            ydwidget.kit_initJS();
                        }
                    }
                };

                // открывает виджет с нужным профилем
                ydwidget.kit_openWidget = function (profile)
                {
                    ydwidget.kit_saveAddressData(profile);
                    ydwidget.kit_onlyDeliveryTypes = [profile];
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
                        
                        if (ydwidget.kit_chosenDeliveryType == "courier" || ydwidget.kit_chosenDeliveryType == "post")
                            $toggleBlock.addClass("postCourier");
                        else
                            $toggleBlock.removeClass("postCourier");
					}, 4000);
					   
                    return false;
                }

                // сохраняем адрес указанный, чтобы его вернуть, если выбрали иной способ доставки
                ydwidget.kit_saveAddressData = function (profile)
                {
                    if (typeof profile == "undefined")
                        profile = false;

                    var addressValue = ydwidget.kit_getAddressInput();

                    if (!ydwidget.kit_savedAddress)
                        ydwidget.kit_savedAddress = addressValue;

                    // тут тонкий момент, либо в виджете открывают самовывоз, либо был не самовывоз в форме и она обновилась UPDATE_STATE, тогда сохраняем адрес
                    if (typeof ydwidget.kit_chosenDeliveryType != "undefined")
                        if (ydwidget.kit_chosenDeliveryType != "pickup" && profile == "pickup")
                            ydwidget.kit_savedAddress = addressValue;
                        else if (profile == false && ydwidget.kit_chosenDeliveryType != "pickup" && !ydwidget.cartWidget.isOpened)
                        {
                            if (ydwidget.kit_oldTemplate)
                                ydwidget.kit_savedAddress = ydwidget.kit_beforeSubmitAddress;
                        }
                }

                // получение соответсвий по названиям тарифов
                ydwidget.kit_getTariffAccording = function ()
                {
                    return {
                        "TODOOR": "courier",
                        "POST": "post",
                        "PICKUP": "pickup"
                    };
                }

                // получение соответсвий по названиям блока адреса
                ydwidget.kit_getAddressAccording = function ()
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
                ydwidget.kit_getTariffAccordingKey = function (key)
                {
                    var according = ydwidget.kit_getTariffAccording();

                    for (var i in according)
                        if (according[i] == key)
                            return i.toLowerCase();

                    return false;
                }

                // проверка выбран ли профиль ЯД
                ydwidget.kit_checkCurrentDelivery = function ()
                {
                    for (var key in ydwidget.kit_htmlIDs)
                        if (ydwidget.kit_currentdelivery == ydwidget.kit_deliveryIDs[key])
                            return key;

                    return false;
                }

                // получение значения поля на форме при аякс обновлении
                ydwidget.kit_getDataFromAjax = function (inputName, returnType)
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
                ydwidget.kit_setTariffInfo = function ()
                {
                    var addrHTML = "";

                    if (ydwidget.kit_oldTemplate)
                    {
                        for (var key in ydwidget.kit_selectPickupTag)
                        {
                            if (ydwidget.kit_selectPickupTag[key])
                            {
                                if (typeof ydwidget.kit_pvzAddress[key] != "undefined" && ydwidget.kit_pvzAddress[key] != "undefined")
                                    addrHTML += ydwidget.kit_pvzAddress[key];

                                if (ydwidget.kit_selectPickupTag[key])
                                    if (!ydwidget.kit_selectPickupTag[key].data("ydButtonSet"))
                                    {
                                        if (yd$("#kit_pvz_address_block").length <= 0)
                                        {
                                            addrHTML = "<span id = 'kit_pvz_address_block'>" + addrHTML + "</span>";
                                            ydwidget.kit_selectPickupTag[key].before(addrHTML);
                                        }
                                        else
                                            yd$("#kit_pvz_address_block").html(addrHTML);

                                        ydwidget.kit_selectPickupTag[key].append(ydwidget.kit_selectPickupBtn[key]);
                                        ydwidget.kit_selectPickupTag[key].data("ydButtonSet", true);
                                    }
                            }
                        }
                    }
                    else
                    {
                        var key = ydwidget.kit_checkCurrentDelivery();

                        if (typeof ydwidget.kit_pvzAddress[key] != "undefined" && ydwidget.kit_pvzAddress[key] != "undefined")
                            addrHTML = ydwidget.kit_pvzAddress[key];

                        if (ydwidget.kit_selectPickupTag[key])
                        {
                            if (yd$("#kit_pvz_address_block").length <= 0)
                            {
                                addrHTML = "<span id = 'kit_pvz_address_block'>" + addrHTML + "</span>";
                                ydwidget.kit_selectPickupTag[key].before(addrHTML);
                            }
                            else
                                yd$("#kit_pvz_address_block").html(addrHTML);

                            ydwidget.kit_selectPickupTag[key].html(ydwidget.kit_selectPickupBtn[key]);
                        }
                    }
                };

                // подписка на отправку формы, не даем оф заказ с профилем яд и не указанным вариантом в виджете
                ydwidget.kit_onSubmitForm = function ()
                {
                    // не даем отправить форму, если выбрана ddelivery и не заполнены данные dataSave
                    yd$(ydwidget.kit_orderForm).on("submit", function (e)
                    {
                        // сохраняем адрес перед отправкой формы
                        if (!ydwidget.cartWidget.isOpened)
                            ydwidget.kit_beforeSubmitAddress = ydwidget.kit_getAddressInput();

                        if (!ydwidget.kit_checkOrderCreate())
                            ydwidget.kit_denieOrderCreate(e);

                        return true;
                    });
                }

                // проверка на возможность создания заказа
                ydwidget.kit_checkOrderCreate = function ()
                {
                    if (ydwidget.kit_checkCurrentDelivery())
                    {
                        var dataSave = yd$("#yd_deliveryData").val(),
                            confirmorder = yd$("#confirmorder").val();

                        if (ydwidget.kit_oldTemplate)
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
                ydwidget.kit_denieOrderCreate = function (e)
                {
                    // добавляем кнопку для открытия виджета на форму, иначе при неоткрытом блоке доставок, нажатие на оформить заказа ничего не делает
                    ydwidget.kit_addInvisibleButton();

                    if (ydwidget.kit_oldTemplate)
                    {
                        yd$("[data-ydwidget-profile=" + ydwidget.kit_checkCurrentDelivery() + "]").click();

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

                        yd$("[data-ydwidget-profile=" + ydwidget.kit_checkCurrentDelivery() + "]").click();
                    }

                    ydwidget.kit_delInvisibleButton();
                }

                // добавление невидимой кнопки для открытия виджета
                ydwidget.kit_addInvisibleButton = function ()
                {
                    var buttonObj = yd$("[data-ydwidget-profile=" + ydwidget.kit_checkCurrentDelivery() + "]");
                    if (buttonObj.length <= 0)
                        yd$(ydwidget.kit_orderForm).append("<div id = 'ydmlab_fake_widget_link' style='display:none'>" + ydwidget.kit_selectPickupBtn[ydwidget.kit_checkCurrentDelivery()] + "</div>");
                }

                // удаление невидимой кнопки для открытия виджета
                ydwidget.kit_delInvisibleButton = function ()
                {
                    yd$("#ydmlab_fake_widget_link").remove();
                }

                // Инициализация JS - замена кнопки "Расчитать..." на выбор ПВЗ, проставление нужного ПВЗ и получение нужных параметров после обновления страницы по Ajax
                ydwidget.kit_initJS = function (ajaxAns)
                {
                    var newTemplateAjax = (typeof(ajaxAns) != 'undefined' && ajaxAns !== null && typeof(ajaxAns.KITyadost) == 'object') ? true : false;

                    // выбранная доставка
                    if (ydwidget.kit_oldTemplate)
                    {
                        if (tmpVal = ydwidget.kit_getDataFromAjax("yd_ajaxDeliveryID", "value"))
                            ydwidget.kit_currentdelivery = tmpVal;
                    }
                    else if (newTemplateAjax)
                        ydwidget.kit_currentdelivery = ajaxAns.KITyadost.yd_ajaxDeliveryID;

                    var curCheckedProfile = ydwidget.kit_checkCurrentDelivery();

                    // проверяем по ответу это не UPDATE_STATE, нет - обновляем сохраненный адрес
                    if (typeof ajaxAns == "undefined" || !(typeof ajaxAns == "object" && typeof ajaxAns["MESSAGE"] == "object" && ajaxAns["ERROR"] == ""))
                        ydwidget.kit_saveAddressData();

                    // если сменили профиль на Яндекс.Доставку и надо сразу открыть виджет
                    var openWidget = false;
                    if (
                        ydwidget.kit_showWidgetOnClick &&
                        curCheckedProfile &&
                        typeof ydwidget.kit_chosenDeliveryType != "undefined" &&
                        ydwidget.kit_chosenDeliveryType != curCheckedProfile
                    )
                        openWidget = true;

                    ydwidget.kit_chosenDeliveryType = curCheckedProfile;

                    // при смене профиля стираем данные о выбранной доставке из тега, либо выставляем его
                    if (
                        curCheckedProfile &&
                        typeof ydwidget.kit_deliveryDataSaved[curCheckedProfile] != "undefined"
                    )
                        ydwidget.kit_putDataToForm(ydwidget.kit_deliveryDataSaved[curCheckedProfile], "yd_deliveryData");
                    else
                        ydwidget.kit_putDataToForm(false, "yd_deliveryData");

                    // при смене профиля ставим на форму адрес пвз полный, если выбран самовывоз
                    if (curCheckedProfile == "pickup" && ydwidget.kit_pvzAddressFull)
                        ydwidget.kit_putDataToForm(ydwidget.kit_pvzAddressFull, "yd_pvzAddressValue");
                    else
                        ydwidget.kit_putDataToForm(false, "yd_pvzAddressValue");


                    // ставим на форму признак, что выбрана Яндекс доставка
                    var tmpDelivSelect = ydwidget.kit_getDataFromAjax("yd_is_select", "object");
                    if (!tmpDelivSelect)
                    {
                        yd$(ydwidget.kit_orderForm).append("<input type = 'hidden' value = '' name = 'yd_is_select'>");
                        tmpDelivSelect = ydwidget.kit_getDataFromAjax("yd_is_select", "object");
                    }

                    // впиливаем стоимость доставки
                    if (curCheckedProfile)
                        ydwidget.kit_putDataToForm(ydwidget.kit_deliveryPrice, "yd_ajaxDeliveryPrice");

                    if (ydwidget.kit_checkCurrentDelivery())
                        tmpDelivSelect.val("kitYadost");
                    else
                        tmpDelivSelect.val("false");

                    // цепляем значения с формы при обновлении по ajax
                    var tmpVal = false;
                    // тип плательщика
                    ydwidget.kit_personType = "<?=$arResult["PERSON_TYPE"]?>";
                    if (ydwidget.kit_oldTemplate)
                    {
                        if (tmpVal = ydwidget.kit_getDataFromAjax("yd_ajaxPersonType", "value"))
                            ydwidget.kit_personType = tmpVal;
                    }
                    else if (newTemplateAjax)
                        ydwidget.kit_personType = ajaxAns.KITyadost.yd_ajaxPersonType;

                    // инпут адреса
                    if (typeof ydwidget.kit_addressInputs[ydwidget.kit_personType]["address"] != "undefined")
                        ydwidget.kit_addrInp = ydwidget.kit_getDataFromAjax("ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["address"], "object");

                    // город
                    if (ydwidget.kit_oldTemplate)
                    {
                        if (tmpVal = ydwidget.kit_getDataFromAjax("yd_ajaxLocation", "value"))
                            ydwidget.kit_currentCity = tmpVal;
                    }
                    else if (newTemplateAjax)
                        ydwidget.kit_currentCity = ajaxAns.KITyadost.yd_ajaxLocation;

                    // место, где будет кнопка "выбрать ПВЗ"
                    ydwidget.kit_selectPickupTag = {
                        "pickup": yd$('#kit_yadost_inject_pickup'),
                        "post": yd$('#kit_yadost_inject_post'),
                        "courier": yd$('#kit_yadost_inject_courier')
                    };

                    var addressValue = ydwidget.kit_getAddressInput();

                    if (addressValue && ydwidget.kit_pvzAddressFull != "") // Если у нас есть адрес выбранного ПВЗ....
                    {
                        //...и он соответствует вдресу в инпуте адреса, то надо заблокировать инпут выбора адреса
                        if (ydwidget.kit_pvzAddressFull && ydwidget.kit_chosenDeliveryType == "pickup")
                        {
                            ydwidget.kit_setAddressInput(ydwidget.kit_pvzAddressFull);
                            ydwidget.kit_blockAddressInput(true);
                        }
                        else
                        {
                            // выставляем адрес сохраненный до пвз, разблокируем поле
                            ydwidget.kit_blockAddressInput(false);
                            if (ydwidget.kit_savedAddress)
                                ydwidget.kit_setAddressInput(ydwidget.kit_savedAddress);
                        }
                    }


                    if (!ydwidget.kit_pvzAddress)
                        ydwidget.kit_pvzAddress = {}; // Если ПВЗ не выбран.

                    // Тут ставим кнопку "выбрать ПВЗ"
                    ydwidget.kit_setTariffInfo();

                    // открываем виджет, если необходимо
                    if (openWidget)
                    {
                        ydwidget.kit_addInvisibleButton();
                        yd$("[data-ydwidget-profile=" + ydwidget.kit_checkCurrentDelivery() + "]").click();
                        ydwidget.kit_delInvisibleButton();
                    }
                };

                // вписывает на форму данные в тег
                ydwidget.kit_putDataToForm = function (data, tagID)
                {
                    var tmpInput = ydwidget.kit_getDataFromAjax(tagID, "object");

                    // удаляем тег, если данные пустые
                    if (!data && tmpInput)
                    {
                        tmpInput.remove();
                        return;
                    }

                    if (tmpInput)
                        tmpInput.val(JSON.stringify(data));
                    else
                        yd$(ydwidget.kit_orderForm).append("<input type = 'hidden' value = '" + JSON.stringify(data) + "' name = '" + tagID + "' id = '" + tagID + "'>");
                }

                // в виджете выбрали вариант доставки, обрабатываем
                ydwidget.kit_onDeliveryChange = function (delivery, isAjax)
                {
                    console.log({"delivery": delivery});

                    if (!delivery)
                    {
                        ydwidget.kit_pvzAddressFull = '';
                        ydwidget.kit_pvzId = '';
                        ydwidget.kit_pvzAddress = {},
                            yd$("#yd_deliveryData").remove();
                        ydwidget.kit_setTariffInfo();
                        return;
                    }

                    // заменяем в комментарии кавычки иначе не переводятся данные нормально в json
                    if (typeof delivery.address != "undefined")
                        if (typeof delivery.address.comment != "undefined")
                            if (delivery.address.comment != null)
                                delivery.address.comment = delivery.address.comment.replace(/\\?("|')/g, '\\$1');

                    var deliveryTypesSeq = ydwidget.kit_getTariffAccording(),
                        deliveryKey = deliveryTypesSeq[delivery.type];

                    // запоминаем новую стоимость доставки
                    ydwidget.kit_deliveryPrice[deliveryKey] = {
                        "price": delivery.costWithRules,
                        "term": delivery.days,
                        "provider": delivery.delivery.name
                    };

                    // вставляем на форму в тег данные выбранного варианта доставки
                    delivery.yadostCity = ydwidget.kit_currentCity;
                    ydwidget.kit_deliveryDataSaved[deliveryKey] = delivery;
                    ydwidget.kit_putDataToForm(ydwidget.kit_deliveryDataSaved[deliveryKey], "yd_deliveryData");

                    // впиливаем стоимость доставки
                    ydwidget.kit_putDataToForm(ydwidget.kit_deliveryPrice, "yd_ajaxDeliveryPrice");

                    // запомнили текущий профиль доставки
                    ydwidget.kit_chosenDeliveryType = deliveryKey;

                    // формируем адрес ПВЗ
                    ydwidget.kit_createAddress(delivery);

                    if (deliveryKey == "pickup")
                    {
                        // Сохраняем параметры выбранного ПВЗ, чтобы после Ajax-перезагрузки формы знать какой ПВЗ был выбран.
                        ydwidget.kit_pvzId = delivery.pickuppointId;

                        ydwidget.kit_setAddressInput(ydwidget.kit_pvzAddressFull);

                        if (typeof isAjax == 'undefined')// Блокируем поле адреса.
                            ydwidget.kit_blockAddressInput(true);
                    }
                    else
                    {
                        if (typeof isAjax == 'undefined')// Разблокируем поле адреса.
                            ydwidget.kit_blockAddressInput(false);

                        // выставляем адрес, который сохранили ранее, когда был выбран ПВЗ
                        ydwidget.kit_setAddressInput(ydwidget.kit_savedAddress);

                        if (deliveryKey == "post")
                        {
                            var addressAccording = ydwidget.kit_getAddressAccording(),
                                autoComplitAddr = ydwidget.cartWidget.getAddress();
                             console.log({"ydwidget.cartWidget.getAddress": autoComplitAddr});

                            if (typeof autoComplitAddr != "undefined" && autoComplitAddr != null)
                                if (typeof ydwidget.kit_addressInputs[ydwidget.kit_personType]["address"] != "undefined")
                                {
                                    var addr = autoComplitAddr["index"];
                                    addr += ", " + autoComplitAddr["city"];
                                    addr += ", " + autoComplitAddr["street"];
                                    addr += ", " + autoComplitAddr["house"];

                                    if (typeof autoComplitAddr["building"] != "undefined" && autoComplitAddr["building"] != null)
                                        addr += ", " + autoComplitAddr["building"];

                                    ydwidget.kit_setAddressInput(addr);

                                    // выставляем индекс
                                    if (typeof ydwidget.kit_addressInputs[ydwidget.kit_personType]["index"] != "undefined")
                                    {
                                        var selector = "[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["index"] + "]";
                                        yd$(selector).val(autoComplitAddr["index"]);
                                        yd$(selector).html(autoComplitAddr["index"]);
                                    }
                                }
                                else
                                    for (var i in autoComplitAddr)
                                    {
                                        if (typeof ydwidget.kit_addressInputs[ydwidget.kit_personType][addressAccording[i]] != "undefined")
                                        {
                                            var selector = "[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType][addressAccording[i]] + "]";
                                            yd$(selector).val(autoComplitAddr[i]);
                                            yd$(selector).html(autoComplitAddr[i]);
                                        }
                                    }
                        }
                    }

                    // выставляем улицу, дом, квартиру, офис, если указаны коды в настройках
                    ydwidget.kit_setAddressInputs(delivery.address, delivery.type);

                    //Выводим подпись о выбранном ПВЗ рядом с кнопкой "Выбрать ПВЗ"
                    ydwidget.kit_setTariffInfo();

                    // закрыли виджет
                    ydwidget.cartWidget.close();

                    // Перезагружаем форму (с применением новой стоимости доставки)
                    if (ydwidget.kit_oldTemplate)
                    {
                        if (typeof isAjax == 'undefined')
                        {
                            var clickObj = yd$('#' + ydwidget.kit_htmlIDs[ydwidget.kit_chosenDeliveryType]);
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
                ydwidget.kit_setAddressInputs = function (addressObj, deliveryType)
                {
                    if (!addressObj ||  deliveryType == 'POST')
                        return false;

                    var addressAccording = ydwidget.kit_getAddressAccording();

                    for (var i in ydwidget.kit_addressInputs[ydwidget.kit_personType])
                    {
                        var inputID = ydwidget.kit_addressInputs[ydwidget.kit_personType][i],
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
                ydwidget.kit_setAddressInput = function (value)
                {
                    if (ydwidget.kit_addrInp)
                    {
                        if (ydwidget.kit_oldTemplate)
                        {
                            ydwidget.kit_addrInp.val(value);
                            ydwidget.kit_addrInp.html(value);
                        }
                        else
                        {
                            ydwidget.kit_addrInp.html(value);
                            ydwidget.kit_addrInp.val(value);
                        }
                    }
                }

                // получает текущее значение в поле адреса
                ydwidget.kit_getAddressInput = function ()
                {
                    var addressValue = false;

                    if (ydwidget.kit_addrInp)
                        if (ydwidget.kit_oldTemplate)
                            addressValue = ydwidget.kit_addrInp.val();
                        else
                            addressValue = ydwidget.kit_addrInp.val();

                    return addressValue;
                }

                // блокирует поле адреса
                ydwidget.kit_blockAddressInput = function (block)
                {
                    if (typeof block == "undefined")
                        block = true;

                    // console.log({"block": block});
                    if (ydwidget.kit_addrInp)
                    {
                        if (block)
                        {
                            ydwidget.kit_addrInp
                            // .css('background-color', '#eee')
                                .addClass('yd_disabled')
                                .bind("change", ydwidget.kit_blockChangeAddr)
                                .bind("keyup", ydwidget.kit_blockChangeAddr);

                            // ydwidget.kit_pvzAddressBlocked = true;
                        }
                        else
                        {
                            ydwidget.kit_addrInp
                            // .css('background-color', '#eee')
                                .removeClass('yd_disabled')
                                .unbind("change", ydwidget.kit_blockChangeAddr)
                                .unbind("keyup", ydwidget.kit_blockChangeAddr);

                            // ydwidget.kit_pvzAddressBlocked = false;
                        }
                    }
                }

                // формируем адрес и подпись для профиля яд
                ydwidget.kit_createAddress = function (delivery)
                {
                    var address = '<span style="font-size:11px">';

                    if (ydwidget.kit_chosenDeliveryType == "pickup")
                    {
                        // адрес для самовывоза
                        ydwidget.kit_pvzAddressFull = '<?=GetMessage('KITyadost_JS_PICKUP')?>: ';
                        ydwidget.kit_pvzAddressFull += delivery.full_address + ' | ';
                        ydwidget.kit_pvzAddressFull += delivery.days + ' <?=GetMessage('KITyadost_JS_DAY')?> | ';
                        ydwidget.kit_pvzAddressFull += ' #' + delivery.pickuppointId;

                        address += delivery.address.street + '<br>';
                    }

                    address += '</span><br>';

                    ydwidget.kit_pvzAddress[ydwidget.kit_chosenDeliveryType] = address;
                }

                // Ф-ция которая не дает поменять адрес доставки при выбранном ПВЗ
                ydwidget.kit_blockChangeAddr = function ()
                {
                    if (ydwidget.kit_oldTemplate)
                    {
                        yd$(this).html(ydwidget.kit_pvzAddressFull);
                        yd$(this).val(ydwidget.kit_pvzAddressFull);
                    }
                    else
                    {
                        yd$(this).val(ydwidget.kit_pvzAddressFull);
                        yd$(this).html(ydwidget.kit_pvzAddressFull);
                    }
                }

                // устанавливаем настройки виджета
                ydwidget.initCartWidget({
                    //получить указанный пользователем город
                    'getCity': function ()
                    {
                        var city = '<?=$arResult["CITY_NAME"]?>';

                        if (ydwidget.kit_currentCity)
                            city = ydwidget.kit_currentCity;

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
                            return yd$("[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["fname"] + "]").val()
                        },
                        'recipient_last_name': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["lname"] + "]").val()
                        },
                        'recipient_phone': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["phone"] + "]").val()
                        },
                        'deliverypoint_street': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["street"] + "]")
                        },
                        'deliverypoint_house': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["house"] + "]").val()
                        },
                        'deliverypoint_index': function ()
                        {
                            return yd$("[name=ORDER_PROP_" + ydwidget.kit_addressInputs[ydwidget.kit_personType]["index"] + "]").val()
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
                        ydwidget.kit_onLoad();
                    },

                    'onDeliveryChange': function (delivery)
                    {
                        ydwidget.kit_onDeliveryChange(delivery);
                    },

                    // 'unSelectMsVariant': function () { yd$('#ms_delivery').prop('checked', false) },
                    // 'selectMsVariant': function () { yd$('#ms_delivery').prop('checked', true) },

                    //создавать заказ в cookie для его последующего создания в Яндекс.Доставке только если выбрана доставка Яндекса
                    'createOrderFlag': function ()
                    {
                        return ydwidget.kit_checkCurrentDelivery() ? true : false;
                    },

                    //запустить сабмит формы, когда валидация успешно прошла и заказ создан в cookie,
                    //либо если createOrderFlag вернул false
                    'runOrderCreation': function ()
                    {
                        return false;
                    },

                    'onlyDeliveryTypes': function ()
                    {
                        return ydwidget.kit_onlyDeliveryTypes;
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
