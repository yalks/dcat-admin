<?php

namespace Dcat\Admin\Grid\Displayers;

use Dcat\Admin\Admin;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Checkbox extends AbstractDisplayer
{
    public function display($options = [])
    {
        if ($options instanceof \Closure) {
            $options = $options->call($this, $this->row);
        }

        $radios = '';
        $name = $this->column->getName();

        if (is_string($this->value)) {
            $this->value = explode(',', $this->value);
        }

        if ($this->value instanceof Arrayable) {
            $this->value = $this->value->toArray();
        }

        foreach ($options as $value => $label) {
            $id = 'ckb'.\Illuminate\Support\Str::random(8);

            $checked = in_array($value, $this->value) ? 'checked' : '';
            $radios .= <<<EOT
<div class="checkbox checkbox-primary ">
    <input id="$id" type="checkbox" name="grid-checkbox-{$name}[]" value="{$value}" $checked />
    <label for="$id">{$label}</label>
</div>
EOT;
        }

        Admin::script($this->script());

        return <<<EOT
<form class="form-group grid-checkbox-$name" style="text-align:left;" data-key="{$this->getKey()}">
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
$('form.grid-checkbox-$name').on('submit', function () {
    var values = $(this).find('input:checkbox:checked').map(function (_, el) {
        return $(el).val();
    }).get(), btn = $(this).find('[type="submit"]');
    
    if (_ckreq) return;
    _ckreq = 1;
    
    btn.button('loading');

    var data = {
        $name: values,
        _token: LA.token,
        _method: 'PUT'
    };
    
    $.ajax({
        url: "{$this->getResource()}/" + $(this).data('key'),
        type: "POST",
        contentType: 'application/json;charset=utf-8',
        data: JSON.stringify(data),
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
