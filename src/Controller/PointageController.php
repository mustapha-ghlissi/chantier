<?php

namespace App\Controller;

use App\Entity\Pointage;
use App\Form\PointageType;
use App\Repository\PointageRepository;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pointage")
 */
class PointageController extends AbstractController
{
    /**
     * @Route("/", name="pointage_index", methods={"GET"})
     */
    public function index(PointageRepository $pointageRepository): Response
    {
        return $this->render('pointage/index.html.twig', [
            'pointages' => $pointageRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="pointage_new", methods={"GET","POST"})
     */
    public function new(Request $request, PointageRepository $pointageRepository): Response
    {
        $pointage = new Pointage();
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $alreadyPointed = $pointageRepository->getOneBy([
                'utilisateur' => $pointage->getUtilisateur()->getId(),
                'chantier' => $pointage->getChantier()->getId(),
                'date' => (new \DateTime())->format('Y-m-d')
            ]);

            if($alreadyPointed instanceof Pointage) {
                $this->addFlash('danger', 'Cet utilisateur a déjà pointé aujourd\'hui');
                return $this->redirect($request->headers->get('referer'));
            }

            $weekStart = Carbon::today()->modify('this week');
            $weekEnd = Carbon::today()->modify('this week +6 days');
            $dureePointee = $pointageRepository->getPointageByWeek([
                'utilisateur' => $pointage->getUtilisateur()->getId(),
                'weekStartDate' => $weekStart->format('Y-m-d'),
                'weekEndDate' => $weekEnd->format('Y-m-d'),
            ]);

            if($dureePointee && $dureePointee + $pointage->getDuree() > 35)
            {
                $this->addFlash('danger', 'Votre pointage ne peut pas dépasser 35 heures pour une semaine.');
                return $this->redirect($request->headers->get('referer'));
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($pointage);
            $entityManager->flush();

            return $this->redirectToRoute('pointage_index');
        }

        return $this->render('pointage/new.html.twig', [
            'pointage' => $pointage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pointage_show", methods={"GET"})
     */
    public function show(Pointage $pointage): Response
    {
        return $this->render('pointage/show.html.twig', [
            'pointage' => $pointage,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="pointage_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Pointage $pointage): Response
    {
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('pointage_index');
        }

        return $this->render('pointage/edit.html.twig', [
            'pointage' => $pointage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pointage_delete", methods={"POST"})
     */
    public function delete(Request $request, Pointage $pointage): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pointage->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($pointage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('pointage_index');
    }
}
