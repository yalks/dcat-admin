<?php

namespace Dcat\Admin\Grid\Displayers;

use Dcat\Admin\Admin;

class Radio extends AbstractDisplayer
{
    public function display($options = [])
    {
        if ($options instanceof \Closure) {
            $options = $options->call($this, $this->row);
        }

        $radios = '';
        $name = $this->column->getName();

        foreach ($options as $value => $label) {
            $id = 'rdo'.\Illuminate\Support\Str::random(8);

            $checked = ($value == $this->value) ? 'checked' : '';
            $radios .= <<<EOT
<div class="radio radio-primary">
    <input id="$id" type="radio" name="grid-radio-$name" value="{$value}" $checked />
    <label for="$id">{$label}</label>
</div>
EOT;
        }

        Admin::script($this->script());

        return <<<EOT
<form class="form-group grid-radio-$name" style="text-align: left" data-key="{$this->getKey()}">
    $radios
    <button type="submit" class="btn btn-primary btn-xs pull-left">
        <i class="fa fa-save"></i>&nbsp;{$this->trans('save')}
    </button>
    <button type="reset" class="btn btn-warning btn-xs pull-left" style="margin-left:10px;">
        <i class="ti-trash"></i>&nbsp;{$this->trans('reset')}
    </button>
</form>
EOT;
    }

    protected function script()
    {
        $name = $this->column->getName();

        return <<<JS
var _ckreq;
$('form.grid-radio-$name').on('submit', function () {
    var value = $(this).find('input:radio:checked').val(), btn = $(this).find('[type="submit"]');
    
    if (_ckreq) return;
    _ckreq = 1;
    
    btn.button('loading');

    $.ajax({
        url: "{$this->getResource()}/" + $(this).data('key'),
        type: "POST",
        data: {
            $name: value,
            _token: LA.token,
            _method: 'PUT'
        },
        success: function (data) {
            btn.button('reset');
            _ckreq = 0;
            LA.success(data.message);
        },
        error: function (a, b, c) {
            btn.button('reset');
            _ckreq = 0;
            LA.ajaxError(a, b, c);
        },
    });

    return false;
});

JS;
    }
}
