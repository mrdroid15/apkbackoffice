<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Apkads extends Model
{
    protected $table = 'apkads';
    protected $fillable = [
        'name',
        'packagename',
        'image',
        'link',
    ];

    /**
     * Keep the R2 bucket in sync with the database: when a row is deleted or
     * its image is replaced, remove the orphaned object from object storage.
     *
     * Notes:
     *   - disk('s3') matches the disk hardcoded in ApkadsForm's FileUpload.
     *   - Storage::delete() is idempotent — deleting a missing key is a no-op,
     *     so we don't need to defend against double-deletes or already-gone
     *     files after a partial failure on a previous save.
     *   - Synchronous on purpose: for an admin panel with low delete volume,
     *     waiting ~50ms for the R2 call is cheaper than the operational cost
     *     of a queued job. Move to dispatch() if bulk-delete ever becomes a
     *     thing.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $apkads): void {
            if ($apkads->image) {
                Storage::disk('s3')->delete($apkads->image);
            }
        });

        static::updating(function (self $apkads): void {
            if (! $apkads->isDirty('image')) {
                return;
            }
            $previousKey = $apkads->getOriginal('image');
            if ($previousKey) {
                Storage::disk('s3')->delete($previousKey);
            }
        });
    }
}
