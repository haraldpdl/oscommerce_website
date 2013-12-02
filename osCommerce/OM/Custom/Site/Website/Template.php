<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  class Template extends \osCommerce\OM\Core\Site\Admin\Template {

/**
 * Holds the template name value
 *
 * @var string
 * @access protected
 */

    protected $_default_template = 'Amy';

/**
 * Holds the html tags of the page
 *
 * @var array
 * @access protected
 */

    protected $_html_tags = array();

/**
 * Holds the html header tags of the page
 *
 * @var array
 * @access protected
 */

    protected $_html_header_tags = array();

/**
 * Returns the html tags of the page
 *
 * @access public
 * @return string
 */

    public function getHtmlTags() {
      $tag_string = '';

      foreach ( $this->_html_tags as $key => $values) {
        $tag_string .= $key . '="' . implode(' ', $values) . '" ';
      }

      return trim($tag_string);
    }

/**
 * Checks if html tags exists for the page
 *
 * @access public
 * @return boolean
 */

    public function hasHtmlTags() {
      return !empty($this->_html_tags);
    }

/**
 * Adds a html tag to the page
 *
 * @access public
 * @param string $key The key of the html tag
 * @param string $value The value of the html tag
 */

    public function addHtmlTag($key, $value) {
      $this->_html_tags[$key][] = $value;
    }

/**
 * Returns the html header tags of the page
 *
 * @access public
 * @return string
 */

    public function getHtmlHeaderTags() {
      return implode("\n", $this->_html_header_tags);
    }

/**
 * Checks if html header tags exists for the page
 *
 * @access public
 * @return boolean
 */

    public function hasHtmlHeaderTags() {
      return !empty($this->_html_header_tags);
    }

/**
 * Adds a html header tag to the page
 *
 * @access public
 * @param string $tag The value of the header tag
 */

    public function addHtmlHeaderTag($tag) {
      $this->_html_header_tags[] = $tag;
    }
  }
?>
