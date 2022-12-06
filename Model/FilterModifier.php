<?php
/**
	* Copyright © marouan-ben-mansour.com All rights reserved.
	* See COPYING.txt for license details.
	*/
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Model;

use Magtunisia\Seofilterurl\Api\FilterModifierInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;

/**
	* Class FilterModifier
	*/
class FilterModifier implements FilterModifierInterface
{
				const NAME_TABLE_LMDC_FILTER_URLS = "seo_filter_urls";
				/**
					* @var UrlRewriteFactory
					*/
				protected $_urlRewriteFactory;
				/**
					* @var StoreManagerInterface
					*/
				protected $_storeManager;
				/**
					* @var ResourceConnection
					*/
				protected $_resourceConnection;
				/**
					* @var ProductFactory
					*/
				protected $_productFactory;

				public function __construct(
				ResourceConnection $resource_connection,
				UrlRewriteFactory $url_rewriteFactory,
				ProductFactory $product_factory
				)
				{
								$this->_resourceConnection = $resource_connection;
								$this->_urlRewriteFactory = $url_rewriteFactory;
								$this->_productFactory = $product_factory;
				}

				/**
					* @inheritdoc
					*/
				public function insertLmdcFiltrsUrls(array $rows)
				{
								$connection = $this->_resourceConnection->getConnection();
								$tableName = $connection->getTableName(self::NAME_TABLE_LMDC_FILTER_URLS);
								if ($connection->insert($tableName, $rows)) {
												return $this->getLmdcFiltersData(["request_filter" => $rows["request_filter"]]);
								}
				}

				/**
					* @inheritdoc
					*/
				public function getLmdcFiltersData(array $conditions = [])
				{
								$connection = $this->_resourceConnection->getConnection();
								$select = $connection->select()
								->from(["fseo" => self::NAME_TABLE_LMDC_FILTER_URLS]);
								if (!empty($conditions)) {
												foreach ($conditions as $code => $value) {
																$select->where("fseo.{$code}=?", $value);
												}
								}
								return $connection->fetchRow($select);
				}

				/**
					* @inheritdoc
					*/
				public function getAllLmdcFilterUrls()
				{
								$connection = $this->_resourceConnection->getConnection();
								$select = $connection->select()
								->from(["fseo" => self::NAME_TABLE_LMDC_FILTER_URLS]);
								return $connection->fetchAll($select);
				}

				/**
					* @inheritdoc
					*/
				public function rewriteLmdcUrlSeoFriendly(array $lmdcDataUrl, $storeId = 1)
				{
								try {
												$urlRewriteModel = $this->_urlRewriteFactory->create();
												$urlRewriteModel->setEntityType("category");
												$urlRewriteModel->setStoreId($storeId);
												$urlRewriteModel->setEntityId($lmdcDataUrl["category_id"]);
												$urlRewriteModel->setRequestPath($lmdcDataUrl["request_path"]);
												$urlRewriteModel->setTargetPath($lmdcDataUrl["target_path"]);
												$urlRewriteModel->save();
												return $urlRewriteModel;
								} catch (\Exception $e) {
												return;
								}
				}

				/**
					* @inheritdoc
					*/
				public function isLmdcUrlSeoFriendlyAlreadyrewrited($requestPath)
				{
								$urlRewriteModel = $this->_urlRewriteFactory->create();
								$result = $urlRewriteModel->load($requestPath, "request_path");
								return !$result->isEmpty();
				}

				/**
					* @inheritdoc
					*/
				public function getAttributeCodeFromTitle($getAttributeCodeFromTitle)
				{
								$connection = $this->_resourceConnection->getConnection();
								$select = $connection->select()
								->from(["attribute" => "eav_attribute"])
								->where("attribute.frontend_label like ?", "%{$getAttributeCodeFromTitle}%");
								return $connection->fetchAll($select);
				}

				/**
					* Retrive attribute code bettwen multiple attributes codes.
					*
					* @param array $attributesCodes
					* @param string $optionsLabels
					*
					* @return mixed
					*/
				public function retrieveAttributrCode(array $attributesCodes, $optionsLabels)
				{
								if (count($attributesCodes) == 1) {
												$result['attribute_code'] = reset($attributesCodes);
								}
								$optionBool = $optionsLabels;
								array_walk($optionBool, function(&$value) {
												$value = strtolower($value);
								});
								$connection = $this->_resourceConnection->getConnection();
								if (
								in_array("yes", $optionBool) ||
								in_array("no", $optionBool) ||
								in_array("oui", $optionBool) ||
								in_array("non", $optionBool)
								) {
												$option = (in_array("yes", $optionBool) || in_array("oui", $optionBool)) ? 1 : 0;
												$select = $connection->select()->from(["eaov" => "catalog_product_entity_int"], ["attribute_id"]);
												$select->where("eaov.value = ? ", $option);
												$select->join(["ea" => "eav_attribute"], "ea.attribute_id = eaov.attribute_id", ["attribute_code"]);
								} else {
												$select = $connection->select()->from(["eaov" => "eav_attribute_option_value"], ["option_id"]);
												$select->where("eaov.value IN (?) ", $optionsLabels);
												$select->join(["eao" => "eav_attribute_option"], "eao.option_id = eaov.option_id", ['attribute_id']);
												$select->join(["ea" => "eav_attribute"], "ea.attribute_id = eao.attribute_id", ["attribute_code"]);
								}

								//@TODO feature add conditions for swatcher here...

								if (!empty($attributesCodes)) {
												$select->where("ea.attribute_code IN (?) ", $attributesCodes);
								}
								return $connection->fetchRow($select);
				}

				/**
					* @inheritdoc
					*/
				public function getOptionIdByLabel($attributeCode, $optionLabel)
				{
								$optionBool = strtolower($optionLabel);
								$connection = $this->_resourceConnection->getConnection();
								if (in_array($optionBool, ['yes', 'no', 'non', 'oui'])) {
												$option = in_array($optionBool, ['yes', 'oui']) ? 1 : 0;
												$select = $connection->select()->from(["eaov" => "catalog_product_entity_int"], ["value"]);
												$select->where("eaov.value = ? ", $option);
												$select->join(["ea" => "eav_attribute"], "ea.attribute_id = eaov.attribute_id", ["attribute_code"]);
												$select->where("ea.attribute_code =?", $attributeCode);
												$result = $connection->fetchRow($select);
												if (!array_key_exists("value", $result)) {
																return null;
												}
												return $result['value'];
								} else {
												$select = $connection->select()->from(["eaov" => "eav_attribute_option_value"], ["option_id"]);
												$select->where("eaov.value LIKE ?", "{$optionLabel}%");
												$select->join(["eao" => "eav_attribute_option"], "eao.option_id = eaov.option_id");
												$select->join(["ea" => "eav_attribute"], "eao.attribute_id = ea.attribute_id");
												$select->where("ea.attribute_code =?", $attributeCode);
												$result = $connection->fetchRow($select);
												if (empty($result)) {
																$optionsLabels = explode(" ", $optionLabel);
																$select = $connection->select()->from(["eaov" => "eav_attribute_option_value"]);
																foreach ($optionsLabels as $indexOption => $option) {
																				if (empty($option)) {
																								continue;
																				}
																				$prefixLeftLike = ($indexOption == 0) ? "" : "%";
																				$select->where("eaov.value LIKE '{$prefixLeftLike}{$option}%'");
																}
																$select->join(["eao" => "eav_attribute_option"], "eao.option_id = eaov.option_id")
																->join(["ea" => "eav_attribute"], "eao.attribute_id = ea.attribute_id")
																->where("ea.attribute_code =?", $attributeCode);
																$result = $connection->fetchRow($select);
												}
												if (empty($result['option_id'])) {
																return null;
												}
												return $result["option_id"];
								}

								//@TODO feature add conditions for swatcher here...
				}

				/**
					* @inheritdoc
					*/
				public function getOptionLabelById($attributeCode, $optionId, $isMutiIds = false)
				{
								$product = $this->_productFactory->create();
								$getAttribute = $product->getResource()->getAttribute($attributeCode);
								if (!$getAttribute || !$getAttribute->usesSource()) {
												return $optionId;
								}
								if ($isMutiIds) {
												$optionsIds = explode("%2C", $optionId);
												$optionsText = [];
												foreach ($optionsIds as $optionId) {
																$getOptionText = $getAttribute->getSource()->getOptionText($optionId);
																if ($getOptionText) {
																				$optionsText[] = $getOptionText;
																				continue;
																}
																$optionsText[] = $optionId;
												}
												return implode("/", $optionsText);
								}
								$getOptionText = $getAttribute->getSource()->getOptionText($optionId);
								if ($getOptionText) {
												return $getOptionText;
								}
								return $optionId;
				}

				/**
					* @inheritdoc
					*/
				public function getTitleAttributeFilter($attributeCode)
				{
								$product = $this->_productFactory->create();
								$getAttribute = $product->getResource()->getAttribute($attributeCode);
								if (!$getAttribute || !$getAttribute->usesSource()) {
												return $attributeCode;
								}
								return strtolower($getAttribute->getStoreLabel());
				}

				/**
					* @inheritdoc
					*/
				public function cleanLmdcSeofilterurl(array $idsToBeDelete)
				{
								$connection = $this->_resourceConnection->getConnection();
								$tableLmdcSeofilterurl = $connection->getTableName(self::NAME_TABLE_LMDC_FILTER_URLS);
								try {
												foreach ($idsToBeDelete as $idToDelete) {
																$connection->delete($tableLmdcSeofilterurl, [
																$connection->quoteInto('entity_id = ?', $idToDelete),
																]);
												}
								} catch (\Exception $e) {
												return $e->getMessage();
								}
				}

				/**
					* @inheritdoc
					*/
				public function deleteRewriteUrl(array $requestPaths)
				{
								try {
												$urlRewriteModel = $this->_urlRewriteFactory->create();
												foreach ($requestPaths as $requestPath) {
																$urlRewriteModel->load($requestPath, "request_path");
																$urlRewriteModel->delete();
												}
								} catch (\Exception $e) {
												return $e->getMessage();
								}
				}

				/**
					* @inheritdoc
					*/
				public function getRewriteUrlByTargetPath($targetPath, $storeId = 1)
				{
								$connection = $this->_resourceConnection->getConnection();
								$select = $connection->select()
								->from(["urlr" => "url_rewrite"])
								->where("urlr.request_path  = ?", $targetPath)
								->where("urlr.store_id = ?", $storeId)
								->where("urlr.entity_type = ?", "category");
								$result = $connection->fetchRow($select);
								$checkEndPath = substr($targetPath, -1) == "/";
								if (empty($result) && !$checkEndPath) {
												$select = $connection->select()
												->from(["urlr" => "url_rewrite"])
												->where("urlr.request_path like ?", "{$targetPath}/")
												->where("urlr.store_id = ?", $storeId)
												->where("urlr.entity_type = ?", "category");
												$result = $connection->fetchRow($select);
								}
								return $result;
				}

				/**
					* @inheritdoc
					*/
				public function getCategoryIdByTargetPath($targetPath, $storeId = 1)
				{
								$connection = $this->_resourceConnection->getConnection();
								$select = $connection->select()
								->from(["urlr" => "url_rewrite"], ["entity_id"])
								->where("urlr.target_path = ?", $targetPath)
								->where("urlr.store_id = ?", "{$storeId}")
								->where("urlr.entity_type = ?", "category");
								return $connection->fetchRow($select);
				}

				/**
					* @inheritdoc
					*/
				public function getCategoryByUrlKey($urlKey, $storeId = 0)
				{
								$connection = $this->_resourceConnection->getConnection();
								$select = $connection->select()
								->from(["ccev" => "catalog_category_entity_varchar"])
								->where("ccev.value like ?", "%{$urlKey}%")
								->where("ccev.store_id = ?", $storeId);
								return $connection->fetchRow($select);
				}
}
