<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** @method User getUser() */
class CommentController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepo,
        private CommentRepository $commentRepo,
        private CommentService    $commentService
    )
    {
    }

    #[Route('/ajax/comments', name: 'comment_add', methods: ['POST'])]
    public function addComment(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->request->all('comment');

        if (!$this->isCsrfTokenValid('comment-add', $data['_token'])) {
            return $this->json([
                'code' => 'INVALID_CSRF_TOKEN'
            ], Response::HTTP_BAD_REQUEST);
        }

        $article = $this->articleRepo->findOneBy(['id' => $data['article']]);

        $comment = $this->commentService->add($data, $article);

        if (!$article) {
            return $this->json([
                'code' => 'ARTICLE_NOT_FOUND'
            ], Response::HTTP_BAD_REQUEST);
        }

        $html = $this->renderView('comment/index.html.twig', [
            'comment' => $comment
        ]);

        return $this->json([
            'code' => 'COMMENT_ADDED_SUCCESSFULLY',
            'detail' => [
                'comment' => $this->commentService->normalize($comment),
                'numberOfComments' => $this->commentRepo->count(['article' => $article])
            ],
            'message' => $html,
        ]);
    }

    #[Route('/ajax/comments/answer', name: 'comment_answer_add', methods: ['POST'])]
    public function addAnswer(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'code' => 'NOT_AUTHENTICATED'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->request->all('comment');

        $comment = $this->commentRepo->findOneBy(['id' => $data['id']]);

        $answer = $this->commentService->add($data, $comment->getArticle(), $comment, true);

        $html = $this->renderView('comment/index.html.twig', [
            'comment' => $answer
        ]);

        return $this->json([
            'code' => 'ANSWER_ADDED_SUCCESSFULLY',
            'detail' => [
                'answer' => $this->commentService->normalize($answer)
            ],
            'message' => $html
        ]);
    }

    #[Route('/ajax/comments/{id}', name: 'comment_edit', methods: ['GET', 'PATCH'])]
    public function editComment(?Comment $comment, Request $request): Response
    {
        if (!$comment) {
            return $this->json([
                'code' => 'COMMENT_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            $cardText = $this->renderView('comment/_card_text.html.twig', [
                'action' => 'edit',
                'content' => $comment->getContent()
            ]);

            $cardFooter = $this->renderView('comment/_card_footer.twig', [
                'action' => 'edit',
                'id' => $comment->getId()
            ]);

            return $this->json([
                'cardText' => trim($cardText),
                'cardFooter' => trim($cardFooter)
            ]);
        }

        $content = json_decode($request->getContent(), true)['content'];

        $this->commentService->edit($comment, $content);

        return $this->json([
            'code' => 'COMMENT_SUCCESSFULLY_EDITED',
            'detail' => [
                'comment' => $this->commentService->normalize($comment)
            ],
            'message' => null
        ]);
    }

    #[Route('/ajax/comments/{id}', name: 'comment_delete', methods: ['DELETE'])]
    public function deleteComment(?Comment $comment): Response
    {
        $preliminaryChecks = $this->commentService->deletePreliminaryChecks($comment);

        if ($preliminaryChecks instanceof JsonResponse) {
            return $preliminaryChecks;
        }

        $this->commentService->delete($comment);

        return $this->json([
            'code' => 'COMMENT_SUCCESSFULLY_DELETED',
            'detail' => [
                'numberOfComments' => $this->commentRepo->count(['article' => $comment->getArticle()])
            ],
            'message' => null
        ]);
    }
}
