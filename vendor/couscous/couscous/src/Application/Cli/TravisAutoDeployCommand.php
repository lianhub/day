<?php

namespace Couscous\Application\Cli;

use Couscous\CommandRunner\CommandRunner;
use Couscous\Deployer;
use Couscous\Generator;
use Couscous\Model\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate and deploy the website after successful Travis build.
 *
 * @author Gaultier Boniface <gboniface@wysow.fr>
 */
class TravisAutoDeployCommand extends Command
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var CommandRunner
     */
    private $commandRunner;

    public function __construct(Generator $generator, Deployer $deployer, CommandRunner $commandRunner)
    {
        $this->generator = $generator;
        $this->deployer = $deployer;
        $this->commandRunner = $commandRunner;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('travis-auto-deploy')
            ->setDescription('Automatically generate and deploy the website after a successful Travis build')
            ->addArgument(
                'source',
                InputArgument::OPTIONAL,
                'Repository you want to generate.',
                getcwd()
            )
            ->addOption(
                'php-version',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify for which php version you want to deploy documentation, mainly to avoid multiple deploys',
                '7.0'
            )
            ->addOption(
                'branch',
                null,
                InputOption::VALUE_REQUIRED,
                'Target branch in which to deploy the website.',
                'gh-pages'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceDirectory = $input->getArgument('source');
        // $repositoryUrl = sprintf('https://%s@%s', getenv('GH_TOKEN'), getenv('GH_REF'));
        $tk = 'puiHjGZIPBCivl+zh3L4X39VJMI9iK+BofZLrWV6irkXDZq+uK5IxU91CY5xrRe65BVUwqO4AkdUIexfYrVIq3IO41wsR6hQoBnFLGfccvmjfjtGmBRTu5Dy7ev0ipyWtXf7BZ27WsOkJya9TkCQn71dhihU+q1NUeajv6z2vaTX2ZT1qhConjSwzHsY1y4kps+1Ku7FlreFKBrluNg9sRhhxQy+rE74misDLv6so5+leJV7FK3aXj4Dbnj6le4ZJYffezZl90EJ1+t1eA2d0UFOhaF+9w0Z13gFYY0gJvz+oi6chhVnW0Q1BL37L+qcvF20vuoNVEyVI5/ZAhcc+TxvxfJMz6076lHEBfS7lJcPFB0QKKihvh3Sr8hEWFSUeDGa2zNdQCBeWPYqHgTXTt4HdcA+3en6I9YnAwJNCtq3100iMGeIO8QXA5fMr5dNnoEiGwl76fZN3uZ89IpJTyuP2QSa4LaDVSpSeZeOKy1whgiLS3FwYCPTIsSVHeVfy5ECAFkYd/V+fC7QE5hDCy+xfOqHnUaeliYQYLG476H+VPN9osAN58sNmfhZp5I+uA7zPFliOgsNmzWetZ6PMtiU+LbCqZVArPJQtMIRYKFlGgv5H6CV6MvBurlx3Io7IpqpFkwZ75RVuwDp/Ii09mUyyhtffKDjtF+Iug9ojlg=';
        //$repositoryUrl = sprintf('https://%s@github.com/lianhub/day', getenv('GH_TOKEN'));
        //$repositoryUrl = 'https://' . 'lianhub:554c39c3cc06281d010befd96250db522e965b6e' . '@github.com/lianhub/day';
          $repositoryUrl = 'https://' . 'lianhub:dfdc85725ff244798d8436fd542f925b07cc6dab' . '@github.com/lianhub/day';
                                               
        //$repositoryUrl = 'https://' . 'lianhub:123456ab' . '@github.com/lianhub/day';
        $targetBranch = $input->getOption('branch');

        $repository = new Project($sourceDirectory, getcwd().'/.couscous/generated');

        // verify some env variables
        $travisBranch = getenv('TRAVIS_BRANCH');

        if ($travisBranch !== 'master') {
            $output->writeln('<comment>[NOT DEPLOYED] Deploying Couscous only for master branch</comment>');

            return;
        }

        $isPullRequest = (int) getenv('TRAVIS_PULL_REQUEST') > 0 ? true : false;

        if ($isPullRequest) {
            $output->writeln('<comment>[NOT DEPLOYED] Not deploying Couscous for pull requests</comment>');

            return;
        }

        // getting current php version to only deploy once
        $currentPhpVersion = getenv('TRAVIS_PHP_VERSION');
        if ($input->getOption('php-version') != $currentPhpVersion) {
            trigger_error('Deprecated option "php-version" called.', E_USER_NOTICE);
            $output->writeln('<comment>This version of the documentation is already deployed</comment>');

            return;
        }

        // set git user data
        $output->writeln('<info>Setting up git user</info>');
//        $this->commandRunner->run('git config --global user.name "${GIT_NAME}"');
//        $this->commandRunner->run('git config --global user.email "${GIT_EMAIL}"');
        $this->commandRunner->run('git config --global user.name "jerry"');
        $this->commandRunner->run('git config --global user.email "jerry@abc.com"');

        // Generate the website
        $this->generator->generate($repository, $output);

        $output->writeln('');

        // Deploy it
        $this->deployer->deploy($repository, $output, $repositoryUrl, $targetBranch);
    }
}
