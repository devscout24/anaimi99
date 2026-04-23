<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MiaHelper
{

    //------- For Image and File Upload
    public static function uploadFile($file, $directory)
    {
        $directoryPath = 'uploads/' . $directory;
        if (!file_exists(public_path($directoryPath))) {
            mkdir(public_path($directoryPath), 0755, true);
        }
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $directoryPath . '/' . $fileName;
        // $filePathFull = public_path($filePath);

        $file->move(public_path($directoryPath), $fileName);
        return $filePath;
    }

    // Usage Example:
    //  if ($request->hasFile('image')) {
    //         $milestone->image = MiaHelper::uploadFile($request->file('image'), 'milestone-images');
    //     }


    //-------- For File Deletion
    public static function deleteFile($filePath)
    {
        if ($filePath && file_exists(public_path($filePath))) {
            unlink(public_path($filePath));
        }
    }

    // Usage Example:
    // if ($request->file('image')) {
    //         MiaHelper::deleteFile($user->image);
    // }

    //-------- For File Update
    public static function updateFile($oldFilePath, $newFile, $directory)
    {
        // Delete old file
        self::deleteFile($oldFilePath);

        // Upload new file
        return self::uploadFile($newFile, $directory);
    }

    // Usage Example:
    // if ($request->hasFile('banner_image')) {
    //         $history->banner_image = MiaHelper::updateFile($history->banner_image, $request->file('banner_image'), 'history-images');
    //     }


    // For Image Upload with Intervention
    public static function uploadImageResize($file, $directory, $width = null, $height = null)
    {
        $directoryPath = 'uploads/' . $directory;
        if (!file_exists(public_path($directoryPath))) {
            mkdir(public_path($directoryPath), 0755, true);
        }
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $directoryPath . '/' . $fileName;
        // $filePathFull = public_path($filePath);

        $image = Image::make($file);

        // Resize logic
        if ($width && !$height) {
            $image->resize($width, null);
        } elseif ($height && !$width) {
            $image->resize(null, $height);
        } elseif ($width && $height) {
            $image->resize($width, $height);
        }

        $image->save(public_path($filePath));
        return $filePath;
    }


    //-------- For Slug Generation
    public static function generateSlug($modelClass, $name, $field = 'slug', $ignoreId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        $query = $modelClass::where($field, $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;

            $query = $modelClass::where($field, $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }


    // Usage Example:
    // $slug = MiaHelper::generateSlug(Category::class, $request->name);
    // if ($category->name !== $request->name) {
    //         $category->slug = MiaHelper::generateSlug(Category::class, $request->name, 'slug', $category->id);
    //     }


}
