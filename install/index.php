<?php
/**
 * Copyright (c) 27/10/2019 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

IncludeModuleLangFile(__FILE__);
if (class_exists("yandex_delivery"))
    return;


Class yandex_delivery extends CModule
{
    var $MODULE_ID = "yandex.delivery";
    var $MODULE_NAME;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "N";
    var $errors;

    function yandex_delivery()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('TRADE_YANDEX_DELIVERY_INSTALL_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('TRADE_YANDEX_DELIVERY_INSTALL_DESCRIPTION');
        $this->PARTNER_NAME = "ASDAFF";
        $this->PARTNER_URI = "https://asdaff.github.io";
    }

    function InstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;
        if (!$DB->Query("SELECT 'x' FROM yandex_delivery", true))
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/mysql/install.sql");

        if ($this->errors !== false) {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return false;
        }

        return true;
    }

    function UnInstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;

        $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/mysql/uninstall.sql");
        if (!empty($this->errors)) {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return false;
        }

        return true;
    }

    function InstallEvents()
    {
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepPersonType", $this->MODULE_ID, "CDeliveryYandex", "setLocationFromCookie");
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepOrderProps", $this->MODULE_ID, "CDeliveryYandex", "loadComponent");
        RegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "CDeliveryYandex", "onBufferContent");
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", $this->MODULE_ID, "CDeliveryYandex", "orderCreate");
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", $this->MODULE_ID, "CDeliveryYandex", "pickupLoader");
        RegisterModuleDependences("main", "OnEpilog", $this->MODULE_ID, "CDeliveryYandex", "onEpilog");
        RegisterModuleDependences("sale", "OnBeforeOrderUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBeforeOrderUpdateHandler");
        RegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnOrderUpdateHandler");
        RegisterModuleDependences("sale", "OnOrderAdd", $this->MODULE_ID, "CDeliveryYandexHelper", "OnOrderAddHandler");
        RegisterModuleDependences("sale", "OnBeforeBasketUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBeforeBasketUpdateHandler");
        RegisterModuleDependences("sale", "OnBasketUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBasketUpdateHandler");
        RegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBasketAddHandler");
        RegisterModuleDependences("sale", "OnBeforeBasketDelete", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBeforeBasketDeleteHandler");
        RegisterModuleDependences("sale", "OnSalePropertyValueSetField", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSalePropertyValueSetFieldHandler");
        RegisterModuleDependences("sale", "OnSaleShipmentSetField", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSaleShipmentSetFieldHandler");
        RegisterModuleDependences("sale", "OnSaleBasketItemSetField", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSaleBasketItemSetFieldHandler");
        RegisterModuleDependences("sale", "OnSaleOrderCanceled", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSaleOrderCanceledHandler");
        RegisterModuleDependences("main", "OnModuleUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnModuleUpdateHandler");
        return true;
    }

    function UnInstallEvents()
    {
        UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepPersonType", $this->MODULE_ID, "CDeliveryYandex", "setLocationFromCookie");
        UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepOrderProps", $this->MODULE_ID, "CDeliveryYandex", "loadComponent");
        UnRegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "CDeliveryYandex", "onBufferContent");
        RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", $this->MODULE_ID, "CDeliveryYandex", "orderCreate");
        UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", $this->MODULE_ID, "CDeliveryYandex", "pickupLoader");
        UnRegisterModuleDependences("main", "OnEpilog", $this->MODULE_ID, "CDeliveryYandex", "onEpilog");
        UnRegisterModuleDependences("sale", "OnBeforeOrderUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBeforeOrderUpdateHandler");
        UnRegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnOrderUpdateHandler");
        UnRegisterModuleDependences("sale", "OnOrderAdd", $this->MODULE_ID, "CDeliveryYandexHelper", "OnOrderAddHandler");
        UnRegisterModuleDependences("sale", "OnBeforeBasketUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBeforeBasketUpdateHandler");
        UnRegisterModuleDependences("sale", "OnBasketUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBasketUpdateHandler");
        UnRegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBasketAddHandler");
        UnRegisterModuleDependences("sale", "OnBeforeBasketDelete", $this->MODULE_ID, "CDeliveryYandexHelper", "OnBeforeBasketDeleteHandler");
        UnRegisterModuleDependences("sale", "OnSalePropertyValueSetField", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSalePropertyValueSetFieldHandler");
        UnRegisterModuleDependences("sale", "OnSaleShipmentSetField", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSaleShipmentSetFieldHandler");
        UnRegisterModuleDependences("sale", "OnSaleBasketItemSetField", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSaleBasketItemSetFieldHandler");
        UnRegisterModuleDependences("sale", "OnSaleOrderCanceled", $this->MODULE_ID, "CDeliveryYandexHelper", "OnSaleOrderCanceledHandler");
        UnRegisterModuleDependences("main", "OnModuleUpdate", $this->MODULE_ID, "CDeliveryYandexHelper", "OnModuleUpdateHandler");
        CAgent::RemoveModuleAgents($this->MODULE_ID);
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/js/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . $this->MODULE_ID, true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/delivery/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/include/sale_delivery/", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/components/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/images/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/images/" . $this->MODULE_ID, true, true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/js/" . $this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/components/" . $this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/images/" . $this->MODULE_ID);
        DeleteDirFilesEx("/bitrix/php_interface/include/sale_delivery/yandex_delivery.php");
        return true;
    }

    function DoInstall()
    {
        global $DB, $APPLICATION, $step;
        $this->errors = false;

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(GetMessage("ddeliveryddelivery_INSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/step1.php");


        global $DB, $APPLICATION, $step;
        $this->errors = false;

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage("delivery_INSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/step1.php");
    }

    function DoUninstall()
    {
        global $DB, $APPLICATION, $step;
        $this->errors = false;

        COption::SetOptionString($this->MODULE_ID, 'logddelivery', '');
        COption::SetOptionString($this->MODULE_ID, 'pasddelivery', '');
        COption::SetOptionString($this->MODULE_ID, 'logged', false);

        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallEvents();

        CAgent::RemoveModuleAgents('ddelivery.ddelivery2');

        UnRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage("ddeliveryddelivery_DEL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/unstep1.php");


        global $DB, $APPLICATION, $step;
        $this->errors = false;

        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallEvents();

        UnRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage("delivery_DEL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/unstep1.php");
    }
} ?>