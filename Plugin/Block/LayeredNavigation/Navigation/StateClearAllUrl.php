<?php
/**
 * Copyright © marouan-ben-mansour.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Plugin\Block\LayeredNavigation\Navigation;

use Magento\LayeredNavigation\Block\Navigation\State as StateFilter;
use Magtunisia\Seofilterurl\Helper\Data;
use Magento\Framework\App\Request\Http;

/**
 * Class State
 */
class StateClearAllUrl
{
  /**
   * @var Http
   */
  protected $_httpRequest;
  /**
   * @var Data
   */
  protected $_mageTunHelper;

  /**
   * State constructor.
   *
   * @param Data $magetun_helper
   * @param Http $http
   */
  public function __construct(
   Data $magetun_helper,
   Http $http
  )
  {
    $this->_mageTunHelper = $magetun_helper;
    $this->_httpRequest = $http;
  }

  /**
   * regenerate Clear All URL
   *
   * @param StateFilter $subject
   * @param array $result
   *
   * @return array|string
   */
  public function afterGetClearUrl(
   StateFilter $subject,
   $result = []
  )
  {
    if ($this->_mageTunHelper->isEnabledSeoFriendly() && !empty($result)) {
      $getRequestString = $this->_httpRequest->getRequestString();
      $getRequestString = $this->_mageTunHelper->transformUrlSeoFriendlyToUrlFilter($getRequestString);
      $parseUrlFilter = parse_url($getRequestString);
      return $this->_mageTunHelper->urlBuilder->getRouteUrl($parseUrlFilter['path']);
    }
    return $result;
  }
}