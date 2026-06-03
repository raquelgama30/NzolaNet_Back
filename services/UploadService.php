<?php

use Cloudinary\Cloudinary;

class UploadService
{
    private int $maxSizeImagem = 5 * 1024 * 1024;
    private int $maxSizeVideo = 50 * 1024 * 1024;

    private Cloudinary $cloudinary;

    private array $imageMimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp'
    ];

    private array $videoMimes = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/webm'
    ];

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => getenv('CLOUDINARY_API_KEY'),
                'api_secret' => getenv('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    public function uploadFotoPerfil(array $file): array
    {
        return $this->uploadImagem($file, 'perfil');
    }

    public function uploadFotoCapa(array $file): array
    {
        return $this->uploadImagem($file, 'capa');
    }

    public function uploadMedia(array $file, string $tipo): array
    {
        if ($tipo === 'video') {
            return $this->uploadVideo($file);
        }

        return $this->uploadImagem($file, 'posts');
    }

    private function uploadImagem(array $file, string $pasta): array
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                "success" => false,
                "message" => "Nenhum ficheiro enviado ou erro no upload"
            ];
        }

        if ($file['size'] > $this->maxSizeImagem) {
            return [
                "success" => false,
                "message" => "Imagem demasiado grande. Máximo 5MB"
            ];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->imageMimes)) {
            return [
                "success" => false,
                "message" => "Tipo inválido. Usa JPEG, PNG ou WebP"
            ];
        }

        return $this->uploadCloudinary($file, $pasta, 'image');
    }

    private function uploadVideo(array $file): array
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                "success" => false,
                "message" => "Nenhum ficheiro enviado ou erro no upload"
            ];
        }

        if ($file['size'] > $this->maxSizeVideo) {
            return [
                "success" => false,
                "message" => "Vídeo demasiado grande. Máximo 50MB"
            ];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->videoMimes)) {
            return [
                "success" => false,
                "message" => "Tipo inválido. Usa MP4, WebM ou MOV"
            ];
        }

        return $this->uploadCloudinary($file, 'videos', 'video');
    }

    private function uploadCloudinary(
        array $file,
        string $pasta,
        string $resourceType
    ): array {
        try {

            $resultado = $this->cloudinary
                ->uploadApi()
                ->upload(
                    $file['tmp_name'],
                    [
                        'folder' => "nzolanet/$pasta",
                        'resource_type' => $resourceType
                    ]
                );

            return [
                "success" => true,
                "url" => $resultado['secure_url'],
                "public_id" => $resultado['public_id']
            ];

        } catch (Exception $e) {

            return [
                "success" => false,
                "message" => "Erro ao enviar para Cloudinary: " . $e->getMessage()
            ];
        }
    }
}