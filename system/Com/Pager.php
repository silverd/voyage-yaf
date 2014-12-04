<?php

/**
 * 分页条
 *
 * @author ColaPHP Framework
 * @modifier JianJian <silverd@sohu.com>
 * $Id: Pager.php 657 2012-12-06 06:07:46Z jiangjian $
 */

class Com_Pager
{
    protected $_config = array(
        'prevNums'       => 2,
        'nextNums'       => 7,
        'showSinglePage' => false,
        'prefix'         => '<style type="text/css">.pages{float:right;font-size:12px;} .pages *{border:1px solid #E6E7E1;height:24px;line-height:24px;padding:3px 6px;margin:1px;color:#0099CC;} .pages b{background-color:#0099CC;border-color:#0099CC;color:#FFFFFF;} .pages a {text-decoration:none;} .pages a:hover{border-color:#0099CC;} .pn{border-color:#0099CC;}</style><div class="pages">',
        'first'          => '<a href="%link%">%page%...</a>',
        'last'           => '<a href="%link%">...%page%</a>',
        'prev'           => '<a class="pn" href="%link%">&lt;&lt;</a>',
        'next'           => '<a class="pn" href="%link%">&gt;&gt;</a>',
        'current'        => '<b>%page%</b>',
        'page'           => '<a href="%link%">%page%</a>',
        'suffix'         => '</div>'
    );

    public $curPage = 1;
    public $pageSize = 20;
    public $totalItems;
    public $url;
    public $totalPages;
    public $startPage;
    public $endPage;

    /**
     * Constructor
     *
     * @param int $curPage
     * @param int $pageSize
     * @param int $totalItems
     * @param string $url
     * @param array $params 附件的分页传递参数
     */
    public function __construct($curPage, $pageSize, $totalItems, $url = '', $params = array())
    {
        $this->curPage    = intval($curPage);
        $this->pageSize   = intval($pageSize);
        $this->totalItems = intval($totalItems);
        $this->url        = self::_buildUrl($url, $params);

        $this->_init();
    }

    /**
     * Set or get config
     *
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    public function config($key = null, $value = null)
    {
        if (is_null($key))
            return $this->_config;

        if (is_array($key)) {
            $this->_config = $key + $this->_config;
            $this->_init();
            return $this;
        }

        if (is_null($value))
            return $this->_config[$key];

        $this->_config[$key] = $value;
        $this->_init();
        return $this;
    }

    /**
     * Prefix HTML
     *
     * @param string $format
     * @return string
     */
    public function prefix($format = null)
    {
        if (is_null($format))
            $format = $this->_config['prefix'];

        return $this->page(null, $format);
    }

    /**
     * Suffix HTML
     *
     * @param string $format
     * @return string
     */
    public function suffix($format = null)
    {
        if (is_null($format))
            $format = $this->_config['suffix'];

        return $this->page(null, $format);
    }

    /**
     * First page HTML
     *
     * @param string $format
     * @return string
     */
    public function first($format = null)
    {
        if (is_null($format))
            $format = $this->_config['first'];

        return $this->page(1, $format);
    }

    /**
     * Last page HTML
     *
     * @param string $format
     * @return string
     */
    public function last($format = null)
    {
        if (is_null($format))
            $format = $this->_config['last'];
        return $this->page($this->totalPages, $format);
    }

    /**
     * Prev page HTML
     *
     * @param string $format
     * @return string
     */
    public function prev($format = null)
    {
        if (1 == $this->curPage)
            return '';

        if (is_null($format))
            $format = $this->_config['prev'];

        $page = $this->curPage - 1;

        return $this->page($page, $format);
    }

    /**
     * Next page HTML
     *
     * @param string $format
     * @return string
     */
    public function next($format = null)
    {
        if ($this->curPage == $this->totalPages)
            return '';

        if (is_null($format))
            $format = $this->_config['next'];

        $page = $this->curPage + 1;

        return $this->page($page, $format);
    }

    /**
     * Current page HTML
     *
     * @param string $format
     * @return string
     */
    public function current($format = null)
    {
        if (is_null($format)) {
            $format = $this->_config['current'];
        }

        return $this->page($this->curPage, $format);
    }

    /**
     * Page HTML
     *
     * @param int $page
     * @param string $format
     * @return string
     */
    public function page($page, $format = null)
    {
        if (is_null($format)) {
            $format = $this->_config['page'];
        }

        $p = array(
            '%link%'       => $this->url,
            '%curPage%'    => $this->curPage,
            '%pageSize%'   => $this->pageSize,
            '%totalItems%' => $this->totalItems,
            '%totalPages%' => $this->totalPages,
            '%startPage%'  => $this->startPage,
            '%endPage%'    => $this->endPage,
            '%page%'       => $page
        );

        return str_replace(array_keys($p), $p, $format);
    }

    /**
     * Pages HTML
     *
     * @return string
     */
    public function html()
    {
        if (1 >= $this->totalPages && ! $this->_config['showSinglePage']) {
            return '';
        }

        $html = $this->prefix() . $this->prev();

        if (1 == $this->startPage - 1) {
            $html .= $this->page(1);
        } elseif (1 < $this->startPage - 1) {
            $html .= $this->first();
        }

        for ($i = $this->startPage; $i <= $this->endPage; $i++) {
            $html .= ($i == $this->curPage ? $this->current() : $this->page($i));
        }

        if (1 == $this->totalPages - $this->endPage) {
            $html .= $this->page($this->totalPages);
        } elseif (1 < $this->totalPages - $this->endPage) {
            $html .= $this->last();
        }

        $html .= $this->next() . $this->suffix();

        return $html;
    }

    /**
     * Display pages
     *
     */
    public function display()
    {
        echo $this->html();
    }

    /**
     * Init Com_Pager
     *
     */
    protected function _init()
    {
        if (1 > $this->pageSize) {
            $this->pageSize = 20;
        }

        $this->totalPages = ceil($this->totalItems / $this->pageSize);
        if (1 > $this->curPage || $this->curPage > $this->totalPages) {
            $this->curPage = 1;
        }

        $this->startPage = $this->curPage - $this->_config['prevNums'];
        if (1 > $this->startPage) {
            $this->startPage = 1;
        }

        $this->endPage = $this->curPage + $this->_config['nextNums'];

        $less = ($this->_config['prevNums'] + $this->_config['nextNums']) - ($this->endPage - $this->startPage);
        if (0 < $less) {
            $this->endPage += $less;
        }

        if ($this->endPage > $this->totalPages) {
            $this->endPage = $this->totalPages;
        }

        $less = ($this->_config['prevNums'] + $this->_config['nextNums']) - ($this->endPage - $this->startPage);
        if (0 < $less) {
            $this->startPage -= $less;
        }

        if (1 > $this->startPage) {
            $this->startPage = 1;
        }
    }

    /**
     * 根据条件组合生成 URL
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    protected static function _buildUrl($url, $params = array())
    {
        if (! $params) {
            return $url;
        }

        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);

        return $url;
    }
}

/*
    Example:
    $params = array(
        'keyword' => 'test',
        'category' => 2,
    );
    $pager = new Com_Pager($curPage, $pageSize, $total, '/_index/page/list/?page=%page%', $params);
    $multipage = $pager->html();
*/