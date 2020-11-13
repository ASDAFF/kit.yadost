<?if(!check_bitrix_sessid()) return;?>
<?IncludeModuleLangFile(__FILE__);?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<?=GetMessage("TRADE_YANDEX_DELIVERY_DEL_TEXT")?><br>
	<input type="submit" name="" value="Ok">
</form>