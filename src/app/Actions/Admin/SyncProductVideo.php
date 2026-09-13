<?php

namespace App\Actions\Admin;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class SyncProductVideo
{
    public function handle(Product $product, ?UploadedFile $upload, bool $remove): void
    {
        if (! $upload && ! $remove) {
            return;
        }

        $oldPath = $product->video_path;
        $newPath = null;
        try {
            if ($upload) {
                $directory = 'videos/products';
                File::ensureDirectoryExists(public_path($directory));
                $filename = 'product-'.$product->getKey().'-'.Str::uuid().'.'.$upload->extension();
                $upload->move(public_path($directory), $filename);
                $newPath = $directory.'/'.$filename;
            }
            $product->forceFill(['video_path' => $newPath])->save();
        } catch (Throwable $exception) {
            if ($newPath) {
                File::delete(public_path($newPath));
            }
            throw $exception;
        }

        if ($oldPath && str_starts_with($oldPath, 'videos/products/product-')) {
            DB::afterCommit(fn () => File::delete(public_path($oldPath)));
        }
    }
}
