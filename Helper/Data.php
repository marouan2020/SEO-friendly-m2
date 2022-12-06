<?php
/**
 * Copyright © marouan-ben-mansour.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Layer\Category\FilterableAttributeList as CategoryFilterableAttributeList;
use Magento\Catalog\Model\Layer\FilterListFactory;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magtunisia\Seofilterurl\Api\FilterModifierInterface;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
  const XML_PATH_ENABLE_SEO_FRIENDLY = 'catalog/seo/enable_friendly_urls';
  /**
   * @var ScopeConfigInterface
   */
  protected $scopeConfig;
  /**
   * @var Resolver
   */
  protected $layerResolver;
  /**
   * @var FilterListFactory
   */
  protected $filterListFactory;
  /**
   * @var UrlInterface
   */
  public $urlBuilder;
  /**
   * @var CategoryFilterableAttributeList
   */
  protected $_categoryFilterableAttributeList;
  /**
   * @var ProductFactory
   */
  protected $_productFactory;
  /**
   * @var array
   */
  public $mappingFilterAndSeoFriendly = [
   "?" => "p",
   "p" => "page",
   "=" => "/"
  ];
  protected $_filterModifier;

  /**
   * Data constructor.
   *
   * @param ScopeConfigInterface $scopeConfig
   * @param Resolver $layerResolver
   * @param FilterListFactory $filterListFactory
   * @param CategoryFilterableAttributeList $categoryFilterableAttributeList
   * @param UrlInterface $urlBuilder
   * @param FilterModifierInterface $filter_modifier
   */
  public function __construct(
   ScopeConfigInterface $scopeConfig,
   Resolver $layerResolver,
   FilterListFactory $filterListFactory,
   CategoryFilterableAttributeList $categoryFilterableAttributeList,
   UrlInterface $urlBuilder,
   FilterModifierInterface $filter_modifier
  )
  {
    $this->scopeConfig = $scopeConfig;
    $this->layerResolver = $layerResolver;
    $this->filterListFactory = $filterListFactory;
    $this->urlBuilder = $urlBuilder;
    $this->_categoryFilterableAttributeList = $categoryFilterableAttributeList;
    $this->_filterModifier = $filter_modifier;
  }

  /**
   * Check the service is enable.
   *
   * @return bool
   */
  public function isEnabledSeoFriendly(): bool
  {
    return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_SEO_FRIENDLY, ScopeInterface::SCOPE_STORE);
  }

  /**
   * Convert url filter magento to url seo friendly.
   *
   * @param array $params
   *
   * @return string
   */
  public function transformToUrlLmdcSeoFriendly(array $params): string
  {
    $path = trim($params['path'], "/");
    $urlLmdcSeoFriendly = "";
    unset($params['path']);
    foreach ($params as $index => $param) {
      foreach ($param as $code => $value) {
        if ($code == "page") {
          $urlLmdcSeoFriendly .= "/page={$value}";
          continue;
        }
        $urlLmdcSeoFriendly .= "/p/{$code}/{$value}";
      }
    }
    return $path . $urlLmdcSeoFriendly;
  }

  /**
   * Add params filter in array
   *
   * @param string $urlFilter
   *
   * @return array
   */
  public function getParamsFromUrl($urlFilter): array
  {
    $parseUrlTArray = parse_url($urlFilter);
    if (empty($parseUrlTArray['query'])) {
      return [];
    }
    $queryToArray = explode("&", $parseUrlTArray['query']);
    $paramsLmdcSeo = [];
    foreach ($queryToArray as $queryItem) {
      $filterToArray = explode("=", $queryItem);
      if ($filterToArray[0] == "p") {
        $paramsLmdcSeo[] = [$this->mappingFilterAndSeoFriendly[$filterToArray[0]] => $filterToArray[1]];
        continue;
      }
      $isMultiFilter = strpos($filterToArray[1], '%2C') !== false;
      $labelFilter = $this->_filterModifier->getOptionLabelById($filterToArray[0], $filterToArray[1], $isMultiFilter);
      $labelFilter = $this->cleaner($labelFilter);
      $titleCodeFilter = str_replace(" ", "-", $this->_filterModifier->getTitleAttributeFilter($filterToArray[0]));
      $paramsLmdcSeo[] = [$titleCodeFilter => $labelFilter];
    }
    $paramsLmdcSeo["path"] = $parseUrlTArray["path"];
    return $paramsLmdcSeo;
  }

  /**
   * Set params to request.
   *
   * @param RequestInterface $request
   * @param String $urlFilter
   *
   * @return RequestInterface
   */
  public function setFilterValues(RequestInterface $request, $urlFilter): RequestInterface
  {
    $parseUrlTArray = parse_url($urlFilter);
    if (empty($parseUrlTArray['query'])) {
      return $request;
    }
    $query = $parseUrlTArray['query'];
    $explodeQuery = explode("&", $query);
    foreach ($explodeQuery as $queryParam) {
      $explodeQueryParam = explode("=", $queryParam);
      $cloneValueQuery = str_replace("%2C", ",", $explodeQueryParam[1]);
      $cloneValueQuery = trim($cloneValueQuery, ",");
      $request->setParams([$explodeQueryParam[0] => $cloneValueQuery]);
    }
    return $request;
  }

  /**
   * Get all activated filter by category id.
   *
   * @param int $categoryId
   *
   * @return array
   *
   * @throws \Magento\Framework\Exception\LocalizedException
   */
  public function getAllFiltersCategoryById($categoryId): array
  {
    $filterList = $this->filterListFactory->create(['filterableAttributes' => $this->_categoryFilterableAttributeList]);
    $layer = clone $this->layerResolver->get();
    $layer->setCurrentCategory($categoryId);
    $filters = $filterList->getFilters($layer);
    $filtersCategoryList = [];
    foreach ($filters as $filter) {
      if ($filter->isEmpty() || $filter->getItemsCount() == 0) {
        continue;
      }
      $valuesFilterItem = [];
      foreach ($filter->getItems() as $item) {
        $valuesFilterItem[] = $item->getValue();
      }
      $filtersCategoryList[] = ["name" => $filter->getName(), "code" => $filter->getRequestVar(), "values" => $valuesFilterItem];
    }
    return $filtersCategoryList;
  }

  /**
   * Convert url seo friendly to origin url filter.
   *
   * @param string $getRequestString
   *
   * @return string
   */
  public function transformUrlSeoFriendlyToUrlFilter($getRequestString): string
  {
    $explodeLmdcSeoUrlFriendly = explode("/p/", $getRequestString);
    $uri = $explodeLmdcSeoUrlFriendly[0];
    unset($explodeLmdcSeoUrlFriendly[0]);
    $filters = "";
    foreach ($explodeLmdcSeoUrlFriendly as $index => $queryParamsLabel) {
      $filterParams = explode("/", $queryParamsLabel);
      $codeAttributeTitle = str_replace("-", " ", $filterParams[0]);
      unset($filterParams[0]);
      $attributesCodes = $this->_filterModifier->getAttributeCodeFromTitle($codeAttributeTitle);
      array_walk($filterParams, function(&$value) {
        $value = preg_replace("/[^A-Za-z0-9 ]/", ' ', $value);
      });
      $filterParams = array_filter($filterParams);
      $attributesCodes = array_column($attributesCodes, "attribute_code");
      $getAttributeCode = $this->_filterModifier->retrieveAttributrCode($attributesCodes, $filterParams);
      if (!$getAttributeCode) {
        continue;
      }
      array_walk($filterParams, function(&$value) use ($getAttributeCode) {
        $value = $this->_filterModifier->getOptionIdByLabel($getAttributeCode["attribute_code"], $value);
      });
      $filters .= $getAttributeCode["attribute_code"] . "=" . implode(",", $filterParams) . "&";
    }
    return trim($uri . "/?" . $filters, "/&,");
  }

  /**
   * Check request url is seo friendly.
   *
   * @param string $urlLmdcSeoFriendly
   *
   * @return bool
   */
  public function isLmdcUrlSeoFriendly($urlLmdcSeoFriendly): bool
  {
    $urlLmdcSeoFriendly = trim($urlLmdcSeoFriendly, "/");
    $findpParamFilter = strpos($urlLmdcSeoFriendly, '/p/');
    return $findpParamFilter !== false;
  }

  /**
   * Validate date before insert they into lmdc seo table.
   *
   * @param array $data
   *
   * @return bool
   */
  public function hasValidDataValues(array $data): bool
  {
    if (strpos($data["target_path"], "/p/")) {
      return false;
    }
    if ((int)$data['category_id'] == 0) {
      return false;
    }
    $parseRequestFilter = parse_url($data["request_filter"]);
    if (!strpos($data["request_filter"], "?") || empty($parseRequestFilter['query'])) {
      return false;
    }
    return true;
  }

  /**
   * Clean text or string.
   *
   * @param string $string
   *
   * @return string
   */
  public function cleaner($string): string
  {
    $textOption = str_replace([" ", "_"], "-", $string);
    $textOption = str_replace("index.php/", "", $textOption);
    $textOption = urldecode($textOption);
    //TODO You can add others replacement here...
    return $textOption;
  }

  /**
   * add /p/ if it is nessary.
   *
   * @param $pathInfos
   * @param $urlLmdcSeoFrindly
   *
   * @return string
   */
  public function resolveQueryPrefix($pathInfos, $urlLmdcSeoFrindly): string
  {
    return substr($pathInfos, -3) == "/p/" ? $urlLmdcSeoFrindly . "/p/" : ltrim($urlLmdcSeoFrindly, "/");
  }

  /**
   * Transform remove url to url seo friendly.
   *
   * @param $url
   * @return string
   * @throws \Safe\Exceptions\UrlException
   */
  public function getTransformerRemoveUrlSeoFriendly($url)
  {
    $parseOriginUrl = \Safe\parse_url($url);
    $query = $parseOriginUrl['query'];
    $urlFriendlySeo = $this->transformUrlSeoFriendlyToUrlFilter($parseOriginUrl['path']);
    $url = str_replace($query, "", $urlFriendlySeo);
    $url = trim($url, "&");
    $url = str_replace(["&&", "?&"], ["&", "?"], $url);
    $urlToSeoFriendlyParams = $this->getParamsFromUrl($url);
    $explodePath = explode("/p/", $parseOriginUrl['path']);
    $urlToSeoFriendlyParams['path'] = $explodePath[0];
    $newUrlSeoFriendly = $this->transformToUrlLmdcSeoFriendly($urlToSeoFriendlyParams);
    $newUrlSeoFriendly = str_replace("index.php/", "", $newUrlSeoFriendly);
    return $this->urlBuilder->getDirectUrl($newUrlSeoFriendly);
  }
}