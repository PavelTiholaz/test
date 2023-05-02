<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
//require_once($_SERVER["DOCUMENT_ROOT"]."/rest/crest.php");
use CRest;
//use lib\NewsLetterClass;
global $USER;
global $DB;
echo '<pre>';
//print_r(NewsLetterClass::newsLetter());
echo '</pre>';
// \CModule::IncludeModule("iblock");

$APPLICATION->IncludeComponent(
	"custom:simplenews.comp", 
	"nav", 
	array(
		"IBLOCK_ID" => "21",
		"IBLOCK_TYPE_ID" => "lists",
		"PAGE_SIZE" => "5",
		"COMPONENT_TEMPLATE" => "nav",
		"IBLOCK_TYPE" => "news",
		"PAGE_SIZE" => "5",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "180",
		"CACHE_GROUPS" => "Y"
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>