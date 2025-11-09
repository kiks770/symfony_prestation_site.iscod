<?php
namespace App\Controller;

use App\Entity\Prestation;
use App\Form\PrestationType;
use App\Repository\PrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/prestation')]
class PrestationController extends AbstractController
{
    #[Route('/', name: 'prestation_index')]
    public function index(PrestationRepository $repo): Response
    {
        $prestations = $repo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('prestation/index.html.twig', ['prestations' => $prestations]);
    }

    #[Route('/mine', name: 'prestation_mine')]
    public function mine(PrestationRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $prestations = $repo->findByUser($this->getUser());
        return $this->render('prestation/mine.html.twig', ['prestations' => $prestations]);
    }

    #[Route('/new', name: 'prestation_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $prestation = new Prestation();
        $form = $this->createForm(PrestationType::class, $prestation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $prestation->setUser($this->getUser());
            $em->persist($prestation);
            $em->flush();
            $this->addFlash('success', 'Prestation créée.');
            return $this->redirectToRoute('prestation_index');
        }

        return $this->render('prestation/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}', name: 'prestation_show')]
    public function show(Prestation $prestation): Response
    {
        $user = $this->getUser();
        if (!$user || $prestation->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('prestation_index');
        }
        return $this->render('prestation/show.html.twig', ['prestation' => $prestation]);
    }

    #[Route('/{id}/edit', name: 'prestation_edit')]
    public function edit(Request $request, Prestation $prestation, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($prestation->getUser()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Non autorisé.');
            return $this->redirectToRoute('prestation_index');
        }
        $form = $this->createForm(PrestationType::class, $prestation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Modifié.');
            return $this->redirectToRoute('prestation_mine');
        }
        return $this->render('prestation/edit.html.twig', ['form' => $form->createView(), 'prestation' => $prestation]);
    }

    #[Route('/{id}', name: 'prestation_delete', methods: ['POST'])]
    public function delete(Request $request, Prestation $prestation, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($prestation->getUser()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Non autorisé.');
            return $this->redirectToRoute('prestation_index');
        }
        if ($this->isCsrfTokenValid('delete'.$prestation->getId(), $request->request->get('_token'))) {
            $em->remove($prestation);
            $em->flush();
            $this->addFlash('success', 'Supprimé.');
        }
        return $this->redirectToRoute('prestation_mine');
    }
}
