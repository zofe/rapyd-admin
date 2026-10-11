<?php

namespace Zofe\Rapyd\Modules\Remote\Expose;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ExposedResource
{
    public array $searchable = [];
    public array $fields = [];
    public ?Closure $label = null;
    public ?Closure $scope = null;

    public function __construct(public string $model) {}

    public function searchable(array $columns): static { $this->searchable = $columns; return $this; }
    public function fields(array $columns): static { $this->fields = $columns; return $this; }
    public function label(Closure $label): static { $this->label = $label; return $this; }
    public function scope(Closure $scope): static { $this->scope = $scope; return $this; }

    public function toArray(Model $model): array
    {
        return [
            'id'         => (string) $model->getKey(),
            'label'      => $this->label ? ($this->label)($model) : (string) $model->getKey(),
            'attributes' => Arr::only($model->attributesToArray(), $this->fields),
        ];
    }
}
