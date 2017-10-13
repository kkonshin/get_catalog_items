<?php

namespace Phpquerytest;

use Bitrix\Main\DB\Exception;
use \Bitrix\Main\Loader;

Loader::includeModule("catalog");
Loader::includeModule("iblock");

/**
 * Класс выводит содержание торгового каталога.
 * Метод getList принимает обязательный аргумент ID инфоблока, являющегося
 * каталогом товаров, либо каталогом торговых предложений, и необязательный массив свойств.
 * Свойства, выводимые по умолчанию - [ID, Название товара, Тип товара, Количество товара]
 * В массив свойств можно передать "*" - тогда будут выведены все свойства.
 *
 * Метод getIBlockProperties возвращает массив свойств элементов инфоблока по ID инфоблока.
 *
 * Метод getIBlockElementProperty возвращает значение указанного свойства элемента инфоблока.
 */
class GetCatalogItems
{
    public static function getList($iblockId, Array $properties = [])
    {

         $properties = array_merge(["ID", "ELEMENT_NAME", "TYPE", "QUANTITY"], $properties);


        //TODO нормальную обработку исключений


        if ($iblockId <= 0 || !is_int($iblockId)) {
//            throw new Exception("ID информационного блока должен быть целым положительным числом большим нуля");
            return "ID инфоблока = {$iblockId}. Однако ID информационного блока должен быть целым положительным числом большим нуля.";
        }

        $catalogItemsList = [];
        $catalogSkusList = [];

        $catalogIb = \CCatalog::GetByID($iblockId);

        if ($catalogIb === false) {
//            throw new Exception("Этот инфоблок не является торговым каталогом");
            return "Этот инфоблок не является торговым каталогом.";
        }

        $catalogItems = \CCatalogProduct::GetList(
            ["TYPE" => "ASC", "QUANTITY" => "ASC"],
            ["ELEMENT_IBLOCK_ID" => $iblockId],
            false,
            false,
            $properties
        );
        while ($res = $catalogItems->GetNext()) {
            if ($res["TYPE"] === "1") {
                $res["TYPE"] = "Простой товар";
                $catalogItemsList[] = $res;
            } elseif ($res["TYPE"] === "2") {
                $res["TYPE"] = "Комплект";
                $catalogItemsList[] = $res;
            } elseif ($res["TYPE"] === "3") {
                $res["TYPE"] = "Товар с торговыми предложениями";
                $catalogItemsList[] = $res;
            } elseif ($res["TYPE"] === "4") {
                $res["TYPE"] = "Торговое предложение";
                $catalogSkusList[] = $res;
            } elseif ($res["TYPE"] === "5") {
                $res["TYPE"] = "Предложение без товара";
                $catalogSkusList[] = $res;
            }
        }
        if ($catalogIb["PRODUCT_IBLOCK_ID"] <> 0) {
            $catalogItemsList = $catalogSkusList;
        }
        return $catalogItemsList;
    }

    public static function getIBlockProperties($iblockId)
    {
        $iblockPropertiesArray = [];
        $iblockProperties = \CIBlockProperty::GetList([], ["IBLOCK_ID" => $iblockId]);

        while ($res = $iblockProperties->GetNext()) {
            $iblockPropertiesArray[] = $res;
        }
        return $iblockPropertiesArray;
    }

    public static function getIBlockElementProperty($iblockId, Array $properties = [])
    {
        $propertiesArray = [];

        foreach ($properties as $property){
            $property = "PROPERTY_" . $property;
            $propertiesArray[] = $property;
        }

        $propertiesArray = array_merge(["IBLOCK_ID", "ID", "NAME"], $propertiesArray);

        $elementListArray = [];

        $elementList = \CIBlockElement::GetList(["ID" => "ASC"], ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"], false, false, $propertiesArray);

        while ($res = $elementList->GetNext()) {
            $elementListArray[] = $res;
        }
        return $elementListArray;
    }
}