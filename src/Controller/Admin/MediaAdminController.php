<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Repository\MediaRepository;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/media')]
final class MediaAdminController extends AbstractController
{
    private const MAX_FILE_SIZE_BYTES = 5_242_880;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploader $mediaUploader,
    ) {
    }

    #[Route('', name: 'api_admin_media_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $mediaItems = $this->mediaRepository->findBy([], ['uploadedAt' => 'DESC']);

        $items = array_map(fn (Media $media): array => $this->normalizeMedia($media), $mediaItems);

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'api_admin_media_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $uploaded = $request->files->get('file');
        if (!$uploaded instanceof UploadedFile) {
            return $this->json(['errors' => ['file' => 'File is required.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$uploaded->isValid()) {
            return $this->json(['errors' => ['file' => $this->resolveUploadErrorMessage($uploaded)]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($uploaded->getSize() > self::MAX_FILE_SIZE_BYTES) {
            return $this->json(['errors' => ['file' => 'File exceeds 5MB limit.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $fileData = $this->mediaUploader->upload($uploaded);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['errors' => ['file' => $exception->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $alt = trim((string) ($request->request->get('alt') ?? ''));

        $media = (new Media())
            ->setFilename($fileData['filename'])
            ->setOriginalName((string) $uploaded->getClientOriginalName())
            ->setAlt($alt !== '' ? $alt : null)
            ->setMimeType($fileData['mimeType'])
            ->setSizeBytes($fileData['sizeBytes'])
            ->setWidth($fileData['width'])
            ->setHeight($fileData['height'])
            ->setUploadedAt(new \DateTimeImmutable());

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $this->json($this->normalizeMedia($media), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_admin_media_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $media = $this->mediaRepository->find($id);
        if (!$media instanceof Media) {
            return $this->json(['error' => 'Media not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('alt', $payload)) {
            $alt = $payload['alt'];
            $media->setAlt($alt !== null ? trim((string) $alt) : null);
        }

        $this->entityManager->flush();

        return $this->json($this->normalizeMedia($media));
    }

    #[Route('/{id}', name: 'api_admin_media_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $media = $this->mediaRepository->find($id);
        if (!$media instanceof Media) {
            return $this->json(['error' => 'Media not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->mediaUploader->remove($media->getFilename());
        $this->entityManager->remove($media);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function normalizeMedia(Media $media): array
    {
        return [
            'id' => $media->getId(),
            'filename' => $media->getFilename(),
            'path' => '/images/' . $media->getFilename(),
            'originalName' => $media->getOriginalName(),
            'alt' => $media->getAlt(),
            'mimeType' => $media->getMimeType(),
            'sizeBytes' => $media->getSizeBytes(),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'uploadedAt' => $media->getUploadedAt()->format(DATE_ATOM),
        ];
    }

    private function resolveUploadErrorMessage(UploadedFile $uploaded): string
    {
        return match ($uploaded->getError()) {
            UPLOAD_ERR_INI_SIZE => sprintf('File exceeds server upload limit (%s).', (string) (ini_get('upload_max_filesize') ?: 'php.ini')),
            UPLOAD_ERR_FORM_SIZE => sprintf('File exceeds form upload limit (%s).', (string) (ini_get('post_max_size') ?: 'form limit')),
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
            default => 'Invalid upload.',
        };
    }
}
