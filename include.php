<?
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id = 'yandex.delivery';
CModule::AddAutoloadClasses(
    $module_id,
    array(
        'CDeliveryYaDriver' => '/classes/general/CDeliveryYaDriver.php',
        'CDeliveryYa' => '/classes/general/CDeliveryYa.php',
        'CDeliveryYaHelper' => '/classes/general/CDeliveryYaHelper.php',
        'CDeliveryYaProps' => '/classes/general/CDeliveryYaProps.php',
        'CDeliveryYaSqlOrders' => '/classes/mysql/CDeliveryYaSqlOrders.php',
    )
);

?>