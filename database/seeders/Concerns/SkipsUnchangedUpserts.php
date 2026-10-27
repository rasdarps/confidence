<?php

namespace Database\Seeders\Concerns;

trait SkipsUnchangedUpserts
{
    protected function upsertOnlyWhenChanged(string $modelClass, array $keyAttributes, array $values): void
    {
        $model = $modelClass::firstOrNew($keyAttributes);

        $model->fill($values);

        if (! $model->exists || $model->isDirty()) {
            $model->save();
        }
    }
}