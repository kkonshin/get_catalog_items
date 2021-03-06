<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/shs.parser/classes/phpQuery/phpQuery.php");

require(__DIR__ . "/classes/GetCatalogItems.php");

\Bitrix\Main\Loader::includeModule("iblock");

use \Bitrix\Main\Page\Asset;

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/library/phpquerytest.css", true);

define("PRODUCT_IBLOCK_ID", 12);
define("SKU_IBLOCK_ID", 13);

// отсортируем товары, у которых количество = 0, и торговые предложения, у которых количество равно 0
// и найдем парсеры, у которых возможно не указано количество по умолчанию

$itemsList = Phpquerytest\GetCatalogItems::getList(PRODUCT_IBLOCK_ID, ["AVAILABLE"]);
$skusList = Phpquerytest\GetCatalogItems::getList(SKU_IBLOCK_ID);

// отбор пустых товаров

$nullItems = [];

foreach ($itemsList as $key => $value) {
    if ($value["QUANTITY"] === "0" && $value["~TYPE"] <> "3") {
        $nullItems[$key]["ID"] = $value["ID"];
        $nullItems[$key]["NAME"] = $value["ELEMENT_NAME"];
        $nullItems[$key]["REASON"] = "Причина: количество товара, не имеющего торговых предложений, равно 0.";
    }
}

// отбор пустых sku, sku без товара, sku cо слишком коротким названием

$nullSkus = [];
$orphanSkus = [];
$suspiciousNames = [];

foreach ($skusList as $key => $value) {
    if ($value["QUANTITY"] === "0") {
        $nullSkus[$key]["ID"] = $value["ID"];
        $nullSkus[$key]["NAME"] = $value["ELEMENT_NAME"];
        $nullSkus[$key]["TYPE"] = $value["TYPE"];
        $nullSkus[$key]["REASON"] = "Причина: количество единиц торгового предложения равно 0.";
    }
    if (($value["~TYPE"] === "5" || $value["TYPE"] === "Предложение без товара") && !empty($value["ID"])) {
        $orphanSkus[$key]["ID"] = $value["ID"];
        $orphanSkus[$key]["NAME"] = $value["ELEMENT_NAME"];
        $orphanSkus[$key]["TYPE"] = $value["TYPE"];
        $orphanSkus[$key]["REASON"] = "Причина: торговое предложение не привязано к товару.";
    }
    if (mb_strlen($value["ELEMENT_NAME"]) < 5 || preg_match("/^(\d+|\W)/", $value["ELEMENT_NAME"])) {
        $suspiciousNames[$value["ID"]] = $value["ELEMENT_NAME"];
    }
}

// отбор элементов, у которых не указано свойство "Сайт" (Заполняется у товара, не у ТП)
// отбор товаров, у которых не заполнено свойство "Размер"
$sitelessElementsArray = [];
$sitelessElements = [];

$emptyItemSizeArray = [];
$emptySkuSizeArray = [];

// TODO продумать отбор аргументов

$propertyAll = Phpquerytest\GetCatalogItems::getIBlockProperties(12);
$propertySiteName = Phpquerytest\GetCatalogItems::getIBlockElementProperty(PRODUCT_IBLOCK_ID, ["SITE_NAME"]);
$propertyItemSize = Phpquerytest\GetCatalogItems::getIBlockElementProperty(PRODUCT_IBLOCK_ID, ["SIZE", "SITE_NAME"]);
$propertySkuSize = Phpquerytest\GetCatalogItems::getIBlockElementProperty(SKU_IBLOCK_ID, ["SIZE"]);

//echo "<pre>";
//print_r($propertyAll);
//echo "</pre>";

// простые товары, доступные к покупке, количество > 0, проверить у них заполненность поля "Размер"

$simpleAvailableItems = [];
foreach ($itemsList as $key => $value) {
    if ($value["~TYPE"] === "1" && $value["AVAILABLE"] === "Y" && $value["QUANTITY"] > 0) {
        $simpleAvailableItems[] = $value["ID"];
    }
}

$propertyItemSizeIds = [];
foreach ($propertyItemSize as $key => $value) {
    $propertyItemSizeIds[] = $value["ID"];
}

//echo "<pre>";
//print_r($simpleAvailableItems);
//print_r($propertyItemSizeIds); // отобрать только ID
//echo "</pre>";

// из выборки свойств товаров, в которой содержится свойство "Размер", выбрать только простые доступные товары
$intersectArray = array_intersect($simpleAvailableItems, $propertyItemSizeIds);

// среди этих товаров пробуем найти товары с заполненным размером.
$sizeCandidates = [];
foreach ($propertyItemSize as $key => $value) {
    if (in_array($value["ID"], $intersectArray)) {
        $sizeCandidates[] = $value;
    }
}

// количество элементов в пересечении должно равняться кол-ву элементов в полученном ниже массиве кандидатов на заполненный размер.
if (count($sizeCandidates) === count($intersectArray)) {
//    echo 'Размеры массивов $sizeCandidates и $intersectArray совпадают';
}

// простые товары с заполненным размером (их всего 2, остальные не заполнены)
$simpleItemsFilledSize = [];
foreach ($sizeCandidates as $key => $value) {
    if (!empty($value["PROPERTY_SIZE_VALUE"])) {
        $simpleItemsFilledSize[] = $value;
    }
}

//echo "<pre>";
//print_r($simpleItemsFilledSize);
//echo "</pre>";

// Отберем ТП с пустым размером
$emptySizeSkus = [];
foreach ($propertySkuSize as $key => $value) {
    if (empty($value["PROPERTY_SIZE_VALUE"])) {
        $emptySizeSkus[] = $value;
    }
}

// TODO Подозрительные размеры

$suspiciousSizeSkus = [];



//echo "<pre>";
//echo (count($emptySizeSkus)) . " торговых предложений, у которых не заполнен размер. <br>\n";
//print_r($emptySizeSkus);
//echo "</pre>";

//echo "<pre>";
//print_r($sizeCandidates);
//echo "</pre>";

//echo "<pre>";
//print_r($intersectArray);
//echo "</pre>";

//echo "<pre>";
//print_r($propertySkuSize);
//echo "</pre>";

//echo "<pre>";
//print_r($itemsList);
//echo "</pre>";

//echo "<pre>";
//print_r($skusList);
//echo "</pre>";

foreach ($propertySiteName as $key => $value) {
    if (empty($value["PROPERTY_SITE_NAME_VALUE"]) || $value["PROPERTY_SITE_NAME_VALUE"] === "") {
        $sitelessElementsArray[] = $value;
    }
}
foreach ($sitelessElementsArray as $key => $value) {
    $sitelessElements[$value["ID"]] = $value["NAME"];
}

unset($key, $value);

file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/test/logs/nullItems.log", "Простые товары, у которых количество = 0:\n\n" . print_r($nullItems, true));
file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/test/logs/nullSkus.log", "Торговые предложения, у которых количество = 0:\n\n" . print_r($nullSkus, true));
file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/test/logs/orphanSkus.log", "Не привязанные к товару ТП:\n\n" . print_r($orphanSkus, true));
file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/test/logs/nullSite.log", "Товары, у которых не указан сайт:\n\n" . print_r($sitelessElements, true));
file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/test/logs/suspiciousSkuNames.log", "ТП с неадекватным названием:\n\n" . print_r($suspiciousNames, true));
file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/test/logs/emptySkuSize.log", "Пустой размер у ТП:\n\n" . print_r($emptySizeSkus, true));



// Parser DEMO


/*
$basedir = "http://www.vtsport.ru";

$section = file_get_contents($basedir . "/katalog-brendov/np/gidroobuv-2017.html");

$parsedSection = phpQuery::newDocument($section);

$sectionItem = $parsedSection->find(".browse-view .row .product .link");

$catalogSectionNames = [];

$detailedPagesLinks = [];
$detailedPagesContent = [];
$detailedPropertiesArray = [];

$parsedDetailedPagesContent = [];

$pqsArray = [];

$itemDescriptionsArray = [];
$clearedItemDescriptions = [];

$imageLinksArray = [];

$skuArray = [];

$resultArray = [];

//change to array for each value

$itemCard = "";

foreach ($sectionItem as $el) {
    $pq = pq($el);
    $detailedPagesLinks[] = $pq->find("a")->attr("href");
    $catalogSectionNames[] = $pq->find("a")->text();

}

// create detailed pages html array

foreach ($detailedPagesLinks as $detailedPageLink) {
    $detailedPagesContent[] = file_get_contents($basedir . $detailedPageLink);
}

//create parsed content of each detailed page array

foreach ($detailedPagesContent as $detailedPageContent) {
    $parsedDetailedPagesContent[] = phpQuery::newDocument($detailedPageContent);
}

//find value description before transform it into pq-objects

foreach ($parsedDetailedPagesContent as $parsedDetailedPageContent) {
    $itemDescriptionsArray[] = trim($parsedDetailedPageContent->find(".product-description")->text());
    $imageLinksArray[] = $parsedDetailedPageContent->find("div.main-image a")->attr("href");
    $skuArray["SKU_PROPERTY_NAME"] = $parsedDetailedPageContent->find("div.product_sku > span")->text();
    $skuArray["SKU_PROPERTY_VALUE"] = explode('</span>', $parsedDetailedPageContent->find("div.product_sku")->html())[1];

}

//remove spaces, redundant line-breaks etc. from value-descriptions

foreach ($itemDescriptionsArray as $itemDescription) {
    $itemDescription = preg_replace('/размерная сетка/u', '', $itemDescription);
    $itemDescription = preg_replace('/Описание/u', '', $itemDescription);
    $clearedItemDescriptions[] = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', '<br>', trim($itemDescription));
}

// merging arrays

$resultArray["CATALOG_SECTION_NAMES"] = $catalogSectionNames;
$resultArray["DETAILED_PAGE_LINKS"] = $detailedPagesLinks;
$resultArray["SKU_PROPERTIES"] = $skuArray;
$resultArray["CLEARED_ITEM_DESCRIPTIONS"] = $clearedItemDescriptions;
$resultArray["IMAGE_LINKS"] = $imageLinksArray;
?>

<div class="test-value-container">
<?
foreach ($resultArray["CATALOG_SECTION_NAMES"] as $key => $value){
    $itemCard = "<img width='250' height='150' src={$resultArray["IMAGE_LINKS"][$key]}><br>";
    $itemCard .= "{$resultArray["CATALOG_SECTION_NAMES"][$key]}<br><br>";
    $itemCard .= "{$resultArray["SKU_PROPERTIES"][$key]}";
    $itemCard .= "{$resultArray["CLEARED_ITEM_DESCRIPTIONS"][$key]}";
    ?>
    <div class="value"><?=$itemCard?></div>
<?}?>
</div>

<?//file_put_contents(__DIR__ . "/logs/resultArray.txt", print_r($resultArray, true));?>
*/ ?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>