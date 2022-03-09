<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    public function __construct(
        private CommentRepository $commentRepo
    ) {}

    #[Route('/article/{slug}', name: 'article_show')]
    public function show(?Article $article): Response
    {
        if (!$article) {
            return $this->redirectToRoute('home');
        }

        $comments = $this->commentRepo->findAllByArticle($article);

        return $this->renderForm('article/index.html.twig', [
            'entity' => $article,
            'comments' => $comments,
            'numberOfComments' => $this->commentRepo->count(['article' => $article])
        ]);
    }
}