# Flysystem Ariadne

This project provides a flysystem adapter for Ariadne. It is used to access
files and directories in an Ariadne structure, which assumes things
translate to directories and files. (pdir and pfile).

## Background

This project is related to the PHP stack of projects by PDS Interop. It is used
for the Solid-Ariadne implementation.

As the functionality seemed useful for other projects, it was implemented as a
separate package.

## Installation

The advised install method is through composer:

```
composer require muze-nl/flysystem-ariadne
```

## Usage

This package offers features to interact with the Filesystem provided by
Ariadne through the Flysystem API.

To use the adapter, instantiate it and add it to a Flysystem filesystem:

```pinp
<pinp>
$folder = $this;

// Create the Nextcloud Adapter
$adapter = ar::construct('\Pdsinterop\Flysystem\Adapter\Ariadne', array($this));

// Create Flysystem as usual, adding the Adapter
$filesystem = ar::construct('\League\Flysystem\Filesystem, array($adapter));

// Read the contents of a file
$content = $filesystem->read('/some.file');
</pinp>
```

## License

This code is licensed under the [MIT License][license-link].

[license-link]: ./LICENSE
