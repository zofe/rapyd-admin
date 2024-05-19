<?php

namespace Zofe\Rapyd\Http\Livewire;

use Livewire\Component;

abstract class BaseComponent extends Component
{
    protected $rules = [];


    public function getQueryString()
    {
        // dump($_SERVER['QUERY_STRING'],$this->queryString);

        //dd($this->queryString,http_build_query($this->queryString));
        //return array_merge(['Zofe%5CRapyd%5CBreadcrumbs%5CBreadcrumbsMiddleware' => ['except' => '*']], $this->queryString);
        //parse_str(http_build_query($this->queryString), $vars);
        //unset($this->queryString["Zofe\\Rapyd\\Breadcrumbs\\BreadcrumbsMiddleware"]);
        //$this->queryString = http_build_query($vars);


        return $this->queryString;
    }


    public function addRule($field, $rule)
    {
        $this->rules[$field] = $rule;
    }

    public function render()
    {
        return '<div></div>';
    }
}
