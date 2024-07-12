<?php

/**************************************************************************
	Copyright (C) Chicken Katsu 2013 - 2024

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$phpInc/ckinc/hash.php");

//#################################################################
//# Auditing for users of the application
//# TBD move to using objstoreDB
//#################################################################
class cAuditAccount{
	public $host = null;
	public $account = null;
	public $user = null;
	public $timestamp = null;
	public $IP = null;
}

//TBD move to using objstoredb
class cAudit{
	const ACCOUNTS_KEY = "cAudit.accounts.key";
	const ACCOUNT_BASE_KEY = "cAudit.account.basekey.";
	const MAX_ENTRIES_PER_USER = 100;
	
	//**************************************************************************************
	public static function audit ($poCredentials, $psEvent){
		cDebug::enter();
		if (! property_exists($poCredentials,"host")) cDebug::error("no host in credentials");
		if (! property_exists($poCredentials,"account")) cDebug::error("no account in credentials");
		if (! method_exists($poCredentials,"get_username")) cDebug::error("no user in credentials");
			
		$oAccount = new cAuditAccount;
		$oAccount->host = $poCredentials->host;
		$oAccount->account = $poCredentials->account;
		$oAccount->user = $poCredentials->get_username();
		$oAccount->timstamp = $sDate = date('d-m-Y H:i:s');
		
		//if this this account hasnt been audited before add to the Audited customers table
		cDebug::write("checking known accounts");
		$sAccountHash = self::pr__get_account_key($oAccount);
		if ( ! cHash::exists($sAccountHash) ){
			cDebug::write("adding to Audited Customers table");
			self::pr__add_audited_account($oAccount);
		}
		
		//if this username hasnt been audited before, add to the known accounts for the account
		cDebug::write("checking known users of account");
		$sUserHash = self::pr__get_user_key($oAccount);
		if ( ! cHash::exists($sUserHash) ){
			cDebug::write("adding username to known Customers table");
			self::pr__add_known_user($oAccount);
		}
		
		//finally add the user audit entry
		cDebug::write("writing audit entry");
		self::pr_add_user_entry($oAccount);
		cDebug::leave();
	}
	
	
	//**************************************************************************************
	public static function get_audited_accounts(){
		return cHash::get(self::ACCOUNTS_KEY);
	}
	
	//**************************************************************************************
	public static function get_known_users($poAccount){
		return cHash::get(self::pr__get_account_key($poAccount));
	}
	
	
	//**************************************************************************************
	public static function get_user_entries($poAccount){
		return cHash::get(self::pr__get_user_key($poAccount));
	}

	//**************************************************************************************
	//*
	//**************************************************************************************
	private static function pr_add_user_entry($poAccount){
		$sHash = self::pr__get_user_key($poAccount);
		
		$aAuditLines = cHash::get($sHash);
		if ($aAuditLines == null) $aAuditLines = [];

		//check size of array - mustnt grow too large
		$iCount = count($aAuditLines);
		while ($iCount >= self::MAX_ENTRIES_PER_USER){
			array_shift($aAuditLines);
			$iCount = count($aAuditLines);
		}
		
		$aAuditLines[] = $poAccount;
		cHash::put($sHash, $aAuditLines, true);
	}

	//**************************************************************************************
	private static function pr__get_user_key($poAccount){
		return self::pr__get_account_key($poAccount).$poAccount->user;
	}
	
	//**************************************************************************************
	private static function pr__get_account_key($poAccount){
		return self::ACCOUNT_BASE_KEY.$poAccount->host.$poAccount->account;
	}
	
	//**************************************************************************************
	private static function pr__add_known_user($poAccount){
		$sHash = self::pr__get_account_key($poAccount);
		$aUsers = cHash::get($sHash);
		if ($aUsers == null) $aUsers = [];
		$aUsers[] = $poAccount;
		cHash::put($sHash, $aUsers, true);
	}
	
	//**************************************************************************************
	private static function pr__add_audited_account($poAccount){
		$aAccounts = self::get_audited_accounts();
		if ($aAccounts == null) $aAccounts = [];
		
		$aAccounts[] = $poAccount;
		cHash::put( self::ACCOUNTS_KEY, $aAccounts, true);
	}
}
?>