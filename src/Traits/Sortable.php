<?php

namespace Zofe\Rapyd\Traits;

use Illuminate\Support\Collection;

trait Sortable
{
    public static $parentIdField = 'parent_id';
    public static $sortOrderField = 'order';

    public static function reorderItem($itemId, $newOrder, $newParentId = null)
    {
        $item = static::find($itemId);
        if (!$item) {
            return false;
        }

        if(static::$parentIdField) {
            $item->{static::$parentIdField} = $newParentId;
            $item->save();
        }

        $siblings = static::where(static::$parentIdField, $newParentId)
            ->where($item->getKeyName(), '!=', $item->id)
            ->orderBy(static::$sortOrderField)
            ->get()
            ->values();

        $ordered = new Collection();
        $inserted = false;

        foreach ($siblings as $index => $sibling) {
            if (!$inserted && $index == $newOrder) {
                $ordered->push($item);
                $inserted = true;
            }
            $ordered->push($sibling);
        }


        if (!$inserted) {
            $ordered->push($item);
        }

        foreach ($ordered as $index => $orderItem) {

            $orderItem->{static::$sortOrderField} = $index;
            $orderItem->save();
        }

        return true;
    }
}
