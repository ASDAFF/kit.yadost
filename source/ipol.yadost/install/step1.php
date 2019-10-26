<? if (!check_bitrix_sessid())
	return; ?>
<? IncludeModuleLangFile(__FILE__); ?>

<form action="<? echo $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<? echo LANG ?>">
	
	<? if (!function_exists('curl_init'))
		echo "<br>" . GetMessage("IPOLyadost_NOCURL_TEXT") . "<br><br>"; ?>
	
	<?= GetMessage("IPOLyadost_INSTALL_TEXT") ?><br><br>
    <input type="submit" name="" value="OK">
</form>