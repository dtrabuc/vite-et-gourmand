<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use InvalidArgumentException;

final readonly class AdminService
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
        private MenuStatisticsService $menuStatisticsService,
        private PasswordPolicy $passwordPolicy,
        private PhoneValidator $phoneValidator
    ) {
    }

    public function createEmployee(array $data): int
    {
        $password = (string) ($data['password'] ?? '');
        $errors = $this->passwordPolicy->validate($password);

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        // même règle que pour les clients : numéros validés puis stockés en E.164
        $phones = [];
        foreach (['phone' => 'Téléphone', 'gsm' => 'GSM'] as $field => $label) {
            $value = trim((string) ($data[$field] ?? ''));
            $error = $this->phoneValidator->validate($value);

            if ($error !== null) {
                throw new InvalidArgumentException($label . ' : ' . $error);
            }

            $phones[$field] = $this->phoneValidator->normalize($value);
        }

        return $this->userRepository->create([
            'email' => $email,
            'password' => $this->passwordPolicy->hash($password),
            'role' => 'employee',
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'phone' => $phones['phone'],
            'gsm' => $phones['gsm'],
            'address' => trim((string) $data['address']),
        ]);
    }

    public function getCustomers(?string $search = null, ?bool $active = null): array
    {
        return $this->userRepository->findCustomers($search, $active);
    }

    public function disableCustomer(int $id): void
    {
        $this->userRepository->setCustomerActive($id, false);
    }

    public function enableCustomer(int $id): void
    {
        $this->userRepository->setCustomerActive($id, true);
    }

    public function getEmployees(): array
    {
        return $this->userRepository->findEmployees();
    }

    public function disableEmployee(int $id): void
    {
        $this->userRepository->setActive($id, false);
    }

    public function enableEmployee(int $id): void
    {
        $this->userRepository->setActive($id, true);
    }

    public function getDashboardStats(): array
    {
        $totalUsers = $this->userRepository->countAll();
        $totalOrders = $this->orderRepository->countAll();
        $pendingOrders = $this->orderRepository->countByStatus('pending');
        $totalRevenue = $this->orderRepository->sumCompletedRevenue();

        $menuStats = [];
        $menuStatsError = null;

        try {
            $statistics = $this->menuStatisticsService->getStatistics('all_time');

            foreach ($statistics as $stat) {
                $menuStats[] = [
                    'menu_id' => (string) $stat['menuId'],
                    'menu_title' => $stat['menuTitle'],
                    'order_count' => (int) $stat['orderCount'],
                    'revenue' => (float) $stat['revenue'],
                ];
            }
        } catch (\Throwable $exception) {
            error_log(
                'Statistiques MongoDB indisponibles : '
                . $exception->getMessage()
            );
            $menuStatsError = 'Les statistiques de commandes par menu sont momentanément indisponibles.';
        }

        return [
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'total_revenue' => $totalRevenue,
            'menu_stats' => $menuStats,
            'menu_stats_error' => $menuStatsError,
        ];
    }

    public function getRevenueByMenu(
        ?string $from = null,
        ?string $to = null,
        ?int $menuId = null
    ): array {
        return array_map(
            static fn (array $row): array => [
                'menu_id' => (int) $row['menu_id'],
                'menu_title' => $row['menu_title'],
                'order_count' => (int) $row['order_count'],
                'revenue' => (float) $row['revenue'],
            ],
            $this->orderRepository->revenueByMenu($from, $to, $menuId)
        );
    }
}
