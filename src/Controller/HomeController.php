<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\User;
use App\Form\Type\WelcomeType;
use App\Model\WelcomeModel;
use App\Repository\CategoryRepository;
use App\Service\ArticleService;
use App\Service\OptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(private OptionService $optionService) {}

    #[Route('/', name: 'home')]
    public function index(ArticleService $articleService, CategoryRepository $categoryRepo): Response
    {
        return $this->render('home/index.html.twig', [
            'articles' => $articleService->getPaginatedArticles(),
            'categories' => $categoryRepo->findAllForWidget()
        ]);
    }

    #[Route('/welcome', name: 'welcome')]
    public function welcome(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->optionService->getValue(WelcomeModel::SITE_TITLE_NAME)) {
            return $this->redirectToRoute('home');
        }

        $welcomeForm = $this->createForm(WelcomeType::class, new WelcomeModel());

        $welcomeForm->handleRequest($request);

        if ($welcomeForm->isSubmitted() && $welcomeForm->isValid()) {
            /** @var WelcomeModel $data */
            $data = $welcomeForm->getData();

            $siteTitle = new Option(WelcomeModel::SITE_TITLE_NAME, $data->getSiteTitle(), WelcomeModel::SITE_TITLE_LABEL, TextType::class);
            $headerTitle = new Option(WelcomeModel::HEADER_TITLE_NAME, $data->getSiteTitle(), WelcomeModel::HEADER_TITLE_LABEL, TextType::class);

            $user = new User($data->getUsername());
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword($passwordHasher->hashPassword($user, $data->getPassword()));

            $em->persist($siteTitle);
            $em->persist($headerTitle);
            $em->persist($user);

            $em->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('home/welcome.html.twig', [
          'form' => $welcomeForm->createView()
        ]);
    }
}
