<?php

namespace Dcat\Admin\Grid\Displayers;

use Dcat\Admin\Admin;

class SwitchDisplay extends AbstractDisplayer
{
    /**
     * @var string
     */
    protected $color = 'var(--primary)';

    public function green()
    {
        $this->color = 'var(--success)';
    }

    public function custom()
    {
        $this->color = 'var(--custom)';
    }

    public function yellow()
    {
        $this->color = 'var(--warning)';
    }

    public function red()
    {
        $this->color = 'var(--danger)';
    }

    public function purple()
    {
        $this->color = 'var(--purple)';
    }

    public function blue()
    {
        $this->color = 'var(--blue)';
    }

    /**
     * Set color of the switcher.
     *
     * @param $color
     * @return $this
     */
    public function color($color)
    {
        $this->color = $color;
    }

    public function display(string $color = '')
    {
        if ($color instanceof \Closure) {
            $color->call($this->row, $this);
        } else {
            if ($color) {
                if (method_exists($this, $color)) {
                    $this->$color();
                } else {
                    $this->color($color);
                }
            }
        }

        $this->setupScript();

        $name    = $this->getElementName();
        $key     = $this->row->{$this->grid->getKeyName()};
        $checked = $this->value ? 'checked' : '';

        return <<<EOF
<input class="grid-switch-{$this->grid->getName()}" data-size="small" name="{$name}" data-key="$key" {$checked} type="checkbox" data-color="{$this->color}"/>
EOF;
    }

    protected function setupScript()
    {
        Admin::script(<<<JS
(function(){
    var swt = $('.grid-switch-{$this->grid->getName()}'), t;
    function init(){
        swt.each(function(k){
            t = $(this);
            new Switchery(t[0], t.data())
        })
    } 
    init();
    swt.change(function(e) {
        var t = $(this), id = t.data('key'), checked = t.is(':checked'), name = t.attr('name'), data = {
            _token: LA.token,
            _method: 'PUT'
        };
        data[name] = checked ? 1 : 0;
        LA.NP.start();
    
        $.ajax({
            url: "{$this->getResource()}/" + id,
            type: "POST",
            data: data,
            success: function (d) {
                LA.NP.done();
                if (d.status) {
                    LA.success(d.message);
                } else {
                    LA.error(d.message);
                }
            }
        });
    });
})();
JS
        );
    }

    protected function collectAssets()
    {
        Admin::collectComponentAssets('switchery');
    }


}
