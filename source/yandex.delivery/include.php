<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id = 'yandex.delivery';
CModule::AddAutoloadClasses(
    $module_id,
    array(
        'CDeliveryYandexDriver' => '/classes/general/CDeliveryYandexDriver.php',
        'CDeliveryYandex' => '/classes/general/CDeliveryYandex.php',
        'CDeliveryYandexHelper' => '/classes/general/CDeliveryYandexHelper.php',
        'CDeliveryYandexProps' => '/classes/general/CDeliveryYandexProps.php',
        'CDeliveryYandexSqlOrders' => '/classes/mysql/CDeliveryYandexSqlOrders.php',
    )
);

?>