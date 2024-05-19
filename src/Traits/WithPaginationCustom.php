<?php

namespace Zofe\Rapyd\Traits;

use Illuminate\Pagination\Paginator;
use Livewire\Features\SupportPagination\HandlesPagination;

trait WithPaginationCustom
{
    use HandlesPagination;

    public $perPage = 10;
    protected $paginationTheme = 'bootstrap';

    public function pageName(): string
    {
        if (property_exists($this, 'pageName')) {
            if (! isset($this->{$this->pageName})) {
                $this->{$this->pageName} = 1;
            }

            return $this->pageName;
        } else {
            return 'page';
        }
    }

    public function getQueryString()
    {
        return array_merge([$this->pageName() => ['except' => 1]], $this->queryString);
    }

    public function initializeWithPagination()
    {
        $this->{$this->pageName()} = $this->resolvePage();

        Paginator::currentPageResolver(function () {
            return $this->{$this->pageName()};
        });

        Paginator::defaultView($this->paginationView());
    }


    public function previousPage($pageName = 'page')
    {
        $this->setPage($this->{$this->pageName()} - 1);
    }

    public function nextPage($pageName = 'page')
    {
        $this->setPage($this->{$this->pageName()} + 1);
    }

    public function setPage($page, $pageName = 'page')
    {
        $this->{$this->pageName()} = $page;
    }

    public function resolvePage()
    {
        return request()->query($this->pageName(), $this->{$this->pageName()});
    }

    public function getPublicPropertiesDefinedBySubClass()
    {
        return tap(parent::getPublicPropertiesDefinedBySubClass(), function (&$props) {
            $props[$this->pageName()] = $this->{$this->pageName()};
        });
    }
}
