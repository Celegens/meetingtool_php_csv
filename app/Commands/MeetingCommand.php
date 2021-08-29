<?php

namespace App\Commands;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class MeetingCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'meeting'; //or via arguments {startdate} {interval}{enddate}

    /**
     * Generate a csv-file with all meeting dates from the start date till the end date.
     *
     * @var string
     */
    protected $description = 'Generate a csv-file with all meeting dates from the start date till the end date.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->info('Welcome to the meeting tool.');
        $startdate = $this->getValidStartDate();
        $interval = $this->getValidInteger();
        $enddate = $this->getValidEndDate($startdate);

        $daysToExclude =  $this->generateDaysToExclude($startdate, $enddate);

        $meetings = [];
        $currentdate = $startdate;
        $i = 1;
        while ($currentdate->copy()->addDays($interval)->isBefore($enddate)) {
            //The meeting dates should be on each n-th day, based on the given interval
            while (true) {
                //A meeting can’t be on the 25/12 or 1/1   || A meeting can’t be in the weekend ||  A meeting can’t be on the 5th of 15th of each month
                if (in_array($currentdate->toDateString(), $daysToExclude) || $currentdate->isWeekend() ||  $currentdate->day == 5  || $currentdate->day == 15) {
                    //Saturday and Sunday should not be counted as days (all excluded aren't counted as days aswel)
                    $currentdate = $currentdate->addDay();
                } else {
                    break;
                }
            }
            $meetings[] =  [$i++,  $currentdate->copy()->toDateString(), $currentdate->englishDayOfWeek]; // 
            $currentdate = $currentdate->addDays($interval);
        }

        $this->createCsv($meetings);
        $this->info('a csv has been created with all the avaible meeting days.');
    }


    /**
     * Generates the days to be excluded in between the given startdate and enddate
     * 
     * @param \Carbon\Carbon $startdate
     * @param \Carbon\Carbon $enddate
     * @return array of of days to be excluded as array of strings
     */
    private function generateDaysToExclude($startdate, $enddate)
    {

        $daysToExclude = [];
        $startYear = $startdate->year;
        $endYear = $enddate->year;
        $currentYear = $startYear;
        //Adept the day and month to match the years inbetween the start- & enddate
        while ($currentYear <= $endYear) {
            $daysToExclude[] = Carbon::createFromFormat('d/m/Y', '25/12/' . $currentYear)->toDateString();
            $daysToExclude[] = Carbon::createFromFormat('d/m/Y', '01/01/' . $currentYear)->toDateString();

            $currentYear += 1;
        }
        return $daysToExclude;
    }
    // Helper functions
    /**
     * Gets a valid startdate from user input.
     * 
     * @return \Carbon\Carbon the valid date
     */
    private function getValidStartDate(): Carbon
    {
        $valid_date = false;
        $date = null;
        while (!$valid_date) {
            $date = $this->ask('Enter a startdate');
            try {
                if (Carbon::createFromFormat('d/m/Y', $date)->format('d/m/Y') === $date) {
                    $valid_date = true;
                } else {
                    $this->info("Please pass a valid date in te given format dd/mm/yyyy (ex. 20/05/2021)");
                }
            } catch (InvalidFormatException $exception) {
                $this->info("Please pass a valid date in te given format dd/mm/yyyy (ex. 20/05/2021)");
            }
        }
        return Carbon::createFromFormat('d/m/Y', $date);
    }
    /**
     * Gets a valid integer from user input.
     * 
     * @return int 
     */
    private function getValidInteger()
    {
        $valid_int = false;
        while (!$valid_int) {

            $int = $this->ask('Enter an interval:');
            if (is_numeric($int) &&  ((int)$int) == $int) {
                $valid_int = true;
            } else {
                $this->info("Pleasea valid integer ex. 1");
            }
        }
        return (int) ($int);
    }
    /**
     * Gets a valid enddate from the users input given the startdate.
     * 
     * @param \Carbon\Carbon $startdate 
     * @return \Carbon\Carbon the valid enddate
     */
    private function getValidEndDate($startdate): Carbon
    {
        $valid_date = false;
        $date = null;
        while (!$valid_date) {
            $date = $this->ask('Enter a end date');
            try {
                if (Carbon::createFromFormat('d/m/Y', $date)->format('d/m/Y') === $date) {
                    if (Carbon::createFromFormat('d/m/Y', $date)->isAfter($startdate)) {
                        $this->info('Enddate is valid');
                        $valid_date = true;
                    } else {
                        $this->info("The enddate needs to be after the startdate(" . $startdate . ")");
                    }
                } else {
                    $this->info("Please pass a valid date in te given format dd/mm/yyyy (ex. 20/05/2021)");
                }
            } catch (InvalidFormatException $exception) {
                $this->info("Please pass a valid date in te given format dd/mm/yyyy (ex. 20/05/2021)");
            }
        }
        return Carbon::createFromFormat('d/m/Y', $date);
    }
    private function createCsv($meetings)
    {
        $file = fopen('meetings.csv', 'w');
        foreach ($meetings as $meeting) {
            fputcsv($file, $meeting);
        }
        fclose($file);
    }
}