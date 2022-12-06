<?php
/**
	* Copyright © marouan-ben-mansour.com All rights reserved.
	* See COPYING.txt for license details.
	*/
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magtunisia\Seofilterurl\Helper\Data;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Controller\Router as UrlRewriteRouter;
use Magtunisia\Seofilterurl\Api\FilterModifierInterface;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;

/**
	* Class Router
	*/
class Router extends UrlRewriteRouter implements RouterInterface
{
				/**
					* @var Http $httpRequest
					*/
				protected $_httpRequest;
				/**
					* @var Data $_helper
					*/
				protected $_helper;
				/**
					* @var FilterModifierInterface $_filterModifierInterface
					*/
				protected $_filterModifierInterface;
				protected $_categoryUrlPathGenerator;

				/**
					* Router constructor.
					*
					* @param ActionFactory $actionFactory
					* @param UrlInterface $url
					* @param StoreManagerInterface $storeManager
					* @param ResponseInterface $response
					* @param UrlFinderInterface $urlFinder
					* @param Http $http_request
					* @param Data $helper
					* @param FilterModifierInterface $filter_modifier
					* @param CategoryUrlPathGenerator $category_url_path_generator
					*/
				public function __construct(
				ActionFactory $actionFactory,
				UrlInterface $url,
				StoreManagerInterface $storeManager,
				ResponseInterface $response,
				UrlFinderInterface $urlFinder,
				Http $http_request,
				Data $helper,
				FilterModifierInterface $filter_modifier,
				CategoryUrlPathGenerator $category_url_path_generator
				)
				{
								$this->_httpRequest = $http_request;
								$this->_helper = $helper;
								$this->_filterModifierInterface = $filter_modifier;
								$this->_categoryUrlPathGenerator = $category_url_path_generator;
								parent::__construct($actionFactory, $url, $storeManager, $response, $urlFinder);
				}

				/**
					* Match corresponding navigation URL and modify request
					*
					* @param RequestInterface $request
					* @return \Magento\Framework\App\ActionInterface|null
					* @throws \Magento\Framework\Exception\NoSuchEntityException
					*/
				public function match(RequestInterface $request)
				{
								$matchRequest = parent::match($request);
								if (!$this->_helper->isEnabledSeoFriendly() ||
								($this->_httpRequest->getFrontName() != "catalog" && !$this->validateRoute()) ||
								$this->_httpRequest->isAjax()
								) {
												return $matchRequest;
								}
								if ($this->_httpRequest->isAjax() && $this->validateRoute()) {
												$getRequestString = $this->_httpRequest->getRequestString();
												$getRequestString = $this->_helper->transformUrlSeoFriendlyToUrlFilter($getRequestString);
												$request = $this->_helper->setFilterValues($request, $getRequestString);
												return parent::match($request);
								}

								//check url have param query
								$getRequestString = $this->_httpRequest->getRequestString();
								$parseRequestUrl = parse_url($getRequestString);
								if (empty($parseRequestUrl['query']) && !$this->validateRoute()) {
												return $matchRequest;
								}
								$pathInfos = $this->_httpRequest->getPathInfo();
								$storeId = $this->storeManager->getStore()->getId();
								if ($this->_httpRequest->getFrontName() == "catalog") {
												$pathInfosToArray = explode("/", $pathInfos);
												$categoryId = end($pathInfosToArray);
								} else if ($this->_helper->isLmdcUrlSeoFriendly($pathInfos)) {
												$urlFilter = $this->_helper->transformUrlSeoFriendlyToUrlFilter($pathInfos);
												$urlFilter = parse_url($urlFilter);
												$getCategoryIdByTargetPath = $this->_filterModifierInterface->getRewriteUrlByTargetPath(trim($urlFilter['path'], '/'), $storeId);
												$categoryId = $getCategoryIdByTargetPath['entity_id'];
								} else {
												$getCategoryIdByTargetPath = $this->_filterModifierInterface->getCategoryIdByTargetPath($pathInfos, $storeId);
												$categoryId = $getCategoryIdByTargetPath['entity_id'];
								}

								//check if the route contains /p/ params
								if ($this->validateRoute()) {
												$getLmdcSeoUrlFriendlyData = $this->_filterModifierInterface->getLmdcFiltersData(["request_path" => ltrim($getRequestString, "/")]);

												//if data exist in lmdc seo table but not rewrited so rewrite it.
												if (!empty($getLmdcSeoUrlFriendlyData)) {
																$getRequestString = $getLmdcSeoUrlFriendlyData["request_filter"];
																$categoryId = $getLmdcSeoUrlFriendlyData["category_id"];
																$rewrite = $this->_filterModifierInterface->getRewriteUrlByTargetPath($getLmdcSeoUrlFriendlyData['request_path'], $storeId);
																if (empty($rewrite)) {
																				$this->_filterModifierInterface->rewriteLmdcUrlSeoFriendly($getLmdcSeoUrlFriendlyData, $storeId);
																}
												} else {

																//transform url seo friendly to url filter with ?
																$getRequestString = $this->_helper->transformUrlSeoFriendlyToUrlFilter($getRequestString, $categoryId);
																$parseRequestString = parse_url($getRequestString);
																$parseRequestString["path"] = trim($parseRequestString["path"], "/");
																$parseRequestString["path"] = str_replace("index.php/", "", $parseRequestString["path"]);

																//Check path info from url_rewrite.
																$rewrite = $this->_filterModifierInterface->getRewriteUrlByTargetPath($parseRequestString["path"], $storeId);

																//if base request path not rewrited also we can't find data related to category. rewrite new url in magento
																if (empty($rewrite)) {
																				$getCategoryByUrlKey = $this->_filterModifierInterface->getCategoryByUrlKey($parseRequestString["path"]);
																				$dataRewritePath = [
																				"category_id" => $getCategoryByUrlKey["entity_id"],
																				"request_path" => $getCategoryByUrlKey["value"] . "/",
																				"target_path" => 'catalog/category/view/id/' . $getCategoryByUrlKey["entity_id"],
																				];
																				$rewrite = $this->_filterModifierInterface->rewriteLmdcUrlSeoFriendly($dataRewritePath, $storeId);
																}
																$pathInfos = $rewrite['target_path'];
																$categoryId = $rewrite['entity_id'];
												}
								} else if ($this->_httpRequest->getFrontName() == "catalog") {
												$getLmdcSeoUrlFriendlyData = $this->_filterModifierInterface->getLmdcFiltersData(["request_filter" => ltrim($getRequestString, "/")]);
								}

								//if data is empty then insert it into lmdc seo table
								if (empty($getLmdcSeoUrlFriendlyData)) {
												$getDataToBeInsertIntoLmdcSeo = $this->getDataToBeInsertIntoLmdcSeo($categoryId, $pathInfos);
												$getDataToBeInsertIntoLmdcSeo["request_filter"] = $this->validateRoute() ? ltrim($getRequestString, "/") : $getDataToBeInsertIntoLmdcSeo["request_filter"];
												if (!$this->_helper->hasValidDataValues($getDataToBeInsertIntoLmdcSeo)) {
																return $matchRequest;
												}
												$getLmdcSeoUrlFriendlyData = $this->_filterModifierInterface->insertLmdcFiltrsUrls($getDataToBeInsertIntoLmdcSeo);
								}

								//if requested path not rewrited so rewrite it.
								if (!$this->_filterModifierInterface->isLmdcUrlSeoFriendlyAlreadyrewrited($getLmdcSeoUrlFriendlyData["request_path"])) {
												$this->_filterModifierInterface->rewriteLmdcUrlSeoFriendly($getLmdcSeoUrlFriendlyData, $storeId);
								}

								// set new params to filter.
								$request = $this->_helper->setFilterValues($request, $getLmdcSeoUrlFriendlyData['request_filter']);
								return parent::match($request);
				}

				/**
					* Prepare data to be insert in lmdc seo table.
					*
					* @param $categoryId
					* @param $pathInfos
					*
					* @return array
					*/
				private function getDataToBeInsertIntoLmdcSeo($categoryId, $pathInfos): array
				{
								$getRequestString = $this->_httpRequest->getRequestString();
								$getParamsFromUrl = $this->_helper->getParamsFromUrl($getRequestString);
								$urlLmdcSeoFrindly = (!empty($getParamsFromUrl) && !$this->validateRoute($this->_httpRequest)) ? $this->_helper->transformToUrlLmdcSeoFriendly($getParamsFromUrl) : ltrim($getRequestString, "/");
								$urlLmdcSeoFrindly = $this->_helper->cleaner($urlLmdcSeoFrindly);
								$urlLmdcSeoFrindly = $this->_helper->resolveQueryPrefix($this->_httpRequest->getPathInfo(), $urlLmdcSeoFrindly);

								if ($this->_helper->isLmdcUrlSeoFriendly($getRequestString)) {
												$getRequestString = $this->_helper->transformUrlSeoFriendlyToUrlFilter($getRequestString, $categoryId);
								}
								$getRequestString = str_replace("index.php/", "", $getRequestString);
								$dataSeoFriendlyUrl = [
								"request_filter" => $getRequestString,
								"target_path" => ltrim($pathInfos, "/"),
								"request_path" => $urlLmdcSeoFrindly,
								"category_id" => $categoryId,
								"created_time" => time(),
								"updated_time" => time(),
								];
								return $dataSeoFriendlyUrl;
				}

				/**
					* Check validate url seo friendly urls.
					*
					* @return bool
					*/
				public function validateRoute(): bool
				{
								return strpos($this->_httpRequest->getRequestString(), '/p/') !== false;
				}
}
