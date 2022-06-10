<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\Type\CommentType;
use App\Service\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @method User getUser()
 */
class ArticleController extends AbstractController
{
    #[Route('/article/{slug}', name: 'article_show')]
    public function show(?Article $article, CommentService $commentService): Response
    {
        if (!$article) {
            return $this->redirectToRoute('home');
        }

        $parameters = [
            'entity' => $article
        ];

        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $commentForm = $this->createForm(CommentType::class,  new Comment($article, $this->getUser()));
            $parameters['commentForm'] = $commentForm;
        }

        return $this->renderForm('article/index.html.twig', $parameters);
    }

    #[Route('/ajax/articles/{id}/comments', name: 'article_list_comments', methods: ['GET'])]
    public function listComments(?Article $article, NormalizerInterface $normalizer): Response
    {
        $comments = $normalizer->normalize($article->getComments(), context: [
            'groups' => 'comment'
        ]);

        return $this->json($comments);
    }
}