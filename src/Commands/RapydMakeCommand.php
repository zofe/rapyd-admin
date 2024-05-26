<?php

namespace Zofe\Rapyd\Commands;



class RapydMakeCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make {component : all|datatable|dataview|dataedit} {model} {--module=}';

    public $description = 'rapyd command to generate components';


    public function handle()
    {
        $component = $this->getComponentName();
        $this->module = $this->option('module');

        //run correct generator
        if (str_ends_with($component, 'Table')) {

            $this->call('rpd:make:table', [
                'component' => $this->argument('component'),
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
            ]);


        } elseif (str_ends_with($component, 'View')) {

            $this->call('rpd:make:view', [
                'component' => $this->argument('component'),
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
            ]);

        } elseif (str_ends_with($component, 'Edit')) {

            $this->call('rpd:make:edit', [
                'component' => $this->argument('component'),
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
            ]);

        } else {

            foreach (['Table', 'View', 'Edit'] as $suffix) {

                if($this->option('module')) {
                    $this->call('rpd:make:'.strtolower($suffix), [
                        'component' => $component.$suffix,
                        'model' => $this->argument('model'),
                        '--module' => $this->option('module'),
                    ]);
                } else {
                    $this->call('rpd:make:'.strtolower($suffix), [
                        'component' => $component.$suffix,
                        'model' => $this->argument('model')
                    ]);
                }

            }

        }

    }


}
