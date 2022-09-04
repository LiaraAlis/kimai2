<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Configuration\LocaleService;
use App\Entity\Timesheet;
use DateTime;
use Exception;
use IntlDateFormatter;
use NumberFormatter;
use Symfony\Component\Intl\Currencies;

/**
 * Use this class to format values into locale specific representations.
 */
final class LocaleFormatter
{
    private ?Duration $durationFormatter = null;
    private ?IntlDateFormatter $dateFormatter = null;
    private ?IntlDateFormatter $dateTimeFormatter = null;
    private ?IntlDateFormatter $timeFormatter = null;
    private ?NumberFormatter $numberFormatter = null;
    private ?NumberFormatter $decimalFormatter = null;
    private ?NumberFormatter $moneyFormatter = null;
    private ?NumberFormatter $moneyFormatterNoCurrency = null;

    public function __construct(private LocaleService $localeService, private string $locale)
    {
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|string|Timesheet|null $duration
     * @param bool $decimal
     * @return string
     */
    public function duration($duration, $decimal = false)
    {
        if ($decimal) {
            return $this->durationDecimal($duration);
        }

        return $this->formatDuration(
            $this->getSecondsForDuration($duration),
            $this->localeService->getDurationFormat($this->locale)
        );
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int|Timesheet|null $duration
     * @return string
     */
    public function durationDecimal(int|Timesheet|null $duration)
    {
        if (null === $this->numberFormatter) {
            $this->decimalFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
            $this->decimalFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        }

        $seconds = $this->getSecondsForDuration($duration);

        $value = round($seconds / 3600, 2);
        if ($value <= 0) {
            $value = 0;
        }

        return $this->decimalFormatter->format((float) $value);
    }

    /**
     * @param string|int|Timesheet|null $duration
     * @return int
     */
    private function getSecondsForDuration(string|int|Timesheet|null $duration): int
    {
        if (null === $duration) {
            return 0;
        }

        if ($duration instanceof Timesheet) {
            if (null === $duration->getEnd()) {
                $duration = time() - $duration->getBegin()->getTimestamp();
            } else {
                $duration = $duration->getDuration();
            }
        }

        return (int) $duration;
    }

    private function formatDuration(int $seconds, string $format): string
    {
        if ($this->durationFormatter === null) {
            $this->durationFormatter = new Duration();
        }

        return $this->durationFormatter->format($seconds, $format);
    }

    /**
     * Used in twig filter |amount and invoice templates.
     *
     * @param int|float $amount
     * @return bool|false|string
     */
    public function amount(null|int|float|string $amount): bool|string
    {
        if ($amount === null) {
            return '0';
        }

        if (null === $this->numberFormatter) {
            $this->numberFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        }

        return $this->numberFormatter->format($amount);
    }

    /**
     * Returns the currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public function currency(string $currency): string
    {
        try {
            return Currencies::getSymbol(strtoupper($currency), $this->locale);
        } catch (\Exception $ex) {
        }

        return $currency;
    }

    public function money(null|int|float $amount, ?string $currency = null, bool $withCurrency = true): string
    {
        if (null === $currency) {
            $withCurrency = false;
        }

        if ($amount === null) {
            $amount = 0;
        }

        if (false === $withCurrency) {
            if (null === $this->moneyFormatterNoCurrency) {
                $this->moneyFormatterNoCurrency = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::POSITIVE_PREFIX, '');
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::POSITIVE_SUFFIX, '');
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::NEGATIVE_PREFIX, '-');
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::NEGATIVE_SUFFIX, '');
            }

            return $this->moneyFormatterNoCurrency->format($amount, NumberFormatter::TYPE_DEFAULT);
        }

        if (null === $this->moneyFormatter) {
            $this->moneyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        }

        return $this->moneyFormatter->formatCurrency($amount, $currency);
    }

    public function dateShort(\DateTimeInterface|string|null $date): string
    {
        if (null === $this->dateFormatter) {
            $this->dateFormatter = new IntlDateFormatter(
                $this->locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $this->localeService->getDateFormat($this->locale)
            );
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date ?? 'now');
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $this->dateFormatter->format($date);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateTime($date)
    {
        if (null === $this->dateTimeFormatter) {
            $this->dateTimeFormatter = new IntlDateFormatter(
                $this->locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $this->localeService->getDateTimeFormat($this->locale)
            );
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $this->dateTimeFormatter->format($date);
    }

    public function dateFormat(\DateTimeInterface|string $date, string $format): bool|string
    {
        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $date->format($format);
    }

    /**
     * @param DateTime|string $date
     * @return string
     * @throws Exception
     */
    public function time(\DateTimeInterface|string $date): string
    {
        if (null === $this->timeFormatter) {
            $this->timeFormatter = new IntlDateFormatter(
                $this->locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $this->localeService->getTimeFormat($this->locale)
            );
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $this->timeFormatter->format($date);
    }

    /**
     * @see https://framework.zend.com/manual/1.12/en/zend.date.constants.html#zend.date.constants.selfdefinedformats
     * @see http://userguide.icu-project.org/formatparse/datetime
     *
     * @param DateTime $dateTime
     * @param string $format
     * @return string
     */
    private function formatIntl(\DateTime $dateTime, string $format): string
    {
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $dateTime->getTimezone()->getName(),
            IntlDateFormatter::GREGORIAN,
            $format
        );

        return $formatter->format($dateTime);
    }

    public function monthName(\DateTime $dateTime, bool $withYear = false): string
    {
        return $this->formatIntl($dateTime, ($withYear ? 'LLLL yyyy' : 'LLLL'));
    }

    public function dayName(\DateTime $dateTime, bool $short = false): string
    {
        return $this->formatIntl($dateTime, ($short ? 'EE' : 'EEEE'));
    }
}
