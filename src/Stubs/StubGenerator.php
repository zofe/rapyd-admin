<?php

namespace Zofe\Rapyd\Stubs;

use Illuminate\Support\Facades\Response;
use Zofe\Rapyd\Stubs\StubFactory;
use Zofe\Rapyd\Stubs\Exceptions\StubGenerationException;
use Zofe\Rapyd\Stubs\Concerns\FileHelpers as StubFileHelpers;

class StubGenerator {

    use StubFileHelpers;

    /**
     * The instance of StubFactory
     *
     * @var object<Zofe\Rapyd\Stubs\StubFactory>
     */
    protected $stubFactory;


    /**
     * The stub file itself
     *
     * @var string
     */
    protected $stub;


    /**
     * The generated file store location path
     *
     * @var string
     */
    protected $storePath;


    /**
     * The generateable file name
     *
     * @var string
     */
    protected $generatingFileName;


    /**
     * The generateable file extension
     *
     * @var string
     */
    protected $generatingFileExtension = 'php';


    /**
     * The replaceable data list of the stub file
     *
     * @var array
     */
    protected $stubReplacers = [];


    /**
     * Should replace an existing generating file
     *
     * @var bool
     */
    protected $replace = false;


    /**
     * Create a new instance
     *
     * @return void
     */
    public function __construct() {

        $this->stubFactory = new StubFactory;
    }


    /**
     * The stub file from which to generate file
     *
     * @param  string   $stubPath
     * @param  bool     $asFullPath
     *
     * @return self
     */
    public function from(string $stubPath, bool $asFullPath = false) {

        $this->stub = $asFullPath ? $stubPath : $this->getFileFullPath($stubPath);

        if ( ! $this->fileExists($this->stub) ) {

            throw StubGenerationException::stubFileNotFound($this->stub);
        }

        return $this;
    }


    /**
     * The path location to store generated file
     *
     * @param  string   $storePath
     * @param  bool     $createIfNotExist
     * @param  bool     $asFullPath
     *
     * @return self
     */
    public function to(string $storePath, bool $createIfNotExist = false, bool $asFullPath = false) {

        $fullStorePath = $asFullPath ? $storePath : $this->getStoreDirectoryPath($storePath);

        if ( $fullStorePath && $this->isDirectory($fullStorePath) ) {

            $this->storePath = $fullStorePath;

            return $this;
        }

        if ( !$createIfNotExist ) {

            throw StubGenerationException::generatingFileStoreDirectoryNotFound($fullStorePath);
        }

        $this->storePath = $this->generateFilePathDirectory($storePath, $asFullPath);

        return $this;
    }


    /**
     * The name of the generating file
     *
     * @param  string   $generatingFileName
     * @return self
     */
    public function as(string $generatingFileName) {

        $this->generatingFileName = $generatingFileName;

        return $this;
    }

    /**
     * The name of the generating file
     *
     * @param  string   $generatingFileExtension
     * @return self
     */
    public function ext(string $generatingFileExtension) {

        $this->generatingFileExtension = $generatingFileExtension;

        return $this;
    }

    /**
     * Determine if the generated file has no extension
     *
     * @return self
     */
    public function noExt() {

        $this->generatingFileExtension = null;

        return $this;
    }


    /**
     * The replaceable key list in the stub file for generating file
     *
     * @param  array stubReplacers
     * @return self
     */
    public function withReplacers(array $stubReplacers = []) {

        $this->stubReplacers = $stubReplacers;

        return $this;
    }


    /**
     * Should replace an existing generating file on new file generation
     *
     * @param  bool replace
     * @return self
     */
    public function replace(bool $replace) {

        $this->replace = $replace;

        return $this;
    }


    /**
     * Save the the generated file
     *
     * @return bool
     */
    public function save() {

        if ( ! $this->storePath ) {

            throw StubGenerationException::generatingFileStoreDirectoryNotProvided();
        }

        if ( ! $this->generatingFileName ) {

            throw StubGenerationException::generatingFileNameNotProvided();
        }

        $fileFullPath = $this->getPath($this->storePath, $this->generatingFileName, $this->generatingFileExtension);

        if ( $this->fileExists($fileFullPath) ) {

            if ( ! $this->replace ) {

                throw StubGenerationException::generatingFileAlreadyExists($fileFullPath);
            }

            if ( ! $this->removeFile($fileFullPath) ) {

                throw StubGenerationException::unableToRemoveExistingFile($fileFullPath);
            }
        }

        $this->newFileWithContent($fileFullPath, $this->toString());

        return true;
    }


    /**
     * Download the file
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function download() {

        if ( ! $this->generatingFileName ) {

            throw StubGenerationException::generatingFileNameNotProvided();
        }

        $headers = [
            'Content-Disposition' => "attachment; filename={$this->generatingFileName}.{$this->generatingFileExtension}",
        ];

        return Response::make($this->toString(), 200, $headers);
    }


    /**
     * Get the generated file content
     *
     * @return string
     */
    public function toString() {

        if ( ! $this->stub ) {

            throw StubGenerationException::stubFileNotProvided();
        }

        return $this->stubFactory->make($this->stub, $this->stubReplacers);
    }

}
