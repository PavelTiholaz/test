<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Libs\Permissions\User;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use \Bitrix\Main\Engine\Response;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class SimpleNewsComponent extends \CBitrixComponent implements Controllerable
{
    protected $nPageSize;
    protected $iblockID;
    protected $cacheTime;
    protected $cacheType;
    protected $activeNews = [];
    protected $rows = [];
    protected $nav;
//
    public function configureActions()
	{
		return [
			'getYear' => [
                'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(
						array(ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST)
					),
					new ActionFilter\Csrf(),
				],
			],
            'getPage' => [
                'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(
						array(ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST)
					),
					new ActionFilter\Csrf(),
				],
			],
		];
	}

    public function onPrepareComponentParams($params)
    {
        if ($params['PAGE_SIZE']) $this->nPageSize = intval($params['PAGE_SIZE']);
        if ($params['IBLOCK_ID']) $this->iblockID = intval($params['IBLOCK_ID']);
        if ($params['CACHE_TIME']) $this->cacheTime = intval($params['CACHE_TIME']);
        if ($params['CACHE_TYPE']) $this->cacheType = $params['CACHE_TYPE'];

        return $params;
    }
    public function executeComponent()
    {
        global $DB;
        Loader::includeModule('iblock');

        // Поиск первой и последней новостей
        $this->arResult['datefrom'] = $this->getDate('ASC');
        $this->arResult['dateto'] = $this->getDate('DESC');

        // Текущий период
        $nowDate = date($DB->DateFormatToPHP(\CSite::GetDateFormat("FULL")), time());//текущая дата
        $nowYear= ConvertDateTime($nowDate, "Y", "ru");
        $this->arResult['nowYear'] =  $nowYear;
        $nextYear = intval($nowYear) + 1;
        $dateTimeFrom = new \Bitrix\Main\Type\DateTime("$nowYear-01-01 00:00:00", "Y-m-d H:i:s");
        $dateTimeTo= new \Bitrix\Main\Type\DateTime("$nextYear-01-01 00:00:00", "Y-m-d H:i:s");

        // Новости за этот год
        $order = array('DATE_CREATE' => 'DESC');
        $filter = array(
            "=IBLOCK_ID" => $this->iblockID,
             "=ACTIVE" => "Y",
             ">=DATE_CREATE"   => $dateTimeFrom,
             "<DATE_CREATE" => $dateTimeTo,
            );

        $arSelect = array('ID',
            "PREVIEW_PICTURE",
            "PREVIEW_TEXT",
            "NAME",
            "ACTIVE_FROM",
            "DATE_CREATE",
            //"DETAIL_TEXT",
            "DETAIL_PICTURE",
            );
        $cache = array();
        if ($this->cacheType == "A" || $this->cacheType == "Y")     
            $cache = array(
                'ttl' => $this->cacheTime,
                'cache_joins' => true,
            );
        
        $newsList = \Bitrix\Iblock\ElementTable::getList(
            array(
                'order' => $order,
                "select" => $arSelect,
                "filter" => $filter,
                "count_total" => true,
                'cache' => $cache,
                "limit" => $this->nPageSize,
           )
        );

        $this->rows = array();
        while($news = $newsList->fetch())
        {
            $this->rows[] = array(
              "ID"              => $news["ID"],
              "DATE_CREATE"     => $news["DATE_CREATE"],
              "ACTIVE_FROM"     => $news["ACTIVE_FROM"],
              "NAME"            => $news["NAME"],
              "PREVIEW_TEXT"    => $news["PREVIEW_TEXT"],
              "PREVIEW_PICTURE" => $news["PREVIEW_PICTURE"],//\CFile::ResizeImageGet($news['PREVIEW_PICTURE'], array('width'=>300, 'height'=>300), BX_RESIZE_IMAGE_PROPORTIONAL, true),
           );
        }
        $this->arResult['rows'] = $this->rows;
        $this->arResult['newsCount'] = $newsList->getCount();

        $this->includeComponentTemplate();
    }

    public function getDate($order)
    {
        $query = new Bitrix\Main\Entity\Query(
            Bitrix\Iblock\ElementTable::getEntity()
        );
        $query->setSelect(array('DATE_CREATE'))
              ->setFilter(array('IBLOCK_ID' => $this->iblockID))
              ->setOrder(array('DATE_CREATE' => $order))
              ->setLimit(1);
        $result = $query->exec()->fetchAll();
        return  $result[0]['DATE_CREATE']->format('Y');
    }

    /****** ACTIONS **********/
    public function getYearAction() {

        $year  = intval($this->request->getPost('year'));
        $pageSize  = intval($this->request->getPost('pagesize'));
        $this->iblockID = intval($this->request->getPost('iblockid'));
        $nextYear = intval($year) + 1;
        $dateTimeFrom = new \Bitrix\Main\Type\DateTime("$year-01-01 00:00:00", "Y-m-d H:i:s");
        $dateTimeTo = new \Bitrix\Main\Type\DateTime("$nextYear-01-01 00:00:00", "Y-m-d H:i:s");
        // Новости за этот год
         $order = array('DATE_CREATE' => 'DESC');
        $filter = array(
            "=IBLOCK_ID" => $this->iblockID,
            "=ACTIVE" => "Y",
            ">=DATE_CREATE"   => $dateTimeFrom,
            "<DATE_CREATE" => $dateTimeTo,
        );

        $arSelect = array('ID',
            "PREVIEW_PICTURE",
            "PREVIEW_TEXT",
            "NAME",
            "ACTIVE_FROM",
            "DATE_CREATE",
            //"DETAIL_TEXT",
            "DETAIL_PICTURE",
        );
        $cache = array(
            'ttl' => 60,
            'cache_joins' => true,
        );
        $newsList = \Bitrix\Iblock\ElementTable::getList(
            array(
                'order' => $order,
                "select" => $arSelect,
                "filter" => $filter,
                "count_total" => true,
                'cache' => $cache,
                //"offset" => $page,
                "limit" => $pageSize,
            )
        );

        $this->rows = array();
        while($news = $newsList->fetch())
        {
            $this->rows[] = array(
                "ID"              => $news["ID"],
                "DATE_CREATE"     => $news["DATE_CREATE"],
                "ACTIVE_FROM"     => $news["ACTIVE_FROM"],
                "NAME"            => $news["NAME"],
                "PREVIEW_TEXT"    => $news["PREVIEW_TEXT"],
                "PREVIEW_PICTURE" => $news["PREVIEW_PICTURE"],//\CFile::ResizeImageGet($news['PREVIEW_PICTURE'], array('width'=>300, 'height'=>300), BX_RESIZE_IMAGE_PROPORTIONAL, true),
            );
        }
        $result = array(
            'newsCount' => $newsList->getCount(),
            'news' => $this->rows,
        );
        return $result;
    }

    public function getPageAction() {

        $year  = intval($this->request->getPost('year'));
        $pageSize  = intval($this->request->getPost('pagesize'));
        $page = intval($this->request->getPost('page'));
        $this->iblockID = intval($this->request->getPost('iblockid'));
        $nextYear = intval($year) + 1;
        $dateTimeFrom = new \Bitrix\Main\Type\DateTime("$year-01-01 00:00:00", "Y-m-d H:i:s");
        $dateTimeTo = new \Bitrix\Main\Type\DateTime("$nextYear-01-01 00:00:00", "Y-m-d H:i:s");
        
        $order = array('DATE_CREATE' => 'DESC');
        $filter = array(
            "=IBLOCK_ID" => $this->iblockID,
            "=ACTIVE" => "Y",
            ">=DATE_CREATE"   => $dateTimeFrom,
            "<DATE_CREATE" => $dateTimeTo,
        );

        $arSelect = array('ID',
            "PREVIEW_PICTURE",
            "PREVIEW_TEXT",
            "NAME",
            "ACTIVE_FROM",
            "DATE_CREATE",
            //"DETAIL_TEXT",
            "DETAIL_PICTURE",
        );
        $cache = array(
            'ttl' => 60,
            'cache_joins' => true,
        );
        $page = ($page - 1)*$pageSize;
        $newsList = \Bitrix\Iblock\ElementTable::getList(
            array(
                'order' => $order,
                "select" => $arSelect,
                "filter" => $filter,
                //"count_total" => true,
                'cache' => $cache,
                "offset" => $page,
                "limit" => $pageSize,
            )
        );

        $this->rows = array();
        while($news = $newsList->fetch())
        {
            $this->rows[] = array(
                "ID"              => $news["ID"],
                "DATE_CREATE"     => $news["DATE_CREATE"],
                "ACTIVE_FROM"     => $news["ACTIVE_FROM"],
                "NAME"            => $news["NAME"],
                "PREVIEW_TEXT"    => $news["PREVIEW_TEXT"],
                "PREVIEW_PICTURE" => $news["PREVIEW_PICTURE"],
            );
        }
        //$this->arResult['rows'] = $this->rows;
        $result = array(
            'news' => $this->rows,
        );
        return $result;
    }


}