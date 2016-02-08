<?php
namespace Bencoder\DrivingTest\Service;


class DateFilter
{
    /**
     * Returns date strings for dates before date string $before
     *
     * @param string[] $dates
     * @param string $before
     *
     * @return string[]
     */
    public function filterDates($dates, $before)
    {
        $filterDateTime = new \DateTime($before);
        $dates = array_filter($dates, function ($date) use ($filterDateTime) {
            return (new \DateTime($date)) <= $filterDateTime;
        });
        return $dates;
    }
}