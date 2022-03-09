<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;

class DatabaseService
{
    public function __construct(
        private KernelInterface $kernel
    ) {}

    public function createDatabase(): bool
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:database:create'
        ]);

        try {
            $application->run($input);
        } catch (\Exception $e) {
            return false;
        }

        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true
        ]);

        try {
            $application->run($input);
        } catch (\Exception $e) {
            return false;
        }

        $input = new ArrayInput([
            'command' => 'doctrine:fixtures:load',
            '--append' => true
        ]);

        try {
            $application->run($input);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}