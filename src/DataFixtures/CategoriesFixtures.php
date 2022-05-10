<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoriesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $category = new Categories();
        $category->setName('Informatique');
        //$category->setSlug('informatique');
        // $manager->persist($product);

        $manager->flush();
    }
}
