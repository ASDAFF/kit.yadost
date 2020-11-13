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
function KITyadost_getSelect(&$arOptions, $selected, $blockName, $empty = false)
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
            KITyadost_measRadio(<?=CUtil::PHPToJSObject($sideMode)?>);
            KITyadost_addressRadio(<?=CUtil::PHPToJSObject($addressMode)?>);
            KITyadost_weiRadio('<?echo ($KITyadost_weight == "CATALOG_WEIGHT") ? "cat" : "prop"?>');

            $('input[name^="deliv_"]').on('change', KITyadost_delivClick);

            var sideMeas = $('#sidesMeas').val();
            $('.MeasLbl').html(sideMeas);

            var $addressMode = $("[name='addressMode]");
            $addressMode.closest('td').css('border-top', '1px solid #BCC2C4');
            $addressMode.closest('td').siblings().css('border-top', '1px solid #BCC2C4');
        });

        function KITyadost_delivClick(wat)
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

        function KITyadost_clearCache()
        {
            $.ajax({
                url: '/bitrix/js/<?=$module_id?>/ajax.php',
                type: 'POST',
                data: 'action=clearCache&<?=bitrix_sessid_get()?>',
                success: function (data)
                {
                    if (data === 'Y')
                        alert('<?=GetMessage("KITyadost_ALERT_cacheCleared")?>');
                    else
                        alert('<?=GetMessage("KITyadost_ALERT_cacheNotCleared")?>');
                }
            })
        }

        function kit_popup_virt(code, info)
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

        function KITyadost_measRadio(wat)
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

            $('.KITyadost_mea').css('display', 'none');
            $('tr[id^="KITyadost_mea_' + wat + '"]').css('display', '');

            $('#KITyadost_meaMode_' + wat).prop('checked', 'true');
        }

        function KITyadost_weiRadio(wat)
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
            $('#KITyadost_weiMode_' + wat).prop('checked', 'true');
        }

        var KITyadost_CONFIG_wnd = false;
        function KITyadost_CONFIG_openWnd()
        {
            if (!KITyadost_CONFIG_wnd)
            {
                KITyadost_CONFIG_wnd = new BX.CDialog({
                    title: "<?=GetMessage('KITyadost_LABEL_LOADCONFIG')?>",
                    content: "<a href='javascript:void(0)' onclick='$(this).siblings(\"div\").toggle()'><?=GetMessage('KITyadost_LABEL_CONFIGORDER')?></a><div style='display:none'><?=GetMessage('KITyadost_TEXT_ABOUTCONFIG')?></div><br><textarea style='width:650px;height:450px;' id='KITyadost_loadConfig_area1'></textarea><br><br><textarea style='width:650px;height:150px;' id='KITyadost_loadConfig_area2'></textarea>",
                    icon: 'head-block',
                    resizable: true,
                    draggable: true,
                    height: '502',
                    width: '692',
                    buttons: ['<input type="button" <?if (!CKITYadostHelper::isAdmin())
					{
						echo "disabled";
					}?> value="<?=GetMessage('KITyadost_BUTTON_LOAD')?>" onclick="KITyadost_CONFIG_LOAD()"/><input type="button" value="<?=GetMessage('KITyadost_BUTTON_CANSEL')?>" onclick="KITyadost_CONFIG_wnd.Close()"/>']
                });
            }

            KITyadost_CONFIG_wnd.Show();
        }

        function KITyadost_CONFIG_LOAD()
        {
            var txt = $('#KITyadost_loadConfig_area1').val(),
                ids = $('#KITyadost_loadConfig_area2').val();

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
                            $('#KITyadost_confRed').hide();
                            $('#KITyadost_confGreen').show();
                            KITyadost_CONFIG_wnd.Close();
                        }
                        else
                        {
                            alert('<?=GetMessage("KITyadost_ALERT_configNotSaved")?>');
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
                alert('<?=GetMessage("KITyadost_ALERT_noConfig")?>');

        }

        function KITyadost_propFixerRun(wrap)
        {
            var $wrap = $('#' + wrap).empty();
            var personeTypes = [];

            $wrap.append('<?=GetMessage("KITyadost_propFix_PAYERS")?>');
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
                    KITyadost_finish();
                    return;
                }

                var steps = [
                    {name: 'persone', 'text': '<?=GetMessage("KITyadost_propFix_persone")?>'},
                    {name: 'location', 'text': '<?=GetMessage("KITyadost_propFix_location")?>'},
                    {name: 'address', 'text': '<?=GetMessage("KITyadost_propFix_address")?>'},
                    {name: 'zip', 'text': '<?=GetMessage("KITyadost_propFix_zip")?>'}
                ];

                if (stepIndex + 1 > steps.length)
                {
                    return process(typeIndex + 1, 0);
                }

                if (stepIndex === 0)
                {
                    $wrap.append('<br><?=GetMessage("KITyadost_propFix_testType")?> "' + personeTypes[typeIndex].NAME + '"<br>');
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
                            $wrap.append('<br><?=GetMessage("KITyadost_propFix_resFields")?> ' + data.data.join(', ') + '<br>');
                        } else
                        {
                            $wrap.append('OK<br>');
                        }

                        process(typeIndex, stepIndex + 1);
                        return true;
                    } else
                    {
                        $wrap.append('<br><?=GetMessage("KITyadost_propFix_error")?>' + data.data.code + '<br>');

                        var btnContinue = $('<a href="#"><?=GetMessage("KITyadost_propFix_continue")?></a>')
                            .click(function ()
                            {
                                // process(typeIndex + 1, 0);
                                process(typeIndex, stepIndex + 1);
                                return false;
                            });

                        var btnBreak = $('<a href="#"><?=GetMessage("KITyadost_propFix_stop")?></a>')
                            .click(function ()
                            {
                                KITyadost_finish();
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

            function KITyadost_finish()
            {
                $wrap.append('<br><?=GetMessage("KITyadost_propFix_finished")?>');
            }
        }

        function KITyadost_addressRadio(mode)
        {
            $('#KITyadost_addressMode_' + mode).prop('checked', 'true');

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
        <div class="pop-text"><?= GetMessage("KITyadost_HELPER_" . $pop) ?></div>
        <div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
    </div>
	<?
}
?>

<?
$dost = CKITYadostHelper::getDelivery();
if ($dost)
{
	if ($dost['ACTIVE'] != 'Y')
	{
		?>
        <tr>
            <td colspan='2'>
                <div class="adm-info-message-wrap adm-info-message-red">
                    <div class="adm-info-message">
                        <div class="adm-info-message-title"><?= GetMessage('KITyadost_NO_ADOST_HEADER') ?></div>
						<?= GetMessage('KITyadost_NO_ADOST_TITLE') ?>
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
                        <div class="adm-info-message-title"><?= GetMessage('KITyadost_NOT_CRTD_HEADER') ?></div>
						<?= GetMessage('KITyadost_NOT_CRTD_TITLE') ?>
					<? }
					else
					{
						?>
                        <div class="adm-info-message-title"><?= GetMessage('KITyadost_NO_DOST_HEADER') ?></div>
						<?= GetMessage('KITyadost_NO_DOST_TITLE') ?>
					<? } ?>
                    <div class="adm-info-message-icon"></div>
                </div>
            </div>
        </td>
    </tr>
	
	<? if ($converted)
{/*?>
		<tr><td>
			<input type="button" onclick="KITyadost_addConvertedDelivery()" value="<?=GetMessage("KITyadost_NOT_CRTD_TITLE_BUTTON")?>">
		</td></tr>
	<?*/
} ?>
<? } ?>


    <tr class="heading"><? //Настройки обмена delivery?>
        <td colspan="2" valign="top" align="center"
            onclick="$('#to_ms_warehouse').closest('tr').show();"><?= GetMessage('KITyadost_OPTTAB_MSOPTIONS') ?></td>
    </tr>
    <tr>
        <td colspan="2">
			<?= GetMessage("KITyadost_WARN_REQUIRE_OPTIONS") ?>
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
            <span id='KITyadost_confGreen'
                  style='color:green;<?= $arConfStyles[0] ?>'><?= GetMessage('KITyadost_LABEL_HASCONFIG') ?></span>
            <span id='KITyadost_confRed'
                  style='color:red;<?= $arConfStyles[1] ?>'><?= GetMessage('KITyadost_LABEL_NOCONFIG') ?></span>
        </td>
        <td valign="top" align="left">
            <a href='javascript:void(0)'
               onclick='KITyadost_CONFIG_openWnd()'><?= GetMessage('KITyadost_LABEL_LOADCONFIG') ?></a>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["msOptions"]); ?>

    <tr class="heading"><? // == Значения из конфига по умолчанию == ?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('KITyadost_OPTTAB_DEFAULT_SENDERS_VAL') ?></td>
    </tr>
<? if ($arRequestConfig)
{
	$arSenders = $arRequestConfig["sender_id"];
	$arWarehouses = $arRequestConfig["warehouse_id"];
	
	foreach ($arWarehouses as $num => $warehouse)
	{
		if (!empty($warehouse))
		{
			$warehouseInfo = CKITYadostHelper::convertFromUTF(CKITYadostDriver::getWarehouseInfo($warehouse));
			if ($warehouseInfo["warehouseInfo"]["data"]["field_name"])
				$arWarehouses[$num] .= " " . $warehouseInfo["warehouseInfo"]["data"]["field_name"];
		}
	}
	
	foreach ($arSenders as $num => $sender)
	{
		if (!empty($sender))
		{
			$senderInfo = CKITYadostHelper::convertFromUTF(CKITYadostDriver::getSenderInfo($sender));
			if ($senderInfo["clientInfo"]["data"]["field_name"])
				$arSenders[$num] .= " " . $senderInfo["clientInfo"]["data"]["field_name"];
		}
	}
	?>
    <tr>
        <td><?= GetMessage("KITyadost_OPT_default_sender") ?></td>
        <td><? echo KITyadost_getSelect($arSenders, COption::GetOptionString($module_id, 'defaultSender', '0'), "defaultSender"); ?></td>
    </tr>
    <tr>
        <td><?= GetMessage("KITyadost_OPT_default_warehouse") ?></td>
        <td><? echo KITyadost_getSelect($arWarehouses, COption::GetOptionString($module_id, 'defaultWarehouse', '0'), "defaultWarehouse"); ?></td>
    </tr>
<? } ?>
<?
// делаем запрос на расчет доставки, чтобы получить ограничения на оценочную стоимость
CKITYadost::$clearOrderData = false;
CKITYadost::$cityTo = GetMessage("KITyadost_DEFAULT_FAKE_CITY_TO_CALC");
CKITYadost::getDeliveryProfiles(null, null);
$arRes = (array)CKITYadost::$calculateRequestResult;

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
            <td><?= GetMessage("KITyadost_LIMITS_TABLE_deliveryName") ?></td>
            <td><?= GetMessage("KITyadost_LIMITS_TABLE_tariffName") ?></td>
            <td><?= GetMessage("KITyadost_LIMITS_TABLE_tress") ?></td>
        </tr>
        </thead>
		<? foreach ($arAssessedLimits as $delivType => $limit)
		{ ?>
            <tr>
                <td colspan=3 style="text-align: center;"><?= GetMessage("KITyadost_PROFILE_" . $delivType) ?></td>
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
        <td><?= GetMessage("KITyadost_OPT_assessedCostPercent") ?><a href='#' class='PropHint'
                                                                      onclick='return kit_popup_virt("pop-assessed_limits",$(this));'></a>
        </td>
        <td><input type="text" size="" maxlength="255"
                   value="<?= FloatVal(COption::getOptionString($module_id, 'assessedCostPercent', '100')) ?>"
                   name="assessedCostPercent"></td>
    </tr>


    <tr class="heading"><? // == Габариты товаров == ?>
        <td colspan="2" valign="top" align="center"
            onclick="$('.measHide').show();"><?= GetMessage('KITyadost_OPTTAB_DIMENSIONS') ?></td>
    </tr>

<? // == Единицы измерения ?>
    <tr>
        <td colspan="2" valign="top" align="center"><strong><?= GetMessage('KITyadost_HEADER_MEASUREMENT') ?></strong>
        </td>
    </tr>
    <tr class="measHide">
        <td><?= GetMessage('KITyadost_LABEL_sidesMeas') ?></td>
        <td>
			<? $KITyadost_wD = COption::GetOptionString($module_id, 'sidesMeas', 'mm'); ?>
            <select name='sidesMeas' id='sidesMeas' onchange="$('.MeasLbl').html( $(this).val() );">
                <option value='mm' id="sidesMeas_mm" <? if ($KITyadost_wD == 'mm')
					echo 'selected'; ?>><?= GetMessage('KITyadost_LABEL_mm') ?></option>
                <option value='cm' <? if ($KITyadost_wD == 'cm')
					echo 'selected'; ?>><?= GetMessage('KITyadost_LABEL_cm') ?></option>
                <option value='m' <? if ($KITyadost_wD == 'm')
					echo 'selected'; ?>><?= GetMessage('KITyadost_LABEL_m') ?></option>
            </select>
            <span id="sidesMeas_fix" style="display:none"><?= GetMessage('KITyadost_LABEL_mm') ?></span>
        </td>
    </tr>
<? // == Тип указания свойств ?>
    <tr>
        <td colspan="2" valign="top" align="center">
            <input type='radio' name='sideMode' id='KITyadost_meaMode_unit' value='unit'
                   onclick='KITyadost_measRadio("unit")'><label
                    for='KITyadost_meaMode_unit'><?= GetMessage('KITyadost_LABEL_dimMode_unit') ?></label>&nbsp;
            <input type='radio' name='sideMode' id='KITyadost_meaMode_sep' value='sep'
                   onclick='KITyadost_measRadio("sep")'><label
                    for='KITyadost_meaMode_sep'><?= GetMessage('KITyadost_LABEL_dimMode_sep') ?></label>
            <input type='radio' name='sideMode' id='KITyadost_meaMode_def' value='def'
                   onclick='KITyadost_measRadio("def")'><label
                    for='KITyadost_meaMode_def'><?= GetMessage('KITyadost_LABEL_dimMode_def') ?></label>
        </td>
    </tr>
<? // == ДШВ ?>
    <tr id='KITyadost_mea_unit_prop' class="KITyadost_mea">
        <td><?= GetMessage('KITyadost_OPT_sides') ?></td>
        <td><? echo KITyadost_getSelect($arIblockProps, COption::GetOptionString($module_id, 'sidesUnit', 'DIMESIONS'), "sidesUnit"); ?></td>
    </tr>
    <tr id='KITyadost_mea_unit' class="KITyadost_mea">
        <td><?= GetMessage('KITyadost_OPT_sidesUnitSprtr') ?></td>
        <td><input type='text' name='sidesUnitSprtr'
                   value='<?= COption::GetOptionString($module_id, 'sidesUnitSprtr', 'x') ?>'></td>
    </tr>
<? // == 3 свойства ?>
    <tr id='KITyadost_mea_sep_l' class="KITyadost_mea">
        <td><?= GetMessage('KITyadost_OPT_MEA_SEP_L') ?></td>
        <td><? /*<input type='text' name='KITyadost_MEA_SEP_L' value='<?=$sides['L']?>'>*/ ?><? echo KITyadost_getSelect($arIblockProps, $sides['L'], "KITyadost_MEA_SEP_L"); ?></td>
    </tr>
    <tr id='KITyadost_mea_sep_w' class="KITyadost_mea">
        <td><?= GetMessage('KITyadost_OPT_MEA_SEP_W') ?></td>
        <td><? /*<input type='text' name='KITyadost_MEA_SEP_W' value='<?=$sides['W']?>'>*/ ?><? echo KITyadost_getSelect($arIblockProps, $sides['W'], "KITyadost_MEA_SEP_W"); ?></td>
    </tr>
    <tr id='KITyadost_mea_sep_h' class="KITyadost_mea">
        <td><?= GetMessage('KITyadost_OPT_MEA_SEP_H') ?></td>
        <td><? /*<input type='text' name='KITyadost_MEA_SEP_H' value='<?=$sides['H']?>'>*/ ?><? echo KITyadost_getSelect($arIblockProps, $sides['H'], "KITyadost_MEA_SEP_H"); ?></td>
    </tr>

<? // == По умолчанию значения ?>
    <tr id='KITyadost_mea_def' class="KITyadost_mea">
        <td colspan="2" style="text-align:center; padding:5px"><?= GetMessage('KITyadost_OPT_MEA_DEF') ?></td>
    </tr>
    <tr>
        <td colspan="2" valign="top" align="center">
            <strong><?= GetMessage('KITyadost_HEADER_MEASUREMENT_DEF') ?></strong></td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["sidesDefaut"]); ?>

<? // -- ВЕС ?>
    <tr>
        <td colspan="2" valign="top" align="center"><strong><?= GetMessage('KITyadost_HEADER_WEIGHT') ?></strong></td>
    </tr>
    <tr class="measHide">
        <td><?= GetMessage('KITyadost_LABEL_sidesMeas') ?></td>
        <td>
			<? $KITyadost_wD = COption::GetOptionString($module_id, 'weightMeas', 'g'); ?>
            <select name='weightMeas' id="weightMeas" onchange="$('.WeightLbl').html( $(this).val() );">
                <option value='g' id="weightMeas_g" <? if ($KITyadost_wD == 'g')
					echo 'selected'; ?>><?= GetMessage('KITyadost_LABEL_g') ?></option>
                <option value='kg' <? if ($KITyadost_wD == 'kg')
					echo 'selected'; ?>><?= GetMessage('KITyadost_LABEL_kg') ?></option>
            </select>
            <span id="weightMeas_fix" style="display:none"><?= GetMessage('KITyadost_LABEL_g') ?></span>
        </td>
    </tr>
    <tr>
        <td>
            <input type='radio' name='weiMode' id='KITyadost_weiMode_cat' value='cat'
                   onclick='KITyadost_weiRadio("cat")'><label
                    for='KITyadost_weiMode_cat'><?= GetMessage('KITyadost_LABEL_weiMode_cat') ?></label><br>
            <input type='radio' name='weiMode' id='KITyadost_weiMode_prop' value='prop'
                   onclick='KITyadost_weiRadio("prop")'><label
                    for='KITyadost_weiMode_prop'><?= GetMessage('KITyadost_LABEL_weiMode_prop') ?></label>
        </td>
        <td>
			<?= KITyadost_getSelect($arIblockProps, $KITyadost_weight, "weightPr"); ?>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["weightDefault"]); ?>

<? /*<tr class="heading"><?// Параметры курьера?>
	<td colspan="2" valign="top" align="center"><?=GetMessage('KITyadost_OPTTAB_COURIER')?> <a href='#' class='PropHint' onclick='return kit_popup_virt("pop-PROPS",$(this));'></a></td>
</tr>
<?ShowParamsHTMLByArray($arAllOptions["courier"]);?>*/ ?>

    <tr class="heading"><? //Свойства заказа?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('KITyadost_OPTTAB_PROPS') ?> <a href='#'
                                                                                                    class='PropHint'
                                                                                                    onclick='return kit_popup_virt("pop-PROPS",$(this));'></a>
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
            <br><strong><?= GetMessage("KITyadost_OPT_addressMode") ?></strong><br>
            <input type='radio' name='addressMode' id='KITyadost_addressMode_one' value='one'
                   onclick='KITyadost_addressRadio("one")'><label
                    for='KITyadost_addressMode_one'><?= GetMessage('KITyadost_LABEL_addressMode_one') ?></label>&nbsp;
            <input type='radio' name='addressMode' id='KITyadost_addressMode_sep' value='sep'
                   onclick='KITyadost_addressRadio("sep")'><label
                    for='KITyadost_addressMode_sep'><?= GetMessage('KITyadost_LABEL_addressMode_sep') ?></label>
        </td>
    </tr>
	
	<? if (!$buttonFixerShow)
{
	$buttonFixerShow = true; ?>
	<? ShowParamsHTMLByArray($arAllOptions["templateOptions"]); ?>
    <tr>
        <td style="padding:0 0 20px;" colspan="2" align="center">
			<? if (!CKITYadostHelper::isAdmin())
			{
				echo GetMessage("KITyadost_HELPER_RightsNotAllow_AutoPropFix");
			} ?>
            <input <? if (!CKITYadostHelper::isAdmin())
			{
				echo "disabled";
			} ?> type="button" class="adm-btn"
                 onclick="KITyadost_propFixerRun('KITyadost_propfix_result')"
                 value="<?= GetMessage("KITyadost_propFix_Start") ?>"
            /> <a href='#' class='PropHint' onclick='return kit_popup_virt("pop-ADD_PROPS_BUTTON",$(this));'></a>

            <div id="KITyadost_propfix_result"></div>
        </td>
    <tr>

<? } ?>

<? }
else
{
	?>

    <tr>
        <td><?= $optValue[1] ?></td>
        <td><? echo KITyadost_getSelect($arOrderProps, COption::GetOptionString($module_id, $optValue[0], $optValue[2]), $optValue[0], true); ?></td>
    </tr>
<? } ?>
<? } ?>


<? //ShowParamsHTMLByArray($arAllOptions["orderProps"]);?>

    <tr class="heading"><? //Статусы заказа?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('KITyadost_OPTTAB_STATUS') ?> <a href='#'
                                                                                                     class='PropHint'
                                                                                                     onclick='return kit_popup_virt("pop-STATUSES",$(this));'></a>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["arStatus"]); ?>

    <tr class="heading"><? //Свойства товара?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('KITyadost_OPTTAB_GOODS') ?> <a href='#'
                                                                                                    class='PropHint'
                                                                                                    onclick='return kit_popup_virt("pop-OPT_articul",$(this));'></a>
        </td>
    </tr>
    <tr>
        <td><?= GetMessage("KITyadost_OPT_artnumber") ?></td>
        <td>
			<? echo KITyadost_getSelect(array_merge(array("ID" => "ID"), $arIblockProps), COption::GetOptionString($module_id, 'artnumber', 'ARTNUMBER'), "artnumber"); ?>
        </td>
    </tr>
<? //ShowParamsHTMLByArray($arAllOptions["propsDefault"]);?>


    <tr class="heading"><? //Свойства компонента?>
        <td colspan="2" valign="top" align="center"><?= GetMessage('KITyadost_OPTTAB_DELIVS') ?> <a href='#'
                                                                                                     class='PropHint'
                                                                                                     onclick='return kit_popup_virt("pop-idOfPVZ",$(this));'></a>
        </td>
    </tr>
<? ShowParamsHTMLByArray($arAllOptions["delivSigns"]); ?>