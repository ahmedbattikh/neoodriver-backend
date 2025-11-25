<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Driver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly EntityManagerInterface $em) {}
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Administration');
    }

    public function configureAssets(): Assets
    {
        // Layout override loads Bootstrap, Adminator CSS, and icon fonts.
        // Only include our local tweaks via assets to allow cache-busting if needed.
        return Assets::new()
            ->addCssFile('/css/adminator-overrides.css');
    }

    // Optional: show icons in the main menu using Font Awesome (loaded by layout override)
    // Add more menu items as needed.
    // use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
    public function configureMenuItems(): iterable
    {
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToRoute('Add User (Wizard)', 'fa fa-user-plus', 'admin_user_wizard', ['step' => 1]);
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToRoute('Users', 'fa fa-users', 'admin_users_list');
    }

    #[Route('/admin/users', name: 'admin_users_list')]
    public function users(): Response
    {
        $users = $this->em->getRepository(User::class)->findBy([], ['id' => 'DESC']);
        return $this->render('admin/users_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function userShow(int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function userEdit(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class)
            ->add('firstName', TextType::class, ['required' => false])
            ->add('lastName', TextType::class, ['required' => false])
            ->add('mobileNumber', TextType::class, ['required' => false])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            return $this->redirectToRoute('admin_users_list');
        }
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/{id}/toggle/{active}', name: 'admin_user_toggle', requirements: ['active' => '\\d+'], methods: ['GET'])]
    public function userToggle(int $id, int $active): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if ($user instanceof User) {
            $driver = $user->getDriverProfile();
            if (!$driver instanceof Driver) {
                $driver = new Driver();
                $driver->setUser($user);
                $this->em->persist($driver);
            }
            $driver->setActive($active === 1);
            $this->em->flush();
        }
        return $this->redirectToRoute('admin_users_list');
    }
}