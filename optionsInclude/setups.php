<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

if (CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog"))
{
	$dbIBlocks = CCatalog::GetList(
		array(),
		array()
	);
	
	// собираем инфоблоки каталог и ТП
	$arIblockIDs = array();
	while ($iBlock = $dbIBlocks->Fetch())
		$arIblockIDs[] = $iBlock["IBLOCK_ID"];
	
	// собираем свойства ИБ
	$arIblockProps = array();
	foreach ($arIblockIDs as $iBlockID)
	{
		$dbIBlockProperties = CIBlockProperty::GetList(
			array(),
			array("IBLOCK_ID" => $iBlockID)
		);
		
		while ($iBlockProp = $dbIBlockProperties->Fetch())
			$arIblockProps[$iBlockProp["CODE"]] = $iBlockProp["NAME"];
	}
}

if (CModule::IncludeModule("sale"))
{
	// собираем свойства заказа
	$dbOrderProps = CSaleOrderProps::GetList(
		array()
	);
	
	$arOrderProps = array();
	while ($arProp = $dbOrderProps->Fetch())
		$arOrderProps[$arProp["CODE"]] = $arProp["NAME"];
}

// получение html select для вывода на форме
function TRADE_YANDEX_DELIVERY_getSelect(&$arOptions, $selected, $blockName, $empty = false)
{
	ob_start();
	?>

    <select name="<?= $blockName ?>">
		<?
		if ($empty)
		{
			?>
            <option value=""></option><?
		}
		
		foreach ($arOptions as $code => $option)
		{
			?>
            <option value="<?= $code ?>" <? if ($code == $selected)
			echo "selected"; ?>><?= "[" . $code . "]" . $option ?></option><?
		}
		?>

    </select>
	
	<?
	$html = ob_get_contents();
	ob_end_clean();
	
	return $html;
}

?>

    <script type='text/javascript'>
        $(document).ready(function ()
        {
            TRADE_YANDEX_DELIVERY_measRadio(<?=CUtil::PHPToJSObject($sideMode)?>);
            TRADE_YANDEX_DELIVERY_addressRadio(<?=CUtil::PHPToJSObject($addressMode)?>);
            TRADE_YANDEX_DELIVERY_weiRadio('<?echo ($TRADE_YANDEX_DELIVERY_weight == "CATALOG_WEIGHT") ? "cat" : "prop"?>');

            $('input[name^="deliv_"]').on('change', TRADE_YANDEX_DELIVERY_delivClick);

            var sideMeas = $('#sidesMeas').val();
            $('.MeasLbl').html(sideMeas);

            var $addressMode = $("[name='addressMode]");
            $addressMode.closest('td').css('border-top', '1px solid #BCC2C4');
            $addressMode.closest('td').siblings().css('border-top', '1px solid #BCC2C4');
        });

        function TRADE_YANDEX_DELIVERY_delivClick(wat)
        {
            wat = $(wat.currentTarget);

            if (typeof(wat.attr('checked')) !== 'undefined')
            {
                if (wat.attr('name') === 'deliv_All')
                {
                    $('[name="deliv_Fast"]').removeAttr('checked');
                    $('[name="deliv_Cheap"]').removeAttr('checked');
                    $('[name="deliv_Balance"]').removeAttr('checked');
                }
                else
                    $('[name="deliv_All"]').removeAttr('checked');
            }
        }

        function TRADE_YANDEX_DELIVERY_clearCache()
        {
            $.ajax({
                url: '/bitrix/js/<?=$module_id?>/ajax.php',
                type: 'POST',
                data: 'action=clearCache&<?=bitrix_sessid_get()?>',
                success: function (data)
                {
                    if (data === 'Y')
                        alert('<?=GetMessage("TRADE_YANDEX_DELIVERY_ALERT_cacheCleared")?>');
                    else
                        alert('<?=GetMessage("TRADE_YANDEX_DELIVERY_ALERT_cacheNotCleared")?>');
                }
            })
        }

        function trade_popup_virt(code, info)
        {
            console.log([code, info, info.position()]);

            var offset = info.position().top;
            var LEFT = info.offset().left;

            var obj;
            if (code === 'next')
                obj = info.next();
            else
                obj = $('#' + code);

            LEFT -= parseInt(parseInt(obj.css('width')) / 2);

            obj.css({
                top: (offset + 15) + 'px',
                left: LEFT,
                display: 'block'
            });
            return false;
        }

        function TRADE_YANDEX_DELIVERY_measRadio(wat)
        {
            if (wat === 'def')
            {
                $('#sidesMeas_mm').attr('selected', 'selected');
                $('#sidesMeas').hide();
                $('#sidesMeas_fix').show();
                $('.MeasLbl').html('mm');
            } else
            {
                $('#sidesMeas').show();
                $('#sidesMeas_fix').hide();
            }

            $('.TRADE_YANDEX_DELIVERY_mea').css('display', 'none');
            $('tr[id^="TRADE_YANDEX_DELIVERY_mea_' + wat + '"]').css('display', '');

            $('#TRADE_YANDEX_DELIVERY_meaMode_' + wat).prop('checked', 'true');
        }

        function TRADE_YANDEX_DELIVERY_weiRadio(wat)
        {
            if (wat === 'cat')
            {
                $('#weightMeas_g').attr('selected', 'selected');
                $('#weightMeas').hide();
                $('#weightMeas_fix').show();
                $('.WeightLbl').html('g');
            } else
            {
                $('#weightMeas').show();
                $('#weightMeas_fix').hide();
            }


            var $handler = $("[name='weightPr']");
            if (wat === 'prop')
            {
                $handler.css('display', '');
                if ($handler.val() === 'CATALOG_WEIGHT')
                    $handler.val('');
            }
            else
            {
                $handler.css('display', 'none');
                $handler.val('CATALOG_WEIGHT');
            }
            $('#TRADE_YANDEX_DELIVERY_weiMode_' + wat).prop('checked', 'true');
        }

        var TRADE_YANDEX_DELIVERY_CONFIG_wnd = false;
        function TRADE_YANDEX_DELIVERY_CONFIG_openWnd()
        {
            if (!TRADE_YANDEX_DELIVERY_CONFIG_wnd)
            {
                TRADE_YANDEX_DELIVERY_CONFIG_wnd = new BX.CDialog({
                    title: "<?=GetMessage('TRADE_YANDEX_DELIVERY_LABEL_LOADCONFIG')?>",
                    content: "<a href='javascript:void(0)' onclick='$(this).siblings(\"div\").toggle()'><?=GetMessage('TRADE_YANDEX_DELIVERY_LABEL_CONFIGORDER')?></a><div style='display:none'><?=GetMessage('TRADE_YANDEX_DELIVERY_TEXT_ABOUTCONFIG')?></div><br><textarea style='width:650px;height:450px;' id='TRADE_YANDEX_DELIVERY_loadConfig_area1'></textarea><br><br><textarea style='width:650px;height:150px;' id='TRADE_YANDEX_DELIVERY_loadConfig_area2'></textarea>",
                    icon: 'head-block',
                    resizable: true,
                    draggable: true,
                    height: '502',
                    width: '692',
                    buttons: ['<input type="button" <?if (!CDeliveryYaHelper::isAdmin())
					{
						echo "disabled";
					}?> value="<?=GetMessage('TRADE_YANDEX_DELIVERY_BUTTON_LOAD')?>" onclick="TRADE_YANDEX_DELIVERY_CONFIG_LOAD()"/><input type="button" value="<?=GetMessage('TRADE_YANDEX_DELIVERY_BUTTON_CANSEL')?>" onclick="TRADE_YANDEX_DELIVERY_CONFIG_wnd.Close()"/>']
                });
            }

            TRADE_YANDEX_DELIVERY_CONFIG_wnd.Show();
        }

        function TRADE_YANDEX_DELIVERY_CONFIG_LOAD()
        {
            var txt = $('#TRADE_YANDEX_DELIVERY_loadConfig_area1').val(),
                ids = $('#TRADE_YANDEX_DELIVERY_loadConfig_area2').val();

            if (txt && ids)
            {
                $.ajax({
                    url: '/bitrix/js/<?=$module_id?>/ajax.php',
                    type: 'POST',
                    data: 'action=setConfig&config1=' + txt + '&config2=' + ids + '&<?=bitrix_sessid_get()?>',
                    dataType: "json",
                    success: function (respond)
                    {
                        if (respond.success)
                        {
                            $('#TRADE_YANDEX_DELIVERY_confRed').hide();
                            $('#TRADE_YANDEX_DELIVERY_confGreen').show();
                            TRADE_YANDEX_DELIVERY_CONFIG_wnd.Close();
                        }
                        else
                        {
                            alert('<?=GetMessage("TRADE_YANDEX_DELIVERY_ALERT_configNotSaved")?>');
                            console.log(respond);
                        }
                    },
                    error: function (a, b)
                    {
                        console.log(a);
                        console.log(b);
                    }
                })
            }
            else
                alert('<?=GetMessage("TRADE_YANDEX_DELIVERY_ALERT_noConfig")?>');

        }

        function TRADE_YANDEX_DELIVERY_propFixerRun(wrap)
        {
            var $wrap = $('#' + wrap).empty();
            var personeTypes = [];

            $wrap.append('<?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_PAYERS")?>');
            $.get('/bitrix/js/<?=$module_id?>/ajax.php', {
                action: "propFixer",
                "sessid": BX.bitrix_sessid()
            }, function (data)
            {
                console.log(data);
                if (data.success)
                {
                    console.log(data);
                    personeTypes = data.data;
                    $wrap.append('OK<br>');

                    process();
                }
                else
                {
                    $wrap.append(data.data.code + '<br>');
                }

            }, 'json');

            function process(typeIndex, stepIndex)
            {
                typeIndex = typeIndex || 0;
                stepIndex = stepIndex || 0;

                if (typeIndex + 1 > personeTypes.length)
                {
                    TRADE_YANDEX_DELIVERY_finish();
                    return;
                }

                var steps = [
                    {name: 'persone', 'text': '<?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_persone")?>'},
                    {name: 'location', 'text': '<?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_location")?>'},
                    {name: 'address', 'text': '<?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_address")?>'},
                    {name: 'zip', 'text': '<?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_zip")?>'}
                ];

                if (stepIndex + 1 > steps.length)
                {
                    return process(typeIndex + 1, 0);
                }

                if (stepIndex === 0)
                {
                    $wrap.append('<br><?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_testType")?> "' + personeTypes[typeIndex].NAME + '"<br>');
                }

                $wrap.append(steps[stepIndex].text + '...');

                $.get('/bitrix/js/<?=$module_id?>/ajax.php', {
                    personeTypeId: personeTypes[typeIndex].ID,
                    step: steps[stepIndex].name,
                    action: "propFixer",
                    "sessid": BX.bitrix_sessid()
                }, function (data)
                {
                    // console.log(data);
                    if (data.success)
                    {
                        if (typeof data.data === 'object')
                        {
                            $wrap.append('<br><?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_resFields")?> ' + data.data.join(', ') + '<br>');
                        } else
                        {
                            $wrap.append('OK<br>');
                        }

                        process(typeIndex, stepIndex + 1);
                        return true;
                    } else
                    {
                        $wrap.append('<br><?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_error")?>' + data.data.code + '<br>');

                        var btnContinue = $('<a href="#"><?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_continue")?></a>')
                            .click(function ()
                            {
                                // process(typeIndex + 1, 0);
                                process(typeIndex, stepIndex + 1);
                                return false;
                            });

                        var btnBreak = $('<a href="#"><?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_stop")?></a>')
                            .click(function ()
                            {
                                TRADE_YANDEX_DELIVERY_finish();
                                return false;
                            });

                        $wrap
                            .append(btnContinue)
                            .append(' ')
                            .append(btnBreak)
                            .append('<br>');


                    }
                }, 'json');
            }

            function TRADE_YANDEX_DELIVERY_finish()
            {
                $wrap.append('<br><?=GetMessage("TRADE_YANDEX_DELIVERY_propFix_finished")?>');
            }
        }

        function TRADE_YANDEX_DELIVERY_addressRadio(mode)
        {
            $('#TRADE_YANDEX_DELIVERY_addressMode_' + mode).prop('checked', 'true');

            var Fields = {
                "sep": ["street", "house", "build", "flat"],
                "one": ["address"]
            };

            for (var i in Fields)
            {
                var show = false;

                if (i === mode)
                    show = true;

                for (var k in Fields[i])
                {
                    if (Fields[i].hasOwnProperty(k))
                    {
                        var obj = $("[name=" + Fields[i][k] + "]");

                        if (show)
                        {
                            obj.closest("tr").show();
                        }
                        else
                        {
                            obj.closest("tr").hide();
                            obj.find("option:selected").prop("selected", false);
                        }
                    }

                }
            }
        }
    </script>

<? $arPopups = array(
	"basketWidget",
	"idOfPVZ",
	"PROPS",
	"STATUSES",
	"dimMode_unit",
	"OPT_address",
	"OPT_articul",
	"ADD_PROPS_BUTTON",
	"oldTemplate",
	"showWidgetOnProfile",
	"RightsNotAllow"
);

foreach ($arPopups as $pop)
{
	$addStyle = "";
	if ($pop == "oldTemplate")
		$addStyle = "width: 60%";
	?>
    <div id="pop-<?= $pop ?>" class="b-popup" style="display: none; <?= $addStyle ?>">
        <div class="pop-text"><?= GetMessage("TRADE_YANDEX_DELIVERY_HELPER_" . $pop) ?></div>
        <div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
    </div>
	<?
}
?>

<?
$dost = CDeliveryYaHelper::getDelivery();
if ($dost)
{
	if ($dost['ACTIVE'] != 'Y')
	{
		?>
        <tr>
            <td colspan='2'>
                <div class="adm-info-message-wrap adm-info-message-red">
                    <div class="adm-info-message">
                        <div class="adm-info-message-title"><?= GetMessage('TRADE_YANDEX_DELIVERY_NO_ADOST_HEADER') ?></div>
						<?= GetMessage('TRADE_YANDEX_DELIVERY_NO_ADOST_TITLE') ?>
                        <div class="adm-info-message-icon"></div>
                    </div>
                </div>
            </td>
        </tr>
		<?
	}
}
else
{
	?>
    <tr>
        <td colspan='2'>
            <div class="adm-info-message-wrap adm-info-message-red">
                <div class="adm-info-message">
					<? if ($converted)
					{
						?>
                        <div class="adm-info-message-title"><?= GetMessage('TRADE_YANDEX_DELIVERY_NOT_CRTD_HEADER') ?></div>
						<?= GetMessage('TRADE_YANDEX_DELIVERY_NOT_CRTD_TITLE') ?>
					<? }
					else
					{
						?>
                        <div class="adm-info-message-title"><?= GetMessage('TRADE_YANDEX_DELIVERY_NO_DOST_HEADER') ?></div>
						<?= GetMessage('TRADE_YANDEX_DELIVERY_NO_DOST_TITLE') ?>
					<? } ?>
                    <div class="adm-info-message-icon"></div>
                </div>
            </div>
        </td>
    </tr>
	
	<? if ($converted)
{/*?>
		<tr><td>
			<input type="button" onclick="TRADE_YANDEX_DELIVERY_addConvertedDelivery()" value="<?=GetMessage("TRADE_YANDEX_DELIVERY_NOT_CRTD_TITLE_BUTTON")?>">
		</td></tr>
	<?*/
} ?>
<? } ?>


    <tr class="heading"><? //Настройки обмена delivery?>
        <td colspan="2" valign="top" align="center"
            onclick="$('#to_ms_warehouse').closest('tr').show();"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_MSOPTIONS') ?></td>
    </tr>
    <tr>
        <td colspan="2">
			<?= GetMessage("TRADE_YANDEX_DELIVERY_WARN_REQUIRE_OPTIONS") ?>
        </td>
    <tr>
    <tr>
        <td valign="top" align="center">
			<?
			$arConfStyles = array('display:none', 'display:none');
			if ($arRequestConfig)
				$arConfStyles[0] = "";
			else
				$arConfStyles[1] = '';
			?>
            <span id='TRADE_YANDEX_DELIVERY_confGreen'
                  style='color:green;<?= $arConfStyles[0] ?>'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_HASCONFIG') ?></span>
            <span id='TRADE_YANDEX_DELIVERY_confRed'
                  style='color:red;<?= $arConfStyles[1] ?>'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_NOCONFIG') ?></span>
        </td>
        <td valign="top" align="left">
            <a href='javascript:void(0)'
               onclick='TRADE_YANDEX_DELIVERY_CONFIG_openWnd()'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_LOADCONFIG') ?></a>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["msOptions"]); ?>

    <tr class="heading"><? // == Значения из конфига по умолчанию == ?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_DEFAULT_SENDERS_VAL') ?></td>
    </tr>
<? if ($arRequestConfig)
{
	$arSenders = $arRequestConfig["sender_id"];
	$arWarehouses = $arRequestConfig["warehouse_id"];
	
	foreach ($arWarehouses as $num => $warehouse)
	{
		if (!empty($warehouse))
		{
			$warehouseInfo = CDeliveryYaHelper::convertFromUTF(CDeliveryYaDriver::getWarehouseInfo($warehouse));
			if ($warehouseInfo["warehouseInfo"]["data"]["field_name"])
				$arWarehouses[$num] .= " " . $warehouseInfo["warehouseInfo"]["data"]["field_name"];
		}
	}
	
	foreach ($arSenders as $num => $sender)
	{
		if (!empty($sender))
		{
			$senderInfo = CDeliveryYaHelper::convertFromUTF(CDeliveryYaDriver::getSenderInfo($sender));
			if ($senderInfo["clientInfo"]["data"]["field_name"])
				$arSenders[$num] .= " " . $senderInfo["clientInfo"]["data"]["field_name"];
		}
	}
	?>
    <tr>
        <td><?= GetMessage("TRADE_YANDEX_DELIVERY_OPT_default_sender") ?></td>
        <td><? echo TRADE_YANDEX_DELIVERY_getSelect($arSenders, COption::GetOptionString($module_id, 'defaultSender', '0'), "defaultSender"); ?></td>
    </tr>
    <tr>
        <td><?= GetMessage("TRADE_YANDEX_DELIVERY_OPT_default_warehouse") ?></td>
        <td><? echo TRADE_YANDEX_DELIVERY_getSelect($arWarehouses, COption::GetOptionString($module_id, 'defaultWarehouse', '0'), "defaultWarehouse"); ?></td>
    </tr>
<? } ?>
<?
// делаем запрос на расчет доставки, чтобы получить ограничения на оценочную стоимость
CDeliveryYa::$clearOrderData = false;
CDeliveryYa::$cityTo = GetMessage("TRADE_YANDEX_DELIVERY_DEFAULT_FAKE_CITY_TO_CALC");
CDeliveryYa::getDeliveryProfiles(null, null);
$arRes = (array)CDeliveryYa::$calculateRequestResult;

$arAssessedLimits = array();
if (!empty($arRes))
	foreach ($arRes as $res)
	{
		$tress = 0;
		foreach ($res["services"] as $service)
		{
			$calcRules = $service["calculateRules"];
			
			if ($calcRules)
				if ($calcRules["calculate_type"] == "PERCENT_CASH")
					$tress = floatVal($calcRules["max_cost"]) / floatVal($calcRules["service_value"]);
		}
		
		if ($tress)
			$arAssessedLimits[$res["type"]][$res["tariffId"]] = array(
				"deliveryName" => $res["delivery"]["name"],
				"tariffName" => $res["tariffName"],
				"tress" => $tress
			);
	}

ob_start();
?>
    <table class="assessed_limits" style="">
        <thead>
        <tr>
            <td><?= GetMessage("TRADE_YANDEX_DELIVERY_LIMITS_TABLE_deliveryName") ?></td>
            <td><?= GetMessage("TRADE_YANDEX_DELIVERY_LIMITS_TABLE_tariffName") ?></td>
            <td><?= GetMessage("TRADE_YANDEX_DELIVERY_LIMITS_TABLE_tress") ?></td>
        </tr>
        </thead>
		<? foreach ($arAssessedLimits as $delivType => $limit)
		{ ?>
            <tr>
                <td colspan=3 style="text-align: center;"><?= GetMessage("TRADE_YANDEX_DELIVERY_PROFILE_" . $delivType) ?></td>
            </tr>
			<? foreach ($limit as $tariffID => $tariff)
		{ ?>
            <tr>
                <td><?= $tariff["deliveryName"] ?></td>
                <td><?= $tariff["tariffName"] ?></td>
                <td style="text-align: right;"><?= number_format($tariff["tress"], 0, '.', ' ') ?></td>
            </tr>
		<? } ?>
		<? } ?>
    </table>
<?
$limitsHtml = ob_get_contents();
ob_end_clean();
// echo $limitsHtml;
?>

    <tr>
        <td><?= GetMessage("TRADE_YANDEX_DELIVERY_OPT_assessedCostPercent") ?><a href='#' class='PropHint'
                                                                      onclick='return trade_popup_virt("pop-assessed_limits",$(this));'></a>
        </td>
        <td><input type="text" size="" maxlength="255"
                   value="<?= FloatVal(COption::getOptionString($module_id, 'assessedCostPercent', '100')) ?>"
                   name="assessedCostPercent"></td>
    </tr>


    <tr class="heading"><? // == Габариты товаров == ?>
        <td colspan="2" valign="top" align="center"
            onclick="$('.measHide').show();"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_DIMENSIONS') ?></td>
    </tr>

<? // == Единицы измерения ?>
    <tr>
        <td colspan="2" valign="top" align="center"><strong><?= GetMessage('TRADE_YANDEX_DELIVERY_HEADER_MEASUREMENT') ?></strong>
        </td>
    </tr>
    <tr class="measHide">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_sidesMeas') ?></td>
        <td>
			<? $TRADE_YANDEX_DELIVERY_wD = COption::GetOptionString($module_id, 'sidesMeas', 'mm'); ?>
            <select name='sidesMeas' id='sidesMeas' onchange="$('.MeasLbl').html( $(this).val() );">
                <option value='mm' id="sidesMeas_mm" <? if ($TRADE_YANDEX_DELIVERY_wD == 'mm')
					echo 'selected'; ?>><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_mm') ?></option>
                <option value='cm' <? if ($TRADE_YANDEX_DELIVERY_wD == 'cm')
					echo 'selected'; ?>><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_cm') ?></option>
                <option value='m' <? if ($TRADE_YANDEX_DELIVERY_wD == 'm')
					echo 'selected'; ?>><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_m') ?></option>
            </select>
            <span id="sidesMeas_fix" style="display:none"><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_mm') ?></span>
        </td>
    </tr>
<? // == Тип указания свойств ?>
    <tr>
        <td colspan="2" valign="top" align="center">
            <input type='radio' name='sideMode' id='TRADE_YANDEX_DELIVERY_meaMode_unit' value='unit'
                   onclick='TRADE_YANDEX_DELIVERY_measRadio("unit")'><label
                    for='TRADE_YANDEX_DELIVERY_meaMode_unit'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_dimMode_unit') ?></label>&nbsp;
            <input type='radio' name='sideMode' id='TRADE_YANDEX_DELIVERY_meaMode_sep' value='sep'
                   onclick='TRADE_YANDEX_DELIVERY_measRadio("sep")'><label
                    for='TRADE_YANDEX_DELIVERY_meaMode_sep'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_dimMode_sep') ?></label>
            <input type='radio' name='sideMode' id='TRADE_YANDEX_DELIVERY_meaMode_def' value='def'
                   onclick='TRADE_YANDEX_DELIVERY_measRadio("def")'><label
                    for='TRADE_YANDEX_DELIVERY_meaMode_def'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_dimMode_def') ?></label>
        </td>
    </tr>
<? // == ДШВ ?>
    <tr id='TRADE_YANDEX_DELIVERY_mea_unit_prop' class="TRADE_YANDEX_DELIVERY_mea">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_OPT_sides') ?></td>
        <td><? echo TRADE_YANDEX_DELIVERY_getSelect($arIblockProps, COption::GetOptionString($module_id, 'sidesUnit', 'DIMESIONS'), "sidesUnit"); ?></td>
    </tr>
    <tr id='TRADE_YANDEX_DELIVERY_mea_unit' class="TRADE_YANDEX_DELIVERY_mea">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_OPT_sidesUnitSprtr') ?></td>
        <td><input type='text' name='sidesUnitSprtr'
                   value='<?= COption::GetOptionString($module_id, 'sidesUnitSprtr', 'x') ?>'></td>
    </tr>
<? // == 3 свойства ?>
    <tr id='TRADE_YANDEX_DELIVERY_mea_sep_l' class="TRADE_YANDEX_DELIVERY_mea">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_OPT_MEA_SEP_L') ?></td>
        <td><? /*<input type='text' name='TRADE_YANDEX_DELIVERY_MEA_SEP_L' value='<?=$sides['L']?>'>*/ ?><? echo TRADE_YANDEX_DELIVERY_getSelect($arIblockProps, $sides['L'], "TRADE_YANDEX_DELIVERY_MEA_SEP_L"); ?></td>
    </tr>
    <tr id='TRADE_YANDEX_DELIVERY_mea_sep_w' class="TRADE_YANDEX_DELIVERY_mea">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_OPT_MEA_SEP_W') ?></td>
        <td><? /*<input type='text' name='TRADE_YANDEX_DELIVERY_MEA_SEP_W' value='<?=$sides['W']?>'>*/ ?><? echo TRADE_YANDEX_DELIVERY_getSelect($arIblockProps, $sides['W'], "TRADE_YANDEX_DELIVERY_MEA_SEP_W"); ?></td>
    </tr>
    <tr id='TRADE_YANDEX_DELIVERY_mea_sep_h' class="TRADE_YANDEX_DELIVERY_mea">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_OPT_MEA_SEP_H') ?></td>
        <td><? /*<input type='text' name='TRADE_YANDEX_DELIVERY_MEA_SEP_H' value='<?=$sides['H']?>'>*/ ?><? echo TRADE_YANDEX_DELIVERY_getSelect($arIblockProps, $sides['H'], "TRADE_YANDEX_DELIVERY_MEA_SEP_H"); ?></td>
    </tr>

<? // == По умолчанию значения ?>
    <tr id='TRADE_YANDEX_DELIVERY_mea_def' class="TRADE_YANDEX_DELIVERY_mea">
        <td colspan="2" style="text-align:center; padding:5px"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPT_MEA_DEF') ?></td>
    </tr>
    <tr>
        <td colspan="2" valign="top" align="center">
            <strong><?= GetMessage('TRADE_YANDEX_DELIVERY_HEADER_MEASUREMENT_DEF') ?></strong></td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["sidesDefaut"]); ?>

<? // -- ВЕС ?>
    <tr>
        <td colspan="2" valign="top" align="center"><strong><?= GetMessage('TRADE_YANDEX_DELIVERY_HEADER_WEIGHT') ?></strong></td>
    </tr>
    <tr class="measHide">
        <td><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_sidesMeas') ?></td>
        <td>
			<? $TRADE_YANDEX_DELIVERY_wD = COption::GetOptionString($module_id, 'weightMeas', 'g'); ?>
            <select name='weightMeas' id="weightMeas" onchange="$('.WeightLbl').html( $(this).val() );">
                <option value='g' id="weightMeas_g" <? if ($TRADE_YANDEX_DELIVERY_wD == 'g')
					echo 'selected'; ?>><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_g') ?></option>
                <option value='kg' <? if ($TRADE_YANDEX_DELIVERY_wD == 'kg')
					echo 'selected'; ?>><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_kg') ?></option>
            </select>
            <span id="weightMeas_fix" style="display:none"><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_g') ?></span>
        </td>
    </tr>
    <tr>
        <td>
            <input type='radio' name='weiMode' id='TRADE_YANDEX_DELIVERY_weiMode_cat' value='cat'
                   onclick='TRADE_YANDEX_DELIVERY_weiRadio("cat")'><label
                    for='TRADE_YANDEX_DELIVERY_weiMode_cat'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_weiMode_cat') ?></label><br>
            <input type='radio' name='weiMode' id='TRADE_YANDEX_DELIVERY_weiMode_prop' value='prop'
                   onclick='TRADE_YANDEX_DELIVERY_weiRadio("prop")'><label
                    for='TRADE_YANDEX_DELIVERY_weiMode_prop'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_weiMode_prop') ?></label>
        </td>
        <td>
			<?= TRADE_YANDEX_DELIVERY_getSelect($arIblockProps, $TRADE_YANDEX_DELIVERY_weight, "weightPr"); ?>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["weightDefault"]); ?>

<? /*<tr class="heading"><?// Параметры курьера?>
	<td colspan="2" valign="top" align="center"><?=GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_COURIER')?> <a href='#' class='PropHint' onclick='return trade_popup_virt("pop-PROPS",$(this));'></a></td>
</tr>
<?ShowParamsHTMLByArray($arAllOptions["courier"]);?>*/ ?>

    <tr class="heading"><? //Свойства заказа?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_PROPS') ?> <a href='#'
                                                                                                    class='PropHint'
                                                                                                    onclick='return trade_popup_virt("pop-PROPS",$(this));'></a>
        </td>
    </tr>

<?
$buttonFixerShow = false;
foreach ($arAllOptions["orderProps"] as $optValue)
{
	?>
	<? if ($optValue[0] == "addressMode")
{
	?>
    <tr>
        <td colspan="2" valign="top" align="center" style="padding: 5px 0 15px;">
            <br><strong><?= GetMessage("TRADE_YANDEX_DELIVERY_OPT_addressMode") ?></strong><br>
            <input type='radio' name='addressMode' id='TRADE_YANDEX_DELIVERY_addressMode_one' value='one'
                   onclick='TRADE_YANDEX_DELIVERY_addressRadio("one")'><label
                    for='TRADE_YANDEX_DELIVERY_addressMode_one'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_addressMode_one') ?></label>&nbsp;
            <input type='radio' name='addressMode' id='TRADE_YANDEX_DELIVERY_addressMode_sep' value='sep'
                   onclick='TRADE_YANDEX_DELIVERY_addressRadio("sep")'><label
                    for='TRADE_YANDEX_DELIVERY_addressMode_sep'><?= GetMessage('TRADE_YANDEX_DELIVERY_LABEL_addressMode_sep') ?></label>
        </td>
    </tr>
	
	<? if (!$buttonFixerShow)
{
	$buttonFixerShow = true; ?>
	<? ShowParamsHTMLByArray($arAllOptions["templateOptions"]); ?>
    <tr>
        <td style="padding:0 0 20px;" colspan="2" align="center">
			<? if (!CDeliveryYaHelper::isAdmin())
			{
				echo GetMessage("TRADE_YANDEX_DELIVERY_HELPER_RightsNotAllow_AutoPropFix");
			} ?>
            <input <? if (!CDeliveryYaHelper::isAdmin())
			{
				echo "disabled";
			} ?> type="button" class="adm-btn"
                 onclick="TRADE_YANDEX_DELIVERY_propFixerRun('TRADE_YANDEX_DELIVERY_propfix_result')"
                 value="<?= GetMessage("TRADE_YANDEX_DELIVERY_propFix_Start") ?>"
            /> <a href='#' class='PropHint' onclick='return trade_popup_virt("pop-ADD_PROPS_BUTTON",$(this));'></a>

            <div id="TRADE_YANDEX_DELIVERY_propfix_result"></div>
        </td>
    <tr>

<? } ?>

<? }
else
{
	?>

    <tr>
        <td><?= $optValue[1] ?></td>
        <td><? echo TRADE_YANDEX_DELIVERY_getSelect($arOrderProps, COption::GetOptionString($module_id, $optValue[0], $optValue[2]), $optValue[0], true); ?></td>
    </tr>
<? } ?>
<? } ?>


<? //ShowParamsHTMLByArray($arAllOptions["orderProps"]);?>

    <tr class="heading"><? //Статусы заказа?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_STATUS') ?> <a href='#'
                                                                                                     class='PropHint'
                                                                                                     onclick='return trade_popup_virt("pop-STATUSES",$(this));'></a>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["arStatus"]); ?>

    <tr class="heading"><? //Свойства товара?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_GOODS') ?> <a href='#'
                                                                                                    class='PropHint'
                                                                                                    onclick='return trade_popup_virt("pop-OPT_articul",$(this));'></a>
        </td>
    </tr>
    <tr>
        <td><?= GetMessage("TRADE_YANDEX_DELIVERY_OPT_artnumber") ?></td>
        <td>
			<? echo TRADE_YANDEX_DELIVERY_getSelect(array_merge(array("ID" => "ID"), $arIblockProps), COption::GetOptionString($module_id, 'artnumber', 'ARTNUMBER'), "artnumber"); ?>
        </td>
    </tr>
<? //ShowParamsHTMLByArray($arAllOptions["propsDefault"]);?>


    <tr class="heading"><? //Свойства компонента?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('TRADE_YANDEX_DELIVERY_OPTTAB_DELIVS') ?> <a href='#'
                                                                                                     class='PropHint'
                                                                                                     onclick='return trade_popup_virt("pop-idOfPVZ",$(this));'></a>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["delivSigns"]); ?>