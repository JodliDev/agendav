<?php

namespace AgenDAV\Event;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

use AgenDAV\Event\RecurrenceId;

/**
 * Interface to generate new Events and EventInstances
 */

interface Builder
{
    /**
     * Creates an empty Event object
     *
     * @param string $uid UID for this event
     * @return \AgenDAV\Event
     */
    public function createEvent($uid);

    /**
     * Creates an empty EventInstance object
     *
     * @param \AgenDAV\Event $event Event this instance will be attached to
     * @param \AgenDAV\Event\RecurrenceId $recurrence_id
     * @return \AgenDAV\EventInstance
     * @throws \LogicException If $event has no UID assigned
     */
    public function createEventInstanceFor(\AgenDAV\Event $event, RecurrenceId $recurrence_id = null);

    /**
     * Creates an EventInstance object after receiving an array of properties
     * with the following keys:
     *
     * summary
     * location
     * start
     * end
     * timezone
     * allday
     * rrule
     * description
     * class
     * transp
     * recurrence-id
     *
     * @param \AgenDAV\Event $event Parent event
     * @param array $attributes
     * @return \AgenDAV\EventInstance
     */
    public function createEventInstanceWithInput(\AgenDAV\Event $event, array $attributes);
}
