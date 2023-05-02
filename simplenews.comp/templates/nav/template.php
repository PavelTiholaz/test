<?php use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global \CMain $APPLICATION */
/** @global \CUser $USER */
/** @global \CDatabase $DB */
/** @var \CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var array $templateData */
/** @var \CBitrixComponent $component */

use Bitrix\Main\Loader;

CJSCore::Init(array("axaj","window"));

$this->setFrameMode(true);
//echo '<pre>',print_r($arParams),'<pre>';
?>
<div> Список новостей: <span id="newscount"><?=$arResult['newsCount']?></span>
</div>
<hr>
<nav class="navbar navbar-light bg-light"><?
    for ($i = $arResult['datefrom']; $i <= $arResult['dateto']; $i++) {
        ?><button class="btn btn-outline-success" type="button" onclick="javascript:getyear(<?=$i?>)" style="color: #025ea1;"><?=$i?></button><?
    }
?></nav>

<table>
    <thead>
    <tr>
        <th>Название</th>
        <th>Дата</th>
        <th>Описание для анонса</th>
        <th>Фото</th>
    </tr>
    </thead>
    <tbody id="newsbody-id">
    <? foreach ($arResult['rows'] as $singleNews) {?>
        <tr>
            <td><?=$singleNews['NAME']?></td>
            <td><?=$singleNews['ACTIVE_FROM']?></td>
            <td><?=$singleNews['PREVIEW_TEXT']?></td>
            <td><?=$singleNews['PREVIEW_PICTURE']?></td>
        </tr><?
    }?>
    </tbody>
</table>
<span>Постраничная навигация:</span>
<div id="pages-id"><?
    for ($i = 1; $i < ($arResult['newsCount'] / $arParams['PAGE_SIZE'] + 1); $i++) {
            ?><button class="btn btn-outline-success" type="button" onclick="javascript:getpage(<?=$i?>, <?=$arResult['dateto']?>)" style="color: #025ea1;"><?=$i?></button><?
        }
?></div>

<div id="success-msg-id"></div>
<div id="error-msg-id"></div>
<script>

function getyear(year) {
    BX.ajax.runComponentAction('custom:simplenews.comp', 'getYear', {
    mode: 'class',
    async: true,
    data: {
        year : year,
        pagesize : <?=$arParams['PAGE_SIZE']?>,
        iblockid : <?=$arParams['IBLOCK_ID']?>,
    },
    }).then(function (response) {
        if (response['data']['newsCount']) {
            BX.adjust(BX('newscount'), {text: response['data']['newsCount']});
            //NEWS_TABLE
            html = ''
            for (news in response['data']['news']) {
            html += `<tr>
                <td>${response['data']['news'][news]['NAME']}</td>
                <td>${response['data']['news'][news]['ACTIVE_FROM']}</td>
                <td>${response['data']['news'][news]['PREVIEW_TEXT']}</td>
                <td>${response['data']['news'][news]['PREVIEW_PICTURE']}</td>
            </tr>`
            }
            BX.adjust(BX('newsbody-id'), {html: html});
            //PAGES 
            html = ''
            for (let i = 1; i < (response['data']['newsCount'] / <?=$arParams['PAGE_SIZE']?> + 1); i++) {
                html += `<button class="btn btn-outline-success" type="button" onclick="javascript:getpage(${i}, ${year})" style="color: #025ea1;">${i}</button>`
            }
            BX.adjust(BX('pages-id'), {html: html});
        }
        if (response['data']['msg'])
            BX.adjust(BX('success-msg-id'), {text: response['data']['msg']});
    }), function (response) {
    BX.adjust(BX('error-msg-id'), {text: response['data']['err']});
    }
}

function getpage(page, year) {
    BX.ajax.runComponentAction('custom:simplenews.comp', 'getPage', {
    mode: 'class',
    async: true,
    data: {
        year : year,
        page : page,
        pagesize : <?=$arParams['PAGE_SIZE']?>,
        iblockid : <?=$arParams['IBLOCK_ID']?>,
    },
    }).then(function (response) {
        if (response['data']['news']) {
            //BX.adjust(BX('newscount'), {text: response['data']['newsCount']});
            //NEWS_TABLE
            html = ''
            for (news in response['data']['news']) {
            html += `<tr>
                <td>${response['data']['news'][news]['NAME']}</td>
                <td>${response['data']['news'][news]['ACTIVE_FROM']}</td>
                <td>${response['data']['news'][news]['PREVIEW_TEXT']}</td>
                <td>${response['data']['news'][news]['PREVIEW_PICTURE']}</td>
            </tr>`
            }
            BX.adjust(BX('newsbody-id'), {html: html});
            //PAGES 
            // html = ''
            // for (let i = 1; i < (response['data']['newsCount'] / <?=$arParams['PAGE_SIZE']?> + 1); i++) {
            //     html += `<button class="btn btn-outline-success" type="button" onclick="javascript:getpage(${i}, ${year})" style="color: #025ea1;">${i}</button>`
            // }
            // BX.adjust(BX('pages-id'), {html: html});
        }
        if (response['data']['msg'])
            BX.adjust(BX('success-msg-id'), {text: response['data']['msg']});
    }), function (response) {
    BX.adjust(BX('error-msg-id'), {text: response['data']['err']});
    }
}
</script>