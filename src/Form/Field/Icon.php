<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Admin;

class Icon extends Text
{
    public function render()
    {
        $this->script = <<<JS
setTimeout(function () {
    $('{$this->getElementClassSelector()}').iconpicker({placement:'bottomLeft'});
}, 10);
JS;

        $this->defaultAttribute('style', 'width: 200px');

        return parent::render();
    }

    public static function collectAssets()
    {
        Admin::collectComponentAssets('fontawesome-iconpicker');
    }
}
