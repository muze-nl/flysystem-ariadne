<?php declare(strict_types=1);

namespace MuzeNl\Flysystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\Config;

/**
 * Filesystem adapter to access files (pdir and pfile) in Ariadne
 */
class Ariadne implements FilesystemAdapter
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
     * @param Config $config
     */
    final public function copy(string $path, string $newpath, Config $config): void
    {
        $node = $this->getObject($path);
        if (!$node) {
            return; // FIXME: throw
        }
        $fullnewpath = $this->getFullPath($newpath);
        $node->call("system.copyto.phtml", array(
            "target" => $fullnewpath
        ));
    }

    /**
     * Create a directory. (recursively)
     *
     * @param string $dirname directory name
     * @param Config $config
     */
    final public function createDirectory(string $dirname, Config $config): void
    {
        $pathicles = explode("/", $dirname);
        $path = "/";
        $parent = $path;
        while (sizeof($pathicles)) {
            $parent = $path;
            $path .= array_shift($pathicles) . "/";
            if (!$this->has($path)) {
                $node = $this->getObject($parent);
                $defaultNls = $node->data->nls->default;
                $node->call("system.new.phtml", array(
                    "arNewType" => "pdir",
                    "arNewFilename" => basename($path),
                    $defaultNls => array(
                        "name" => basename($path)
                    )
                ));
            }
        }

        $node = $this->getObject($parent);
        $defaultNls = $node->data->nls->default;
        $node->call("system.new.phtml", array(
            "arNewType" => "pdir",
            "arNewFilename" => basename($path),
            $defaultNls => array(
                "name" => basename($path)
            )
        ));
    }

    /**
     * Delete a file.
     *
     * @param string $path
     */
    final public function delete(string $path): void
    {
        $node = $this->getObject($path);
        if (!$node) {
            return;
        }
        
        $node->call("system.delete.phtml");
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     */
    final public function deleteDirectory(string $dirname): void
    {
        $node = $this->getObject($path);
        if (!$node) {
            return; // FIXME: throw
        }
            
        if (!$this->isDirectory($node)) {
            return; // FIXME: throw
        }
        
        $node->call("system.delete.phtml"); // FIXME: Recurse?
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    final public function getAttributes(string $path): ?FileAttributes
    {
        $node = $this->getObject($path);
        if (!$node) {
            return null; // FIXME: throw;
        }
        $metaData = this->normalizeNodeInfo($node);

        return new FileAttributes(
            $path,
            $metaData['size'],
            $metaData['visibility'],
            $metaData['timestamp'],
            $metaData['mimetype']
        );
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    final public function mimeType(string $path): FileAttributes
    {
        return $this->getAttributes($path);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    final public function fileSize(string $path): FileAttributes
    {
        return $this->getAttributes($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    final public function lastModified(string $path): FileAttributes
    {
        return $this->getAttributes($path);
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    final public function visibility(string $path): FileAttributes
    {
        return $this->getAttributes($path);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    final public function fileExists(string $path): bool
    {
        $fullpath = $this->getFullPath($path);
        return $this->rootObject->exists($fullpath);
    }

    /**
     * Check whether a directory exists.
     *
     * @param string $path
     *
     * @return bool
     */
    final public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return iterable
     */
    final public function listContents(string $directory = '', bool $recursive = false): iterable
    {
        $result = [];
        $directory = $this->getObject($directory);

        if (!$directory) {
            return [];
        }
        $nodes = $directory->ls($directory->path, "system.get.phtml");
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
     * @return ?string
     */
    final public function read(string $path): ?string
    {
        $node = $this->getObject($path);
        if (!$node) {
            return null; // FIXME: throw
        }
        return $node->getFile();
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return resource
     */
    final public function readStream(string $path)
    {
        $node = $this->getObject($path);
        if (!$node) {
            return; // FIXME: throw
        }
        return $node->getFileStream();
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     * @param Config $config
     */
    final public function move(string $path, string $newpath, Config $config): void
    {
        $node = $this->getObject($path);
        if (!$node) {
            return; // FIXME: Throw
        }
        $fullnewpath = $this->getFullPath($newpath);
        $node->call("system.rename.phtml", array(
            "target" => $fullnewpath // CHECKME: args
        ));
    }

    /**
     * TODO Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     */
    final public function setVisibility(string $path, string $visibility): void
    {
        // FIXME: implement something here
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     */
    final public function write(string $path, string $contents, Config $config): void
    {
        try {
            if ($this->has($path)) {
                $node = $this->getObject($path);
                $node->SaveFile($contents);
            } else {
                $filename = basename($path);
                $dirname = dirname($path);
                if (!$this->has($dirname)) {
                    $this->createDir($dirname, $config);
                }
                $node = $this->getObject($dirname);
                $defaultNls = $node->data->nls->default;
                $node->call("system.new.phtml", array(
                    "arNewType" => "pfile",
                    "arNewFilename" => $filename,
                    $defaultNls => array(
                        "name" => $filename
                    )
                ));
                $file = $this->getObject($path);
                $file->SaveFile($contents);
            }
        } catch(\Exception $e) {
            return;
        }
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     */
    final public function writeStream($path, $resource, Config $config): void
    {
        $result = true;

        try {
            if ($this->has($path)) {
                $node = $this->getObject($path);
                $node->SaveFile($resource);
            } else {
                $filename = basename($path);
                $dirname = dirname($path);
                if (!$this->has($dirname)) {
                    $this->createDir($dirname, $config);
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
            return; // FIXME: throw
        }
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
        $dirPath = substr($node->path, strlen($this->rootPath));
        $filePath = substr($dirPath, 0, -1);
        
        switch ($node->type) {
            case "pdir":
                $defaultNls = $node->data->nls->default;
                return array_merge([
                    'mimetype' => "directory",
                    'path' => $dirPath,
                    'size' => $node->size,
                    'basename' => basename($node->path),
                    'timestamp' => $node->data->mtime,
                    'type' => "dir",
                    // @FIXME: check grants to set private or public
                    'visibility' => 'public',
                    /*/
                    'CreationTime' => $node->getCreationTime(),
                    'Etag' => $node->getEtag(),
                    'Owner' => $node->getOwner(),
                    /*/
                ], $metaData);
            break;
            case "pfile":
                $defaultNls = $node->data->nls->default;
                return array_merge([
                    'mimetype' => $node->data->$defaultNls->mimetype ?? "text/turtle",
                    'path' => $filePath,
                    'size' => $node->size,
                    'basename' => basename($node->path),
                    'timestamp' => $node->data->mtime,
                    'type' => "file",
                    // @FIXME: check grants to set private or public
                    'visibility' => 'public',
                    /*/
                    'CreationTime' => $node->getCreationTime(),
                    'Etag' => $node->getEtag(),
                    'Owner' => $node->getOwner(),
                    /*/
                ], $metaData);
            break;
            default:
                $defaultNls = $node->data->nls->default;
                return array_merge([
                    'mimetype' => $node->type,
                    'path' => $filePath,
                    'size' => $node->size,
                    'basename' => basename($node->path),
                    'timestamp' => $node->data->mtime,
                    'type' => "file",
                    // @FIXME: check grants to set private or public
                    'visibility' => 'public',
                    /*/
                    'CreationTime' => $node->getCreationTime(),
                    'Etag' => $node->getEtag(),
                    'Owner' => $node->getOwner(),
                    /*/
                ], $metaData);
            break;
        }
    }
}
