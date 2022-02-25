<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

final class DurationWeek extends AbstractCounterDuration
{
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_WEEK], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_other_timesheet'];
    }

    public function getId(): string
    {
        return 'durationWeek';
    }

    public function getTitle(): string
    {
        return 'stats.durationWeek';
    }

    public function getData(array $options = [])
    {
        $this->setQueryWithUser(false);
        $this->setBegin('monday this week 00:00:00');
        $this->setEnd('sunday this week 23:59:59');

        return parent::getData($options);
    }
}
