<?php

namespace Calendar;

use Calendar\CalendarInterface;
use DateTimeInterface;
use DateTime;

class Calendar implements CalendarInterface {

  const DAYS_PER_WEEK = 7;

  const STANDARD_WEEKS_PER_YEAR = 52;

  /**
   * @var DateTimeInterface
   */
  private $currentDate;

  /**
   * @var int
   */
  private $firstWeekDay;

  /**
   * @var int
   */
  private $numberOfDaysInPreviousMonth;

  /**
   * @var int
   */
  private $firstWeek;

  /**
   * @var int
   */
  private $lastWeek;

  /**
   * @var int
   */
  private $yearWeeksNumber;

  /**
   * @var boolean
   */
  private $weekHighlighted;

  /**
   * @param DateTimeInterface $datetime
   */
  public function __construct(DateTimeInterface $datetime) {
    $this->currentDate = $datetime;
  }

  /**
   * Get the calendar array
   *
   * @return array
   */
  public function getCalendar() {
    $calendarOutput = array();
    $calendarRows = $this->getCalendarRowsNumber();

    $startDay = 1;
    $startWeek = $this->getFirstWeek();

    for($i = 0; $i < $calendarRows; $i++) {
      $calendarWeek = $this->drawCalendarWeek($startDay);
      $calendarOutput[$startWeek] = $calendarWeek;

      $startDay = $this->getStartDayOfNextWeek($calendarWeek);

      $startWeek = $this->setNextWeekNumber($startWeek);
    }

    return $calendarOutput;
  }

  /**
   * Get the number of rows necessary to draw a month
   * @return int
   */
  private function getCalendarRowsNumber() {
    if($this->getFirstWeek() >= self::STANDARD_WEEKS_PER_YEAR) {
      $numberOfWeeks = $this->getLastWeek() + 1;
    } else {
      $numberOfWeeks = ($this->getLastWeek() - $this->getFirstWeek()) + 1;
    }

    return $numberOfWeeks;
  }

  private function getStartDayOfNextWeek($currentWeek) {
    $keysOfCalendarWeek = array_keys($currentWeek);
    $startDay = $keysOfCalendarWeek[count($keysOfCalendarWeek) - 1] + 1;

    return $startDay;
  }

  private function setNextWeekNumber($currentWeek) {
    if($currentWeek >= $this->getYearWeeksNumber()) {
      $nextWeek = 1;
    } else {
      $nextWeek = $currentWeek + 1;
    }

    return $nextWeek;
  }

  /**
   * Draw a calendar week
   * @param  int $startDay the starting date (day) of the week
   * @return array
   */
  private function drawCalendarWeek($startDay) {
    $weekOutput = array();
    $start = 1;
    $this->setWeekHighlighted(true);

    $daysOfPreviousMonth = $this->setDaysFromPreviousMonth($startDay);
    if(!empty($daysOfPreviousMonth)) {
      $weekOutput = $daysOfPreviousMonth;
      $start = $this->getFirstWeekDay();
    }

    for($i = $start; $i <= self::DAYS_PER_WEEK; $i++) {
      $startDay = $this->checkOverlapOnNextMonth($startDay);
      $weekOutput[$startDay] = false;

      $startDay++;
    }

    $weekOutput = $this->highlightWeek($startDay, $weekOutput);

    return $weekOutput;
  }

  /**
   * As the first day of the month may not be on a Monday, set the previous days
   * @param int $startDay
   */
  private function setDaysFromPreviousMonth($startDay) {
    $previousDaysOutput = array();

    if($startDay == 1 && $this->getFirstWeekDay() > 1) {
      $start = $this->getFirstWeekDay();

      $previousMonthDaysNumber = $this->getNumberOfDaysInPreviousMonth();
      $startPreviousMonth = $previousMonthDaysNumber - ($start - 2);

      for($i = $startPreviousMonth; $i <= $previousMonthDaysNumber; $i++) {
        $previousDaysOutput[$i] = false;
      }
    }

    return $previousDaysOutput;
  }

  /**
   * Check if the current day is bigger than the last day of the month and reset it to 1
   * block the highlight of week for the current month
   * @param  int $startDay
   * @return int
   */
  private function checkOverlapOnNextMonth($startDay) {
    if($startDay > $this->getNumberOfDaysInThisMonth()) {
      $startDay = 1;
      $this->setWeekHighlighted(false);
    }

    return $startDay;
  }

  /**
   * If the currentDate is next week, highlight the current week
   * @param  int $lastDayCurrentWeek
   * @param  array $weekOutput
   * @return array
   */
  private function highlightWeek($lastDayCurrentWeek, $weekOutput) {
    if($this->isWeekHighlighted() &&
      $this->getDay() >= $lastDayCurrentWeek && $this->getDay() <= ($lastDayCurrentWeek + (self::DAYS_PER_WEEK - 1))) {
      $weekOutput = array_map(function($day){
        return !$day;
      }, $weekOutput);
    }

    return $weekOutput;
  }

  /**
   * Get the day of the month without leading zeros 1 - 31
   *
   * @return int
   */
  public function getDay() {
    return (int) $this->currentDate->format('j');
  }

  public function getWeekDay() {
    $weekDay = $this->currentDate->format('N');
    return (int) $weekDay;
  }

  public function getFirstWeekDay() {
    if($this->firstWeekDay === null) {
      $firstDayOfTheMonth = new DateTime($this->getDateStringFormat());
      $firstDayOfTheMonth->modify('first day of this month');
      $this->firstWeekDay = (int) $firstDayOfTheMonth->format('N');
    }

    return $this->firstWeekDay;
  }

  public function getFirstWeek() {
    if($this->firstWeek === null) {
      $firstDayOfTheMonth = new DateTime($this->getDateStringFormat());
      $firstDayOfTheMonth->modify('first day of this month');
      $this->firstWeek = (int) $firstDayOfTheMonth->format('W');
    }

    return $this->firstWeek;
  }

  /**
   * Get the last week of this month
   */
  public function getLastWeek() {
    if($this->lastWeek === null) {
      $lastDayOfTheMonth = new DateTime($this->getDateStringFormat());
      $lastDayOfTheMonth->modify('last day of this month');
      $this->lastWeek = (int) $lastDayOfTheMonth->format('W');
    }

    return $this->lastWeek;
  }

  public function getNumberOfDaysInThisMonth() {
    return (int) $this->currentDate->format('t');
  }

  public function getNumberOfDaysInPreviousMonth() {
    if($this->numberOfDaysInPreviousMonth === null) {
      $lastDayOfPreviousMonth = new DateTime($this->getDateStringFormat());
      $lastDayOfPreviousMonth->modify('last day of -1 month');
      $this->numberOfDaysInPreviousMonth = (int) $lastDayOfPreviousMonth->format('t');
    }

    return $this->numberOfDaysInPreviousMonth;
  }

  public function getYearWeeksNumber() {
    if($this->yearWeeksNumber === null) {
      $firstDayOfTheMonth = new DateTime($this->getDateStringFormat());
      $firstDayOfTheMonth->modify('last day of december');
      $this->yearWeeksNumber = (int) $firstDayOfTheMonth->format('W');
    }

    return $this->yearWeeksNumber;
  }

  public function getDateStringFormat() {
    return $this->currentDate->format('Y-m-d');
  }

  public function setWeekHighlighted($highlighted) {
    $this->weekHighlighted = $highlighted;
  }

  /**
   * Check if it is still possible to highlit a week for the current month
   * @return boolean
   */
  public function isWeekHighlighted() {
    return $this->weekHighlighted;
  }
}
