<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

// статусы
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DRAFT"] = "Черновик";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_CREATED"] = "Создан";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_SENDER_SENT"] = "Ждёт подтверждения службы доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_LOADED"] = "Подтверждён службой доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_ERROR"] = "Ошибка";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_FULFILMENT_LOADED"] = "Подтверждён единым складом";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_SENDER_WAIT_FULFILMENT"] = "Ожидается на едином складе";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_SENDER_WAIT_DELIVERY"] = "Ожидается в службе доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_FULFILMENT_ARRIVED"] = "На едином складе";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_FULFILMENT_PREPARED"] = "Готов к передаче в службу доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_FULFILMENT_TRANSMITTED"] = "Передан в службу доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_AT_START"] = "На складе службы доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_TRANSPORTATION"] = "Доставляется";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_ARRIVED"] = "В городе покупателя";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_TRANSPORTATION_RECIPIENT"] = "Доставляется по городу";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_ARRIVED_PICKUP_POINT"] = "В пункте самовывоза";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_DELIVERY_DELIVERED"] = "Доставлен";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_RETURN_PREPARING"] = "Готовится к возврату";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_RETURN_ARRIVED_DELIVERY"] = "Возвращён на склад службы доставки";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_RETURN_ARRIVED_FULFILMENT"] = "Возвращён на единый склад";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_RETURN_PREPARING_SENDER"] = "Готов к возврату в магазин";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_RETURN_RETURNED"] = "Возвращён в магазин";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_LOST"] = "Утерян";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_UNEXPECTED"] = "Статус уточняется";
$MESS["TRADE_YANDEX_DELIVERY_YD_STATUS_CANCELED"] = "Отменён";

// свойства
$MESS["TRADE_YANDEX_DELIVERY_prop_name_tradeAdelivery_PVZ_ADDRESS"] = "Адрес ПВЗ Яндекс.Доставки";
$MESS["TRADE_YANDEX_DELIVERY_prop_descr_tradeAdelivery_PVZ_ADDRESS"] = "Свойство содержит полный адрес пункта вывоза заказа Яндекс.Доставкой";

// окно сообщений
$MESS["TRADE_YANDEX_DELIVERY_NOTICE_WINDOW_HEADER"] = "Предупреждение";
$MESS["TRADE_YANDEX_DELIVERY_NOTICE_WINDOW_MSG_CHANGE_ORDER"] = "Вы изменили заказ, необходимо пересчитать стоимость доставки в Яндекс.Доставке.<br> Номера заказов с изменениями: ";
$MESS["TRADE_YANDEX_DELIVERY_NOTICE_WINDOW_MSG_CANCEL_ORDER"] = "Для отмененных заказов необходимо отменить соответствующий оформленный заказ в Яндекс.Доставке.<br> Номера отмененных заказов: ";
$MESS["TRADE_YANDEX_DELIVERY_NOTICE_WINDOW_MSG_ERROR_STATUS_ORDER"] = "Произошла ошибка в обработке заказов, необходимо связаться с технической поддержкой Яндекс.Доставки для выяснения причин.<br> Номера заказов с ошибкой: ";

// города отправления
$MESS["TRADE_YANDEX_DELIVERY_CityFrom_MOSCOW"] = "Москва";
$MESS["TRADE_YANDEX_DELIVERY_CityFrom_PITER"] = "Санкт-Петербург";
?>