<?php

namespace Zofe\Rapyd\Traits;

trait WithDataTable
{
    //use WithPaginationCustom;
    use \Livewire\WithPagination;
    use WithSorting;

    public $perPage = 10;

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
}
