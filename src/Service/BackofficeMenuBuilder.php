<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BackofficeMenuBuilder
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function build(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'url' => $this->urlGenerator->generate('backoffice'),
            ],
            [
                'label' => 'Users',
                'url' => $this->urlGenerator->generate('backoffice_users_list'),
            ],
            [
                'label' => 'Unverified Users',
                'url' => $this->urlGenerator->generate('backoffice_users_unverified'),
            ],
            [
                'label' => 'Import Users',
                'url' => $this->urlGenerator->generate('backoffice_users_import'),
            ],
            [
                'label' => 'Configuration',
                'children' => [
                    [
                        'label' => 'Integration',
                        'url' => $this->urlGenerator->generate('backoffice_integrations_index'),
                    ],
                    [
                        'label' => 'Goals',
                        'url' => $this->urlGenerator->generate('backoffice_goals_index'),
                    ],
                    [
                        'label' => 'Neoo Config',
                        'url' => $this->urlGenerator->generate('backoffice_neoo_config_index'),
                    ],
                    [
                        'label' => 'Neoo Fees',
                        'url' => $this->urlGenerator->generate('backoffice_neoo_fees_index'),
                    ],
                ],
            ],
        ];
    }
}
