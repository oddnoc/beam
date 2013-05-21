<?php

namespace Heyday\Component\Beam\Command;

use Heyday\Component\Beam\Deployment\DeploymentResult;
use Heyday\Component\Beam\Deployment\Rsync;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RsyncCommand
 * @package Heyday\Component\Beam\Command
 */
class RsyncCommand extends BeamCommand
{
    /**
     * @var
     */
    protected $deploymentProvider;
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('rsync')
            ->setDescription('A deployment tool using rsync')
            ->addOption(
                'no-checksum',
                '',
                InputOption::VALUE_NONE,
                'Performs a faster file change check'
            )
            ->addOption(
                'delete',
                '',
                InputOption::VALUE_NONE,
                'Use with caution, deletes items that don\'t exist at the target'
            )
            ->addOption(
                'no-compress',
                '',
                InputOption::VALUE_NONE,
                'Removes compression'
            )
            ->addOption(
                'no-delay-updates',
                '',
                InputOption::VALUE_NONE,
                'Transfers as it runs not all at the end'
            )->addOption(
                'owner',
                '',
                InputOption::VALUE_NONE,
                'Transfer file and directory owners'
            )->addOption(
                'group',
                '',
                InputOption::VALUE_NONE,
                'Transfer file and directory groups'
            );
    }
    /**
     * @param  InputInterface $input
     * @return array
     */
    protected function getOptions(InputInterface $input, OutputInterface $output)
    {
        $options = parent::getOptions($input, $output);
        $options['deploymentprovider'] = $this->deploymentProvider = new Rsync(
            array(
                'checksum'      => !$input->getOption('no-checksum'),
                'delete'        => $input->getOption('delete'),
                'compress'      => !$input->getOption('no-compress'),
                'delay-updates' => !$input->getOption('no-delay-updates'),
                'owner'         => $input->getOption('owner'),
                'group'         => $input->getOption('group')
            )
        );

        return $options;
    }
    /**
     * @{inheritDoc}
     */
    protected function getDeploymentOutputHandler(
        OutputInterface $output,
        ProgressHelper $progressHelper,
        FormatterHelper $formatterHelper,
        DeploymentResult $deploymentResult
    ) {
        $count = count($deploymentResult);
        $deploymentProvider = $this->deploymentProvider;

        return function (
            $type,
            $data
        ) use (
            $output,
            $progressHelper,
            $formatterHelper,
            $count,
            $deploymentProvider
        ) {
            static $totalSteps = 0;
            static $buffer = '';
            if ($totalSteps == 0) {
                $progressHelper->setAutoWidth($count);
                // Start the progress bar
                $progressHelper->start($output, $count, 'File: ');
            }
            if ($type == 'out') {
                // add the current data to the buffer
                $buffer .= $data;
                // get the pos of any last newline
                $pos = strrpos($buffer, PHP_EOL);
                // there isn't a last newline then skip and continue filling the buffer
                if ($pos !== false) {
                    // get the changes from the start of the buffer to the last newline
                    $changes = $deploymentProvider->formatOutput(substr($buffer, 0, $pos));
                    foreach ($changes as $change) {
                        // update the progress bar and increment the steps
                        $progressHelper->advance(1, false, $change['filename']);
                        $totalSteps++;
                    }
                    // clear the processed buffer keeping anything after the last newline
                    $buffer = ltrim(substr($buffer, $pos), PHP_EOL);
                }
                // Check if we have finished (rsync stops outputing data
                // before things have entirely finished)
                if ($totalSteps >= $count) {
                    $progressHelper->finish();
                    $output->writeln(
                        $formatterHelper->formatSection(
                            'info',
                            'Finalizing deployment'
                        )
                    );
                }
            } elseif ($type == 'err') {
                $output->write(
                    $formatterHelper->formatSection(
                        'error',
                        $data,
                        'error'
                    )
                );
            }
        };
    }
}
