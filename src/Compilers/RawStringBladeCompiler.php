<?php

namespace Zofe\Rapyd\Compilers;

use Illuminate\Support\Facades\Blade;

class RawStringBladeCompiler
{
    public static function render($string, $data = [])
    {
        /* Create a file with name as hash of the passed string */
        $filename = hash('sha1', $string);

        /* Putting it in storage/framework/views so that these files get cleared on `php artisan view:clear*/
        $file_location = storage_path('framework/views/');
        $filepath = storage_path('framework/views/'.$filename.'.blade.php');

        /* Create file only if it doesn't exist */
        if (! file_exists($filepath)) {
            file_put_contents($filepath, $string);
        }

        /* Add storage/framework/views as a location from where view files can be picked, used in make function below */
        view()->addLocation($file_location);

        /* call the usual view helper to render the blade file created above */
        return view($filename, $data);
    }
}
