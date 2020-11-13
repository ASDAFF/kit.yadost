<?
/**
 * Copyright (c) 13/11/2020 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

// class sqldeliveryOrders
class sqldeliveryOrdersTable
{
	// public function deliveryLog($wat,$sign){CKITYadostDriver::deliveryLog($wat,$sign);}
	private static $tableName = "yadost_yadost";
	public static function Add($Data)
    {
        // = $Data = format:
		// PARAMS - ALL INFO
		// ORDER_ID - corresponding order
		// STATUS - response from iml
		// MESSAGE - info from server
		// BARCODE && ENCBARCODE - recieved from logistics
		// OK - 0 / 1 - was confirmed
		// UPTIME - время добавления
		
		global $DB;
        
		if(!$Data['STATUS'])
			$Data['STATUS']='NEW';
		// if($Data['STATUS']=='NEW')
			// $Data['MESSAGE']='';
		// if(is_array($Data['PARAMS'])) {
			// $Data['PARAMS'] = serialize($Data['PARAMS']);
		// }
		
		$Data['UPTIME']=time();
			
		$rec = self::CheckRecord($Data['ORDER_ID']);
		if($rec)
		{
			$strUpdate = $DB->PrepareUpdate(self::$tableName, $Data);
			$strSql = "UPDATE ".self::$tableName." SET ".$strUpdate." WHERE ID=".$rec['ID'];
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		else
		{
			$arInsert = $DB->PrepareInsert(self::$tableName, $Data);
			$strSql =
				"INSERT INTO ".self::$tableName."(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return self::CheckRecord($Data['ORDER_ID']); 
    }
	
	// валидация входных данных
	private static function validate($field, $value)
	{
		$arFields = array(
			"ID" => array(
				"TYPE" => "integer"
			),
			"PARAMS" => array(
				"TYPE" => "json"
			),
			"ORDER_ID" => array(
				"TYPE" => "integer"
			),
			"delivery_ID" => array(
				"TYPE" => "integer"
			),
			"parcel_ID" => array(
				"TYPE" => "integer"
			),
			"STATUS" => array(
				"TYPE" => "string"
			),
			"MESSAGE" => array(
				"TYPE" => "json"
			),
			"UPTIME" => array(
				"TYPE" => "string"
			),
		);
		
		if (!isset($arFields[$field]))
			return array("success" => false, "data" => null);
		
		try {
			$data = call_user_func("self::check".$arFields[$field]["TYPE"], $value);
			$success = true;
		}
		catch (Exception $e)
		{
			$success = false;
			$data = null;
			
			self::$errorMessagesCustom[] = $e->getMessage();
		}
	
		return array("success" => $success, "data" => $data);
	}
	
	private static function checkinteger($value)
	{
		$tmp = intVal($value);
		
		if (!is_int($tmp))
			throw new Exception("Invalid integer value");
		
		return $tmp;
	}
	
	private static function checkstring($value)
	{
		global $DB;
		
		if (is_array($value))
		{
			foreach ($value as $key => $elem)
			{
				if (!is_string($elem))
					throw new Exception("Invalid string ". $elem);
				
				$value[$key] = $DB->forSQL($elem);
			}
			
			return $value;
		}
		else
		{
			if (!is_string($value))
				throw new Exception("Invalid string ". $value);
			
			return "'".$DB->forSQL($value)."'";
		}
	}
	
	private static function checkjson($value)
	{
		if (!json_decode(CKITYadostHelper::convertToUTF($value)))
			throw new Exception("Invalid json");
		
		$tmp = self::checkstring($value);
		
		return $tmp;
	}
	
	// public static function select($arOrder=array("ID","DESC"),$arFilter=array(),$arNavStartParams=array())
	public static function getList($inputParams)
	{
		if ($inputParams["order"])
			$arOrder = $inputParams["order"];
		else
			$arOrder=array("ID","DESC");
		
		if ($inputParams["filter"])
			$arFilter = $inputParams["filter"];
		else
			$arFilter=array();
		
		
		global $DB;
		
		$strSql='';
		
		$where='';
		if(strpos($arFilter['>=UPTIME'],".")!==false)
			$arFilter['>=UPTIME']=strtotime($arFilter['>=UPTIME']);
		if(strpos($arFilter['<=UPTIME'],".")!==false)
			$arFilter['<=UPTIME']=strtotime($arFilter['<=UPTIME']);

	 	if(count($arFilter)>0)
			foreach($arFilter as $field => $value)
			{
				$sign = " = ";
				$signLength = 1;
				
				$arSigns = array(
					"!" => " != ", 
					"<=" => " <= ", 
					">=" => " >= ", 
					">" => " > ", 
					"<" => " < "
				);
				
				foreach ($arSigns as $oneSign => $resSign)
					if(strpos($field, $oneSign) !== false)
					{
						$sign = $resSign;
						$signLength = strlen($oneSign);
						
						$field = substr($field, $signLength);
					}
				
				$validRes = self::validate($field, $value);
				
				if ($validRes["success"])
					$value = $validRes["data"];
				else
				{
					$res = new CDBResult();
					$res->arResult = false;
					return $res;
				}
				
				if ($where)
					$where.=' AND ';
				
				if(is_array($value))
				{
					foreach($value as $val)
					{
						if ($where)
							$where.=' AND ';
						
						$where .= $field. $sign . "'" .$val . "'";
					}
				}
				else
				{
					if ($value === false)
					{
						$where .= $field . " IS ";
						if ($sign != " = ")
							$where .= "NOT";
						$where .= " NULL";
					}
					else
						$where .= $field . $sign . $value;
				}
			}
		
		if($where) 
			$strSql.="WHERE ".$where;
			
		if(in_array($arOrder[0],array('ID','ORDER_ID','STATUS','UPTIME'))&&($arOrder[1]=='ASC'||$arOrder[1]=='DESC'))
			$strSql.="
			ORDER BY ".$arOrder[0]." ".$arOrder[1];
		
		$cnt=$DB->Query("SELECT COUNT(*) as C FROM ".self::$tableName." ".$strSql, false, $err_mess.__LINE__)->Fetch();
		
		if($arNavStartParams['nPageSize']==0)
			$arNavStartParams['nPageSize']=$cnt['C'];
		
		
		$strSql = "SELECT * FROM ".self::$tableName." ".$strSql;
		
		// die($strSql);
		
		$res = new CDBResult();
		$res->NavQuery($strSql, $cnt['C'], $arNavStartParams);

		return $res;
	}
		
	public static function Delete($orderId)
    {
		global $DB;
		$orderId = $DB->ForSql($orderId);
		$strSql =
            "DELETE FROM ".self::$tableName." 
            WHERE ORDER_ID='".$orderId."'";
		$DB->Query($strSql, true);
        
        return true; 
    }
	
	// public static function GetByOI($orderId)
	// {
		// global $DB;
		// $orderId=$DB->ForSql($orderId);
		// $strSql =
            // "SELECT PARAMS, STATUS, delivery_ID, MESSAGE, OK, MESS_ID ".
            // "FROM ".self::$tableName." ".
			// "WHERE ORDER_ID = '".$orderId."'";
		// $res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		// $arReturn=array();
		// if($arr = $res->Fetch())
			// return $arr;
		// else return false;
	// }
	static $errorMessagesCustom = null;
	private static function clearErrorMessages()
	{
		self::$errorMessagesCustom = null;
	}
	
	public static function getErrorMessagesCustom()
	{
		return self::$errorMessagesCustom;
	}
	
	public static function CheckRecord($orderId)
	{
		global $DB;
		
		$orderId = $DB->ForSql($orderId);
        $strSql =
            "SELECT ID, STATUS, delivery_ID, parcel_ID ".
            "FROM ".self::$tableName." ".
			"WHERE ORDER_ID = '".$orderId."'";
	
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res)
		{
			if($arr = $res->Fetch())
				return $arr;
		}
		return false;
	}
	
	public static function updateCustom($arFilter, $arFields)
	{
		if ($arFilter["ORDER_ID"] && !self::CheckRecord($arFilter["ORDER_ID"]))
		{
			self::Add(array("ORDER_ID" => $arFilter["ORDER_ID"]));
		}
		
		self::clearErrorMessages();
		
		$strSql = "UPDATE ". self::$tableName. " ";
		
		$set = "";
		foreach ($arFields as $key => $field)
		{
			if (!$set)
				$set .= "SET ";
			else
				$set .= ", ";
			
			$set .= $key."=";
			
			$validRes = self::validate($key, $field);
				
			if ($validRes["success"])
				$field = $validRes["data"];
			else
				return false;
			
			$set .= $field;
			// if (is_numeric($field))
				// $set .= $field;
			// else
				// $set .= "'".$field."'";
		}
		
		$where = "";
		foreach ($arFilter as $key => $field)
		{
			if (!$where)
				$where .= "WHERE ";
			
			if (is_array($field))
			{
				$where .= $key." IN (";
				
				$in_where = "";
				foreach ($field as $fkey => $fval)
				{
					$validRes = self::validate($field, $fval);
				
					if ($validRes["success"])
						$fval = $validRes["data"];
					else
						return false;
					
					if ($in_where)
						$in_where .= ", ";
					
					$in_where .= $fval;
				}
				
				$where .= $in_where.")";
			}
			else
			{
				$validRes = self::validate($key, $field);
				
				if ($validRes["success"])
					$field = $validRes["data"];
				else
					return false;
					
				$where .= $key."=".$field;
			}
		}
		
		if ($set)
		{
			$strSql .= $set." ".$where;
			
			global $DB;
			
			$DB->Query($strSql, false, "SQL error: ". __FILE__ . __LINE__);
			return true;
		}
		
		return false;
	}
	
	// public static function updateStatus($arParams){
		// global $DB;
		// foreach($arParams as $key => $val)
			// $arParams[$key] = $DB->ForSql($val);

		// $okStat='';
		// if($arParams["STATUS"]=='OK')
			// $okStat=" OK='1',";
		// elseif($arParams["STATUS"]=='DELETE')
			// $okStat=" OK='',";

		// $setStr = "STATUS ='".$arParams["STATUS"]."', MESSAGE = '".$arParams["MESSAGE"]."',";
		// if($arParams["delivery_ID"])
			// $setStr.="delivery_ID = '".$arParams["delivery_ID"]."',";
		// if($arParams["MESS_ID"])
			// $setStr.="MESS_ID = '".$arParams["MESS_ID"]."',";

		// $setStr.=$okStat." UPTIME= '".mktime()."'";
		
		// $strSql =
            // "UPDATE ".self::$tableName." 
			// SET ".$setStr."
			// WHERE ORDER_ID = '".$arParams["ORDER_ID"]."'";
		
		// if($DB->Query($strSql, true))
			// return true;
		// else 
			// return false;
	// }
	
	// возвращает какие статусы есть в таблице
	// public static function getStatuses($arParams)
	// {
		// global $DB;
		
		// $strSql = "SELECT STATUS FROM ".self::$tableName." GROUP BY STATUS";
		// if($res = $DB->Query($strSql, true))
			// return $res;
		// else 
			// return false;
	// }
}
?>