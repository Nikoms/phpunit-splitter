<?php

namespace Nikoms\PhpUnitSplitter\Storage;

/**
 * Class FileStorage
 */
class FileStorage
{
    /**
     * @var string
     */
    private $pathName;

    /**
     * FileStorage constructor.
     *
     * @param string $pathName
     */
    public function __construct($pathName)
    {
        $this->pathName = $pathName;
    }

    /**
     * @return array
     */
    public function get()
    {
        return file_exists($this->pathName)
            ? include($this->pathName)
            : [];
    }

    /**
     *
     */
    public function delete()
    {
        if (file_exists($this->pathName)) {
            unlink($this->pathName);
        }
    }
    /**
     * @param array $model
     */
    public function save(array $model)
    {
        file_put_contents($this->pathName, '<?php return '.var_export($model, true).';');
        //When docker run the command
        @chmod($this->pathName, 0777);
    }
}