<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Workflow;

use Automate\Logger\ConsoleLogger;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use Automate\Workflow;
use phpseclib\Net\SSH2;
use Prophecy\Argument;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployTest extends AbstractContextTest
{
    public function testRemoteDeploy()
    {
        $io = $this->prophesize(SymfonyStyle::class);
        $logger = new ConsoleLogger($io->reveal());

        $ssh = $this->prophesize(SSH2::class);
        $ssh->setTimeout(0)->shouldBeCalled();
        $ssh->getExitStatus()->willReturn(0);
        $ssh->exec(Argument::any())->shouldBeCalled();

        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger);
        $workflow = new Workflow\Deployer($context);

        $releaseId = $context->getReleaseId();
        $currentDir = $context->getPlatform()->getServers()[0]->getCurrentDir();

        $ssh->exec("mkdir -p /home/wwwroot/automate/demo/releases/$releaseId")->shouldBeCalled();
        $ssh->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master .")->shouldBeCalled();
        $ssh->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; php -v")->shouldBeCalled();
        $ssh->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; composer install")->shouldBeCalled();
        $ssh->exec("ln -sfn /home/wwwroot/automate/demo/releases/$releaseId /home/wwwroot/automate/demo/".$currentDir)->shouldBeCalled();


        $rs = $workflow->deploy();

        $this->assertTrue($rs);

    }

    public function testError()
    {
        $io = $this->prophesize(SymfonyStyle::class);
        $logger = new ConsoleLogger($io->reveal());

        $ssh = $this->prophesize(SSH2::class);
        $ssh->setTimeout(0)->shouldBeCalled();
        $ssh->getExitStatus()->willReturn(1);
        $ssh->exec(Argument::any())->shouldBeCalled();

        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger);
        $workflow = new Workflow\Deployer($context);

        $rs = $workflow->deploy();

        $this->assertFalse($rs);
    }

    public function testCheckout()
    {
        $logger = $this->prophesize(ConsoleLogger::class);

        $ssh = $this->prophesize(SSH2::class);
        $ssh->setTimeout(0)->shouldBeCalled();
        $ssh->getExitStatus()->willReturn(0);
        $ssh->exec(Argument::any())->shouldBeCalled();

        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal(), 'master');
        $workflow = new Workflow\Deployer($context);

        $rs = $workflow->deploy('1.0.0');

        $this->assertTrue($rs);
    }

    public function testLocalDeploy()
    {
        $io = $this->prophesize(SymfonyStyle::class);
        $logger = new ConsoleLogger($io->reveal());

        $context = $this->createLocalContext($logger);

        $workflow = new Workflow\Deployer($context);
        $this->assertFalse($workflow->deploy());
    }
}
