<?php

namespace Innologi\Decosdata\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Migrate Command
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MigrateCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription(
            'decospublisher => decosdata migration',
        )->addOption(
            'table-source',
            't',
            InputOption::VALUE_NONE,
            'Override source-extension requirement if tables exist',
        );
        // @LOW setHelp()
        // @LOW addUsage()
        // @LOW disableSimulateArg?
    }

    /**
     * Executes the command for adding the lock file
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $output->setDecorated(true);
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $arguments = [
            'overrideSourceRequirement' => (bool) $input->getOption('table-source'),
        ];

        try {
            $controller = GeneralUtility::makeInstance(
                Controller\MigrateController::class,
                $io,
                $arguments,
            );
            // @extensionScannerIgnoreLine false positive
            $controller->main();
            return 0;
        } catch (\Exception $e) {
            // @extensionScannerIgnoreLine false positive
            $io->error('[' . $e->getCode() . '] ' . $e->getMessage());
            return 1;
        }
    }
}
