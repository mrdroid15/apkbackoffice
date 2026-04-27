<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Apkads extends Model
{
    protected $table = 'apkads';
    protected $fillable = [
        'name',
        'packagename',
        'image',
        'link',
        'privacy_policy',
        'privacy_policy_slug',
        'privacy_policy_generated_at',
    ];

    protected $casts = [
        'privacy_policy_generated_at' => 'datetime',
    ];

    /**
     * Slug rule: an Android packagename like "com.example.app" is already
     * URL-safe (alphanumerics + dots/dashes/underscores), so we use it as-is.
     * For anything else (empty, weird characters, non-ASCII), we fall back to
     * Str::slug($name). The result is collision-resolved against the existing
     * slugs in the table by appending -2, -3, ... until unique.
     */
    private const SAFE_PACKAGENAME = '/^[A-Za-z0-9._-]+$/';

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

        // Slug stability: compute on create, and on update only when the slug
        // is null (i.e., the editor explicitly cleared it to force a regen).
        // Renaming the app or changing the package never silently rewrites
        // the public URL, so existing links don't break.
        static::creating(function (self $apkads): void {
            if (empty($apkads->privacy_policy_slug)) {
                $apkads->privacy_policy_slug = self::buildUniqueSlug($apkads);
            }
        });

        static::updating(function (self $apkads): void {
            if (empty($apkads->privacy_policy_slug)) {
                $apkads->privacy_policy_slug = self::buildUniqueSlug($apkads);
            }
        });
    }

    /**
     * The public URL where this apkad's privacy policy lives. Returns null
     * until the row has a slug (i.e., before the first save).
     */
    protected function privacyPolicyUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->privacy_policy_slug) {
                return null;
            }
            return 'https://' . config('app.privacy_policy_domain') . '/' . $this->privacy_policy_slug;
        });
    }

    private static function buildUniqueSlug(self $apkads): string
    {
        $base = self::baseSlugFor($apkads);
        $candidate = $base;
        $suffix = 2;

        while (
            self::where('privacy_policy_slug', $candidate)
                ->when($apkads->exists, fn ($q) => $q->where('id', '!=', $apkads->id))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    private static function baseSlugFor(self $apkads): string
    {
        $package = (string) $apkads->packagename;
        if ($package !== '' && preg_match(self::SAFE_PACKAGENAME, $package) === 1) {
            return $package;
        }
        $fromName = Str::slug((string) $apkads->name);
        return $fromName !== '' ? $fromName : 'app-' . ($apkads->id ?: Str::random(6));
    }
}
