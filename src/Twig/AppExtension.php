<?php

namespace App\Twig;

use App\Controller\Admin\ArticleCrudController;
use App\Controller\Admin\CategoryCrudController;
use App\Controller\Admin\PageCrudController;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Menu;
use App\Entity\Page;
use Doctrine\Common\Collections\Collection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    const ADMIN_NAMESPACE = 'App\Controller\Admin';

    public function __construct(
        private RouterInterface $router,
        private AdminUrlGenerator $adminUrlGenerator,
        private Security $security
    ) {

    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ea_admin_url', [$this, 'getAdminUrl']),
            new TwigFunction('ea_edit', [$this, 'getAdminEditUrl']),
            new TwigFunction('entity_label', [$this, 'getEditCurrentEntityLabel']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('menuLink', [$this, 'menuLink']),
            new TwigFilter('categoriesToString', [$this, 'categoriesToString']),
            new TwigFilter('isCommentAuthor', [$this, 'isCommentAuthor']),
        ];
    }

    public function menuLink(Menu $menu): string
    {
        $url = $menu->getLink() ?: '#';

        if ($url !== '#') {
            return $url;
        }

        $page = $menu->getPage();

        if ($page) {
            $name = 'page_show';
            $slug = $page->getSlug();
        }

        $article = $menu->getArticle();

        if ($article) {
            $name = 'article_show';
            $slug = $article->getSlug();
        }

        $category = $menu->getCategory();

        if ($category) {
            $name = 'category_show';
            $slug = $category->getSlug();
        }

        return $this->router->generate($name, [
            'slug' => $slug
        ]);
    }

    public function categoriesToString(Collection $categories): string
    {
        $generateCategoryLink = function(Category $category) {
            $url = $this->router->generate('category_show', [
                'slug' => $category->getSlug()
            ]);
            return "<a href='$url' class='text-decoration-none' style='color: {$category->getColor()}'>{$category->getName()}</a>";
        };

        $categoryLinks = array_map($generateCategoryLink, $categories->toArray());

        return implode(', ', $categoryLinks);
    }

    public function getEditCurrentEntityLabel(object $entity): string
    {
        return match($entity::class) {
            Article::class => "Modifier l'article",
            Category::class => 'Modifier la catégorie',
            Page::class => 'Modifier la page'
        };
    }

    public function getAdminUrl(string $controller, string $action = Action::INDEX): string
    {
        return $this->adminUrlGenerator
            ->setController(self::ADMIN_NAMESPACE . '\\' . $controller)
            ->setAction($action)
            ->generateUrl();
    }

    public function getAdminEditUrl(object $entity): ?string
    {
        $crudController = match ($entity::class) {
            Article::class => ArticleCrudController::class,
            Category::class => CategoryCrudController::class,
            Page::class => PageCrudController::class
        };

        return $this->adminUrlGenerator
            ->setController($crudController)
            ->setAction(Action::EDIT)
            ->setEntityId($entity->getId())
            ->generateUrl();
    }

    public function isCommentAuthor(Comment $comment): bool
    {
        return $this->security->getUser() === $comment->getUser();
    }
}