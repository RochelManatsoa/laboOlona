<?php

namespace App\Form\DataTransformer;

use App\Entity\Candidate\Competences;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CompetencesTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager, 
        private SluggerInterface $sluggerInterface,
        private MailerService $mailerService,
        private UserService $userService,
    ){       
    }

    public function transform($competences)
    {
        if (null === $competences) {
            return '';
        }
        
        // Convertir en tableau si c'est une ArrayCollection ou une PersistentCollection
        if ($competences instanceof \Doctrine\Common\Collections\Collection) {
            $competences = $competences->toArray();
        }

        return implode(',', array_map(function(Competences $competence) {
            return $competence->getNom();
        }, $competences));
    }

    public function reverseTransform($competencesString)
    {
        if ('' === $competencesString || null === $competencesString) {
            return new ArrayCollection(); // ou null, selon votre cas
        }
        // Convertissez la chaîne de caractères en une collection d'objets Competences
        $competencesArray = explode(',', $competencesString);
        $competencesCollection = new ArrayCollection();

        foreach ($competencesArray as $competenceId) {
            $competence = $this->entityManager->getRepository(Competences::class)->findOneBy([
                'nom' => $competenceId
            ]);
            if (is_numeric($competenceId)) {
                $competence = $this->entityManager->getRepository(Competences::class)->find($competenceId);
            } else {
                // Trouvez ou créez une entité basée sur la valeur textuelle
                $competence = $this->entityManager->getRepository(Competences::class)->findOneBy([
                    'nom' => $competenceId
                ]);
                if(!$competence instanceof Competences){
                    $competence = new Competences();
                    $competence
                        ->setNom($competenceId)
                        ->setSlug($this->sluggerInterface->slug(strtolower($competenceId)))
                    ; 
                    // ou toute autre opération pour initialiser l'entité
                    $this->entityManager->persist($competence);
                    $this->entityManager->flush();
                }
            }
            if ($competence instanceof Competences) {
                $competencesCollection->add($competence);
            }
        }

        return $competencesCollection;
    }
}