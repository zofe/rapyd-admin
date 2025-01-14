<?php

namespace Zofe\Rapyd\Traits;

trait WithDataTable
{
    //use WithPaginationCustom;
    use \Livewire\WithPagination;
    use WithSorting;

    public $perPage = 10;
}
