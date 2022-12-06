<?php
/**
	* Copyright © marouan-ben-mansour.com All rights reserved.
	* See COPYING.txt for license details.
	*/
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Api;

/**
	* Interface FilterModifierInterface
	*/
interface FilterModifierInterface
{
				/**
					* Create lmdc seo friendly url.
					*
					* @param array $rows
					*
					* @return mixed
					*/
				public function insertLmdcFiltrsUrls(array $rows);

				/**
					* Retrieve Data url by identifier.
					*
					* @param array $conditions
					*
					* @return array
					*/
				public function getLmdcFiltersData(array $conditions = []);

				/**
					* Get all filter urls from table lmdc_filter_urls
					* @return mixed
					*/
				public function getAllLmdcFilterUrls();

				/**
					* Create rewrite url filter category.
					*
					* @param array $lmdcDataUrl
					* @param int $storeId
					* @return mixed
					*/
				public function rewriteLmdcUrlSeoFriendly(array $lmdcDataUrl, $storeId = 1);

				/**
					* Check if lmdc url seo friendly has been rewrited.
					*
					* @param String $requestPath
					*
					* @return bool
					*/
				public function isLmdcUrlSeoFriendlyAlreadyrewrited($requestPath);

				/**
					* Get attribute by title.
					*
					* @param string $getAttributeCodeFromTitle
					*/
				public function getAttributeCodeFromTitle($getAttributeCodeFromTitle);

				/**
					* Get option id.
					*
					* @param string $attributeCode
					* @param string $optionLabel
					*
					* @return int|string
					*/
				public function getOptionIdByLabel($attributeCode, $optionLabel);

				/**
					* Get option label by id.
					*
					* @param string $attributeCode
					* @param int $optionId
					* @param bool $isMutiIds
					*
					* @return string
					*/
				public function getOptionLabelById($attributeCode, $optionId, $isMutiIds = false);

				/**
					*  Get custom attribute title.
					*
					* @param $attributeCode
					*
					* @return mixed
					*/
				public function getTitleAttributeFilter($attributeCode);

				/**
					* Clean table Lmdc Seo Filter Urls.
					*
					* @param array $idsToBeDelete
					* @return mixed
					*/
				public function cleanLmdcSeofilterurl(array $idsToBeDelete);

				/**
					* Delate unused rewrite urls.
					*
					* @param array $requestPaths
					* @return array
					*/
				public function deleteRewriteUrl(array $requestPaths);

				/**
					* @param array $attributesCodes
					* @param $optionLabel
					* @return mixed
					*/
				public function retrieveAttributrCode(array $attributesCodes, $optionLabel);

				/**
					* @param $getRequestString
					* @param $storeId
					* @return mixed
					*/
				public function getRewriteUrlByTargetPath($getRequestString, $storeId);

				/**
					* Get category id by target.
					*
					* @param $targetPath
					* @param int $storeId
					*
					* @return mixed
					*/
				public function getCategoryIdByTargetPath($targetPath, $storeId = 0);

				/**
					* Retrieve category by url key.
					*
					* @param $urlKey
					* @param $storeId
					*
					* @return mixed
					*/
				public function getCategoryByUrlKey($urlKey, $storeId);
}