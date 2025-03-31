<?php declare(strict_types=1);

namespace MuzeNl\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * Filesystem adapter to access files (pdir and pfile) in Ariadne
 */
class Ariadne implements AdapterInterface
{
    private $rootObject;
    private $rootPath;

    final public function __construct($rootObject)
    {
        $this->rootObject = $rootObject;
        $this->rootPath = $rootObject->path;
    }

    private function getFullPath($path)
    {
        return $this->rootObject->make_path($this->rootPath . $path);
    }
    
    private function getObject($path)
    {
        $fullpath = $this->getFullPath($path);
        return current($this->rootObject->get($fullpath, "system.get.phtml"));
    }
    
    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    final public function copy($path, $newpath)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
        $fullnewpath = $this->getFullPath($newpath);
        $node->call("system.copyto.phtml", array(
            "target" => $fullnewpath
        ));
        return true;
    }

    /**
     * Create a directory. (recursively)
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    final public function createDir($dirname, Config $config)
    {
        $pathicles = explode("/", $dirname);
        $path = $this->rootPath;
        $parent = $path;
        while (sizeof($pathicles)) {
            $parent = $path;
            $path .= shift($pathicles) . "/";
            if (!$this->has($path)) {
                $node = $this->getObject($parent);
                $defaultNls = $node->data->nls->default;
                $node->call("system.new.phtml", array(
                    "arNewType" => "pdir",
                    "arNewFilename" => basename($path),
                    "data" => array(
                        $defaultNls => array(
                            "name" => basename($path)
                        )
                    )
                ));
            }
        }
        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     *
     */
    final public function delete($path)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
        
        $node->call("system.delete.phtml");
        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    final public function deleteDir($dirname)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
            
        if (!$this->isDirectory($node)) {
            return false;
        }
        
        $node->call("system.delete.phtml"); // FIXME: Recurse?
        return true;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    final public function getMetadata($path)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
        return $this->normalizeNodeInfo($node);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    final public function getMimeType($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    final public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    final public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    final public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    final public function has($path)
    {
        $fullpath = $this->getFullPath($path);
        return $rootOb->exists($fullpath);
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    final public function listContents($directory = '', $recursive = false)
    {
        $result = [];
        $directory = $this->getObject($directory);
        if (!$directory) {
            return [];
        }
        
        $nodes = $directory->ls("system.get.phtml");
        $result = array_map(function($node) {
            return $this->normalizeNodeInfo($node);
        }, $nodes);

        return $result;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     *
     * @throws \OCP\Files\InvalidPathException
     */
    final public function read($path)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
        return $this->normlalizeNodeInfo($node, [
            'contents' => $node->getFile()
        ]);
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    final public function readStream($path)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
        return $this->normlalizeNodeInfo($node, [
            'contents' => $node->getFileStream()
        ]);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    final public function rename($path, $newpath)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return false;
        }
        $fullnewpath = $this->getFullPath($newpath);
        $node->call("system.rename.phtml", array(
            "target" => $fullnewpath // CHECKME: args
        ));
        return true;
    }

    /**
     * TODO Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    final public function setVisibility($path, $visibility)
    {
        return false;
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    final public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    final public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    final public function write($path, $contents, Config $config)
    {
        $result = true;

        try {
            if ($this->has($path)) {
                $node = $this->getObject($path);
                $node->SaveFile($contents);
                $result = $this->normalizeNodeInfo($node, [
                    'contents' => $node->GetFile()
                ]);
            } else {
                $filename = basename($path);
                $dirname = dirname($path);
                if (!$this->has($dirname)) {
                    $this->createFolder($dirname, $config);
                }

                $node = $this->getObject($dirname);
                $node->call("system.new.phtml", array(
                    "arNewType" => "pfile",
                    "arNewFilename" => $filename,
                    "data" => array(
                        $defaultNls => array(
                            "name" => $filename
                        )
                    )
                ));
                $file = $this->getObject($path);
                $file->SaveFile($contents);
            }
        } catch(\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    final public function writeStream($path, $resource, Config $config)
    {
        $result = true;

        try {
            if ($this->has($path)) {
                $node = $this->getObject($path);
                $node->SaveFile($resource);
                $result = $this->normalizeNodeInfo($node, [
                    'contents' => $node->GetFile()
                ]);
            } else {
                $filename = basename($path);
                $dirname = dirname($path);
                if (!$this->has($dirname)) {
                    $this->createFolder($dirname, $config);
                }

                $node = $this->getObject($dirname);
                $node->call("system.new.phtml", array(
                    "arNewType" => "pfile",
                    "arNewFilename" => $filename,
                    "data" => array(
                        $defaultNls => array(
                            "name" => $filename
                        )
                    )
                ));
                $file = $this->getObject($path);
                $file->SaveFile($resource);
            }
        } catch(\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * @param $node
     *
     * @return bool
     */
    private function isDirectory($node)
    {
        return $node->implements("pdir");
    }

    /**
     * @param $node
     * @param array $metaData
     *
     * @return array
     */
    private function normalizeNodeInfo($node, array $metaData = []) : array
    {
        $defaultNls = $node->data->nls->default;
        return array_merge([
            'mimetype' => $this->isDirectory($node) ? "directory" : $node->data->mimetype,
            'path' => substr($node->path, strlen($this->rootPath)+1),
            'size' => $node->data->$defaultNls->filesize,
            'basename' => basename($node->path),
            'timestamp' => $node->data->mtime,
            'type' => $node->type,
            // @FIXME: Use $node->getPermissions() to set private or public
            //         as soon as we figure out what Nextcloud permissions mean in this context
            'visibility' => 'public',
            /*/
            'CreationTime' => $node->getCreationTime(),
            'Etag' => $node->getEtag(),
            'Owner' => $node->getOwner(),
            /*/
        ], $metaData);
    }
}
