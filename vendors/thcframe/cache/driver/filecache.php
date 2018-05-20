<?php

namespace THCFrame\Cache\Driver;

use THCFrame\Cache as Cache;
use THCFrame\Cache\Exception;
use THCFrame\Filesystem\FileManager;

/**
 * Class handles operations with file cache
 */
class Filecache extends Cache\Driver
{

    /**
     * @readwrite
     */
    protected $_duration;

    /**
     * @readwrite
     */
    protected $_path;

    /**
     * @readwrite
     */
    protected $_suffix;

    /**
     * @readwrite
     */
    protected $_mode;

    /**
     * @var FileManager
     */
    private $_fileManager;

    /**
     * Filecache constructor.
     * @param array $options
     * @throws \Exception
     * @throws \THCFrame\Filesystem\Exception\IO
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_fileManager = new FileManager();
        $this->_path = APP_PATH.DIRECTORY_SEPARATOR.$this->_path.DIRECTORY_SEPARATOR;
        $this->_suffix = '.'.trim($this->_suffix, '.');

        if (!is_dir($this->_path)) {
            $this->_fileManager->mkdir($this->_path, 0755);
        }
    }

    /**
     * Method checks if cache file is not expired
     *
     * @param string $key
     * @return boolean
     */
    public function isFresh($key)
    {
        if ($this->_mode == 'active' && ENV == \THCFrame\Core\Core::ENV_DEV) {
            return false;
        }

        if (file_exists($this->_path . $key . $this->_suffix)) {
            if (time() - filemtime($this->_path . $key . $this->_suffix) <= $this->duration) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Loads cache file content
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get($key, $default = null)
    {
        if ($this->isFresh($key)) {
            $data = unserialize(file_get_contents($this->_path . $key . $this->_suffix));
            return $data;
        } else {
            return $default;
        }
    }

    /**
     * Save data into file named by key
     *
     * @param string $key
     * @param mixed $value
     * @throws Exception\Service
     * @throws \THCFrame\Filesystem\Exception\IO
     */
    public function set($key, $value)
    {
        $file = $this->_path . $key . $this->_suffix;

        if (false !== @file_put_contents($file, serialize($value))) {
            $this->_fileManager->chmod($file, 0755, umask());

            return;
        }

        throw new Exception\Service(sprintf('Failed to write cache file %s', $file));
    }

    /**
     * Removes file with specific name
     *
     * @param string $key
     * @throws \THCFrame\Filesystem\Exception\IO
     */
    public function erase($key)
    {
        $matches = glob($this->_path.'*'.$key.'*');
        $this->_fileManager->remove($matches);
    }

    /**
     * Removes all files and folders from cache folder
     *
     * @throws \THCFrame\Filesystem\Exception\IO
     */
    public function clearCache()
    {
        $this->_fileManager->remove($this->_path);
        return;
    }

}
