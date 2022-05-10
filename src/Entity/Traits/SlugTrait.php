<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait SlugTrait
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    public function getSlug(): ?String
    {
        return $this->slug;
    }

    public function setSlug(String $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}