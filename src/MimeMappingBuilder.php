<?php

/**
 * Mimey - PHP package for converting file extensions to MIME types and vice versa.
 *
 * @author    Eric Sizemore <admin@secondversion.com>
 * @package   Mimey
 * @link      https://www.secondversion.com/
 * @version   1.0.0
 * @copyright (C) 2023 Eric Sizemore
 * @license   The MIT License (MIT)
 */
namespace Esi\Mimey;

use JetBrains\PhpStorm\Pure;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Mimey - PHP package for converting file extensions to MIME types and vice versa.
 *
 * @author    Eric Sizemore <admin@secondversion.com>
 * @package   Mimey
 * @link      https://www.secondversion.com/
 * @version   1.0.0
 * @copyright (C) 2023 Eric Sizemore
 * @license   The MIT License (MIT)
 *
 * Copyright (C) 2023 Eric Sizemore. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to 
 * deal in the Software without restriction, including without limitation the 
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or 
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Esi\Mimey is a fork of Elephox\Mimey (https://github.com/elephox-dev/mimey) which is:
 *     Copyright (c) 2022 Ricardo Boss
 * Elephox\Mimey is a fork of ralouphie/mimey (https://github.com/ralouphie/mimey) which is:
 *     Copyright (c) 2016 Ralph Khattar
 */

/**
 * Class for converting MIME types to file extensions and vice versa.
 */
class MimeMappingBuilder
{
    /**
     * @var array The mapping array.
     */
    protected array $mapping;

    /**
     * Create a new mapping builder.
     *
     * @param array $mapping An associative array containing two entries. See `MimeTypes` constructor for details.
     */
    private function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * Add a conversion.
     *
     * @param string $mime The MIME type.
     * @param string $extension The extension.
     * @param bool $prependExtension Whether this should be the preferred conversion for MIME type to extension.
     * @param bool $prependMime Whether this should be the preferred conversion for extension to MIME type.
     */
    public function add(string $mime, string $extension, bool $prependExtension = true, bool $prependMime = true): void
    {
        $existingExtensions = empty($this->mapping['extensions'][$mime]) ? [] : $this->mapping['extensions'][$mime];
        $existingMimes = empty($this->mapping['mimes'][$extension]) ? [] : $this->mapping['mimes'][$extension];

        if ($prependExtension) {
            array_unshift($existingExtensions, $extension);
        } else {
            $existingExtensions[] = $extension;
        }

        if ($prependMime) {
            array_unshift($existingMimes, $mime);
        } else {
            $existingMimes[] = $mime;
        }

        $this->mapping['extensions'][$mime] = array_unique($existingExtensions);
        $this->mapping['mimes'][$extension] = array_unique($existingMimes);
    }

    /**
     * @return array The mapping.
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * Compile the current mapping to PHP.
     *
     * @param bool $pretty Whether to pretty print the output.
     *
     * @return string The compiled PHP code to save to a file.
     * @throws JsonException
     */
    public function compile(bool $pretty = false): string
    {
        $mapping = $this->getMapping();

        return json_encode($mapping, flags: JSON_THROW_ON_ERROR | ($pretty ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * Save the current mapping to a file.
     *
     * @param string $file The file to save to.
     * @param int $flags Flags for `file_put_contents`.
     * @param resource $context Context for `file_put_contents`.
     *
     * @return false|int The number of bytes that were written to the file, or false on failure.
     * @throws JsonException
     */
    public function save(string $file, int $flags = 0, mixed $context = null): false|int
    {
        return file_put_contents($file, $this->compile(), $flags, $context);
    }

    /**
     * Create a new mapping builder based on the built-in types.
     *
     * @return MimeMappingBuilder A mapping builder with built-in types loaded.
     */
    public static function create(): MimeMappingBuilder
    {
        return self::load(dirname(__DIR__) . '/dist/mime.types.min.json');
    }

    /**
     * Create a new mapping builder based on types from a file.
     *
     * @param string $file The compiled PHP file to load.
     *
     * @return MimeMappingBuilder A mapping builder with types loaded from a file.
     */
    public static function load(string $file): MimeMappingBuilder
    {
        try {
            $json = file_get_contents($file);

            return new self(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to parse built-in types at ' . $file, 0, $e);
        }
    }

    /**
     * Create a new mapping builder that has no types defined.
     *
     * @return MimeMappingBuilder A mapping builder with no types defined.
     */
    #[Pure]
    public static function blank(): MimeMappingBuilder
    {
        return new self(['mimes' => [], 'extensions' => []]);
    }
}
