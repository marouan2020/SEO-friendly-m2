<?php
/**
 * Copyright © marouan-ben-mansour.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Cron;

use Magtunisia\Seofilterurl\Api\FilterModifierInterface;
use Magtunisia\Seofilterurl\Helper\Data;
use Psr\Log\LoggerInterface;

/**
	* Class CleanerLmdcSeofilterurl
	*/
class CleanerLmdcSeofilterurl
{

  /**
   * @var LoggerInterface $_logger
   */
  protected $_logger;

  /**
   * @var FilterModifierInterface $_filterModifier
   */
  protected $_filterModifier;

  /**
   * @var Data $_lmdcHelper
   */
  protected $_lmdcHelper;

  /**
   * CleanerLmdcSeofilterurl constructor.
   *
   * @param Data $lmdc_helper
   * @param FilterModifierInterface $filter_modifier_interface
   * @param LoggerInterface $logger
   */
  public function __construct(
    Data $lmdc_helper,
    FilterModifierInterface $filter_modifier_interface,
    LoggerInterface $logger
  )
  {
    $this->_lmdcHelper = $lmdc_helper;
    $this->_filterModifier = $filter_modifier_interface;
    $this->_logger = $logger;
  }

  /**
   * Execute the cron
   *
   * @return void
   */
  public function execute()
  {
    $getAllLmdcUrls = $this->_filterModifier->getAllLmdcFilterUrls();
    if (empty($getAllLmdcUrls)) {
      return;
    }
    $idsToBeDelete = [];
    foreach ($getAllLmdcUrls as $lmdcFilterData) {
      if (
        !strpos($lmdcFilterData['request_path'], "/p/") ||
        strpos($lmdcFilterData['request_path'], "?") ||
        strpos($lmdcFilterData['request_filter'], "is_scroll") ||
        strpos($lmdcFilterData['request_path'], "is-scroll") ||
        !strpos($lmdcFilterData['request_filter'], "?")
      ) {
        $idsToBeDelete["lmdc"][] = $lmdcFilterData['entity_id'];
        $idsToBeDelete["rewrite"][] = $lmdcFilterData['request_path'];
        continue;
      }
      $parserRequestFilter = parse_url($lmdcFilterData['request_filter']);
      $findParamInRequestFilter = parse_url($lmdcFilterData['request_filter']);
      if (
        empty($findParamInRequestFilter['query']) && !strpos($lmdcFilterData['request_filter'], "/p/") ||
        !empty($parserRequestFilter['query']) && !$this->checkQueryFilters($parserRequestFilter['query'])
      ) {
        $idsToBeDelete["lmdc"][] = $lmdcFilterData['entity_id'];
        $requestPathToArray = explode("/", $lmdcFilterData['request_path']);
        if (count($requestPathToArray) > 1) {
          $idsToBeDelete["rewrite"][] = $lmdcFilterData['request_path'];
        }
        continue;
      }
    }
    if (empty($idsToBeDelete["lmdc"])) {
      return;
    }
    $this->_filterModifier->cleanLmdcSeofilterurl($idsToBeDelete["lmdc"]);
    $this->_filterModifier->deleteRewriteUrl($idsToBeDelete["rewrite"]);
    $this->_logger->addInfo("Cronjob 'Cleaner Lmdc Seo Filter Urls' is executed.");
  }

  private function checkQueryFilters($query)
  {
    $query = urldecode($query);
    $paramsQuery = explode("&", $query);
    $invalidFilters = 0;
    foreach ($paramsQuery as $param) {
      $attributeValue = explode("=", $param);
      $explodeParamValues = explode(",", $attributeValue[1]);
      foreach ($explodeParamValues as $value) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
          $optionId = $this->_filterModifier->getOptionIdByLabel($attributeValue[0], $value);
          if ($optionId === $value) {
            $invalidFilters++;
          }
        } else {
          $optionText = $this->_filterModifier->getOptionLabelById($attributeValue[0], $value);
          if ($optionText === $value) {
            $invalidFilters++;
          }
        }
      }
    }
    if ($invalidFilters > 0) {
      return false;
    }
    return true;
  }
}

