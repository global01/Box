<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command;

use KevinGH\Box\Box;
use RecursiveDirectoryIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extracts one or more files from a PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Info extends Command
{
    /**
     * Recognized compression algorithms.
     *
     * @var array
     */
    private $compression = array(
        Box::BZ2 => 'BZ2',
        Box::GZ => 'GZ',
        Box::TAR => 'TAR',
        Box::ZIP => 'ZIP'
    );

    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('info');
        $this->setDescription('Displays PHAR information.');

        $this->addOption(
            'list',
            'l',
            InputOption::VALUE_NONE,
            'List the contents?'
        );

        $this->addArgument(
            'phar',
            InputArgument::OPTIONAL,
            'The PHAR to view.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $box = $this->getHelper('box');

        $box->setOutput($output);

        if ($file = $input->getArgument('phar')) {
            $phar = new Box($file);

            $box->putln('FILE', $file);
            $box->putln('INFO', '<comment>API:</comment> v' . $phar->getVersion());
            $box->putln('INFO', sprintf(
                '<comment>Compression:</comment> %s',
                $phar->isCompressed() ? $this->compression[$phar->isCompressed()] : 'None'
            ));

            $signature = $phar->getSignature();

            $box->putln('INFO', '<comment>Signature:</comment> ' . $signature['hash_type']);

            if ($input->getOption('list')) {
                $show = function (SplFileInfo $info, $indent = 0) use ($box, &$show) {
                    $box->putln(
                        'LIST',
                        str_repeat(' ', $indent)
                            . $info->getFilename()
                            . ($info->isDir() ? DIRECTORY_SEPARATOR : '')
                    );

                    if ($info->isDir()) {
                        $iterator = new RecursiveDirectoryIterator(
                            $info->getPath() . DIRECTORY_SEPARATOR . $info->getFilename()
                        );

                        foreach ($iterator as $subInfo) {
                            $show($subInfo, $indent + 2);
                        }
                    }
                };

                foreach ($phar as $info) {
                    $show($info);
                }
            }
        } else {
            $box->putln('PHAR', 'v' . Box::apiVersion());

            $box->putln('INFO', sprintf(
                '<comment>Compression Algorithms:</comment> %s',
                join(', ', Box::getSupportedCompression())
            ));

            $box->putln('INFO', sprintf(
                '<comment>Signature Algorithms:</comment> %s',
                join(', ', Box::getSupportedSignatures())
            ));
        }
    }
}

