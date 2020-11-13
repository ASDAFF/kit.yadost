<?
/**
 * Copyright (c) 25/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id = 'ipol.yadost';
CModule::AddAutoloadClasses(
    $module_id,
    array(
        'CIPOLYadostDriver' => '/classes/general/CIPOLYadostDriver.php',
        'CIPOLYadost' => '/classes/general/CIPOLYadost.php',
        'CIPOLYadostHelper' => '/classes/general/CIPOLYadostHelper.php',
        'CIPOLYadostProps' => '/classes/general/CIPOLYadostProps.php',
        'CIPOLYadostSqlOrders' => '/classes/mysql/CIPOLYadostSqlOrders.php',
    )
);

?>