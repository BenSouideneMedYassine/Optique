<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\LunetteRepository;
use App\Entity\Lunette;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(LunetteRepository $lr): Response
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'lunettes' => $lr->findAll(),
        ]);
    }
    #[Route('/item/{id}', name: 'app_item')]
    public function indexitem(LunetteRepository $lr, int $id): Response
    {
        $lunette = $lr->find($id);
        if (!$lunette) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }
        return $this->render('default/item.html.twig', [
            'lunette' => $lunette, 
        ]);
    }



//     #[Route('/acheter/{id}', name: 'app_default_acheter', methods: ['POST'])]
// public function acheter(
//     LunetteRepository $lunetteRepository,
//     CommandeRepository $commandeRepository,
//     EntityManagerInterface $entityManager,
//     int $id
// ): Response {
//     // Récupérer la lunette par son ID
//     $lunette = $lunetteRepository->find($id);

//     // Vérifier si la lunette existe et est disponible
//     if (!$lunette || $lunette->getQuantite() <= 0) {
//         $this->addFlash('danger', 'Lunette indisponible ou épuisée.');
//         return $this->redirectToRoute('app_default');
//     }

//     // Créer une nouvelle commande
//     $commande = new Commande();
//     $commande->setProduit($lunette->getModele());
//     $commande->setQuantite(1);
//     $commande->setPrix($lunette->getPrix());
//     $commande->setDateCommande(new \DateTime());

//     // Persister la commande
//     $entityManager->persist($commande);

//     // Diminuer la quantité de la lunette
//     $lunette->setQuantite($lunette->getQuantite() - 1);

//     // Sauvegarder les changements dans la base de données
//     $entityManager->flush();

//     // Ajouter un message flash pour l'utilisateur
//     $this->addFlash('success', 'Votre commande a été enregistrée avec succès !');

//     // Rediriger vers la page d'accueil ou une page de confirmation
//     return $this->redirectToRoute('app_default');
// }

#[Route('/achat/{id}', name: 'app_default_achat', methods: ['POST'])]
// #[IsGranted('ROLE_USER')]
    public function acheter(
        Lunette $lunette,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        if ($lunette->getQuantite() <= 0) {
            $this->addFlash('danger', 'Cette lunette est indisponible.');
            return $this->redirectToRoute('app_default');
        }

        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour effectuer cet achat.');
            return $this->redirectToRoute('app_login');
        }

        $commande = new Commande();
        $commande->setLunette($lunette);
        $commande->setQuantite(1); 
        $commande->setPrix($lunette->getPrix());
        $commande->setDate(new \DateTime());

        $commande->setUser($user);

        $lunette->setQuantite($lunette->getQuantite() - 1);

        $entityManager->persist($commande);
        $entityManager->persist($lunette);
        $entityManager->flush();

    $this->addFlash('success', 'Commande effectuée avec succès.');
    return $this->redirectToRoute('app_default', ['success' => 1]);
    }
}
