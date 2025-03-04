<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;

class ModalForm
{
    const QUERY_NAME = '_form_win_';

    /**
     * @var string
     */
    public static $contentView = 'admin::contents.modal-form';

    /**
     * @var array
     */
    protected $options = [
        'title'          => 'Form',
        'area'           => ['700px', '670px'],
        'defaultUrl'     => null,
        'buttonSelector' => null,
        'query'          => null,
        'lang'           => null,
        'forceRefresh'   => false,
        'disableReset'   => false,
    ];

    /**
     * @var array
     */
    protected $handlers = [
        'saved'   => null,
        'success' => null,
        'error'   => null,
    ];

    public function __construct(?string $title = null, $url = null)
    {
        $this->title($title);

        $this->url($url);
    }

    /**
     *
     * @param array $options
     * @return $this
     */
    public function options($options = [])
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * 设置弹窗标题
     *
     * @param string $title
     * @return $this
     */
    public function title(?string $title)
    {
        $this->options['title'] = $title;

        return $this;
    }

    /**
     * 绑定点击按钮
     *
     * @param string $buttonSelector
     * @return $this
     */
    public function click(string $buttonSelector)
    {
        $this->options['buttonSelector'] = $buttonSelector;

        return $this;
    }

    /**
     * 强制每次点击按钮都重新渲染表单弹窗
     *
     * @return $this
     */
    public function forceRefresh()
    {
        $this->options['forceRefresh'] = true;

        return $this;
    }

    /**
     * 禁用重置按钮
     *
     * @return $this
     */
    public function disableResetButton()
    {
        $this->options['disableReset'] = true;

        return $this;
    }

    /**
     * 保存后触发的js的代码（不论成功还是失败）
     *
     * @param string $script
     * @return $this
     */
    public function saved(string $script)
    {
        $this->handlers['saved'] = $script;

        return $this;
    }

    /**
     * 保存失败时触发的js代码
     *
     * @param string $script
     * @return $this
     */
    public function error(string $script)
    {
        $this->handlers['error'] = $script;

        return $this;
    }

    /**
     * 保存成功后触发的js代码
     *
     * @param string $script
     * @return $this
     */
    public function success(string $script)
    {
        $this->handlers['success'] = $script;

        return $this;
    }

    /**
     * 设置弹窗宽高
     * 支持百分比和"px"
     *
     * @param string $width
     * @param string $height
     * @return $this
     */
    public function dimensions(string $width, string $height)
    {
        $this->options['area'] = [$width, $height];

        return $this;
    }


    /**
     * 设置弹窗宽度
     * 支持百分比和"px"
     *
     * @param string|null $width
     * @return $this
     */
    public function width(?string $width)
    {
        $this->options['area'][0] = $width;

        return $this;
    }

    /**
     * 设置弹窗高度
     * 支持百分比和"px"
     *
     * @param string|null $height
     * @return $this
     */
    public function height(?string $height)
    {
        $this->options['area'][1] = $height;

        return $this;
    }

    /**
     * 设置默认的表单页面url
     *
     * @param null|string $url
     * @return $this
     */
    public function url(?string $url)
    {
        if ($url) {
            $this->options['defaultUrl'] = Helper::urlWithQuery(
                admin_url($url),
                [static::QUERY_NAME => 1]
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->setupOptions();

        $opts = json_encode($this->options);

        Admin::script(
            <<<JS
(function () {
    var opts = {$opts};
    
    opts.success = function (success, response) {
        {$this->handlers['success']}
    };
    opts.error = function (success, response) {
        {$this->handlers['error']}
    };
    opts.saved = function (success, response) {
        {$this->handlers['saved']}
    };
    
    LA.ModalForm(opts);
})();
JS
        );

    }

    protected function setupOptions()
    {
        $this->options['lang'] = [
            'submit'      => trans('admin.submit'),
            'reset'       => trans('admin.reset'),
            'save_failed' => trans('admin.save_failed'),
        ];
        $this->options['query'] = static::QUERY_NAME;
    }

    /**
     * 判断是否是获取弹窗表单内容的请求
     *
     * @return bool
     */
    public static function is()
    {
        return request(static::QUERY_NAME) ? true : false;
    }

    /**
     * @param Form $form
     */
    public static function prepare(Form $form)
    {
        if (!static::is()) {
            return;
        }

        Admin::$baseCss = [];
        Admin::$baseJs  = [];
        Admin::$fonts   = '';

        Admin::$disableSkinCss = true;

        $form->wrap(function ($v) {
            return $v;
        });

        $form->disableHeader();
        $form->disableFooter();

        $form->setWidth(9, 2);
        $form->hidden('_token')->value(csrf_token());

        Content::composing(function (Content $content) {
            $content->setView(static::$contentView);
        });
    }

}
