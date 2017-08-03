<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\HTML;

class Template extends \osCommerce\OM\Core\Site\Admin\Template
{

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

    protected $html_tags = [];

/**
 * Holds the html elements of the page
 *
 * @var array
 * @access protected
 */

    protected $html_elements = [];

/**
 * HPDL: Temporarily override core method to avoid double encoding with HTML::outputProtected() and template {value} tag
 */

    public function getPageTitle()
    {
        return $this->_application->getPageTitle();
    }

/**
 * Returns the html tags of the page
 *
 * @access public
 * @return string
 */

    public function getHtmlTags()
    {
        $tag_string = '';

        foreach ($this->html_tags as $key => $values) {
            $tag_string .= HTML::outputProtected($key) . '="' . HTML::outputProtected(implode(' ', $values)) . '" ';
        }

        return trim($tag_string);
    }

/**
 * Checks if html tags exists for the page
 *
 * @access public
 * @return boolean
 */

    public function hasHtmlTags()
    {
        return !empty($this->html_tags);
    }

/**
 * Adds a html tag to the page
 *
 * @access public
 * @param string $key The key of the html tag
 * @param string $value The value of the html tag
 */

    public function addHtmlTag($key, $value)
    {
        $this->html_tags[$key][] = $value;
    }

/**
 * Returns the html header tags of the page
 *
 * @access public
 * @return string
 */

    public function getHtmlElements(string $group = null): string
    {
        if (isset($group)) {
            return implode("\n", $this->html_elements[$group] ?? []);
        }

        $result = '';

        foreach ($this->html_elements as $g) {
            $result .= implode("\n", $g);
        }

        return $result;
    }

/**
 * Checks if html header tags exists for the page
 *
 * @access public
 * @return boolean
 */

    public function hasHtmlElements(string $group = null): bool
    {
        if (isset($group)) {
            return isset($this->html_elements[$group]) && !empty($this->html_elements[$group]);
        }

        return !empty($this->html_elements);
    }

/**
 * Adds a html header tag to the page
 *
 * @access public
 * @param string $tag The value of the header tag
 */

    public function addHtmlElement(string $group, string $element)
    {
        $this->html_elements[$group][] = $element;
    }
}
