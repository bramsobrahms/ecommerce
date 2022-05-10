<?php

namespace App\DataFixtures;

use App\Entity\Products;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class ProductsFixtures extends Fixture
{

    public function __construct(SluggerInterface $slugger){
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $faker= Faker\Factory::create('fr_BE');

        for($prod =1; $prod<=10; $prod++){
            $product = new Products();
            $product->setName($faker->text(15));
            $product->setDescription($faker->text());
            $product->setSlug($this->slugger->slug($product->getName())->lower());
            $product->setPrice($faker->numberBetween(900,150000));
            $product->setStock($faker->numberBetween(0,10));

            //on va chercher une ref de catégorie
            $category = $this->getReference('cat-'.rand(1,8));
            $product->setCategories($category);

            //mettre une reference
            $this->setReference('prod-'.$prod, $product);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
