<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles')]
class ArticleController extends AbstractController
{
    #[Route('', name: 'api_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findBy([], ['createdAt' => 'DESC']);

        $data = array_map([$this, 'articleToArray'], $articles);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_article_show', methods: ['GET'])]
    public function show(?Article $article): JsonResponse
    {
        if (!$article) {
            return $this->json(['message' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->articleToArray($article));
    }

    #[Route('', name: 'api_article_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));

        $validationError = $this->validateArticleData($title, $content);
        if ($validationError) {
            return $validationError;
        }

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setCreatedAt(new \DateTimeImmutable());

        $em->persist($article);
        $em->flush();

        return $this->json($this->articleToArray($article), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_article_update', methods: ['PUT'])]
    public function update(?Article $article, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$article) {
            return $this->json(['message' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));

        $validationError = $this->validateArticleData($title, $content);
        if ($validationError) {
            return $validationError;
        }

        $article->setTitle($title);
        $article->setContent($content);
        $article->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json($this->articleToArray($article));
    }

    #[Route('/{id}', name: 'api_article_delete', methods: ['DELETE'])]
    public function delete(?Article $article, EntityManagerInterface $em): JsonResponse
    {
        if (!$article) {
            return $this->json(['message' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($article);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function validateArticleData(?string $title, ?string $content): ?JsonResponse
    {
        if (!$title || !$content || $title === '' || $content === '') {
            return $this->json(['message' => 'Title and content are required'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($title) > 150) {
            return $this->json(['message' => 'Title cannot exceed 150 characters'], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    private function articleToArray(Article $article): array
    {
        return [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'created_at' => $article->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $article->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}