<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait Auditable
 *
 * Tell static analyzers/IDEs that this trait is used in Eloquent models
 * so methods like `creating`, `updating`, `deleting` are recognised.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Auditable
{
    /**
     * Boot the auditable trait.
     */
    public static function bootAuditable()
    {
        static::creating(function ($model) {
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        static::deleting(function ($model) {
            // If the model uses SoftDeletes, set deleted_by before soft delete
            if (method_exists($model, 'isForceDeleting')) {
                if (! $model->isForceDeleting()) {
                    $model->deleted_by = Auth::id();
                    $model->saveQuietly();
                }
            } else {
                // non-soft delete
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }
}
