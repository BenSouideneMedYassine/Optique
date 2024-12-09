<?php

namespace App\Controller;

use App\Entity\Lunette;
use App\Entity\Commande;
use App\Form\LunetteType;
use App\Repository\LunetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/lunette')]
class LunetteController extends AbstractController
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'app_lunette_index', methods: ['GET'])]
    public function index(LunetteRepository $lunetteRepository): Response
    {
        return $this->render('lunette/index.html.twig', [
            'lunettes' => $lunetteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_lunette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lunette = new Lunette();
        $form = $this->createForm(LunetteType::class, $lunette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('photo')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename); 
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'), 
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                $lunette->setPhoto($newFilename);
            }

            $entityManager->persist($lunette);
            $entityManager->flush();

            return $this->redirectToRoute('app_lunette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lunette/new.html.twig', [
            'lunette' => $lunette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lunette_show', methods: ['GET'])]
    public function show(Lunette $lunette): Response
    {
        return $this->render('lunette/show.html.twig', [
            'lunette' => $lunette,
        ]);
    }
    // #[Route('/{id}/edit', name: 'app_lunette_edit', methods: ['GET', 'POST'])]
    // public function edit(Request $request, Lunette $lunette, EntityManagerInterface $entityManager): Response
    // {
    //     $form = $this->createForm(LunetteType::class, $lunette);
    //     $form->handleRequest($request);
    
    //     $originalPhoto = $lunette->getPhoto();
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $newFilename = $form->get('photo')->getData();
    
    //         if ($imageFile) {
    //             // Supprimez l'ancienne image si elle existe
    //             if ($lunette->getPhoto()) {
    //                 $oldImagePath = $this->getParameter('images_directory') . '/' . $lunette->getPhoto();
    //                 if (file_exists($oldImagePath)) {
    //                     unlink($oldImagePath);
    //                 }
    //             }
    
    //             $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
    //             $safeFilename = $this->slugger->slug($originalFilename); 
    //             $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
    //             try {
    //                 $imageFile->move(
    //                     $this->getParameter('images_directory'),
    //                     $newFilename
    //                 );
    //             } catch (FileException $e) {
    //                 // Gérer l'erreur si nécessaire
    //             }
    
    //             $lunette->setPhoto($newFilename);
    //         }
    
    //         $entityManager->flush();
    
    //         return $this->redirectToRoute('app_lunette_index', [], Response::HTTP_SEE_OTHER);
    //     }
    
    //     return $this->renderForm('lunette/edit.html.twig', [
    //         'lunette' => $lunette,
    //         'form' => $form,
    //     ]);
    // }
    #[Route('/{id}/edit', name: 'app_lunette_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Lunette $lunette, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(LunetteType::class, $lunette);
    $form->handleRequest($request);

    // Stocker l'ancienne photo
    $originalPhoto = $lunette->getPhoto();

    if ($form->isSubmitted() && $form->isValid()) {
        // Récupérer le fichier photo soumis dans le formulaire
        $imageFile = $form->get('photo')->getData();

        if ($imageFile) {
            if ($originalPhoto) {
                $oldImagePath = $this->getParameter('images_directory') . '/' . $originalPhoto;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
            }

            $lunette->setPhoto($newFilename);
        } else {
            $lunette->setPhoto($originalPhoto);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_lunette_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->renderForm('lunette/edit.html.twig', [
        'lunette' => $lunette,
        'form' => $form,
    ]);
}

    
    // #[Route('/{id}', name: 'app_lunette_delete', methods: ['POST'])]
    // public function delete(Request $request, Lunette $lunette, EntityManagerInterface $entityManager): Response
    // {
    //     if ($this->isCsrfTokenValid('delete'.$lunette->getId(), $request->request->get('_token'))) {
    //         $entityManager->remove($lunette);
    //         $entityManager->flush();
    //     }

    //     return $this->redirectToRoute('app_lunette_index', [], Response::HTTP_SEE_OTHER);
    // }
    #[Route('/{id}', name: 'app_lunette_delete', methods: ['POST'])]
public function delete(Request $request, Lunette $lunette, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$lunette->getId(), $request->request->get('_token'))) {
        // Find and remove all commandes related to this lunette
        $commandes = $entityManager->getRepository(Commande::class)->findBy(['lunette' => $lunette]);
        foreach ($commandes as $commande) {
            $entityManager->remove($commande);
        }

        // Now remove the lunette
        $entityManager->remove($lunette);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_lunette_index', [], Response::HTTP_SEE_OTHER);
}

}
