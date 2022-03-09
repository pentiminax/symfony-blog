<?php

namespace App\Controller;

use App\Entity\Page;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/page/{slug}', name: 'page_show')]
    public function show(?Page $page): Response
    {
        if (!$page) {
            return $this->redirectToRoute('home');
        }

        return $this->render('page/index.html.twig', [
            'entity' => $page
        ]);
    }
}
