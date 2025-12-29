<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Models\EventModel;
use Myhealth\Models\AccountModel;

class EventController
{
    public function viewLogs()
    {
        $usernames = (new AccountModel())->getAllUsernames();

        $events = Request('event');

        if ($events == '') {
            $selectedEvents = ['Any'];
        }
        else {
            $selectedEvents = (!is_array($events) ? [$events] : $events);
        }

        if (count($selectedEvents) > 1 && in_array('Any', $selectedEvents)) {
            unset($selectedEvents);
            $selectedEvents = ['Any'];
        }

        $limit = (int) Request('limit');

        $filter = (object) [
            'user' => Request('user'),
            'from' => Request('from'),
            'to' => Request('to'),
            'events' => $selectedEvents,
            'limit' => ($limit > 0 ? $limit : 200),
        ];

        $eventDaos = (new EventModel())->getEvents(
            $filter->user,
            $filter->from,
            $filter->to,
            $filter->events,
            $filter->limit
        );

        $passInData = [
            'errorMsg' => '',
            'filter' => &$filter,
            'usernames' => &$usernames,
            'eventDaos' => &$eventDaos,
        ];

        View::render('view-logs', 'View Logs', $passInData);
    }
}