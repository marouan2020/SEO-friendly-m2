<?php
/**
 * Copyright © marouan-ben-mansour.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magtunisia\Seofilterurl\Rewrite\Magento\Catalog\Layer\Filter;

use Magento\Framework\UrlInterface;
use Magento\Theme\Block\Html\Pager;
use Magtunisia\Seofilterurl\Helper\Data;

/**
 * Class Item
 */
class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{
  /**
   * @var Data
   */
  protected $_seoHelper;

  /**
   * Item constructor.
   *
   * @param Data $seo_helper
   * @param UrlInterface $url
   * @param Pager $htmlPagerBlock
   * @param array $data
   */
  public function __construct(
   Data $seo_helper,
   UrlInterface $url,
   Pager $htmlPagerBlock,
   array $data = []
  )
  {
    $this->_seoHelper = $seo_helper;
    parent::__construct($url, $htmlPagerBlock, $data);
  }

  /**
   * Transform url item to url seo friendly.
   *
   * @return string
   */
  public function getUrl()
  {
    $url = parent::getUrl();
    if ($this->_seoHelper->isEnabledSeoFriendly()) {
      $params = $this->_seoHelper->getParamsFromUrl($url);
      $url = $this->_seoHelper->transformToUrlLmdcSeoFriendly($params);
      $url = str_replace("index.php/", "", $url);
      return $this->_seoHelper->urlBuilder->getDirectUrl($url);
    }
    return $url;
  }

  /**
   * Transform remove url item to url seo friendly.
   *
   * @return mixed|string
   *
   * @throws \Safe\Exceptions\UrlException
   */
  public function getRemoveUrl()
  {
    if ($this->_seoHelper->isEnabledSeoFriendly()) {
      return $this->_seoHelper->getTransformerRemoveUrlSeoFriendly(parent::getUrl());
    }
    return parent::getRemoveUrl();
  }

  /**
   * Get Clear Link Url.
   *
   * @return false|string
   *
   * @throws \Safe\Exceptions\UrlException
   */
  public function getClearLinkUrl()
  {
    if ($this->_seoHelper->isEnabledSeoFriendly()) {
      return $this->_seoHelper->getTransformerRemoveUrlSeoFriendly(parent::getUrl());
    }
    return parent::getClearLinkUrl();
  }
}

