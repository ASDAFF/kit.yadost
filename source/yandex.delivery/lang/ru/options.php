<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$MESS ['TRADE_YANDEX_DELIVERY_DEMO_MODE_EXPIRED'] = "Настройки модуля недоступны, так как не удалось подключить функционал модуля. Проверьте не истек ли демо-режим.<br>Если модуль был активирован по купону, и настройки недоступны, то требуется деинсталировать модуль и установить повторно, тогда он будет работать в боевом режиме. Сделать это можно в<br>Админке - Marketplace - Установленные решения. После удаления модуля, он появится в таблице доступных модулей, из нее его можно установить. При повторной установке не требуется указывать настройки модуля снова, они будут сохранены.";

$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_MSOPTIONS'] = "Настройки обмена Яндекс.Доставки";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_SITESETTINGS'] = "Настройки сайта";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_DIMENSIONS'] = "Габариты товаров";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_DEFAULT_SENDERS_VAL'] = "Значения отправителя по умолчанию";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_PROPS'] = "Свойства заказа";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_COURIER'] = "Параметры доставки посылок на склад Яндекс.Доставки";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_GOODS'] = "Свойства товаров";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_DELIVS'] = "Настройки компонента";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_PAYERS'] = "Типы плательщиков";
$MESS ['TRADE_YANDEX_DELIVERY_OPTTAB_PAYSYSDEPTH'] = "Платежные системы, при которых курьер <b>не</b> берет деньги с покупателя";

$MESS ['TRADE_YANDEX_DELIVERY_FAQ_SETUP_TITLE'] = "Настройка модуля";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_SETUP_DESCR'] = "Перед началом использования модуля необходимо заполнить все настройки, руководствуясь пояснениями к пунктам. Особое внимание необходимо уделить группе настроек 'Настройки обмена Яндекс.Доставки', которые отвечают за получение доступа к АПИ Яндекс.Доставки.<br><br>После завершения настройки, активируйте модуль, нажав на кнопку 'Активировать' выше.<br><br>
Проверьте, чтобы у вас в <a href='/bitrix/admin/sale_location_admin.php'>настройках магазина</a> был создан список местоположений для доставки. Если у вас нет Местоположений, запустите импорт местоположений.<br>
Проверьте, чтобы в <a href='/bitrix/admin/sale_order_props.php'>свойствах заказа</a> было свойство помеченное как Местоположение. Если такого свойства нет, создайте его.
<br><br>";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_ADD_TITLE'] = "Дополнительные возможности";

$MESS ['TRADE_YANDEX_DELIVERY_FAQ_TROUBLE_TITLE'] = "Решение проблем";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_TROUBLE_DESCR'] = "
<ul><li>При выборе службы доставки слетает информация о сроке доставки<br><br>
Эта проблема встречается в 14-й версии битрикса при использовании стандартного шаблона оформления заказа. Для ее исправления необходимо в шаблоне компонента оформления заказа (sale.order.ajax) найти файл delivery.php, в котором подключать компонент bitrix:sale.ajax.yandexDelivery.calculator в любом случае ( достаточно строку <i>if(\$arProfile[\"CHECKED\"] == \"Y\" && doubleval(\$arResult[\"DELIVERY_PRICE\"]) > 0):</i> привести к виду <i>if(\$arProfile[\"CHECKED\"] == \"Y\" && doubleval(\$arResult[\"DELIVERY_PRICE\"]) > 0 && false):</i> ).<br><br>
</li><li>Не отображается ссылка для открытия окна выбора ПВЗ<br><br>
Возможно у вас используется модифицированный шаблон оформления заказа и скрипт не может проставить ссылку. Для решения этой проблемы поставьте определенный ID элементу, в котором должна выводиться ссылка и введите его значение в настройку \"ID элемента, в который добавлять ссылку на выбор ПВЗ ( Пункты Выдачи Заказов )\".<br><br>
</li><li>Не появляются службы доставки или присутствуют другие проблемы<br><br>
В любой непонятной ситуации следует первым делом попробовать <a href='/bitrix/admin/cache.php' target='_blank'>очистить кэш битрикса</a>.<br><br>
</li><li>Отображается кнопка 'расcчитать стоимость' напротив служб доставки, вместо уже раcсчитанной стоимости<br><br>
Для того, чтобы стоимость расчитывалась автоматом, необходимо в параметрах компонента оформления заказа (sale.order.ajax) поставить галочку 'Рассчитывать стоимость доставки сразу'.</li>
</ul>
";

$MESS ['TRADE_YANDEX_DELIVERY_WARN_REQUIRE_OPTIONS'] = "<b style = 'color:red'>Важно!</b> Настройки \"Конфигурационный файл\", \"Код корзинного виджета\", \"Город отправления\" являются критическими для работы модуля, без их корректного заполнения работа модуля невозможна.";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_delivery_type_import_widthdraw'] = "Тип отгрузки по умолчанию";
$MESS ['TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_import'] = "Самопривоз";
$MESS ['TRADE_YANDEX_DELIVERY_INPUTS_delivery_type_withdraw'] = "Забор";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_to_yd_warehouse'] = "Использовать склад Яндекс.Доставки";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_cityFrom'] = "Город отправления";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_default_sender'] = "ID отправителя по умолчанию";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_default_warehouse'] = "ID склада по умолчанию";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_cityFrom'] = "Город отправления";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_dontSendOrder'] = "Отправлять заказ в Яндекс.Доставку: ";
$MESS ['TRADE_YANDEX_DELIVERY_OPTVAR_dSR_odrCrt'] = "при оформлении заказа";
$MESS ['TRADE_YANDEX_DELIVERY_OPTVAR_dSR_flgRzd'] = "разрешением доставки";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_sides'] = "Код свойства с размерами в формате дшв";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_sidesUnitSprtr'] = "Символ-разделитель";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_MEA_SEP_L'] = "Код свойства с длиной товара";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_MEA_SEP_W'] = "Код свойства с шириной товара";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_MEA_SEP_H'] = "Код свойства с высотой товара";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_MEA_DEF'] = "Габариты будут взяты из стандартных параметров каталога (доступно для 14-й и выше версии Bitrix)";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_lengthD'] = "Длина товара, <span class='MeasLbl'>cm<span>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_widthD'] = "Ширина товара, <span class='MeasLbl'>cm<span>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_heightD'] = "Высота товара, <span class='MeasLbl'>cm<span>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_weightD'] = "Вес заказа по умолчанию, <span class='WeightLbl'>kg<span>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_assessedCost'] = "Оценочная стоимость заказа";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_assessedCostPercent'] = "Оценочная стоимость заказа в процентах";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_artnumber'] = "Код свойства товара используемого как артикул";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_site_selection'] = "Выберите сайт/сайты для работы с модулем";

$MESS ['TRADE_YANDEX_DELIVERY_OPT_addressMode'] = "Поле адрес:";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_oldTemplate'] = "На сайте используется старый шаблон компонента sale.order.ajax <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-oldTemplate\",$(this));'></a>";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_addressMode_one'] = "Единое поле адреса";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_addressMode_sep'] = "Раздельные поля адреса";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_fname'] = "Имя";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_lname'] = "Фамилия";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_mname'] = "Отчество";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_phone'] = "Телефон";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_index'] = "Индекс";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_email'] = "Email";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_address'] = "Адрес <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-OPT_address\",$(this));'></a>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_house'] = "Дом";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_build'] = "Корпус/блок/строение";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_street'] = "Улица";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_flat'] = "Квартира/офис";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_deliv_Fast'] = "Самый быстрый до двери";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_deliv_Cheap'] = "Самый дешёвый до двери";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_deliv_Balance'] = "Сбалансированный до двери";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_deliv_All'] = "Все доступные варианты до двери";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_signDeliv'] = "Выводить названия служб доставки";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_basketWidget'] = "Код корзинного виджета <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-basketWidget\",$(this));'></a>";

$MESS["TRADE_YANDEX_DELIVERY_OPT_courier_name"] = "Имя курьера";
$MESS["TRADE_YANDEX_DELIVERY_OPT_car_number"] = "Номер автомобиля";
$MESS["TRADE_YANDEX_DELIVERY_OPT_car_model"] = "Марка автомобиля";

$MESS["TRADE_YANDEX_DELIVERY_OPTTAB_STATUS"] = "Статусы заказа";

$MESS ['TRADE_YANDEX_DELIVERY_OPT_idOfPVZ'] = "ID элемента, в который добавлять ссылку на выбор ПВЗ <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-idOfPVZ\",$(this));'></a>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_showWidgetOnProfile'] = "Открывать корзинный виджет при клике на профиль доставки сразу <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-showWidgetOnProfile\",$(this));'></a>";
$MESS ['TRADE_YANDEX_DELIVERY_OPT_isNewTmpl'] = "Использовать новый шаблон вывода иконок доставки <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-isNewTmpl\",$(this));'></a>";


$MESS ['TRADE_YANDEX_DELIVERY_HELPER_basketWidget'] = "В поле необходимо вставить код корзинного виджета, который можно получить в личном кабинете на <a target='_blank' href='https://delivery.yandex.ru'>сайте Яндекс.Доставки</a> в разделе Настройки - Интеграция - Виджеты - Корзинный виджет - Установить.<br>Появится код  вида &lt;script&gt;текст&lt;/script&gt;.";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_PROPS'] = "В полях необходимо выбрать <a target = '_blank' href='/bitrix/admin/sale_order_props.php'>коды свойств заказа</a>, которые будут переданы в службу доставки. Для разных типов плательщиков коды у однотипных свойств должны совпадать.<br><br>
<strong>Пояснения к полям:</strong><br>
Имя, фамилия, отчество - если на сайте эти данные заполняются в одном свойстве - укажите код во всех полях. Введенные данные будут разделены по пробелу.<br><br>
Адрес - поле, куда будут передаваться данные о самовывозе, так же адрес ПВЗ будет записан в свойство заказ. Если на сайте поля адреса разделены, то оставьте поле пустым.<br><br>
Для успешного функционирования вам так же необходимо иметь свойство заказа помеченное как 'Местоположение'. Если у вас отсутствует данное свойство, создайте его.<br><br>
";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_ADD_PROPS_BUTTON'] = "Автоматическая настройка свойств заказа, проверяет наличие обязательных свойств заказа. Свойство \"Адрес\" будет разделено на отдельные поля. Для каждого из свойств будет установлена привязка к соответсвующему профилю Яндекс.Доставки.<br>";

$MESS ['TRADE_YANDEX_DELIVERY_HELPER_oldTemplate'] = "Значение этой настройки влияет на работу скриптов, которые запускаются по нажатию кнопки \"Автоматически задать свойства\". В случае пропажи на странице оформления заказа свойств после автоматической настройки, попробуйте изменить данную данную настройку и повторить автоматическую настройку свойств заказа.<br>Старый и новый шаблон можно определить визуально, перейдите на страницу оформления заказа, определите шаблон по блоку доставок.
<div style = 'width: 100%'><b>Старый шаблон</b></div>
<div style = 'width: 100%'><img style = 'width: 100%' src = '/bitrix/images/yandex.delivery/oldTemplate.png'></div><div style = 'clear: both;'></div>
<div style = 'width: 100%'><b>Новый шаблон</b></div>
<div style = 'width: 100%'><img style = 'width: 100%' src = '/bitrix/images/yandex.delivery/newTemplate.png'></div>";

$MESS ['TRADE_YANDEX_DELIVERY_HELPER_dimMode_unit'] = "Если размеры товара хранятся в одном свойстве - необходимо ввести код этого свойства, а так же символ, который разделяет размеры. Например, есть свойство 'Габариты' в котором единой строкой значения хранятся в формате 10х30х50 - в таком случае символ разделитеть - это 'х'.";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_sidesMeas'] = "Для передачи службе доставки информации по габаритам товаров, необходимо выбрать единицы измерения и свойство, где хранятся габариты. В случае, если габариты не будут найдены, будут переданы габариты по умолчанию, подставленные в указанных единицах измерения.";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_OPT_address'] = "Если вы используете раздельное заполнение адреса (отдельно дом, улица и т.д.), оставьте данное поле пустым";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_OPT_articul'] = "Необходимо указать свойства товаров, которые будут передаваться в Яндекс.Доставку";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_STATUSES'] = "Укажите соответствие состяний посылки и статусов заказа на сайте. Указанные статусы будут выставлятся заказам при обмене данными с Яндекс.Доставкой, изменение статуса повлечет за собой все сопутсвующие события: отправку электронных писем, иное информирование, если оно предусмотрено.<br><br> Обмен статусами производится каждые 30 минут с помощью агента.<br><br>Если не требуется изменять статус при определенном сотсоянии, оставьте соответствие пустым.";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_idOfPVZ'] = "Если на странице оформления заказа выводится ссылка 'Выбрать вариант доставки', то настройку необходимо оставить пустой.<br><br>Если шаблон модифицирован, то в нем необходимо для Яндекс.Доставки вставить тег, в который будет выводится указанная ссылка, задать ему id и указать его в настройке.";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_showWidgetOnProfile'] = "Если установить эту опцию, то на странице оформления заказа, при клике на один из профилей Яндекс.Доставки, корзинный виджет будет открываться сразу без необходимости нажатия покупателем ссылки \"Выбрать вариант доставки\". При этом ссылка на открытие виджета будет сохранена и продолжит выполнять свои функции.";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_assessed_limits'] = "Каждая служба доставки обладает ограничениями по оценочной стоимости заказа. Если оценочная стоимость превысит указанные пределы, то соответсвующий профиль доставки на странице оформления заказа не будет отображаться. Укажите в данной настройке в процентах оценочную стоимость заказа от суммарной стоимости корзины заказа. Ограничение для различных городов получения идентичны.<br>Пример ограничений оценочной стоимости для города Москвы:";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_RightsNotAllow_SaveOption'] = "<span style = 'color: red'>Недостаточно прав!</span> Свяжитесь с администратором для возможности сохранения настроек модуля.<br>";
$MESS ['TRADE_YANDEX_DELIVERY_HELPER_RightsNotAllow_AutoPropFix'] = "<span style = 'color: red'>Недостаточно прав!</span> Свяжитесь с администратором для возможности автоматической настройки свойств заказа.<br>";


$MESS ['TRADE_YANDEX_DELIVERY_HEADER_MEASUREMENT'] = "Размеры товаров";
$MESS ['TRADE_YANDEX_DELIVERY_HEADER_MEASUREMENT_DEF'] = "Размеры заказа по умолчанию";
$MESS ['TRADE_YANDEX_DELIVERY_HEADER_WEIGHT'] = "Вес";
$MESS ['TRADE_YANDEX_DELIVERY_HEADER_DELIVTYPES'] = "Отображение доставок";
$MESS ['TRADE_YANDEX_DELIVERY_HEADER_DELIVSIGNS'] = "Подписи доставок";

$MESS ['TRADE_YANDEX_DELIVERY_LABEL_dimMode_unit'] = "Одним свойством <a href='#' class='PropHint' onclick='return trade_popup_virt(\"pop-dimMode_unit\",$(this));'></a>";

$MESS ['TRADE_YANDEX_DELIVERY_LABEL_dimMode_sep'] = "Раздельными свойствами";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_dimMode_def'] = "Из каталога";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_sidesMeas'] = "Единицы измерения";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_weiMode_cat'] = "Брать вес товара из каталога";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_weiMode_prop'] = "Брать вес товара из свойства";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_mm'] = "mm";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_cm'] = "cm";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_m'] = "m";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_g'] = "g";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_kg'] = "kg";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_LOADCONFIG'] = "Загрузить конфигурационный файл";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_HASCONFIG'] = "Конфигурационный файл загружен";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_NOCONFIG'] = "Конфигурационный файл не обнаружен";
$MESS ['TRADE_YANDEX_DELIVERY_LABEL_CONFIGORDER'] = "Порядок настройки";

$MESS ['TRADE_YANDEX_DELIVERY_BUTTON_LOAD'] = "Загрузить";
$MESS ['TRADE_YANDEX_DELIVERY_BUTTON_CANSEL'] = "Отмена";
$MESS ['TRADE_YANDEX_DELIVERY_BUTTON_CLEARCACHE'] = "Очистить кэш модуля";

$MESS ['TRADE_YANDEX_DELIVERY_TEXT_ABOUTCONFIG'] = "В окно необходимо вставить ключи к АПИ Яндекс.Доставки, которые можно получить в личном кабинете на <a target='_blank' href='https://delivery.yandex.ru/integration/index'>сайте Яндекс.Доставки</a> в разделе Интеграция -> API-ключи, на странице пройти по ссылке 'Получить'.<br>Открывшийся код надо скопировать в окно модуля.";

$MESS ['TRADE_YANDEX_DELIVERY_ALERT_noConfig'] = "Вставьте конфигурацию";
$MESS ['TRADE_YANDEX_DELIVERY_ALERT_configSaved'] = "Конфигурация сохранена";
$MESS ['TRADE_YANDEX_DELIVERY_ALERT_configNotSaved'] = "Конфигурация не сохранена, проверьте введенный ключ";
$MESS ['TRADE_YANDEX_DELIVERY_ALERT_cacheCleared'] = "Кэш модуля очищен";
$MESS ['TRADE_YANDEX_DELIVERY_ALERT_cacheNotCleared'] = "Кэш уже был очищен";

$MESS ['TRADE_YANDEX_DELIVERY_cashe'] = "наличные";

$MESS['TRADE_YANDEX_DELIVERY_NOT_CRTD_HEADER'] = "Служба Яндекс.Доставки не найдена";
$MESS['TRADE_YANDEX_DELIVERY_NOT_CRTD_TITLE'] = "Служба доставки не найдена. Необходимо добавить службу доставки: Магазин - Настройки - Службы доставки, нажать кнопку \"Добавить\", в меню выбрать \"Автоматизированная служба доставки\", во вкладке \"Настройки обработчика\" в настройке \"Служба доставки\" указать обработчик \"tradeDeliveryYandex\".";

$MESS['TRADE_YANDEX_DELIVERY_NO_ADOST_HEADER'] = "Служба Яндекс.Доставки отключена";
$MESS['TRADE_YANDEX_DELIVERY_NO_ADOST_TITLE'] = "Чтобы служба доставки отображалась на странице оформления заказа поставьте ей активность:<br>Магазин - Настройки - Службы доставки</a>.";

$MESS['TRADE_YANDEX_DELIVERY_NO_DOST_HEADER'] = "Служба Яндекс.Доставки удалена";
$MESS['TRADE_YANDEX_DELIVERY_NO_DOST_TITLE'] = "Служба доставки была удалена. Чтобы вернуть ее - переустановите модуль.";

// добавление/изменение свойств кнопкой
$MESS['TRADE_YANDEX_DELIVERY_propFix_Start'] = 'Автоматически задать свойства';

$MESS['TRADE_YANDEX_DELIVERY_propFix_PAYERS'] = 'Получение типов платильщиков...';
$MESS['TRADE_YANDEX_DELIVERY_propFix_persone'] = 'Проверка полей CONTACT_PERSONE или FIO';
$MESS['TRADE_YANDEX_DELIVERY_propFix_location'] = 'Проверка поля местоположения';
$MESS['TRADE_YANDEX_DELIVERY_propFix_address'] = 'Проверка поля ADDRESS';
$MESS['TRADE_YANDEX_DELIVERY_propFix_zip'] = 'Проверка поля ZIP';

$MESS['TRADE_YANDEX_DELIVERY_propFix_testType'] = 'Проверка типа';
$MESS['TRADE_YANDEX_DELIVERY_propFix_resFields'] = 'Добавлены/изменены следующие поля: ';

$MESS['TRADE_YANDEX_DELIVERY_propFix_error'] = 'Ошибка: ';
$MESS['TRADE_YANDEX_DELIVERY_propFix_continue'] = 'Продолжить';
$MESS['TRADE_YANDEX_DELIVERY_propFix_stop'] = 'Остановить';
$MESS['TRADE_YANDEX_DELIVERY_propFix_finished'] = '--- Настройка завершена, изменения в настройках станут доступны после <a href = "#" onClick = "window.location = window.location;">перезагрузки страницы</a> ---';

$MESS ['TRADE_YANDEX_DELIVERY_FAQ_TAB_RIGHTS'] = "Права доступа";

//FAQ
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_TAB_SETUP'] = "Помощь";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_HDR_SETUP'] = "Установка";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_WTF_TITLE'] = "- Для чего нужен модуль";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_WTF_DESCR'] = "Модуль обеспечивает интеграцию Интернет-магазина со службой <a href = 'https://delivery.yandex.ru'>Яндекс.Доставки</a>. В модуле присутствует компонент с двумя шаблонами для подключения корзинного виджета на странице оформления заказа и возможности подключения иных виджетов в теле сайта. Модуль обеспечивает отправку заявок на доставку заказов, мониторинг статусов доставки заказов и выставление соответствующих им статусов в админке Битрикса. Предусмотрен функционал печати актов и товарных накладных для заказов.<br>";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_HIW_TITLE'] = "- Как работает модуль";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_HIW_DESCR'] = "Состав модуля:
<ul>
	<li>функционал автоматизированной службы доставки;</li>
	<li>функционал расчета габаритов заказа;</li>
	<li>функионал расчета стоимости доставки;</li>
	<li>функционал отображения информации о пунктах самовывоза;</li>
	<li>функционал оформления заявки на доставку;</li>
	<li>функционал печати заказов и актов;</li>
	<li>база данных с отосланными заявками;</li>
	<li>прочий функционал</li>
</ul>
<p>Модуль использует встроенный функционал рассчета габаритов заказа и виджет Яндекс.Доставки для вычисления стоимости доставки при оформлении заказа.</p>
<p>Модуль устанавливает компонент \"Пункты самовывоза delivery\", который отображает детальные сведения о пунктах, и может использоваться в качестве наглядной информации о доставке.</p>";

// FAQ: Начало работы
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_HDR_ABOUT'] = "Начало работы";

$MESS ['TRADE_YANDEX_DELIVERY_FAQ_TURNON_TITLE'] = "- Включение функционала";
$MESS ['TRADE_YANDEX_DELIVERY_FAQ_TURNON_DESCR'] = "<p>1. Необходимо проверить работает ли на сервере сайта библиотека CURL и обратиться к администратору с просьбой о включении.</p><p>2. Проверить создана ли служба Яндекс.Доставки тут:<br>Админка - Магазин - Настройки - Службы доставки<br>Если служба отсутствует, ее необходимо добавить, в качестве обработчика службы выбрать \"Яндекс.Доставка [tradeDeliveryYandex]\".</p><p>3. В настройках модуля <b>обязательно</b> задайте код корзинного виджета и конфигурационный файл, получить их можно в личном кабинете Яндекс.Доставки:<br> Настройки - Интеграция.<br>Также укажите где модулю брать габариты товаров. Если они не заданы хоть для одного товара в корзине заказа, то применятся габариты по умолчанию для всего заказа. Вышесказанное касается и веса товаров и посылки.</p>";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_DELSYS_TITLE'] = "- Настройка службы доставки";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_DELSYS_DESCR'] = "
<p>
	<strong>1. Управление службами доставки</strong><br> Админка - Магазин - Настройки - Службы доставки - Автоматизированные<br> Здесь можно настроить: <ul><li>Активность службы доставки и ее профилей</li><li>Название и описание службе доставки и ее профилям</li><li>Привязку профилей к платежным системам</li><li>Ограничения по габаритам и стоимости заказа</li></ul>
</p>
<p>
	<strong>2. Привязка способа оплаты к Службе Доставок.</strong><br>
	Для того, чтобы привязать платежные системы к конкретным вариантам доставки используйте стандартный функционал Bitrix (доступен с 14-й версии) - в <a href='/bitrix/admin/sale_pay_system.php' target='_blank'>настройках платежных систем</a> откройте нужную плат.систему и во вкладке 'Службы доставки' выберите службы для которых будет доступна данная платежная система.
</p>
<p>
	<strong>3. Отображается кнопка 'расcчитать стоимость'.</strong><br>
		Для того, чтобы стоимость расчитывалась автоматом, необходимо в параметрах компонента оформления заказа (sale.order.ajax) поставить галочку 'Рассчитывать стоимость доставки сразу'.
</p>
<p>
	<strong>4. Учет веса заказа.</strong><br>
		Расчет доставки производится корзинным виджетом, габариты и вес в него передаются в виде одного товара с итоговыми рассчитанными габаритами и весом посылки. Если стоимость доставки неверна, проверьте в настройках откуда модуль получает габариты товаров.<br><br>
		Алгоритм расчета веса посылки:<br>
		1) В посылке нет товаров с нулевым весом.<br>
		Суммарный вес посылки равен сумме весов всех товаров.<br>
		2) В посылке есть товары с нулевым весом и вес посылки больше или равен весу по умолчанию.<br>
		Вес каждого товара с нулевым весом считаем равным 10г.<br>
		3) В посылке есть товары с нулевым весом и вес посылки меньше веса по умолчанию.<br>
		Вес каждого товара с нулевым весом считаем равным округленному вверх отношению разницы между весом по умолчанию и суммарным весом товаров, у которых он задан, и количеством товаров с нулевым весом. Иными словами, вес посылки принимается за вес по умолчанию, а разница между текущим весом и весом по умолчанию пропорционально распределяется на товары с нулевым весом.<br>
</p>
";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_SEND_TITLE'] = "- Оформление и отправка заявки";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_SEND_DESCR'] = "<p>Для отправки заявок модуль добавляет в детальную карточку заказа кнопку \"Яндекс.Доставка\". По ее нажатию отображается форма редактирования данных заявки. Необходимо задать значения полям формы. Если необходимо оставить поле пустым, то поставьте в него пробел или -.<br> По нажатию кнопки создания черновика заявка будет выгружена в Яндекс.Доставку со всеми данными заказа и его корзине. Чтобы создать отгрузку для заказа, необходимо нажать кнопку \"Выгрузить в СД\", после этого черновик будет переведен в отгрузку в личном кабинете ЯД. Дальнейшие манипуляции с отгрузками пока не предусмотрены в модуле и производятся в личном кабинете ЯД.</p>";

	// FAQ: Дополнительные возможности
$MESS['TRADE_YANDEX_DELIVERY_FAQ_HDR_WORK'] = "Дополнительные возможности";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_PELENG_TITLE'] = "- Отслеживание состояний";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_PELENG_DESCR'] = "<p><strong>1. Статусы заказов.</strong><br>
	Опрос статусов заказов происходит каждые 30 минут для всех заказов, статусы которых не финальные(например: завершен). Если статус заказа изменился, то он обновится на сайте, если в настройках модуля ему выставлено соответствие статуса на сайте, то он будет обновлен, что повлечет за собой все события изменения статуса(например: отправку письма об измении статуса).
</p>
<p>
	<strong>2. Печать документов.</strong><br>
	В форме отправки заявки предусмотрена печать документов заказа. Она возможна после выгрузки заявки непосредственно в службу доставки, то есть после подтверждения отгрузки в личном кабинете ЯД.
</p>";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_COMPONENT_TITLE'] = "- Компонент \"Пункты Самовывоза delivery\"";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_COMPONEMT_DESCR'] = "Компонент используются в первую очередь на странице оформления заказа, так же его можно использовать на странице доставки, чтобы вывести информацию о самовывозах, стоимости и сроках доставки для всех профилей. <strong>На странице оформления заказа компонент подключать не нужно!</strong> Он подключится автоматически.<br>Компонент предназначен для вывода карты с отображением на ней пунктов самовывоза и информации о них, а так же проведения различных манипуляций вроде выбора пункта для доставки. Функционал выбора пункта самовывоза на странице оформления заказа реализован с помощью этого компонента. Его так же можно использовать, чтобы отображать информацию о пунктах самовывоза в разделе \"Доставка\".<br>
Вставить компонент на страницу можно с помощью визуального редактора. Расположен он по пути \"Магазин\" -> \"Компоненты trade\". Если после установки модуля компонент в визуальном редакторе не появился - попробуйте <a href='/bitrix/admin/cache.php' target='_blank'>очистить файлы кэша</a> Битрикса.<br>
<img src=\"/bitrix/images/yandex.delivery/componentAdd.png\"><br>
Компонент так же можно вставить php-кодом:<br>
<div style='color:#AC12B1'>
&lt;?\$GLOBALS['APPLICATION']->IncludeComponent(\"trade:yandex.deliveryPickup\",\".default\",array(),false);?&gt;
</div>
Компонент имеет следующие настройки:<br>
<ul>
	<li>Не подключать Яндекс-карты - если на странице с компонентом код Яндекс-карт подключается где-либо еще (в особенности - если подключается версия не 2.1), нужно поднять этот флаг, чтобы скрипты не конфликтовали.</li>
	<li>Отображать окно выбора города - указывает отображать ли поле ввода города.</li>
	<li>Отображать рассчитанный тариф курьерской доставки - если необходимо вывести информацию о стоимости и сроках доставки курьером, необходимо отметить данную опцию.</li>
	<li>ID города для отображения - задает город получатель для компонента.</li>
	
</ul>
Вместе с компонентом поставляются два шаблона:
<ul>
	<li>.default - шаблон, предназначенный для отображения информации о пунктах самовывоза.</li>
	<li>order - шаблон, используемый для выбора пункта самовывоза при оформлении заказа.</li>
</ul>
Крайне не рекомендуется модифицировать эти шаблоны, в особенности - их скрипт. При необходимости вынесете их в отдельное пространство имен, иначе корректная работа модуля (в особенности - при оформлении заказа) не гарантируется.
";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_WIDGET_INCLUDE_TITLE'] = "- Включение виджетов(гео, карточный) на сайт";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_WIDGET_INCLUDE'] = "<p>Для установки на сайт виджетов Яндекс.Доставки предусмотрен шаблон компонента модуля trade:deliveryPickup, название шаблона info.</p><p>Для получения кода включения компонента необходимо авторизоваться на сайте под админом. Перейти на главную страницу сайта. В панели админа создать новую тестовую страницу.</p><img src = '/bitrix/images/yandex.delivery/Widget1.png'/><p>Укажите параметры для страницы и нажмите \"Готово\"</p><img src = '/bitrix/images/yandex.delivery/Widget2.png'/><p>Откроется визуальный редактор страницы test.php, в нем необходимо на странице разместить компонент модуля Яндекс.Доставки</p><img src = '/bitrix/images/yandex.delivery/Widget3.png'/><p>После размещения компонента откроется окно редактирования его параметров, укажите шаблон info, скопируйте из личного кабинета Яндекс.Доставки код виджета, задайте город по умолчанию. Настройка \"Использовать склад Яндекс\" проставляется автоматически из настроек модуля. Для расчета доставки компонент использует текущую корзину клиента на сайте.</p><img src = '/bitrix/images/yandex.delivery/Widget4.png'/><p>Если подключается карточный виджет, необходимо указать какой товар передавать в виджет для расчета, отметьте настройку \"Использовать для расчета товар, а не корзину\", в появившихся полях задайте id товара и количество. По умолчанию для шаблона карточки товара id товара для расчета выглядит так:<br> Если в карточке товара используются торговые предложения: ={\$arResult[\"OFFERS\"][0][\"ID\"]}<br>если нет: ={\$arResult[\"ID\"]}</p><img src = '/bitrix/images/yandex.delivery/Widget5.png'/><p>Сохраните изменения и перейдите к редактированию страницы в режиме php</p><img src = '/bitrix/images/yandex.delivery/Widget6.png'/><p>Скопируйте выделенный код подключения компонента и вставьте его в шаблон сайта, где необходимо отображать виджет</p><img src = '/bitrix/images/yandex.delivery/Widget7.png'/><p></p>";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_DELIVERYPRICE_TITLE'] = "- Модификация результатов расчетов (для программистов)";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_DELIVERYPRICE'] = "<p>Функционал модуля содержит в себе механизм событий. Работа с событиями описана в документации Битрикс и соответсвующих форумах. Не рекомендуется использовать описаный в разделе функционал, если Вы не уверены, что понимаете написанное в нем.</p>".
"<p>Набор событий модуля:<ul>".
"<li><b>onGetOrderData</b> - выполняется после получения данных заказа.<br>Параметры \$arOrder - массив данных заказа.</li>".
"<li><b>onGetOrderProps</b> - выполняется после получения значенией свойств заказа.<br>Параметры \$arOrderProps - массив значений свойств заказа.</li>".
"<li><b>onGetWidgetData</b> - выполняется после получения данных виджета.<br>Параметры \$arWidgetData - массив данных виджета со страницы оформления заказа, формы отправки заказа.</li>".
"<li><b>onGetBasketData</b> - выполняется после получения корзины заказа, габаритов и веса.<br>Параметры \$arBasketData - массив данных содержимого корзины с габаритами, весом и итоговыми их значениями.</li>".
"</ul></p>".
"<p>При необходимости добавления событий для кастомизации работы модуля, необходимо обратится в службу поддержки модуля.</p>";

$MESS['TRADE_YANDEX_DELIVERY_DEFAULT_FAKE_CITY_TO_CALC'] = "Москва";
$MESS['TRADE_YANDEX_DELIVERY_PROFILE_TODOOR'] = "Курьер";
$MESS['TRADE_YANDEX_DELIVERY_PROFILE_POST'] = "Почта";
$MESS['TRADE_YANDEX_DELIVERY_PROFILE_PICKUP'] = "Самовывоз";

$MESS['TRADE_YANDEX_DELIVERY_LIMITS_TABLE_deliveryName'] = "Доставка";
$MESS['TRADE_YANDEX_DELIVERY_LIMITS_TABLE_tariffName'] = "Тариф";
$MESS['TRADE_YANDEX_DELIVERY_LIMITS_TABLE_tress'] = "Ограничение, руб.";

$MESS ['TRADE_YANDEX_DELIVERY_NOCURL_TEXT'] = "<span style='color:red'>Внимание! Не включена php библиотека CURL, необходимая для работы модуля. Для корректной его работы, необходимо подключить эту библиотеку. Обратитесь за помощью к техподдержке сервера.</span>";

$MESS['TRADE_YANDEX_DELIVERY_FAQ_WIDGET_ENCODING_TITLE'] = "- Если кодировка виджета (гео, карточного) отображается некорректно";
$MESS['TRADE_YANDEX_DELIVERY_FAQ_WIDGET_ENCODING'] = "<p>Если после установки виджета наблюдаются проблемы с кодировкой дат, необходимо выполнить следующие действия:</p>

<img src = '/bitrix/images/yandex.delivery/Encoding_1.png'/>

<ul>
	<li>Скопируйте код скрипта, размещенного по ссылке, указанной при подключении виджета, и добавьте его в новый созданный файл, например, widgetJsLoader.js.</li>
	<li>Подключите новый файл вместо указанного.</li>
	<li>Ориентировочно на строке 1252 вместо кода <br />
	
		<p><b>this.oneDate = function (array) {
          return moment(array[0]).format(\"D MMMM\");
        };</b></p>
		разместите следующий<br />
		<p><b>this.oneDate = function (array) {
          return moment(array[0]).format(\"D.MM\");
        };</b></p>
		
	</li>
	<li> Код, начинающийся со строки 1260, 
	
		<p><b>if (moment(minDate).month() == moment(maxDate).month()) {
            return moment(minDate).date() + '&ndash;' + moment(maxDate).format(\"D MMMM\");
        } else {
            return moment(minDate).format(\"D MMM\") + '&ndash;' + moment(maxDate).format(\"D MMM\");
        }</b></p>
		
		<p>разместите</p>
		
		<p><b>if (moment(minDate).month() == moment(maxDate).month()) {
            return moment(minDate).format(\"D.MM\") + '&nbsp;' + '&ndash;' + '&nbsp;' + moment(maxDate).format(\"D.MM\");
        } else {
            return moment(minDate).format(\"D.MM\") + '&nbsp;' + '&ndash;' + '&nbsp;' + moment(maxDate).format(\"D.MM\");
        }</b></p>
	</li>
	<li>Пример подключения виджета
		<p id='script_YA_DOST'>
		".htmlspecialchars('<meta name="ydWidgetData" id="f379084dbc3d6bd6c42d7c66bb845e2a" content="" data-sender_id="2713" data-weight="3" data-cost="0" data-height="30" data-length="20" data-width="30" data-city_from="Москва" data-geo_id_from="213" data-css_name="geo_tpl" data-tpl_name="geo_tpl" data-container_tag_id="d5d376983d309d2f84db43b7babe4a2" data-resource_id="246" data-resource_key="087a6fbf6c7acd3bad505bdb20e017b8" data-tracking_method_key="cdc4c8da10b67f51683024a946dd2594" data-autocomplete_method_key="3f1e84c33d4e31480e9fde4479f49314"></meta><!--[if lt IE 9]><script>document.createElement("msw");</script><![endif]-->
<script src="/widgetJsLoader.js?dataTagID=f379084dbc3d6bd6c42d7c66bb845e2a" charset="win-1251"></script>
<msw id="d5d376983d309d2f84db43b7babe4a2" class="yd-widget-container"></msw>')."
        </p>
	</li>
</ul>

<p>Виджет будет выглядеть следующим образом:</p>

<img src = '/bitrix/images/yandex.delivery/Encoding_done_1.png'/>
<br />
<img src = '/bitrix/images/yandex.delivery/Encoding_done_2.png'/>

";