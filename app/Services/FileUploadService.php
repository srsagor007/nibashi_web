<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Str;

class FileUploadService
{
    public static function getFileUrl(?string $path = null): string
    {
        if (! $path) {
            return asset('theme/media/no-pictures.png');
        }

        if (strpos($path, 'https://momenta.renata-ltd.com/') === 0) {
            $file_path = str_replace('https://momenta.renata-ltd.com/', '', $path);

            return asset("storage/$file_path");
        }

        if (strpos($path, 'http') === 0) {
            return $path;
        }

        return $path ? asset("storage/$path") : asset('theme/media/no-pictures.png');
    }

    public function upload($fileName, $path, $uploadFileName = null)
    {
        ini_set('upload_max_filesize', '500M');
        ini_set('post_max_size', '500M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');

        try {
            if ($uploadFileName) {
                return request()
                    ->file($fileName)
                    ->storeAs($path, $uploadFileName, 'public');
            }

            return request()
                ->file($fileName)
                ->store($path, 'public');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function upload_multiple($fileName, $path)
    {
        ini_set('upload_max_filesize', '500M');
        ini_set('post_max_size', '500M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');

        $files = request()->file($fileName);

        if (! is_array($files)) {
            $files = [$files];
        }

        $uploadedFiles = [];
        foreach ($files as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $extension = $file->getClientOriginalExtension();
                $file_name_slug = \Str::of($file->getClientOriginalName())
                    ->slug('_');
                // ->replaceMatches('/[^a-z0-9\-\.]/', '')
                // ->toString();
                $file_name_slug = $file_name_slug . '.' . $extension;
                $uploadedFiles[] = $file->storeAs($path, $file_name_slug, 'public');
            }
        }

        return $uploadedFiles;
    }

    public function upload_file($file, $path)
    {
        try {
            return $file->store($path, 'public');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function upload_multiple_to_s3(array $files, string $folder = 'uploads'): array
    {
        $uploadedPaths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $extension = $file->getClientOriginalExtension();
                $file_name_slug = \Str::of($file->getClientOriginalName())->slug('_');
                $file_name = $file_name_slug . '.' . $extension;
                $path = "$folder/$file_name";

                Storage::disk('s3')->put($path, file_get_contents($file), 'public');

                $uploadedPaths[] = $path;
            }
        }

        return $uploadedPaths;
    }

    public static function getFileUrlS3(?string $path = null): string
    {
        if (! $path) {
            return asset('theme/media/no-pictures.png');
        }

        return strpos($path, 'http') === 0 ? $path : Storage::disk('s3')->url($path);
    }

    /**
     * Generate a unique filename for the uploaded file
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($name);
        $timestamp = now()->timestamp;

        return "{$slug}-{$timestamp}." . $extension;
    }

    public function delete(string $path)
    {
        try {
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
