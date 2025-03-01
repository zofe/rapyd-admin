<?php

namespace Zofe\Rapyd\Stubs\Exceptions;

use Exception;

class StubGenerationException extends Exception {

    /**
     * The stub file not provided exception
     *
     * @return object<\Exception>
     */
    public static function stubFileNotProvided() {

        return new static("Stub file not provided");
    }


    /**
     * The stub file not found exception
     *
     * @param  string  $stubFilePath
     * @return object<\Exception>
     */
    public static function stubFileNotFound(string $stubFilePath) {

        return new static("Stub file not found at given path `{$stubFilePath}`");
    }


    /**
     * The store directory location for generating file not provided exception
     *
     * @return object<\Exception>
     */
    public static function generatingFileStoreDirectoryNotProvided() {

        return new static("The generating file store location or directory not provided");
    }


    /**
     * The generating file name not provided exception
     *
     * @return object<\Exception>
     */
    public static function generatingFileNameNotProvided() {

        return new static("The generating file name not provided");
    }


    /**
     * The store directory path of the generating file not found exception
     *
     * @param  string  $stubFilePath
     * @return object<\Exception>
     */
    public static function generatingFileStoreDirectoryNotFound(string $directoryPath) {

        return new static("Invalid location `{$directoryPath}` to store generated file from stub");
    }


    /**
     * The same name of generating file name already exist at given location exception
     *
     * @param  string  $stubFilePath
     * @return object<\Exception>
     */
    public static function generatingFileAlreadyExists(string $filePath) {

        return new static("The file already exists at `{$filePath}`");
    }


    /**
     * Can nt delete the existing file of same name as the generating file name exception
     *
     * @param  string  $stubFilePath
     * @return object<\Exception>
     */
    public static function unableToRemoveExistingFile(string $filePath) {

        return new static("Can not remove already existing file at `{$filePath}`");
    }

}
