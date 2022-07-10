<?php

namespace App\DataFixtures;

use App\Entity\Option;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class OptionFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $options[] = new Option('Texte du copyright', 'blog_copyright', 'Tous droits réservés', TextType::class);
        $options[] = new Option("Nombre d'articles par page", "blog_articles_limit", 5, NumberType::class);
        $options[] = new Option("Tout le monde peu s'inscrire", "users_can_register", true, CheckboxType::class);
        $options[] = new Option('A propos', 'blog_about', 'A propos de moi', TextType::class);

        foreach ($options as $option) {
            $manager->persist($option);
        }

        $manager->flush();
    }
}