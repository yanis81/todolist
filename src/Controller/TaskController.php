<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    // ✅ Route pour la page d'accueil -> Redirige vers /task/
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('task_index');
    }

    // 🟢 Lister toutes les tâches avec un filtre "à faire"
    #[Route('/task/', name: 'task_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérifier si l'utilisateur veut afficher uniquement les tâches à faire
        $showOnlyPending = $request->query->get('filter') === 'pending';

        // Récupérer les tâches, en filtrant si nécessaire
        $tasks = $entityManager->getRepository(Task::class)->findBy(
            $showOnlyPending ? ['isDone' => false] : []
        );

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'showOnlyPending' => $showOnlyPending,
        ]);
    }

    // 🟢 Ajouter une tâche
    #[Route('/task/new', name: 'task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // 🟠 Modifier une tâche
    #[Route('/task/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if (!$task) {
            throw $this->createNotFoundException('Cette tâche n\'existe pas.');
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    // 🔴 Supprimer une tâche
    #[Route('/task/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if (!$task) {
            throw $this->createNotFoundException('Cette tâche n\'existe pas.');
        }

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();
        }

        return $this->redirectToRoute('task_index');
    }
}