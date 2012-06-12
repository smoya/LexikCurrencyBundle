<?php

namespace Lexik\Bundle\CurrencyBundle\Command;

use Lexik\Bundle\CurrencyBundle\Entity\Currency;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Yoann Aparici <y.aparici@lexik.fr>
 */
class ImportCurrencyCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('lexik:currency:import')
            ->setDescription('Import currency rate')
            ->addArgument('adapter', InputArgument::REQUIRED, 'Adapter to import in database')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = $this->getContainer()
            ->get('lexik_currency.adapter_collector')
            ->get($input->getArgument('adapter'));
        $adapter->attachAll();

        // Persist currencies
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('Lexik\Bundle\CurrencyBundle\Entity\Currency');

        foreach ($adapter as $value) {
            // Check if already exist
            $currency = $repository->findOneBy(array(
                'code' => $value->getCode(),
            ));

            if (!$currency) {
                $currency = $value;
                $em->persist($currency);

                $output->writeln(sprintf('<comment>Add: %s = %s</comment>', $currency->getCode(), $currency->getRate()));
            } else {
                $currency->setRate($value->getRate());

                $output->writeln(sprintf('<comment>Update: %s = %s</comment>', $currency->getCode(), $currency->getRate()));
            }
        }

        $em->flush();
    }
}