<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BootstrapMultipleFile extends Field
{
    use BootstrapUploadField;

    /**
     * Css.
     *
     * @var array
     */
    protected static $css = [
        '/vendor/dcat-admin/bootstrap-fileinput/css/fileinput.min.css?v=4.5.2',
    ];

    /**
     * Js.
     *
     * @var array
     */
    protected static $js = [
        '/vendor/dcat-admin/bootstrap-fileinput/js/plugins/canvas-to-blob.min.js',
        '/vendor/dcat-admin/bootstrap-fileinput/js/fileinput.min.js?v=4.5.2',
    ];

    /**
     * Create a new File instance.
     *
     * @param string $column
     * @param array  $arguments
     */
    public function __construct($column, $arguments = [])
    {
        $this->initStorage();

        parent::__construct($column, $arguments);
    }

    /**
     * Default directory for file to upload.
     *
     * @return mixed
     */
    public function defaultDirectory()
    {
        return config('admin.upload.directory.file');
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(array $input)
    {
        if (request()->has(static::FILE_DELETE_FLAG)) {
            return false;
        }

        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        $attributes = [];

        if (!$fieldRules = $this->getRules()) {
            return false;
        }

        $attributes[$this->column] = $this->label;

        list($rules, $input) = $this->hydrateFiles(Arr::get($input, $this->column, []));

        return Validator::make($input, $rules, $this->getValidationMessages(), $attributes);
    }

    /**
     * Hydrate the files array.
     *
     * @param array $value
     *
     * @return array
     */
    protected function hydrateFiles(array $value)
    {
        if (empty($value)) {
            return [[$this->column => $this->getRules()], []];
        }

        $rules = $input = [];

        foreach ($value as $key => $file) {
            $rules[$this->column.$key] = $this->getRules();
            $input[$this->column.$key] = $file;
        }

        return [$rules, $input];
    }

    /**
     * Prepare for saving.
     *
     * @param UploadedFile|array $files
     *
     * @return mixed|string
     */
    public function prepare($files)
    {
        if (request()->has(static::FILE_DELETE_FLAG)) {
            return $this->destroy(request(static::FILE_DELETE_FLAG));
        }

        $targets = array_map([$this, 'prepareForeach'], $files);

        return array_merge($this->original(), $targets);
    }

    /**
     * @return array|mixed
     */
    public function original()
    {
        if (empty($this->original)) {
            return [];
        }

        return $this->original;
    }

    protected function formatFieldData($data)
    {
        $value = Arr::get($data, $this->column);

        if ($value && is_string($value)) {
            return explode(',', $value);
        }

        return $value ? (array)$value : [];
    }

    /**
     * Prepare for each file.
     *
     * @param UploadedFile $file
     *
     * @return mixed|string
     */
    protected function prepareForeach(UploadedFile $file = null)
    {
        $this->name = $this->getStoreName($file);

        return tap($this->upload($file), function () {
            $this->name = null;
        });
    }

    /**
     * Preview html for file-upload plugin.
     *
     * @return array
     */
    protected function preview()
    {
        $files = $this->value ?: [];

        return array_map([$this, 'objectUrl'], $files);
    }

    /**
     * Initialize the caption.
     *
     * @param array $caption
     *
     * @return string
     */
    protected function initialCaption($caption)
    {
        if (empty($caption)) {
            return '';
        }

        $caption = array_map('basename', $caption);

        return implode(',', $caption);
    }

    /**
     * @return array
     */
    protected function initialPreviewConfig()
    {
        $files = $this->value ?: [];

        $config = [];

        foreach ($files as $index => $file) {
            $config[] = [
                'caption' => basename($file),
                'key'     => $index,
            ];
        }

        return $config;
    }

    /**
     * Render file upload field.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function render()
    {
        $this->attribute('multiple', true);

        $this->setupDefaultOptions();

        if (!empty($this->value())) {
            $this->options(['initialPreview' => $this->preview()]);
            $this->setupPreviewOptions();
        }

        $options = json_encode($this->options);

        $this->script = <<<JS
$("{$this->getElementClassSelector()}").fileinput({$options});
JS;

        return parent::render();
    }

    /**
     * Destroy original files.
     *
     * @return string.
     */
    public function destroy($key)
    {
        $files = $this->original ?: [];

        $file = Arr::get($files, $key);

        $this->deleteFile($file);

        unset($files[$key]);

        return array_values($files);
    }
}
