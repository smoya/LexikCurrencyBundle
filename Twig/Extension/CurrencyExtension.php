<?php

namespace Lexik\Bundle\CurrencyBundle\Twig\Extension;

use InvalidArgumentException, NumberFormatter;
use Lexik\Bundle\CurrencyBundle\Adapter\AdapterCollector;
use Lexik\Bundle\CurrencyBundle\Converter\Converter;
use Lexik\Bundle\CurrencyBundle\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Session;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Yoann Aparici <y.aparici@lexik.fr>
 */
class CurrencyExtension extends \Twig_Extension
{

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var Session
     */
    private $session;

    /**
     * Construct.
     *
     * @param Session $session
     * @param Converter $converter
     */
    public function __construct(Session $session, Converter $converter)
    {
        $this->session = $session;
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            'currency_format'   => new \Twig_Filter_Method($this, 'currencyFormat'),
        );
    }

    /**
     * Format price
     *
     * @param mixed $value
     * @param string $code - target code
     * @param boolean $decimal
     * @param boolean $symbol
     * @param string $valueCode - the $value's code
     * @return string
     */
    public function currencyFormat($value, $code, $decimal = true, $symbol = true, $valueCode = null)
    {
        $formatter = new NumberFormatter($this->session->getLocale(), $symbol ? NumberFormatter::CURRENCY : NumberFormatter::PATTERN_DECIMAL);

        try {
            $value = $this->converter->convert($value, $code, !$decimal, $valueCode);
        } catch (NotFoundException $e) {
            $code = $this->converter->getDefaultCurrency();
        }

        $value = $formatter->formatCurrency($value, $code);

        if (!$decimal) {
            $value = preg_replace('/[.,]00((?=\D)|$)/','',$value);
        }

        $value = str_replace(array('EU', 'UK', 'US'), '', $value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'lexik_currency.currency_extension';
    }
}