<?php

/*
 * Copyright 2011-2014 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Data\Preferences;
use AgenDAV\DateHelper;
use AgenDAV\CalDAV\Resource\Calendar;

class Prefs extends MY_Controller
{

    private $prefs;
    private $preferences_repository;
    private $username;
    private $client;

    public function __construct()
    {
        parent::__construct();

        // Force authentication
        if (!$this->container['session']->isAuthenticated()) {
            redirect('/login');
        }

        $this->username = $this->container['session']->get('username');
        $this->preferences_repository = $this->container['preferences_repository'];
        $this->client = $this->container['caldav_client'];


        // Preferences
        $this->prefs = $this->preferences_repository->userPreferences($this->username);
    }

    public function index()
    {
        // Layout components
        $components = array();
        $title = $this->config->item('site_title');

        $data_header = array(
                'title' => $title,
                'logged_in' => true,
                'username' => $this->username,
                'body_class' => array('prefspage'),
                );

        $data_calendar = array();
        $logo = $this->config->item('logo');
        $data_calendar['logo'] = custom_logo($logo, $title);
        $data_calendar['title'] = $title;

        $components['header'] =
            $this->load->view('common_header', $data_header, true);

        $components['navbar'] =
            $this->load->view('navbar', $data_header, true);


        // Calendar list
        // TODO show shared calendars too
        $calendar_home = $this->container['session']->get('calendar_home_set');
        $calendar_list = $this->client->getCalendars($calendar_home);

        // TODO refactor this part
        $hidden_calendars = $this->prefs->hidden_calendars;
        if ($hidden_calendars === null) {
            $hidden_calendars = array();
        }

        // Default calendar
        $default_calendar = $this->prefs->default_calendar;

        // User timezone
        $timezone = $this->prefs->get('timezone', $this->config->item('default_timezone'));
        $available_timezones = DateHelper::getAllTimeZones();

        $calendar_ids_and_dn = array();
        foreach ($calendar_list as $cal) {
            $calendar_ids_and_dn[$cal->getUrl()] = $cal->getProperty(Calendar::DISPLAYNAME);
        }

        $data_prefs = array(
                'calendar_list' => $calendar_list,
                'calendar_ids_and_dn' => $calendar_ids_and_dn,
                'default_calendar' => $default_calendar,
                'timezone' => $timezone,
                'available_timezones' => $available_timezones,
                'hidden_calendars' => $hidden_calendars,
                );

        $components['content'] = $this->load->view('preferences_page',
                $data_prefs, true);
        $components['footer'] = $this->load->view('footer',
                array(
                    'load_session_refresh' => true,
                    ), true);

        $this->load->view('layouts/plain.php', $components);
    }

    /**
     * Settings currently processed by this action:
     *  - calendar@form: hidden_calendars
     *  - default_calendar@form: default_calendar
     */
    function save() {
        $calendar = $this->input->post('calendar', true);
        $default_calendar = $this->input->post('default_calendar', true);
        $timezone = $this->input->post('timezone', true);

        if ($calendar === false) {
            $calendar = [];
        }

        if ($default_calendar === FALSE) {
            log_message('ERROR',
                'Preferences save attempt with default_calendar not set');
            $this->_throw_error($this->i18n->_('messages',
                        'error_interfacefailure'));
        }

        if ($timezone === FALSE) {
            log_message('ERROR',
                'Preferences save attempt with timezone not set');
            $this->_throw_error($this->i18n->_('messages',
                        'error_interfacefailure'));
        }

        $current_prefs = $this->preferences_repository->userPreferences($this->username);

        // Default calendar
        $current_prefs->default_calendar = $default_calendar;

        // Timezone
        $current_prefs->timezone = $timezone;

        // Calendar processing
        $hidden_calendars = array();

        foreach ($calendar as $url => $settings) {
            if (isset($settings['hide'])) {
                $hidden_calendars[$url] = true;
            }
        }

        $current_prefs->hidden_calendars = $hidden_calendars;

        // Save preferences
        $this->preferences_repository->save($this->username, $current_prefs);

        $this->container['session']->set('prefs', $current_prefs->getAll());
        $this->_throw_success();
    }

    // TODO: refactor these methods and caldav2json ones into a single library
    /**
     * Throws an exception message
     */
    function _throw_exception($message) {
        $this->output->set_output(json_encode(array(
                        'result' => 'EXCEPTION',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws an error message
     */
    function _throw_error($message) {
        $this->output->set_output(json_encode(array(
                        'result' => 'ERROR',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws a success message
     */
    function _throw_success($message = '') {
        $this->output->set_output(json_encode(array(
                        'result' => 'SUCCESS',
                        'message' => $message)));
        $this->output->_display();
        die();
    }
}
