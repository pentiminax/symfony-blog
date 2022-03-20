<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\Type\CommentFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/article/{slug}', name: 'article_show')]
    public function show(?Article $article): Response
    {
        if (!$article) {
            return $this->redirectToRoute('home');
        }

        $comment = new Comment($article);

        $commentForm = $this->createForm(CommentFormType::class, $comment);

        return $this->renderForm('article/index.html.twig', [
            'entity' => $article,
            'commentForm' => $commentForm
        ]);
    }
}