<?php

namespace App\DataFixtures;

use App\Entity\Users;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class UsersFixtures extends Fixture
{

    public function __construct(UserPasswordHasherInterface $passwordEncoder, SluggerInterface $slugger){

        $this->passwordEncoder = $passwordEncoder;
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Users();
        $admin->setEmail('admin@gmail.com');
        $admin->setLastname('WICK');
        $admin->setFirstname('John');
        $admin->setAddress('12 rue de La Paix');
        $admin->setZipcode('1000');
        $admin->setCity('Bruxelles');
        $admin->setPassword(
            $this->passwordEncoder->hashPassword($admin,'admin')
        );
        $admin->setRoles(['ROLES_ADMIN']);

        $manager->persist($admin);

        //Generate datas by Faker
        $faker = Faker\Factory::create('fr_BE');

        for($usr = 1; $usr<=5; $usr++){
            $user = new Users();
            $user->setEmail($faker->email);
            $user->setLastname($faker->lastName);
            $user->setFirstname($faker->firstname);
            $user->setAddress($faker->streetAddress);
            $user->setZipcode(str_replace(' ','',$faker->postcode));
            $user->setCity($faker->city);
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user,'secret')
            );
            //dump($user);
            $manager->persist($user); 
        }

        $manager->flush();
    }
}
