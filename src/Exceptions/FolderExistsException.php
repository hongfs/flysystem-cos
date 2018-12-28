<?php

namespace Hongfs\Cos\Exceptions;

use Exception as BaseException;

class FolderExistsException extends Exception
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Constructor.
     *
     * @param string        $path
     * @param int           $code
     * @param BaseException $previous
     */
    public function __construct($path, $code = 0, BaseException $previous = null)
    {
        $this->path = $path;

        parent::__construct('Folder already exists: '.$this->getPath(), $code, $previous);
    }

    /**
     * Get the path which was found.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
