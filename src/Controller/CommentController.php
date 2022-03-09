<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** @method User getUser() */
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepo,
        private CommentRepository $commentRepo
    ) {}

    #[Route('/ajax/comments', name: 'comment_add')]
    public function add(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $commentForm = $request->request->all('comment_form');

        if (!$this->isCsrfTokenValid('add-comment', $commentForm['token'])) {
            return $this->json([
                'code' => 'INVALID_CSRF_TOKEN'
            ], Response::HTTP_BAD_REQUEST);
        }

        $article = $this->articleRepo->findOneBy(['id' => $commentForm['article']]);

        if (!$article) {
            return $this->json([
                'code' => 'ARTICLE_NOT_FOUND'
            ], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setContent($commentForm['content']);
        $comment->setArticle($article);
        $comment->setUser($this->getUser());
        $comment->setCreatedAt(new \DateTime());
        $this->em->persist($comment);
        $this->em->flush();

        $html = $this->renderView('comment/index.html.twig', [
            'comment' => $comment
        ]);

        return $this->json([
            'code' => 'COMMENT_ADDED_SUCCESSFULLY',
            'message' => $html,
            'numberOfComments' => $this->commentRepo->count(['article' => $article])
        ]);
    }
}
