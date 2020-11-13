<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id = 'kit.yadost';
CModule::AddAutoloadClasses(
    $module_id,
    array(
        'CKITYadostDriver' => '/classes/general/CKITYadostDriver.php',
        'CKITYadost' => '/classes/general/CKITYadost.php',
        'CKITYadostHelper' => '/classes/general/CKITYadostHelper.php',
        'CKITYadostProps' => '/classes/general/CKITYadostProps.php',
        'CKITYadostSqlOrders' => '/classes/mysql/CKITYadostSqlOrders.php',
    )
);

?>