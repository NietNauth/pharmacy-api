<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    protected Cloudinary $cloudinary;
    protected string $folder;

    public function __construct()
    {
        // Disable SSL verification for local development (Fixes cURL error 60 on Windows)
        stream_context_set_default([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key'    => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
        $this->folder = config('services.cloudinary.folder', 'pharmacy');
    }

    /**
     * Upload a file to Cloudinary and return the secure URL.
     *
     * @param  UploadedFile  $file
     * @param  string|null   $subfolder  Optional subfolder inside the main folder (e.g. 'products')
     * @return string  The secure public URL of the uploaded asset
     *
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $subfolder = 'products'): string
    {
        $folder = $this->folder . '/' . $subfolder;

        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder'           => $folder,
                'resource_type'    => 'image',
                'use_filename'     => false,
                'unique_filename'  => true,
                'overwrite'        => false,
                'transformation'   => [
                    ['quality' => 'auto', 'fetch_format' => 'auto'],
                ],
            ]
        );

        if (empty($result['secure_url'])) {
            throw new \Exception('Cloudinary did not return a secure URL.');
        }

        return $result['secure_url'];
    }

    /**
     * Delete an asset from Cloudinary by its public_id.
     *
     * @param  string  $publicId
     * @return array
     */
    public function delete(string $publicId): array
    {
        return (array) $this->cloudinary->uploadApi()->destroy($publicId);
    }
}
